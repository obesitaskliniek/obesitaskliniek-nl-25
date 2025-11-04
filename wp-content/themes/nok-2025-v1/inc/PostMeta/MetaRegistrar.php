<?php

namespace NOK2025\V1\PostMeta;

/**
 * MetaRegistrar - WordPress integration for custom post meta fields
 *
 * Handles WordPress registration and REST API integration for meta fields
 * defined in MetaRegistry. Responsibilities:
 * - Register meta fields with WordPress via register_post_meta()
 * - Define REST API schemas for complex types (e.g., opening_hours)
 * - Enqueue block editor UI for meta field panels
 * - Provide sanitize/prepare callbacks for data conversion
 *
 * @example Usage (automatically initialized by Theme class)
 * // Fields registered in Theme::register_post_custom_fields() are
 * // automatically registered with WordPress when MetaRegistrar initializes
 *
 * @package NOK2025\V1\PostMeta
 */
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

				$args = [
					'type'              => MetaRegistry::get_rest_type( $field['type'] ),
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
					'default'           => $field['default'],
				];

				// Add schema for object types
				if ( $field['type'] === 'opening_hours' ) {
					$args['show_in_rest'] = [
						'schema' => [
							'type'                 => 'object',
							'properties'           => [
								'weekdays'  => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'monday'    => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'tuesday'   => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'wednesday' => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'thursday'  => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'friday'    => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'saturday'  => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
								'sunday'    => [
									'type'  => 'array',
									'items' => [
										'type'       => 'object',
										'properties' => [
											'opens'  => [ 'type' => 'string' ],
											'closes' => [ 'type' => 'string' ],
											'closed' => [ 'type' => 'boolean' ],
										],
									],
								],
							],
							'additionalProperties' => false,
						],
						'prepare_callback' => function( $value ) {
							// Decode JSON string back to object for REST API
							if ( is_string( $value ) ) {
								$decoded = json_decode( $value, true );
								return is_array( $decoded ) ? $decoded : [];
							}
							return is_array( $value ) ? $value : [];
						},
					];
				}

				register_post_meta( $post_type, $meta_key, $args );
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