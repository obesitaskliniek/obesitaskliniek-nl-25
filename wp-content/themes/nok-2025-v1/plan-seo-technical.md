# SEO Technical Remediation Plan

Source: SEO audit 2026-03-05 (`website_check.txt` + `SEO_NOK_Website Check_05032026.xlsx`).

Ordered by leverage: template-level fixes first (one change → many rows fixed), then per-page work.

**Scope note:** `/__designs/` (the page-showcase template) is out of scope for all
fixes in this plan. The page is correctly set to `noindex, nofollow` and excluded
from Yoast sitemaps via `YoastIntegration.php` (`noindex_showcase_page` +
`exclude_showcase_from_sitemap`), so issues reported *on* or *from* this page
don't affect Google's index. Screaming Frog still crawls it because noindex
doesn't prevent crawling — this is audit noise, not a real SEO problem. Ignore
any audit entries where the source or destination is `/__designs/`.

---

## Phase 1 — Template-level fixes (highest leverage)

### 1.1 Yoast title template: shorten suffix [IMPLEMENTED]
**Problem:** 552 pages have over-long `<title>` (e.g. `"…" - Nederlandse Obesitas Kliniek` = 66+ chars, 579+px).
**Action:** In Yoast → Search Appearance, change title template suffix from `- Nederlandse Obesitas Kliniek` to `- NOK` (or drop suffix on long titles). Apply per post type: `post`, `ervaringen`, `kennisbank`, `voorlichting`, `vestiging`.
**Verification:** Re-crawl with Screaming Frog → `< 60 chars / < 580px` for ≥95% of URLs.
**Files:** Yoast admin only (no theme code). Document the chosen templates in `inc/SEO/YoastIntegration.php` header comment for traceability.

### 1.2 Yoast meta description template: vestiging boilerplate [IMPLEMENTED]
**Problem:** 570 pages have over-long meta descriptions; vestiging pages share a ~350-char boilerplate.
**Action:** Rewrite the vestiging default meta description template in Yoast to ≤155 chars. Example:
> `Obesitas kliniek in %%regio%%. Behandeling van ernstig overgewicht, vergoed door zorgverzekering. Maak een afspraak bij NOK %%vestiging%%.`
**Verification:** Spot-check 5 vestiging pages, confirm length.
**Files:** Yoast admin, plus update any hardcoded description defaults in `inc/SEO/YoastIntegration.php` if present.

### 1.3 Auto-generated meta descriptions for kennisbank [IMPLEMENTED]
**Problem:** 25+ kennisbank pagination + single URLs missing meta description.
**Action:**
- For `/kennisbank/page/N/` archive pagination → emit template-level meta description in `archive-kennisbank.php` or via `wpseo_metadesc` filter.
- For kennisbank singles → derive from excerpt or first paragraph via `wpseo_metadesc` filter.
**Files:**
- `inc/SEO/YoastIntegration.php` — add `wpseo_metadesc` filter for CPT `kennisbank` and paged archive
- Test locally against REST API + live re-crawl

### 1.4 Pagination overshoot bug (15× broken links) [IMPLEMENTED]
**Problem:** 15 internal links point to `/nieuws/page/15/` which 404s (beyond last page).
**Action:**
- Locate the pagination render (likely `archive.php` / `archive-nieuws` / shared pagination partial).
- Clamp `max_num_pages` so links beyond the last page are never emitted.
- Audit any hardcoded "last page" links in page parts.
**Files:** Grep for `page/15` and `paginate_links` in theme. Fix root in the pagination helper.
**Verification:** Manually click "next" until end; confirm no link beyond final page.

### 1.5 Missing H1 template fix (`symptomen-en-gevolgen-obesitas` subtree) [SKIPPED]
**Note (2026-04-15, after investigation):** The plan's premise was wrong. `nok-hero.php:43`
already renders the page title as `<h1 class="nok-fs-giant">`. The 10 affected pages
are missing their hero page part entirely — they embed only `nok-header-main` (site
navigation) + content blocks, so no h1 is ever rendered. This is true for all 10
pages in the audit, not just the 7 in the `symptomen-en-gevolgen-obesitas/*` subtree
(verified on `/advies-over-obesitas/symptomen-en-gevolgen-obesitas/diabetes/`).

