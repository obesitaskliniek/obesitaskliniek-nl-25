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

	/**
	 * Constructor
	 *
	 * @param array $fields Field values from post meta
	 * @param array $defaults Default values from template registry
	 */
	public function __construct(array $fields, array $defaults = []) {
		$this->fields = $fields;
		$this->defaults = $defaults;
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
}