/**
 * @fileoverview Lightweight Scroll Animation Observer - IntersectionObserver-based visibility tracking
 * @module nok-aos
 * @version 4.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / Klaas Leussink / hnldesign
 * @since 2025
 *
 * @description
 * Tracks element visibility via IntersectionObserver and updates data attributes.
 * Supports per-element thresholds, dynamic element detection, and viewport-relative
 * threshold calculation.
 *
 * Threshold semantics: the threshold represents the fraction of the **viewport**
 * that the element's visible area must cover before triggering. A threshold of 0.35 means
 * "trigger when 35% of the viewport height is covered by this element" — consistent
 * regardless of element height. Elements fully visible in the viewport always trigger,
 * even if they're too small to meet the viewport-coverage threshold.
 *
 * @example
 * import AOS from './nok-aos.mjs';
 * const aos = AOS.init({ threshold: 0.5, once: true });
 */

import {logger} from "./domule/core.log.min.mjs";

export const NAME = 'aos';

// ============================================================================
// CONSTANTS
// ============================================================================

/** @private */
const DEFAULTS = {
  selector: '[data-aos]',
  dataName: 'visible',
  offset: 120,
  once: false,
  disableMutationObserver: false,
  threshold: 0.1,
};

/** @private Number of discrete threshold steps for IntersectionObserver */
const THRESHOLD_STEPS = 101;

/** @private */
const ELEMENT_NODE = 1;

/** @private Resize debounce delay in ms */
const RESIZE_DEBOUNCE_MS = 200;

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

/** @type {WeakMap<HTMLElement, number>} Cached parsed thresholds per element */
const thresholdCache = new WeakMap();

// ============================================================================
// PRIVATE UTILITIES
// ============================================================================

/**
 * Parses threshold value from string percentage or number.
 * @private
 * @param {string|number} value - Threshold value ('50%' or 0.5)
 * @returns {number} Normalized threshold (0-1)
 */
function parseThreshold(value) {
  return typeof value === 'string' && value.endsWith('%')
      ? parseFloat(value) / 100
      : parseFloat(value);
}

/**
 * Gets or computes cached threshold for element.
 * @private
 * @param {HTMLElement} el - Target element
 * @param {number} defaultThreshold - Fallback threshold
 * @returns {number} Cached or computed threshold
 */
function getElementThreshold(el, defaultThreshold) {
  if (!thresholdCache.has(el)) {
    const value = el.dataset.aosThreshold ?? defaultThreshold;
    thresholdCache.set(el, parseThreshold(value));
  }
  return thresholdCache.get(el);
}

/**
 * Initializes element for AOS tracking.
 * @private
 * @param {HTMLElement} el - Element to initialize
 * @param {Object} options - Configuration options
 */
function initElement(el, options) {
  el.classList.add('nok-aos');
  if (el.dataset[options.dataName] !== 'true') {
    el.dataset[options.dataName] = 'false';
  }
  if (!el.dataset.aosOnce) {
    el.dataset.aosOnce = String(options.once);
  }
}

/**
 * Tests whether an element meets visibility criteria using viewport-relative ratio.
 * Pure function — used by both IntersectionObserver callback and synchronous initial check.
 *
 * @private
 * @param {number} visibleHeight - Height of the element's visible portion in px
 * @param {number} elementHeight - Total element height in px
 * @param {number} viewportHeight - Effective viewport height in px (includes rootMargin)
 * @param {number} threshold - Viewport-coverage fraction required (0-1)
 * @returns {boolean} Whether the element meets visibility criteria
 */
function isVisible(visibleHeight, elementHeight, viewportHeight, threshold) {
  if (viewportHeight <= 0) return false;

  // Small elements fully in view always qualify, even if they can't fill
  // the threshold fraction of the viewport
  if (elementHeight > 0 && visibleHeight >= elementHeight * 0.99) return true;

  return (visibleHeight / viewportHeight) >= threshold;
}

// ============================================================================
// CORE CLASS
// ============================================================================

/**
 * Animation on Scroll controller using IntersectionObserver.
 * @class
 * @private
 */
