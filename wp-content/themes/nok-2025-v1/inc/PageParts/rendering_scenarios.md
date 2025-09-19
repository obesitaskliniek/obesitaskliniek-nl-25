# NOK Theme Page Part Rendering Scenarios
(c) 2025 Klaas Leussink / Nederlandse Obesitas Kliniek B.V.

## Context Detection & CSS Handling

| Scenario | Context | CSS Strategy | Why |
|----------|---------|--------------|-----|
| **Frontend Page** | `CONTEXT_FRONTEND` | Standard `wp_enqueue_style()` | Full WordPress asset system available |
| **Page Editor Preview** | `CONTEXT_PAGE_EDITOR_PREVIEW` | Standard `wp_enqueue_style()` | Block editor handles asset loading |
| **Post Editor Preview** | `CONTEXT_POST_EDITOR_PREVIEW` | Enqueue + inline `<link>` | Immediate CSS needed for preview |
| **REST Embed** | `CONTEXT_REST_EMBED` | Inline `<link>` only | No WordPress asset system |

## Detailed Scenarios

### 1. Frontend Rendering
**Path**: Page → Page Part → Post Part  
**Context**: `CONTEXT_FRONTEND`  
**CSS**: Standard WordPress enqueue system  
**Entry Point**: `Theme::include_page_part_template()`

### 2. Page Editor Preview (Page Part in Page)
**Path**: Page Editor → Block → REST API → Page Part  
**Context**: `CONTEXT_REST_EMBED`  
**CSS**: Inline `<link>` tags in REST response  
**Entry Point**: `RestEndpoints::embed_page_part_callback()`

### 3. Post Editor Preview (Page Part)
**Path**: Post Editor → Preview → Page Part  
**Context**: `CONTEXT_POST_EDITOR_PREVIEW`  
**CSS**: Enqueue + inline output  
**Entry Point**: Preview system with transient data

### 4. Post Editor Preview (Post Part in Page Part)
**Path**: Post Editor → Page Part template includes Post Part  
**Context**: `CONTEXT_POST_EDITOR_PREVIEW`  
**CSS**: Enqueue + inline output  
**Entry Point**: Template calls `TemplateRenderer::render_post_part()`

### 5. Page Editor Preview (Post Part in Page Part in Page)
**Path**: Page Editor → REST → Page Part → Post Part  
**Context**: `CONTEXT_REST_EMBED`  
**CSS**: Inline `<link>` tags  
**Entry Point**: REST callback → Page Part → Post Part

### 6. Block Editor Context (Admin)
**Path**: Block editor interfaces  
**Context**: `CONTEXT_PAGE_EDITOR_PREVIEW`  
**CSS**: Standard enqueue  
**Entry Point**: Various admin interfaces

## CSS Loading Logic

```php
// Frontend - Standard WordPress
wp_enqueue_style($design, $css_uri, [], $version);

// Page Editor - Standard WordPress  
wp_enqueue_style($design, $css_uri, [], $version);

// Post Editor - Enqueue + Immediate Output
wp_enqueue_style($design, $css_uri, [], $version);
echo "<link rel=\"stylesheet\" href=\"{$css_uri}\" />";

// REST Embed - Inline Only
echo "<link rel=\"stylesheet\" href=\"{$css_uri}\" />";
```

## Context Detection

```php
private function detect_context(): string {
    if (wp_is_serving_rest_request()) return 'rest_embed';
    if (is_preview()) return 'post_editor_preview'; 
    if (is_admin()) return 'page_editor_preview';
    return 'frontend';
}
```

## Usage Examples

```php
// Frontend page rendering
$theme->include_page_part_template($design, $args);

// Direct page part rendering (any context)
$renderer->render_page_part($design, $fields);

// Direct post part rendering (any context)  
$renderer->render_post_part($design, $fields);

// Check current context
$context = $renderer->get_current_context();
```