# PHP Documentation Standards

## Purpose
Provide comprehensive documentation for PHPStorm IDE support, developer onboarding, and code maintainability.

## Class-Level Documentation

### Structure
```php
/**
 * ClassName - Brief one-line description
 * 
 * Multi-line explanation of:
 * - What the class does
 * - Key responsibilities
 * - Important behaviors
 * 
 * @example Usage example with code
 * $instance = new ClassName($param);
 * $result = $instance->method();
 * 
 * @example Another usage scenario
 * if ($instance->check()) {
 *     // Do something
 * }
 * 
 * @property-read Type $property Description for magic properties
 * 
 * @package YourNamespace
 */
class ClassName {
```

### Rules
- **First line**: Class name + brief description (< 80 chars)
- **Blank line** after first line
- **Description**: Bullet points for key features
- **@example blocks**: Show real usage patterns, not theory
- **@property-read**: Document magic properties for IDE autocomplete
- **@package**: Always include namespace

## Method Documentation

### Public Methods
```php
/**
 * Brief description of what method does
 * 
 * Extended explanation if needed:
 * - Special behaviors
 * - Side effects
 * - Performance considerations
 * 
 * @param Type $param Description of parameter
 * @param Type|null $optional Optional parameter with default
 * @return Type Description of return value
 */
public function methodName(Type $param, ?Type $optional = null): Type {
```

### Private/Protected Methods
```php
/**
 * Brief description sufficient for internal use
 * 
 * @param Type $param
 * @return Type
 */
private function helperMethod(Type $param): Type {
```

## Type Hints

### Always Use Type Hints
```php
// ✅ CORRECT
public function process(string $name, int $count): array {
    return [];
}

// ❌ WRONG
public function process($name, $count) {
    return [];
}
```

### Union Types (PHP 8.0+)
```php
public function get(string $key): string|int|null {
    return $this->data[$key] ?? null;
}
```

### Mixed Type
```php
public function raw(): mixed {
    return $this->value;
}
```

### Nullable Types
```php
// Short syntax (preferred)
public function find(?int $id): ?User {
    return $id ? User::find($id) : null;
}

// Union syntax (when combined with other types)
public function get(string $key): string|null {
    return $this->fields[$key] ?? null;
}
```

## PHPStorm Annotations

### Magic Properties (@property, @property-read, @property-write)
For classes with `__get()`, `__set()`, or `__isset()`:

```php
/**
 * @property-read FieldValue $title
 * @property-read FieldValue $content
 * @property-read FieldValue $button_url
 * @property string $name Writable property
 * @property-write int $id Write-only property
 */
class FieldContext {
    public function __get(string $key): FieldValue {
        return new FieldValue($this->get($key));
    }
}
```

### Magic Methods (@method)
For classes with `__call()`:

```php
/**
 * @method static User find(int $id)
 * @method bool save()
 */
class Model {
    public function __call(string $name, array $args) {
        // Dynamic method handling
    }
}
```

### Deprecation Warnings
```php
/**
 * Get escaped HTML value
 * 
 * @deprecated Use magic getter: $context->field->html()
 * @see FieldValue::html()
 * @param string $key
 * @return string
 */
public function get_esc_html(string $key): string {
```

### Parameter Documentation

#### Simple Parameters
```php
/**
 * @param string $key Field name
 * @param mixed $default Fallback value
 * @return mixed
 */
```

#### Complex Types
```php
/**
 * @param array<string, mixed> $config Configuration array
 * @param callable(string): bool $validator Validation callback
 * @return array<int, User> Array of User objects
 */
```

#### Variadic Parameters
```php
/**
 * @param string ...$values Variable number of values
 * @return bool
 */
public function in(string ...$values): bool {
```

## Examples and Usage Blocks

### Show Real Usage, Not Theory
```php
// ✅ GOOD - Real code someone would write
/**
 * @example Basic field access
 * $c = $context;
 * if ($c->has('button_url')) {
 *     echo '<a href="' . $c->button_url->url() . '">';
 * }
 */

// ❌ BAD - Theoretical placeholder code
/**
 * @example
 * $obj = new Thing();
 * $result = $obj->doSomething();
 */
```

### Multiple Examples for Different Scenarios
```php
/**
 * @example Equality check
 * $isLeft = $context->layout->is('left');
 * 
 * @example Multiple value check
 * $isVisible = $context->status->in('active', 'live', 'published');
 * 
 * @example Checkbox check
 * if ($context->featured->isTrue()) {
 *     // Handle featured content
 * }
 */
```

## Inline Comments

### When to Use
- Complex logic that isn't obvious
- Workarounds or gotchas
- Performance considerations
- Backward compatibility notes

### Style
```php
// Single-line for brief explanations
$value = $this->transform($input);

/* Multi-line for longer explanations
 * that need more context about why
 * something is done a certain way
 */
```

### What NOT to Comment
```php
// ❌ DON'T - States the obvious
$total = $price * $quantity; // Calculate total

// ✅ DO - Explains non-obvious behavior
// Quantity includes promotional items for volume discount calculation
$total = $price * $quantity;
```

## Complete Example

```php
<?php
namespace NOK2025\V1\PageParts;

/**
 * FieldValue - Smart field value wrapper with auto-escaping
 * 
 * Wraps field values from FieldContext to provide:
 * - Auto-escaping for different contexts (HTML, URL, attributes)
 * - Placeholder-aware escaping (preserves editor placeholders)
 * - Convenience comparison methods
 * - Type-safe value access
 * 
 * @example Basic usage with auto-escaping
 * <h1><?= $context->title ?></h1>
 * <a href="<?= $context->url->url() ?>">Link</a>
 * 
 * @example Conditional logic
 * $isLeft = $context->layout->is('left');
 * if ($context->featured->isTrue()) { }
 * 
 * @package NOK2025\V1\PageParts
 */
class FieldValue {
    private mixed $value;
    private bool $is_placeholder;

    /**
     * Constructor
     * 
     * @param mixed $value Field value from database
     */
    public function __construct(mixed $value) {
        $this->value = $value;
        $this->is_placeholder = is_string($value) 
            && str_starts_with($value, '<span class="placeholder-field');
    }

    /**
     * Get HTML-escaped value
     * 
     * Placeholders are preserved (not escaped) to display
     * correctly in the WordPress editor preview.
     * 
     * @return string
     */
    public function html(): string {
        return $this->is_placeholder ? $this->value : esc_html($this->value);
    }

    /**
     * Check if value equals given string
     * 
     * @param string $value Value to compare against
     * @return bool
     */
    public function is(string $value): bool {
        return $this->value === $value;
    }

    /**
     * Default string conversion uses HTML escaping
     * 
     * Allows: <?= $context->field ?> without explicit ->html()
     * 
     * @return string
     */
    public function __toString(): string {
        return $this->html();
    }
}
```

## Checklist

Before committing:
- [ ] Class has complete docblock with description and @package
- [ ] All public methods have docblocks with @param and @return
- [ ] Type hints on all parameters and return values
- [ ] Magic properties documented with @property-read
- [ ] At least one @example showing real usage
- [ ] Deprecated methods marked with @deprecated
- [ ] Complex logic has inline comments explaining "why"