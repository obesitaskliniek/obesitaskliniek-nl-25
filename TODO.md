# Project TODO Tracker

This file centralizes all technical debt, feature requests, and implementation tasks.
Inline TODO comments have been removed from the codebase in favor of this central tracking.

**Last Updated:** 2026-02-11
**Last Audit:** 2026-02-11 (comprehensive codebase-wide technical debt audit)

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

### SECURITY-003: CSP in Report-Only Mode
**Priority:** Critical
**Status:** Review Required
**Related File:** `inc/Helpers.php` (lines 1755-1872)

Content-Security-Policy is currently in **Report-Only** mode with permissive directives
(`unsafe-eval`, `unsafe-inline`). While this is documented as necessary for WordPress/Gutenberg
compatibility, the `X-Frame-Options: Allow` header is overly permissive.

**Issues:**
- CSP Report-Only does not enforce protections
- `unsafe-eval` and `unsafe-inline` negate most XSS protection
- `X-Frame-Options: Allow` permits clickjacking

**Recommendation:** Review whether stricter CSP directives can be enforced in production
while keeping Report-Only for the admin dashboard.

---

## High Priority

### HIGH-003: ContactForm DEBUG_MODE Hardcoded to TRUE
**Priority:** High
**Status:** Action Required
**Related File:** `inc/ContactForm.php` (line 27)

`DEBUG_MODE` is hardcoded to `true`, routing **all** contact form emails to `it@obesitaskliniek.nl`
instead of the intended vestiging recipients. This affects production functionality.

```php
private const DEBUG_MODE = true;  // Should be null (follow Theme::is_development_mode())
private const DEBUG_EMAIL = 'it@obesitaskliniek.nl';
```

**Fix:** Set `DEBUG_MODE = null` to follow the theme-wide development mode flag.

---

### HIGH-004: Hardcoded Development URL in functions.php
**Priority:** High
**Status:** Action Required
**Related File:** `functions.php` (lines 64-65)

Site configuration is hardcoded to development values:

```php
define( 'SITE_BASE_URI', 'https://dev.obesitaskliniek.nl');
define( 'SITE_LIVE', false);
```

**Issues:**
- Requires manual code changes for production deployment
- No `.env` or environment-based configuration

**Recommendation:** Use `wp-config.php` constants or environment variables to derive these values.

---

### HIGH-005: No Testing Infrastructure
**Priority:** High
**Status:** Not Started
**Related Files:** `package.json`, `composer.json`

The project has **zero automated tests** across 103 PHP files and multiple complex Gutenberg blocks.

**Missing:**
- No PHPUnit configuration or PHP test files
- No Jest/Vitest configuration or JS test files
- No `test` script in `package.json`
- No CI/CD pipeline to enforce quality

**Recommendation:** Start with unit tests for the most critical/complex systems:
1. `inc/PageParts/Registry.php` - field definition parsing
2. `inc/PageParts/FieldContext.php` - field value access
3. `inc/ContactForm.php` - email routing logic
4. `src/blocks/embed-nok-page-part/index.js` - block state management

---

### HIGH-006: No Linting or Formatting Configuration
**Priority:** High
**Status:** Not Started

No ESLint, Prettier, PHPStan, or EditorConfig found in the project.

**Impact:**
- No automated code quality enforcement
- Inconsistent code style across files
- No pre-commit hooks prevent regressions

**Recommendation:**
1. Add ESLint + Prettier for JavaScript
2. Add PHPStan or Psalm for PHP static analysis
3. Add `.editorconfig` for consistent whitespace
4. Add Husky + lint-staged for pre-commit hooks

---

### HIGH-007: Platform-Specific Build Script
**Priority:** High
**Status:** Action Required
**Related File:** `package.json`

The `postbuild` script uses Windows-specific `xcopy` command:

```json
"postbuild": "xcopy build\\*.* . /E /H /Y"
```

This fails on Linux/macOS. Should use a cross-platform alternative like `cpx`, `cpy-cli`, or
a Node.js script.

---

## Medium Priority

### MED-003: Usage Tracking System
**Priority:** Medium

Implement usage tracking for page parts to identify unused components.

---

### MED-004: Large JavaScript Files Need Splitting
**Priority:** Medium
**Related Files:**
- `src/nok-page-part-design-selector.js` (915 lines)
- `src/blocks/embed-nok-page-part/index.js` (875 lines)

These files contain multiple component definitions, state management, and rendering logic
in single files. They should be split into smaller, focused modules for maintainability.

**Recommendation:** Extract into:
- Component files (one per React component)
- Utility/helper files
- Constants files

---

### MED-005: Error Suppression with @ Operator in PHP
**Priority:** Medium
**Related File:** `inc/BlockRenderers.php` (lines 58, 72, 112, 156, 202, 252)

6 instances of `@$dom->loadHTML(...)` suppress XML parsing errors. Also 1 instance
in `inc/Assets.php` (line 200): `@$dom->loadXML($svg)`.

**Recommendation:** Replace with `libxml_use_internal_errors(true)` and explicit error handling:

```php
libxml_use_internal_errors(true);
$dom->loadHTML($html);
$errors = libxml_get_errors();
libxml_clear_errors();
```

---

### MED-006: Console Statements in Production JavaScript
**Priority:** Medium
**Related Files:**
- `src/yoast-page-parts-integration.js` - 8 console.log/warn statements
- `src/blocks/embed-nok-page-part/index.js` - 2 console.log statements
- `src/nok-page-part-design-selector.js` - 2 console.error statements

