<?php
// inc/PageParts/Registry.php

namespace NOK2025\V1\PageParts;

/**
 * Registry - Page part template registry and metadata parser
 *
 * Responsible for:
 * - Scanning page-parts directory for template files
 * - Parsing template header metadata (name, description, icon)
 * - Extracting and parsing custom field definitions from templates
 * - Converting field definitions to structured arrays
 * - Generating human-readable labels from field names
 * - Caching parsed registry for performance
 *
 * Field definition syntax supports:
 * - Simple fields: "field_name:text"
 * - Select fields: "position:select(left|right)"
 * - Checkbox fields: "enabled:checkbox(true)"
 * - Icon selector: "icon:icon-selector"
 * - Taxonomy selector: "cats:taxonomy(category)" or "cat:taxonomy(category)!single"
 * - Repeaters: "items:repeater(title:text,url:url)"
 * - Post repeaters: "posts:post_repeater(post:news,events)"
 * - Flags: !page-editable, !default(value), !descr[description]
 *
 * @example Get all registered page parts
 * $registry = new Registry();
 * $parts = $registry->get_registry();
 * foreach ($parts as $slug => $config) {
 *     echo "{$config['name']}: {$config['description']}\n";
 * }
 *
 * @example Access field definitions for a page part
 * $registry = new Registry();
 * $parts = $registry->get_registry();
 * $hero_fields = $parts['nok-hero']['custom_fields'];
 * foreach ($hero_fields as $field) {
 *     echo "{$field['label']} ({$field['type']})\n";
 * }
 *
 * @package NOK2025\V1\PageParts
 */
class Registry {
	private ?array $part_registry = null;
	private ?array $block_parts_registry = null;

	/** @var string Transient key for cached page-parts registry */
	private const CACHE_KEY = 'nok_page_parts_registry';

	/** @var string Transient key for cached block-parts registry */
	private const BLOCK_PARTS_CACHE_KEY = 'nok_block_parts_registry';

	/** @var int Cache duration in seconds (1 hour in production, 0 in dev) */
	private const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Get complete page part registry
	 *
	 * Scans all page-part template files and parses their header metadata,
	 * including custom field definitions. Results are cached:
	 * - Instance cache: Prevents re-parsing during same request
	 * - Transient cache: Persists across requests (production only)
	 *
	 * Cache invalidation: Automatic when any template file is modified
	 * (uses max mtime of all template files as cache key component)
	 *
	 * @return array Associative array keyed by page part slug containing:
	 *               - 'name' (string): Template display name
	 *               - 'description' (string): Template description
	 *               - 'slug' (string): URL-safe identifier
	 *               - 'icon' (string): Icon identifier
	 *               - 'custom_fields' (array): Parsed field definitions
	 *               - 'featured_image_overridable' (bool): Whether featured image can be overridden
	 *
	 * @example Get all page parts
	 * $registry = $this->get_registry();
	 * // Returns: ['nok-hero' => [...], 'nok-cta' => [...], ...]
	 *
	 * @example Check if page part exists
	 * $registry = $this->get_registry();
	 * if (isset($registry['nok-hero'])) {
	 *     $hero_config = $registry['nok-hero'];
	 * }
	 *
	 * @example Get field definitions for a page part
	 * $registry = $this->get_registry();
	 * $fields = $registry['nok-cta']['custom_fields'];
	 */
	public function get_registry(): array {
		// Level 1: Instance cache (same request)
		if ( $this->part_registry !== null ) {
			return $this->part_registry;
		}

		$files = glob( THEME_ROOT_ABS . '/template-parts/page-parts/*.php' );

		// Calculate cache key based on latest file modification time
		// This auto-invalidates cache when any template file changes
		$max_mtime = 0;
		foreach ( $files as $file ) {
			$mtime = filemtime( $file );
			if ( $mtime > $max_mtime ) {
				$max_mtime = $mtime;
			}
		}
		$cache_key = self::CACHE_KEY . '_' . $max_mtime;

		// Level 2: Transient cache (across requests) - only in production
		$use_transient_cache = defined( 'SITE_LIVE' ) && SITE_LIVE;
		if ( $use_transient_cache ) {
			$cached = get_transient( $cache_key );
			if ( $cached !== false && is_array( $cached ) ) {
				$this->part_registry = $cached;
				return $this->part_registry;
			}
		}

		// Parse all template files
		$this->part_registry = [];

		foreach ( $files as $file ) {
			$data = $this->get_custom_file_data( $file, [
				'name'                       => 'Template Name',
				'description'                => 'Description',
				'slug'                       => 'Slug',
				'icon'                       => 'Icon',
				'custom_fields'              => 'Custom Fields',
				'featured_image_overridable' => 'Featured Image Overridable',
				'restriction'                => 'Restriction'
			] );

			if ( empty( $data['slug'] ) ) {
				$data['slug'] = sanitize_title( $data['name'] ?? basename( $file, '.php' ) );
			}

			// Parse custom fields
			$data['custom_fields'] = $this->parse_custom_fields( $data['custom_fields'], $data['slug'] );

			// Parse featured image overridable flag
			$data['featured_image_overridable'] = strtolower( $data['featured_image_overridable'] ?? '' ) === 'true';

			// Parse restriction
			$data['restriction'] = $this->parse_restriction( $data['restriction'] ?? '' );

			$this->part_registry[ $data['slug'] ] = $data;
		}

		// Store in transient cache (production only)
		if ( $use_transient_cache ) {
			set_transient( $cache_key, $this->part_registry, self::CACHE_DURATION );

			// Clean up old cache keys (different mtime)
			$this->cleanup_old_cache_keys( $cache_key );
		}

		return $this->part_registry;
	}

