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
| Link | `button_url:link` | string (alias for URL) |
| Checkbox | `featured:checkbox` | '1' or '0' |
| Select | `layout:select(left\|right)` | string |
| Select (labeled) | `position:select(Left::left\|Right::right)` | string |
| Icon Selector | `icon:icon-selector` | string |
| Repeater | `items:repeater(title:text,url:url)` | JSON array |
| Post Repeater | `posts:post_repeater(post:ervaringen)` | JSON array |
| Post Repeater | `posts:post_repeater(kennisbank)` | JSON array (short form) |

### Field Flags

- `!default(value)` - Default value in template header
- `!page-editable` - Overridable in page editor
- `!descr[text]` - Help text shown below field in editor

Example: `narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable`

## FieldContext Usage

```php
$c = $context;  // Standard shorthand
```

### Output Methods (FieldValue)

| Method | Returns | Description |
|--------|---------|-------------|
| `$c->field` | string | Auto-escaped HTML (same as `->html()`) |
| `->html()` | string | HTML entity escaped |
| `->url($fallback)` | string | URL encoded. Returns `$fallback` if empty |
| `->attr()` | string | Attribute escaped for use in HTML attributes |
| `->raw($fallback)` | mixed | Unescaped value. **Never output directly**. Returns `$fallback` if empty |

```php
<h1><?= $c->title ?></h1>                         // Auto-escaped
<a href="<?= $c->link->url() ?>">                 // URL-escaped
<a href="<?= $c->link->url('/fallback') ?>">     // With fallback URL
<div data-id="<?= $c->id->attr() ?>">            // Attribute-escaped
$value = $c->field->raw();                        // For logic only
$value = $c->field->raw('default');               // With fallback
```

### Conditional Methods (FieldValue)

All conditional methods return `bool` when called without arguments, or return `$ifTrue`/`$ifFalse` values when provided.

| Method | Description |
|--------|-------------|
| `->is($value, $ifTrue, $ifFalse)` | Exact equality check |
| `->isTrue($ifTrue, $ifFalse)` | Checkbox/boolean check (matches '1' or 'true') |
| `->in($array, $ifTrue, $ifFalse)` | Check if value is in array |
| `->contains($needle, $ifTrue, $ifFalse)` | Substring check |

```php
// Boolean usage
if ($c->layout->is('left')) { }
if ($c->featured->isTrue()) { }
if ($c->status->in(['active', 'live'])) { }
if ($c->colors->contains('dark')) { }

// Inline conditional (returns $ifTrue or $ifFalse)
$class = $c->layout->is('left', 'order-1', 'order-2');
$class = $c->featured->isTrue('active', '');
$class = $c->status->in(['active', 'live'], 'visible', 'hidden');
$class = $c->colors->contains('dark', 'light-text', 'dark-text');
```

### Utility Methods (FieldValue)

| Method | Returns | Description |
|--------|---------|-------------|
| `->otherwise($fallback)` | mixed | Returns field value, or `$fallback` if empty |
| `->json($fallback)` | array | Parses JSON string to array. Returns `$fallback` on invalid/empty |
| `->css_var($name)` | string | Generates CSS custom property: `--name:value` or empty string |

```php
// Fallback for empty fields
$title = $c->custom_title->otherwise('Default Title');

// Parse repeater JSON
$items = $c->items->json([]);                     // Empty array fallback
$items = $c->items->json($defaultItems);          // Custom fallback

// CSS custom properties (for inline styles)
<div style="<?= $c->bg_color->css_var('background-color') ?>">
// Outputs: style="--background-color:#ff0000" or style="" if empty
```

### Context Methods (FieldContext)

| Method | Returns | Description |
|--------|---------|-------------|
| `$c->has($key, $ifTrue, $ifFalse)` | bool\|mixed | Check if field has non-empty value |
| `$c->title()` | string | Post title (HTML-escaped, supports per-page override) |
| `$c->content()` | string | Post content (with wpautop, supports per-page override) |

```php
// Existence check (returns bool)
if ($c->has('button_url')) { }

// Inline conditional (returns $ifTrue or $ifFalse)
$class = $c->has('image', 'has-image', 'no-image');

// Title and content with override support
<h1><?= $c->title() ?></h1>
<?= $c->content() ?>
```

### Empty Value Detection

`has()` and `otherwise()` consider these values as empty:
- Empty string `''`
- Unchecked checkbox `'0'`
- Empty JSON `'[]'` or `'{}'`
- `null`

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

## Title & Content

Templates use `$context->title()` and `$context->content()` for post title and content.
These methods support per-page overrides when the same page part appears on multiple pages.

```php
// Title (HTML-escaped)
<h1><?= $context->title() ?></h1>
<h1 class="nok-fs-giant"><?= $c->title() ?></h1>

// Content (with wpautop formatting)
<?= $context->content() ?>
<div class="nok-fs-body"><?= $c->content() ?></div>
```

### Why Not the_title() / the_content()?

Using `$context->title()` and `$context->content()` instead of WordPress's `the_title()`
and `the_content()` enables **per-page overrides** for SEO duplicate content prevention.

When the same page part is embedded on multiple pages, editors can override the title
and/or content specifically for that page via the block editor sidebar panel
"Pagina-afhankelijke overrides".

**Migration from old pattern:**
```php
// Before (deprecated)
<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
<?php the_content(); ?>

// After (current)
<h1 class="nok-fs-giant"><?= $c->title() ?></h1>
<?= $c->content() ?>
```

### Override Storage

Overrides are stored in block attributes (not post meta):
- `_override_title` - Alternative title for this page
- `_override_content` - Alternative content for this page

Empty override = use original page part title/content.

## WordPress Integration

```php
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
- Use `$c->title()` and `$c->content()` instead of `the_title()` / `the_content()`
- Set defaults in field headers, not inline
- Check field existence with `$c->has()` before output
- Manual `esc_html()` for repeater array values
- Never output `->raw()` directly