**Fix is editorial, not code:** Add a `nok-hero` page part as the first block on
each of the 10 affected pages via the WP admin. No template change required.

**Original plan (not applicable):**
- Identify the page-part template used by these pages (likely a shared hero/intro page part that renders `<h2>` instead of `<h1>`).
- Ensure the first heading on a page is always `<h1>` via the hero page part (conditional: if primary hero, output `h1`).
- Verify via `view-source` on affected pages.

**Files (none — editorial task):**
- `template-parts/page-parts/nok-hero.php` (verified correct, no change needed)

### 1.6 Remove redirect chains (update source links) [IMPLEMENTED]
**Note (2026-04-15):** Root cause was WordPress's `redirect_guess_404_permalink()`
rescuing would-be 404s via `page_part` slug matching, creating 2-hop chains.
Fixed via two code changes in `inc/PostTypes.php`:
1. `page_part` CPT: `publicly_queryable => is_user_logged_in()` and `exclude_from_search => true`
2. New `redirect_canonical` filter (`block_page_part_canonical_guess`) as defensive layer.

Editorial work still required: update content links on source pages to target
final URLs directly (3 remaining after /congres/ was fixed on the nieuws post):
- `/behandeling/de-effecten-van-bariatrische-chirurgie/` — change psycholoog link to `/behandeling/behandelteam/psycholoog/`
- `/__designs/` — same psycholoog link
- `/resultaten/` — change OBESI-Q link to the direct PDF URL `/wp-content/uploads/2026/02/OBESI-Q-versie-2.0-definitief-NL.pdf`

