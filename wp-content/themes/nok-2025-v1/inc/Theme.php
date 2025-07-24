<?php
// inc/Theme.php

namespace NOK2025\V1;

use NOK2025\V1\PostTypes;

final class Theme {
	private static ?Theme $instance = null;

	// Settings store (can hold customizer values)
	private array $settings = [];

	// Registry of page part templates (lazy-loaded)
	private ?array $part_registry = null;

	public function __construct() {
		// Ensure CPTs are registered
		new PostTypes();
	}

	public static function get_instance(): Theme {
		if ( self::$instance === null ) {
			self::$instance = new self();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	private function setup_hooks(): void {
		// Core theme setup
		add_action( 'init', [ $this, 'theme_supports' ] );
		add_action( 'init', [ $this, 'register_design_meta' ] );

		// Assets
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets'] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'backend_assets'] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets'] );
		add_action( 'admin_head', [ $this, 'custom_editor_inline_styles'] );

		// Customizer
		add_action( 'customize_register', [ $this, 'register_customizer' ] );

		// Page part preview system
		add_action( 'init', [ $this, 'handle_preview_meta' ] );
		add_action( 'init', [ $this, 'handle_preview_rendering' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_preview_meta_box' ] );
		add_action( 'save_post_page_part', [ $this, 'save_design_meta' ], 10, 2 );

		// REST API endpoints
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Content filters
		add_filter( 'the_content', [ $this, 'enhance_paragraph_classes' ] );
		add_filter( 'show_admin_bar', [ $this, 'maybe_hide_admin_bar' ] );
	}

	// =============================================================================
	// CORE THEME SETUP
	// =============================================================================

	public function theme_supports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', [ 'search-form', 'comment-form' ] );
	}

	public function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		\NOK2025\V1\Customizer::register( $wp_customize );
	}

	public function get_setting( string $key, $default = null ) {
		return get_theme_mod( $key, $default );
	}

	// =============================================================================
	// ASSETS
	// =============================================================================

	public function frontend_assets(): void {
		wp_register_style(
			'nok-components-css',
			THEME_ROOT . '/assets/css/nok-components.css',
			[],
			filemtime( THEME_ROOT_ABS . '/assets/css/nok-components.css')
		);
		wp_register_style(
			'nok-colors-css',
			THEME_ROOT . '/assets/css/color_tests-v2.css',
			[],
			filemtime( THEME_ROOT_ABS . '/assets/css/color_tests-v2.css')
		);
	}

	public function backend_assets(): void {
		$parts = $this->get_page_part_registry();

		foreach ( $parts as $slug => $meta ) {
			$css_file = THEME_ROOT_ABS . "/template-parts/page-parts/{$meta['slug']}.css";
			if ( file_exists( $css_file ) ) {
				wp_enqueue_style(
					$meta['slug'],
					THEME_ROOT . "/template-parts/page-parts/{$meta['slug']}.css",
					[],
					filemtime( $css_file )
				);
			}
		}
	}

	public function admin_assets( $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->post_type !== 'page_part' ) {
			return;
		}

		// Preview system script
		$asset = require get_theme_file_path( '/assets/js/nok-page-part-preview.asset.php' );
		wp_enqueue_script(
			'nok-page-part-live-preview',
			get_stylesheet_directory_uri() . '/assets/js/nok-page-part-preview.js',
			$asset['dependencies'],
			$asset['version']
		);

		// React design selector component
		$react_asset = require get_theme_file_path( '/assets/js/nok-page-part-design-selector.asset.php' );
		wp_enqueue_script(
			'nok-page-part-design-selector',
			get_stylesheet_directory_uri() . '/assets/js/nok-page-part-design-selector.js',
			$react_asset['dependencies'],
			$react_asset['version']
		);

