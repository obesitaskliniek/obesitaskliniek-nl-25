<?php

namespace NOK2025\V1;

/**
 * PostTypes - Custom post type registration and protection
 *
 * Registers theme custom post types:
 * - page_part: Reusable page components for embedding
 * - template_layout: Block editor templates for single post types
 * - vestiging: Clinic locations with address and contact info
 * - kennisbank: Knowledge base with FAQ and informational articles
 *   URL structure: /kennisbank/{category}/{post-slug}/
 *
 * Protects internal post types from public access:
 * - Returns 404 for page_part and template_layout single views
 * - Prevents search engine indexing
 * - Allows logged-in users (for previews)
 *
 * @example Initialize in theme setup
 * $post_types = new PostTypes();
 *
 * @example Query vestigingen
 * $locations = get_posts(['post_type' => 'vestiging']);
 *
 * @package NOK2025\V1
 */
class PostTypes {
	/** @var array Track post types with archives for dynamic settings pages */
	private static array $archive_post_types = [];
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_kennisbank_permalink_filter' ] );
		add_action( 'template_redirect', [ $this, 'protect_post_types' ] );
		add_filter( 'single_template', [ $this, 'kennisbank_category_template' ] );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types(): void {
		$this->register_page_part_post_type();
		$this->register_template_layout_post_type();
		$this->register_vestiging_post_type();
		$this->register_kennisbank_taxonomy();
		$this->register_kennisbank_post_type();
		$this->track_archive_post_types();
	}

