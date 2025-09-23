# NOK 2025 Theme
© 2025 Klaas Leussink

## Overview

WordPress theme with component-based page building using "page parts" - reusable sections that can be composed into flexible page layouts.

## Page Parts System

### Architecture
```
inc/
├── Theme.php                    # Main orchestrator
├── Core/
│   └── AssetManager.php         # CSS/JS with minification
└── PageParts/
    ├── Registry.php             # Template scanning
    ├── MetaManager.php          # Meta operations
    ├── PreviewSystem.php        # Live previews
    ├── TemplateRenderer.php     # Context-aware rendering
    ├── RenderContext.php        # Rendering context detection
    └── RestEndpoints.php        # API endpoints
```

### Template Creation
Place templates in `template-parts/page-parts/` with header definitions:

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
 * - layout:select(Left::left|Center::center|Right::right)
 * - featured:checkbox(true)
 */

echo '<h1>' . esc_html($page_part_fields['title']) . '</h1>';
```

### Field Types
- `text`, `textarea`, `url` - Standard inputs
- `select(option1|option2)` - Dropdown selection
- `checkbox(true)` - Boolean with default
- `repeater` - JSON array storage

### CSS Handling
- Development: Uses `.css` files
- Production: Prefers `.min.css` when available
- Context-aware loading (frontend vs preview vs REST)

### Editor Features
- React-based template selector
- Live iframe preview with 500ms debouncing
- Unified transient system for editor state
- Smart change detection (user vs autosave)

## Rendering Contexts

| Context | Trigger | CSS Strategy |
|---------|---------|--------------|
| Frontend | Normal page | `wp_enqueue_style()` |
| Page Editor | Block preview | Standard enqueue |
| Post Editor | Page part preview | Enqueue + inline |
| REST Embed | API preview | Inline only |

## Configuration

Set production mode in `Theme.php`:
```php
private bool $development_mode = false; // Uses .min.css
```

## Usage

```php
// Render page parts
$theme = Theme::get_instance();
$theme->include_page_part_template($design, $args);
$theme->embed_page_part_template($design, $fields);

// Get fields
$fields = $theme->get_page_part_fields($post_id, $design);
```

## Current Status

**Completed:**
- ✅ Modular component architecture
- ✅ Context-aware CSS loading
- ✅ Live preview system with transients
- ✅ Dynamic field generation
- ✅ Performance optimizations
- ✅ Development/production asset modes
- ✅ Repeater field UI improvements

**TODO - High Priority:**
- [ ] SEO integration (Gutenberg blocks for page parts)
- [ ] Cache invalidation when page parts change

**TODO - Medium Priority:**
- [ ] Usage tracking system
- [ ] Enhanced field types (image, etc.)

## Technical Notes

- WordPress-native APIs for future-proofing
- Custom multiline header parser
- Type-based field sanitization
- REST API: `/wp-json/nok-2025-v1/v1/embed-page-part/{id}`
- 5-minute transient expiration for previews
