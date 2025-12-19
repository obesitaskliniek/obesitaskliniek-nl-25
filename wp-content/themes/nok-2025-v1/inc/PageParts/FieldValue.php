<?php
// inc/PageParts/FieldValue.php
namespace NOK2025\V1\PageParts;

/**
 * FieldValue - Smart field value wrapper with auto-escaping and convenience methods
 *
 * Wraps field values from FieldContext to provide:
 * - Auto-escaping for different contexts (HTML, URL, attributes)
 * - Placeholder-aware escaping (preserves editor placeholders)
 * - Convenience comparison methods
 * - Type-safe value access
 *
 * Usage in templates via magic getter:
 *
 * @example Basic usage with auto-escaping
 * <h1><?= $context->title ?></h1>                          // Auto-escaped HTML
 * <a href="<?= $context->url->url() ?>">Link</a>           // URL-escaped
 * <div class="<?= $context->classes ?>">Content</div>      // Auto-escaped
 *
 * @example Explicit escaping methods
 * <?= $context->field->html() ?>  // HTML escape
 * <?= $context->field->url() ?>   // URL escape
 * <?= $context->field->attr() ?>  // Attribute escape
 * <?= $context->field->link() ?>  // Link resolution (post:123 → permalink)
 *
 * @example Comparisons and conditionals
 * $left = $context->layout->is('left');                              // Boolean
 * $class = $context->layout->is('left', 'order-1', 'order-2');       // Conditional value, acts as inline if
 * $visible = $context->status->in(['active', 'live']);                             // Boolean
 * $statusClass = $context->status->in(['active', 'live'], 'visible', 'hidden');    // Inline if
 * $hasDarkBG = $context->colors->contains('dark');                   // Boolean
 * $bgClass = $context->colors->contains('dark', 'light-text', '');   // Substring check with value, acts as inline if
 * if ($context->featured->isTrue()) { }                              // Checkbox check
 *
 * @example Raw value access (for logic, not output)
 * $raw = $context->field->raw();                           // Unescaped value
 * $left = $context->layout->raw() === 'left';              // Direct comparison
 * $items = $context->repeater_field->json();               // Parse JSON repeater
 * $items = $context->repeater_field->json($fallbackData);  // Parse JSON repeater (with custom fallback)
 *
 * @package NOK2025\V1\PageParts
 *
 * FieldContext - Template field access with defaults and magic getters
 *
 * @property-read FieldValue $layout
 * @property-read FieldValue $colors
 * @property-read FieldValue $title
 * @property-read FieldValue $content
 * @property-read FieldValue $button_text
 * @property-read FieldValue $button_url
 *
 * Note: Add actual field names from your templates for better IDE support
 *
 * @package NOK2025\V1\PageParts
 */
class FieldValue {
	private mixed $value;
	private bool $is_placeholder;

	public function __construct( mixed $value ) {
		$this->value          = $value;
		$this->is_placeholder = is_string( $value ) && str_starts_with( $value, '<span class="placeholder-field' );
	}

	/**
	 * Get HTML-escaped value
	 * Placeholders are preserved (not escaped)
	 *
	 * @return string
	 */
	public function html(): string {
		return $this->is_placeholder ? $this->value : esc_html( $this->value );
	}

	/**
	 * Get URL-escaped value
	 * Use for href/src attributes
	 *
	 * @param string $fallback
	 *
	 * @return string
	 */
	public function url( string $fallback = ''): string {
		return $this->value ? ($this->is_placeholder ? $this->value : esc_url( $this->value )) : $fallback;
	}

	/**
	 * Resolve link value to URL
	 * Handles post:123, term:123, and archive:post_type formats
	 * Falls back to regular URL escaping for other values
	 *
	 * @param string $fallback Fallback URL if empty or item not found
	 * @return string Resolved and escaped URL
	 */
	public function link( string $fallback = '' ): string {
		if ( ! $this->value ) {
			return $fallback;
		}

		if ( $this->is_placeholder ) {
			return $this->value;
		}

		// Check for post:123 format (posts and pages)
		if ( is_string( $this->value ) && str_starts_with( $this->value, 'post:' ) ) {
			$post_id = (int) substr( $this->value, 5 );
			if ( $post_id > 0 ) {
				$permalink = get_permalink( $post_id );
				if ( $permalink ) {
					return esc_url( $permalink );
				}
			}
			// Post not found, return fallback
			return $fallback;
		}

		// Check for term:123 format (categories, tags, custom taxonomies)
		if ( is_string( $this->value ) && str_starts_with( $this->value, 'term:' ) ) {
			$term_id = (int) substr( $this->value, 5 );
			if ( $term_id > 0 ) {
				$term_link = get_term_link( $term_id );
				if ( $term_link && ! is_wp_error( $term_link ) ) {
					return esc_url( $term_link );
				}
			}
			// Term not found, return fallback
			return $fallback;
		}

		// Check for archive:post_type format (post type archives)
		if ( is_string( $this->value ) && str_starts_with( $this->value, 'archive:' ) ) {
			$post_type = substr( $this->value, 8 );
			if ( $post_type ) {
				$archive_link = get_post_type_archive_link( $post_type );
				if ( $archive_link ) {
					return esc_url( $archive_link );
				}
			}
			// Archive not found, return fallback
			return $fallback;
		}

		// Regular URL
		return esc_url( $this->value );
	}

