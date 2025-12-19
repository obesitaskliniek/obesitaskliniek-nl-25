<?php
// inc/PageParts/FieldContext.php
namespace NOK2025\V1\PageParts;

/**
 * FieldContext - Template field access with defaults and magic getters
 *
 * Provides field values to templates with:
 * - Default value resolution from template headers
 * - Magic getter returning FieldValue objects for auto-escaping
 * - Legacy get_esc_* methods for backward compatibility
 * - ArrayAccess for backward compatibility with old templates
 *
 * @example Basic usage in templates
 * $c = $context; // Common shorthand
 *
 * // Check if field has value
 * if ($c->has('button_url')) {
 *     echo '<a href="' . $c->button_url->url() . '">' . $c->button_text . '</a>';
 * }
 *
 * // Use defaults from template header
 * $left = $c->layout->is('left'); // Falls back to header default
 *
 * // Parse repeater JSON
 * $items = $c->quote_items->json();
 *
 * @example Backward compatible usage
 * $value = $context->get('field_name', 'fallback');
 * $escaped = $context->get_esc_html('field_name');
 * $old_style = $context['field_name']; // ArrayAccess
 *
 * @property-read FieldValue $layout Common layout field
 * @property-read FieldValue $colors Common colors field
 * @property-read FieldValue $title Common title field
 * @property-read FieldValue $content Common content field
 * @property-read FieldValue $button_text Common button text field
 * @property-read FieldValue $button_url Common button URL field
 * @property-read FieldValue $tagline Common tagline field
 * @property-read FieldValue $icon Common icon field
 *
 * Note: Add your template-specific field names above for IDE autocomplete
 *
 * @package NOK2025\V1\PageParts
 */
class FieldContext implements \ArrayAccess {
	private array $fields;
	private array $defaults;
	private array $generic_overrides;

	/**
	 * Constructor
	 *
	 * @param array $fields Field values from post meta
	 * @param array $defaults Default values from template registry
	 */
	public function __construct(array $fields, array $defaults = [], array $generic_overrides = []) {
		$this->fields = $fields;
		$this->defaults = $defaults;
		$this->generic_overrides = $generic_overrides;
	}

	/**
	 * Check if value is non-empty
	 * Optionally return values based on result
	 *
	 * Returns false for:
	 * - Empty strings
	 * - Unchecked checkboxes ('0')
	 * - Empty JSON structures ('[]', '{}')
	 * - null values
	 *
	 * @param string $key Key to check for
	 * @param mixed $ifTrue Optional: return this if value exists
	 * @param mixed $ifFalse Optional: return this if value is empty
	 *
	 * @return bool|mixed
	 */
	public function has(string $key, mixed $ifTrue = null, mixed $ifFalse = null): mixed {
		if (!isset($this->fields[$key])) {
			$exists = false;
		} else {
			$value = $this->fields[$key];
			$exists = !($value === '' || $value === '0' || $value === '[]' || $value === '{}');
		}

		if ($ifTrue !== null) {
			return $exists ? $ifTrue : $ifFalse;
		}

		return $exists;
	}

	/**
	 * Get field value with fallback chain
	 *
	 * Resolution order:
	 * 1. Field value if set
	 * 2. Explicit default parameter if provided
	 * 3. Template header default if exists
	 * 4. Empty string
	 *
	 * @param string $key Field name
	 * @param mixed $default Optional explicit default
	 * @return mixed
	 */
	public function get(string $key, $default = null): mixed {
		if (isset($this->fields[$key])) {
			return $this->fields[$key];
		}
		if ($default !== null) {
			return $default;
		}
		return $this->defaults[$key] ?? '';
	}

	/**
	 * Get HTML-escaped field value (legacy method)
	 *
	 * @deprecated Use magic getter: $context->field->html() or just $context->field
	 * @param string $key Field name
	 * @param string $default Fallback value
	 * @return string
	 */
	public function get_esc_html(string $key, $default = ''): string {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_html($value);
	}

	/**
	 * Get URL-escaped field value (legacy method)
	 *
	 * @deprecated Use magic getter: $context->field->url()
	 * @param string $key Field name
	 * @param string $default Fallback value
	 * @return string
	 */
	public function get_esc_url(string $key, $default = ''): string {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_url($value);
	}

