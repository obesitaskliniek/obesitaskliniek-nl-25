# NOK 2025 Theme
© 2025 Klaas Leussink - Nederlandse Obesitas Kliniek B.V.

WordPress theme with component-based page building using "page parts" - reusable sections composed into flexible layouts.

## Architecture

```
inc/
├── Theme.php                    # Main orchestrator
├── PostTypes.php                # CPT registration & protection
├── Customizer.php               # Theme customization
├── Core/
│   └── AssetManager.php         # CSS/JS with dev/prod modes
├── Navigation/
│   └── MenuManager.php          # Menu registration & rendering
└── PageParts/
    ├── Registry.php             # Template scanning & metadata
    ├── MetaManager.php          # Meta operations & validation
    ├── PreviewSystem.php        # Live previews via transients
    ├── TemplateRenderer.php     # Context-aware rendering
    ├── RenderContext.php        # Context detection
    └── RestEndpoints.php        # API endpoints

blocks/
└── embed-nok-page-part/         # Gutenberg integration
```

## Template Creation

Place in `template-parts/page-parts/`:

```php
<?php
/**
 * Template Name: Hero Section
 * Description: Main hero banner
 * Slug: hero
 * Icon: dashicons-format-image
 * Featured Image Overridable: true
 * Custom Fields:
 * - title:text
 * - subtitle:textarea
 * - layout:select(Left::left|Center::center|Right::right)
 * - featured:checkbox(true)
 * - repeater_field:repeater
 */

echo '<h1>' . esc_html($page_part_fields['title']) . '</h1>';

if (has_post_thumbnail()) {
    the_post_thumbnail('large');
}
```

## Field Types

| Type | Syntax | Storage | Notes |
|------|--------|---------|-------|
| `text` | `title:text` | String | Single line |
| `textarea` | `content:textarea` | String | Multi-line |
| `url` | `link:url` | URL | Validated |
| `select` | `layout:select(left\|center\|right)` | String | Dropdown |
| `select` (labeled) | `pos:select(Left::left\|Center::center)` | String | Display vs value |
| `checkbox` | `featured:checkbox(true)` | '1' or '0' | Default in parentheses |
| `repeater` | `items:repeater` | JSON array | Dynamic rows |
| `icon-selector` | `icon:icon-selector` | String | Icon picker |

## Post Type Protection

`page_part` CPT is protected from public access:
- Non-logged users get 404
- Prevents search indexing
- REST/admin access preserved
- Logged users can preview

## CSS Handling

**Development mode** (`development_mode = true`):
- Uses `.css` files

**Production mode** (`development_mode = false`):
- Prefers `.min.css` when available
- Falls back to `.css`

**Context-aware loading:**

| Context | CSS Method |
|---------|-----------|
| Frontend | `wp_enqueue_style()` |
| Page Editor | Block preview, standard enqueue |
| Post Editor | Page part preview, enqueue + inline |
| REST API | Inline only |

## Gutenberg Block Integration

**Block:** `embed-nok-page-part`

Features:
- Dropdown page part selector
- Live iframe preview
- Per-page field overrides
- Featured image override (when template allows)
- 500ms debounced updates

Usage in page editor:
1. Insert block
2. Select page part
3. Override fields as needed
4. Preview updates in real-time

## Preview System

Uses WordPress transients for live previews without saving:

1. JavaScript captures editor changes
2. Stores state in transient (5min TTL)
3. Filters `get_post_metadata` during preview
4. Renders with temporary data

**Transient key:** `preview_editor_state_{$post_id}`

## REST Endpoints

**Base:** `/wp-json/nok-2025-v1/v1/`

**Endpoints:**
- `GET /embed-page-part/{id}` - Page part HTML with inline CSS
- Response includes rendered template with context-appropriate styles

## Navigation System

**Registered locations:**
- `primary` - Main navigation
- `mobile_primary` - Mobile navigation
- `footer` - Footer navigation

**Rendering methods:**
```php
$menu_manager = Theme::get_instance()->get_menu_manager();
$menu_manager->render_desktop_menu_bar('primary');
$menu_manager->render_desktop_dropdown('primary');
$menu_manager->render_mobile_carousel('mobile_primary');
```

Returns hierarchical menu arrays with `is_current`, `is_current_ancestor`, and `has_children` flags.

## Theme Configuration

**Set production mode:**
```php
// inc/Theme.php constructor
private bool $development_mode = false; // Production: use .min.css
```

**Theme supports:**
- `title-tag`
- `post-thumbnails`
- `html5` (search-form, comment-form)

## Usage Examples

```php
// Get theme instance
$theme = Theme::get_instance();

// Render page part (frontend)
$theme->include_page_part_template($design, [
    'post' => $post,
    'page_part_fields' => $fields
]);

// Get page part fields
$fields = $theme->get_page_part_fields($post_id, $design);

// Direct rendering
$renderer = new TemplateRenderer();
$renderer->render_page_part($design, $fields);
$renderer->render_post_part($design, $fields);

// Access registry
$registry = $theme->get_page_part_registry();
$template_data = $registry[$design];
```

## File Structure

```
template-parts/
├── page-parts/
│   ├── hero.php
│   ├── hero.css
│   ├── hero.min.css          # Auto-selected in production
│   └── hero.preview.css      # Page part editor only
├── post-parts/
│   ├── card.php
│   └── card.css
└── navigation/
    ├── desktop-menu-bar.php
    ├── desktop-dropdown.php
    └── mobile-menu.php
```

## Customizer Integration

Access theme settings:
```php
$accent = Theme::get_instance()->get_setting('accent_color', '#FF0000');
```

Register settings in `inc/Customizer.php`.

## Current Status

**Completed:**
- ✅ Modular component architecture
- ✅ Context-aware CSS loading
- ✅ Live preview system with transients (5min TTL)
- ✅ Dynamic field generation
- ✅ Development/production asset modes
- ✅ Repeater field UI
- ✅ **Parameter overriding in page editor**
- ✅ **Featured image override support**
- ✅ **Gutenberg block integration**
- ✅ Post type protection
- ✅ Navigation system
- ✅ REST API endpoints

**TODO - High Priority:**
- [ ] Cache invalidation when page parts change
- [ ] Enhanced SEO integration
  - [ ] YOAST
  - [ ] Sitemaps

**TODO - Medium Priority:**
- [ ] Usage tracking system
- [ ] Additional field types (image gallery, color picker)
- [ ] Bulk operations for page parts

## External Dependencies

**DOMule** (JavaScript):
- Location: `assets/js/domule/`
- Source: https://github.com/c-kick/DOMule
- Not managed via npm
- Required for frontend functionality