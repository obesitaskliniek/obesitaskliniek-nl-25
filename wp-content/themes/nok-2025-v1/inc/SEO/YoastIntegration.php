<?php
/**
 * YoastIntegration - Page Parts SEO Content Analysis
 *
 * Integrates page parts with Yoast SEO by collecting rendered content
 * from iframe previews and providing aggregated text to Yoast's analysis engine.
 *
 * Architecture notes:
 * - Page parts render in iframes (not directly in editor DOM)
 * - Each iframe extracts its semantic content via meta tag
 * - JavaScript waits for all iframes to load before registering with Yoast
 * - Visual editor mode only (code editor cannot access iframe content)
 *
 * @example Basic initialization in Theme class
 * $yoast = new YoastIntegration();
 * $yoast->register_hooks();
 *
 * @package NOK2025\V1\SEO
 */

namespace NOK2025\V1\SEO;

class YoastIntegration {

	/**
	 * Register WordPress hooks
	 *
	 * Hooks into admin_enqueue_scripts to load integration JavaScript
	 * only on appropriate edit screens with Yoast SEO active.
	 */
	public function register_hooks(): void {
		add_action('admin_enqueue_scripts', [$this, 'enqueue_integration_script'], 20);
		// Exclude page_part from Yoast indexables and sitemaps
		add_filter('wpseo_indexable_excluded_post_types', [$this, 'exclude_page_parts_from_indexables']);
		add_filter('wpseo_sitemap_exclude_post_type', [$this, 'exclude_page_parts_from_sitemap'], 10, 2);
		// Fix breadcrumb archive URLs for custom post types
		add_filter('wpseo_breadcrumb_links', [$this, 'fix_breadcrumb_archive_urls']);
	}

	public function exclude_page_parts_from_indexables(array $excluded): array {
		$excluded[] = 'page_part';
		return $excluded;
	}

	public function exclude_page_parts_from_sitemap(bool $excluded, string $post_type): bool {
		return $post_type === 'page_part' ? true : $excluded;
	}

	/**
	 * Fix breadcrumb archive URLs for custom post types
	 *
	 * Yoast's breadcrumb system has an architectural limitation where it doesn't
	 * automatically respect WordPress's has_archive custom slugs. When a post type
	 * is registered with has_archive set to a custom string (e.g., 'vestigingen'
	 * for post type 'vestiging'), Yoast constructs breadcrumb URLs from the post
	 * type name rather than querying WordPress's rewrite system.
	 *
	 * This method uses Yoast's official wpseo_breadcrumb_links extension point
	 * to correct archive URLs by calling WordPress core's get_post_type_archive_link()
	 * function, ensuring breadcrumbs respect the has_archive configuration.
	 *
	 * @param array $links Array of breadcrumb items from Yoast
	 * @return array Modified breadcrumb array with corrected archive URLs
	 */
	public function fix_breadcrumb_archive_urls(array $links): array {
		// Only process on vestiging-related pages
		if (!is_singular('vestiging') && !is_post_type_archive('vestiging')) {
			return $links;
		}

		foreach ($links as $key => $link) {
			// Identify the vestiging archive breadcrumb by its ptarchive reference
			if (isset($link['ptarchive']) && $link['ptarchive'] === 'vestiging') {
				// Use WordPress core function to get the correct archive URL
				$links[$key]['url'] = get_post_type_archive_link('vestiging');
			}
		}

		return $links;
	}

	/**
	 * Check if Yoast SEO is active
	 *
	 * @return bool True if WPSEO_VERSION constant is defined
	 */
	private function is_yoast_active(): bool {
		return defined('WPSEO_VERSION');
	}

	/**
	 * Enqueue the Yoast integration script
	 *
	 * Loads JavaScript integration only when:
	 * - On post/page edit screens
	 * - Yoast SEO is active
	 * - Block editor is enabled
	 * - Post type supports page parts (page/post)
	 *
	 * Passes expected page part IDs to JavaScript for loading detection.
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_integration_script(string $hook): void {
		// Only load on post edit screens
		if (!in_array($hook, ['post.php', 'post-new.php'])) {
			return;
		}

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

		// Extract expected page part IDs from saved post content
		$post_id = get_the_ID();
		$expected_parts = [];

		if ($post_id) {
			$post = get_post($post_id);
			if ($post && !empty($post->post_content)) {
				$expected_parts = $this->extract_page_part_ids($post->post_content);
			}
		}

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

		// JavaScript will use expectedParts to know when all iframes have loaded
		wp_localize_script(
			'nok-yoast-page-parts-integration',
			'nokYoastIntegration',
			[
				'expectedParts' => $expected_parts,
				'postId' => $post_id,
				'debug' => defined('WP_DEBUG') && WP_DEBUG
			]
		);
	}

	/**
	 * Extract page part IDs from post content
	 *
	 * Parses Gutenberg block structure to find all nok2025/embed-nok-page-part
	 * blocks and extracts their postId attributes.
	 *
	 * @param string $content Raw post_content with block comments
	 * @return array Array of unique page part IDs
	 */
	private function extract_page_part_ids(string $content): array {
		$blocks = parse_blocks($content);
		return $this->find_page_part_ids_recursive($blocks);
	}

	/**
	 * Recursively find page part IDs in block structure
	 *
	 * Handles nested blocks (page parts inside columns, groups, etc.)
	 *
	 * @param array $blocks Array of parsed block arrays from parse_blocks()
	 * @return array Array of unique page part IDs
	 */
	private function find_page_part_ids_recursive(array $blocks): array {
		$part_ids = [];

		foreach ($blocks as $block) {
			if ($block['blockName'] === 'nok2025/embed-nok-page-part') {
				$post_id = $block['attrs']['postId'] ?? 0;
				if ($post_id > 0) {
					$part_ids[] = $post_id;
				}
			}

			// Check inner blocks recursively
			if (!empty($block['innerBlocks'])) {
				$part_ids = array_merge(
					$part_ids,
					$this->find_page_part_ids_recursive($block['innerBlocks'])
				);
			}
		}

		return array_unique($part_ids);
	}
}