<?php
/**
 * Yoast SEO Integration
 *
 * Integrates the page parts system with Yoast SEO by providing
 * aggregated content to Yoast's analysis engine.
 *
 * @package NOK2025\V1\SEO
 */

namespace NOK2025\V1\SEO;

class YoastIntegration {

	public function register_hooks(): void {
		// Only load if Yoast SEO is active
		add_action('admin_enqueue_scripts', [$this, 'enqueue_integration_script']);
	}

	/**
	 * Check if Yoast SEO is active
	 *
	 * @return bool
	 */
	private function is_yoast_active(): bool {
		return defined('WPSEO_VERSION');
	}

	/**
	 * Enqueue the Yoast integration script
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_integration_script(string $hook): void {
		// Only load on post edit screens
		if (!in_array($hook, ['post.php', 'post-new.php'])) {
			return;
		}

		// Only load if Yoast SEO is active
		if (!$this->is_yoast_active()) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen) {
			return;
		}

		// Only for post types that can contain page parts
		$allowed_post_types = ['page', 'post'];
		if (!in_array($screen->post_type, $allowed_post_types)) {
			return;
		}

		// Only in block editor
		if (!$screen->is_block_editor()) {
			return;
		}

		// Load the integration script
		$asset_file = get_theme_file_path('/assets/js/yoast-page-parts-integration.asset.php');

		if (!file_exists($asset_file)) {
			error_log('[Yoast Integration] Asset file not found. Run npm build.');
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'nok-yoast-page-parts-integration',
			get_stylesheet_directory_uri() . '/assets/js/yoast-page-parts-integration.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Localize script with any needed data
		wp_localize_script(
			'nok-yoast-page-parts-integration',
			'nokYoastIntegration',
			[
				'restUrl' => rest_url('nok-2025-v1/v1'),
				'nonce' => wp_create_nonce('wp_rest'),
				'postId' => get_the_ID(),
				'debug' => defined('WP_DEBUG') && WP_DEBUG
			]
		);
	}
}