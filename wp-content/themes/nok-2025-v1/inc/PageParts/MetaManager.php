<?php
// inc/PageParts/MetaManager.php

namespace NOK2025\V1\PageParts;

use NOK2025\V1\Helpers;

/**
 * MetaManager - Page part meta field management and persistence
 *
 * Handles all aspects of page part custom field data:
 * - Registering meta fields with WordPress REST API
 * - Retrieving field values with fallback defaults
 * - Sanitizing and saving field data
 * - Protecting internal meta from Custom Fields panel
 * - Admin list columns and template filtering
 * - Preview state synchronization from Gutenberg editor
 * - Field value placeholders in editing mode
 *
 * Integrates with:
 * - Registry: Reads field definitions
 * - PreviewSystem: Saves transient editor state
 * - REST API: Exposes fields to block editor
 * - WordPress admin: Adds columns and filters
 *
 * @example Initialize with registry
 * $registry = new Registry();
 * $meta_manager = new MetaManager($registry);
 * $meta_manager->register_hooks();
 *
 * @example Get page part fields for rendering
 * $fields = $meta_manager->get_page_part_fields(123, 'nok-hero', false);
 * echo $fields['title']; // Output field value
 *
 * @example Get fields in editor (with placeholders)
 * $fields = $meta_manager->get_page_part_fields(123, 'nok-cta', true);
 * // Returns placeholders for empty fields
 *
 * @package NOK2025\V1\PageParts
 */
class MetaManager {
	private Registry $registry;

	/**
	 * Cached set of protected meta keys for O(1) lookup
	 * Built lazily on first access to avoid unnecessary work
	 *
	 * @var array<string, true>|null
	 */
	private ?array $protected_meta_keys = null;

	/**
	 * Constructor
	 *
	 * @param Registry $registry Page part registry instance
	 */
	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Build the protected meta keys lookup set
	 *
	 * Creates a hash set of all meta keys that should be protected from
	 * the Custom Fields panel. Called once per request and cached.
	 *
	 * @return array<string, true> Hash set of protected meta keys
	 */
	private function get_protected_meta_keys(): array {
		if ( $this->protected_meta_keys !== null ) {
			return $this->protected_meta_keys;
		}

		$this->protected_meta_keys = [
			'design_slug'  => true,
			'_section_id'  => true,
		];

		$registry = $this->registry->get_registry();
		foreach ( $registry as $template_data ) {
			if ( empty( $template_data['custom_fields'] ) ) {
				continue;
			}

			foreach ( $template_data['custom_fields'] as $field ) {
				$this->protected_meta_keys[ $field['meta_key'] ] = true;
			}
		}

		return $this->protected_meta_keys;
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
		add_action( 'add_meta_boxes', [ $this, 'add_usage_meta_box' ] );
		add_action( 'wp_ajax_nok_check_page_part_usage', [ $this, 'ajax_check_page_part_usage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_usage_warning_scripts' ] );
		if ( ! isset( $_GET['show_custom_fields'] ) ) {
			add_filter( 'is_protected_meta', [ $this, 'protect_page_part_meta' ], 10, 2 );
		}
	}

	/**
	 * Mark page part meta fields as protected from Custom Fields panel
	 *
	 * Uses a cached hash set for O(1) lookup instead of iterating through
	 * all templates and fields for every meta key (which was O(n*m) per call).
	 *
	 * @param bool $protected Whether the key is protected
	 * @param string $meta_key Meta key being checked
	 *
	 * @return bool Whether to protect this meta key
	 */
	public function protect_page_part_meta( bool $protected, string $meta_key ): bool {
		// O(1) lookup in cached hash set
		$protected_keys = $this->get_protected_meta_keys();

		if ( isset( $protected_keys[ $meta_key ] ) ) {
			return true;
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
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
			'default'           => '',
		] );

