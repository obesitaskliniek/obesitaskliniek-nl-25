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

		// Localize data for React components
		wp_localize_script(
			'nok-page-part-design-selector',
			'PagePartDesignSettings',
			[
				'registry' => $this->get_page_part_registry(),
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			]
		);
		wp_localize_script(
			'nok-page-part-design-selector',
			'PagePartDesignSettings',
			[
				'registry' => $this->get_page_part_registry(),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'nok_preview_state_nonce' )  // Add this line
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
		add_action( 'wp_ajax_store_preview_state', function() {
			// Verify nonce first
			if ( !wp_verify_nonce( $_POST['nonce'] ?? '', 'nok_preview_state_nonce' ) ) {
				wp_send_json_error( 'Invalid nonce' );
				return;
			}

			$post_id = (int) $_POST['post_id'];
			$editor_state_raw = $_POST['editor_state'] ?? '';

			if ( $post_id && !empty($editor_state_raw) ) {
				// Decode the complete editor state
				$editor_state = json_decode(stripslashes($editor_state_raw), true);

				if (json_last_error() === JSON_ERROR_NONE && is_array($editor_state)) {
					// Sanitize the complete state
					$sanitized_state = [
						'title' => sanitize_text_field($editor_state['title'] ?? ''),
						'content' => wp_kses_post($editor_state['content'] ?? ''),
						'excerpt' => sanitize_textarea_field($editor_state['excerpt'] ?? ''),
						'meta' => $this->sanitize_meta_fields($editor_state['meta'] ?? [])
					];

					// Store in single transient
					set_transient( "preview_editor_state_{$post_id}", $sanitized_state, 300 );

					// DEBUG: Dump transient to log file
					$log_file = THEME_ROOT_ABS . '/debug-transient.log';
					$timestamp = date('Y-m-d H:i:s');
					$log_entry = "\n--- {$timestamp} - Post ID: {$post_id} ---\n";
					$log_entry .= print_r($sanitized_state, true);
					$log_entry .= "\n--- End Entry ---\n";
					file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

					wp_send_json_success( "Stored complete editor state for post {$post_id}" );
				} else {
					error_log("JSON decode error: " . json_last_error_msg());
					wp_send_json_error( 'Invalid editor state data' );
				}
			} else {
				wp_send_json_error( 'Missing post ID or editor state' );
			}
		});
	}

	/**
	 * Sanitize meta fields based on their registered field types
	 */
	private function sanitize_meta_fields( array $meta_fields ): array {
		$sanitized = [];
		$registry = $this->get_page_part_registry();

		foreach ( $meta_fields as $meta_key => $meta_value ) {
			// Find the field definition to get proper sanitization
			$field_found = false;
			foreach ( $registry as $template_slug => $template_data ) {
				if ( empty( $template_data['custom_fields'] ) ) {
					continue;
				}

				foreach ( $template_data['custom_fields'] as $field ) {
					if ( $field['meta_key'] === $meta_key ) {
						$sanitize_callback = $this->get_sanitize_callback( $field['type'] );
						$sanitized[$meta_key] = call_user_func( $sanitize_callback, $meta_value );
						$field_found = true;
						break 2; // Break out of both loops
					}
				}
			}

			// If field not found in registry, use default sanitization
			if ( !$field_found ) {
				$sanitized[$meta_key] = sanitize_text_field( $meta_value );
			}
		}

		return $sanitized;
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
				$preview_state = get_transient( "preview_editor_state_{$post_id}" );

				if ( $preview_state && is_array( $preview_state ) ) {
					error_log( "Preview rendering for post {$post_id} with unified state" );

					// Filter meta values during preview
					add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key ) use ( $post_id, $preview_state ) {
						// Only filter for the current post being previewed
						if ( $object_id != $post_id ) {
							return $value;
						}

						// Check if this meta key exists in our preview state
						if ( isset( $preview_state['meta'][$meta_key] ) ) {
							error_log( "Filtering {$meta_key} for preview: {$preview_state['meta'][$meta_key]}" );
							return [ $preview_state['meta'][$meta_key] ];
						}

						return $value;
					}, 5, 3 );

					// Filter title during preview
					add_filter( 'the_title', function( $title, $post_id_filter ) use ( $post_id, $preview_state ) {
						if ( $post_id_filter == $post_id && isset( $preview_state['title'] ) ) {
							error_log( "Filtering title for preview: {$preview_state['title']}" );
							return $preview_state['title'];
						}
						return $title;
					}, 5, 2 );

					// Filter content during preview
					add_filter( 'the_content', function( $content ) use ( $post_id, $preview_state ) {
						if ( get_the_ID() == $post_id && isset( $preview_state['content'] ) ) {
							error_log( "Filtering content for preview" );
							return $preview_state['content'];
						}
						return $content;
					}, 5 );
				}
			}
		}, 5 );
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
	 * Save editor state from unified transient or fallback methods
	 */
	public function save_design_meta( int $post_id, \WP_Post $post ): void {
		// Let WordPress REST API handle saves automatically
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Check for unified transient data
		$preview_state = get_transient( "preview_editor_state_{$post_id}" );

		if ( $preview_state && is_array( $preview_state ) ) {
			// Save title and content
			if ( isset( $preview_state['title'] ) ) {
				wp_update_post([
					'ID' => $post_id,
					'post_title' => $preview_state['title']
				]);
			}

			if ( isset( $preview_state['content'] ) ) {
				wp_update_post([
					'ID' => $post_id,
					'post_content' => $preview_state['content']
				]);
			}

			// Save all meta fields
			if ( isset( $preview_state['meta'] ) && is_array( $preview_state['meta'] ) ) {
				foreach ( $preview_state['meta'] as $meta_key => $meta_value ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}

			// Clean up transient
			delete_transient( "preview_editor_state_{$post_id}" );
			return;
		}

		// Fallback: traditional form submission (keep existing logic for compatibility)
		if ( isset( $_POST['page_part_design_slug'] ) ) {
			$new = sanitize_key( wp_unslash( $_POST['page_part_design_slug'] ) );
			update_post_meta( $post_id, 'design_slug', $new );
		}

		// Handle traditional form submission for custom fields
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
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'embed_page_part_callback' ],
			]
		);
	}

	/**
	 * REST API callback for embedding page parts
	 */
	public function embed_page_part_callback( \WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'page_part' ) {
			status_header( 404 );
			exit;
		}

		// Check for preview data from transients first, then fall back to saved meta
		$preview_design = get_transient( "preview_design_slug_{$id}" );
		$preview_meta   = get_transient( "preview_all_meta_{$id}" );

		$design = $preview_design ?: get_post_meta( $id, 'design_slug', true ) ?: 'nok-hero';

		// Set up meta filtering for the embed rendering if we have preview data
		if ( $preview_design || $preview_meta ) {
			add_filter( 'get_post_metadata', function ( $value, $object_id, $meta_key ) use ( $id, $preview_design, $preview_meta ) {
				if ( $object_id != $id ) {
					return $value;
				}

				// Handle design_slug
				if ( $meta_key === 'design_slug' && $preview_design ) {
					return [ $preview_design ];
				}

				// Handle custom fields from transient
				if ( $preview_meta && is_array( $preview_meta ) && isset( $preview_meta[ $meta_key ] ) ) {
					return [ $preview_meta[ $meta_key ] ];
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
		$html      .= '</head><body>
        <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-top halign-center valign-center">
            <a href="' . $edit_link . '" type="button" target="_blank" class="nok-button nok-align-self-to-sm-stretch fill-group-column nok-bg-darkerblue nok-text-contrast no-shadow" tabindex="0">Bewerken</a>
        </nok-screen-mask>';

		// Store original state
		global $post, $wp_query;
		$original_post = $post;
		$original_wp_query = $wp_query;

        // FIXED: Properly set up the post and pass it to template via $args
		$post = get_post( $id );

	    // Set up a proper WordPress query context
	    $wp_query = new \WP_Query( [
		    'post_type'      => 'page_part',
		    'p'              => $id,
		    'posts_per_page' => 1
	    ] );

	    // Make sure we have the post in the loop
	    if ( $wp_query->have_posts() ) {
		    $wp_query->the_post(); // This sets up all the globals properly

		    ob_start();

		    // FIXED: Pass the post object to the template via $args
		    $args = [ 'post' => $post ];

		    // Include with the proper args
		    include get_theme_file_path( "template-parts/page-parts/{$design}.php" );
		    $html .= ob_get_clean();

		    wp_reset_postdata();
	    } else {
		    $html .= '<p style="color: red; padding: 20px;">Error: Could not load page part data</p>';
	    }

	    // Restore original state
	    $post     = $original_post;
	    $wp_query = $original_wp_query;

		$html .= '</body></html>';

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