**Deploy note:** Flush permalinks (Settings → Permalinks → Save) after deploy
to clear the cached rewrite rule for `/page-part/(.+)`.
**Problem:** 16 links chain through `/page-part/*` → final URL.
**Action:** Bulk search-replace in `wp_posts` (content) and in page-part meta for the old `/page-part/*` URLs, pointing to the final 200 URLs directly. List from audit:
- `/page-part/psycholoog/` → `/behandeling/behandelteam/psycholoog/`
- (extract all 16 from the spreadsheet's `Redirect URL 1` column → `Final URL` column)
**Files:** WP-CLI `search-replace` or SQL migration on `wp_posts.post_content` and `wp_postmeta.meta_value`.
**Verification:** Re-crawl, confirm 0 redirect chains in the new report.

### 1.7 Noindex policy pages [SKIPPED]
**Note (2026-04-15):** Declined after consideration. The audit's recommendation
applies a generic SEO default regardless of vertical. For a YMYL (Your Money or
Your Life) healthcare site like NOK, the reasoning runs the other way:

- Patients and regulators actively search for branded queries like
  "NOK klachtenregeling" or "Obesitaskliniek privacy". Noindexing removes those
  pages from branded SERPs — a trust/findability regression.
- Visible, easily-found policy pages are an E-E-A-T trust signal for YMYL content.
- The crawl-budget and thin-content arguments cited by the audit don't
  meaningfully apply at this site's URL count.

Keeping `/klachten/`, `/privacyverklaring/`, and cookie pages indexable is the
right call for this specific context. Revisit only if a page starts ranking
inappropriately for a non-branded query.

**Original plan:**
- Problem: Cookie-, privacy-, klachten-pages are being indexed.
- Action: Set `noindex` via Yoast per-page meta, OR programmatic filter in `inc/SEO/YoastIntegration.php`.
- Files: `inc/SEO/YoastIntegration.php` — hook `wpseo_robots` or `wp_robots`.

---

## Phase 2 — 4xx inlinks cleanup (34 broken links)

### 2.1 High-volume fixes [IMPLEMENTED]
All editorial edits completed 2026-04-15:
- `/www.mca.nl` (1×) — fixed.
- `/verwijzers/` → `/behandelprogramma` ("Meer over het behandelprogramma") — repointed to `/behandeling/`.
- `/advies-over-obesitas/symptomen-en-gevolgen-obesitas/slaapapneu-en-overgewicht/` → `/behandeling/de-operatie/gastric-band-maagband/` ("Gastric Band") — repointed to `/behandeling/de-operatie/` (no dedicated gastric band page).
- `/verwijzers/over-de-behandeling/` → same gastric-band link — same fix.
- `/nieuws/afvallen-zonder-operatie/` → `/behandeling/behandeling-zonder-operatie/medicamenteuze-behandeling/` ("behandelingen met medicatie") — repointed to `/kennisbank/blogs/medicatie-als-behandeling-bij-obesitas-wat-zijn-de-opties-zonder-operatie/`.
- `/ervaringen/twintig-kilo-kwijt-in-een-half-jaar/` → same medicamenteuze link — same fix.

`/__designs/`-sourced entries excluded per the scope note at the top of this plan.

### 2.2 Attachment/upload 404s (~8 entries) [IMPLEMENTED]
Editorial edits completed 2026-04-15 (7 of 8):
- #1 `/resultaten/` preprocessor-URL image — fixed.
- #2 `/resultaten/` `/wp-content/uploads/2024/10/kwaliteit-van-leven.png` — **NOT fixed**;
  source image file could not be located. Revisit when asset is available, or
  remove the `<img>` placeholder from the page.
- #3 `/nieuws/beeindiging-samenwerking-oca-nok/` — broken link removed.
- #4 `/nieuws/ons-beleid-inzake-het-coronavirus-covid-19/` — broken link removed.
- #5 `/nieuws/in-een-dag-thuis-na-maagverkleining/` — missing picture re-added.
- #6 `/ervaringen/ik-was-op-vakantie-en-dacht-dit-moet-anders/` — fixed.
- #7 `/nieuws/afvallen-zonder-operatie/` — fixed.
- #8 `/nieuws/maagballon-maagplooien-en-medicijnen-tegen-overgewicht/` — fixed.

**Follow-up:** TODO added at top of `assets/js/nok-lightbox.mjs` — extend the
lightbox module to work on regular Gutenberg image blocks in post_content
(not just the `nok-small-picture-text-block` page part).

### 2.3 Tooling [SKIPPED]
**Note (2026-04-16):** Already covered by the Redirection plugin
(https://wordpress.org/plugins/redirection/), which logs 404s and tracks
redirect hits. No custom tooling needed.

---

## Phase 3 — Images

### 3.1 Heavy images (39 > 100KB)
**Action:**
- Most are served from `/wp-content/uploads/` directly. Route through `assets.obesitaskliniek.nl` image preprocessor (per CLAUDE.md) with quality/size params.
- For the vestiging gallery (`/vestigingen/`) — ensure thumbnails use `srcset` with appropriately sized variants, not raw 1024px files.
- For hero images that can't be moved, compress via `squoosh`/`sharp` (MozJPEG q75, or AVIF/WebP).
**Files:**
- `inc/Assets.php` / image-rendering helpers
- Page-part templates that output images — verify `srcset`/`sizes`

### 3.2 Missing alt text (99 entries)
**Pattern:** Many references to the same stock photo: `NOK Stockfotos 2025 - 05-12-2024 - 45:100x0-25-0-0-center-0.jpg`.
**Action:**
- Add alt text once in Media Library → propagates to every `wp_get_attachment_image()` call.
- For images embedded via page-part fields (where alt may be overridden), ensure the `alt` field falls back to the attachment's alt text when empty.
**Files:**
- `inc/PageParts/FieldContext.php` / image field renderer — verify fallback chain

---

## Phase 4 — Verification & monitoring

1. Re-crawl with Screaming Frog after Phase 1 completes; confirm ≥80% reduction in each category.
2. Add a small WP admin dashboard widget (future work) showing: count of missing H1 / missing meta desc / 4xx links — live from a nightly cron.
3. Capture new Lighthouse baseline (CLS/LCP) to confirm image compression didn't regress layout.

---

## Risk & sequencing notes

- **Yoast template changes (1.1, 1.2)** are the single highest-leverage fix (~1,100 rows). Do first, verify, then move on.
- **Search-replace for redirect chains (1.6)** mutates the database. Back up first, run on staging, diff before applying to prod.
- **Pagination bug (1.4)** likely a one-line fix once located; investigate with high priority since broken pagination hurts crawl budget.
- Do NOT batch Phase 1 into one PR — each fix should be verifiable independently.