	/**
	 * Get link field value with post:123/term:123/archive:type resolution (legacy method)
	 *
	 * Resolves post:123, term:123, and archive:type formats to permalinks
	 *
	 * @deprecated Use magic getter: $context->field->link()
	 * @param string $key Field name
	 * @param string $default Fallback value
	 * @return string Resolved URL
	 */
	public function get_link(string $key, $default = ''): string {
		$value = $this->get($key, $default);

		if ($this->is_placeholder($value)) {
			return $value;
		}

		if (is_string($value) && str_starts_with($value, 'post:')) {
			$post_id = (int) substr($value, 5);
			if ($post_id > 0) {
				$permalink = get_permalink($post_id);
				if ($permalink) {
					return esc_url($permalink);
				}
			}
			return $default;
		}

		if (is_string($value) && str_starts_with($value, 'term:')) {
			$term_id = (int) substr($value, 5);
			if ($term_id > 0) {
				$term_link = get_term_link($term_id);
				if ($term_link && !is_wp_error($term_link)) {
					return esc_url($term_link);
				}
			}
			return $default;
		}

		if (is_string($value) && str_starts_with($value, 'archive:')) {
			$post_type = substr($value, 8);
			if ($post_type) {
				$archive_link = get_post_type_archive_link($post_type);
				if ($archive_link) {
					return esc_url($archive_link);
				}
			}
			return $default;
		}

		return $value ? esc_url($value) : $default;
	}

	/**
	 * Get attribute-escaped field value (legacy method)
	 *
	 * @deprecated Use magic getter: $context->field->attr()
	 * @param string $key Field name
	 * @param string $default Fallback value
	 * @return string
	 */
	public function get_esc_attr(string $key, $default = ''): string {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_attr($value);
	}

	/**
	 * Get all field values as array
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->fields;
	}

	/**
	 * Magic getter - returns FieldValue wrapper for auto-escaping
	 *
	 * Enables: $context->field_name instead of $context->get('field_name')
	 *
	 * @param string $key Field name
	 * @return FieldValue
	 */
	public function __get(string $key): FieldValue {
		return new FieldValue($this->get($key));
	}

	/**
	 * Create new context with additional defaults merged in
	 *
	 * @param array $defaults Additional defaults to merge
	 * @return self
	 */
	public function with_defaults(array $defaults): self {
		$merged_defaults = array_merge($this->defaults, $defaults);
		return new self($this->fields, $merged_defaults);
	}

	// =========================================================================
	// ArrayAccess Implementation - Backward Compatibility
	// =========================================================================

	/**
	 * Check if field exists (ArrayAccess)
	 *
	 * @param mixed $offset Field name
	 * @return bool
	 */
	public function offsetExists($offset): bool {
		return isset($this->fields[$offset]);
	}

	/**
	 * Get field value (ArrayAccess)
	 *
	 * @param mixed $offset Field name
	 * @return mixed
	 */
	public function offsetGet($offset): mixed {
		return $this->fields[$offset] ?? null;
	}

	/**
	 * Set field value (ArrayAccess)
	 *
	 * @param mixed $offset Field name
	 * @param mixed $value Field value
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		$this->fields[$offset] = $value;
	}

	/**
	 * Unset field (ArrayAccess)
	 *
	 * @param mixed $offset Field name
	 * @return void
	 */
	public function offsetUnset($offset): void {
		unset($this->fields[$offset]);
	}

	/**
	 * Check if value is an editor placeholder
	 *
	 * @param mixed $value Value to check
	 * @return bool
	 */
	private function is_placeholder(mixed $value): bool {
		return is_string($value) && str_starts_with($value, '<span class="placeholder-field');
	}

	public function title(): string {
		if (!empty($this->generic_overrides['_override_title'])) {
			return esc_html($this->generic_overrides['_override_title']);
		}
		global $post;
		return $post ? esc_html($post->post_title) : '';
	}

	public function content(): string {
		if (!empty($this->generic_overrides['_override_content'])) {
			return wp_kses_post(wpautop($this->generic_overrides['_override_content']));
		}
		global $post;
		return $post ? wpautop(wptexturize($post->post_content)) : '';
	}
}