		register_post_meta( 'page_part', '_section_id', [
			'type'              => 'string',
			'show_in_rest'      => true,
			'single'            => true,
			'sanitize_callback' => function ( $value ) {
				// Cannot use sanitize_title directly as sanitize_callback because
				// WordPress passes ($value, $meta_key, ...) and sanitize_title
				// treats the second arg as fallback_title — so empty values
				// would return the meta key name as the value.
				return sanitize_title( $value );
			},
			'auth_callback'     => function () {
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

				// Check if field has empty string as valid option
				$has_empty_option = $field['type'] === 'select'
				                    && in_array( '', $field['options'] ?? [], true );

				$meta_args = [
					'type'              => $this->get_meta_type( $field['type'] ),
					'show_in_rest'      => true,
					'single'            => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				];

				// Only register default if field doesn't have empty string as option
				// Allows WordPress to save empty strings as explicit values
				if ( ! $has_empty_option ) {
					$meta_args['default'] = $this->get_default_value( $field['type'], $field );
				}

				register_post_meta( 'page_part', $field['meta_key'], $meta_args );
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

			$actual_meta_value = get_post_meta( $post_id, $meta_key, true );

			// Use template default only if meta key doesn't exist in database
			// get_post_meta() returns false when key doesn't exist, but '' when set to empty
			if ( $actual_meta_value === false && isset( $field['default'] ) ) {
				$page_part_fields[ $short_field_name ] = $field['default'];
			} elseif ( $actual_meta_value === false ) {
				// Meta key doesn't exist and no default defined
				$page_part_fields[ $short_field_name ] = $is_editing ?
					( $is_text_based ? Helpers::show_placeholder( $short_field_name ) : ( $default_fields[ $field['type'] ] ?? '' ) ) : '';
			} else {
				// Meta key exists - use exact value (including empty string)
				$page_part_fields[ $short_field_name ] = $actual_meta_value;
			}
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
				'<option value="%s" %s>%s</option>',
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

	/**
	 * Add usage meta box to page_part edit screen
	 *
	 * @return void
	 */
	public function add_usage_meta_box(): void {
		add_meta_box(
			'nok-page-part-usage',
			__( 'Page Part gebruik', THEME_TEXT_DOMAIN ),
			[ $this, 'render_usage_meta_box' ],
			'page_part',
			'side',
			'default'
		);
	}

	/**
	 * Get pages/posts that use a specific page part
	 *
	 * Searches post_content for the embed block with matching postId attribute.
	 *
	 * @param int $post_id Page part post ID
	 *
	 * @return array Array of posts using this page part
	 */
	public function get_page_part_usage( int $post_id ): array {
		global $wpdb;

		// Search for the embed block with this page_part's ID
		// Pattern: "postId":123 (where 123 is the page_part ID)
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type, post_status
				 FROM {$wpdb->posts}
				 WHERE post_content LIKE %s
				 AND post_type IN ('page', 'post')
				 AND post_status IN ('publish', 'draft', 'private', 'pending')
				 ORDER BY post_title ASC",
				'%"postId":' . $post_id . '%'
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Render the usage meta box content
	 *
	 * Displays a list of pages/posts that embed this page part,
	 * with edit links and status indicators.
	 *
	 * @param \WP_Post $post Current page part post
	 *
	 * @return void
	 */
	public function render_usage_meta_box( \WP_Post $post ): void {
		$usages = $this->get_page_part_usage( $post->ID );

		if ( empty( $usages ) ) {
			echo '<p><em>' . esc_html__( 'Deze page part wordt niet gebruikt op pagina\'s.', THEME_TEXT_DOMAIN ) . '</em></p>';
			return;
		}

		$count = count( $usages );
		printf(
			'<p>' . esc_html__( 'Gebruikt op %d pagina(\'s):', THEME_TEXT_DOMAIN ) . '</p>',
			$count
		);

		echo '<ul style="margin: 0; padding-left: 1.2em;">';
		foreach ( $usages as $usage ) {
			$edit_url     = get_edit_post_link( $usage['ID'] );
			$status_label = $usage['post_status'] !== 'publish'
				? ' <span style="color: #666;">(' . esc_html( $usage['post_status'] ) . ')</span>'
				: '';

			printf(
				'<li><a href="%s" target="_blank">%s</a>%s</li>',
				esc_url( $edit_url ),
				esc_html( $usage['post_title'] ?: __( '(geen titel)', THEME_TEXT_DOMAIN ) ),
				$status_label
			);
		}
		echo '</ul>';

		echo '<p style="margin-top: 1em; padding: 8px; background: #fff8e5; border-left: 4px solid #ffb900;">';
		echo '<strong>' . esc_html__( 'Let op:', THEME_TEXT_DOMAIN ) . '</strong> ';
		printf(
			esc_html__( 'Wijzigingen aan deze page part hebben effect op alle %d pagina(\'s) hierboven.', THEME_TEXT_DOMAIN ),
			$count
		);
		echo '</p>';
	}

	/**
	 * AJAX handler to check page part usage
	 *
	 * Returns JSON with usage data for a specific page part.
	 *
	 * @return void
	 */
	public function ajax_check_page_part_usage(): void {
		check_ajax_referer( 'nok_page_part_usage', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Geen toegang.', THEME_TEXT_DOMAIN ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Ongeldig post ID.', THEME_TEXT_DOMAIN ) ] );
		}

		$usages = $this->get_page_part_usage( $post_id );
		$count  = count( $usages );

		if ( $count > 0 ) {
			$page_titles = array_map( function ( $usage ) {
				return $usage['post_title'] ?: __( '(geen titel)', THEME_TEXT_DOMAIN );
			}, $usages );

			wp_send_json_success( [
				'in_use' => true,
				'count'  => $count,
				'pages'  => $page_titles,
			] );
		} else {
			wp_send_json_success( [
				'in_use' => false,
				'count'  => 0,
				'pages'  => [],
			] );
		}
	}

	/**
	 * Enqueue usage warning scripts for page_part admin screens
	 *
	 * @param string $hook Current admin page hook
	 *
	 * @return void
	 */
	public function enqueue_usage_warning_scripts( string $hook ): void {
		$screen = get_current_screen();

		// Only load on page_part list and edit screens
		if ( ! $screen || $screen->post_type !== 'page_part' ) {
			return;
		}

		// Load on edit.php (list) and post.php (edit) screens
		if ( ! in_array( $hook, [ 'edit.php', 'post.php' ], true ) ) {
			return;
		}

		wp_enqueue_script(
			'nok-page-part-usage-warning',
			get_template_directory_uri() . '/assets/js/nok-page-part-usage-warning.js',
			[ 'jquery', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n' ],
			filemtime( get_template_directory() . '/assets/js/nok-page-part-usage-warning.js' ),
			true
		);

		wp_localize_script( 'nok-page-part-usage-warning', 'nokPagePartUsage', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nok_page_part_usage' ),
			'i18n'    => [
				'warningTitle'       => __( 'Let op: Page Part in gebruik', THEME_TEXT_DOMAIN ),
				'warningMessage'     => __( 'Deze page part wordt gebruikt op de volgende pagina\'s:', THEME_TEXT_DOMAIN ),
				'trashWarning'       => __( 'Als je deze page part naar de prullenbak verplaatst, zal het blok niet meer zichtbaar zijn op deze pagina\'s.', THEME_TEXT_DOMAIN ),
				'unpublishWarning'   => __( 'Als je deze page part depubliceert, zal het blok niet meer zichtbaar zijn voor bezoekers op deze pagina\'s.', THEME_TEXT_DOMAIN ),
				'confirmTrash'       => __( 'Naar prullenbak verplaatsen', THEME_TEXT_DOMAIN ),
				'confirmUnpublish'   => __( 'Toch depubliceren', THEME_TEXT_DOMAIN ),
				'cancel'             => __( 'Annuleren', THEME_TEXT_DOMAIN ),
				'andMorePages'       => __( 'en %d andere pagina\'s...', THEME_TEXT_DOMAIN ),
			],
		] );
	}
}