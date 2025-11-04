# NOK v25 Theme Page Part System
&copy; 2024 Klaas Leussink - Nederlandse Obesitas Kliniek B.V.

A component-based content management system for WordPress themes with live preview capabilities.

## Architecture

**Pages** → **Page Parts** (custom post type) → **Post Parts** (template files)

```
inc/
├── Theme.php                    # Main orchestrator
├── PostTypes.php                # Custom post type registration
├── Helpers.php                  # Utility functions (phone formatting, opening hours, etc)
├── Core/
│   └── AssetManager.php         # CSS/JS handling
├── SEO/
│   └── YoastIntegration.php     # Yoast SEO customizations
├── PostMeta/
│   ├── MetaRegistry.php         # Field registration & type system
│   └── MetaRegistrar.php        # WordPress integration & REST schema
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

## Custom Post Types & Meta Fields

### Overview
The theme includes a flexible post meta system for registering custom fields on any post type. Custom post types are registered in `inc/PostTypes.php` and their meta fields are defined in `inc/Theme.php`.

### Available Post Types

#### Page Part (`page_part`)
Reusable page components (see Page Part System above)

#### Template Layout (`template_layout`)
Block editor layouts for configuring single post templates

#### Vestiging (`vestiging`)
Clinic locations with address, contact info, and opening hours
- Archive: `/vestigingen/`
- Single: `/vestigingen/{slug}/`

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

### Supported Field Types

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

Special field type for managing business hours with advanced features:

```php
PostMeta\MetaRegistry::register_field('vestiging', 'opening_hours', [
    'type' => 'opening_hours',
    'label' => 'Openingstijden',
    'description' => 'Stel hier de standaard openingstijden in...',
]);
```

Features:
- **Werkdagen template** - Set default hours for Monday-Friday
- **Individual overrides** - Override specific days with custom hours
- **Explicit closed** - Mark days as closed even when weekdays are set
- **Weekdays only** - Supports Monday-Friday (no weekend hours)
- **Data format** - JSON stored in database, object in REST API

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

Fields can be restricted to specific post categories:

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
// Get single field
$street = get_post_meta($post_id, '_street', true);

// Get all meta (includes protected meta with _ prefix)
$post_meta = get_post_meta($post_id);
$phone = $post_meta['_phone'][0] ?? '';

// Format phone number (Dutch formatting)
use NOK2025\V1\Helpers;
$formatted = Helpers::format_phone($phone);
```

### Creating Custom Post Type Templates

Archive template: `archive-{post-type}.php`
```php
// archive-vestiging.php
get_header('generic');
if (have_posts()) {
    while (have_posts()) {
        the_post();
        // Access meta fields
        $city = get_post_meta(get_the_ID(), '_city', true);
    }
}
get_footer();
```

Single template: `single-{post-type}.php` or `template-parts/single-{post-type}-content.php`

### Post Meta Architecture

```
inc/PostMeta/
├── MetaRegistry.php      # Field registration & type definitions
├── MetaRegistrar.php     # WordPress registration & REST schema
```

**MetaRegistry** - Stores field definitions and provides type utilities
**MetaRegistrar** - Registers fields with WordPress, enqueues editor UI

### REST API Schema

For complex types like `opening_hours`, the schema is defined in `MetaRegistrar.php`:
- Validates data structure
- Converts between JSON (database) and objects (REST API)
- Uses `sanitize_callback` for saving
- Uses `prepare_callback` for loading

## Key Classes

- `Theme` - Main orchestrator, delegates to components
- `Registry` - Scans templates, parses field definitions
- `MetaManager` - Handles meta fields, validation, storage
- `TemplateRenderer` - Context-aware rendering with CSS
- `PreviewSystem` - Live preview functionality
- `RenderContext` - Detects rendering context for CSS strategy
- `PostTypes` - Registers custom post types
- `PostMeta\MetaRegistry` - Field registration and type system
- `PostMeta\MetaRegistrar` - WordPress integration and REST schema