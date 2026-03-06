# Plan: Questionnaire System (Vragenlijst)

## Background

The old NOK 4.2 theme had a hardcoded jQuery questionnaire (`quest-inclusie.js`) that evaluated whether a patient qualifies for bariatric surgery. It was a single-purpose wizard with embedded Dutch text and fixed BMI/comorbidity logic.

The new system should be **generic**: support multiple questionnaires, CMS-defined questions, branching logic, and configurable result screens — while fitting naturally into the existing page-parts architecture.

## Goals

1. **CMS-defined questionnaires** — editors create/edit questionnaires in WordPress without code changes
2. **Multi-step wizard UI** — one question per screen, prev/next navigation, progress indicator
3. **Conditional branching** — skip or show questions based on previous answers
4. **Configurable results** — multiple outcome paths with custom advice text per result
5. **Reusable** — the inclusie-check is the first use case, but the system should handle other questionnaires (e.g. patient satisfaction, pre-intake screening)
6. **Accessible** — keyboard navigation, screen reader support, focus management
7. **Performant** — lazy-loaded JS module, no dependencies beyond DOMule
8. **Dual presentation** — works both inline (page-part) and as a popup, using the existing popup system
9. **Inline link triggers** — any `<a>` link in Gutenberg content can open a questionnaire popup

---

## Architecture

### Custom Post Type + Meta Fields

A `vragenlijst` CPT where each post is one questionnaire. Questions, logic, and results are stored as structured post meta (JSON).

**Why CPT over page-part fields:** A questionnaire is a self-contained data object with internal relationships (question → branch → result). The page-part field DSL (text, select, repeater) can't express conditional logic trees. A dedicated CPT with a React-based editor sidebar gives editors a proper UI for building question flows.

**Why not Gravity Forms / third-party:** GF could handle simple surveys but lacks the branded wizard UI, conditional result logic, and tight theme integration (e.g. BMI calculator reuse). A native solution keeps control and avoids plugin lock-in.

**Why not individual meta fields per question:** A single JSON blob keeps the questionnaire atomic — no orphaned questions, no sync issues between separate meta rows. The React editor reads/writes the whole structure. Same pattern used by Gravity Forms internally.

### Data Model

```
vragenlijst (CPT)
├── post_title          → Questionnaire name (admin only)
├── post_content        → Optional intro text (Gutenberg)
├── meta: _vl_config    → JSON blob (see schema below)
```

#### Config Schema

```json
{
  "settings": {
    "show_progress": true,
    "allow_back": true,
    "start_button_text": "Start de vragenlijst",
    "submit_button_text": "Bekijk resultaat"
  },
  "questions": [
    {
      "id": "q_bmi",
      "type": "number",
      "label": "Wat is uw BMI?",
      "help": "Gebruik de BMI-calculator hierboven",
      "required": true,
      "validation": {
        "min": 10,
        "max": 80,
        "error": "Vul een geldig BMI in (10-80)"
      },
      "branch": {
        "op": "<",
        "value": 35,
        "action": "skip_to",
        "target": "r_not_eligible"
      }
    },
    {
      "id": "q_diabetes",
      "type": "radio",
      "label": "Heeft u diabetes?",
      "options": [
        { "value": "ja", "label": "Ja" },
        { "value": "nee", "label": "Nee" }
      ]
    }
  ],
  "results": [
    {
      "id": "r_eligible",
      "condition": {
        "match": "any",
        "rules": [
          { "question": "q_bmi", "op": ">=", "value": 40 },
          {
            "match": "all",
            "rules": [
              { "question": "q_bmi", "op": ">=", "value": 35 },
              {
                "match": "any",
                "rules": [
                  { "question": "q_diabetes", "op": "==", "value": "ja" },
                  { "question": "q_bloeddruk", "op": "==", "value": "ja" },
                  { "question": "q_gewrichtsklachten", "op": "==", "value": "ja" },
                  { "question": "q_osas", "op": "==", "value": "ja" },
                  { "question": "q_andere_aandoening", "op": "==", "value": "ja" }
                ]
              }
            ]
          }
        ]
      },
      "title": "U komt mogelijk in aanmerking",
      "body": "<p>Op basis van uw antwoorden komt u <em>mogelijk</em> in aanmerking voor een behandeling bij de Nederlandse Obesitas Kliniek.</p><p>Voor een behandeling is een verwijzing van uw (huis)arts vereist.</p>",
      "cta_text": "Bekijk verwijsinformatie",
      "cta_url": "/verwijzen/",
      "style": "positive"
    },
    {
      "id": "r_not_eligible",
      "condition": "default",
      "title": "U komt helaas niet in aanmerking",
      "body": "...",
      "style": "neutral"
    }
  ]
}
```

