<?php

namespace NOK2025\V1\PostMeta;

/**
 * MetaRegistry - Field registration and type system for custom post meta
 *
 * Centralized registry for defining custom meta fields on any post type.
 * Provides:
 * - Field type definitions (text, textarea, email, opening_hours, etc.)
 * - Default values and sanitization callbacks per type
 * - REST API type mapping
 * - Field storage and retrieval
 *
 * @example Register a text field
 * MetaRegistry::register_field('vestiging', 'street', [
 *     'type' => 'text',
 *     'label' => 'Straat',
 *     'placeholder' => 'Voer straatnaam in...',
 * ]);
 *
 * @example Register opening hours field
 * MetaRegistry::register_field('vestiging', 'opening_hours', [
 *     'type' => 'opening_hours',
 *     'label' => 'Openingstijden',
 * ]);
 *
 * @package NOK2025\V1\PostMeta
 */
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
		// Get type to determine appropriate default
		$field_type = $args['type'] ?? 'text';

		$defaults = [
			'type'              => 'text',
			'label'             => ucwords( str_replace( '_', ' ', $field_key ) ),
			'sanitize_callback' => null,
			'default'           => self::get_default_value( $field_type ),
			'show_in_rest'      => true,
			'placeholder'       => '',
			'categories'        => [],
			'taxonomies'        => [],
		];

		$field = array_merge( $defaults, $args );

		// Ensure default value matches the field type
		if ( ! isset( $args['default'] ) ) {
			$field['default'] = self::get_default_value( $field['type'] );
		}

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
			'number', 'post_select' => 'absint',
			'checkbox' => fn( $v ) => in_array( $v, [ '1', 1, true ], true ) ? '1' : '0',
			'opening_hours' => fn( $v ) => is_string( $v ) ? $v : wp_json_encode( $v ),
			default => 'sanitize_text_field',
		};
	}

	/**
	 * Get REST API type for field
	 */
	public static function get_rest_type( string $type ): string {
		return match ( $type ) {
			'number', 'checkbox', 'post_select' => 'integer',
			'opening_hours' => 'object',
			default => 'string',
		};
	}

	/**
	 * Get default value for field type
	 * Returns type-appropriate default to avoid WordPress meta registration errors
	 */
	public static function get_default_value( string $type ) {
		return match ( $type ) {
			'number', 'checkbox', 'post_select' => 0,
			'opening_hours' => [],
			default => '',
		};
	}
}