	/**
	 * Register the page_part custom post type
	 */
	private function register_page_part_post_type(): void {
		$labels = [
			'name'               => __( 'Page Parts', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Page Part', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Add New Page Part', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Edit Page Part', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'New Page Part', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'View Page Part', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'View Page Parts', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Search Page Parts', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'No page parts found.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'No page parts found in Trash.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'All Page Parts', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Page Part Archives', THEME_TEXT_DOMAIN ),
			'attributes'         => __( 'Page Part Attributes', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Reusable page components that can be embedded into pages.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'page-parts',
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-layout',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields'
			],
			'taxonomies'          => [ 'category', 'post_tag' ],
			'has_archive'         => false,
			'rewrite'             => [
				'slug'       => 'page-part',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'page_part', $args );
	}

	/**
	 * Register the template_layout custom post type
	 *
	 * Template layouts are block editor compositions used to define
	 * the structure of single post templates (e.g., single-cat-ervaringen.php).
	 * They allow admins to configure template zones using page part blocks
	 * without hard-coding post IDs in template files.
	 */
	private function register_template_layout_post_type(): void {
		$labels = [
			'name'               => __( 'Template Layouts', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Template Layout', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Add New Template Layout', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Edit Template Layout', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'New Template Layout', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'View Template Layout', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'View Template Layouts', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Search Template Layouts', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'No template layouts found.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'No template layouts found in Trash.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'All Template Layouts', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Block editor layouts for configuring single post templates.', THEME_TEXT_DOMAIN ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'template-layouts',
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-schedule',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'revisions',
			],
			'has_archive'         => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'template_layout', $args );
	}

	/**
	 * Register the vestiging custom post type
	 *
	 * Vestigingen (locations/offices) represent physical clinic locations.
	 * Each vestiging has address, contact info, and opening hours.
	 *
	 * Meta fields (registered in Theme.php):
	 * - _street, _housenumber, _postal_code, _city
	 * - _phone (formatted via Helpers::format_phone())
	 * - _email
	 * - _opening_hours (JSON, formatted via Helpers::format_opening_hours())
	 *
	 * URLs:
	 * - Archive: /vestigingen/
	 * - Single: /vestigingen/{slug}/
	 *
	 * Templates:
	 * - archive-vestiging.php
	 * - template-parts/single-vestiging-content.php
	 */
	private function register_vestiging_post_type(): void {
		$labels = [
			'name'               => __( 'Vestigingen', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Vestiging', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuwe vestiging toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Vestiging bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuwe vestiging', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Vestiging bekijken', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'Vestigingen bekijken', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Vestigingen zoeken', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'Geen vestigingen gevonden.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'Geen vestigingen gevonden in prullenbak.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'Alle vestigingen', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Vestiging archieven', THEME_TEXT_DOMAIN ),
			'attributes'         => __( 'Vestiging attributen', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Kliniek vestigingen met adres- en contactgegevens.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'vestigingen',
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-location-alt',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'custom-fields',
			],
			'has_archive'         => 'vestigingen',
			'rewrite'             => [
				'slug'       => 'vestigingen',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'vestiging', $args );

		// Track for archive settings
		if ($args['has_archive']) {
			self::$archive_post_types['vestiging'] = [
				'slug' => is_string($args['has_archive']) ? $args['has_archive'] : 'vestiging',
				'label' => $labels['name'],
			];
		}
	}

	/**
	 * Register the kennisbank_categories taxonomy
	 *
	 * Must be registered BEFORE the kennisbank post type for rewrite rules to work.
	 */
	private function register_kennisbank_taxonomy(): void {
		$labels = [
			'name'              => __( 'Kennisbank Categorieën', THEME_TEXT_DOMAIN ),
			'singular_name'     => __( 'Kennisbank Categorie', THEME_TEXT_DOMAIN ),
			'search_items'      => __( 'Zoek categorieën', THEME_TEXT_DOMAIN ),
			'all_items'         => __( 'Alle categorieën', THEME_TEXT_DOMAIN ),
			'parent_item'       => __( 'Bovenliggende categorie', THEME_TEXT_DOMAIN ),
			'parent_item_colon' => __( 'Bovenliggende categorie:', THEME_TEXT_DOMAIN ),
			'edit_item'         => __( 'Categorie bewerken', THEME_TEXT_DOMAIN ),
			'update_item'       => __( 'Categorie bijwerken', THEME_TEXT_DOMAIN ),
			'add_new_item'      => __( 'Nieuwe categorie toevoegen', THEME_TEXT_DOMAIN ),
			'new_item_name'     => __( 'Nieuwe categorienaam', THEME_TEXT_DOMAIN ),
			'menu_name'         => __( 'Categorieën', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'kennisbank_categories',
			'rewrite'           => [
				'slug'         => 'kennisbank',
				'with_front'   => false,
				'hierarchical' => true,
			],
		];

		register_taxonomy( 'kennisbank_categories', [ 'kennisbank' ], $args );
	}

	/**
	 * Register the kennisbank custom post type
	 *
	 * Kennisbank (knowledge base) contains FAQ items and informational articles.
	 * URL structure: /kennisbank/{category}/{post-slug}/
	 *
	 * Previously registered via ACF - migrated to theme for custom permalink structure.
	 */
	private function register_kennisbank_post_type(): void {
		$labels = [
			'name'               => __( 'Kennisbank', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Kennisbank item', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuw item toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Item bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuw item', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Item bekijken', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'Items bekijken', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Items zoeken', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'Geen items gevonden.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'Geen items gevonden in prullenbak.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'Alle items', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Kennisbank archief', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'In de kennisbank vind je naast veelgestelde vragen ook veel extra informatie over de behandeling van obesitas.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'kennisbank',
			'menu_position'       => 7,
			'menu_icon'           => 'dashicons-excerpt-view',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields',
			],
			'taxonomies'          => [ 'kennisbank_categories' ],
			'has_archive'         => 'kennisbank',
			'rewrite'             => [
				'slug'       => 'kennisbank/%kennisbank_categories%',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'kennisbank', $args );

		// Track for archive settings
		self::$archive_post_types['kennisbank'] = [
			'slug'  => 'kennisbank',
			'label' => $labels['name'],
		];
	}

	/**
	 * Register permalink filter and rewrite rules for kennisbank posts
	 *
	 * - Adds filter to replace %kennisbank_categories% placeholder in URLs
	 * - Adds rewrite rule to parse /kennisbank/{category}/{post}/ URLs
	 */
	public function register_kennisbank_permalink_filter(): void {
		add_filter( 'post_type_link', [ $this, 'kennisbank_permalink' ], 10, 2 );

		// Add rewrite rule to match /kennisbank/{category}/{post-slug}/
		// Must be added after post type registration, before 'parse_request'
		add_rewrite_rule(
			'^kennisbank/([^/]+)/([^/]+)/?$',
			'index.php?kennisbank=$matches[2]',
			'top'
		);
	}

	/**
	 * Filter kennisbank permalinks to include category slug
	 *
	 * @param string   $permalink The post permalink
	 * @param \WP_Post $post      The post object
	 * @return string Modified permalink
	 */
	public function kennisbank_permalink( string $permalink, \WP_Post $post ): string {
		if ( $post->post_type !== 'kennisbank' ) {
			return $permalink;
		}

		$terms = get_the_terms( $post->ID, 'kennisbank_categories' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			// Use the first (primary) category
			$category_slug = $terms[0]->slug;
		} else {
			// Fallback for uncategorized posts
			$category_slug = 'uncategorized';
		}

		return str_replace( '%kennisbank_categories%', $category_slug, $permalink );
	}

	/**
	 * Load category-specific single template for kennisbank posts
	 *
	 * Allows different templates per category:
	 * - single-kennisbank-blogs.php (for "blogs" category)
	 * - single-kennisbank-medisch.php (for "medisch" category)
	 * - single-kennisbank.php (fallback)
	 *
	 * @param string $template Default template path
	 * @return string Modified template path if category-specific template exists
	 */
	public function kennisbank_category_template( string $template ): string {
		if ( ! is_singular( 'kennisbank' ) ) {
			return $template;
		}

		$post = get_queried_object();
		$terms = get_the_terms( $post->ID, 'kennisbank_categories' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return $template;
		}

		// Try to find a category-specific template
		$category_slug = $terms[0]->slug;
		$category_template = locate_template( "single-kennisbank-{$category_slug}.php" );

		if ( $category_template ) {
			return $category_template;
		}

		return $template;
	}

	/**
	 * Protect page_part posts from being accessed by non-logged-in users
	 *
	 * Page parts are designed to be embedded components, not standalone pages.
	 * This prevents them from being indexed by search engines or accessed directly.
	 */
	public function protect_post_types(): void {
		global $pagenow, $wp_query;

		// Skip protection in admin, AJAX, CRON, and REST contexts
		if ( is_admin()
		     || defined( 'DOING_AJAX' )
		     || defined( 'DOING_CRON' )
		     || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return;
		}

		// Allow logged-in users to access page parts (for previews, etc.)
		if ( is_user_logged_in() ) {
			return;
		}

		// Allow WordPress login and registration pages
		if ( in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		// Define protected post types
		$protected_post_types = [ 'page_part', 'template_layout' ];

		// Protect single views
		if ( is_singular( $protected_post_types ) ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		// Protect archive pages
		if ( is_post_type_archive( $protected_post_types ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
	}

	/**
	 * Track all registered post types that have archives
	 *
	 * Iterates through all registered post types and builds registry
	 * for dynamic archive settings page generation.
	 *
	 * @return void
	 */
	private function track_archive_post_types(): void {
		$post_types = get_post_types(['_builtin' => false], 'objects');

		foreach ($post_types as $post_type => $post_type_obj) {
			if ($post_type_obj->has_archive) {
				self::$archive_post_types[$post_type] = [
					'slug' => is_string($post_type_obj->has_archive)
						? $post_type_obj->has_archive
						: $post_type,
					'label' => $post_type_obj->labels->name,
				];
			}
		}
	}

	/**
	 * Get all registered post types with archives
	 *
	 * @return array Associative array of post_type => ['slug', 'label']
	 */
	public static function get_archive_post_types(): array {
		return self::$archive_post_types;
	}
}