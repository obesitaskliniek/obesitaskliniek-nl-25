# NOK 2025 Theme
© 2025 Klaas Leussink - Nederlandse Obesitas Kliniek B.V.

WordPress theme with component-based page building using "page parts" - reusable sections composed into flexible layouts.

## Architecture

```
inc/
├── Theme.php                    # Main orchestrator
├── Assets.php                   # Static asset helpers (icons, images)
├── BlockRenderers.php           # Custom block render callbacks
├── Customizer.php               # Theme customization
├── Helpers.php                  # Utility functions (phone, hours formatting)
├── PostTypes.php                # CPT registration & protection
├── Core/
│   └── AssetManager.php         # CSS/JS with dev/prod modes
├── Navigation/
│   ├── MenuManager.php          # Menu registration & rendering
│   └── MenuWalker.php           # Custom menu walker
├── PageParts/
│   ├── Registry.php             # Template scanning & metadata
│   ├── MetaManager.php          # Meta operations & validation
│   ├── PreviewSystem.php        # Live previews via transients
│   ├── TemplateRenderer.php     # Context-aware rendering
│   ├── RenderContext.php        # Context detection
│   ├── RestEndpoints.php        # API endpoints
│   ├── FieldContext.php         # Template field access wrapper
│   └── FieldValue.php           # Individual field value object
├── PostMeta/
│   ├── MetaRegistry.php         # Field registration & types
│   └── MetaRegistrar.php        # WordPress/REST integration
└── SEO/
    └── YoastIntegration.php     # Yoast SEO customizations

src/blocks/
└── embed-nok-page-part/         # Gutenberg integration
```

## Template Creation

Place in `template-parts/page-parts/`:

```php
<?php
/**
 * Template Name: Hero Section
 * Description: Main hero banner
 * Slug: nok-hero
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text!page-editable
 * - button_text:text!page-editable!default(Learn More)
 * - button_url:url!page-editable
 * - layout:select(Left::left|Center::center|Right::right)
 * - icon:icon-selector!default(nok_check)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

$c = $context;  // Standard shorthand
?>

<section class="hero layout-<?= $c->layout->attr() ?>">
    <h1><?= $c->title() ?></h1>
    <?= $c->content() ?>

    <?php if ($c->has('button_url')) : ?>
        <a href="<?= $c->button_url->url() ?>"><?= $c->button_text ?></a>
    <?php endif; ?>
</section>
```

## Field Types

| Type | Syntax | Storage | Notes |
|------|--------|---------|-------|
| `text` | `title:text` | String | Single line |
| `textarea` | `content:textarea` | String | Multi-line |
| `url` | `link:url` | URL | Validated |
| `select` | `layout:select(left\|center\|right)` | String | Dropdown |
| `select` (labeled) | `pos:select(Left::left\|Center::center)` | String | Display vs value |
| `checkbox` | `featured:checkbox` | '1' or '0' | Boolean |
| `repeater` | `items:repeater(title:text,url:url)` | JSON array | Dynamic rows |
| `post_repeater` | `posts:post_repeater(post:category)` | JSON array | Post selector |
| `icon-selector` | `icon:icon-selector` | String | Icon picker |

### Field Flags

- `!page-editable` — Field can be overridden per-page when embedded
- `!default(value)` — Default value if not set
- `!descr[text]` — Help text shown below field in editor

## FieldContext Usage

```php
$c = $context;  // Standard shorthand

// Output methods (auto-escaped)
<?= $c->field ?>                  // HTML-escaped (default)
<?= $c->field->url() ?>           // URL-escaped
<?= $c->field->url('/fallback') ?>// With fallback
<?= $c->field->attr() ?>          // Attribute-escaped
$c->field->raw()                  // Unescaped (logic only)

// Conditional methods (return bool, or $ifTrue/$ifFalse when provided)
$c->has('field')                              // Existence check
$c->has('field', 'yes', 'no')                 // Inline conditional
$c->field->is('value', 'match', 'no-match')   // Equality check
$c->field->isTrue('active', '')               // Checkbox check
$c->field->in(['a', 'b'], 'found', '')        // Array membership
$c->field->contains('sub', 'has-it', '')      // Substring check

// Utility methods
$c->field->otherwise('fallback')              // Default if empty
$c->field->json([])                           // Parse JSON to array
$c->field->css_var('bg-color')                // Returns "--bg-color:value"

// Title & content (with per-page override support)
<?= $c->title() ?>
<?= $c->content() ?>
```

**Full documentation:** See `template-parts/page-parts/readme.md`

## Custom Post Types & Meta Fields

### Available Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Page Part | `page_part` | Reusable page sections |
| Template Layout | `template_layout` | Single post template configs |
| Vestiging | `vestiging` | Clinic locations (`/vestigingen/`) |

### Registering Meta Fields

```php
// In inc/Theme.php register_post_custom_fields()
PostMeta\MetaRegistry::register_field('vestiging', 'street', [
    'type' => 'text',
    'label' => 'Straat',
    'placeholder' => 'Voer straatnaam in...',
    'description' => 'De straatnaam van deze vestiging',
]);
```

### Supported Meta Field Types

| Type | Description | UI Component |
|------|-------------|--------------|
| `text` | Single line text | TextControl |
| `textarea` | Multi-line text | TextareaControl |
| `email` | Email address | TextControl (email) |
| `url` | URL | TextControl (url) |
| `number` | Integer | TextControl (number) |
| `checkbox` | True/false | CheckboxControl |
| `post_select` | Select from posts | SelectControl |
| `opening_hours` | Opening hours editor | Custom component |

