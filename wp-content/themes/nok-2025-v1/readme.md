# NOK v25 Theme Page Part System
&copy; 2024 Klaas Leussink - Nederlandse Obesitas Kliniek B.V.

A component-based content management system for WordPress themes with live preview capabilities.

## Architecture

**Pages** → **Page Parts** (custom post type) → **Post Parts** (template files)

```
inc/
├── Theme.php                    # Main orchestrator
├── Core/
│   └── AssetManager.php         # CSS/JS handling
└── PageParts/
    ├── Registry.php             # Template scanning
    ├── MetaManager.php          # Meta operations
    ├── PreviewSystem.php        # Live previews
    ├── TemplateRenderer.php     # Template rendering
    ├── RenderContext.php        # Context detection
    └── RestEndpoints.php        # API endpoints
```

## Creating Templates

### Page Parts
Create: `template-parts/page-parts/hero.php`

```php
<?php
/**
 * Template Name: Hero Section
 * Description: Main hero banner
 * Slug: hero
 * Icon: dashicons-format-image
 * Custom Fields:
 * - title:text
 * - subtitle:textarea  
 * - button_text:text
 * - button_url:url
 * - layout:select(left|center|right)
 * - featured:checkbox(true)
 */

// Access fields via $page_part_fields array
echo '<h1>' . esc_html($page_part_fields['title']) . '</h1>';
```

### Post Parts
Create: `template-parts/post-parts/card.php`

```php
<?php
// Simple template file - no header required
// Access via $page_part_fields array
echo '<div class="card">';
echo '<h3>' . esc_html($page_part_fields['title']) . '</h3>';
echo '</div>';
```

### CSS Files
- `hero.css` - Development version
- `hero.min.css` - Production version (auto-selected when `development_mode = false`)

## Field Types

| Type | Example | Storage |
|------|---------|---------|
| `text` | `title:text` | String |
| `textarea` | `content:textarea` | String |
| `url` | `link:url` | URL |
| `select` | `layout:select(left\|center\|right)` | String |
| `checkbox` | `featured:checkbox(true)` | '1' or '0' |

Advanced select with labels:
```php
// position:select(Left Align::left|Center::center|Right Align::right)
```

## Rendering Contexts

| Context | Trigger | CSS Method |
|---------|---------|-----------|
| Frontend | Normal page view | `wp_enqueue_style()` |
| Page Editor | Block preview | `wp_enqueue_style()` |
| Post Editor | Page part preview | Enqueue + inline |
| REST Embed | API preview | Inline only |

## Usage

### Render Page Parts
```php
// Frontend rendering
$theme = Theme::get_instance();
$theme->include_page_part_template($design, [
    'post' => $post,
    'page_part_fields' => $fields
]);

// Direct rendering (any context)
$renderer = new TemplateRenderer();
$renderer->render_page_part($design, $fields);
$renderer->render_post_part($design, $fields);
```

### Access Fields
```php
// In templates
echo $page_part_fields['title'];
echo $page_part_fields['button_text'];

// From post ID
$fields = $meta_manager->get_page_part_fields($post_id, $design);
```

## Configuration

### Development Mode
```php
// Theme.php constructor
private bool $development_mode = true;  // Dev: use .css
private bool $development_mode = false; // Prod: prefer .min.css
```

### Template Registry
Templates auto-register based on file headers. Registry provides:
- Field definitions
- Template metadata
- Validation rules

## File Structure
```
template-parts/
├── page-parts/
│   ├── hero.php
│   ├── hero.css
│   ├── hero.min.css
│   └── hero.preview.css
└── post-parts/
    ├── card.php
    ├── card.css
    └── card.min.css
```

## Preview System

Live previews use WordPress transients to store editor state without saving. The system:
1. Captures editor changes via JavaScript
2. Stores state in transients (5min expiry)
3. Filters WordPress queries during preview
4. Renders with temporary data

## Key Classes

- `Theme` - Main orchestrator, delegates to components
- `Registry` - Scans templates, parses field definitions
- `MetaManager` - Handles meta fields, validation, storage
- `TemplateRenderer` - Context-aware rendering with CSS
- `PreviewSystem` - Live preview functionality
- `RenderContext` - Detects rendering context for CSS strategy