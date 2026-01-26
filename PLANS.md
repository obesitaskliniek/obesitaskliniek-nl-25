# Implementation Plans

This document provides detailed implementation plans for items tracked in [TODO.md](./TODO.md).

**Generated:** 2026-01-26
**Theme:** `nok-2025-v1`

---

## Table of Contents

1. [Critical/Security Items](#criticalsecurity-items)
   - [SECURITY-001: REST API Rate Limiting](#security-001-rest-api-rate-limiting)
   - [SECURITY-002: dangerouslySetInnerHTML Policy](#security-002-dangerouslysetinnerhtml-policy)
2. [High Priority Items](#high-priority-items)
   - [HIGH-001: Cache Invalidation for Page Parts](#high-001-cache-invalidation-for-page-parts)
   - [HIGH-002: SEO Integration](#high-002-seo-integration)
3. [Medium Priority Items](#medium-priority-items)
   - [MED-001: Add tel Field Type](#med-001-add-tel-field-type)
   - [MED-002: Voorlichtingen Carousel Integration](#med-002-voorlichtingen-carousel-integration)
   - [MED-003: Usage Tracking System](#med-003-usage-tracking-system)
4. [Low Priority Items](#low-priority-items)
   - [LOW-001 to LOW-006: Brief Plans](#low-priority-brief-plans)
5. [Deprecated API Migration](#deprecated-api-migration)
   - [DEP-001: Global Helper Functions](#dep-001-global-helper-functions)
   - [DEP-002: FieldContext Legacy Methods](#dep-002-fieldcontext-legacy-methods)

---

## Critical/Security Items

### SECURITY-001: REST API Rate Limiting

**Status:** Verification Required
**Type:** Infrastructure/DevOps Task
**Risk Level:** HIGH - Public endpoints without rate limiting can enable abuse

#### Background

The REST endpoints in `RestEndpoints.php` intentionally delegate rate limiting to server-level configuration (nginx/WAF) rather than implementing application-level limiting. This is architecturally sound but requires verification that server-level protection is in place.

**Affected Endpoints:**
| Endpoint | Method | Risk |
|----------|--------|------|
| `/wp-json/nok-2025-v1/v1/posts/query` | GET | Enumeration attacks |
| `/wp-json/nok-2025-v1/v1/embed-page-part/{id}` | GET | Resource exhaustion |
| `/wp-json/nok-2025-v1/v1/search/autocomplete` | GET | DoS via expensive queries |

#### Implementation Plan

**Phase 1: Verification (Immediate)**

1. **Document current infrastructure**
   - Identify hosting provider (Kinsta, WP Engine, custom VPS, etc.)
   - Check for existing WAF services (Cloudflare, AWS WAF, Sucuri)
   - Review nginx/Apache configuration

2. **Verification checklist**
   ```bash
   # Test rate limiting (run from external IP)
   for i in {1..50}; do curl -s -o /dev/null -w "%{http_code}\n" \
     "https://domain.com/wp-json/nok-2025-v1/v1/search/autocomplete?q=test"; done
   # Should see 429 responses after threshold
   ```

3. **Expected configuration (nginx)**
   ```nginx
   # /etc/nginx/conf.d/rate-limit.conf
   limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

   # In server block or location
   location ~ /wp-json/nok-2025-v1/ {
       limit_req zone=api burst=20 nodelay;
       limit_req_status 429;
   }
   ```

**Phase 2: Fallback Implementation (If No Server-Level Protection)**

If server-level rate limiting cannot be confirmed, implement lightweight application-level limiting:

```php
// inc/PageParts/RateLimiter.php (new file)
namespace NOK2025\V1\PageParts;

class RateLimiter {
    private const RATE_LIMIT = 60;        // requests per window
    private const WINDOW_SECONDS = 60;    // 1 minute window

    public static function check(string $endpoint): bool {
        $ip = self::get_client_ip();
        $key = "rate_limit_{$endpoint}_" . md5($ip);

        $current = get_transient($key);
        if ($current === false) {
            set_transient($key, 1, self::WINDOW_SECONDS);
            return true;
        }

        if ($current >= self::RATE_LIMIT) {
            return false; // Rate limited
        }

        set_transient($key, $current + 1, self::WINDOW_SECONDS);
        return true;
    }

    private static function get_client_ip(): string {
        // Standard IP detection with proxy support
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return explode(',', $_SERVER[$header])[0];
            }
        }
        return 'unknown';
    }
}
```

**Files to Modify:**
| File | Change |
|------|--------|
| `inc/PageParts/RestEndpoints.php` | Add rate limit check to public callbacks |
| New: `inc/PageParts/RateLimiter.php` | Lightweight rate limiter class |

**Testing:**
- [ ] Verify server-level rate limiting exists
- [ ] If not, implement and test fallback
- [ ] Load test with 100 concurrent requests
- [ ] Verify legitimate traffic is unaffected

---

### SECURITY-002: dangerouslySetInnerHTML Policy

**Status:** Policy Enforcement (No Code Changes)
**Type:** Code Review Process
**Risk Level:** LOW - Current usage is safe, policy prevents future issues

#### Background

The Gutenberg block `src/blocks/embed-nok-page-part/index.js` uses `dangerouslySetInnerHTML` in multiple locations. Current analysis confirms all sources are trusted (admin-controlled content).

**Current Usage (Safe):**
| Line | Source | Trust Level |
|------|--------|-------------|
| 152 | `selectedOption?.label` | Admin-created page part titles |
| 158 | `selectedOption.template` | Theme-controlled template names |
| 285 | `displayLabel` | Derived from admin page parts |
| 287 | `option.template` | Theme registry |

#### Implementation Plan

**Phase 1: Documentation (Complete)**

The security note is already documented in the file header (lines 1-34). No additional code changes needed.

**Phase 2: Code Review Process**

Add to PR review checklist:

```markdown
## PR Review Checklist - embed-nok-page-part Changes

- [ ] No user-submitted content passed to `dangerouslySetInnerHTML`
- [ ] All HTML sources remain admin-controlled or theme-controlled
- [ ] If user content needed: DOMPurify sanitization implemented
- [ ] Security note in file header remains accurate
```

**Phase 3: Automated Protection (Optional)**

Add ESLint rule to flag new `dangerouslySetInnerHTML` usage:

```js
// .eslintrc.js addition
rules: {
  'react/no-danger': 'warn', // Flags for review, not error
}
```

**Files to Modify:**
| File | Change |
|------|--------|
| `.github/PULL_REQUEST_TEMPLATE.md` | Add security checklist |
| `.eslintrc.js` | Add dangerouslySetInnerHTML warning |

---

## High Priority Items

### HIGH-001: Cache Invalidation for Page Parts

**Status:** Ready for Implementation
**Type:** Feature Development
**Impact:** Healthcare content accuracy

#### Background

The page part system uses transient caching for the registry (`Registry.php`) but lacks cache invalidation when individual page parts are modified. For healthcare content, stale information can have material impact.

**Current Cache Architecture:**
```
┌─────────────────────┐    ┌───────────────────────┐
│ Instance Cache      │ -> │ Transient Cache       │
│ (per-request)       │    │ (1 hour in production)│
│ $this->part_registry│    │ nok_page_parts_       │
└─────────────────────┘    │ registry_{mtime}      │
                           └───────────────────────┘
                                     ↓
                           ┌───────────────────────┐
                           │ File-based mtime      │
                           │ (template changes)    │
                           └───────────────────────┘
```

**Gap:** Content changes to page parts don't trigger cache invalidation.

#### Implementation Plan

**Phase 1: Hook-Based Invalidation**

```php
// Add to inc/PageParts/Registry.php

/**
 * Register cache invalidation hooks
 */
public function register_invalidation_hooks(): void {
    // Invalidate on page part save
    add_action('save_post_page_part', [$this, 'invalidate_cache'], 10, 2);

    // Invalidate on page part delete
    add_action('delete_post', [$this, 'invalidate_on_delete'], 10, 2);

    // Invalidate on meta update (design_slug changes)
    add_action('updated_post_meta', [$this, 'invalidate_on_meta_change'], 10, 4);
}

/**
 * Clear all registry caches
 */
public function invalidate_cache(int $post_id, \WP_Post $post): void {
    // Skip autosaves and revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    // Clear instance cache
    $this->part_registry = null;

    // Clear all transient variants
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . self::CACHE_KEY . '%'
        )
    );

    // Optional: Clear object cache if available
    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group('nok_page_parts');
    }

    // Log invalidation in development
    if (!defined('SITE_LIVE') || !SITE_LIVE) {
        error_log("[NOK Cache] Registry invalidated for post {$post_id}");
    }
}

/**
 * Invalidate cache when page part is deleted
 */
public function invalidate_on_delete(int $post_id, \WP_Post $post): void {
    if ($post->post_type === 'page_part') {
        $this->invalidate_cache($post_id, $post);
    }
}

/**
 * Invalidate when design_slug meta changes (template switch)
 */
public function invalidate_on_meta_change(
    int $meta_id,
    int $object_id,
    string $meta_key,
    $meta_value
): void {
    if ($meta_key === 'design_slug') {
        $post = get_post($object_id);
        if ($post && $post->post_type === 'page_part') {
            $this->invalidate_cache($object_id, $post);
        }
    }
}
```

**Phase 2: Page-Level Fragment Cache (Optional Enhancement)**

For high-traffic pages, implement fragment caching of rendered page parts:

```php
// inc/PageParts/FragmentCache.php (new file)
namespace NOK2025\V1\PageParts;

class FragmentCache {
    private const TTL = 3600; // 1 hour

    public static function get(int $post_id, string $design, array $overrides = []): ?string {
        $key = self::build_key($post_id, $design, $overrides);
        return get_transient($key) ?: null;
    }

    public static function set(int $post_id, string $design, array $overrides, string $html): void {
        $key = self::build_key($post_id, $design, $overrides);
        set_transient($key, $html, self::TTL);
    }

    public static function invalidate(int $post_id): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_nok_fragment_' . $post_id . '%'
            )
        );
    }

    private static function build_key(int $post_id, string $design, array $overrides): string {
        return 'nok_fragment_' . $post_id . '_' . md5($design . serialize($overrides));
    }
}
```

**Files to Modify:**
| File | Change |
|------|--------|
| `inc/PageParts/Registry.php` | Add invalidation hooks and methods |
| `inc/Theme.php` | Call `$registry->register_invalidation_hooks()` |
| New: `inc/PageParts/FragmentCache.php` | Optional fragment caching |

**Testing:**
- [ ] Modify page part content, verify cache cleared
- [ ] Change page part template (design_slug), verify cache cleared
- [ ] Delete page part, verify cache cleared
- [ ] Verify autosaves don't trigger invalidation
- [ ] Load test with high traffic scenario

---

### HIGH-002: SEO Integration

**Status:** Ready for Implementation
**Type:** Feature Development
**Dependencies:** Yoast SEO plugin (already integrated)

#### Background

Page parts need enhanced SEO integration for:
1. Schema.org structured data (healthcare-specific)
2. Open Graph meta tags
3. XML sitemap inclusion

**Current State:**
- Yoast SEO integration exists for content analysis (`yoast-content` meta tag)
- No structured data for page parts
- Page parts not in sitemap

#### Implementation Plan

**Phase 1: Schema.org Structured Data**

Create healthcare-appropriate schema for page parts:

```php
// inc/SEO/PagePartSchema.php (new file)
namespace NOK2025\V1\SEO;

class PagePartSchema {

    public function register_hooks(): void {
        add_action('wp_head', [$this, 'output_schema'], 20);
    }

    public function output_schema(): void {
        if (!is_singular('page_part')) {
            return;
        }

        $post_id = get_the_ID();
        $design = get_post_meta($post_id, 'design_slug', true);

        $schema = $this->build_schema($post_id, $design);
        if ($schema) {
            echo '<script type="application/ld+json">' .
                 wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
                 '</script>' . "\n";
        }
    }

    private function build_schema(int $post_id, string $design): ?array {
        $base = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPageElement',
            'name' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'url' => get_permalink($post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
        ];

        // Add healthcare-specific schema based on design type
        switch ($design) {
            case 'nok-hero':
            case 'nok-hero-video':
                $base['@type'] = 'WebPageElement';
                $base['cssSelector'] = '.nok-hero';
                break;

            case 'nok-faq':
                return $this->build_faq_schema($post_id);

            case 'nok-team':
                return $this->build_team_schema($post_id);

            default:
                // Generic WebPageElement
                break;
        }

        return $base;
    }

    private function build_faq_schema(int $post_id): array {
        // Extract FAQ items from page part content
        $faqs = $this->extract_faq_items($post_id);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(function($faq) {
                return [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer']
                    ]
                ];
            }, $faqs)
        ];
    }

    private function build_team_schema(int $post_id): array {
        // Medical organization schema for team sections
        return [
            '@context' => 'https://schema.org',
            '@type' => 'MedicalOrganization',
            'name' => 'Nederlands Obesitas Kliniek',
            'url' => home_url(),
            'medicalSpecialty' => 'Bariatric Surgery'
        ];
    }

    private function extract_faq_items(int $post_id): array {
        // Implementation depends on how FAQs are stored
        // Could be repeater field or structured content
        return [];
    }
}
```

**Phase 2: Open Graph Enhancement**

```php
// inc/SEO/OpenGraphEnhancer.php (new file)
namespace NOK2025\V1\SEO;

class OpenGraphEnhancer {

    public function register_hooks(): void {
        // Filter Yoast's Open Graph output
        add_filter('wpseo_opengraph_image', [$this, 'filter_og_image'], 10, 2);
        add_filter('wpseo_opengraph_desc', [$this, 'filter_og_description']);
    }

    public function filter_og_image($image, $presenter = null): string {
        // Use page part featured image if available
        if (is_singular() && has_post_thumbnail()) {
            return get_the_post_thumbnail_url(null, 'large');
        }
        return $image;
    }

    public function filter_og_description($description): string {
        // Extract description from page part content if empty
        if (empty($description) && is_singular('page_part')) {
            $content = get_the_content();
            $description = wp_trim_words(wp_strip_all_tags($content), 30);
        }
        return $description;
    }
}
```

**Phase 3: Sitemap Integration**

```php
// inc/SEO/SitemapIntegration.php
namespace NOK2025\V1\SEO;

class SitemapIntegration {

    public function register_hooks(): void {
        // Yoast sitemap filter
        add_filter('wpseo_sitemap_entry', [$this, 'modify_sitemap_entry'], 10, 3);

        // Exclude certain page parts from sitemap
        add_filter('wpseo_exclude_from_sitemap_by_post_ids', [$this, 'exclude_private_parts']);
    }

    public function modify_sitemap_entry($url, $type, $post): array {
        if ($post && $post->post_type === 'page_part') {
            // Set appropriate priority based on design
            $design = get_post_meta($post->ID, 'design_slug', true);

            $priorities = [
                'nok-hero' => '0.8',
                'nok-faq' => '0.7',
                'nok-cta' => '0.5',
            ];

            $url['pri'] = $priorities[$design] ?? '0.5';
            $url['mod'] = get_the_modified_date('c', $post);
        }
        return $url;
    }

    public function exclude_private_parts(array $excluded): array {
        // Exclude page parts marked as internal-only
        // Implementation depends on how internal parts are flagged
        return $excluded;
    }
}
```

**Files to Create:**
| File | Purpose |
|------|---------|
| `inc/SEO/PagePartSchema.php` | Schema.org structured data |
| `inc/SEO/OpenGraphEnhancer.php` | Open Graph meta enhancements |
| `inc/SEO/SitemapIntegration.php` | XML sitemap configuration |

**Files to Modify:**
| File | Change |
|------|--------|
| `inc/Theme.php` | Initialize SEO classes |
| `functions.php` | Autoload new SEO namespace |

---

## Medium Priority Items

### MED-001: Add tel Field Type

**Status:** Ready for Implementation
**Type:** Feature Enhancement
**Complexity:** Low

#### Background

The phone field for `vestiging` currently uses `type: 'text'` instead of proper `tel` type. Adding `tel` type provides:
- Mobile numeric keyboard
- Better accessibility
- HTML5 semantic correctness

**Current State (Theme.php:630):**
```php
PostMeta\MetaRegistry::register_field('vestiging', 'phone', [
    'type'        => 'text',  // Should be 'tel'
    'label'       => 'Telefoonnummer',
    ...
]);
```

#### Implementation Plan

**Step 1: Update MetaRegistry.php**

```php
// Add to get_sanitize_callback() match statement
'tel' => 'sanitize_text_field',

// Add to get_rest_type() match statement
'tel' => 'string',

// Add to get_default_value() match statement
'tel' => '',
```

**Step 2: Update nok-post-meta-panel.js**

```js
// In the field type switch statement, add:
case 'tel':
    return (
        <TextControl
            key={metaKey}
            label={field.label}
            value={meta[metaKey] || ''}
            onChange={(value) => setMeta({ ...meta, [metaKey]: value })}
            type="tel"
            placeholder={field.placeholder || ''}
            help={field.description || ''}
        />
    );
```

**Step 3: Update Theme.php**

```php
// Change line ~630 from:
'type' => 'text',
// To:
'type' => 'tel',
```

**Files to Modify:**
| File | Lines | Change |
|------|-------|--------|
| `inc/PostMeta/MetaRegistry.php` | 84, 102, 117 | Add `'tel'` cases |
| `assets/js/nok-post-meta-panel.js` | Field switch | Add `'tel'` case |
| `inc/Theme.php` | ~630 | Change `'text'` to `'tel'` |

**Testing:**
- [ ] Phone field shows numeric keyboard on mobile
- [ ] REST API returns string type
- [ ] Existing phone data preserved after migration

---

### MED-002: Voorlichtingen Carousel Integration

**Status:** Ready for Integration
**Type:** Feature Integration
**Dependencies:** Component exists at `template-parts/post-parts/nok-vestiging-voorlichtingen.php`

#### Background

The voorlichtingen carousel component is complete but not integrated into vestiging pages. The component:
- Auto-detects city from vestiging title
- Displays upcoming voorlichtingen in carousel
- Links to filtered archive

#### Implementation Plan

**Option A: Template Integration (Recommended)**

Add to `template-parts/single-vestiging-content.php`:

```php
// After main vestiging content, before closing tags
<?php
// Voorlichtingen carousel - shows upcoming sessions for this location
$theme = \NOK2025\V1\Theme::get_instance();
$theme->embed_post_part_template('nok-vestiging-voorlichtingen');
?>
```

**Option B: Customizer Control**

Add toggle in Customizer to show/hide on vestiging pages:

```php
// inc/Customizer/VestigingSettings.php
$wp_customize->add_setting('show_voorlichtingen_on_vestiging', [
    'default' => true,
    'sanitize_callback' => 'wp_validate_boolean',
]);

$wp_customize->add_control('show_voorlichtingen_on_vestiging', [
    'label' => __('Toon voorlichtingen op vestiging pagina\'s', 'nok-2025-v1'),
    'section' => 'vestiging_settings',
    'type' => 'checkbox',
]);
```

Then in template:
```php
<?php if (get_theme_mod('show_voorlichtingen_on_vestiging', true)): ?>
    <?php $theme->embed_post_part_template('nok-vestiging-voorlichtingen'); ?>
<?php endif; ?>
```

**Files to Modify:**
| File | Change |
|------|--------|
| `template-parts/single-vestiging-content.php` | Add carousel embed |
| Optional: `inc/Customizer/VestigingSettings.php` | Add toggle control |

**Testing:**
- [ ] Carousel appears on vestiging pages
- [ ] Correct voorlichtingen shown for each location
- [ ] Empty state handled gracefully (no carousel if no voorlichtingen)

---

### MED-003: Usage Tracking System

**Status:** Design Phase
**Type:** New Feature
**Complexity:** Medium

#### Background

Implement tracking to identify unused page parts for cleanup. Important for maintaining a lean content system.

#### Implementation Plan

**Phase 1: Database Schema**

```sql
-- Create tracking table (WordPress-style)
CREATE TABLE {$wpdb->prefix}nok_page_part_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_part_id BIGINT UNSIGNED NOT NULL,
    host_post_id BIGINT UNSIGNED NOT NULL,
    block_index INT UNSIGNED DEFAULT 0,
    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usage (page_part_id, host_post_id, block_index),
    KEY idx_page_part (page_part_id),
    KEY idx_last_seen (last_seen)
);
```

**Phase 2: Tracking Hook**

```php
// inc/PageParts/UsageTracker.php
namespace NOK2025\V1\PageParts;

class UsageTracker {

    public function register_hooks(): void {
        // Track on post save
        add_action('save_post', [$this, 'track_page_part_usage'], 20, 2);

        // Cleanup on post delete
        add_action('delete_post', [$this, 'cleanup_tracking']);
    }

    public function track_page_part_usage(int $post_id, \WP_Post $post): void {
        // Skip autosaves, revisions, page_parts themselves
        if (wp_is_post_autosave($post_id) ||
            wp_is_post_revision($post_id) ||
            $post->post_type === 'page_part') {
            return;
        }

        // Parse blocks and find page part embeds
        $blocks = parse_blocks($post->post_content);
        $this->scan_blocks_recursive($blocks, $post_id);
    }

    private function scan_blocks_recursive(array $blocks, int $host_id, int &$index = 0): void {
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'nok2025/embed-nok-page-part') {
                $page_part_id = $block['attrs']['postId'] ?? 0;
                if ($page_part_id > 0) {
                    $this->record_usage($page_part_id, $host_id, $index);
                }
                $index++;
            }

            if (!empty($block['innerBlocks'])) {
                $this->scan_blocks_recursive($block['innerBlocks'], $host_id, $index);
            }
        }
    }

    private function record_usage(int $page_part_id, int $host_id, int $index): void {
        global $wpdb;
        $table = $wpdb->prefix . 'nok_page_part_usage';

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (page_part_id, host_post_id, block_index, last_seen)
             VALUES (%d, %d, %d, NOW())
             ON DUPLICATE KEY UPDATE last_seen = NOW()",
            $page_part_id, $host_id, $index
        ));
    }
}
```

**Phase 3: Admin Interface**

Create admin page showing:
- Unused page parts (never referenced)
- Rarely used page parts (single reference)
- Usage statistics per page part

**Files to Create:**
| File | Purpose |
|------|---------|
| `inc/PageParts/UsageTracker.php` | Core tracking logic |
| `inc/Admin/PagePartUsageReport.php` | Admin interface |

---

## Low Priority Items

### Low Priority Brief Plans

**LOW-001: Comma Support in Descriptions**
- Implement quoted string parsing in `FieldDefinitionParser.php`
- Syntax: `!descr["Text with, comma"]`
- Use regex: `/!descr\["([^"]+)"\]/` or `/!descr\[([^\]]+)\]/`

**LOW-002: Conflicting Options Handler**
- Add `!disables(field_name)` modifier to field parser
- Implement JavaScript handler in admin panel to disable/uncheck conflicting fields
- Store relationships in field definition registry

**LOW-003: Spacers/Gaps System Refactor**
- Audit all spacing utilities in SCSS files
- Create design tokens file: `_spacing-tokens.scss`
- Base unit: 4px with multipliers (0.25, 0.5, 1, 1.5, 2, 3, 4, 6, 8)
- Generate utility classes: `.nok-gap-{size}`, `.nok-mt-{size}`, etc.

**LOW-004: Additional Field Types**
- Image gallery: Use WordPress media library multi-select
- Color picker: Use `@wordpress/components` ColorPicker
- Add to MetaRegistry with appropriate sanitization

**LOW-005: Bulk Operations**
- Extend WordPress admin list table
- Add bulk actions: Edit (batch meta), Delete, Duplicate
- Use `admin_init` hook with `current_screen` check

**LOW-006: Partner Logo System**
- Create `partner_logo` custom post type
- Reuse icon-selector component architecture
- Store as SVG or optimized image references

---

## Deprecated API Migration

### DEP-001: Global Helper Functions

**Status:** Safe to Remove (wrapper functions)
**Location:** `inc/Helpers.php:1870-1889`

#### Current State

```php
// Deprecated wrappers
function makeRandomString(int $bits = 256): string {
    return \NOK2025\V1\Helpers::makeRandomString($bits);
}

function format_phone(string $phone, string $landcode = '31'): string {
    return \NOK2025\V1\Helpers::format_phone($phone);
}
```

#### Migration Plan

1. **Search for usage:**
   ```bash
   grep -r "makeRandomString\|format_phone" --include="*.php" \
     wp-content/themes/nok-2025-v1/
   ```

2. **Update call sites** to use namespaced methods:
   ```php
   // Before
   $random = makeRandomString(128);
   $phone = format_phone($number);

   // After
   $random = \NOK2025\V1\Helpers::makeRandomString(128);
   $phone = \NOK2025\V1\Helpers::format_phone($number);
   ```

3. **Remove deprecated functions** after all call sites updated

---

### DEP-002: FieldContext Legacy Methods

**Status:** Safe to Remove (no usage found)
**Location:** `inc/PageParts/FieldContext.php:120-204`

#### Current State

The following methods are marked deprecated and no usage found in templates:
- `get_esc_html($key)` → `$context->field->html()`
- `get_esc_url($key)` → `$context->field->url()`
- `get_link($key)` → `$context->field->link()`
- `get_esc_attr($key)` → `$context->field->attr()`

#### Migration Plan

1. **Verify no usage:**
   ```bash
   grep -r "get_esc_html\|get_esc_url\|get_link\|get_esc_attr" \
     --include="*.php" wp-content/themes/nok-2025-v1/template-parts/
   ```

2. **If usage found**, update to magic getter syntax:
   ```php
   // Before
   echo $context->get_esc_html('title');

   // After
   echo $context->title->html();
   // Or simply (auto-escapes):
   echo $context->title;
   ```

3. **Remove deprecated methods** from FieldContext.php

---

## Appendix: Quick Reference

### Priority Matrix

| ID | Priority | Effort | Impact | Status |
|----|----------|--------|--------|--------|
| SECURITY-001 | Critical | Low | High | Verify |
| SECURITY-002 | Critical | None | Low | Policy |
| HIGH-001 | High | Medium | High | Ready |
| HIGH-002 | High | Medium | Medium | Ready |
| MED-001 | Medium | Low | Low | Ready |
| MED-002 | Medium | Low | Medium | Ready |
| MED-003 | Medium | High | Medium | Design |
| LOW-* | Low | Varies | Low | Backlog |
| DEP-* | Low | Low | None | Ready |

### File Index

| File | Related TODOs |
|------|---------------|
| `inc/PageParts/RestEndpoints.php` | SECURITY-001 |
| `src/blocks/embed-nok-page-part/index.js` | SECURITY-002 |
| `inc/PageParts/Registry.php` | HIGH-001 |
| `inc/SEO/` (new) | HIGH-002 |
| `inc/PostMeta/MetaRegistry.php` | MED-001 |
| `inc/Theme.php` | MED-001 |
| `template-parts/single-vestiging-content.php` | MED-002 |
| `inc/PageParts/FieldDefinitionParser.php` | LOW-001, LOW-002 |
| `inc/Helpers.php` | DEP-001 |
| `inc/PageParts/FieldContext.php` | DEP-002 |