### Structured Rule Engine (no expression parser)

Conditions use a **structured rule format** instead of a free-text expression language. This eliminates the need for a custom parser, makes conditions impossible to write with syntax errors, and is trivially renderable in a visual editor UI.

**Rule format:**

```json
{
  "match": "all",          // "all" (AND) or "any" (OR)
  "rules": [
    { "question": "q_bmi", "op": ">=", "value": 35 },
    { "question": "q_diabetes", "op": "==", "value": "ja" },
    {
      "match": "any",     // nested group
      "rules": [ ... ]
    }
  ]
}
```

**Supported operators:** `==`, `!=`, `>`, `>=`, `<`, `<=`

**Evaluator implementation** (~30 lines, recursive, no parser needed):

```js
function evaluateRule(rule, answers) {
  // Leaf rule: compare a question's answer to a value
  if (rule.question) {
    const answer = answers[rule.question];
    if (answer === undefined) return false;
    const a = Number(answer), v = Number(rule.value);
    const useNumeric = !isNaN(a) && !isNaN(v);
    const left = useNumeric ? a : String(answer);
    const right = useNumeric ? v : String(rule.value);
    switch (rule.op) {
      case '==': return left === right;
      case '!=': return left !== right;
      case '>':  return left > right;
      case '>=': return left >= right;
      case '<':  return left < right;
      case '<=': return left <= right;
      default:   return false;
    }
  }
  // Group rule: combine child rules with all/any
  if (rule.match && Array.isArray(rule.rules)) {
    if (rule.rules.length > 50) return false; // depth/size guard
    const fn = rule.match === 'all' ? 'every' : 'some';
    return rule.rules[fn](r => evaluateRule(r, answers));
  }
  return false;
}
```

**Nesting depth guard:** The evaluator limits recursion to prevent malformed configs from causing stack overflows. A depth limit of 8 levels is enforced (more than sufficient — the inclusie-check needs 3 levels).

**Why not a free-text expression language:**
- A parser for `(q_bmi >= 35 && (q_diabetes == 'ja' || q_comorbidity == 'ja'))` with correct operator precedence, type coercion, and error reporting is 150-200 lines — not the "~60 lines" initially estimated.
- Editors can't write syntax errors in structured rules (the visual editor produces valid JSON by construction).
- Structured rules are trivially serializable to a visual builder (Phase 2): each `match` group becomes a card, each leaf rule becomes a row with dropdowns.
- If a free-text expression language is ever needed for power users, it can be added as an alternative condition type later without changing the evaluator.

### Branch Logic Semantics

Branching controls which questions are shown based on previous answers. The semantics must be unambiguous:

**Branch structure on a question:**

```json
{
  "id": "q_bmi",
  "branch": {
    "op": "<",
    "value": 35,
    "action": "skip_to",
    "target": "r_not_eligible"
  }
}
```

**`action` types:**

| Action | Behavior |
|--------|----------|
| `skip_to` (question ID) | Jump forward to a specific question, skipping all questions between current and target. |
| `skip_to` (result ID) | Short-circuit to result evaluation immediately. The result with the matching ID is shown directly — remaining questions are skipped entirely. |
| `exclude` | Mark specific questions as excluded (by ID list in `target`). Remaining questions still play in order. |

**How targets are distinguished:** Question IDs use `q_` prefix, result IDs use `r_` prefix. The engine checks the prefix to determine whether `skip_to` targets a question or a result.

**Only one branch per question.** If multiple conditions are needed, the branch itself can use a structured rule group (`match: all/any` with nested rules). But only one branch outcome is evaluated per question — first match wins if branch is an array (future extension), single object for Phase 1.

**Back-navigation rules:**

1. The user can navigate back to any previously visited question.
2. When the user changes an answer on a previously visited question, **all answers after that point are cleared** and the engine re-evaluates branches from that question forward. This prevents stale answers from influencing results.
3. The "history" is a stack of visited question IDs. Going back pops the stack. Going forward pushes to it.
4. When a branch short-circuits to a result (`skip_to` a result ID), the back button returns to the last answered question — not the result screen.

