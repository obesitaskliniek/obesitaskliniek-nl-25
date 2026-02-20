# Project TODO Tracker

This file centralizes all technical debt, feature requests, and implementation tasks.
Inline TODO comments have been removed from the codebase in favor of this central tracking.

**Last Updated:** 2026-01-26

---

## Critical / Security

### SECURITY-001: Verify REST API Rate Limiting
**Priority:** Critical
**Status:** Verification Required
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
**Status:** Policy (No Code Changes Needed)
**Related File:** `src/blocks/embed-nok-page-part/index.js`

The Gutenberg block uses `dangerouslySetInnerHTML` for admin-only UI rendering.
Current usage is safe (admin-controlled content only).

**Policy:** Any PR modifying this file MUST verify no user-submitted content is passed to innerHTML.
If user content is ever needed, implement DOMPurify sanitization first.

---

## Medium Priority

### MED-003: Usage Tracking System
**Priority:** Medium

Implement usage tracking for page parts to identify unused components. For example, to find page parts that are orphaned (defined as posts but not used in any page).

---

## Low Priority / Enhancements

### ~~RESOLVED: LOW-001: Field Parser - Comma Support in Descriptions~~
**Resolved:** 2026-02-16
**Implementation:** `inc/PageParts/Registry.php`

Updated the `preg_split` regex in `parse_custom_fields()` to also skip commas
inside square brackets. The regex changed from `/,(?![^\(]*\))/` to
`/,(?![^\(]*\))(?![^\[]*\])/`, which protects commas in `!descr[text with, commas]`
from being treated as field delimiters.

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

## Completed / Resolved

### ~~RESOLVED: HIGH-001: Cache Invalidation for Page Parts~~
**Resolved:** 2026-01-26
**Implementation:** `inc/PageParts/Registry.php`

Added cache invalidation hooks:
- `save_post_page_part` - clears cache when page part is saved
- `before_delete_post` - clears cache when page part is deleted
- `updated_post_meta` / `added_post_meta` - clears cache when design_slug changes

---

### ~~RESOLVED: HIGH-002: SEO Integration~~
**Resolved:** 2026-01-26
**Implementation:** `inc/SEO/PagePartSchema.php`

Added Schema.org structured data support:
- FAQPage schema for FAQ page parts
- MedicalOrganization schema for team sections
- MedicalClinic schema for vestiging (location) sections
- EducationEvent schema for voorlichting sections
- Hook `nok_page_part_rendered` fires after page part rendering for schema collection

---

### ~~RESOLVED: MED-001: Add `tel` Field Type to MetaRegistry~~
**Resolved:** 2026-01-26
**Implementation:** `inc/PostMeta/MetaRegistry.php`, `src/nok-post-meta-panel.js`, `inc/Theme.php`

Added `tel` field type:
- Sanitization: `sanitize_text_field`
- REST type: `string`
- Default: `''`
- UI: TextControl with `type="tel"` (mobile numeric keyboard)
- Updated vestiging phone field to use `tel` type

---

### ~~RESOLVED: MED-002: Integrate Voorlichtingen Carousel into Vestiging Pages~~
**Resolved:** 2026-01-26
**Implementation:** `template-parts/single-vestiging-content.php`

Integrated voorlichtingen carousel into vestiging pages:
- Added `get_template_part('template-parts/post-parts/nok-vestiging-voorlichtingen')`
- Carousel auto-detects city from vestiging title
- Shows upcoming voorlichtingen for that location

---

### ~~RESOLVED: DEP-001: Global Helper Functions~~
**Resolved:** 2026-01-26
**Was In:** `inc/Helpers.php:1870-1889`

Removed deprecated global wrapper functions:
- `makeRandomString()` - all usages already use `Helpers::makeRandomString()`
- `format_phone()` - all usages already use `Helpers::format_phone()`

---

### ~~RESOLVED: DEP-002: FieldContext Legacy Methods~~
**Resolved:** 2026-01-26
**Was In:** `inc/PageParts/FieldContext.php:120-204`

Removed deprecated methods (no usage found):
- `get_esc_html()` - use `$context->field->html()`
- `get_esc_url()` - use `$context->field->url()`
- `get_link()` - use `$context->field->link()`
- `get_esc_attr()` - use `$context->field->attr()`

---

### ~~RESOLVED: post_select Field Implementation~~
**Resolved:** 2026-01-26
**Was In:** `inc/PageParts/TemplateRenderer.php:453`

The `post_select` field type is fully implemented in MetaRegistry.
Stale TODO comment removed.
