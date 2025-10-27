<?php

namespace NOK2025\V1\PostMeta;

class MetaRegistry {
	private static array $registered_fields = [];

	/**
	 * Register custom post meta field
	 *
	 * @param string $post_type Post type slug
	 * @param string $field_key Meta key (without underscore prefix)
	 * @param array $args Field configuration
	 */
	public static function register_field( string $post_type, string $field_key, array $args ): void {
		$defaults = [
			'type'              => 'text',
			'label'             => ucwords( str_replace( '_', ' ', $field_key ) ),
			'sanitize_callback' => null,
			'default'           => '',
			'show_in_rest'      => true,
			'placeholder'       => '',
			'categories'        => [],
			'taxonomies'        => [],
		];

		$field = array_merge( $defaults, $args );

		// Force underscore prefix for protected meta
		$meta_key = '_' . ltrim( $field_key, '_' );

		if ( ! isset( self::$registered_fields[ $post_type ] ) ) {
			self::$registered_fields[ $post_type ] = [];
		}

		self::$registered_fields[ $post_type ][ $meta_key ] = $field;
	}

	public static function get_fields( string $post_type ): array {
		return self::$registered_fields[ $post_type ] ?? [];
	}

	public static function get_all_fields(): array {
		return self::$registered_fields;
	}

	/**
	 * Get sanitize callback for field type
	 */
	public static function get_sanitize_callback( string $type ): callable {
		return match ( $type ) {
			'textarea' => 'sanitize_textarea_field',
			'url' => 'esc_url_raw',
			'email' => 'sanitize_email',
			'number' => 'absint',
			'checkbox' => fn( $v ) => in_array( $v, [ '1', 1, true ], true ) ? '1' : '0',
			default => 'sanitize_text_field',
		};
	}

	/**
	 * Get REST API type for field
	 */
	public static function get_rest_type( string $type ): string {
		return match ( $type ) {
			'number', 'checkbox' => 'integer',
			default => 'string',
		};
	}
}