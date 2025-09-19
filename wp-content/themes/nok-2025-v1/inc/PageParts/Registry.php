<?php
// inc/PageParts/Registry.php

namespace NOK2025\V1\PageParts;

class Registry {
	private ?array $part_registry = null;

	/**
	 * Scan all page-part templates and pull their metadata including custom fields.
	 *
	 * @return array Array of [ slug => [ 'name' => ..., 'description' => ..., 'icon' => ..., 'custom_fields' => [...] ] ]
	 */
	public function get_registry(): array {
		if ($this->part_registry !== null) {
			return $this->part_registry;
		}

		$files = glob(THEME_ROOT_ABS . '/template-parts/page-parts/*.php');
		$this->part_registry = [];

		foreach ($files as $file) {
			$data = $this->get_custom_file_data($file, [
				'name'          => 'Template Name',
				'description'   => 'Description',
				'slug'          => 'Slug',
				'icon'          => 'Icon',
				'custom_fields' => 'Custom Fields',
			]);

			if (empty($data['slug'])) {
				$data['slug'] = sanitize_title($data['name'] ?? basename($file, '.php'));
			}

			// Parse custom fields
			$data['custom_fields'] = $this->parse_custom_fields($data['custom_fields'], $data['slug']);

			$this->part_registry[$data['slug']] = $data;
		}

		return $this->part_registry;
	}

	private function get_custom_file_data(string $file, array $headers): array {
		$file_content = file_get_contents($file);
		if (!$file_content) {
			return array_fill_keys(array_keys($headers), '');
		}

		// Extract only the comment block at the top
		if (!preg_match('/^<\?php\s*\/\*\*(.*?)\*\//s', $file_content, $matches)) {
			return array_fill_keys(array_keys($headers), '');
		}

		$comment_block = $matches[1];
		$result = [];

		foreach ($headers as $key => $header_name) {
			if ($header_name === 'Custom Fields') {
				// Find the Custom Fields header line
				if (preg_match('/^\s*\*\s*' . preg_quote($header_name, '/') . '\s*:\s*$/m', $comment_block, $matches, PREG_OFFSET_CAPTURE)) {
					$start_pos = $matches[0][1] + strlen($matches[0][0]);
					$remaining_content = substr($comment_block, $start_pos);

					// Find all lines that start with "* -" until we hit another header or end
					preg_match_all('/^\s*\*\s*-\s*([^,\n]+)(?:,\s*)?$/m', $remaining_content, $field_matches);

					$result[$key] = implode(',', array_map('trim', $field_matches[1]));
				} else {
					$result[$key] = '';
				}
			} else {
				// Standard single-line header parsing
				$pattern = '/^\s*\*\s*' . preg_quote($header_name, '/') . '\s*:\s*(.+)$/m';

				if (preg_match($pattern, $comment_block, $header_matches)) {
					$result[$key] = trim($header_matches[1]);
				} else {
					$result[$key] = '';
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
	 * @return array Parsed field definitions
	 */
	private function parse_custom_fields(string $fields_string, string $template_slug): array {
		if (empty($fields_string)) {
			return [];
		}

		$fields = [];

		// Split by commas, but not commas inside brackets
		$field_definitions = preg_split('/,(?![^\(]*\))/', $fields_string);

		foreach ($field_definitions as $definition) {
			// Remove asterisks, whitespace, and dashes from comment blocks
			$definition = trim($definition, " \t\n\r\0\x0B*-");

			if (empty($definition)) {
				continue;
			}

			// Check for select field with bracket notation: "position:select(left|right)"
			if (preg_match('/^([^:]+):select\((.*)$/', $definition, $matches)) {
				$field_name = trim($matches[1]);
				$content_with_trailing = trim($matches[2]);

				// Remove the trailing ) that closes the select() function
				$options_string = rtrim($content_with_trailing, ')');

				$raw_options = array_map('trim', explode('|', $options_string));

				// Parse options with optional nice names (label::value or just value)
				$options = [];
				$option_labels = [];

				foreach ($raw_options as $raw_option) {
					if (strpos($raw_option, '::') !== false) {
						list($label, $value) = array_map('trim', explode('::', $raw_option, 2));
						$options[] = $value;
						$option_labels[] = $label;
					} else {
						$options[] = $raw_option;
						$option_labels[] = $raw_option; // Use value as label if no label provided
					}
				}

				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'          => $field_name,
					'type'          => 'select',
					'meta_key'      => $meta_key,
					'label'         => $this->generate_field_label($field_name),
					'options'       => $options,        // Actual values
					'option_labels' => $option_labels,  // Display labels
				];
			}
			// Check for checkbox field with optional default: "field_name:checkbox(true)"
			elseif (preg_match('/^([^:]+):checkbox(?:\(([^)]+)\))?$/', $definition, $matches)) {
				$field_name = trim($matches[1]);
				$default_value = isset($matches[2]) ? trim($matches[2]) : 'false';

				// Convert string to boolean, then to our storage format
				$is_default_checked = in_array(strtolower($default_value), ['true', '1', 'yes', 'on'], true);
				$default_storage_value = $is_default_checked ? '1' : '0';

				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'     => $field_name,
					'type'     => 'checkbox',
					'meta_key' => $meta_key,
					'label'    => $this->generate_field_label($field_name),
					'default'  => $default_storage_value,
					'options'  => [], // Empty for checkbox fields
				];
			}
			// Handle regular fields: "field_name:type"
			else {
				$parts = explode(':', $definition);
				if (count($parts) < 2) {
					continue;
				}

				$field_name = trim($parts[0]);
				$field_type = trim($parts[1]);

				// Create prefixed meta key
				$meta_key = $template_slug . '_' . $field_name;

				$fields[] = [
					'name'     => $field_name,
					'type'     => $field_type,
					'meta_key' => $meta_key,
					'label'    => $this->generate_field_label($field_name),
					'options'  => [], // Empty for non-select fields
				];
			}
		}

		return $fields;
	}

	/**
	 * Generate a human-readable label from field name
	 */
	public function generate_field_label(string $field_name): string {
		// Convert snake_case or kebab-case to Title Case
		$label = str_replace(['_', '-'], ' ', $field_name);
		return ucwords($label);
	}
}