**Answer clearing rationale:** Without clearing, a user could answer "BMI = 40" (sees comorbidity questions, answers them), go back, change BMI to 28 (branch skips comorbidity questions), and the old comorbidity answers would still be in the answers map — potentially influencing result evaluation even though those questions were never shown. Clearing prevents this class of bugs entirely.

**`info` type questions:** Questions with `type: "info"` have no input field. The "Next" button is always enabled for these. They are skipped in result evaluation (no answer stored). Useful for displaying explanatory text between question groups.

### Progress Indicator

The progress bar uses **percentage-based display**, not "step X of Y". This avoids confusing jumps when branch logic changes the visible question count mid-flow.

**Implementation:**
- Progress = `(visitedCount / totalVisibleCount) * 100%`, where `totalVisibleCount` is computed from the *current* answer state (questions excluded by branches are not counted).
- Displayed as a horizontal bar with `aria-valuenow` and `aria-valuemax` for screen readers.
- The bar only advances, never jumps backward — even when going back, the bar stays at the high-water mark until the user passes it again. This prevents the disorienting effect of the bar shrinking.
- When branch logic changes the total (e.g., BMI < 35 excludes 5 comorbidity questions), the bar smoothly adjusts — because it's percentage-based, the visual change is a smooth fill rather than a jarring "step 3 of 7 → step 3 of 2."

---

## Popup Mode

### How the Existing Popup System Works

The theme has a general-purpose popup system used by the BMI calculator and search:

1. **Popup containers** are `<nok-popup id="popup-{name}">` elements inside `<nok-screen-mask>` inside `<nok-top-navigation>`, rendered in `nok-header-main.php`. Each has a header (title + close button) and body.

2. **Triggers** are links/buttons with toggler data-attributes:
   - `data-toggles-class="popup-open"` on `nok-top-navigation` (body scroll lock)
   - `data-toggles-attribute="data-state"` / `data-toggles-attribute-value="open"` on the specific `<nok-popup>`
   - `data-attribute-target="#popup-{name}"` to target by ID

3. **Menu integration:** WordPress menu items with URL `#popup-{name}` are auto-detected by `MenuManager.php` (line 113) and converted to popup triggers with the correct data-attributes. This is how the header's BMI and search buttons work.

4. **CSS:** `_nok-popup.scss` handles visibility (opacity/visibility transition), body scroll lock (`body:has(.popup-open)`), grid layout, and the open animation.

### Questionnaire Popup Integration

The questionnaire popup follows the exact same pattern — no new popup system needed.

**Popup container** — DONE. Added to `nok-header-main.php` alongside BMI/search popups. Uses slug-based lookup (`vragenlijst_slug => 'inclusie-check'`) instead of a post ID constant — portable across environments.

```php
<nok-popup class="nok-bg-body nok-dark-bg-darkerblue" id="popup-vragenlijst"
           data-on-close="reset">
    <nok-popup-header>
        <nok-popup-title>Kom ik in aanmerking?</nok-popup-title>
        <button ...close button attrs...>
            <?= Assets::getIcon('ui_close') ?>
        </button>
    </nok-popup-header>
    <nok-popup-body>
        <?php $theme->embed_post_part_template('nok-vragenlijst', [
            'vragenlijst_slug' => 'inclusie-check'
        ], true); ?>
    </nok-popup-body>
</nok-popup>
```

**Trigger via navigation menu:** Add a menu item with URL `#popup-vragenlijst` in WordPress admin. MenuManager auto-converts it to a popup trigger. Works in both desktop menu bar and mobile drawer.

**Trigger via buttons/CTAs in page parts:** Any element can trigger the popup using the standard toggler data-attributes:

```html
<a href="#" class="nok-button"
   data-toggles-class="popup-open" data-class-target="nok-top-navigation"
   data-toggles-attribute="data-state" data-toggles-attribute-value="open"
   data-attribute-target="#popup-vragenlijst">
   Doe de inclusie-check
</a>
```

### Generic Popup Close Handler (`data-on-close`) — DONE

**Implemented in `nok-toggler.mjs`.** A `dispatchCloseEventIfNeeded(target)` helper checks for `data-on-close` on any element that has an attribute removed, and dispatches a `nok-popup:close` CustomEvent. Called from both code paths:
1. `executeAction()` — close button click (`data-unsets-attribute`)
2. `executeUndoStack()` — outside-click / escape restore

