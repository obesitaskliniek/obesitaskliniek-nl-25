# Page Parts Templating System

## File Structure

```
template-parts/page-parts/
├── template-slug.php           # Template file
├── template-slug.css           # Frontend & preview CSS (optional)
└── template-slug.preview.css   # Page editor preview only (optional)
```

## Template Header

```php
<?php
/**
 * Template Name: Display Name
 * Description: Brief description
 * Slug: template-slug
 * Featured Image Overridable: true
 * Custom Fields:
 * - field_name:type
 * - field_name:type!default(value)
 * - field_name:type!page-editable
 * - field_name:type!page-editable!default(value)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */
```

## Field Types

| Type | Syntax | Storage |
|------|--------|---------|
| Text | `title:text` | string |
| Textarea | `content:textarea` | string |
| URL | `link:url` | string |
| Checkbox | `featured:checkbox` | '1' or '0' |
| Select | `layout:select(left\|right)` | string |
| Select (labeled) | `position:select(Left::left\|Right::right)` | string |
| Icon Selector | `icon:icon-selector` | string |
| Repeater | `items:repeater(title:text,url:url)` | JSON array |

### Field Flags

- `!default(value)` - Default value in template header
- `!page-editable` - Overridable in page editor

## FieldContext Usage

```php
$c = $context;  // Standard shorthand

// Access fields (auto-escaped HTML)
<?= $c->field_name ?>

// Explicit escaping
<?= $c->field_name->html() ?>   // HTML entities
<?= $c->field_name->url() ?>    // URL encoding
<?= $c->field_name->attr() ?>   // Attribute encoding

// Raw value (logic only, never output)
$value = $c->field_name->raw();

// Conditionals
$c->field_name->is('value')                      // bool
$c->field_name->is('value', 'if-true', 'else')  // inline if
$c->field_name->in('val1', 'val2')               // bool
$c->field_name->contains('substring')            // bool
$c->field_name->isTrue()                         // checkbox check
$c->has('field_name')                            // existence check

// Utilities
$c->field_name->otherwise('fallback')            // default if empty
$c->field_name->css_var('property-name')         // CSS custom property
$c->repeater_field->json($fallback_array)        // parse JSON repeater
```

## Common Patterns

### Layout Direction
```php
$left = $c->layout->is('left');
$order = $c->layout->is('left', 'order-1', 'order-2');
```

### CSS Custom Properties
```php
$circle_style = $c->circle_color->css_var('circle-background-color');
// Outputs: --circle-background-color:value or empty string
```

### Conditional Classes
```php
<section class="<?= $c->pull_down->isTrue('active', '') ?>">
<div class="layout-<?= $c->layout->attr() ?>">
```

### Repeater Fields
```php
$items = $c->items->json([/* fallback array */]);
foreach ($items as $item) {
    echo esc_html($item['title']);  // Manual escaping for array values
}
```

### Button with Existence Check
```php
<?php if ($c->has('button_url')) : ?>
    <a href="<?= $c->button_url->url() ?>">
        <?= $c->button_text ?>
    </a>
<?php endif; ?>
```

## WordPress Integration

```php
// Title and content
<?php the_title('<h1>', '</h1>'); ?>
<?php the_content(); ?>

// Featured image
use NOK2025\V1\Helpers;
$featured_image = Helpers::get_featured_image('class-name');

// Icons
use NOK2025\V1\Assets;
<?= Assets::getIcon('icon-name', 'class-name') ?>
```

## Best Practices

- Always use `$c = $context;` shorthand
- Use `->attr()` for values in HTML attributes
- Use `->url()` for href/src attributes
- Set defaults in field headers, not inline
- Check field existence with `$c->has()` before output
- Manual `esc_html()` for repeater array values
- Never output `->raw()` directly