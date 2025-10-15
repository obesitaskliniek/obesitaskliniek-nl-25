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
	public function embed_page_part_callback( \WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'page_part' ) {
			status_header( 404 );
			exit;
		}

		// Get all query parameters as potential overrides
		$query_params = $request->get_query_params();
		unset( $query_params['_locale'] ); // Remove WordPress internals

		$preview_state = get_transient( "preview_editor_state_{$id}" );
		$design        = $preview_state['meta']['design_slug'] ?? get_post_meta( $id, 'design_slug', true ) ?: 'nok-hero';

		// Set up meta filtering for the embed rendering if we have preview data
		if ( $preview_state && is_array( $preview_state ) ) {
			$this->setup_preview_filters( $id, $preview_state );
		}

		// Pass overrides to rendering
		$this->output_embed_html( $id, $design, $query_params );
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
	private function output_embed_html(int $id, string $design, array $overrides = []): void {
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
		$html .= '<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        <link href="'.THEME_ROOT.'/assets/fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
        </head><body>
        <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-top halign-center valign-center">
            <a href="' . $edit_link . '" type="button" target="_blank" class="nok-button nok-align-self-to-sm-stretch fill-group-column nok-bg-darkerblue nok-text-contrast no-shadow" tabindex="0">Bewerken</a>
        </nok-screen-mask>';

		$html .= $this->render_page_part_content($id, $design, $overrides);
		$html .= '</body></html>';
;
		print $html;
		exit;
	}

	/**
	 * Render the page part content within proper WordPress context
	 */
	private function render_page_part_content(int $id, string $design, array $overrides = []): string {
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

			// Apply featured image override if set
			if (isset($overrides['_override_thumbnail_id']) && $overrides['_override_thumbnail_id'] !== '') {
				add_filter('post_thumbnail_id', function($thumbnail_id, $post) use ($id, $overrides) {
					$check_id = is_object($post) ? $post->ID : $post;
					return ($check_id == $id) ? (int)$overrides['_override_thumbnail_id'] : $thumbnail_id;
				}, 10, 2);
			}

			$wp_query->the_post();

			ob_start();

			// Get base page part fields
			$page_part_fields = $this->meta_manager->get_page_part_fields($id, $design, false);

			// Apply overrides from query parameters
			if (!empty($overrides)) {
				$registry = \NOK2025\V1\Theme::get_instance()->get_page_part_registry();
				$template_data = $registry[$design] ?? [];
				$custom_fields = $template_data['custom_fields'] ?? [];

				foreach ($custom_fields as $field) {
					if ($field['page_editable']
					    && isset($overrides[$field['meta_key']])
					    && $overrides[$field['meta_key']] !== '') {

						// Sanitize based on field type
						$sanitize_callback = $this->meta_manager->get_sanitize_callback($field['type']);
						$page_part_fields[$field['name']] = call_user_func(
							$sanitize_callback,
							$overrides[$field['meta_key']]
						);
					}
				}
			}

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