class AOS {
  /**
   * Creates AOS instance.
   * @param {Object} options - Configuration options
   * @param {string} [options.selector='[data-aos]'] - Element selector
   * @param {string} [options.dataName='visible'] - Data attribute name for visibility state
   * @param {number} [options.offset=120] - Trigger offset in pixels (expands viewport bounds)
   * @param {boolean} [options.once=false] - Animate only once, then unobserve
   * @param {boolean} [options.disableMutationObserver=false] - Disable dynamic element detection
   * @param {number|string} [options.threshold=0.1] - Viewport-coverage fraction (0-1) required to trigger
   */
  constructor(options = {}) {
    this.options = {...DEFAULTS, ...options};

    /** @type {Set<HTMLElement>} Tracked elements */
    this.elements = new Set();

    /** @type {IntersectionObserver|null} */
    this.observer = null;

    /** @type {MutationObserver|null} */
    this.mutationObserver = null;

    /** @type {boolean} */
    this.initialized = false;

    // Bound handlers for cleanup
    this._onResize = null;
    this._onPageShow = null;
  }

  /**
   * Initializes observer, tracks elements, and binds window event listeners.
   * @public
   */
  init() {
    if (this.initialized) {
      logger.warn(NAME, 'Already initialized');
      return;
    }

    const els = document.querySelectorAll(this.options.selector);

    if (!els.length) {
      logger.info(NAME, 'No elements found');
      return;
    }

    els.forEach(el => {
      initElement(el, this.options);
      this.elements.add(el);
    });

    this._setupIntersectionObserver();
    this._checkInitialVisibility();

    if (!this.options.disableMutationObserver) {
      this._setupMutationObserver();
    }

    this._bindWindowEvents();

    this.initialized = true;
    logger.info(NAME, `Tracking ${this.elements.size} element(s)`);
  }

  /**
   * Creates IntersectionObserver with viewport-relative threshold handling.
   * @private
   */
  _setupIntersectionObserver() {
    const rootMargin = `${this.options.offset}px 0px ${this.options.offset}px 0px`;

    this.observer = new IntersectionObserver(
        entries => {
          entries.forEach(entry => {
            const el = entry.target;
            const threshold = getElementThreshold(el, this.options.threshold);

            // rootBounds includes rootMargin inflation, so the effective "viewport"
            // is larger than the physical screen by the configured offset amount.
            const viewportHeight = entry.rootBounds?.height ?? window.innerHeight;
            const visibleHeight = entry.intersectionRect.height;
            const elementHeight = entry.boundingClientRect.height;

            if (isVisible(visibleHeight, elementHeight, viewportHeight, threshold)) {
              el.dataset[this.options.dataName] = 'true';
              if (el.dataset.aosOnce === 'true') {
                this.observer.unobserve(el);
              }
            } else if (el.dataset.aosOnce !== 'true') {
              el.dataset[this.options.dataName] = 'false';
            }
          });
        },
        {
          rootMargin,
          threshold: Array.from({length: THRESHOLD_STEPS}, (_, i) => i / (THRESHOLD_STEPS - 1))
        }
    );

    this.elements.forEach(el => {
      // Don't re-observe elements that already triggered with once mode
      if (el.dataset.aosOnce === 'true' && el.dataset[this.options.dataName] === 'true') return;
      this.observer.observe(el);
    });
  }

  /**
   * Synchronous visibility check for elements already in viewport at init time.
   * Prevents the flash-of-invisible-content that occurs when IO callback delivery
   * is deferred (initial load, bfcache restore).
   *
   * Uses the same `isVisible()` function as the IO callback, with rootMargin
   * accounted for by expanding the effective viewport height.
   * @private
   */
  _checkInitialVisibility() {
    const viewportHeight = window.innerHeight + (this.options.offset * 2);

    this.elements.forEach(el => {
      // Already visible (e.g. from a previous observation) — skip
      if (el.dataset[this.options.dataName] === 'true') return;

      const rect = el.getBoundingClientRect();
      // Element is outside expanded viewport — skip
      if (rect.bottom < -this.options.offset || rect.top > window.innerHeight + this.options.offset) return;

      const visibleTop = Math.max(rect.top, -this.options.offset);
      const visibleBottom = Math.min(rect.bottom, window.innerHeight + this.options.offset);
      const visibleHeight = Math.max(0, visibleBottom - visibleTop);
      const threshold = getElementThreshold(el, this.options.threshold);

      if (isVisible(visibleHeight, rect.height, viewportHeight, threshold)) {
        el.dataset[this.options.dataName] = 'true';
        if (el.dataset.aosOnce === 'true') {
          this.observer?.unobserve(el);
        }
      }
    });
  }