**Implementation note:** No tag name check — fires on ANY element with `data-on-close`, not just `<nok-popup>`. More generic than originally planned (per staff review feedback).

**Questionnaire usage** (for when `nok-vragenlijst.mjs` is built):

```js
const popup = container.closest('nok-popup');
if (popup) {
  popup.addEventListener('nok-popup:close', () => {
    engine.reset();
    renderer.showIntro();
  });
}
```

### Inline Link Triggers from Gutenberg Content — DONE

Editors create inline popup links via a **RichText format type** (`nok/popup-link`). Select text → click "Popup Link" in the `˅` rich text controls dropdown → pick target from dropdown.

**How it works:**

1. **Editor:** `registerFormatType` wraps selected text in `<span class="nok-popup-link" data-popup="popup-id">`. Uses `<span>` (not `<a>`) to avoid collision with core's link format which owns `<a>`. Editor CSS styles the span as a link with a small icon indicator and "Popup-link" hover tooltip.

2. **PHP render:** `BlockRenderers::expand_popup_links()` (`render_block` filter) converts the `<span>` to `<a href="#" class="nok-popup-link" ...toggler attrs...>`. Uses `BlockRenderers::get_popup_toggler_attrs()` as single source of truth for the attribute string.

3. **Frontend:** The toggler picks up the `<a>` with data-attributes. `data-requires="./nok-toggler.mjs"` is on `<main id="main-content">` (moved from `<nok-top-navigation>` to cover both nav and page content scope).

**Key files:**
- `src/nok-popup-link-format.js` — format type registration + React toolbar component
- `webpack.config.js` — entry point
- `inc/Core/AssetManager.php` — enqueue + `wp_localize_script('nokPopupTargets', [...])`
- `inc/BlockRenderers.php` — `expand_popup_links()` + `get_popup_toggler_attrs()`
- `header.php` — `data-requires` on `<main>`

**Regex note:** The regex matches `nok-popup-link` anywhere in the `<span>` attributes (attribute-order-agnostic), then extracts `data-popup` separately. This was needed because Gutenberg serializes `data-popup` before `class`.

**Available targets** (hardcoded in `AssetManager.php` for now):
- `popup-bmi-calculator` — BMI Calculator
- `popup-search` — Zoeken
- `popup-vragenlijst` — Vragenlijst

### Popup vs Inline: Same Component, Two Contexts

The `nok-vragenlijst.mjs` module doesn't know or care whether it's inside a popup or inline on a page. The `init(container)` function receives the wrapper div and works within it. This means:

- **Inline:** Page part template renders the wrapper `<div>` directly in the page flow. JS initializes when the element enters the viewport (lazy loading).
- **Popup:** The same page part template is embedded inside `<nok-popup-body>`. JS initializes at `docReady` (visually hidden until the popup opens). It listens for `nok-popup:close` on the parent `<nok-popup>` to reset on close.

No conditional logic needed in the JS module. The only popup-specific behavior is the close event listener, set up by checking `container.closest('nok-popup')` at init time — if present, listen for the event; if not, skip it.

### Multiple Questionnaires as Popups

Each questionnaire popup needs its own `<nok-popup>` container in `nok-header-main.php`. For Phase 1 (just the inclusie-check), this is hardcoded. For Phase 2+, consider a dynamic approach:

- Query all published `vragenlijst` posts in `nok-header-main.php`
- Render a `<nok-popup>` for each, with `id="popup-vragenlijst-{post_id}"`
- Auto-register them in the popup registry filter
- Editor links: `#popup-vragenlijst-{post_id}`

This is deferred — for now, the inclusie-check is the only questionnaire popup.

---

## Components

### 1. CPT Registration (`inc/PostTypes.php`)

- Register `vragenlijst` CPT (not public, no archive, `show_in_rest` for Gutenberg)
- Register `_vl_config` meta field with `show_in_rest` (JSON string type with `sanitize_callback`)
- The `sanitize_callback` performs structural validation (see Schema Validation below)

### 2. Editor UI (`src/vragenlijst-editor/`) — Phase 2

A React sidebar panel (Gutenberg `PluginDocumentSettingPanel`) for building questionnaires visually:

- Question list with drag-to-reorder
- Per-question: type selector, label, help text, options (for radio/select), validation rules
- Branch logic builder (simple: "if answer [op] [value], skip to [question/result]")
- Result cards with structured rule builder (nested all/any groups with dropdowns)
- In-editor preview

