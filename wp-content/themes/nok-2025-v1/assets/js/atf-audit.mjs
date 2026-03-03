/**
 * ATF CSS Audit Tool
 *
 * Loads when ?dev-css&debug=true is present.
 * Reports which CSS selectors from the ATF stylesheet are actually used
 * by elements in the initial viewport, and which ATF selectors are unused.
 *
 * Outputs to console as a grouped, color-coded report.
 */

(function atfAudit() {
  'use strict';

  // Only run when explicitly requested
  const params = new URLSearchParams(window.location.search);
  if (!params.has('dev-css') || !params.has('debug')) return;

  const VIEWPORT_HEIGHT = window.innerHeight;
  const VIEWPORT_WIDTH = window.innerWidth;

  /**
   * Check if an element is in the initial viewport (above the fold).
   */
  function isAboveFold(el) {
    const rect = el.getBoundingClientRect();
    return rect.top < VIEWPORT_HEIGHT && rect.bottom > 0 && rect.left < VIEWPORT_WIDTH && rect.right > 0;
  }

  /**
   * Get all elements in the viewport at page load.
   */
  function getViewportElements() {
    const all = document.querySelectorAll('*');
    const inViewport = new Set();
    const belowFold = new Set();

    for (const el of all) {
      if (isAboveFold(el)) {
        inViewport.add(el);
      } else {
        belowFold.add(el);
      }
    }
    return { inViewport, belowFold };
  }

  /**
   * Extract selectors from the ATF inline stylesheet.
   */
  function getATFSelectors() {
    const style = document.getElementById('nok-critical-css');
    if (!style || !style.sheet) {
      console.warn('[ATF Audit] No #nok-critical-css style element found');
      return [];
    }

    const selectors = [];
    try {
      for (const rule of style.sheet.cssRules) {
        if (rule.selectorText) {
          // Split grouped selectors
          for (const sel of rule.selectorText.split(',')) {
            selectors.push(sel.trim());
          }
        } else if (rule.cssRules) {
          // @media or @supports
          for (const inner of rule.cssRules) {
            if (inner.selectorText) {
              for (const sel of inner.selectorText.split(',')) {
                selectors.push(sel.trim());
              }
            }
          }
        }
      }
    } catch (e) {
      console.warn('[ATF Audit] Cannot read cssRules (CORS?):', e.message);
    }

    return [...new Set(selectors)];
  }

  /**
   * Test if a selector matches any element in a set.
   */
  function selectorMatchesAny(selector, elements) {
    try {
      for (const el of elements) {
        if (el.matches(selector)) return true;
      }
    } catch {
      // Invalid selector (pseudo-elements, etc.)
      return null;
    }
    return false;
  }

  /**
   * Run the audit after page load.
   */
  function runAudit() {
    console.log('%c[ATF CSS Audit]', 'font-weight:bold;font-size:14px;color:#0070e0');
    console.log(`Viewport: ${VIEWPORT_WIDTH}×${VIEWPORT_HEIGHT}`);

    const { inViewport, belowFold } = getViewportElements();
    console.log(`Elements in viewport: ${inViewport.size}, below fold: ${belowFold.size}`);

    // --- Element inventory ---
    const viewportTags = {};
    const viewportClasses = new Set();
    for (const el of inViewport) {
      const tag = el.tagName.toLowerCase();
      viewportTags[tag] = (viewportTags[tag] || 0) + 1;
      for (const cls of el.classList) {
        viewportClasses.add(cls);
      }
    }

    console.groupCollapsed(`Custom elements in viewport`);
    const customEls = Object.entries(viewportTags)
      .filter(([tag]) => tag.includes('-'))
      .sort((a, b) => b[1] - a[1]);
    for (const [tag, count] of customEls) {
      console.log(`  ${tag}: ${count}`);
    }
    console.groupEnd();

    console.groupCollapsed(`NOK classes in viewport (${viewportClasses.size})`);
    const nokClasses = [...viewportClasses].filter(c => c.startsWith('nok-')).sort();
    for (const cls of nokClasses) {
      console.log(`  .${cls}`);
    }
    console.groupEnd();

    // --- ATF selector coverage ---
    const atfSelectors = getATFSelectors();
    console.log(`ATF selectors: ${atfSelectors.length}`);

    const used = [];
    const unused = [];
    const untestable = [];

    for (const sel of atfSelectors) {
      // Skip :root
      if (sel === ':root') continue;

      const matchesViewport = selectorMatchesAny(sel, inViewport);
      if (matchesViewport === null) {
        untestable.push(sel);
      } else if (matchesViewport) {
        used.push(sel);
      } else {
        // Check if it matches below-fold elements (it's in ATF but only used below)
        const matchesBelow = selectorMatchesAny(sel, belowFold);
        if (matchesBelow) {
          unused.push({ sel, reason: 'below fold only' });
        } else {
          unused.push({ sel, reason: 'no match on page' });
        }
      }
    }

    console.log(`%c  Used in viewport: ${used.length}`, 'color:green');
    console.log(`%c  Unused: ${unused.length}`, 'color:orange');
    console.log(`  Untestable (pseudo-elements): ${untestable.length}`);

    if (unused.length > 0) {
      console.groupCollapsed(`%cUnused ATF selectors (${unused.length})`, 'color:orange');
      const byReason = {};
      for (const { sel, reason } of unused) {
        if (!byReason[reason]) byReason[reason] = [];
        byReason[reason].push(sel);
      }
      for (const [reason, sels] of Object.entries(byReason)) {
        console.group(`${reason} (${sels.length})`);
        for (const s of sels.sort()) console.log(`  ${s}`);
        console.groupEnd();
      }
      console.groupEnd();
    }

    // --- Missing classes: in viewport but not covered by ATF ---
    console.groupCollapsed('Classes in viewport NOT matched by any ATF selector');
    for (const cls of nokClasses) {
      const inATF = atfSelectors.some(sel => sel.includes(cls));
      if (!inATF) {
        console.log(`%c  .${cls}`, 'color:red');
      }
    }
    console.groupEnd();
  }

  // Run after everything is loaded and painted
  if (document.readyState === 'complete') {
    setTimeout(runAudit, 100);
  } else {
    window.addEventListener('load', () => setTimeout(runAudit, 100));
  }
})();