  /**
   * Sets up MutationObserver for dynamically added elements.
   * @private
   */
  _setupMutationObserver() {
    this.mutationObserver = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
          if (node.nodeType !== ELEMENT_NODE) return;

          if (node.matches(this.options.selector)) {
            this._addElement(node);
          }
          node.querySelectorAll?.(this.options.selector)
              .forEach(el => this._addElement(el));
        });
      });
    });

    this.mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  /**
   * Binds resize and pageshow (bfcache) handlers.
   * @private
   */
  _bindWindowEvents() {
    // Debounced resize — re-observe with current viewport dimensions
    let resizeTimer;
    this._onResize = () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => this.refresh(), RESIZE_DEBOUNCE_MS);
    };
    window.addEventListener('resize', this._onResize);

    // bfcache restore — IO state is lost, do a synchronous visibility pass
    this._onPageShow = (e) => {
      if (e.persisted) {
        logger.info(NAME, 'bfcache restore — re-checking visibility');
        this._checkInitialVisibility();
      }
    };
    window.addEventListener('pageshow', this._onPageShow);
  }

  /**
   * Removes window event listeners.
   * @private
   */
  _unbindWindowEvents() {
    if (this._onResize) {
      window.removeEventListener('resize', this._onResize);
      this._onResize = null;
    }
    if (this._onPageShow) {
      window.removeEventListener('pageshow', this._onPageShow);
      this._onPageShow = null;
    }
  }

  /**
   * Adds element to tracking.
   * @private
   * @param {HTMLElement} el - Element to track
   */
  _addElement(el) {
    if (this.elements.has(el)) return;

    initElement(el, this.options);
    this.elements.add(el);
    this.observer?.observe(el);

    logger.info(NAME, 'Added dynamic element');
  }

  /**
   * Refreshes observer (lightweight). Re-queries DOM for elements matching
   * the selector and re-creates the IntersectionObserver.
   * @public
   */
  refresh() {
    this.observer?.disconnect();

    // Re-query DOM — may include new elements
    document.querySelectorAll(this.options.selector).forEach(el => {
      if (!this.elements.has(el)) {
        initElement(el, this.options);
        this.elements.add(el);
      }
    });

    this._setupIntersectionObserver();
    this._checkInitialVisibility();
    logger.info(NAME, 'Refreshed');
  }

  /**
   * Full reinitialization (expensive).
   * @public
   */
  refreshHard() {
    this.destroy();
    this.init();
  }

  /**
   * Cleans up observers, window listeners, and resets state.
   * @public
   */
  destroy() {
    this.observer?.disconnect();
    this.mutationObserver?.disconnect();
    this._unbindWindowEvents();

    this.elements.forEach(el => {
      delete el.dataset[this.options.dataName];
      thresholdCache.delete(el);
    });

    this.elements = new Set();
    this.observer = null;
    this.mutationObserver = null;
    this.initialized = false;

    logger.info(NAME, 'Destroyed');
  }
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Creates and initializes an AOS instance.
 * @param {Object} options - Configuration options
 * @returns {AOS} AOS instance
 *
 * @example
 * import AOS from './nok-aos.mjs';
 * const aos = AOS.init({ threshold: 0.5, once: true });
 */
export default {
  init(options = {}) {
    const aos = new AOS(options);

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => aos.init());
    } else {
      aos.init();
    }

    return aos;
  }
};
