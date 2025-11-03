<?php
// inc/PageParts/TemplateRenderer.php

namespace NOK2025\V1\PageParts;

/**
 * TemplateRenderer - Handles page part and post part template rendering
 *
 * Responsible for:
 * - Rendering page-parts and post-parts templates
 * - Managing render contexts (frontend, editor preview, REST embed)
 * - Context-aware CSS loading (enqueue vs inline)
 * - Providing FieldContext to templates
 * - Managing WordPress postdata setup/reset
 * - Resolving minified CSS files in production
 * - Template error handling
 * - Supporting field value overrides
 *
 * Render contexts:
 * - CONTEXT_FRONTEND: Standard frontend rendering with wp_enqueue_style
 * - CONTEXT_PAGE_EDITOR_PREVIEW: Block editor preview with enqueue
 * - CONTEXT_POST_EDITOR_PREVIEW: Post editor preview with enqueue + inline
 * - CONTEXT_REST_EMBED: REST API context with inline CSS only
 *
 * Templates receive a $context variable (FieldContext) with methods:
 * - $context->get('field_name'): Get field value with default fallback
 * - $context->getOrFail('field_name'): Get required field or throw exception
 *
 * @example Basic page part rendering
 * $renderer = new TemplateRenderer();
 * $renderer->render_page_part('nok-hero', [
 *     'title' => 'Welcome',
 *     'subtitle' => 'To our site'
 * ]);
 *
 * @example Rendering with post context
 * $renderer->include_page_part_template('nok-cta', [
 *     'post' => $post_object,
 *     'page_part_fields' => $fields
 * ]);
 *
 * @example Rendering with overrides
 * $html = $renderer->render_page_part_with_context(
 *     $part_id,
 *     ['nok-hero_title' => 'Custom Title'],
 *     $meta_manager
 * );
 *
 * @package NOK2025\V1\PageParts
 */
class TemplateRenderer {
	private RenderContext $context;

	public function __construct() {
		$this->context = new RenderContext();
	}

	/**
	 * Include a page part template with standardized setup
	 * Used for frontend rendering and high-level template inclusion
	 */
	public function include_page_part_template(string $design, array $args = []): void {
		// Standardized setup that every template needs
		global $post;
		$post = $args['post'] ?? null;
		setup_postdata($post);

		// Include the actual template
		$this->render_page_part($design, $args['page_part_fields'] ?? []);

		wp_reset_postdata();
	}

	/**
	 * Render a page part template directly
	 * Used for embedding and preview scenarios
	 */
	public function render_page_part(string $design, array $fields): void {
		$this->render_template('page-parts', $design, $fields);
	}

	/**
	 * Render a page part with field overrides and return HTML
	 *
	 * Loads a page_part post, retrieves its fields, applies overrides,
	 * and returns the rendered HTML as a string. Manages WordPress query
	 * context to avoid interfering with the main loop.
	 *
	 * @param int $part_id Page part post ID
	 * @param array $overrides Field overrides keyed by meta_key (e.g., ['nok-hero_title' => 'New Title'])
	 * @param MetaManager $meta_manager MetaManager instance for field retrieval
	 * @return string Rendered HTML output
	 *
	 * @example Render with title override
	 * $html = $renderer->render_page_part_with_context(
	 *     123,
	 *     ['nok-hero_title' => 'Custom Title'],
	 *     $meta_manager
	 * );
	 * echo $html;
	 *
	 * @example Render without overrides
	 * $html = $renderer->render_page_part_with_context(456, [], $meta_manager);
	 */
	public function render_page_part_with_context(int $part_id, array $overrides, MetaManager $meta_manager): string {
		$post = get_post($part_id);
		if (!$post || $post->post_type !== 'page_part') {
			return '';
		}

		$design = get_post_meta($part_id, 'design_slug', true);
		if (!$design) {
			return '';
		}

		global $wp_query;
		$original_post = $GLOBALS['post'] ?? null;
		$original_query = $wp_query;

		$wp_query = new \WP_Query(['p' => $part_id, 'post_type' => 'page_part']);

		if ($wp_query->have_posts()) {
			$wp_query->the_post();
		}

		$page_part_fields = $meta_manager->get_page_part_fields($part_id, $design, false);

		if (!empty($overrides)) {
			$registry = \NOK2025\V1\Theme::get_instance()->get_page_part_registry();
			$template_data = $registry[$design] ?? [];
			$custom_fields = $template_data['custom_fields'] ?? [];

			foreach ($custom_fields as $field) {
				if (isset($overrides[$field['meta_key']]) && $overrides[$field['meta_key']] !== '') {
					$page_part_fields[$field['name']] = $overrides[$field['meta_key']];
				}
			}
		}

		ob_start();
		$this->render_page_part($design, $page_part_fields);
		$output = ob_get_clean();

		wp_reset_postdata();
		$GLOBALS['post'] = $original_post;
		$wp_query = $original_query;

		return $output;
	}

	/**
	 * Render a post part template directly
	 */
	public function render_post_part(string $design, array $fields): void {
		$this->render_template('post-parts', $design, $fields);
	}

