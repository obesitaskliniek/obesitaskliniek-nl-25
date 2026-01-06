<?php
// inc/PageParts/RestEndpoints.php

namespace NOK2025\V1\PageParts;

/**
 * RestEndpoints - REST API endpoints for page parts and SEO content
 *
 * Provides REST endpoints for:
 * - Embedding page parts with live preview support
 * - Pruning orphaned template field metadata
 * - Rendering page parts with override parameters
 *
 * @example Register endpoints in theme initialization
 * $endpoints = new RestEndpoints($renderer, $meta_manager);
 * $endpoints->register_hooks();
 *
 * @example Access embed endpoint
 * GET /wp-json/nok-2025-v1/v1/embed-page-part/123?override_field=value
 *
 * @package NOK2025\V1\PageParts
 */
class RestEndpoints {
	private TemplateRenderer $renderer;
	private MetaManager $meta_manager;

	/**
	 * Constructor
	 *
	 * @param TemplateRenderer $template_renderer Template rendering service
	 * @param MetaManager $meta_manager Meta field management service
	 */
	public function __construct(
		TemplateRenderer $template_renderer,
		MetaManager $meta_manager
	) {
		$this->renderer           = $template_renderer;
		$this->meta_manager       = $meta_manager;
	}

	/**
	 * Register WordPress action hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action('rest_api_init', [$this, 'register_rest_routes' ] );
		add_action('rest_api_init', [$this, 'register_prune_endpoint']);
		add_action('rest_api_init', [$this, 'register_post_query_endpoint']);
		add_action('rest_api_init', [$this, 'register_orphaned_fields_endpoint']);
		add_action('rest_api_init', [$this, 'register_search_endpoint']);
	}

	/**
	 * Register custom REST API routes
	 *
	 * @return void
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
	 * Register endpoint for pruning orphaned template field metadata
	 *
	 * @return void
	 */
	public function register_prune_endpoint(): void {
		register_rest_route('nok/v1', '/page-part/(?P<id>\d+)/prune-fields', [
			'methods' => 'POST',
			'callback' => [$this, 'prune_fields'],
			'permission_callback' => function() {
				return current_user_can('edit_posts');
			},
			'args' => [
				'retain_current' => [
					'required' => false,
					'type' => 'boolean',
					'default' => false
				]
			]
		]);
	}

	/**
	 * Prune orphaned template-specific meta fields
	 *
	 * Deletes meta fields from old templates when template changes.
	 * Optionally retains fields belonging to current template.
	 *
	 * @param \WP_REST_Request $request Request with id and retain_current parameter
	 * @return \WP_REST_Response Response with deleted field list and count
	 */
	public function prune_fields(\WP_REST_Request $request): \WP_REST_Response {
		$post_id = (int) $request['id'];
		$retain_current = $request->get_param('retain_current') ?? false;

		$current_template = get_post_meta($post_id, 'design_slug', true);
		$all_meta = get_post_meta($post_id);
		$deleted = [];

		foreach (array_keys($all_meta) as $meta_key) {
			// Match pattern: template-slug_field_name
			if (preg_match('/^[a-z0-9-]+_[a-z_]+$/', $meta_key) && $meta_key !== 'design_slug') {
				$belongs_to_current = strpos($meta_key, $current_template . '_') === 0;

				if (!($retain_current && $belongs_to_current)) {
					delete_post_meta($post_id, $meta_key);
					$deleted[] = $meta_key;
				}
			}
		}

		return new \WP_REST_Response([
			'deleted' => $deleted,
			'count' => count($deleted)
		]);
	}