	/**
	 * Get attribute-escaped value
	 * Use for data-* and other HTML attributes
	 *
	 * @return string
	 */
	public function attr(): string {
		return $this->is_placeholder ? $this->value : esc_attr( $this->value );
	}

	/**
	 * Get raw unescaped value
	 * Use only for logic/comparisons, never for output
	 *
	 * @param string $fallback
	 *
	 * @return mixed
	 */
	public function raw( string $fallback = ''): mixed {
		return $this->value ?: $fallback;
	}

	/**
	 * Get value or fallback if empty
	 * Use for providing defaults when field might be empty
	 *
	 * @param mixed $fallback Value to return if field is empty
	 * @return mixed
	 */
	public function otherwise(mixed $fallback): mixed {
		// Check for empty values
		if ($this->value === '' || $this->value === '0' || $this->value === '[]' || $this->value === '{}' || $this->value === null) {
			return $fallback;
		}

		return $this->value;
	}

	/**
	 * Check if value equals given string
	 * Optionally return values based on result
	 *
	 * @param string $value Value to compare against
	 * @param mixed $ifTrue Optional: return this if match
	 * @param mixed $ifFalse Optional: return this if no match
	 *
	 * @return bool|mixed
	 */
	public function is( string $value, mixed $ifTrue = null, mixed $ifFalse = null ): mixed {
		$matches = $this->value === $value;

		if ( $ifTrue !== null ) {
			return $matches ? $ifTrue : $ifFalse;
		}

		return $matches;
	}

	/**
	 * Check if value is truthy (for checkboxes)
	 * Matches '1' or 'true' strings
	 *
	 * @param mixed $ifTrue Optional: return this if true
	 * @param mixed $ifFalse Optional: return this if false
	 *
	 * @return bool|mixed
	 */
	public function isTrue( mixed $ifTrue = null, mixed $ifFalse = null ): mixed {
		$eval = $this->value === '1' || $this->value === 'true';

		if ( $ifTrue !== null || $ifFalse !== null ) {
			return $eval ? $ifTrue : ( $ifFalse ?? '' );
		}

		return $eval;
	}

	/**
	 * Check if value matches any in the array
	 * Optionally return values based on result
	 *
	 * @param array $values Values to check against
	 * @param mixed $ifTrue Optional: return this if match found
	 * @param mixed $ifFalse Optional: return this if no match
	 *
	 * @return bool|mixed
	 */
	public function in( array $values, mixed $ifTrue = null, mixed $ifFalse = null ): mixed {
		$matches = in_array( $this->value, $values, true );

		if ( $ifTrue !== null ) {
			return $matches ? $ifTrue : $ifFalse;
		}

		return $matches;
	}

	/**
	 * Check if value contains given substring
	 * Optionally return values based on result
	 *
	 * @param string $needle Substring to search for
	 * @param mixed $ifTrue Optional: return this if found
	 * @param mixed $ifFalse Optional: return this if not found
	 *
	 * @return bool|mixed
	 */
	public function contains( string $needle, mixed $ifTrue = null, mixed $ifFalse = null ): mixed {
		$found = is_string( $this->value ) && str_contains( $this->value, $needle );

		if ( $ifTrue !== null ) {
			return $found ? $ifTrue : $ifFalse;
		}

		return $found;
	}

	/**
	 * Parse JSON value to array
	 * Returns fallback array on invalid JSON or empty value
	 *
	 * @param array $fallback Fallback value if JSON is invalid or empty
	 *
	 * @return array
	 */
	public function json(array $fallback = []): array {
		if (!is_string($this->value)) {
			return $fallback;
		}

		$decoded = json_decode($this->value, true);

		if (!is_array($decoded)) {
			return $fallback;
		}

		// Return fallback if decoded is empty but fallback has data
		return (empty($decoded) && !empty($fallback)) ? $fallback : $decoded;
	}

	/**
	 * Generate CSS custom property declaration
	 * Returns empty string if field empty
	 *
	 * @param string $property_name Property name (without --)
	 * @return string '--property-name: value' or ''
	 */
	public function css_var(string $property_name): string {
		if ($this->value === '' || $this->value === null) {
			return '';
		}
		return '--' . $property_name . ':' . esc_attr($this->value);
	}

	/**
	 * Default string conversion uses HTML escaping
	 * Allows echo/interpolation without explicit ->html()
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->html();
	}
}