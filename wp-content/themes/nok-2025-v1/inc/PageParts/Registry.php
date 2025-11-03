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

	/**
	 * Get complete page part registry
	 *
	 * Scans all page-part template files and parses their header metadata,
	 * including custom field definitions. Results are cached after first scan.
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
		if ( $this->part_registry !== null ) {
			return $this->part_registry;
		}

		$files               = glob( THEME_ROOT_ABS . '/template-parts/page-parts/*.php' );
		$this->part_registry = [];

		foreach ( $files as $file ) {
			$data = $this->get_custom_file_data( $file, [
				'name'                       => 'Template Name',
				'description'                => 'Description',
				'slug'                       => 'Slug',
				'icon'                       => 'Icon',
				'custom_fields'              => 'Custom Fields',
				'featured_image_overridable' => 'Featured Image Overridable'
			] );

			if ( empty( $data['slug'] ) ) {
				$data['slug'] = sanitize_title( $data['name'] ?? basename( $file, '.php' ) );
			}

			// Parse custom fields
			$data['custom_fields'] = $this->parse_custom_fields( $data['custom_fields'], $data['slug'] );

			// Parse featured image overridable flag
			$data['featured_image_overridable'] = strtolower( $data['featured_image_overridable'] ?? '' ) === 'true';

			$this->part_registry[ $data['slug'] ] = $data;
		}

		return $this->part_registry;
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

		// Split by commas, but not commas inside parentheses/brackets
		// Pattern: comma not followed by content between parentheses
		// Example: "title:text,items:repeater(name:text,url:url),subtitle:text"
		// Splits into: ["title:text", "items:repeater(name:text,url:url)", "subtitle:text"]
		$field_definitions = preg_split( '/,(?![^\(]*\))/', $fields_string );

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
				$options_string = rtrim( $content_with_trailing, ')' );

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
					'default'       => $default_value,
					'description'   => $description
				];
			}
			// Field Type 2: Checkbox field
			// Pattern: "name:checkbox" or "name:checkbox(true)" or "name:checkbox(false)"
			// Example: "show_overlay:checkbox(true)"
			// Default is false if not specified
			elseif ( preg_match( '/^([^:]+):checkbox(?:\(([^)]+)\))?$/', $definition, $matches ) ) {
				$field_name    = trim( $matches[1] );
				// Extract default value from parentheses, or use 'false' if not provided
				$default_value = isset( $matches[2] ) ? trim( $matches[2] ) : 'false';

				// Convert various truthy strings to boolean
				// Accepts: true, 1, yes, on (case-insensitive)
				$is_default_checked    = in_array( strtolower( $default_value ), [ 'true', '1', 'yes', 'on' ], true );
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
					'default'       => $default_value,
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
			// Field Type 5: Post repeater field
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
			// Field Type 6: Regular simple fields
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
}