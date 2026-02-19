---
name: css-deduplication
description: Audit new component SCSS against the existing utility class and layout system to prevent CSS duplication. Run before writing or reviewing any new SCSS component.
allowed-tools: Read, Grep, Glob
user-invocable: true
---

# CSS Deduplication Audit

Before writing any new SCSS component, audit it against the existing utility classes, layout system, and section patterns. **New component SCSS should only contain styles that are truly unique to the component** — everything else should use existing classes in the HTML template.

## When to Run

- Before writing a new `_nok-*.scss` component file
- When reviewing SCSS that was just written
- When a component's template uses custom CSS for things that look like they already exist

## Audit Process

### Step 1: Read the component's template

Read the PHP template and the SCSS file side by side. For every CSS rule in the SCSS, ask: **does a utility class or existing pattern already do this?**

### Step 2: Check against the existing systems

For each property being set in the component SCSS, check these systems in order:

#### Section Structure (already handled by HTML patterns)
The standard section pattern handles container widths, padding, and margins:
```html
<nok-section class="[bg-color] [text-color]">
  <div class="nok-section__inner">
    <div class="nok-layout-grid nok-layout-grid__1-column">
      <!-- children get automatic gap spacing -->
    </div>
  </div>
</nok-section>
```
**Never redefine:** section padding, section max-width, section margins, gap between section children.

#### Typography (`nok-fs-*`, heading font)
| Class | Size | Common use |
|-------|------|------------|
| `nok-fs-1` | h6 (1rem) | Small text |
| `nok-fs-2` | h5 (1.25rem) | Secondary text, accordion titles |
| `nok-fs-3` | h4 (1.5rem) | Sub-section headings |
| `nok-fs-4` | h3 (1.75rem) | Card headings |
| `nok-fs-5` | h2 (2rem) | Medium section titles |
| `nok-fs-6` | h1 (2.5rem) | **Standard section title** |
| `nok-fs-7` | h1+ (3.125rem) | Giant headings |

All font sizes are **responsive** (fluid scaling via CSS custom properties).

**Never redefine in SCSS:** `font-size` on headings/titles — use `nok-fs-*` class instead.
**Never include** `@include nok-heading-font` on a section-level title — `<h2>` already inherits it, and `nok-fs-6` is the standard.

#### Opacity (`nok-alpha-*`)
Classes `nok-alpha-0` through `nok-alpha-10` set `--text-alpha-value`.

**Never redefine:** `opacity: 0.8` on descriptive text — use `nok-alpha-8` in HTML. Or consider whether the base styles already handle it (e.g. `<p>` inside a `nok-text-contrast` section already has appropriate contrast).

#### Spacing (`nok-m*-*`, `nok-p*-*`, `nok-gap-*`)

Fluid responsive spacing utilities exist for all directions:
- **Padding:** `nok-p-{size}`, `nok-pt-*`, `nok-pb-*`, `nok-px-*`, `nok-py-*`
- **Margin:** `nok-m-{size}`, `nok-mt-*`, `nok-mb-*`, `nok-mx-*`, `nok-my-*`
- **Gap:** `nok-gap-{size}`, `nok-grid-gap-*`, `nok-column-gap-*`

Sizes: `0`, `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl` (plus legacy `0_25`, `0_5`, `1`–`5`).

Breakpoint variants: `nok-mb-lg-2` (lg and up), `nok-mt-to-lg-0` (below lg).

**Never redefine:** `margin-bottom: var(--nok-space-lg)` — use `nok-mb-lg` class.

#### Layout (`nok-layout-grid`, `nok-layout-flex-*`)

Grid system with automatic gap and responsive column collapsing:
- `nok-layout-grid__1-column` — single column with gap (handles vertical spacing between children)
- `nok-layout-grid__2-column` — collapses to 1 on mobile
- `nok-layout-grid__3-column` — collapses to 2 then 1

**Never redefine:** vertical spacing between a section's title, description, and content — `nok-layout-grid__1-column` already provides gap.

#### Colors & Backgrounds
- **Backgrounds:** `nok-bg-darkerblue`, `nok-bg-white`, `nok-bg-body--darker`, etc.
- **Text:** `nok-text-contrast`, `nok-text-white`, `nok-text-darkerblue`, `nok-text-lightblue`
- **Background alpha:** `nok-bg-alpha-{0-10}`

**Never redefine** background or text colors in component SCSS when a class exists.

#### Alignment & Sizing
- `nok-align-items-{center|start|end|stretch}`
- `nok-align-self-{center|start|end|stretch}`
- `nok-justify-content-{center|start|end|stretch|space-between}`
- `nok-span-all-columns`, `nok-column-first-*`, `nok-column-last-*`
- `w-100`, `nok-h-100`

#### Borders & Effects
- `nok-rounded-border`, `nok-rounded-border-large`, `nok-rounded-border-x-large`
- `nok-subtle-shadow`

#### Visibility
- `nok-invisible-{breakpoint}`, `nok-invisible-to-{breakpoint}`

### Step 3: Apply the deduplication

For every redundant rule found, decide:

1. **Move to HTML template** — add the existing utility class to the element
2. **Remove from SCSS** — delete the now-redundant CSS rule
3. **Remove wrapper elements** — if a wrapper `<div>` existed only to group elements for custom CSS spacing, and the layout grid now handles it, remove the wrapper

### Step 4: Validate what remains

After deduplication, the component SCSS should only contain:
- **Unique component layout** (e.g. a 3-column grid for download items with icon/info/action)
- **Unique interactive states** (hover backgrounds, focus rings specific to this component's interaction model)
- **Unique visual treatment** (translucent backgrounds, text overflow ellipsis, custom transitions)
- **BEM children** that have no utility class equivalent (internal component structure)

If the SCSS still contains any of these, it's a duplication smell:
- `font-size` on a heading → use `nok-fs-*`
- `margin` or `padding` with spacing token values → use `nok-m*-*` / `nok-p*-*`
- `opacity` on text → use `nok-alpha-*`
- `display: grid` + `gap` for vertical flow → use `nok-layout-grid__1-column`
- `@include nok-heading-font` on a section title → `<h2>` inherits it
- wrapper divs with only `margin-bottom` → remove wrapper, use grid gap

## Reference: Standard Section Title Pattern

This is the canonical pattern used by ~15+ page-part and block-part templates. Follow it exactly:

```html
<nok-section class="nok-bg-darkerblue nok-text-contrast">
  <div class="nok-section__inner">
    <div class="nok-layout-grid nok-layout-grid__1-column">
      <h2 class="nok-fs-6"><?= $title ?></h2>
      <p><?= $description ?></p>
      <!-- component-specific content here -->
    </div>
  </div>
</nok-section>
```

**What this gives you for free:**
- Section background and text color
- Container max-width and responsive padding
- Vertical spacing between title, description, and content (grid gap)
- Responsive heading size
- Proper heading font

**What you still need component SCSS for:**
- The inner component layout (e.g. download item rows, card grids, carousel slides)
- Component-specific interactive states
- Component-specific visual treatment