<?php
// inc/PageParts/RestEndpoints.php

namespace NOK2025\V1\PageParts;

class RestEndpoints {
	private TemplateRenderer $renderer;
	private MetaManager $meta_manager;
	private ?\NOK2025\V1\SEO\ContentAggregator $content_aggregator = null;

	public function __construct(
		TemplateRenderer $template_renderer,
		MetaManager $meta_manager,
		?\NOK2025\V1\SEO\ContentAggregator $content_aggregator = null
	) {
		$this->renderer           = $template_renderer;
		$this->meta_manager       = $meta_manager;
		$this->content_aggregator = $content_aggregator;
	}

	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register custom REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route( 'nok-2025-v1/v1', '/embed-page-part/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'embed_page_part_callback' ],
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

		// Render the page part HTML
		$rendered_html = $this->render_page_part_html( $id, $design, $query_params );

		// Extract semantic content for Yoast
		$semantic_content = $this->extract_semantic_content( $rendered_html );

		// Pass overrides to rendering (now includes semantic content)
		$this->output_embed_html( $id, $design, $query_params, $rendered_html, $semantic_content );
	}

	/**
	 * Extract semantic content from HTML for SEO analysis
	 *
	 * Extracts headings, paragraphs, lists, and image alt text.
	 * Returns plain text suitable for Yoast SEO content analysis.
	 *
	 * @param string $html Rendered HTML content
	 *
	 * @return string Extracted semantic text
	 */
	private function extract_semantic_content( string $html ): string {
		if ( empty( $html ) ) {
			return '';
		}

		// Skip if contains raw Gutenberg blocks
		if ( strpos( $html, '<!-- wp:' ) !== false ) {
			return '';
		}

		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$doc->loadHTML(
			mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		$content_parts = [];

		// Extract headings (h1-h6)
		for ( $i = 1; $i <= 6; $i ++ ) {
			$headings = $doc->getElementsByTagName( "h{$i}" );
			foreach ( $headings as $heading ) {
				$text = trim( $heading->textContent );
				if ( ! empty( $text ) ) {
					$content_parts[] = $text;
				}
			}
		}

		// Extract paragraphs
		$paragraphs = $doc->getElementsByTagName( 'p' );
		foreach ( $paragraphs as $p ) {
			$text = trim( $p->textContent );
			if ( ! empty( $text ) ) {
				$content_parts[] = $text;
			}
		}

		// Extract list items
		$lists = $doc->getElementsByTagName( 'li' );
		foreach ( $lists as $li ) {
			$text = trim( $li->textContent );
			if ( ! empty( $text ) ) {
				$content_parts[] = $text;
			}
		}

		// Extract images (preserve as HTML img tags for Yoast)
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $img ) {
			$src = $img->getAttribute( 'src' );
			$alt = $img->getAttribute( 'alt' );

			if ( ! empty( $src ) ) {
				// Create simplified img tag that Yoast can detect
				$img_tag = '<img src="' . esc_url( $src ) . '"';
				if ( ! empty( $alt ) ) {
					$img_tag .= ' alt="' . esc_attr( $alt ) . '"';
				}
				$img_tag .= ' />';

				$content_parts[] = $img_tag;
			}
		}

		return implode( "\n\n", array_unique( $content_parts ) );
	}


