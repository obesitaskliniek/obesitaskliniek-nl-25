<?php

namespace NOK2025\V1;

class PostTypes {
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'template_redirect', [ $this, 'protect_post_types' ] );
	}

	public function register_post_types() {
		$labels = [
			'name'          => __( 'Page Parts', THEME_TEXT_DOMAIN ),
			'singular_name' => __( 'Page Part', THEME_TEXT_DOMAIN ),
			'add_new_item'  => __( 'Add New Page Part', THEME_TEXT_DOMAIN ),
			'edit_item'     => __( 'Edit Page Part', THEME_TEXT_DOMAIN ),
			'all_items'     => __( 'All Page Parts', THEME_TEXT_DOMAIN ),
			'view_item'     => __( 'View Page Part', THEME_TEXT_DOMAIN ),
		];
		$args   = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'menu_position'      => 5,
			'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ],
			'taxonomies'         => [ 'category', 'post_tag' ],
			'menu_icon'          => 'dashicons-layout',
			'has_archive'        => false,
		];
		register_post_type( 'page_part', $args );
	}

	public function protect_post_types() {
		global $pagenow, $wp_query;

		// 1) never touch admin, AJAX, CRON...
		if ( is_admin()
		     || defined('DOING_AJAX')
		     || defined('DOING_CRON')
		     || (defined('REST_REQUEST') && REST_REQUEST) ) {
			return;
		}

		// 2) already logged in? no need to protect.
		if ( is_user_logged_in() ) {
			return;
		}

		// 3) allow the real wp-login.php & wp-register.php
		if ( in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		// 4) your CPT slug(s)
		$protected = [ 'page_part' ];

		// 5) single CPT view?
		if ( is_singular( $protected ) ) {
			// Option A: redirect to home
			// wp_safe_redirect( home_url('/') ); exit;

			// Option B: serve a 404 instead (better for de‑indexing)
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();

			// return and let WP go through its normal template‐loader
			return;
		}

		// 6) CPT archive? (if you ever turn on has_archive)
		if ( is_post_type_archive( $protected ) ) {
			wp_safe_redirect( home_url('/') );
			exit;
		}
	}

}