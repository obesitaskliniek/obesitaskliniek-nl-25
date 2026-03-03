# NOK 2025 Theme

WordPress theme for Nederlandse Obesitas Kliniek. Component-based architecture using a "page parts" system — reusable sections assembled via Gutenberg block editor.

**Stack:** PHP 8+, React/Gutenberg, SCSS (OKLCH color system), @wordpress/scripts

## Build Commands

```bash
npm install          # Install dependencies
npm run build        # Production build
npm run start        # Development watch mode
```

**ATF/BTF pipeline:** After modifying component SCSS, run `node tools/split-atf-btf.mjs` to regenerate the ATF/BTF CSS split (see [CSS Architecture](#css-architecture)).

## Architecture

```
inc/
├── Theme.php                    # Main orchestrator (singleton)
├── Assets.php                   # Icon management with caching
├── AllowedEditorBlocks.php      # Editor block filtering
├── BlockRenderers.php           # Custom block render callbacks
├── Colors.php                   # Color palette management
├── Customizer.php               # Theme customization settings
├── Helpers.php                  # Utility functions (phone, hours, dates)
├── PostTypes.php                # CPT/taxonomy registration
├── VoorlichtingForm.php         # Event form handling
├── Core/
│   └── AssetManager.php         # CSS/JS with dev/prod modes, ATF/BTF loading
├── libs/
│   └── hnl.cspGenerator.php     # CSP header generation
├── Navigation/
│   ├── MenuManager.php          # Menu registration & rendering
│   └── MenuIconFields.php       # Menu icon field handling
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
    ├── YoastIntegration.php     # Breadcrumbs, sitemaps, indexables
    └── PagePartSchema.php       # Schema.org structured data
```

```
template-parts/
├── page-parts/           # Page part CPT templates (kebab-case filenames)
├── post-parts/           # Post type-specific widgets (BMI calculator, etc.)
├── block-parts/          # Gutenberg block render templates
└── navigation/           # Desktop, mobile, footer navigation templates
```

```
src/
├── blocks/               # Gutenberg blocks (see below)
└── components/           # Shared React components
    ├── ColorSelector.js
    ├── FieldImportWizard.js
    ├── IconSelector.js
    ├── ImageSelector.js
    ├── LinkField.js
    └── TaxonomySelector.js
```

## Gutenberg Blocks

| Block | Description |
|-------|-------------|
| `embed-nok-page-part` | Embed a reusable page part with per-page field overrides |
| `embed-nok-post-part` | Embed a post-part widget (e.g. BMI calculator) |
| `embed-nok-video` | Video with play button overlay |
| `nok-video-section` | Video section (YouTube, Vimeo, self-hosted) |
| `general-nok-section` | Section wrapper with `nok-section` styling for regular content |
| `nok-attachment-downloads` | Downloadable attachments list (PDF, documents) |
| `nok-vestiging-voorlichtingen` | Upcoming events carousel (auto-detects vestiging) |
| `content-placeholder-nok-template` | Content placeholder within template layouts |

### Block Structure

```
src/blocks/{block-name}/
├── index.js          # Block registration and editor UI
├── render.php        # Server-side rendering (optional)
└── block.json        # Block metadata and attributes
```

### Embed Block Preview

The `embed-nok-page-part` block renders an iframe preview in the editor:
- iframe `src` is a REST endpoint: `/nok-2025-v1/v1/embed-page-part/{id}?overrides...`
- Page-part selection changes apply immediately
- Override field edits are debounced at **2 seconds** to avoid Cloudflare rate limits

## Custom Post Types

| Post Type | Slug | Archive | REST Base | Description |
|-----------|------|---------|-----------|-------------|
| Page Part | `page_part` | — | `page-parts` | Reusable page sections |
| Template Layout | `template_layout` | — | `template-layouts` | Single post template configs |
| Vestiging | `vestiging` | `/vestigingen/` | `vestigingen` | Clinic locations |
| Regio | `regio` | — | `regios` | SEO landing pages per region |
| Kennisbank | `kennisbank` | `/kennisbank/` | `kennisbank` | Knowledge base articles |

**Taxonomies:**
- `kennisbank_categories` — Hierarchical categories for kennisbank articles (enables `/kennisbank/{parent}/{child}/{post}/` URL structure)

**Protection:** `page_part` CPT is protected from public access — non-logged users get 404 while REST/admin access is preserved.

## Template Creation (Page Parts)

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

### Field Types

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

**Flags:** `!page-editable` (overridable per-page), `!default(value)`, `!descr[help text]`

### FieldContext API

```php
$c = $context;

// Output (auto-escaped)
<?= $c->field ?>                  // HTML-escaped (default)
<?= $c->field->url() ?>           // URL-escaped
<?= $c->field->attr() ?>          // Attribute-escaped
$c->field->raw()                  // Unescaped (logic only)

// Conditionals
$c->has('field')                              // Existence check
$c->has('field', 'yes', 'no')                 // Inline conditional
$c->field->is('value', 'match', 'no-match')   // Equality check
$c->field->isTrue('active', '')               // Checkbox check

// Utilities
$c->field->otherwise('fallback')              // Default if empty
$c->field->json([])                           // Parse JSON to array
$c->field->css_var('bg-color')                // Returns "--bg-color:value"
<?= $c->title() ?>                            // Title (with override support)
<?= $c->content() ?>                          // Content (with override support)
```

**Full documentation:** See `template-parts/page-parts/readme.md`

## CSS Architecture

### ATF/BTF Pipeline

Production CSS uses automated Above The Fold / Below The Fold splitting:

1. `nok-components.scss` compiles to the full CSS bundle via webpack
2. `tools/split-atf-btf.mjs` (PostCSS) splits it into `nok-atf.css` + `nok-btf.css` based on `tools/atf-btf-config.mjs`
3. CSSO minifies both to `.min.css` variants
4. `nok-atf.min.css` is inlined in `<head>` by `header.php`
5. `nok-btf.min.css` is enqueued deferred (`media="print" onload`) by `AssetManager.php`

**Diagnostic URLs** (logged-in only):
- `?critical-css-only` — renders only inlined ATF CSS
- `?legacy-css` — falls back to old hand-curated critical CSS
- `?debug` — loads ATF audit overlay

### Entry Points

| File | Purpose |
|------|---------|
| `assets/css/nok-components.scss` | Frontend styles (webpack) |
| `assets/css/nok-backend-css.scss` | Admin/editor styles (webpack) |
| `assets/css/color_tests-v2.scss` | Color system (`nok-colors-css`) |

### Dev/Prod Modes

| Mode | Behavior |
|------|----------|
| `development_mode = true` | Uses unminified `.css` files |
| `development_mode = false` | Prefers `.min.css`, falls back to `.css` |

Set in `inc/Theme.php` constructor.

### Context-Aware Loading

| Context | CSS Method |
|---------|-----------|
| Frontend | `wp_enqueue_style()` (ATF inline + BTF deferred) |
| Page Editor | Block preview, standard enqueue |
| Post Editor | Page part preview, enqueue + inline |
| REST API | Inline only |

## Post Meta Fields

### Registering Fields

```php
// In inc/Theme.php register_post_custom_fields()
PostMeta\MetaRegistry::register_field('vestiging', 'street', [
    'type' => 'text',
    'label' => 'Straat',
    'placeholder' => 'Voer straatnaam in...',
]);
```

### Supported Types

| Type | UI Component |
|------|-------------|
| `text` | TextControl |
| `textarea` | TextareaControl |
| `email` | TextControl (email) |
| `url` | TextControl (url) |
| `number` | TextControl (number) |
| `checkbox` | CheckboxControl |
| `post_select` | SelectControl |
| `opening_hours` | Custom component |

### Opening Hours

Special field type for business hours with werkdagen templates, individual day overrides, and explicit closed marking:

```php
PostMeta\MetaRegistry::register_field('vestiging', 'opening_hours', [
    'type' => 'opening_hours',
    'label' => 'Openingstijden',
]);

// Display
echo Helpers::format_opening_hours(get_post_meta($post_id, '_opening_hours', true));
```

## Navigation

**Registered locations:**

| Location | Description |
|----------|-------------|
| `primary` | Desktop main navigation |
| `mobile_primary` | Mobile main navigation |
| `mobile_drawer_footer` | Mobile drawer footer |
| `top_row` | Desktop top row |
| `footer` | Footer navigation |

**Rendering:**
```php
$menu_manager = Theme::get_instance()->get_menu_manager();
$menu_manager->render_desktop_menu_bar('primary');
$menu_manager->render_desktop_dropdown('primary');
$menu_manager->render_mobile_carousel('mobile_primary');
```

## REST Endpoints

**Base:** `/wp-json/nok-2025-v1/v1/`

| Route | Method | Permission | Description |
|-------|--------|------------|-------------|
| `/embed-page-part/{id}` | GET | Public | Rendered page part HTML with inline CSS |
| `/search/autocomplete` | GET | Public | Search autocomplete |
| `/posts/query` | GET | Public | Query posts with filters |
| `/link-search` | GET | `edit_posts` | Link field search (editor) |

**Base:** `/wp-json/nok/v1/`

| Route | Method | Permission | Description |
|-------|--------|------------|-------------|
| `/page-part/{id}/prune-fields` | POST | `edit_posts` | Prune orphaned template fields |
| `/page-part/{id}/orphaned-fields` | GET | `edit_posts` | Get orphaned fields from previous templates |

## Preview System

Uses WordPress transients for live previews without saving:

1. JavaScript captures editor changes
2. Stores state in transient (5min TTL)
3. Filters `get_post_metadata` during preview
4. Renders with temporary data

**Transient key:** `preview_editor_state_{$post_id}`

## Yoast SEO Integration

Page parts are automatically included in Yoast SEO content analysis when editing pages.

**How it works:**
1. Each page part renders in an iframe (reuses preview system)
2. Server extracts semantic content (headings, paragraphs, lists, image alt text)
3. Content embedded in iframe as `<meta name="yoast-content">` tag
4. Yoast reads aggregated content during analysis

**Zero additional network overhead** — uses preview requests that already happen.

**Per-block exclusion:** Each page part block has an "SEO Instellingen" panel with a toggle to exclude from analysis.

**Schema.org:** `PagePartSchema.php` collects structured data from rendered page parts via `nok_page_part_rendered` hook.

## Icon System

SVG icons in `assets/icons/`, categorized by filename prefix:

| Prefix | Category | Used for |
|--------|----------|----------|
| `ui_`  | UI       | Buttons, navigation, interface elements |
| `nok_` | NOK      | Medical/content illustrations |
| `logo_`| Logo     | Brand/partner logos |

## External Dependencies

**DOMule** (JavaScript):
- Location: `assets/js/domule/`
- Source: https://github.com/c-kick/DOMule
- Not managed via npm
- Provides event system, module loading, viewport scrolling

## Production Checklist

| Setting | File | Dev | Prod |
|---------|------|-----|------|
| `$development_mode` | `inc/Theme.php` | `true` | `false` |
| `$maintenance_mode` | `inc/Theme.php` | `false` | `false` |

## Further Documentation

Detailed development instructions, coding standards, and architecture decisions are documented in `CLAUDE.md` and the theme's `wp-content/themes/nok-2025-v1/CLAUDE.md`.

**Roadmap & Technical Debt:** See [TODO.md](TODO.md) for tracked items.

---

**License:** Proprietary
2025 Klaas Leussink - Nederlandse Obesitas Kliniek B.V.