	/**
	 * Render page part HTML (separated for content extraction)
	 *
	 * @param int $id Page part ID
	 * @param string $design Design slug
	 * @param array $overrides Override parameters
	 *
	 * @return string Rendered HTML
	 */
	private function render_page_part_html( int $id, string $design, array $overrides = [] ): string {
		global $post, $wp_query;

		$original_post     = $post;
		$original_wp_query = $wp_query;

		$post     = get_post( $id );
		$wp_query = new \WP_Query( [ 'p' => $id, 'post_type' => 'page_part' ] );

		// Handle thumbnail override filter
		if ( ! empty( $overrides['_override_thumbnail_id'] ) ) {
			add_filter( 'post_thumbnail_id', function ( $thumbnail_id, $check_post ) use ( $id, $overrides ) {
				$check_id = is_object( $check_post ) ? $check_post->ID : $check_post;

				return ( $check_id == $id ) ? (int) $overrides['_override_thumbnail_id'] : $thumbnail_id;
			}, 10, 2 );
		}

		$wp_query->the_post();

		ob_start();

		// Get fields with overrides
		$page_part_fields = $this->meta_manager->get_page_part_fields( $id, $design, false );

		if ( ! empty( $overrides ) ) {
			$registry      = \NOK2025\V1\Theme::get_instance()->get_page_part_registry();
			$template_data = $registry[ $design ] ?? [];
			$custom_fields = $template_data['custom_fields'] ?? [];

			foreach ( $custom_fields as $field ) {
				if ( $field['page_editable']
				     && isset( $overrides[ $field['meta_key'] ] )
				     && $overrides[ $field['meta_key'] ] !== '' ) {

					$sanitize_callback                  = $this->meta_manager->get_sanitize_callback( $field['type'] );
					$page_part_fields[ $field['name'] ] = call_user_func(
						$sanitize_callback,
						$overrides[ $field['meta_key'] ]
					);
				}
			}
		}

		$this->renderer->render_page_part( $design, $page_part_fields );
		$output = ob_get_clean();

		$post     = $original_post;
		$wp_query = $original_wp_query;

		return $output;
	}


	/**
	 * Setup preview filters for meta and content
	 */
	private function setup_preview_filters( int $id, array $preview_state ): void {
		add_filter( 'get_post_metadata', function ( $value, $object_id, $meta_key ) use ( $id, $preview_state ) {
			if ( $object_id != $id ) {
				return $value;
			}

			// Handle custom fields from unified preview state
			if ( isset( $preview_state['meta'][ $meta_key ] ) ) {
				return [ $preview_state['meta'][ $meta_key ] ];
			}

			return $value;
		}, 10, 3 );

		// Filter title and content for preview
		add_filter( 'the_title', function ( $title, $post_id_filter ) use ( $id, $preview_state ) {
			if ( $post_id_filter == $id && isset( $preview_state['title'] ) ) {
				return $preview_state['title'];
			}

			return $title;
		}, 5, 2 );

		add_filter( 'the_content', function ( $content ) use ( $id, $preview_state ) {
			if ( get_the_ID() == $id && isset( $preview_state['content'] ) ) {
				return $preview_state['content'];
			}

			return $content;
		}, 5 );
	}

	/**
	 * Output the complete HTML response for the embed
	 *
	 * @param int $id Page part ID
	 * @param string $design Design slug
	 * @param array $overrides Override parameters
	 * @param string $rendered_html Pre-rendered HTML content
	 * @param string $semantic_content Extracted semantic content for Yoast
	 */
	private function output_embed_html( int $id, string $design, array $overrides = [], string $rendered_html = '', string $semantic_content = '' ): void {
		$css_uris = [
			'/assets/css/nok-components.css',
			'/assets/css/color_tests-v2.css',
			"/template-parts/page-parts/{$design}.preview.css",
			'/assets/css/nok-page-parts-editor-styles.css',
		];

		header( 'Content-Type: text/html; charset=utf-8' );

		$html = '<!doctype html><html><head><meta charset="utf-8">';

		// Add semantic content meta tag for Yoast integration
		if ( ! empty( $semantic_content ) ) {
			$html .= '<meta name="yoast-content" content="' . esc_attr( $semantic_content ) . '">';
		}

		foreach ( $css_uris as $uri ) {
			if ( file_exists( THEME_ROOT_ABS . $uri ) ) {
				$html .= '<link rel="stylesheet" href="' . esc_url( THEME_ROOT . $uri ) . '">';
			}
		}

		//$edit_link = admin_url( "post.php?post={$id}&action=edit" );
		$html      .= '<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        <link href="' . THEME_ROOT . '/assets/fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
        </head><body>';

		//$html .= '<div style="position: fixed; top: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 8px 12px; font-size: 12px; z-index: 9999; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">
        //<strong>Preview:</strong> ' . esc_html( get_the_title( $id ) ) . '
        //<a href="' . esc_url( $edit_link ) . '" target="_blank" style="color: #8cf; margin-left: 12px; text-decoration: none;">✏️ Bewerken</a>
        //</div>';

		//$html .= '<div style="margin-top: 36px; pointer-events: none;">';
		$html .= $rendered_html;
		//$html .= '</div>';
		$html .= '</body></html>';

		echo $html;
		exit;
	}

