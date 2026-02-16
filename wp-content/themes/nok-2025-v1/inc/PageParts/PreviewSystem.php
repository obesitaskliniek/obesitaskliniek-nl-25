<?php
// inc/PageParts/PreviewSystem.php

namespace NOK2025\V1\PageParts;

/**
 * PreviewSystem - Live preview system for page part custom post type editor
 *
 * Manages preview functionality in the page part editor:
 * - Stores editor state in transients via AJAX
 * - Intercepts preview requests and applies transient data
 * - Adds preview meta box with iframe rendering
 * - Filters post meta, title, and content during preview
 *
 * Works with PreviewEditor JavaScript to provide real-time updates
 * as editors make changes without saving.
 *
 * @example Initialize in theme setup
 * $preview = new PreviewSystem($meta_manager);
 * $preview->register_hooks();
 *
 * @example Preview flow
 * 1. Editor types in custom field
 * 2. JavaScript sends AJAX request to store_preview_state
 * 3. State saved in transient for 5 minutes
 * 4. Preview iframe refresh picks up transient data
 * 5. Filters apply transient values instead of database values
 *
 * @package NOK2025\V1\PageParts
 */
class PreviewSystem {
	private MetaManager $meta_manager;

	public function __construct(MetaManager $meta_manager) {
		$this->meta_manager = $meta_manager;
	}

	public function register_hooks(): void {
		add_action('init', [$this, 'handle_preview_state']);
		add_action('init', [$this, 'handle_preview_rendering']);
		add_action('add_meta_boxes', [$this, 'add_preview_meta_box']);
	}

	/**
	 * Handle AJAX requests for storing complete editor state in transients
	 */
	public function handle_preview_state(): void {
		add_action('wp_ajax_store_preview_state', function () {
			// Verify nonce
			if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nok_preview_state_nonce')) {
				wp_send_json_error('Invalid nonce');
				return;
			}

			// Verify user has permission to edit posts
			if (!current_user_can('edit_posts')) {
				wp_send_json_error('Insufficient permissions');
				return;
			}

			$post_id = (int) $_POST['post_id'];
			$meta_fields_raw = $_POST['meta_fields'] ?? '';

			if ($post_id && !empty($meta_fields_raw)) {
				$meta_fields = json_decode(stripslashes($meta_fields_raw), true);

				if (json_last_error() === JSON_ERROR_NONE && is_array($meta_fields)) {
					// Sanitize only meta fields
					$sanitized_meta = $this->meta_manager->sanitize_meta_fields($meta_fields);

					// Store only meta in transient
					set_transient("preview_editor_state_{$post_id}", [
						'meta' => $sanitized_meta
					], 300);

					wp_send_json_success("Stored meta fields for post {$post_id}");
				} else {
					wp_send_json_error('Invalid meta fields data');
				}
			} else {
				wp_send_json_error('Missing post ID or meta fields');
			}
		});
	}

	/**
	 * Override post data during preview rendering using unified transient data
	 */
	public function handle_preview_rendering(): void {
		// Hook earlier in the WordPress loading process
		add_action('wp', function () {
			// Only apply during preview of page_part posts
			if (is_preview() && get_post_type() === 'page_part') {
				$post_id = get_the_ID();
				$preview_state = get_transient("preview_editor_state_{$post_id}");

				if ($preview_state && is_array($preview_state)) {
					// Filter meta values during preview
					add_filter('get_post_metadata', function ($value, $object_id, $meta_key) use ($post_id, $preview_state) {
						// Only filter for the current post being previewed
						if ($object_id != $post_id) {
							return $value;
						}

						// Handle custom fields from unified preview state
						if (isset($preview_state['meta'][$meta_key])) {
							return [$preview_state['meta'][$meta_key]];
						}

						// For page part fields that don't exist in DB yet, return empty to trigger fallback
						if (preg_match('/^[a-z-]+_[a-z_]+$/', $meta_key)) {
							return [''];
						}

						return $value;
					}, 5, 3);

					// Filter title during preview
					add_filter('the_title', function ($title, $post_id_filter) use ($post_id, $preview_state) {
						if ($post_id_filter == $post_id && isset($preview_state['title'])) {
							return $preview_state['title'];
						}

						return $title;
					}, 5, 2);

					// Filter content during preview
					add_filter('the_content', function ($content) use ($post_id, $preview_state) {
						if (get_the_ID() == $post_id && isset($preview_state['content'])) {
							return $preview_state['content'];
						}

						return $content;
					}, 5);
				}
			}
		}, 5);
	}

	/**
	 * Add preview meta box to page_part edit screen
	 */
	public function add_preview_meta_box(): void {
		add_meta_box(
			'nok-page-part-preview',
			__('NOK - Live Page Part Preview', THEME_TEXT_DOMAIN),
			[$this, 'render_preview_meta_box'],
			'page_part',
			'normal',
			'high'
		);
	}

	/**
	 * Render the preview meta box content
	 */
	public function render_preview_meta_box(\WP_Post $post): void {
		wp_nonce_field('nok_preview_nonce', 'nok_preview_nonce');

		echo '<button id="nok-page-part-preview-button" type="button" class="button button-primary">'
		     . esc_html__('Refresh Preview', THEME_TEXT_DOMAIN) . '</button>';
		echo '<div><p>' . esc_html__('Let op: Page Parts zijn niet afzonderlijk publiek benaderbaar (of indexeerbaar) en zijn ontworpen om onderdeel van een pagina te zijn.', THEME_TEXT_DOMAIN) . '</p></div>';
		echo '<div id="nok-page-part-preview-root" style="border:1px solid #ddd; min-height:300px"></div>';
	}
}