Phase 1 uses raw JSON editing via a code editor panel in the sidebar. The structured rule format makes this manageable — editors copy/adapt the inclusie-check example.

### 3. Page Part Template (`template-parts/page-parts/nok-vragenlijst.php`)

```php
/**
 * Template Name: Vragenlijst
 * Description: Toont een interactieve vragenlijst met stapsgewijze vragen
 * Slug: nok-vragenlijst
 * Custom Fields:
 * - vragenlijst_id:number!descr[Post-ID van de vragenlijst]
 * - show_title:checkbox!default(true)!page-editable
 */
```

The template:
- Fetches the selected `vragenlijst` post by ID and its `_vl_config` meta
- Validates the post exists and is published; renders error message (admin only) if not
- Renders a `<div class="nok-vragenlijst" data-requires="nok-vragenlijst.mjs">` wrapper
- Outputs the config as a `<script type="application/json">` block inside the wrapper (no global JS, no inline script execution)
- Renders a `<noscript>` fallback with clinic contact information:
  ```html
  <noscript>
    <div class="nok-vragenlijst__noscript">
      <p>Deze vragenlijst vereist JavaScript. Neem contact op met de
      Nederlandse Obesitas Kliniek via <a href="tel:+31880070">ons telefoonnummer</a>
      of <a href="/contact/">het contactformulier</a> om te bespreken
      of u in aanmerking komt voor behandeling.</p>
    </div>
  </noscript>
  ```
- The intro text (post_content) renders above the form
- Questions and results are rendered entirely by JS from the JSON config

**Note on `data-requires` element:** DOMule's loader passes the element with `data-requires` as the container argument to `init()`. Using a `<div>` (not `<form>`) avoids browser form behaviors (Enter triggering submit, autocomplete interference). The wrapper is semantic — no actual form submission occurs.

### 4. JS Module (`assets/js/nok-vragenlijst.mjs`)

Vanilla ES module, loaded via DOMule `data-requires`, lazy-loadable.

**Architecture:**

```
nok-vragenlijst.mjs
├── init(container)           → finds config JSON, bootstraps wizard
│                               detects popup context, listens for close event
├── VragenlijstEngine         → state machine
│   ├── config                → parsed JSON config (immutable after init)
│   ├── answers{}             → current answer map
│   ├── history[]             → stack of visited question IDs
│   ├── evaluateRule(rule)    → recursive structured rule evaluator
│   ├── getVisibleQuestions() → filters out branched-away questions
│   ├── getResult()           → finds first matching result, falls back to default
│   ├── canAdvance()          → validation check for current question
│   ├── next()               → evaluate branch, push to history, advance
│   ├── prev()               → pop history, clear answers after that point
│   └── reset()              → clear answers + history, return to intro
├── VragenlijstRenderer       → DOM construction
│   ├── renderQuestion(q)    → builds input HTML by type
│   ├── renderProgress()     → percentage-based progress bar
│   ├── renderNavigation()   → prev/next buttons with state
│   ├── renderResult(r)      → result card with CTA
│   ├── renderInfo(q)        → read-only text block (info type)
│   └── transition(dir)      → slide/fade animation (respects prefers-reduced-motion)
└── evaluateRule()            → standalone pure function (see implementation above)
```

**Popup close handling:**

```js
// In init(container):
const popup = container.closest('nok-popup');
if (popup) {
  popup.addEventListener('nok-popup:close', () => {
    engine.reset();
    renderer.showIntro();
  });
}
```