	/**
	 * Render the page part content within proper WordPress context
	 */
	private function render_page_part_content( int $id, string $design, array $overrides = [] ): string {
		// Store original state
		global $post, $wp_query;
		$original_post     = $post;
		$original_wp_query = $wp_query;

		$post     = get_post( $id );
		$wp_query = new \WP_Query( [
			'post_type'      => 'page_part',
			'p'              => $id,
			'posts_per_page' => 1
		] );

		$output = '';

		if ( $wp_query->have_posts() ) {
			// Apply featured image override if set
			if ( isset( $overrides['_override_thumbnail_id'] ) && $overrides['_override_thumbnail_id'] !== '' ) {
				add_filter( 'post_thumbnail_id', function ( $thumbnail_id, $post ) use ( $id, $overrides ) {
					$check_id = is_object( $post ) ? $post->ID : $post;

					return ( $check_id == $id ) ? (int) $overrides['_override_thumbnail_id'] : $thumbnail_id;
				}, 10, 2 );
			}

			$wp_query->the_post();

			// ✓ Use shared renderer method
			ob_start();

			// Get fields with overrides
			$page_part_fields = $this->meta_manager->get_page_part_fields( $id, $design, false );

			if ( ! empty( $overrides ) ) {
				$registry      = \NOK2025\V1\Theme::get_instance()->get_page_part_registry();
				$template_data = $registry[ $design ] ?? [];
				$custom_fields = $template_data['custom_fields'] ?? [];

				foreach ( $custom_fields as $field ) {
					if ( $field['page_editable']
					     && isset( $overrides[ $field['meta_key'] ] )
					     && $overrides[ $field['meta_key'] ] !== '' ) {

						$sanitize_callback                  = $this->meta_manager->get_sanitize_callback( $field['type'] );
						$page_part_fields[ $field['name'] ] = call_user_func(
							$sanitize_callback,
							$overrides[ $field['meta_key'] ]
						);
					}
				}
			}

			$this->renderer->render_page_part( $design, $page_part_fields );
			$output = ob_get_clean();
		} else {
			$output = '<p style="color: red; padding: 20px;">Error: Could not load page part data</p>';
		}

		$post     = $original_post;
		$wp_query = $original_wp_query;

		return $output;
	}

	/**
	 * REST endpoint: Get aggregated SEO content for a post
	 *
	 * @param \WP_REST_Request $request Request object
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function get_seo_content( \WP_REST_Request $request ) {
		$post_id   = (int) $request['id'];
		$part_ids  = $request->get_param( 'part_ids' );  // May be null
		$use_cache = $request->get_param( 'use_cache' );

		if ( ! $this->content_aggregator ) {
			return new \WP_Error(
				'aggregator_not_initialized',
				'Content aggregator not available',
				[ 'status' => 500 ]
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found', [ 'status' => 404 ] );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'forbidden', 'You do not have permission to view this content', [ 'status' => 403 ] );
		}

		// If part_ids provided, use them directly (editor state)
		if ( $part_ids !== null && is_array( $part_ids ) ) {
			$result = $this->content_aggregator->get_aggregated_content_from_parts( $post_id, $part_ids );
		} else {
			// Fallback to parsing post_content (saved state)
			$result = $this->content_aggregator->get_aggregated_content( $post_id, $use_cache );
		}

		return new \WP_REST_Response( [
			'post_id'           => $post_id,
			'post_title'        => get_the_title( $post_id ),
			'content'           => $result['content'],
			'part_count'        => $result['part_count'],
			'parts'             => $result['parts'],
			'content_length'    => strlen( $result['content'] ),
			'word_count'        => str_word_count( $result['content'] ),
			'from_editor_state' => ( $part_ids !== null )
		], 200 );
	}
}