### Opening Hours Field

Special field type for managing business hours:

```php
PostMeta\MetaRegistry::register_field('vestiging', 'opening_hours', [
    'type' => 'opening_hours',
    'label' => 'Openingstijden',
]);
```

Features:
- **Werkdagen template** - Set default hours for Monday-Friday
- **Individual overrides** - Override specific days with custom hours
- **Explicit closed** - Mark days as closed even when weekdays are set

Data structure:
```json
{
  "weekdays": [{"opens": "09:00", "closes": "17:00"}],
  "monday": [],
  "wednesday": [{"closed": true}],
  "friday": [{"opens": "09:00", "closes": "16:00"}]
}
```

Display hours:
```php
use NOK2025\V1\Helpers;
$opening_hours = get_post_meta($post_id, '_opening_hours', true);
echo Helpers::format_opening_hours($opening_hours);
```

### Category-Specific Fields

Restrict fields to specific post categories:

```php
$experience_cat = get_category_by_slug('ervaringen');

PostMeta\MetaRegistry::register_field('post', 'naam_patient', [
    'type' => 'text',
    'label' => 'Naam patiënt',
    'categories' => [$experience_cat->term_id],
]);
```

### Post Select Fields

Link to other post types:

```php
PostMeta\MetaRegistry::register_field('post', 'behandeld_door', [
    'type' => 'post_select',
    'post_type' => 'vestiging',
    'label' => 'Vestiging',
    'placeholder' => 'Onbekend / Niet van toepassing',
]);
```

### Accessing Meta Values

```php
// Get single field (note underscore prefix)
$street = get_post_meta($post_id, '_street', true);

// Format phone number (Dutch formatting)
use NOK2025\V1\Helpers;
$formatted = Helpers::format_phone($phone);
```

### Custom Post Type Templates

Archive template: `archive-{post-type}.php`
```php
// archive-vestiging.php
get_header('generic');
if (have_posts()) {
    while (have_posts()) {
        the_post();
        $city = get_post_meta(get_the_ID(), '_city', true);
    }
}
get_footer();
```

Single template: `single-{post-type}.php` or `template-parts/single-{post-type}-content.php`

### REST API Schema

For complex types like `opening_hours`, the schema is defined in `MetaRegistrar.php`:
- Validates data structure
- Converts between JSON (database) and objects (REST API)
- Uses `sanitize_callback` for saving
- Uses `prepare_callback` for loading

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

## Yoast SEO Integration

Page parts are automatically included in Yoast SEO content analysis when editing pages.

### How It Works

The integration piggybacks the existing iframe preview system:

1. Each page part renders in an iframe (already happens for preview)
2. Server extracts semantic content (headings, paragraphs, lists, image alt text)
3. Content embedded in iframe as `<meta name="yoast-content">` tag
4. Block component reads meta tag and stores in global data store
5. Yoast SEO reads aggregated content during analysis

**Zero additional network overhead** - uses preview requests that already happen.

### Features

**Visual Editor Only**
- SEO analysis works in visual mode
- Code editor shows notice: "Page Part SEO analysis is disabled in code editor mode"
- Automatically re-enables when switching back to visual mode

**Per-Block Exclusion**
- Each page part block has "SEO Instellingen" panel
- Toggle "Meenemen in SEO analyse" checkbox to exclude specific parts
- Visual badge shows on excluded blocks
- Analysis updates in real-time when toggled

**Content Analysis**
- Extracts: h1-h6 headings, paragraphs, list items, image alt text
- Skips: raw Gutenberg blocks, non-semantic markup
- Updates automatically when page parts change

### Technical Details

**Architecture:**
- Synchronous content delivery (no async race conditions)
- Conforms to [Yoast Developer Integration Guide](https://developer.yoast.com/blog/yoast-seo-developer-integration/)
- Follows ACF/Elementor integration patterns
- Content always matches rendered output (WYSIWYG for SEO)

**Debug Mode:**
Enable debug logging in browser console:
```javascript
window.nokYoastIntegration.debug = true;
```

**Limitations:**
- Only analyzes published page part content (not drafts)
- Requires visual editor mode
- Page parts must render successfully in iframes

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
    'context' => $context  // FieldContext instance
]);

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
│   ├── nok-hero.php
│   ├── nok-hero.css
│   ├── nok-hero.min.css          # Auto-selected in production
│   └── nok-hero.preview.css      # Page part editor only
├── post-parts/
│   ├── nok-bmi-calculator.php
│   └── nok-bmi-calculator.css
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

## Build Commands

```bash
npm install          # Install dependencies
npm run build        # Production build
npm run start        # Development watch mode
```

## Current Status

**Completed:**
- Modular component architecture
- Context-aware CSS loading
- Live preview system with transients (5min TTL)
- Dynamic field generation
- Development/production asset modes
- Repeater field UI
- Parameter overriding in page editor
- Featured image override support
- Gutenberg block integration
- Post type protection
- Navigation system
- REST API endpoints
- Yoast page part logic integration

**TODO - High Priority:**
- Cache invalidation when page parts change
- Enhanced SEO integration
- Sitemaps

**TODO - Medium Priority:**
- Usage tracking system
- Additional field types (image gallery, color picker)
- Bulk operations for page parts
- Partner logo system, like the icon-selector

## External Dependencies

**DOMule** (JavaScript):
- Location: `assets/js/domule/`
- Source: https://github.com/c-kick/DOMule
- Not managed via npm
- Required for frontend functionality