	/**
	 * Register endpoint for getting orphaned template fields
	 *
	 * Returns meta fields from previous templates that can be imported
	 * into the current template.
	 *
	 * @return void
	 */
	public function register_orphaned_fields_endpoint(): void {
		register_rest_route('nok/v1', '/page-part/(?P<id>\d+)/orphaned-fields', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_orphaned_fields'],
			'permission_callback' => function() {
				return current_user_can('edit_posts');
			},
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => fn($param) => is_numeric($param)
				],
				'current_template' => [
					'required' => true,
					'type'     => 'string'
				]
			]
		]);
	}

	/**
	 * Get orphaned fields from previous templates
	 *
	 * Returns all meta fields that have values but don't belong to the current template.
	 * Groups fields by source template with type information for mapping.
	 *
	 * @param \WP_REST_Request $request Request with id and current_template parameters
	 * @return \WP_REST_Response Response with sources array containing orphaned fields
	 */
	public function get_orphaned_fields(\WP_REST_Request $request): \WP_REST_Response {
		$post_id          = (int) $request->get_param('id');
		$current_template = $request->get_param('current_template');

		$all_meta = get_post_meta($post_id);
		$registry = \NOK2025\V1\Theme::get_instance()->get_page_part_registry();

		// Group orphaned fields by source template
		$sources = [];

		foreach ($all_meta as $meta_key => $values) {
			$value = $values[0] ?? '';

			// Skip empty values
			if ($value === '' || $value === '[]' || $value === '0') {
				continue;
			}

			// Skip current template's fields
			if (str_starts_with($meta_key, $current_template . '_')) {
				continue;
			}

			// Skip non-page-part fields (must match slug_fieldname pattern)
			if (!preg_match('/^([a-z0-9-]+)_([a-z0-9_]+)$/i', $meta_key, $matches)) {
				continue;
			}

			$source_slug = $matches[1];
			$field_name  = $matches[2];

			// Skip special fields
			if ($field_name === 'design_slug') {
				continue;
			}

			// Find source template in registry to get field definitions
			$source_template = null;
			$field_def       = null;

			foreach ($registry as $template) {
				if ($template['slug'] === $source_slug) {
					$source_template = $template;
					foreach ($template['custom_fields'] ?? [] as $f) {
						if ($f['name'] === $field_name) {
							$field_def = $f;
							break;
						}
					}
					break;
				}
			}

			// Initialize source group if needed
			if (!isset($sources[$source_slug])) {
				$sources[$source_slug] = [
					'template_slug' => $source_slug,
					'template_name' => $source_template['name'] ?? ucfirst(str_replace('-', ' ', $source_slug)),
					'fields'        => []
				];
			}

			// Build field info
			$field_info = [
				'meta_key' => $meta_key,
				'name'     => $field_name,
				'type'     => $field_def['type'] ?? $this->infer_field_type($value),
				'label'    => $field_def['label'] ?? ucfirst(str_replace('_', ' ', $field_name)),
				'value'    => $value
			];

			// Add schema for repeaters
			if ($field_info['type'] === 'repeater' && isset($field_def['schema'])) {
				$field_info['schema'] = $field_def['schema'];
			} elseif ($field_info['type'] === 'repeater') {
				// Infer schema from value
				$decoded = json_decode($value, true);
				if (is_array($decoded) && !empty($decoded[0])) {
					$field_info['schema'] = [];
					foreach (array_keys($decoded[0]) as $key) {
						if ($key === '_id') {
							continue;
						}
						$field_info['schema'][] = [
							'name'  => $key,
							'label' => ucfirst(str_replace('_', ' ', $key)),
							'type'  => 'text' // Default assumption
						];
					}
				}
			}

			// Add options for select fields
			if ($field_info['type'] === 'select' && isset($field_def['options'])) {
				$field_info['options'] = $field_def['options'];
			}

			$sources[$source_slug]['fields'][] = $field_info;
		}

		return new \WP_REST_Response([
			'sources' => array_values($sources)
		], 200);
	}

	/**
	 * Post type to Dutch label mapping for search results
	 *
	 * @var array<string, string>
	 */
	private const POST_TYPE_LABELS = [
		'page'       => 'Pagina',
		'post'       => 'Artikel',
		'vestiging'  => 'Vestiging',
		'kennisbank' => 'Kennisbank',
	];

	/**
	 * Post types to include in search results
	 *
	 * @var array<string>
	 */
	private const SEARCH_POST_TYPES = ['page', 'post', 'vestiging', 'kennisbank'];

	/**
	 * Meta fields to include in search for specific post types
	 *
	 * @var array<string, array<string>>
	 */
	private const SEARCHABLE_META_FIELDS = [
		'vestiging' => ['_city', '_street'],
	];

	/**
	 * Register endpoint for search autocomplete
	 *
	 * @return void
	 */
	public function register_search_endpoint(): void {
		register_rest_route('nok-2025-v1/v1', '/search/autocomplete', [
			'methods'             => 'GET',
			'callback'            => [$this, 'search_autocomplete_callback'],
			'permission_callback' => '__return_true',
			'args'                => [
				'q' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => fn($param) => is_string($param) && strlen(trim($param)) > 0,
				],
				'limit' => [
					'required'          => false,
					'type'              => 'integer',
					'default'           => 5,
					'sanitize_callback' => 'absint',
					'validate_callback' => fn($param) => is_numeric($param) && $param > 0 && $param <= 20,
				],
			],
		]);
	}

	/**
	 * REST callback: Search autocomplete
	 *
	 * Performs content-aware search using Relevanssi if available,
	 * falling back to standard WP_Query search with meta field support.
	 *
	 * @param \WP_REST_Request $request Request with q and limit parameters
	 * @return \WP_REST_Response Response with results array and total count
	 */
	public function search_autocomplete_callback(\WP_REST_Request $request): \WP_REST_Response {
		$query_string = trim($request->get_param('q'));
		$limit        = (int) $request->get_param('limit');

		$args = [
			'post_type'      => self::SEARCH_POST_TYPES,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			's'              => $query_string,
			'orderby'        => 'relevance',
			'order'          => 'DESC',
		];

		$query = new \WP_Query($args);

		// Use Relevanssi if available for better content-aware ranking
		if (function_exists('relevanssi_do_query')) {
			relevanssi_do_query($query);
		}

		// Collect post IDs from title/content search
		$found_ids = wp_list_pluck($query->posts, 'ID');

		// Search meta fields for post types that have searchable meta
		$meta_results = $this->search_meta_fields($query_string, $limit, $found_ids);

		// Merge results, prioritizing title/content matches
		$all_posts = array_merge($query->posts, $meta_results);

		// Remove duplicates and limit results
		$seen_ids = [];
		$unique_posts = [];
		foreach ($all_posts as $post) {
			$post_id = is_object($post) ? $post->ID : $post;
			if (!in_array($post_id, $seen_ids, true)) {
				$seen_ids[] = $post_id;
				$unique_posts[] = is_object($post) ? $post : get_post($post_id);
			}
			if (count($unique_posts) >= $limit) {
				break;
			}
		}

		$results = [];
		foreach ($unique_posts as $post) {
			if (!$post) {
				continue;
			}
			$post_type = get_post_type($post);
			$results[] = [
				'id'         => $post->ID,
				'title'      => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
				'url'        => get_permalink($post),
				'type'       => $post_type,
				'type_label' => self::POST_TYPE_LABELS[$post_type] ?? ucfirst($post_type),
			];
		}

		// Calculate total including meta matches
		$total = $query->found_posts + count($meta_results);

		return new \WP_REST_Response([
			'results' => $results,
			'total'   => $total,
		], 200);
	}

	/**
	 * Search meta fields for matching posts
	 *
	 * @param string $query_string Search query
	 * @param int $limit Maximum results
	 * @param array $exclude_ids Post IDs to exclude (already found)
	 * @return array Array of WP_Post objects
	 */
	private function search_meta_fields(string $query_string, int $limit, array $exclude_ids): array {
		$meta_posts = [];

		foreach (self::SEARCHABLE_META_FIELDS as $post_type => $meta_keys) {
			$meta_query = ['relation' => 'OR'];

			foreach ($meta_keys as $meta_key) {
				$meta_query[] = [
					'key'     => $meta_key,
					'value'   => $query_string,
					'compare' => 'LIKE',
				];
			}

			$args = [
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_query'     => $meta_query,
				'post__not_in'   => $exclude_ids,
			];

			$query = new \WP_Query($args);
			$meta_posts = array_merge($meta_posts, $query->posts);
		}

		return $meta_posts;
	}

	/**
	 * Infer field type from value when template definition unavailable
	 *
	 * @param string $value The meta value to analyze
	 * @return string Inferred field type
	 */
	private function infer_field_type(string $value): string {
		if ($value === '0' || $value === '1') {
			return 'checkbox';
		}
		if (str_starts_with($value, '[') && json_decode($value) !== null) {
			return 'repeater';
		}
		if (filter_var($value, FILTER_VALIDATE_URL)) {
			return 'url';
		}
		if (str_contains($value, "\n")) {
			return 'textarea';
		}
		return 'text';
	}

	/**
	 * Register endpoint for querying posts
	 *
	 * @return void
	 */
	public function register_post_query_endpoint(): void {
		register_rest_route('nok-2025-v1/v1', '/posts/query', [
			'methods' => 'GET',
			'callback' => [$this, 'query_posts_callback'],
			'permission_callback' => '__return_true',
			'args' => [
				'post_type' => [
					'required' => false,
					'type' => 'string',
					'default' => 'post'
				],
				'categories' => [
					'required' => false,
					'type' => 'string',
					'default' => ''
				],
				'include' => [
					'required' => false,
					'type' => 'string',
					'default' => ''
				],
				'exclude' => [
					'required' => false,
					'type' => 'string',
					'default' => ''
				],
				'search' => [
					'required' => false,
					'type' => 'string',
					'default' => ''
				]
			]
		]);
	}

	/**
	 * REST callback: Query posts by date, excluding specified IDs
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function query_posts_callback(\WP_REST_Request $request): \WP_REST_Response {
		$post_type = $request->get_param('post_type');
		$exclude = $request->get_param('exclude');
		$search = $request->get_param('search');

		// Handle comma-separated post types
		if (is_string($post_type) && strpos($post_type, ',') !== false) {
			$post_type = array_map('trim', explode(',', $post_type));
		}

		$args = [
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => 50,
			'orderby' => !empty($search) ? 'relevance' : 'date',  // ← Key change
			'order' => 'DESC'
		];

		$categories = $request->get_param('categories');
		if (!empty($categories)) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'category',
					'field' => 'slug',
					'terms' => array_map('trim', explode(',', $categories)),
					'operator' => 'IN'
				]
			];
		}

		if (!empty($exclude)) {
			$args['post__not_in'] = array_map('intval', explode(',', $exclude));
		}

		$include = $request->get_param('include');
		if (!empty($include)) {
			$args['post__in'] = array_map('intval', explode(',', $include));
			$args['orderby'] = 'post__in';
			$args['post_type'] = 'any'; // Allow any post type when fetching by ID
		}

		if (!empty($search)) {
			// WordPress default search
			$args['s'] = $search;

			// Add explicit title search as fallback
			add_filter('posts_search', function($search, $wp_query) use ($request) {
				global $wpdb;
				$search_term = $request->get_param('search');
				if (empty($search_term)) {
					return $search;
				}

				// Add OR condition for exact title match
				$search_term_like = '%' . $wpdb->esc_like($search_term) . '%';
				$search .= $wpdb->prepare(" OR {$wpdb->posts}.post_title LIKE %s", $search_term_like);

				return $search;
			}, 10, 2);
		}

		$query = new \WP_Query($args);
		$posts = [];

		foreach ($query->posts as $post) {

			// Skip revisions - they can appear when using post_type 'any'
			if ($post->post_type === 'revision') {
				continue;
			}

			$posts[] = [
				'id' => $post->ID,
				'title' => html_entity_decode(get_the_title($post->ID), ENT_QUOTES, 'UTF-8'),
				'date' => get_the_date('Y-m-d', $post->ID),
				'type' => $post->post_type
			];
		}

		return new \WP_REST_Response($posts, 200);
	}

	/**
	 * REST API callback for embedding page parts
	 *
	 * Renders page part with preview state and query parameter overrides.
	 * Extracts semantic content for Yoast SEO integration.
	 * Only runs in backend when editing a page.
	 *
	 * @param \WP_REST_Request $request Request with id parameter and optional overrides
	 * @return void Outputs HTML and exits
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

		// Extract generic overrides (title/content)
		$generic_overrides = [];
		if ( isset( $overrides['_override_title'] ) && $overrides['_override_title'] !== '' ) {
			$generic_overrides['_override_title'] = sanitize_text_field( $overrides['_override_title'] );
		}
		if ( isset( $overrides['_override_content'] ) && $overrides['_override_content'] !== '' ) {
			$generic_overrides['_override_content'] = wp_kses_post( $overrides['_override_content'] );
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

		$this->renderer->render_page_part( $design, $page_part_fields, $generic_overrides );
		$output = ob_get_clean();

		$post     = $original_post;
		$wp_query = $original_wp_query;

		return $output;
	}


	/**
	 * Setup preview filters for meta and content
	 *
	 * @param int $id Page part ID
	 * @param array $preview_state Preview state data from transient
	 * @return void
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
	 * Output the complete HTML response for the embed in the page
	 *
	 * @param int $id Page part ID
	 * @param string $design Design slug
	 * @param array $overrides Override parameters
	 * @param string $rendered_html Pre-rendered HTML content
	 * @param string $semantic_content Extracted semantic content for Yoast
	 * @return void Outputs HTML and exits
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

		$html      .= '<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
        <link href="' . THEME_ROOT . '/assets/fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
        </head><body>';
		$html .= $rendered_html;
		//$html .= '</div>';
		$html .= '</body></html>';

		echo $html;
		exit;
	}

}