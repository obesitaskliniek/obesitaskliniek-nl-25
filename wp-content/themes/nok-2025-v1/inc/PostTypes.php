<?php

namespace NOK2025\V1;

class PostTypes {
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'template_redirect', [ $this, 'protect_post_types' ] );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types(): void {
		$this->register_page_part_post_type();
		$this->register_template_layout_post_type();
		// Add other post types here as needed
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
}