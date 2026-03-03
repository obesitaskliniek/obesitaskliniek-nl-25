/**
 * ATF (Above-The-Fold) CSS Extraction Configuration
 *
 * Controls how extract-atf-css.mjs splits nok-components.css into:
 * - nok-atf.css  → inlined in <head> for instant first paint
 * - nok-btf.css  → loaded deferred via media="print" onload
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │                       DECISION FLOW                                │
 * │                                                                    │
 * │  For each CSS selector in nok-components.css:                      │
 * │                                                                    │
 * │  1. Does it match an EXCLUSION pattern?          → BTF (override)  │
 * │  2. Does it match a REBOOT selector? (exact)     → ATF             │
 * │  3. Does it match a SUBSTRING token?             → ATF             │
 * │  4. Does it match a BOUNDARY token?              → ATF             │
 * │  5. None of the above?                           → BTF (default)   │
 * │                                                                    │
 * │  Exclusions WIN over inclusions. A selector matching both          │
 * │  ".nok-button" (include) and ".nok-button--small" (exclude)        │
 * │  goes to BTF.                                                      │
 * └─────────────────────────────────────────────────────────────────────┘
 *
 * Special handling (not controlled by selectors):
 * - :root blocks   → duplicated to both, then ATF copy is pruned to
 *                    only variables referenced by ATF rules
 * - @font-face     → ATF only (from nok-atf-reboot.css prepend)
 * - @keyframes     → BTF (unless listed in options.atfKeyframes)
 * - Comments       → stripped (except license comments /*!)
 *
 * To audit: node scripts/extract-atf-css.mjs --dry-run --verbose
 */


// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  INCLUDE — Substring tokens                                         ║
// ║  Match: token appears ANYWHERE in the selector string               ║
// ║  Use for: custom elements and BEM families where all children,      ║
// ║           modifiers, and descendants should be included              ║
// ║  Example: 'nok-hero' matches nok-hero, .nok-hero__inner,            ║
// ║           nok-hero.nok-hero--fullscreen .nok-hero-fullscreen__title  ║
// ╚═══════════════════════════════════════════════════════════════════════╝
const atfSubstringTokens = [
    // Navigation — only the visible top bar and its children.
    'nok-top-navigation',
    'nok-navigation-menu-bar',
    'nok-navigation-top-row',
    'nok-navigation-mobile',
    'nok-navigation-desktop',
    'nok-logo',

    // Navigation inner classes (BEM children of visible bar)
    '.nok-navigation-menu-bar__inner',
    '.nok-navigation-top-row__inner',

    // Navigation menu items (visible in bar; dropdown items excluded via nok-nav-menu-bar-dropdown)
    '.nok-nav-menu-item',
    '.nok-nav-menu-icon',

    // Hero (need all internal structure)
    'nok-hero',
    '.nok-hero__inner',

];

// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  INCLUDE — Prefix tokens (substring variant for utility prefixes)   ║
// ║  Match: token appears anywhere in selector (same as substring)      ║
// ║  Use for: utility class families sharing a common prefix            ║
// ║  Example: '.nok-fs-' matches .nok-fs-1, .nok-fs-giant,             ║
// ║           .nok-fs-to-lg-3                                           ║
// ╚═══════════════════════════════════════════════════════════════════════╝
const atfPrefixTokens = [
    '.nok-fs-giant',
];

// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  INCLUDE — Boundary tokens                                          ║
// ║  Match: token followed by end-of-string, pseudo-class, or non-word  ║
// ║         character. Must NOT be followed by [a-zA-Z0-9_-]            ║
// ║  Use for: specific classes where you do NOT want BEM children or    ║
// ║           modifier variants to be included                          ║
// ║  Example: '.nok-button' matches '.nok-button:hover'                 ║
// ║           but NOT '.nok-button-group' or '.nok-button--small'       ║
// ╚═══════════════════════════════════════════════════════════════════════╝
const atfBoundaryTokens = [
    // Section base
    '.nok-section',
    'nok-section',
    '.nok-section__inner',
    '.nok-section__inner--stretched',

    // Button base only (NOT -group, -badge, -menu)
    '.nok-button',

    // Layout
    '.nok-layout-grid',
    '.nok-usp',
    '.nok-hyperlink',

    // Color utilities — specific classes only
    '.nok-bg-body',
    '.nok-bg-body--lighter',
    '.nok-bg-alpha-6',
    '.nok-bg-white',
    //'.nok-bg-darkblue',
    //'.nok-bg-darkestblue',
    //'.nok-bg-lightblue',
    //'.nok-bg-greenblue',
    //'.nok-text-contrast',
    //'.nok-text-darkerblue',
    //'.nok-text-lightblue',
    //'.nok-text-lightgrey',
    //'.nok-text-white',
    //'.nok-fill-contrast',
    //'.nok-bg-blur',
    //'.nok-bg-blur--large',

    // Dark mode — only the specific classes used above the fold
    '.nok-dark-bg-darkerblue',
    '.nok-dark-bg-darkestblue',
    //'.nok-dark-text-contrast',
    //'.nok-dark-text-white',
    //'.nok-dark-bg-alpha-10',

    // Spacing
    '.nok-mt-0',
    '.nok-my-0',
    '.nok-px-0',
    '.nok-px-section-padding',
    '.nok-pl-section-padding',
    '.nok-px-to-lg-section-padding',

    // Layout utilities
    //'.nok-layout-grid__1-column',
    //'.nok-order-1',
    //'.nok-order-2',
    //'.nok-column-gap-3',
    //'.nok-columns-to-lg-1',

    // Visibility
    //'.nok-invisible-sm',
    '.nok-invisible-to-sm',
    //'.nok-invisible-to-lg',
    //'.nok-invisible-to-xl',
    //'.nok-invisible-to-xxxl',
    //'.nok-visible-xs',

    '.sr-only'
];

// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  INCLUDE — Reboot selectors (exact match)                           ║
// ║  Match: selector must EXACTLY equal one of these after stripping    ║
// ║         pseudo-classes and pseudo-elements                          ║
// ║  Use for: element-level resets (*, html, body, h1, img, etc.)       ║
// ║  Example: 'a' matches 'a', 'a:hover', 'a::before'                  ║
// ║           but NOT '.nok-hyperlink a' (compound selectors don't      ║
// ║           match — only the base stripped selector is compared)       ║
// ╚═══════════════════════════════════════════════════════════════════════╝
export const atfRebootSelectors = [
    '*',
    'html',
    'body',
    'img',
    'svg',
    'a',
    'p',
    'h1',
    'h2',
    //'h3',
    //'h4',
    //'h5',
    //'h6',
    'figure',
    'button',
    //'section',
    //'ul',
    //'ol',
    'svg.nok-icon',
    'nok-logo',
    // Hidden-at-load elements are handled by nok-atf-reboot.css (display:none).
    // Their full component rules are in BTF which overrides with proper closed state.
];

// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  EXCLUDE — Override patterns (checked BEFORE inclusion)             ║
// ║                                                                     ║
// ║  Matching mode is auto-detected from the pattern syntax:            ║
// ║                                                                     ║
// ║  SUBSTRING — patterns containing '.', ':', '[', '#', space, etc.   ║
// ║    The pattern can appear ANYWHERE in the selector string.          ║
// ║    '.nok-button--small' matches '.nok-button--small:hover'          ║
// ║    ':hover' matches '.nok-button:hover', 'a:hover', etc.           ║
// ║                                                                     ║
// ║  BOUNDARY — bare identifiers (letters, digits, hyphens only)       ║
// ║    Must NOT be preceded or followed by [a-zA-Z0-9_-].              ║
// ║    'nok-popup' matches 'body nok-popup' and 'nok-popup:hover'      ║
// ║    but NOT 'nok-popup-body' or '.nok-popup'                         ║
// ║                                                                     ║
// ║  Effect: any selector matching an include token BUT ALSO matching   ║
// ║          an exclusion pattern is forced to BTF.                     ║
// ║  Use for: interaction states, hidden elements, JS-dependent states, ║
// ║           and component variants not visible at first paint         ║
// ╚═══════════════════════════════════════════════════════════════════════╝
const atfExcludePatterns = [
    // Section decorator variants (gradient, linked transitions, collapse spacing)
    '.linked',
    '.gradient-background',
    '.collapse-top',
    '.collapse-bottom',
    // Animation-on-scroll (invisible at first paint, JS-triggered)
    '.nok-aos',
    // Button size/shape variants NOT present in hero (hero uses default size)
    '.nok-button--circle',
    '.nok-button--huge',
    '.nok-button--large',
    '.nok-button--small',
    // Button interaction states (hover/focus/active happen after first paint)
    '.nok-button.active',
    '.nok-button.disabled',
    // Navigation dropdown active state (requires JS interaction)
    '[data-active-menu]',
    // Section inner variants that aren't the base
    '.nok-section__inner.condensed',
    '.nok-section__inner.double-margin',
    '.nok-section__inner.triple-margin',
    'article .nok-section__inner',
    // Single-post body class overrides (never ATF on initial load)
    'body.nok-single-',
    // Sidebar open state (requires JS interaction)
    '.sidebar-open',
    // Active states and pseudo states
    '.active',
    '.disabled',
    ':active',
    ':disabled',
    ':hover',
    ':visited',
    ':focus',
    ':focus-visible',
    ':focus-within',
    '::after',
    '::before',
    ':has(.nok-stretched-link)',
    'a:not([href]):not([class])',

    // Hidden-at-load custom elements (display:none in nok-atf-reboot.css)
    'nok-nav-menu-bar-dropdown',
    'nok-navigation-drawer',
    'nok-accessibility-helper',
    '.nok-pp-edit',
    'nok-page-footer',

    'body.__enable-transitions',
    'body.domready',
    'body.no-js',
    'body:has(.popup-open)',

    '.nok-hyperlink',

    'nok-popup',
    'nok-screen-mask',
    'p:empty',

    'nok-top-navigation.popup-open',

    '.nok-layout-grid',

    '.pull-down',
    '.z-ascend',

    '.nok-section:nth-child(3)',
    '.nok-section:nth-child(4)',
    '.nok-section:nth-child(5)',
    '.nok-section:nth-child(6)',
    '.nok-section:nth-child(7)',
    '.nok-section:nth-child(8)',
    '.nok-section:nth-child(9)',
    '.nok-section:nth-child(10)',
    '.nok-section:nth-child(11)',
    '.nok-section:nth-child(12)',
    '.nok-section:nth-child(13)',
    '.nok-section:nth-child(14)',
    '.nok-section:nth-child(15)',
    '.nok-section:nth-child(16)',
    '.nok-section:nth-child(17)',
    '.nok-section:nth-child(18)',
    '.nok-section:nth-child(19)',
    '.nok-section:nth-child(20)',
    'nok-section:nth-child(3)',
    'nok-section:nth-child(4)',
    'nok-section:nth-child(5)',
    'nok-section:nth-child(6)',
    'nok-section:nth-child(7)',
    'nok-section:nth-child(8)',
    'nok-section:nth-child(9)',
    'nok-section:nth-child(10)',
    'nok-section:nth-child(11)',
    'nok-section:nth-child(12)',
    'nok-section:nth-child(13)',
    'nok-section:nth-child(14)',
    'nok-section:nth-child(15)',
    'nok-section:nth-child(16)',
    'nok-section:nth-child(17)',
    'nok-section:nth-child(18)',
    'nok-section:nth-child(19)',
    'nok-section:nth-child(20)',

    '.nok-section-narrow',

    'nok-hero .nok-hero__inner .article',
    'nok-hero .nok-hero__inner .article p',
    'nok-hero .nok-hero__inner .header',
    'nok-hero .nok-hero__inner .header p',
    'nok-hero .nok-hero__inner button',
    'nok-hero .nok-hero__inner header',
    'nok-hero .nok-hero__inner header p',
    'nok-hero .nok-hero__inner-figure-mask',
    'nok-hero.nok-hero--fullscreen .nok-button-group',
    'nok-hero.nok-hero--fullscreen .nok-hero-fullscreen__breadcrumbs',
    'nok-hero.nok-hero--fullscreen .nok-hero-fullscreen__content--text-shadow',
    'nok-hero.nok-hero--fullscreen .nok-hero-fullscreen__overlay--gradient-left',
    'nok-hero.nok-hero--fullscreen.nok-hero-fullscreen--dark',
    'nok-hero.nok-hero--fullscreen.nok-hero-fullscreen--dark .nok-hero-fullscreen__tagline',
    'nok-hero.nok-hero--fullscreen.nok-hero-fullscreen--light .nok-hero-fullscreen__breadcrumbs',
    'nok-hero.nok-hero--fullscreen.nok-hero-fullscreen--light .nok-hero-fullscreen__breadcrumbs a',

    'nok-navigation-menu-bar .nok-navigation-menu-bar__inner [aria-expanded="true"] .nok-icon',
    'nok-navigation-menu-bar img'
];

