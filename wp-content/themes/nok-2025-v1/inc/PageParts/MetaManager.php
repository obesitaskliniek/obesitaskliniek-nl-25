<?php
// inc/PageParts/MetaManager.php

namespace NOK2025\V1\PageParts;

use NOK2025\V1\Helpers;

class MetaManager {
	private Registry $registry;

	/**
	 * Constructor
	 *
	 * @param Registry $registry Page part registry instance
	 */
	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Register WordPress hooks for meta management
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_design_meta' ] );
		add_action( 'save_post_page_part', [ $this, 'save_editor_state' ], 10, 2 );
		add_filter( 'manage_page_part_posts_columns', [ $this, 'add_page_part_columns' ] );
		add_action( 'manage_page_part_posts_custom_column', [ $this, 'render_page_part_column' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'add_template_filter' ] );
		add_action( 'parse_query', [ $this, 'filter_by_template' ] );
		if ( ! isset($_GET['show_custom_fields'])) {
			add_filter( 'is_protected_meta', [ $this, 'protect_page_part_meta' ], 10, 2 );
		}
	}

	/**
	 * Mark page part meta fields as protected from Custom Fields panel
	 *
	 * @param bool   $protected Whether the key is protected
	 * @param string $meta_key  Meta key being checked
	 * @return bool Whether to protect this meta key
	 */
	public function protect_page_part_meta( bool $protected, string $meta_key ): bool {
		// Protect design_slug
		if ( $meta_key === 'design_slug' ) {
			return true;
		}

		// Protect all registered template fields
		$registry = $this->registry->get_registry();
		foreach ( $registry as $template_data ) {
			if ( empty( $template_data['custom_fields'] ) ) {
				continue;
			}

			foreach ( $template_data['custom_fields'] as $field ) {
				if ( $field['meta_key'] === $meta_key ) {
					return true;
				}
			}
		}

		return $protected;
	}

	/**
	 * Register design_slug meta field and all custom fields for REST API access
	 *
	 * @return void
	 */
	public function register_design_meta(): void {
		register_post_meta( 'page_part', 'design_slug', [
			'type'              => 'string',
			'show_in_rest'      => true,
			'single'            => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
			'default'           => '',
		] );

		$registry = $this->registry->get_registry();

		foreach ( $registry as $template_slug => $template_data ) {
			if ( empty( $template_data['custom_fields'] ) ) {
				continue;
			}

			foreach ( $template_data['custom_fields'] as $field ) {
				$sanitize_callback = $this->get_sanitize_callback( $field['type'] );

				register_post_meta( 'page_part', $field['meta_key'], [
					'type'              => $this->get_meta_type( $field['type'] ),
					'show_in_rest'      => true,
					'single'            => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
					'default'           => $this->get_default_value( $field['type'], $field ),
				] );
			}
		}
	}

	/**
	 * Get page part fields with defaults and placeholders
	 *
	 * Returns field values from database with fallbacks:
	 * - Editing mode: Shows placeholders for empty text fields
	 * - Display mode: Returns empty strings for missing fields
	 *
	 * @param int $post_id Page part post ID
	 * @param string $design Design slug to get fields for
	 * @param bool $is_editing Whether in editor context (shows placeholders)
	 *
	 * @return array Associative array of field name => value
	 */
	public function get_page_part_fields( int $post_id, string $design, bool $is_editing = false ): array {
		$registry              = $this->registry->get_registry();
		$current_template_data = $registry[ $design ] ?? [];
		$expected_fields       = $current_template_data['custom_fields'] ?? [];

		$default_fields = [
			'text' => '(leeg)',
			'url'  => '#',
		];

		$page_part_fields = [];

		foreach ( $expected_fields as $field ) {
			$meta_key         = $field['meta_key'];
			$short_field_name = $field['name'];
			$is_text_based    = in_array( $field['type'], [ 'text', 'textarea' ], true );

			$actual_meta_value                     = get_post_meta( $post_id, $meta_key, true );
			$page_part_fields[ $short_field_name ] = empty( $actual_meta_value ) ?
				( $is_editing ?
					( $is_text_based ? Helpers::show_placeholder( $short_field_name ) : ( $default_fields[ $field['type'] ] ?? '' ) ) : '' ) :
				$actual_meta_value;
		}

		return $page_part_fields;
	}

	/**
	 * Save editor state from unified transient or fallback methods
	 *
	 * Priority order:
	 * 1. Transient preview state (from Gutenberg editor)
	 * 2. REST API handled by WordPress
	 * 3. Legacy form submission
	 *
	 * @param int $post_id Page part post ID
	 * @param \WP_Post $post Post object
	 *
	 * @return void
	 */

	public function save_editor_state( int $post_id, \WP_Post $post ): void {
		// Only handle autosaves (for preview generation)
		// Let REST API handle real saves
		if ( ! wp_is_post_autosave( $post_id ) ) {
			return;
		}

		static $saving = [];
		if ( isset( $saving[ $post_id ] ) ) {
			return;
		}
		$saving[ $post_id ] = true;

		// For autosaves, use transient if it exists
		$preview_state = get_transient( "preview_editor_state_{$post_id}" );

		if ( $preview_state && is_array( $preview_state ) && isset( $preview_state['meta'] ) ) {
			foreach ( $preview_state['meta'] as $meta_key => $meta_value ) {
				if ( $meta_value === null ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}
		}

		unset( $saving[ $post_id ] );
	}

	/**
	 * Add custom columns to page_part post list
	 *
	 * @param array $columns Existing columns
	 *
	 * @return array Modified columns with template column
	 */
	public function add_page_part_columns( array $columns ): array {
		// Insert template column after title
		$new_columns = [];
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key === 'title' ) {
				$new_columns['design_template'] = __( 'Template', THEME_TEXT_DOMAIN );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content for page_part posts
	 *
	 * @param string $column_name Column identifier
	 * @param int $post_id Post ID being rendered
	 *
	 * @return void
	 */
	public function render_page_part_column( string $column_name, int $post_id ): void {
		if ( $column_name === 'design_template' ) {
			$design_slug = get_post_meta( $post_id, 'design_slug', true );

			if ( $design_slug ) {
				$registry      = $this->registry->get_registry();
				$template_name = $registry[ $design_slug ]['name'] ?? $design_slug;
				echo esc_html( $template_name );
			} else {
				echo '<em>' . esc_html__( 'No template', THEME_TEXT_DOMAIN ) . '</em>';
			}
		}
	}

	/**
	 * Sanitize meta fields based on their registered field types
	 *
	 * @param array $meta_fields Associative array of meta_key => value
	 *
	 * @return array Sanitized meta fields
	 */
	public function sanitize_meta_fields( array $meta_fields ): array {
		$sanitized = [];
		$registry  = $this->registry->get_registry();

		foreach ( $meta_fields as $meta_key => $meta_value ) {
			// Find the field definition to get proper sanitization
			$field_found = false;
			foreach ( $registry as $template_slug => $template_data ) {
				if ( empty( $template_data['custom_fields'] ) ) {
					continue;
				}

				foreach ( $template_data['custom_fields'] as $field ) {
					if ( $field['meta_key'] === $meta_key ) {
						$sanitize_callback      = $this->get_sanitize_callback( $field['type'] );
						$sanitized[ $meta_key ] = call_user_func( $sanitize_callback, $meta_value );
						$field_found            = true;
						break 2; // Break out of both loops
					}
				}
			}

			// If field not found in registry, use default sanitization
			if ( ! $field_found ) {
				$sanitized[ $meta_key ] = sanitize_text_field( $meta_value );
			}
		}

		return $sanitized;
	}

	/**
	 * Handle legacy form submission for backward compatibility
	 *
	 * @param int $post_id Page part post ID
	 *
	 * @return void
	 */
	private function handle_legacy_form_submission( int $post_id ): void {
		if ( isset( $_POST['page_part_design_slug'] ) ) {
			$new = sanitize_key( wp_unslash( $_POST['page_part_design_slug'] ) );
			update_post_meta( $post_id, 'design_slug', $new );
		}

		// Handle traditional form submission for custom fields
		$registry = $this->registry->get_registry();
		foreach ( $registry as $template_slug => $template_data ) {
			if ( empty( $template_data['custom_fields'] ) ) {
				continue;
			}

			foreach ( $template_data['custom_fields'] as $field ) {
				$form_field_name = 'page_part_' . $field['meta_key'];

				if ( isset( $_POST[ $form_field_name ] ) ) {
					$sanitize_callback = $this->get_sanitize_callback( $field['type'] );
					$sanitized_value   = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $form_field_name ] ) );
					update_post_meta( $post_id, $field['meta_key'], $sanitized_value );
				}
			}
		}
	}

	/**
	 * Get appropriate sanitize callback for field type
	 *
	 * @param string $type Field type (text, textarea, url, checkbox, repeater, etc.)
	 *
	 * @return callable Sanitization callback function
	 */
	public function get_sanitize_callback( string $type ): callable {
		return function ( $value ) use ( $type ) {
			// Allow null for deletion
			if ( $value === null ) {
				return null;
			}

			switch ( $type ) {
				case 'textarea':
					return sanitize_textarea_field( $value );
				case 'url':
					return esc_url_raw( $value );
				case 'checkbox':
					return in_array( $value, [ '1', 1, true, '0', 0, false ], true ) ?
						( $value ? '1' : '0' ) : '0';
				case 'repeater':
					return is_string( $value ) ? $value : wp_json_encode( $value );
				case 'icon-selector':
				case 'select':
				case 'text':
				default:
					return sanitize_text_field( $value );
			}
		};
	}

	/**
	 * Get meta type for WordPress registration
	 *
	 * @param string $field_type Field type from template definition
	 *
	 * @return string WordPress meta type (always 'string' currently)
	 */
	private function get_meta_type( string $field_type ): string {
		switch ( $field_type ) {
			case 'repeater':
				return 'string'; // JSON stored as string
			default:
				return 'string';
		}
	}

	/**
	 * Get default value for field type
	 *
	 * @param string $field_type Field type from template definition
	 * @param array $field Complete field definition (may contain custom default)
	 *
	 * @return mixed Default value for field type
	 */
	private function get_default_value( string $field_type, array $field = [] ) {
		// Use field-specific default if provided
		if ( isset( $field['default'] ) ) {
			return $field['default'];
		}
		switch ( $field_type ) {
			case 'repeater':
				return '[]'; // Empty JSON array
			case 'checkbox':
				return '0';
			default:
				return '';
		}
	}

	/**
	 * Sanitize JSON field data
	 *
	 * Handles both array input (REST API) and string input (form/double-encoded).
	 * Returns valid JSON string or empty array.
	 *
	 * @param mixed $value Array or JSON string to sanitize
	 *
	 * @return string Valid JSON string
	 */
	public function sanitize_json_field( $value ) {
		// Handle arrays passed directly (REST API)
		if ( is_array( $value ) ) {
			return wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		if ( is_string( $value ) ) {
			// First, try to decode it
			$decoded = json_decode( $value, true );

			// If decode failed, the string might be double-encoded
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				// Try decoding again (handles double-encoding)
				$decoded = json_decode( stripslashes( $value ), true );
			}

			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				// Re-encode with proper UTF-8 handling
				return wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE );
			}
		}

		return '[]';
	}

	/**
	 * Sanitize checkbox field - convert to '1' or '0'
	 *
	 * @param mixed $value Truthy or falsy value
	 *
	 * @return string Either '1' or '0'
	 */
	public function sanitize_checkbox_field( $value ) {
		return $value ? '1' : '0';
	}

	/**
	 * Add template filter dropdown to page_part admin list
	 *
	 * @return void
	 */
	public function add_template_filter(): void {
		$post_type = $_GET['post_type'] ?? '';

		if ( $post_type !== 'page_part' ) {
			return;
		}

		$registry         = $this->registry->get_registry();
		$current_template = $_GET['design_template'] ?? '';

		echo '<select name="design_template">';
		echo '<option value="">' . esc_html__( 'All Templates', THEME_TEXT_DOMAIN ) . '</option>';

		foreach ( $registry as $slug => $data ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $slug ),
				selected( $current_template, $slug, false ),
				esc_html( $data['name'] )
			);
		}

		echo '</select>';
	}

	/**
	 * Filter page_part query by template
	 *
	 * @param \WP_Query $query Query object to modify
	 *
	 * @return void
	 */
	public function filter_by_template( \WP_Query $query ): void {
		global $pagenow;

		if ( $pagenow !== 'edit.php'
		     || ! isset( $_GET['post_type'] )
		     || $_GET['post_type'] !== 'page_part'
		     || ! isset( $_GET['design_template'] )
		     || $_GET['design_template'] === ''
		) {
			return;
		}

		$query->set( 'meta_query', [
			[
				'key'     => 'design_slug',
				'value'   => sanitize_key( $_GET['design_template'] ),
				'compare' => '='
			]
		] );
	}
}