	/**
	 * Legacy method names for backward compatibility
	 */
	public function embed_page_part_template(string $design, array $fields, bool $register_css = false): void {
		$this->render_page_part($design, $fields);
	}

	public function embed_post_part_template(string $design, array $fields, bool $register_css = false): void {
		$this->render_post_part($design, $fields);
	}

	/**
	 * Core template rendering logic
	 */
	private function render_template(string $template_type, string $design, array $fields): void {
		// Build defaults from registry
		$registry = (new Registry())->get_registry();
		$template_data = $registry[$design] ?? [];
		$defaults = [];
		foreach (($template_data['custom_fields'] ?? []) as $field) {
			if (!empty($field['default'])) {
				$defaults[$field['name']] = $field['default'];
			}
		}

		$context = new FieldContext($fields, $defaults);
		$template_path = get_theme_file_path("template-parts/{$template_type}/{$design}.php");

		if (!file_exists($template_path)) {
			$this->render_template_error($design, $template_type);
			return;
		}

		// Handle CSS based on context and template type
		$this->handle_template_css($template_type, $design);

		include $template_path;
	}

	/**
	 * Handle CSS loading based on context and template type
	 */
	private function handle_template_css(string $template_type, string $design): void {
		$theme = \NOK2025\V1\Theme::get_instance();
		$dev_mode = $theme->is_development_mode();

		// Try minified version first in production
		$css_info = $this->resolve_css_file($template_type, $design, $dev_mode);

		if (!$css_info) {
			return; // No CSS file found
		}

		switch ($this->context->get_context()) {
			case RenderContext::CONTEXT_FRONTEND:
				$this->handle_frontend_css($design, $css_info['url'], $css_info['version']);
				break;

			case RenderContext::CONTEXT_PAGE_EDITOR_PREVIEW:
				$this->handle_page_editor_css($design, $css_info['url'], $css_info['version']);
				break;

			case RenderContext::CONTEXT_POST_EDITOR_PREVIEW:
				$this->handle_post_editor_css($design, $css_info['url'], $css_info['version']);
				break;

			case RenderContext::CONTEXT_REST_EMBED:
				$this->handle_rest_embed_css($design, $css_info['url'], $css_info['version']);
				break;
		}
	}

	/**
	 * Handle CSS for frontend rendering
	 * Standard WordPress enqueue system
	 */
	private function handle_frontend_css(string $design, string $css_uri, int $version): void {
		wp_enqueue_style($design, $css_uri, [], $version);
	}

	/**
	 * Handle CSS for page editor preview context
	 * Standard enqueue - block editor handles asset loading
	 */
	private function handle_page_editor_css(string $design, string $css_uri, int $version): void {
		wp_enqueue_style($design, $css_uri, [], $version);
	}

	/**
	 * Handle CSS for post editor preview context
	 * Enqueue + inline output for immediate availability
	 */
	private function handle_post_editor_css(string $design, string $css_uri, int $version): void {
		wp_enqueue_style($design, $css_uri, [], $version);
		$this->output_inline_css_link($design, $css_uri);
	}

	/**
	 * Handle CSS for REST embed context
	 * Inline output only - no WordPress asset system available
	 */
	private function handle_rest_embed_css(string $design, string $css_uri, int $version): void {
		$this->output_inline_css_link($design, $css_uri);
	}

	/**
	 * Output CSS as inline link tag for immediate loading
	 */
	private function output_inline_css_link(string $design, string $css_uri): void {
		$handle = esc_attr($design . '-css');
		$url = esc_url($css_uri);
		echo "<link rel=\"stylesheet\" id=\"{$handle}\" href=\"{$url}\" media=\"all\" />\n";
	}

	/**
	 * Render template error message
	 */
	private function render_template_error(string $design, string $template_type): void {
		$readable_type = str_replace('-', ' ', $template_type);
		echo '<p class="nok-bg-error nok-p-3">Error: ' . sprintf(
				esc_html__('Template for "%s" %s not found!', THEME_TEXT_DOMAIN),
				esc_html($design),
				esc_html($readable_type)
			) . '</p>';
	}

	/**
	 * Get current render context (for debugging/logging)
	 */
	public function get_current_context(): string {
		return $this->context->get_context();
	}

	/**
	 * Resolve CSS file path, preferring minified version in production
	 * Returns array with 'url', 'version' or null if no file found
	 */
	private function resolve_css_file(string $template_type, string $design, bool $dev_mode): ?array {
		$base_path = "/template-parts/{$template_type}/{$design}";

		// Try minified version first in production
		if (!$dev_mode) {
			$minified_path = THEME_ROOT_ABS . $base_path . '.min.css';
			if (file_exists($minified_path)) {
				return [
					'url' => THEME_ROOT . $base_path . '.min.css',
					'version' => filemtime($minified_path)
				];
			}
		}

		// Fallback to regular version
		$regular_path = THEME_ROOT_ABS . $base_path . '.css';
		if (file_exists($regular_path)) {
			return [
				'url' => THEME_ROOT . $base_path . '.css',
				'version' => filemtime($regular_path)
			];
		}

		return null;
	}
}