No MutationObserver needed — the toggler dispatches `nok-popup:close` on the `<nok-popup>` element when it unsets `data-state` (see [Generic Popup Close Handler](#generic-popup-close-handler-data-on-close)).

**Interaction flow:**
1. User clicks "Start" → intro hides, first question fades in
2. User answers → "Next" enables (auto-enabled for `info` type), Enter advances
3. On advance: branch evaluated → determines next question or short-circuits to result
4. Question pushed to history stack. Progress bar updates.
5. After last visible question → evaluate result conditions in order, show first match (or `default`)
6. Result card shows advice text + optional CTA button
7. "Opnieuw" button calls `reset()`, returns to intro screen
8. Back button: pops history, clears answers after that point, re-renders previous question

**Accessibility:**
- `aria-live="polite"` on question container for screen reader announcements
- Focus moves to first input on each step transition
- Progress bar uses `role="progressbar"` with `aria-valuenow`/`aria-valuemax`
- Keyboard: Enter advances (when Next is enabled), Escape goes back (in popup context, Escape also closes the popup — handled by the toggler system, not the questionnaire)
- `prefers-reduced-motion`: skip slide/fade animations, use instant transitions

### 5. Popup Link Format Type (`src/nok-popup-link-format.js`) — DONE

See [Inline Link Triggers](#inline-link-triggers-from-gutenberg-content--done) above for full details. Implemented, tested, and working on production.

### 6. SCSS (`assets/css/components/_nok-vragenlijst.scss`)

- `.nok-vragenlijst` — outer wrapper (`display: block` — custom elements caveat)
- `.nok-vragenlijst__intro` — start screen with button
- `.nok-vragenlijst__progress` — percentage bar
- `.nok-vragenlijst__question` — individual question card (fade/slide transitions)
- `.nok-vragenlijst__field` — input wrapper (text, radio group, select)
- `.nok-vragenlijst__help` — help text below label
- `.nok-vragenlijst__error` — validation error message
- `.nok-vragenlijst__nav` — prev/next button row
- `.nok-vragenlijst__result` — result card (`--positive`, `--neutral`, `--negative` modifiers)
- `.nok-vragenlijst__result-cta` — CTA button in result
- `.nok-vragenlijst__noscript` — noscript fallback styling

No popup-specific overrides needed — the questionnaire renders inside `<nok-popup-body>` which already handles overflow scrolling and layout.

Follows existing BEM conventions. Uses theme spacing tokens, color system, and font scale.

---

## Schema Validation (`sanitize_callback`)

The `_vl_config` meta field's `sanitize_callback` validates the JSON structure on every save (both classic editor and REST API). This catches editor mistakes at save time rather than producing a silently broken frontend.

**Validation rules:**

```
Required structure:
├── settings (object, optional — defaults applied if missing)
├── questions (array, required, non-empty)
│   └── each question:
│       ├── id (string, required, unique across all questions)
│       ├── type (string, required, one of: text|number|radio|select|checkbox|info)
│       ├── label (string, required)
│       ├── options (array, required if type is radio|select)
│       │   └── each option: { value (string), label (string) }
│       ├── validation (object, optional)
│       │   └── min, max (number), error (string)
│       ├── branch (object, optional)
│       │   └── op (string), value (mixed), action (string), target (string)
│       │       └── target must reference an existing question ID or result ID
│       └── required (boolean, optional, default true)
├── results (array, required, non-empty)
│   └── each result:
│       ├── id (string, required, unique)
│       ├── condition (object|"default", required)
│       │   └── if object: valid structured rule (recursive validation)
│       ├── title (string, required)
│       ├── body (string, required — HTML, sanitized with wp_kses_post)
│       ├── cta_url (string, optional — validated as relative path or same-domain URL)
│       └── style (string, optional, one of: positive|neutral|negative)
└── Exactly one result must have condition: "default"
```

**On validation failure:** The save proceeds but a transient admin notice is stored, displayed on next page load: "Vragenlijst configuratie bevat fouten: [specific error]. De vragenlijst wordt mogelijk niet correct weergegeven."

**CTA URL validation:** `cta_url` is validated to be either a relative path (starts with `/`) or a URL on the same domain (`obesitaskliniek.nl`). External URLs are stripped. This prevents a compromised editor account from inserting phishing links into a medical advice CTA.

---

## Phased Implementation

### Phase 1: Working MVP (core functionality)

**Popup infrastructure (DONE):**
- [x] Move `data-requires="./nok-toggler.mjs"` from `<nok-top-navigation>` to `<main>` (`header.php`)
- [x] Add `expand_popup_links` render filter + `get_popup_toggler_attrs` helper (`BlockRenderers.php`)
- [x] Add `data-on-close` event dispatch to `nok-toggler.mjs` (both close-button and outside-click paths)
- [x] Add inclusie-check popup shell to `nok-header-main.php` (slug-based lookup)
- [x] Create `nok-popup-link-format.js` — Gutenberg format type for popup links (pulled from Phase 2)
- [x] Add webpack entry + editor enqueue + localize popup targets (`AssetManager.php`)

**Questionnaire core (TODO):**
- [ ] Register `vragenlijst` CPT with JSON meta field and schema validation
- [ ] Create `nok-vragenlijst.mjs` — engine (with structured rule evaluator), renderer
- [ ] Create `_nok-vragenlijst.scss` — wizard styling
- [ ] Create `nok-vragenlijst.php` page-part template (slug-based lookup, not post ID)
- [ ] Add `#popup-vragenlijst` menu item in WordPress admin
- [ ] Manually create the inclusie-check questionnaire via REST API (raw JSON)
- [ ] Write unit tests for `evaluateRule()` and engine state transitions
- [ ] Define manual test matrix for inclusie-check paths (see Testing section)

**Deliverable:** The old inclusie-check works on the new site — both inline and as a popup triggered from navigation or content links.

**Note:** Phase 1 uses a slug-based lookup for the questionnaire in the popup template (`vragenlijst_slug => 'inclusie-check'`). The page part template resolves this to a post ID at render time. No hardcoded ID constant needed.

### Phase 2: Editor UI + post selector

- [ ] Add `post_selector` field type to Registry.php, MetaManager.php, and React editor
- [ ] Gutenberg sidebar panel for questionnaire building
- [ ] Drag-reorder questions
- [ ] Visual structured rule builder (nested all/any groups with dropdowns)
- [ ] Result card editor with rich text
- [ ] In-editor preview
- [ ] Dynamic popup rendering for multiple questionnaires
- [ ] Make popup target list dynamic (filter-based registry instead of hardcoded array in AssetManager)

**Deliverable:** Editors can create questionnaires without touching JSON. ~~Popup link format type~~ (done in Phase 1).

### Phase 3: Enhancements

- [ ] Analytics integration (track completion rates, drop-off points via `dataLayer.push()`)
- [ ] Multi-page questionnaires (sections/chapters)
- [ ] Computed fields (e.g., auto-calculate BMI from height+weight within the questionnaire)
- [ ] Email results to patient (optional, via existing form infrastructure)
- [ ] Conditional text interpolation in results (e.g., "Uw BMI van {q_bmi} is...")

---

## Question Types

| Type | Input | Stored Value | Next Button |
|------|-------|--------------|-------------|
| `text` | `<input type="text">` | string | Enabled when non-empty (or not required) |
| `number` | `<input type="number">` with min/max/step | number | Enabled when valid number in range |
| `radio` | Radio button group | string (selected value) | Enabled when option selected |
| `select` | `<select>` dropdown | string (selected value) | Enabled when non-placeholder selected |
| `checkbox` | Single checkbox | boolean | Always enabled (unchecked = false) |
| `info` | Read-only text block (no input) | — (not stored) | Always enabled |

Extensible: new types can be added by registering a renderer function in the JS module.

---

## Security Considerations

- **No `eval()`, no expression parser** — structured rule evaluator is a pure recursive function over known JSON shapes. No string parsing, no code injection surface.
- **Recursion depth limit** — rule evaluator enforces max depth of 8 and max 50 rules per group to prevent stack overflow from malformed configs.
- **JSON config is read-only on frontend** — no client-side config mutation.
- **Schema validation on save** — structural validation catches malformed configs before they reach the frontend. Runs on both classic editor and REST API saves.
- **Output escaping** — result body HTML is stored by editors (same trust model as post_content), sanitized with `wp_kses_post()` on output.
- **CTA URL validation** — only relative paths and same-domain URLs allowed. External URLs stripped to prevent phishing via CTA buttons on medical advice screens.
- **Popup link conversion** — the `expand_popup_links` render filter only converts `<span class="nok-popup-link">` markers created by the editor format type. The `data-popup` value is escaped via `esc_attr()`. A link targeting a non-existent popup ID is harmless (toggler finds no target element, does nothing).
- **No data submission** — questionnaires are self-contained assessments, no answers are sent to the server (privacy by design). If submission is needed later, it goes through a separate, authenticated endpoint.
- **REST API exposure** — `_vl_config` is readable via REST API (`show_in_rest: true`). This is a known trade-off: the same data is already in the frontend `<script>` tag. If a future questionnaire contains sensitive routing logic, `show_in_rest` can be set to `false` for that field and data passed only via the page-part template.

---

## Testing

### Unit tests (Phase 1 deliverable)

**`evaluateRule()` — pure function, trivially testable:**
- Leaf rule with each operator (`==`, `!=`, `>`, `>=`, `<`, `<=`)
- String comparison (`"ja" == "ja"`, `"ja" != "nee"`)
- Numeric comparison with string-typed answers (`"35" >= 35`)
- Missing answer returns `false`
- `match: "all"` — all true, one false, empty rules
- `match: "any"` — one true, all false, empty rules
- Nested groups (3 levels deep)
- Depth limit enforcement (9+ levels returns false)
- Size limit enforcement (51+ rules returns false)

**Engine state transitions:**
- Linear flow (no branches): next/prev cycle through all questions
- Branch skip_to question: skips intermediate questions
- Branch skip_to result: short-circuits to result screen
- Back navigation: clears answers after current point
- Reset: returns to intro, clears all state
- `info` type: Next always enabled, no answer stored
- Popup context: reset triggered on `nok-popup:close` event

### Manual test matrix (inclusie-check)

| Path | BMI | Comorbidities | Expected Result |
|------|-----|---------------|-----------------|
| Eligible (high BMI) | >= 40 | any | `r_eligible` |
| Eligible (moderate BMI + comorbidity) | 35-39 | at least one "ja" | `r_eligible` |
| Not eligible (moderate BMI, no comorbidity) | 35-39 | all "nee" | `r_not_eligible` |
| Not eligible (low BMI) | < 35 | skipped (branch) | `r_not_eligible` |
| Edge: BMI exactly 35 | 35 | one "ja" | `r_eligible` |
| Edge: BMI exactly 40 | 40 | all "nee" | `r_eligible` |
| Back-nav: change BMI after comorbidity | 38 → 28 | answered | answers cleared, branch to result |
| Popup: complete then close/reopen | any | any | resets to intro |
| Inline popup link: `<span class="nok-popup-link">` in content | — | — | PHP converts to `<a>` with toggler attrs, popup opens |
| `data-on-close="reset"`: toggler unsets popup state | — | — | `nok-popup:close` event fires, questionnaire resets |

---

## Files to Create/Modify

### Already done (popup infrastructure)

| File | Action | Description |
|------|--------|-------------|
| `header.php` | Modified | `data-requires="./nok-toggler.mjs"` moved to `<main>` |
| `template-parts/page-parts/nok-header-main.php` | Modified | Removed `data-requires` from `<nok-top-navigation>`, added vragenlijst popup shell |
| `assets/js/nok-toggler.mjs` | Modified | `dispatchCloseEventIfNeeded()` in both `executeAction` and `executeUndoStack` |
| `inc/BlockRenderers.php` | Modified | `expand_popup_links()` filter + `get_popup_toggler_attrs()` helper |
| `src/nok-popup-link-format.js` | Created | `registerFormatType` for popup link toolbar button |
| `webpack.config.js` | Modified | Entry point for popup link format |
| `inc/Core/AssetManager.php` | Modified | Enqueue + localize popup targets + editor CSS for popup links |

### Phase 1 remaining (questionnaire core)

| File | Action | Description |
|------|--------|-------------|
| `inc/PostTypes.php` | Modify | Register `vragenlijst` CPT + meta with validation |
| `template-parts/page-parts/nok-vragenlijst.php` | Create | Page part template (slug-based lookup) |
| `assets/js/nok-vragenlijst.mjs` | Create | Wizard engine + renderer |
| `assets/css/components/_nok-vragenlijst.scss` | Create | Wizard styles |
| `assets/css/nok-components.scss` | Modify | Add `@use` for vragenlijst component |
| `tools/atf-btf-config.mjs` | Modify | Classify vragenlijst selectors as BTF |

### Phase 2

| File | Action | Description |
|------|--------|-------------|
| `inc/PageParts/Registry.php` | Modify | Add `post_selector` field type |
| `inc/PageParts/MetaManager.php` | Modify | Handle `post_selector` save/load |
| `src/vragenlijst-editor/` | Create | React editor panel |
| `inc/Core/AssetManager.php` | Modify | Dynamic popup target registry (replace hardcoded array) |

---

## Open Questions

1. **BMI integration** — should the questionnaire auto-read BMI from the calculator on the same page, or should the user manually enter it? The old system required manual entry. Auto-reading is slicker but couples the two modules.

2. **Analytics** — is tracking questionnaire completion important from day one, or can it wait for Phase 3? Could be as simple as a `dataLayer.push()` on completion for GTM.

3. **Multiple results** — can a questionnaire show multiple result cards simultaneously (e.g., BMI advice + referral info), or always exactly one?

4. ~~**Popup constant**~~ — **Resolved.** Uses slug-based lookup (`vragenlijst_slug => 'inclusie-check'`) instead of a post ID constant. Portable across environments.
