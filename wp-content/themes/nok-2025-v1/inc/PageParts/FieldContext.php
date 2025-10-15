<?php
// inc/PageParts/FieldContext.php
namespace NOK2025\V1\PageParts;

class FieldContext implements \ArrayAccess {
	private array $fields;

	public function __construct(array $fields) {
		$this->fields = $fields;
	}

	public function has(string $key): bool {
		return isset($this->fields[$key]) && $this->fields[$key] !== '';
	}

	public function get(string $key, $default = ''): mixed {
		return $this->fields[$key] ?? $default;
	}

	public function get_esc_html(string $key, $default = ''): mixed {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_html($value);
	}

	public function get_esc_url(string $key, $default = ''): mixed {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_url($value);
	}

	public function get_esc_attr(string $key, $default = ''): mixed {
		$value = $this->get($key, $default);
		return $this->is_placeholder($value) ? $value : esc_attr($value);
	}
	public function all(): array {
		return $this->fields;
	}

// ArrayAccess for backwards compatibility
	public function offsetExists($offset): bool {
		return isset($this->fields[$offset]);
	}

	public function offsetGet($offset): mixed {
		return $this->fields[$offset] ?? null;
	}

	public function offsetSet($offset, $value): void {
		$this->fields[$offset] = $value;
	}

	public function offsetUnset($offset): void {
		unset($this->fields[$offset]);
	}

	private function is_placeholder(mixed $value): bool {
		return is_string($value) && str_starts_with($value, '<span class="placeholder-field');
	}
}