	/**
	 * Cleanup old transient cache keys
	 *
	 * When template files are modified, the cache key changes. This removes
	 * stale cache entries to prevent database bloat.
	 *
	 * @param string $current_key Current valid cache key to preserve
	 */
	private function cleanup_old_cache_keys( string $current_key ): void {
		global $wpdb;

		// Delete old transients matching our prefix but not current key
		// This is a simple approach - for high-traffic sites, consider a scheduled cleanup
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name != %s AND option_name != %s",
				$wpdb->esc_like( '_transient_' . self::CACHE_KEY . '_' ) . '%',
				'_transient_' . $current_key,
				'_transient_timeout_' . $current_key
			)
		);
	}

	/**
	 * Clear the registry cache
	 *
	 * Call this when you need to force a refresh (e.g., after saving a template file).
	 */
	public static function clear_cache(): void {
		global $wpdb;

		// Clear all page-parts registry transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%' . $wpdb->esc_like( self::CACHE_KEY ) . '%'
			)
		);

		// Clear all block-parts registry transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%' . $wpdb->esc_like( self::BLOCK_PARTS_CACHE_KEY ) . '%'
			)
		);
	}

	/**
	 * Get block parts registry
	 *
	 * Scans template-parts/block-parts/ for block templates.
	 * Uses same header parsing as page parts.
	 *
	 * @return array Associative array keyed by block part slug containing:
	 *               - 'name' (string): Template display name
	 *               - 'description' (string): Template description
	 *               - 'slug' (string): URL-safe identifier
	 *               - 'icon' (string): Icon identifier
	 *               - 'keywords' (array): Search keywords
	 *               - 'custom_fields' (array): Parsed field definitions
	 *
	 * @example Get all block parts
	 * $registry = $this->get_block_parts_registry();
	 * // Returns: ['general-section' => [...], 'video-section' => [...], ...]
	 */
	public function get_block_parts_registry(): array {
		// Level 1: Instance cache (same request)
		if ( $this->block_parts_registry !== null ) {
			return $this->block_parts_registry;
		}

		$folder_path = THEME_ROOT_ABS . '/template-parts/block-parts';

		// Return empty if folder doesn't exist yet
		if ( ! is_dir( $folder_path ) ) {
			$this->block_parts_registry = [];
			return $this->block_parts_registry;
		}

		$files = glob( $folder_path . '/*.php' );

		if ( empty( $files ) ) {
			$this->block_parts_registry = [];
			return $this->block_parts_registry;
		}

		// Calculate cache key based on latest file modification time
		$max_mtime = 0;
		foreach ( $files as $file ) {
			$mtime = filemtime( $file );
			if ( $mtime > $max_mtime ) {
				$max_mtime = $mtime;
			}
		}
		$cache_key = self::BLOCK_PARTS_CACHE_KEY . '_' . $max_mtime;

		// Level 2: Transient cache (across requests) - only in production
		$use_transient_cache = defined( 'SITE_LIVE' ) && SITE_LIVE;
		if ( $use_transient_cache ) {
			$cached = get_transient( $cache_key );
			if ( $cached !== false && is_array( $cached ) ) {
				$this->block_parts_registry = $cached;
				return $this->block_parts_registry;
			}
		}

		// Parse all template files
		$this->block_parts_registry = [];

		foreach ( $files as $file ) {
			$data = $this->get_custom_file_data( $file, [
				'name'          => 'Block Part',
				'description'   => 'Description',
				'slug'          => 'Slug',
				'icon'          => 'Icon',
				'keywords'      => 'Keywords',
				'custom_fields' => 'Custom Fields',
			] );

			if ( empty( $data['slug'] ) ) {
				$data['slug'] = sanitize_title( $data['name'] ?? basename( $file, '.php' ) );
			}

			// Parse custom fields
			$data['custom_fields'] = $this->parse_custom_fields( $data['custom_fields'], $data['slug'] );

			// Parse keywords into array
			$data['keywords'] = $this->parse_keywords( $data['keywords'] ?? '' );

			$this->block_parts_registry[ $data['slug'] ] = $data;
		}

		// Store in transient cache (production only)
		if ( $use_transient_cache ) {
			set_transient( $cache_key, $this->block_parts_registry, self::CACHE_DURATION );

			// Clean up old cache keys (different mtime)
			$this->cleanup_old_block_parts_cache_keys( $cache_key );
		}

		return $this->block_parts_registry;
	}

	/**
	 * Cleanup old block-parts transient cache keys
	 *
	 * @param string $current_key Current valid cache key to preserve
	 */
	private function cleanup_old_block_parts_cache_keys( string $current_key ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name != %s AND option_name != %s",
				$wpdb->esc_like( '_transient_' . self::BLOCK_PARTS_CACHE_KEY . '_' ) . '%',
				'_transient_' . $current_key,
				'_transient_timeout_' . $current_key
			)
		);
	}

	/**
	 * Parse keywords string into array
	 *
	 * @param string $keywords_string Comma-separated keywords
	 * @return array Array of keywords
	 */
	private function parse_keywords( string $keywords_string ): array {
		if ( empty( $keywords_string ) ) {
			return [];
		}

		return array_map( 'trim', explode( ',', $keywords_string ) );
	}

	/**
	 * Register cache invalidation hooks
	 *
	 * Hooks into WordPress actions to automatically clear cache when
	 * page part content is modified. Important for healthcare content
	 * where stale information can have material impact.
	 */
	public static function register_invalidation_hooks(): void {
		// Invalidate on page_part save
		add_action( 'save_post_page_part', [ self::class, 'on_page_part_save' ], 10, 2 );

		// Invalidate on page_part delete
		add_action( 'before_delete_post', [ self::class, 'on_page_part_delete' ], 10, 2 );

		// Invalidate when design_slug meta changes (template switch)
		add_action( 'updated_post_meta', [ self::class, 'on_meta_update' ], 10, 4 );
		add_action( 'added_post_meta', [ self::class, 'on_meta_update' ], 10, 4 );
	}

	/**
	 * Handle page_part save event
	 *
	 * @param int      $post_id Post ID
	 * @param \WP_Post $post    Post object
	 */
	public static function on_page_part_save( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		self::clear_cache();

		// Log in development for debugging
		if ( ! defined( 'SITE_LIVE' ) || ! SITE_LIVE ) {
			error_log( "[NOK Cache] Registry cleared: page_part {$post_id} saved" );
		}
	}

	/**
	 * Handle page_part delete event
	 *
	 * @param int      $post_id Post ID being deleted
	 * @param \WP_Post $post    Post object
	 */
	public static function on_page_part_delete( int $post_id, \WP_Post $post ): void {
		if ( $post->post_type !== 'page_part' ) {
			return;
		}

		self::clear_cache();

		if ( ! defined( 'SITE_LIVE' ) || ! SITE_LIVE ) {
			error_log( "[NOK Cache] Registry cleared: page_part {$post_id} deleted" );
		}
	}

	/**
	 * Handle meta update event (for design_slug changes)
	 *
	 * @param int    $meta_id    Meta ID
	 * @param int    $object_id  Post ID
	 * @param string $meta_key   Meta key
	 * @param mixed  $meta_value New meta value
	 */
	public static function on_meta_update( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		// Only care about design_slug changes on page_parts
		if ( $meta_key !== 'design_slug' ) {
			return;
		}

		$post = get_post( $object_id );
		if ( ! $post || $post->post_type !== 'page_part' ) {
			return;
		}

		self::clear_cache();

		if ( ! defined( 'SITE_LIVE' ) || ! SITE_LIVE ) {
			error_log( "[NOK Cache] Registry cleared: page_part {$object_id} design_slug changed to {$meta_value}" );
		}
	}

	/**
	 * Extract custom metadata from template file header
	 *
	 * Parses the PHPDoc comment block at the top of template files to extract
	 * metadata headers. Handles both single-line headers (e.g., "Template Name: Hero")
	 * and multi-line headers (e.g., "Custom Fields:" with list items).
	 *
	 * @param string $file Absolute path to template file
	 * @param array $headers Headers to extract, keyed by result key
	 * @return array Extracted header values keyed by result keys
	 */
	private function get_custom_file_data( string $file, array $headers ): array {
		$file_content = file_get_contents( $file );
		if ( ! $file_content ) {
			return array_fill_keys( array_keys( $headers ), '' );
		}

		// Extract only the PHPDoc comment block at the top of the file
		// Pattern: <?php /** ... */ (with 's' flag for multiline matching)
		if ( ! preg_match( '/^<\?php\s*\/\*\*(.*?)\*\//s', $file_content, $matches ) ) {
			return array_fill_keys( array_keys( $headers ), '' );
		}

		$comment_block = $matches[1];
		$result        = [];

		foreach ( $headers as $key => $header_name ) {
			if ( $header_name === 'Custom Fields' ) {
				// Special handling for multi-line Custom Fields header
				// Pattern: "* Custom Fields:" followed by lines starting with "* -"
				// Example:
				//   * Custom Fields:
				//   *  - title:text
				//   *  - subtitle:textarea
				if ( preg_match( '/^\s*\*\s*' . preg_quote( $header_name, '/' ) . '\s*:\s*$/m', $comment_block, $matches, PREG_OFFSET_CAPTURE ) ) {
					/** @noinspection PhpWrongStringConcatenationInspection */
					$start_pos         = $matches[0][1] + strlen( $matches[0][0] );
					$remaining_content = substr( $comment_block, $start_pos );

					// Extract all list items: lines starting with "* -"
					// Captures everything after the dash until end of line
					preg_match_all( '/^\s*\*\s*-\s*(.+)$/m', $remaining_content, $field_matches );

					// Join all field definitions with commas for parse_custom_fields()
					$result[ $key ] = implode( ',', array_map( 'trim', $field_matches[1] ) );
				} else {
					$result[ $key ] = '';
				}
			} else {
				// Standard single-line header parsing
				// Pattern: "* Header Name: value"
				// Example: "* Template Name: Hero Section"
				$pattern = '/^\s*\*\s*' . preg_quote( $header_name, '/' ) . '\s*:\s*(.+)$/m';

				if ( preg_match( $pattern, $comment_block, $header_matches ) ) {
					$result[ $key ] = trim( $header_matches[1] );
				} else {
					$result[ $key ] = '';
				}
			}
		}

		return $result;
	}

	/**
	 * Parse custom fields definition from template header
	 *
	 * @param string $fields_string Format: comma-separated with bracket options
	 * @param string $template_slug Template slug for field prefixing
	 *
	 * @return array Parsed field definitions
	 */
	private function parse_custom_fields( string $fields_string, string $template_slug ): array {
		if ( empty( $fields_string ) ) {
			return [];
		}

		$fields = [];

		// Split by commas, but not commas inside parentheses or square brackets
		// Parentheses: protects repeater/select/etc. arguments e.g. repeater(name:text,url:url)
		// Square brackets: protects !descr[text with, commas] descriptions
		// Example: "title:text!descr[A title, with subtitle],items:repeater(name:text,url:url)"
		// Splits into: ["title:text!descr[A title, with subtitle]", "items:repeater(name:text,url:url)"]
		$field_definitions = preg_split( '/,(?![^\(]*\))(?![^\[]*\])/', $fields_string );

		foreach ( $field_definitions as $definition ) {
			// Clean up definition: remove asterisks, whitespace, and dashes from comment blocks
			$definition = trim( $definition, " \t\n\r\0\x0B*-" );

			if ( empty( $definition ) ) {
				continue;
			}

			// Extract optional flags (can appear in any order):
			// 1. !page-editable: Field can be overridden per-page
			// 2. !default(value): Default value if field is empty
			// 3. !descr[text]: Help text/description for field

			// Flag 1: !page-editable
			// Example: "title:text,!page-editable,!default(Hello)"
			$is_page_editable = false;
			if ( str_contains( $definition, '!page-editable' ) ) {
				$is_page_editable = true;
				// Remove flag from definition, including optional trailing comma
				$definition = preg_replace( '/!page-editable\s*,?\s*/', '', $definition );
				$definition = trim( $definition );
			}

			// Flag 2: !default(value)
			// Pattern: !default(...) where ... can be any text except closing paren
			// Example: "title:text,!default(Welcome)"
			$default_value = null;
			if ( preg_match( '/!default\(([^)]+)\)/', $definition, $default_match ) ) {
				$default_value = trim( $default_match[1] );
				// Remove flag from definition, including optional trailing comma
				$definition = preg_replace( '/!default\([^)]+\)\s*,?\s*/', '', $definition );
				$definition = trim( $definition );
			}

			// Flag 3: !descr[text]
			// Pattern: !descr[...] where ... can be any text except closing bracket
			// Example: "email:email,!descr[Enter your email address]"
			$description = '';
			if ( preg_match( '/!descr\[([^\]]+)\]/', $definition, $descr_match ) ) {
				$description = trim( $descr_match[1] );
				// Remove flag from definition, including optional trailing comma
				$definition = preg_replace( '/!descr\[[^\]]+\]\s*,?\s*/', '', $definition );
				$definition = trim( $definition );
			}

			// Field Type 1: Select field
			// Pattern: "name:select(option1|option2|option3)"
			// With labels: "name:select(Label 1::value1|Label 2::value2)"
			// Example: "position:select(Left::left|Right::right|Center::center)"
			if ( preg_match( '/^([^:]+):select\((.*)$/', $definition, $matches ) ) {
				$field_name            = trim( $matches[1] );
				$content_with_trailing = trim( $matches[2] );

				// Remove the trailing ) that closes the select() function
				$options_string = preg_replace( '/\)$/', '', $content_with_trailing, 1 );

				// Split options by pipe separator
				$raw_options = array_map( 'trim', explode( '|', $options_string ) );

				// Parse options with optional nice names (label::value or just value)
				// If label present: "Nice Label::stored_value"
				// If no label: "value" (value serves as both label and value)
				$options       = [];
				$option_labels = [];

				foreach ( $raw_options as $raw_option ) {
					if ( strpos( $raw_option, '::' ) !== false ) {
						// Split "Label::value" into separate label and value
						list( $label, $value ) = array_map( 'trim', explode( '::', $raw_option, 2 ) );
						$options[]       = $value;
						$option_labels[] = $label;
					} else {
						// No label provided, use value as both label and value
						$options[]       = $raw_option;
						$option_labels[] = $raw_option;
					}
				}

				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'select',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'options'       => $options,        // Actual values
					'option_labels' => $option_labels,  // Display labels
					'page_editable' => $is_page_editable,
					'default'       => $this->resolve_select_default( $default_value, $options, $option_labels ),
					'description'   => $description
				];
			}
			// Field Type 2: Checkbox field
			// Pattern: "name:checkbox" or "name:checkbox(true)" or "name:checkbox(false)"
			// Example: "show_overlay:checkbox(true)"
			// Default is false if not specified
			elseif ( preg_match( '/^([^:]+):checkbox(?:\(([^)]+)\))?$/', $definition, $matches ) ) {
				$field_name    = trim( $matches[1] );
				// Determine checkbox default value
				if (isset($matches[2]) && $matches[2] !== '') {
					$checkbox_default = trim($matches[2]);
				} elseif ($default_value !== null && $default_value !== '') {
					$checkbox_default = $default_value;
				} else {
					$checkbox_default = 'false';
				}

				$is_default_checked = in_array( strtolower($checkbox_default), [ 'true', '1', 'yes', 'on' ], true );
				// Store as '1' or '0' for WordPress meta compatibility
				$default_storage_value = $is_default_checked ? '1' : '0';

				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'checkbox',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'options'       => [], // Empty for checkbox fields
					'page_editable' => $is_page_editable,
					'default'       => $default_storage_value,
					'description'   => $description
				];
			}
			// Field Type 3: Icon selector field
			// Pattern: "name:icon-selector"
			// Example: "icon:icon-selector"
			// Provides UI for selecting from available icon set
			elseif ( preg_match( '/^([^:]+):icon-selector$/', $definition, $matches ) ) {
				$field_name = trim( $matches[1] );
				$meta_key   = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'icon-selector',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'page_editable' => $is_page_editable,
					'default'       => $default_value,
					'description'   => $description
				];
			}
			// Field Type 3b: Color selector field
			// Pattern: "name:color-selector(palette-name)"
			// Example: "bg_color:color-selector(backgrounds)"
			// Provides visual swatch picker for selecting from centralized color palettes
			elseif ( preg_match( '/^([^:]+):color-selector\(([^)]+)\)$/', $definition, $matches ) ) {
				$field_name   = trim( $matches[1] );
				$palette_name = trim( $matches[2] );
				$meta_key     = $template_slug . '_' . $field_name;

				// Get palette options from Colors class
				$palette = \NOK2025\V1\Colors::getPalette( $palette_name );

				// Extract options and labels from palette
				$options       = array_column( $palette, 'value' );
				$option_labels = array_column( $palette, 'label' );

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'color-selector',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'palette'       => $palette_name,
					'options'       => $options,
					'option_labels' => $option_labels,
					'page_editable' => $is_page_editable,
					'default'       => $this->resolve_select_default( $default_value, $options, $option_labels ),
					'description'   => $description
				];
			}
			// Field Type 3c: Image field (WordPress Media Library)
			// Pattern: "name:image"
			// Example: "block_image:image"
			// Stores attachment ID, provides media library picker in editor
			elseif ( preg_match( '/^([^:]+):image$/', $definition, $matches ) ) {
				$field_name = trim( $matches[1] );
				$meta_key   = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'image',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'page_editable' => $is_page_editable,
					'default'       => $default_value,
					'description'   => $description
				];
			}
			// Field Type 4: Repeater field (custom fields)
			// Pattern: "name:repeater(field1:type1,field2:type2,...)"
			// Example: "team_members:repeater(name:text,role:text,photo:image)"
			// Creates repeatable group of sub-fields
			elseif ( preg_match( '/^([^:]+):repeater\((.+)\)$/', $definition, $matches ) ) {
				$field_name    = trim( $matches[1] );
				$schema_string = trim( $matches[2] );

				// Parse schema: comma-separated field definitions
				// Each definition: "name:type"
				$schema = [];
				$parts  = explode( ',', $schema_string );

				foreach ( $parts as $field_def ) {
					$field_def = trim( $field_def );
					if ( strpos( $field_def, ':' ) !== false ) {
						// Split "name:type" into components
						list( $name, $type ) = explode( ':', $field_def, 2 );
						$schema[] = [ 'name' => trim( $name ), 'type' => trim( $type ) ];
					}
				}

				$fields[] = [
					'name'             => $field_name,
					'type'             => 'repeater',
					'meta_key'         => $template_slug . '_' . $field_name,
					'label'            => $this->generate_field_label( $field_name ),
					'schema'           => $schema,
					'repeater_subtype' => 'fields',
					'page_editable'    => $is_page_editable,
					'default'          => $default_value,
					'description'      => $description
				];
			}
			// Field Type 5: Taxonomy selector field
			// Pattern: "name:taxonomy(taxonomy_slug)" for multi-select (default)
			// Pattern: "name:taxonomy(taxonomy_slug)!single" for single-select
			// Example: "category_filter:taxonomy(kennisbank_categories)"
			// Creates UI for selecting taxonomy terms from REST API
			elseif ( preg_match( '/^([^:]+):taxonomy\(([^)]+)\)(.*)$/', $definition, $matches ) ) {
				$field_name    = trim( $matches[1] );
				$taxonomy_slug = trim( $matches[2] );
				$flags         = trim( $matches[3] ?? '' );

				// Check for !single flag (default is multi-select)
				$is_single = str_contains( $flags, '!single' );

				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'taxonomy',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'taxonomy'      => $taxonomy_slug,
					'multiple'      => ! $is_single,
					'page_editable' => $is_page_editable,
					'default'       => $default_value,
					'description'   => $description
				];
			}
			// Field Type 6: Post repeater field
			// Pattern: "name:post_repeater(post_type)" or "name:post_repeater(post_type:cat1,cat2)"
			// Multiple post types: "name:post_repeater(post|page|custom_type)"
			// With categories: "name:post_repeater(post:news,events)"
			// Example: "featured_posts:post_repeater(post:news,announcements)"
			// Creates UI for selecting existing WordPress posts
			elseif ( preg_match( '/^([^:]+):post_repeater\((.+)\)$/', $definition, $matches ) ) {
				$field_name    = trim( $matches[1] );
				$schema_string = trim( $matches[2] );

				// Check if category filter is specified (contains colon)
				// Format: "post_type:cat1,cat2"
				if ( strpos( $schema_string, ':' ) !== false ) {
					list( $post_type, $category_string ) = explode( ':', $schema_string, 2 );
					$post_types = [ trim( $post_type ) ];
					// Parse comma-separated category slugs
					$categories = array_map( 'trim', explode( ',', $category_string ) );
				} else {
					// Simple post type(s) without category filter
					// Multiple types separated by pipe: "post|page|custom_type"
					$post_types = array_map( 'trim', explode( '|', $schema_string ) );
					$categories = [];
				}

				$fields[] = [
					'name'             => $field_name,
					'type'             => 'repeater',
					'meta_key'         => $template_slug . '_' . $field_name,
					'label'            => $this->generate_field_label( $field_name ),
					'schema'           => [],
					'repeater_subtype' => 'post',
					'post_types'       => $post_types,
					'categories'       => $categories,
					'page_editable'    => $is_page_editable,
					'default'          => $default_value,
					'description'      => $description
				];
			}
			// Field Type 7: Regular simple fields
			// Pattern: "name:type"
			// Example: "title:text", "content:textarea", "email:email", "url:url"
			// Handles all standard field types (text, textarea, email, url, number, date, etc.)
			else {
				$parts = explode( ':', $definition );
				// Require both name and type
				if ( count( $parts ) < 2 ) {
					continue;
				}

				$field_name = trim( $parts[0] );
				$field_type = trim( $parts[1] );

				// Create prefixed meta key to avoid collisions
				// Example: "nok-hero" + "title" = "nok-hero_title"
				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => $field_type,
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label( $field_name ),
					'options'       => [], // Empty for non-select fields
					'page_editable' => $is_page_editable,
					'default'       => $default_value,
					'description'   => $description
				];
			}
		}

		return $fields;
	}

	/**
	 * Resolve select default - map label to value if needed
	 *
	 * @param mixed $default_value Default from !default() flag
	 * @param array $options Actual values
	 * @param array $option_labels Display labels
	 * @return mixed Resolved value or original
	 */
	private function resolve_select_default( $default_value, array $options, array $option_labels ) {
		if ( $default_value === null || $default_value === '' ) {
			return $default_value;
		}

		// Check if default is a label - map to corresponding value
		$label_index = array_search( $default_value, $option_labels, true );
		if ( $label_index !== false ) {
			return $options[ $label_index ];
		}

		// Check if default is already a value - use as-is
		if ( in_array( $default_value, $options, true ) ) {
			return $default_value;
		}

		// Fallback to literal value (backward compat)
		return $default_value;
	}

	/**
	 * Generate human-readable label from field name
	 *
	 * Converts snake_case or kebab-case field names into Title Case labels
	 * suitable for display in forms and UI. Preserves acronyms and special terms.
	 *
	 * @param string $field_name Field name in snake_case or kebab-case
	 * @return string Human-readable label in Title Case
	 *
	 * @example Convert snake_case
	 * $label = $this->generate_field_label('background_color');
	 * // Returns: "Background Color"
	 *
	 * @example Convert kebab-case
	 * $label = $this->generate_field_label('hero-title');
	 * // Returns: "Hero Title"
	 *
	 * @example Convert with acronym
	 * $label = $this->generate_field_label('cta_url');
	 * // Returns: "Cta Url"
	 */
	public function generate_field_label( string $field_name ): string {
		// Convert snake_case or kebab-case to Title Case
		$label = str_replace( [ '_', '-' ], ' ', $field_name );

		return ucwords( $label );
	}

	/**
	 * Parse restriction from template header
	 *
	 * Parses the Restriction header to determine which post types can use this page part.
	 * Format: "post_types:type1,type2,type3"
	 *
	 * @param string $restriction_string Raw restriction string from template header
	 * @return array Parsed restriction with 'post_types' key containing array of allowed post types
	 *
	 * @example Parse simple restriction
	 * $restriction = $this->parse_restriction('post_types:post,page');
	 * // Returns: ['post_types' => ['post', 'page']]
	 *
	 * @example Parse single post type
	 * $restriction = $this->parse_restriction('post_types:template_layout');
	 * // Returns: ['post_types' => ['template_layout']]
	 *
	 * @example Empty restriction (no restrictions)
	 * $restriction = $this->parse_restriction('');
	 * // Returns: []
	 */
	private function parse_restriction( string $restriction_string ): array {
		if ( empty( $restriction_string ) ) {
			return [];
		}

		$restriction = [];

		// Parse post_types restriction
		// Format: "post_types:type1,type2,type3"
		// Example: "post_types:post,template_layout"
		if ( preg_match( '/post_types:([a-zA-Z0-9_,]+)/', $restriction_string, $matches ) ) {
			$post_types_string = $matches[1];
			// Split by comma and trim whitespace
			$post_types = array_map( 'trim', explode( ',', $post_types_string ) );
			// Remove empty values
			$post_types = array_filter( $post_types );

			if ( ! empty( $post_types ) ) {
				$restriction['post_types'] = $post_types;
			}
		}

		return $restriction;
	}
}