		// Localize data for React component
		wp_localize_script(
			'nok-page-part-design-selector',
			'PagePartDesignSettings',
			[
				'registry' => $this->get_page_part_registry(),
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			]
		);
	}

	public function custom_editor_inline_styles(): void {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			echo '<style>
                .editor-styles-wrapper {
                    min-height: 35vh !important;
                }
                .editor-styles-wrapper::after {
                    height: 0 !important;
                }
            </style>';
		}
	}

	// =============================================================================
	// PAGE PART REGISTRY
	// =============================================================================

	/**
	 * Scan all page-part templates and pull their metadata including custom fields.
	 *
	 * @return array Array of [ slug => [ 'name' => ..., 'description' => ..., 'icon' => ..., 'custom_fields' => [...] ] ]
	 */
	private function get_page_part_registry(): array {
		if ( $this->part_registry !== null ) {
			return $this->part_registry;
		}

		$files = glob( THEME_ROOT_ABS . '/template-parts/page-parts/*.php' );
		$this->part_registry = [];

		foreach ( $files as $file ) {
			$data = get_file_data( $file, [
				'name'          => 'Template Name',
				'description'   => 'Description',
				'slug'          => 'Slug',
				'icon'          => 'Icon',
				'custom_fields' => 'Custom Fields',
			] );

			if ( empty( $data['slug'] ) ) {
				$data['slug'] = sanitize_title( $data['name'] ?? basename( $file, '.php' ) );
			}

			// Parse custom fields
			$data['custom_fields'] = $this->parse_custom_fields( $data['custom_fields'], $data['slug'] );

			$this->part_registry[ $data['slug'] ] = $data;
		}

		return $this->part_registry;
	}

	/**
	 * Parse custom fields definition from template header
	 *
	 * @param string $fields_string Format: "field1:type,field2:type,field3:type"
	 * @param string $template_slug Template slug for field prefixing
	 * @return array Parsed field definitions
	 */
	private function parse_custom_fields( string $fields_string, string $template_slug ): array {
		if ( empty( $fields_string ) ) {
			return [];
		}

		$fields = [];
		$field_definitions = explode( ',', $fields_string );

		foreach ( $field_definitions as $definition ) {
			$definition = trim( $definition );
			if ( empty( $definition ) ) {
				continue;
			}

			$parts = explode( ':', $definition );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$field_name = trim( $parts[0] );
			$field_type = trim( $parts[1] );

			// Create prefixed meta key
			$meta_key = $template_slug . '_' . $field_name;

			$fields[] = [
				'name'     => $field_name,
				'type'     => $field_type,
				'meta_key' => $meta_key,
				'label'    => $this->generate_field_label( $field_name ),
			];
		}

		return $fields;
	}

	/**
	 * Generate a human-readable label from field name
	 *
	 * @param string $field_name
	 * @return string
	 */
	private function generate_field_label( string $field_name ): string {
		// Convert snake_case or kebab-case to Title Case
		$label = str_replace( [ '_', '-' ], ' ', $field_name );
		return ucwords( $label );
	}

	// =============================================================================
	// PAGE PART META & PREVIEW SYSTEM
	// =============================================================================

	/**
	 * Register design_slug meta field and all custom fields for REST API access
	 */
	public function register_design_meta(): void {
		// Register the main design_slug field
		register_post_meta( 'page_part', 'design_slug', [
			'type'              => 'string',
			'show_in_rest'      => true,
			'single'            => true,
			'sanitize_callback' => 'sanitize_key',
			'default'           => '',
		] );

		// Register all custom fields from templates
		$registry = $this->get_page_part_registry();

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
					'default'           => $this->get_default_value( $field['type'] ),
				] );
			}
		}
	}

	/**
	 * Get appropriate sanitize callback for field type
	 */
	private function get_sanitize_callback( string $field_type ) {
		switch ( $field_type ) {
			case 'url':
				return 'esc_url_raw';
			case 'textarea':
				return 'sanitize_textarea_field';
			case 'repeater':
				return [ $this, 'sanitize_json_field' ];
			case 'text':
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Get meta type for WordPress registration
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
	 */
	private function get_default_value( string $field_type ) {
		switch ( $field_type ) {
			case 'repeater':
				return '[]'; // Empty JSON array
			default:
				return '';
		}
	}

	/**
	 * Sanitize JSON field data
	 */
	public function sanitize_json_field( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return wp_json_encode( $decoded );
			}
		}
		return '[]';
	}

	/**
	 * Handle AJAX requests for storing preview meta in transients
	 */
	public function handle_preview_meta(): void {
		add_action( 'wp_ajax_store_preview_meta', function() {
			$post_id = (int) $_POST['post_id'];
			$design_slug = sanitize_key( $_POST['design_slug'] );

			// Handle all custom fields meta - comes as JSON string from JavaScript
			$all_meta_raw = $_POST['all_meta'] ?? '';

			if ( $post_id && $design_slug ) {
				// Store design_slug as before
				set_transient( "preview_design_slug_{$post_id}", $design_slug, 300 );

				// Store all custom fields meta after decoding JSON
				if (!empty($all_meta_raw)) {
					// Decode the JSON string that was sent from JavaScript
					$all_meta = json_decode(stripslashes($all_meta_raw), true);

					if (json_last_error() === JSON_ERROR_NONE && is_array($all_meta)) {
						set_transient( "preview_all_meta_{$post_id}", $all_meta, 300 );
						wp_send_json_success( "Stored design_slug: {$design_slug} and " . count($all_meta) . " meta fields" );
					} else {
						// Log the JSON error for debugging
						error_log("JSON decode error: " . json_last_error_msg() . " | Raw data: " . $all_meta_raw);
						wp_send_json_success( "Stored design_slug: {$design_slug} (custom fields failed to parse)" );
					}
				} else {
					wp_send_json_success( "Stored design_slug: {$design_slug} (no custom fields)" );
				}
			} else {
				wp_send_json_error( 'Missing data' );
			}
		});
	}


	/**
	 * Override meta values during preview rendering using transient data
	 */
	public function handle_preview_rendering(): void {
		// Hook earlier in the WordPress loading process
		add_action( 'wp', function() {
			// Only apply during preview of page_part posts
			if ( is_preview() && get_post_type() === 'page_part' ) {
				$post_id = get_the_ID();
				$preview_design = get_transient( "preview_design_slug_{$post_id}" );
				$preview_meta = get_transient( "preview_all_meta_{$post_id}" );

				// Debug logging
				if ( $preview_design || $preview_meta ) {
					error_log( "Preview rendering for post {$post_id}: design={$preview_design}, meta_count=" . ( is_array($preview_meta) ? count($preview_meta) : 'none' ) );
				}

				// Filter meta values during preview - use higher priority
				add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key ) use ( $post_id, $preview_design, $preview_meta ) {
					// Only filter for the current post being previewed
					if ( $object_id != $post_id ) {
						return $value;
					}

					// Handle design_slug
					if ( $meta_key === 'design_slug' && $preview_design ) {
						error_log( "Filtering design_slug for preview: {$preview_design}" );
						return [ $preview_design ];
					}

					// Handle custom fields from transient
					if ( $preview_meta && is_array( $preview_meta ) && isset( $preview_meta[$meta_key] ) ) {
						error_log( "Filtering {$meta_key} for preview: {$preview_meta[$meta_key]}" );
						return [ $preview_meta[$meta_key] ];
					}

					return $value;
				}, 5, 3 ); // Lower number = higher priority
			}
		}, 5 ); // Hook early in wp action
	}

	/**
	 * Add preview meta box to page_part edit screen
	 */
	public function add_preview_meta_box(): void {
		add_meta_box(
			'nok-page-part-preview',
			__( 'NOK - Live Page Part Preview', THEME_TEXT_DOMAIN ),
			[ $this, 'render_preview_meta_box' ],
			'page_part',
			'normal',
			'high'
		);
	}

	/**
	 * Render the preview meta box content
	 */
	public function render_preview_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'nok_preview_nonce', 'nok_preview_nonce' );

		echo '<button id="nok-page-part-preview-button" type="button" class="button button-primary">'
		     . esc_html__( 'Refresh Preview', THEME_TEXT_DOMAIN ) . '</button>';
		echo '<div><p>' . esc_html__( 'Let op: Page Parts zijn niet afzonderlijk publiek benaderbaar (of indexeerbaar) en zijn ontworpen om onderdeel van een pagina te zijn.', THEME_TEXT_DOMAIN ) . '</p></div>';
		echo '<div id="nok-page-part-preview-root" style="border:1px solid #ddd; min-height:300px"></div>';
	}

	/**
	 * Save design meta from React component (via transient) or fallback methods
	 */
	public function save_design_meta( int $post_id, \WP_Post $post ): void {
		// Let WordPress REST API handle saves automatically
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Check for transient values (from React component)
		$transient_design = get_transient( "preview_design_slug_{$post_id}" );
		$transient_meta = get_transient( "preview_all_meta_{$post_id}" );

		// Save design_slug
		if ( $transient_design ) {
			update_post_meta( $post_id, 'design_slug', $transient_design );
			delete_transient( "preview_design_slug_{$post_id}" );
		}

		// NEW: Save all custom fields from transient
		if ( $transient_meta && is_array( $transient_meta ) ) {
			$registry = $this->get_page_part_registry();

			foreach ( $registry as $template_slug => $template_data ) {
				if ( empty( $template_data['custom_fields'] ) ) {
					continue;
				}

				foreach ( $template_data['custom_fields'] as $field ) {
					$meta_key = $field['meta_key'];

					if ( isset( $transient_meta[$meta_key] ) ) {
						$sanitize_callback = $this->get_sanitize_callback( $field['type'] );
						$sanitized_value = call_user_func( $sanitize_callback, $transient_meta[$meta_key] );
						update_post_meta( $post_id, $meta_key, $sanitized_value );
					}
				}
			}

			delete_transient( "preview_all_meta_{$post_id}" );
			return;
		}

		// Fallback: traditional form submission (if HTML form is used)
		if ( isset( $_POST['page_part_design_slug'] ) ) {
			$new = sanitize_key( wp_unslash( $_POST['page_part_design_slug'] ) );
			update_post_meta( $post_id, 'design_slug', $new );
		}

		// NEW: Handle traditional form submission for custom fields
		$registry = $this->get_page_part_registry();
		foreach ( $registry as $template_slug => $template_data ) {
			if ( empty( $template_data['custom_fields'] ) ) {
				continue;
			}

			foreach ( $template_data['custom_fields'] as $field ) {
				$form_field_name = 'page_part_' . $field['meta_key'];

				if ( isset( $_POST[$form_field_name] ) ) {
					$sanitize_callback = $this->get_sanitize_callback( $field['type'] );
					$sanitized_value = call_user_func( $sanitize_callback, wp_unslash( $_POST[$form_field_name] ) );
					update_post_meta( $post_id, $field['meta_key'], $sanitized_value );
				}
			}
		}
	}

	// =============================================================================
	// REST API ENDPOINTS
	// =============================================================================

	/**
	 * Register custom REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'nok-2025-v1/v1',
			'/embed-page-part/(?P<id>\d+)',
			[
				'methods'  => 'GET',
				'permission_callback' => '__return_true',
				'callback' => [ $this, 'embed_page_part_callback' ],
			]
		);
	}

	/**
	 * REST API callback for embedding page parts
	 */
	public function embed_page_part_callback( \WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'page_part' ) {
			status_header( 404 );
			exit;
		}

		// Check for preview data from transients first, then fall back to saved meta
		$preview_design = get_transient( "preview_design_slug_{$id}" );
		$preview_meta = get_transient( "preview_all_meta_{$id}" );

		$design = $preview_design ?: get_post_meta( $id, 'design_slug', true ) ?: 'nok-hero';

		// Set up meta filtering for the embed rendering if we have preview data
		if ( $preview_design || $preview_meta ) {
			add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key ) use ( $id, $preview_design, $preview_meta ) {
				if ( $object_id != $id ) {
					return $value;
				}

				// Handle design_slug
				if ( $meta_key === 'design_slug' && $preview_design ) {
					return [ $preview_design ];
				}

				// Handle custom fields from transient
				if ( $preview_meta && is_array( $preview_meta ) && isset( $preview_meta[$meta_key] ) ) {
					return [ $preview_meta[$meta_key] ];
				}

				return $value;
			}, 10, 3 );
		}

		$css_uris = [
			get_stylesheet_directory_uri() . '/assets/css/nok-components.css',
			get_stylesheet_directory_uri() . '/assets/css/color_tests-v2.css',
			get_stylesheet_directory_uri() . "/template-parts/page-parts/{$design}.css",
			get_stylesheet_directory_uri() . '/assets/css/nok-page-parts-editor-styles.css',
		];

		header( 'Content-Type: text/html; charset=utf-8' );

		$html = '<!doctype html><html><head><meta charset="utf-8">';
		foreach ( $css_uris as $uri ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( $uri ) . '">';
		}

		$edit_link = admin_url( "post.php?post={$id}&action=edit" );
		$html .= '</head><body>
        <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-top halign-center valign-center">
            <a href="' . $edit_link . '" type="button" target="_blank" class="nok-button nok-align-self-to-sm-stretch fill-group-column nok-bg-darkerblue nok-text-contrast no-shadow" tabindex="0">Bewerken</a>
        </nok-screen-mask>';

		// Set up the global $post for the template
		global $post;
		$post = get_post( $id );
		setup_postdata( $post );

		ob_start();
		include get_theme_file_path( "template-parts/page-parts/{$design}.php" );
		$html .= ob_get_clean();
		$html .= '</body></html>';

		wp_reset_postdata();

		print $html;
		exit;
	}

	// =============================================================================
	// CONTENT FILTERS
	// =============================================================================

	/**
	 * Add paragraph classes to content
	 */
	public function enhance_paragraph_classes( $content ) {
		return str_replace( '<p>', '<p class="wp-block-paragraph">', $content );
	}

	/**
	 * Hide admin bar when requested via URL parameter
	 */
	public function maybe_hide_admin_bar( $show ) {
		if ( isset( $_GET['hide_adminbar'] ) ) {
			return false;
		}
		return $show;
	}
}