<?php
declare(strict_types=1);

namespace NOK2025\V1;

/**
 * Comments - Disables WordPress comments and pingbacks site-wide
 *
 * - Closes comments and pings for all content types
 * - Removes comment-related admin UI
 * - Blocks public comment feeds, REST routes, and XML-RPC pingbacks
 *
 * @example Register hooks from the theme bootstrap
 * $comments = new Comments();
 * $comments->register_hooks();
 *
 * @package NOK2025\V1
 */
class Comments {

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'remove_post_type_support' ], 100 );
		add_action( 'admin_menu', [ $this, 'remove_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'redirect_comments_admin' ] );
		add_action( 'admin_bar_menu', [ $this, 'remove_admin_bar_node' ], 999 );
		add_action( 'wp_dashboard_setup', [ $this, 'remove_dashboard_widgets' ] );
		add_action( 'widgets_init', [ $this, 'unregister_comment_widgets' ] );
		add_action( 'template_redirect', [ $this, 'block_comment_feeds' ], 9 );

		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'get_comments_number', '__return_zero', 20 );
		add_filter( 'wp_insert_post_data', [ $this, 'force_closed_on_save' ], 20 );
		add_filter( 'pre_option_default_comment_status', [ $this, 'closed_status' ] );
		add_filter( 'pre_option_default_ping_status', [ $this, 'closed_status' ] );
		add_filter( 'wp_headers', [ $this, 'remove_pingback_header' ] );
		add_filter( 'xmlrpc_methods', [ $this, 'remove_xmlrpc_pingback_methods' ] );
		add_filter( 'rest_endpoints', [ $this, 'remove_rest_comment_routes' ] );
	}

	/**
	 * Remove comments and trackbacks support from every registered post type
	 *
	 * @return void
	 */
	public function remove_post_type_support(): void {
		foreach ( get_post_types( [], 'names' ) as $post_type ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}

	/**
	 * Remove the Comments item from the admin menu
	 *
	 * @return void
	 */
	public function remove_admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Redirect direct access to the comments admin screen
	 *
	 * @return void
	 */
	public function redirect_comments_admin(): void {
		global $pagenow;

		if ( $pagenow !== 'edit-comments.php' ) {
			return;
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Remove the comments node from the admin bar
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
	 *
	 * @return void
	 */
	public function remove_admin_bar_node( \WP_Admin_Bar $wp_admin_bar ): void {
		$wp_admin_bar->remove_node( 'comments' );
	}

	/**
	 * Remove dashboard widgets that surface comment activity
	 *
	 * @return void
	 */
	public function remove_dashboard_widgets(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Unregister the core Recent Comments widget
	 *
	 * @return void
	 */
	public function unregister_comment_widgets(): void {
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	/**
	 * Return the closed status for WordPress discussion defaults
	 *
	 * @return string Closed discussion status
	 */
	public function closed_status(): string {
		return 'closed';
	}

	/**
	 * Force comments and pings closed whenever posts are saved
	 *
	 * @param array<string, mixed> $data Sanitized post data before database insert
	 *
	 * @return array<string, mixed> Post data with discussion fields closed
	 */
	public function force_closed_on_save( array $data ): array {
		$data['comment_status'] = 'closed';
		$data['ping_status']    = 'closed';

		return $data;
	}

	/**
	 * Remove the X-Pingback response header
	 *
	 * @param array<string, string> $headers HTTP response headers
	 *
	 * @return array<string, string> Headers without X-Pingback
	 */
	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	/**
	 * Remove XML-RPC methods that can create pingbacks
	 *
	 * @param array<string, callable|string> $methods Registered XML-RPC methods
	 *
	 * @return array<string, callable|string> XML-RPC methods without pingback handlers
	 */
	public function remove_xmlrpc_pingback_methods( array $methods ): array {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	/**
	 * Remove core REST API comment routes
	 *
	 * @param array<string, array<int, array<string, mixed>>> $endpoints Registered REST routes
	 *
	 * @return array<string, array<int, array<string, mixed>>> REST routes without comments
	 */
	public function remove_rest_comment_routes( array $endpoints ): array {
		unset( $endpoints['/wp/v2/comments'], $endpoints['/wp/v2/comments/(?P<id>[\d]+)' ] );

		return $endpoints;
	}

	/**
	 * Block comment feeds with a 404 response
	 *
	 * @return void
	 */
	public function block_comment_feeds(): void {
		if ( ! is_comment_feed() ) {
			return;
		}

		status_header( 404 );
		nocache_headers();
		wp_die(
			esc_html__( 'Comments are disabled.', THEME_TEXT_DOMAIN ),
			esc_html__( 'Comments disabled', THEME_TEXT_DOMAIN ),
			[ 'response' => 404 ]
		);
	}
}
