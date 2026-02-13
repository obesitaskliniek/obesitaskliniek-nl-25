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

	/**
	 * The original queried object (page/post being viewed) for token replacement.
	 * Captured before any WP_Query manipulation to ensure tokens resolve
	 * against the actual page context, not the page_part being rendered.
	 */
	private ?\WP_Post $context_post = null;

	public function __construct() {
		$this->context = new RenderContext();
	}

	/**
	 * Register WordPress hooks for token replacement
	 */
	public function register_hooks(): void {
		// Process tokens in page part titles
		add_filter('the_title', [$this, 'process_title_tokens'], 10, 2);

		// Process tokens in all rendered content (catches tokens in layout templates, blocks, etc.)
		add_filter('the_content', [$this, 'process_content_tokens_filter'], 20);
	}

	/**
	 * Filter callback for the_content to process tokens
	 *
	 * Runs after block rendering (priority 20) to catch tokens in:
	 * - Layout template content
	 * - Regular Gutenberg blocks (headings, paragraphs)
	 * - Any other content that goes through the_content
	 *
	 * @param string $content The post content
	 * @return string Content with tokens replaced
	 */
	public function process_content_tokens_filter(string $content): string {
		// Only process if content contains tokens (performance optimization)
		if (strpos($content, '{{') === false) {
			return $content;
		}

		return $this->process_content_tokens($content);
	}

	/**
	 * Process tokens in page part post titles
	 *
	 * Applies token replacement to page_part post titles wherever they're displayed.
	 * Only processes page_part post type to avoid overhead on other content.
	 *
	 * @param string $title Post title
	 * @param int $post_id Post ID
	 * @return string Title with tokens replaced
	 */
	public function process_title_tokens(string $title, int $post_id): string {
		// Only process page_part post type
		if (get_post_type($post_id) !== 'page_part') {
			return $title;
		}

		// Use existing process_content_tokens() method
		return $this->process_content_tokens($title);
	}

	/**
	 * Include a page part template with standardized setup
	 * Used for frontend rendering and high-level template inclusion
	 *
	 * NOTE: Global $post restoration for nested page parts
	 * =====================================================
	 * This method saves and restores the original global $post rather than
	 * just calling wp_reset_postdata(). This is important because:
	 *
	 * 1. wp_reset_postdata() restores to the main query's post, not the
	 *    previous post in a nested rendering scenario.
	 *
	 * 2. When page parts are nested (a page part embedding another page part),
	 *    the inner part would corrupt the outer part's $post context if we
	 *    only used wp_reset_postdata().
	 *
	 * 3. By saving $original_post before manipulation and restoring it after,
	 *    nested page parts work correctly with each level maintaining its
	 *    own post context.
	 */
	public function include_page_part_template(string $design, array $args = []): void {
		// Capture original queried object BEFORE any query/post manipulation
		// This ensures token replacement resolves against the page being viewed
		$queried_object = get_queried_object();
		$this->context_post = ($queried_object instanceof \WP_Post) ? $queried_object : null;

		// Save original post for proper restoration (supports nested page parts)
		global $post;
		$original_post = $post;

		// Standardized setup that every template needs
		$post = $args['post'] ?? null;
		setup_postdata($post);

		// Include the actual template
		$this->render_page_part($design, $args['page_part_fields'] ?? [], $args['generic_overrides'] ?? []);

		// Restore original post (not just reset to main query post)
		$post = $original_post;
		if ($original_post) {
			setup_postdata($original_post);
		} else {
			wp_reset_postdata();
		}

		// Clear context post after rendering
		$this->context_post = null;
	}

	/**
	 * Render a page part template directly
	 * Used for embedding and preview scenarios
	 *
	 * @param string $design Design slug
	 * @param array $fields Field values
	 * @param array $generic_overrides Generic overrides for title/content
	 */
	public function render_page_part(string $design, array $fields, array $generic_overrides = []): void {
		$this->render_template('page-parts', $design, $fields, $generic_overrides);
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

		// Capture original queried object BEFORE replacing $wp_query
		// This ensures token replacement resolves against the page being viewed
		$queried_object = get_queried_object();
		$this->context_post = ($queried_object instanceof \WP_Post) ? $queried_object : null;

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

		// Clear context post after rendering
		$this->context_post = null;

		return $output;
	}

	/**
	 * Render a post part template directly
	 */
	public function render_post_part(string $design, array $fields): void {
		$this->render_template('post-parts', $design, $fields);
	}

	/**
	 * Render a block part template and return HTML
	 *
	 * Used for Gutenberg blocks that render via block-parts templates.
	 * Handles CSS loading and creates FieldContext from block attributes.
	 *
	 * @param string $slug Block part template slug (e.g., 'general-section')
	 * @param array $fields Field values from block attributes
	 * @param array $extra Extra variables to pass to template (e.g., 'content', 'attributes')
	 * @return string Rendered HTML
	 *
	 * @example Render general-section block part
	 * $html = $renderer->render_block_part('general-section', $fields, [
	 *     'content' => $inner_blocks_content,
	 *     'attributes' => $block_attributes
	 * ]);
	 */
	public function render_block_part(string $slug, array $fields, array $extra = []): string {
		// Get defaults from block-parts registry
		$registry = (new Registry())->get_block_parts_registry();
		$template_data = $registry[$slug] ?? [];
		$defaults = [];

		foreach (($template_data['custom_fields'] ?? []) as $field) {
			if (!empty($field['default'])) {
				$defaults[$field['name']] = $field['default'];
			}
		}

		// Create FieldContext from fields
		$context = new FieldContext($fields, $defaults);

		// Build template path
		$template_path = get_theme_file_path("template-parts/block-parts/{$slug}.php");

		if (!file_exists($template_path)) {
			return '<p class="nok-bg-error nok-p-3">Error: ' . sprintf(
				esc_html__('Block part template "%s" not found!', THEME_TEXT_DOMAIN),
				esc_html($slug)
			) . '</p>';
		}

		// Handle CSS loading for block-parts
		$this->handle_template_css('block-parts', $slug);

		// Extract extra variables for template scope
		$content = $extra['content'] ?? '';
		$attributes = $extra['attributes'] ?? [];

		// Render template
		ob_start();
		echo "\n<!-- block-parts: {$slug} -->\n";
		include $template_path;
		return ob_get_clean();
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
	 *
	 * @param string $template_type Template type ('page-parts' or 'post-parts')
	 * @param string $design Design slug
	 * @param array $fields Field values
	 * @param array $generic_overrides Generic overrides for title/content
	 */
	private function render_template(string $template_type, string $design, array $fields, array $generic_overrides = []): void {
		// Build defaults from registry
		$registry      = ( new Registry() )->get_registry();
		$template_data = $registry[ $design ] ?? [];
		$defaults      = [];
		foreach ( ( $template_data['custom_fields'] ?? [] ) as $field ) {
			if ( ! empty( $field['default'] ) ) {
				$defaults[ $field['name'] ] = $field['default'];
			}
		}

		// Replace tokens in field values
		$fields = $this->replace_tokens( $fields );

		// Create context with generic overrides
		$context = new FieldContext($fields, $defaults, $generic_overrides);
		$template_path = get_theme_file_path( "template-parts/{$template_type}/{$design}.php" );

		if ( ! file_exists( $template_path ) ) {
			$this->render_template_error( $design, $template_type );

			return;
		}

		// Handle CSS based on context and template type
		$this->handle_template_css( $template_type, $design );

		// Output template identifier comment for debugging
		echo "\n<!-- {$template_type}: {$design} -->\n";
		if ($template_type === 'post-parts') {
			echo "<nok-post-part>\n";
		}

		include $template_path;

		if ($template_type === 'post-parts') {
			echo "</nok-post-part>\n";
		}

		// Fire action for SEO schema collection (frontend only)
		if ( $this->context->get_context() === RenderContext::CONTEXT_FRONTEND ) {
			$post_id = $fields['_post_id'] ?? 0;
			/**
			 * Fires after a page part is rendered on the frontend.
			 *
			 * @param int    $post_id Page part post ID (0 if not available)
			 * @param string $design  Template/design slug
			 * @param array  $fields  Field values passed to template
			 */
			do_action( 'nok_page_part_rendered', $post_id, $design, $fields );
		}
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
	 * Replace tokens in field values with dynamic content
	 *
	 * Supported tokens:
	 * - {{post_title}} - Current post title
	 * - {{post_meta:field_name}} - Post meta field value
	 * - {{vestiging_meta:field_name}} - Vestiging meta field value (via _behandeld_door or _parent_vestiging relationship)
	 *
	 * @param array $fields Field values to process
	 * @return array Fields with tokens replaced
	 */
	private function replace_tokens(array $fields): array {
		// Use stored context_post (captured before query manipulation) if available,
		// otherwise fall back to get_queried_object() for backward compatibility
		$post = $this->context_post ?? get_queried_object();

		// Only process if we have a post context
		if (!$post || !($post instanceof \WP_Post)) {
			return $fields;
		}

		$processed_fields = [];

		foreach ($fields as $key => $value) {
			// Only process string values
			if (!is_string($value)) {
				$processed_fields[$key] = $value;
				continue;
			}

			// Replace {{post_title}}
			$value = str_replace('{{post_title}}', $post->post_title, $value);

			// Replace {{post_meta:field_name}}
			$value = preg_replace_callback('/\{\{post_meta:([a-zA-Z0-9_-]+)\}\}/', function($matches) use ($post) {
				$field_name = $matches[1];
				$meta_key = '_' . $field_name;
				$meta_value = get_post_meta($post->ID, $meta_key, true);
				return $meta_value ?: '';
			}, $value);

			// Replace {{vestiging_meta:field_name}}
			$value = preg_replace_callback('/\{\{vestiging_meta:([a-zA-Z0-9_-]+)\}\}/', function($matches) use ($post) {
				$field_name = $matches[1];

				// Get related vestiging post ID: _behandeld_door (ervaringen) or _parent_vestiging (regio)
				$vestiging_id = get_post_meta($post->ID, '_behandeld_door', true)
				             ?: get_post_meta($post->ID, '_parent_vestiging', true);

				if (!$vestiging_id || !is_numeric($vestiging_id)) {
					return '';
				}

				// Get vestiging meta field
				$meta_key = '_' . $field_name;
				$meta_value = get_post_meta($vestiging_id, $meta_key, true);
				return $meta_value ?: '';
			}, $value);

			$processed_fields[$key] = $value;
		}

		return $processed_fields;
	}

	/**
	 * Process tokens in string content (public wrapper for page part content)
	 *
	 * @param string $content Content with tokens to process
	 * @return string Content with tokens replaced
	 */
	public function process_content_tokens(string $content): string {
		// Wrap in array for replace_tokens(), then unwrap
		$result = $this->replace_tokens(['content' => $content]);
		return $result['content'] ?? $content;
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