<?php
// inc/PageParts/RestEndpoints.php

namespace NOK2025\V1\PageParts;

class RestEndpoints {
	private TemplateRenderer $renderer;
	private MetaManager $meta_manager;

	public function __construct(TemplateRenderer $renderer, MetaManager $meta_manager) {
		$this->renderer = $renderer;
		$this->meta_manager = $meta_manager;
	}

	public function register_hooks(): void {
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	/**
	 * Register custom REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'nok-2025-v1/v1',
			'/embed-page-part/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [$this, 'embed_page_part_callback'],
			]
		);
	}

	/**
	 * REST API callback for embedding page parts
	 * Only runs in the backend when editing a PAGE, embedding page parts
	 */
	public function embed_page_part_callback(\WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$post = get_post($id);

		if (!$post || $post->post_type !== 'page_part') {
			status_header(404);
			exit;
		}

		// Check for unified preview state
		$preview_state = get_transient("preview_editor_state_{$id}");
		$design = $preview_state['meta']['design_slug'] ?? get_post_meta($id, 'design_slug', true) ?: 'nok-hero';

		// Set up meta filtering for the embed rendering if we have preview data
		if ($preview_state && is_array($preview_state)) {
			$this->setup_preview_filters($id, $preview_state);
		}

		// Output complete HTML response
		$this->output_embed_html($id, $design);
	}

	/**
	 * Setup preview filters for meta and content
	 */
	private function setup_preview_filters(int $id, array $preview_state): void {
		add_filter('get_post_metadata', function ($value, $object_id, $meta_key) use ($id, $preview_state) {
			if ($object_id != $id) {
				return $value;
			}

			// Handle custom fields from unified preview state
			if (isset($preview_state['meta'][$meta_key])) {
				return [$preview_state['meta'][$meta_key]];
			}

			return $value;
		}, 10, 3);

		// Filter title and content for preview
		add_filter('the_title', function ($title, $post_id_filter) use ($id, $preview_state) {
			if ($post_id_filter == $id && isset($preview_state['title'])) {
				return $preview_state['title'];
			}

			return $title;
		}, 5, 2);

		add_filter('the_content', function ($content) use ($id, $preview_state) {
			if (get_the_ID() == $id && isset($preview_state['content'])) {
				return $preview_state['content'];
			}

			return $content;
		}, 5);
	}

	/**
	 * Output the complete HTML response for the embed
	 */
	private function output_embed_html(int $id, string $design): void {
		$css_uris = [
			'/assets/css/nok-components.css',
			'/assets/css/color_tests-v2.css',
			"/template-parts/page-parts/{$design}.preview.css",
			'/assets/css/nok-page-parts-editor-styles.css',
		];

		header('Content-Type: text/html; charset=utf-8');

		$html = '<!doctype html><html><head><meta charset="utf-8">';
		foreach ($css_uris as $uri) {
			if (file_exists(THEME_ROOT_ABS . $uri)) {
				$html .= '<link rel="stylesheet" href="' . esc_url(THEME_ROOT . $uri) . '">';
			}
		}

		$edit_link = admin_url("post.php?post={$id}&action=edit");
		$html .= '</head><body>
        <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-top halign-center valign-center">
            <a href="' . $edit_link . '" type="button" target="_blank" class="nok-button nok-align-self-to-sm-stretch fill-group-column nok-bg-darkerblue nok-text-contrast no-shadow" tabindex="0">Bewerken</a>
        </nok-screen-mask>';

		$html .= $this->render_page_part_content($id, $design);
		$html .= '</body></html>';

		print $html;
		exit;
	}

	/**
	 * Render the page part content within proper WordPress context
	 */
	private function render_page_part_content(int $id, string $design): string {
		// Store original state
		global $post, $wp_query;
		$original_post = $post;
		$original_wp_query = $wp_query;

		// Set up the post and pass it to template via $args
		$post = get_post($id);

		// Set up a proper WordPress query context
		$wp_query = new \WP_Query([
			'post_type'      => 'page_part',
			'p'              => $id,
			'posts_per_page' => 1
		]);

		$output = '';

		// Make sure we have the post in the loop
		if ($wp_query->have_posts()) {
			$wp_query->the_post(); // This sets up all the globals properly

			ob_start();

			// Get processed page part fields
			$page_part_fields = $this->meta_manager->get_page_part_fields($id, $design, false);

            // Use render_page_part for REST context (handles CSS properly)
            $this->renderer->render_page_part($design, $page_part_fields);

			$output = ob_get_clean();
			wp_reset_postdata();
		} else {
			$output = '<p style="color: red; padding: 20px;">Error: Could not load page part data</p>';
		}

		// Restore original state
		$post = $original_post;
		$wp_query = $original_wp_query;

		return $output;
	}
}