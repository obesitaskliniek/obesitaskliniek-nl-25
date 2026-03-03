# Project TODO Tracker

This file centralizes all technical debt, feature requests, and implementation tasks.
Inline TODO comments have been removed from the codebase in favor of this central tracking.

**Last Updated:** 2026-03-03

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

### ~~RESOLVED: HIGH-003: Critical CSS System — Full Revision~~
**Resolved:** 2026-02-23
**Implementation:** `scripts/extract-atf-css.mjs`, `scripts/atf-selectors.config.mjs`, `header.php`, `inc/Core/AssetManager.php`

Automated ATF/BTF extraction pipeline fully implemented and deployed to production:
- PostCSS-based splitting of `nok-components.css` into ATF (inlined) + BTF (deferred) bundles
- Config-driven selector classification (includes/excludes/boundary patterns)
- Intelligent CSS variable pruning (only referenced `:root` vars in ATF)
- Validation checks (declaration count integrity, critical token coverage, ~65KB size budget)
- Legacy `nok-critical.scss` retained for rollback via `?legacy-css` query param
- Verification URLs: `?critical-css-only`, `?legacy-css`, `?debug`

Replaces manual `nok-critical.scss` duplication with deterministic extraction from component SCSS source of truth.

---

## Medium Priority

### MED-006: Archive Page Hero Alignment
**Priority:** Medium
**Related Files:** `archive-kennisbank.php`, `archive-vestiging.php`, `archive-voorlichting.php`

The `<nok-hero>` section on archive pages has alignment issues. All three archives use the same
`nok-hero__inner` class structure — investigate whether the problem is shared or archive-specific,
and whether it's a CSS issue (e.g. missing `display` on the custom element) or a markup difference
compared to single-page heroes.

---

### MED-007: CSS Cache Strategy — Longer Lifetime with Reliable Invalidation
**Priority:** Medium
**Related File:** `inc/Core/AssetManager.php`

Browser cache lifetime for CSS assets should be extended for better performance, but visitors
must receive updated styles when CSS changes. Current state: `AssetManager` uses `filemtime`-based
`?ver=` query strings for cache busting, but `Cache-Control` headers (set at server level) may be
too short or too long.

**Options to evaluate:**
1. **Content-hashed filenames** (e.g. `nok-components.a3f8b2.css`) — immutable caching with
   `Cache-Control: max-age=31536000`. Requires build pipeline changes to generate hashes and
   update references.
2. **Long `max-age` + `?ver=` query string** (current approach, just needs server config) —
   set `Cache-Control: max-age=2592000` (30 days) for `.css` files in nginx. The `?ver={timestamp}`
   already invalidates on file change. Verify CDN (if any) respects query strings.
3. **`stale-while-revalidate`** — add `Cache-Control: max-age=86400, stale-while-revalidate=2592000`
   to serve stale CSS while revalidating in background.

**Decision needed:** Which approach fits the hosting setup (nginx, CDN, deployment process)?

### MED-004: Portrait Carousel Repeater Field
**Priority:** Medium
**Related File:** `template-parts/page-parts/nok-portrait-carousel.php`

The `team_members:repeater` field is declared in the Custom Fields docblock but not used in the template.
Currently the carousel reads portrait images directly from the filesystem (`assets/img/people/`) and assigns
random specialist titles. The repeater should be implemented to allow CMS-controlled team member data.

---

### MED-005: Desktop Dropdown CTA — Make Dynamic
**Priority:** Medium
**Related File:** `template-parts/navigation/desktop-dropdown.php`

The CTA block at the bottom of the desktop dropdown menu ("Vragen, of behoefte aan persoonlijk advies?")
has a hardcoded heading, button text, and URL (`/contact/`). This should be driven by a theme setting
or menu location so editors can update it without code changes.

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

### LOW-004: Image Gallery Field Type
**Priority:** Low
**Related File:** `inc/PostMeta/MetaRegistry.php`

**Requested Types:**
- Image gallery field (multi-select from WordPress media library)
- ~~Color picker field~~ — Implemented as `color-selector(palette)` in `Registry.php`

---

### LOW-005: Bulk Operations for Page Parts
**Priority:** Low

Add bulk operations in admin for page parts (bulk edit, bulk delete, bulk duplicate).

---

### LOW-006: Partner Logo System
**Priority:** Low

Create a partner logo management system similar to the icon-selector.

---

### LOW-007: Ervaringen Text Block — CTA Button
**Priority:** Low
**Related File:** `template-parts/page-parts/nok-ervaringen-text-block.php`

Add an optional button field to link to the testimonials archive ("Alle ervaringen").
Currently the section shows quotes but has no call-to-action linking to the full overview.

---

### LOW-008: Menu Carousel — Popup Link Support
**Priority:** Low
**Related File:** `assets/js/nok-menu-carousel.mjs`

The carousel's `setupSlide()` function only handles `a.nok-nav-menu-item` links with `#` hrefs
pointing to in-page targets. Popup trigger links (which also use `href="#"` but with
`data-toggles-class` attributes) are not recognized and generate console warnings.

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

### ~~RESOLVED: MED-003: Usage Tracking System~~
**Resolved:** 2026-02-23
**Implementation:** `inc/PageParts/MetaManager.php`

Implemented usage tracking for page parts:
- `get_page_part_usage()` queries which pages embed a given page part
- AJAX handler with nonce verification for admin UI
- Admin column showing usage count
- JS warning script when deleting page parts that are in use

---

### ~~RESOLVED: LOW-003: Spacers/Gaps System Refactor~~
**Resolved:** 2026-02-23
**Implementation:** `assets/css/_nok-spacing.scss`

Unified spacing system implemented with semantic scale (`xs` through `4xl`),
both static (`--nok-space-md`) and fluid (`--nok-space-fluid-md`) CSS custom properties,
and utility classes. Legacy numeric keys (`0_25`, `0_5`, `1`–`5`) mapped to semantic equivalents.

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
