# Project TODO Tracker

This file centralizes all technical debt, feature requests, and implementation tasks.
Inline TODO comments have been removed from the codebase in favor of this central tracking.

**Last Updated:** 2026-01-26

---

## Critical / Security

### SECURITY-001: Verify REST API Rate Limiting
**Priority:** Critical
**Related File:** `inc/PageParts/RestEndpoints.php`

The public REST endpoints do not implement application-level rate limiting by design.
Server-level (nginx/WAF) rate limiting MUST be verified in production.

**Affected Endpoints:**
- `/wp-json/nok-2025-v1/posts/query`
- `/wp-json/nok-2025-v1/embed-page-part/`
- `/wp-json/nok-2025-v1/search/autocomplete`

**Required nginx configuration:**
```nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
location ~ /wp-json/nok-2025-v1/ {
    limit_req zone=api burst=20 nodelay;
}
```

**Verification Steps:**
1. Confirm nginx rate limiting is configured OR
2. Confirm WAF (Wordfence, Cloudflare, AWS WAF) is in place

---

### SECURITY-002: dangerouslySetInnerHTML Code Review Policy
**Priority:** Critical
**Related File:** `src/blocks/embed-nok-page-part/index.js`

The Gutenberg block uses `dangerouslySetInnerHTML` for admin-only UI rendering.
Current usage is safe (admin-controlled content only).

**Policy:** Any PR modifying this file MUST verify no user-submitted content is passed to innerHTML.
If user content is ever needed, implement DOMPurify sanitization first.

---

## High Priority

### HIGH-001: Cache Invalidation for Page Parts
**Priority:** High
**Related File:** `inc/PageParts/`

Implement cache invalidation when page parts are modified.
Critical for healthcare content where stale information can have material impact.

**Considerations:**
- Hook into `save_post_nok_page_part` action
- Clear relevant object cache keys
- Consider fragment caching with proper invalidation

---

### HIGH-002: SEO Integration
**Priority:** High

Enhance SEO integration for page parts system.

**Tasks:**
- Schema.org structured data for page parts
- Open Graph meta tag support
- XML sitemap generation

---

## Medium Priority

### MED-001: Add `tel` Field Type to MetaRegistry
**Priority:** Medium
**Related File:** `inc/PostMeta/MetaRegistry.php`, `inc/Theme.php:631`

The phone field for vestiging currently uses `type: 'text'` instead of proper `tel` type.

**Implementation Steps:**
1. Add `'tel'` case to `MetaRegistry::get_sanitize_callback()` - same as text
2. Add `'tel'` case to `MetaRegistry::get_rest_type()` - returns `'string'`
3. Add `'tel'` case to `MetaRegistry::get_default_value()` - returns `''`
4. Update `nok-post-meta-panel.js` to render `TextControl` with `type="tel"`
5. Change `Theme.php:631` from `'text'` to `'tel'`

**Benefits:**
- Mobile devices show numeric keyboard
- Better accessibility for screen readers
- HTML5 semantic correctness

---

### MED-002: Integrate Voorlichtingen Carousel into Vestiging Pages
**Priority:** Medium
**Related File:** `template-parts/post-parts/nok-vestiging-voorlichtingen.php`

The voorlichtingen carousel component is complete but not integrated into vestiging pages.

**Integration Options:**
1. Add to vestiging template layout via Customizer
2. Add to fallback template `template-parts/single-vestiging-content.php`

**Usage:**
```php
$theme->embed_post_part_template('nok-vestiging-voorlichtingen')
```

**Context:** See memory.md entry "2026-01-15 - Voorlichting-Vestiging Linkage System"

---

### MED-003: Usage Tracking System
**Priority:** Medium

Implement usage tracking for page parts to identify unused components.

---

## Low Priority / Enhancements

### LOW-001: Field Parser - Comma Support in Descriptions
**Priority:** Low
**Related File:** `inc/PageParts/FieldDefinitionParser.php`

Field definitions use commas as delimiters, which breaks when commas appear in description text.

**Current Syntax:**
```php
* - field_name:type!descr[Description text here]
```

**Proposed Solutions:**
1. Implement quoted string support: `!descr["Text with, comma"]`
2. Use alternative delimiter (e.g., `|` instead of `,`)

---

### LOW-002: Field Parser - Conflicting Options Handler
**Priority:** Low
**Related File:** `inc/PageParts/FieldDefinitionParser.php`

Add support for mutually exclusive field options.

**Use Case:** `full_section` and `narrow_section` checkboxes should be mutually exclusive.

**Proposed Syntax:**
```php
* - narrow_section:checkbox!disables(full_section)
```

**Behavior:** When `narrow_section` is checked, `full_section` is disabled and unchecked.

---

### LOW-003: Spacers/Gaps System Refactor
**Priority:** Low
**Related Files:** Various SCSS files

The spacing utility classes grew organically. Consider a unified spacing system.

**Current State:**
- `quart-flex-gap` added ad-hoc
- Multiple spacer implementations across SCSS files

**Proposed:** Create systematic spacing scale (e.g., 4px base unit with multipliers).

---

### LOW-004: Additional Field Types
**Priority:** Low
**Related File:** `inc/PostMeta/MetaRegistry.php`

**Requested Types:**
- Image gallery field
- Color picker field

---

### LOW-005: Bulk Operations for Page Parts
**Priority:** Low

Add bulk operations in admin for page parts (bulk edit, bulk delete, bulk duplicate).

---

### LOW-006: Partner Logo System
**Priority:** Low

Create a partner logo management system similar to the icon-selector.

---

## Deprecated APIs (Migration Tracking)

### DEP-001: Global Helper Functions
**Status:** Deprecated, still in use
**Related File:** `inc/Helpers.php:1870-1889`

| Deprecated Function | Replacement | Used In |
|---------------------|-------------|---------|
| `makeRandomString()` | `\NOK2025\V1\Helpers::makeRandomString()` | Unknown |
| `format_phone()` | `\NOK2025\V1\Helpers::format_phone()` | Templates |

**Migration:** Update call sites to use class methods, then remove global wrappers.

---

### DEP-002: FieldContext Legacy Methods
**Status:** Deprecated, NOT in use
**Related File:** `inc/PageParts/FieldContext.php:120-204`

| Deprecated Method | Replacement |
|-------------------|-------------|
| `get_esc_html($key)` | `$context->field->html()` |
| `get_esc_url($key)` | `$context->field->url()` |
| `get_link($key)` | `$context->field->link()` |
| `get_esc_attr($key)` | `$context->field->attr()` |

**Migration:** Safe to remove - no usage found in templates.

---

## Completed / Resolved

### ~~RESOLVED: post_select Field Implementation~~
**Resolved:** 2026-01-26
**Was In:** `inc/PageParts/TemplateRenderer.php:453`

The `post_select` field type is fully implemented in MetaRegistry.
Stale TODO comment removed.
