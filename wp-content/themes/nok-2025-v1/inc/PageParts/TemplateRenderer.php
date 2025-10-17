<?php
// inc/PageParts/TemplateRenderer.php

namespace NOK2025\V1\PageParts;

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