Most are conditional on debug flags, but some (`console.log` in embed block, `console.error`
in design selector) are unconditional.

**Recommendation:** Remove all console statements or replace with a proper logging utility
that is stripped in production builds via webpack.

---

### MED-007: Empty/Minimal Catch Blocks in JavaScript
**Priority:** Medium
**Related Files:**
- `src/nok-page-part-design-selector.js` (lines 61, 211, 225) - `catch { return []; }`
- `src/blocks/embed-nok-page-part/index.js` (lines 410, 433) - `catch (e) { // ignore }`
- `src/nok-page-part-preview.js` (line 46) - `catch (e) { // ignore }`

Silent error swallowing hides potential bugs. Even if the error is expected (cross-origin),
it should at least log in development mode.

---

### MED-008: Global Window Object State Management
**Priority:** Medium
**Related Files:**
- `src/nok-page-part-preview.js` - sets `window.nokUpdatePreview`
- `src/yoast-page-parts-integration.js` - uses `window.nokPagePartData`
- `src/nok-page-part-design-selector.js` - reads `window.PagePartDesignSettings`
- `src/nok-post-meta-panel.js` - reads `window.nokPostMetaFields`
- `src/nok-button-extension.js` - reads `window.nokButtonIcons`

20+ global `window.*` accesses create implicit dependencies between modules.

**Recommendation:** Centralize through a configuration module or use `wp.data` stores
for shared state.

---

### MED-009: PHP Version Mismatch Between Config Files
**Priority:** Medium
**Related Files:** `style.css`, `composer.json`

- `style.css` declares `Requires PHP: 8.0`
- `composer.json` declares `"php": ">=8.1"`

These should be aligned to avoid confusion about the actual minimum requirement.

---

### MED-010: Long PHP Methods Needing Refactoring
**Priority:** Medium
**Related Files:**
- `inc/PageParts/Registry.php` (lines 492-825) - `parse_custom_fields()` is 333 lines
- `inc/PostMeta/MetaRegistrar.php` (lines 33-165) - 132 lines in single method
- `inc/PageParts/RestEndpoints.php` (lines 271-374) - 103 lines

**Recommendation:** Break into smaller, focused methods. The field parser in particular
should be extracted into a dedicated `FieldDefinitionParser` class (already referenced
in LOW-001 and LOW-002).

---

### MED-011: Inefficient JSON.stringify Equality Checks
**Priority:** Medium
**Related Files:**
- `src/nok-page-part-preview.js` (line 247) - `JSON.stringify(meta) !== JSON.stringify(lastMeta)`
- `src/yoast-page-parts-integration.js` (line 286) - `JSON.stringify(currentState) !== JSON.stringify(prevState)`

Using `JSON.stringify` for deep equality comparison is O(n) serialization on every check.

**Recommendation:** Use a shallow/deep equality utility (e.g., `lodash.isEqual` or `@wordpress/is-shallow-equal`).

---

### MED-012: Hardcoded Asset URLs as Fallbacks
**Priority:** Medium
**Related File:** `inc/Helpers.php` (lines 111, 195-199)

Multiple hardcoded external URLs for fallback images:
```php
'https://assets.obesitaskliniek.nl/files/2025_fotos/...'
```

If the external CDN changes paths, these fallbacks break silently.

**Recommendation:** Move to theme options or a constants file for centralized management.

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

### LOW-007: Hardcoded Pixel Values in Accessibility Helper SCSS
**Priority:** Low
**Related File:** `assets/css/components/_nok-accessibility-helper.scss` (lines 8-55)

Multiple hardcoded pixel values (`16px`, `32px`, `20px`) that should use the spacing
system CSS variables (`--nok-space-*`).

---

### LOW-008: Z-Index Magic Number in Datepicker
**Priority:** Low
**Related File:** `assets/css/components/_nok-datepicker.scss` (line 70)

Hardcoded `z-index: 1000` instead of using the centralized `$z-stack-top` variable
from `_nok-variables.scss`.

---

### LOW-009: Commented-Out SCSS Code Blocks
**Priority:** Low
**Related Files:**
- `assets/css/_nok-helpers.scss` (lines 295-299, 330-340) - disabled utility generation
- `assets/css/_nok-layout.scss` (lines 20-22, 54, 74, 99-113) - disabled layout patterns
- `assets/css/components/_nok-square-block.scss` (lines 9-18, 23, 91)

Development artifacts that should be cleaned up or removed.

---

### LOW-010: Missing Return Type Declarations in PHP
**Priority:** Low
**Related Files:**
- `inc/Helpers.php` - Several parameters without type hints (`$post`, `$field`)
- `inc/BlockRenderers.php` (line 153) - `render_heading_block()` missing return type

WordPress hook compatibility requires some untyped parameters, but return types can be added.

---

### LOW-011: Google Fonts Loaded via Hardcoded URLs
**Priority:** Low
**Related Files:**
- `header.php` (lines 9-11)
- `inc/PageParts/RestEndpoints.php` (lines 975-977)

Google Fonts URLs are hardcoded in templates and REST responses. Consider registering
via `wp_enqueue_style()` for proper dependency management.

---

### LOW-012: No Docker or Containerization Setup
**Priority:** Low

No Dockerfile, docker-compose.yml, or container configuration exists.
Manual server setup required for each environment.

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
