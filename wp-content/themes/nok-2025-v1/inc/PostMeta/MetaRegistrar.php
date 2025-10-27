<?php

namespace NOK2025\V1\PostMeta;

class MetaRegistrar {
	private MetaRegistry $registry;

	public function __construct() {
		$this->registry = new MetaRegistry();
		add_action( 'init', [ $this, 'register_all_meta' ], 20 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Register all defined meta fields with WordPress
	 */
	public function register_all_meta(): void {
		foreach ( MetaRegistry::get_all_fields() as $post_type => $fields ) {
			foreach ( $fields as $meta_key => $field ) {
				$sanitize_callback = $field['sanitize_callback']
				                     ?? MetaRegistry::get_sanitize_callback( $field['type'] );

				register_post_meta( $post_type, $meta_key, [
					'type'              => MetaRegistry::get_rest_type( $field['type'] ),
					'single'            => true,
					'show_in_rest'      => $field['show_in_rest'],
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
					'default'           => $field['default'],
				] );
			}
		}
	}

	/**
	 * Enqueue Gutenberg editor assets for registered post types
	 */
	public function enqueue_editor_assets(): void {
		$screen = get_current_screen();
		if (!$screen || $screen->base !== 'post') {
			return;
		}

		$fields = MetaRegistry::get_fields($screen->post_type);
		if (empty($fields)) {
			return;
		}

		wp_enqueue_script(
			'nok-post-meta-panel',
			get_template_directory_uri() . '/assets/js/nok-post-meta-panel.js',
			['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'],
			filemtime(get_template_directory() . '/assets/js/nok-post-meta-panel.js')
		);

		// Pass field configuration with category constraints
		wp_localize_script('nok-post-meta-panel', 'nokPostMetaFields', [
			'fields' => $fields,
			'postType' => $screen->post_type,
		]);
	}
}