// ---------------------------------------------------------------------------
// Combined exports
// ---------------------------------------------------------------------------
export const atfTokens = [
    ...atfSubstringTokens,
    ...atfPrefixTokens,
];

export const atfBoundaryTokensList = atfBoundaryTokens;

export const atfExcludePatternsList = atfExcludePatterns;

// ---------------------------------------------------------------------------
// Options
// ---------------------------------------------------------------------------
export const options = {
    /** When true: duplicate ALL :root blocks to both files (safe but large).
     *  When false: prune ATF :root to only vars referenced by ATF rules (smaller). */
    duplicateRootVars: false,

    /** @keyframes names to force into ATF (empty = all go to BTF) */
    atfKeyframes: [],

    /** Warn if ATF exceeds this size in KB (minified). 58KB min ≈ 10KB gzip. */
    sizeBudgetKB: 65,
};

// ╔═══════════════════════════════════════════════════════════════════════╗
// ║  VALIDATION — Critical tokens                                       ║
// ║  ATF output must contain at least one rule matching each of these.  ║
// ║  Extraction fails with a warning if any are missing.                ║
// ╚═══════════════════════════════════════════════════════════════════════╝
export const criticalTokens = [
    'nok-top-navigation',
    'nok-hero',
    '.nok-section',
    'nok-section',
    '.nok-button',
];
