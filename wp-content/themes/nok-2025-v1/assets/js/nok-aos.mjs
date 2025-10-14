/**
 * @fileoverview Lightweight Scroll Animation Observer - IntersectionObserver-based visibility tracking
 * @module nok-aos
 * @version 2.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / Klaas Leussink / hnldesign
 * @since 2025
 *
 * @description
 * Tracks element visibility via IntersectionObserver and updates data attributes.
 * Supports per-element thresholds, dynamic element detection, and tall-element handling.
 * Integrates with DOMule module system while maintaining standalone API.
 *
 * @example
 * // DOMule usage
 * <div data-requires="nok-aos.mjs" data-aos data-aos-threshold="75%">
 *
 * @example
 * // Standalone usage
 * import AOS from './nok-aos.mjs';
 * const aos = AOS.init({ threshold: 0.5, once: true });
 */

import {logger} from './domule/core.log.mjs';

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
  mirror: false,
  anchorPlacement: 'top-bottom',
  disableMutationObserver: false,
  threshold: 0.1,
};

/** @private */
const THRESHOLD_STEPS = 101;

/** @private */
const MAX_THRESHOLD_RATIO = 0.99;

/** @private */
const ELEMENT_NODE = 1;

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

/** @type {WeakMap<HTMLElement, number>} Cached parsed thresholds per element */
const thresholdCache = new WeakMap();

/** @type {AOS|null} Global instance for DOMule integration */
let globalInstance = null;

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
  el.dataset[options.dataName] = 'false';
  if (!el.dataset.aosOnce) {
    el.dataset.aosOnce = String(options.once);
  }
}

/**
 * Calculates effective threshold for tall elements.
 * Prevents unreachable thresholds when element exceeds viewport height.
 * @private
 * @param {number} requestedThreshold - User-requested threshold
 * @param {number} viewportHeight - Viewport height in pixels
 * @param {number} elementHeight - Element height in pixels
 * @returns {number} Capped threshold value
 */
function calculateEffectiveThreshold(requestedThreshold, viewportHeight, elementHeight) {
  const maxRatio = Math.min(1, viewportHeight / elementHeight);
  return Math.min(requestedThreshold, maxRatio * MAX_THRESHOLD_RATIO);
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
   * @param {string} [options.selector] - Element selector
   * @param {string} [options.dataName] - Data attribute for visibility state
   * @param {number} [options.offset] - Trigger offset in pixels
   * @param {boolean} [options.once] - Animate only once
   * @param {boolean} [options.mirror] - Reverse on scroll up
   * @param {string} [options.anchorPlacement] - Trigger point
   * @param {boolean} [options.disableMutationObserver] - Disable dynamic detection
   * @param {number|string} [options.threshold] - Visibility threshold
   */
  constructor(options = {}) {
    this.options = { ...DEFAULTS, ...options };
    this.elements = [];
    this.observer = null;
    this.mutationObserver = null;
    this.initialized = false;
  }

  /**
   * Initializes observer and tracks elements.
   * @public
   */
  init() {
    if (this.initialized) {
      logger.warn(NAME, 'Already initialized');
      return;
    }

    this.elements = Array.from(document.querySelectorAll(this.options.selector));

    if (!this.elements.length) {
      logger.info(NAME, 'No elements found');
      return;
    }

    this.elements.forEach(el => initElement(el, this.options));
    this._setupIntersectionObserver();

    if (!this.options.disableMutationObserver) {
      this._setupMutationObserver();
    }

    this.initialized = true;
    logger.info(NAME, `Tracking ${this.elements.length} element(s)`);
  }

  /**
   * Creates IntersectionObserver with dynamic threshold handling.
   * @private
   */
  _setupIntersectionObserver() {
    this.observer = new IntersectionObserver(
        entries => {
          entries.forEach(entry => {
            const el = entry.target;
            const requestedThreshold = getElementThreshold(el, this.options.threshold);

            const effectiveThreshold = calculateEffectiveThreshold(
                requestedThreshold,
                entry.rootBounds?.height ?? window.innerHeight,
                entry.boundingClientRect.height
            );

            if (entry.intersectionRatio >= effectiveThreshold) {
              el.dataset[this.options.dataName] = 'true';
            } else if (this.options.mirror && el.dataset.aosOnce !== 'true') {
              el.dataset[this.options.dataName] = 'false';
            }
          });
        },
        {
          rootMargin: this._calculateRootMargin(),
          threshold: Array.from({ length: THRESHOLD_STEPS }, (_, i) => i / (THRESHOLD_STEPS - 1))
        }
    );

    this.elements.forEach(el => this.observer.observe(el));
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
          node.querySelectorAll(this.options.selector)
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
   * Adds element to tracking.
   * @private
   * @param {HTMLElement} el - Element to track
   */
  _addElement(el) {
    if (this.elements.includes(el)) return;

    initElement(el, this.options);
    this.elements.push(el);
    this.observer?.observe(el);

    logger.info(NAME, 'Added dynamic element');
  }

  /**
   * Calculates rootMargin based on anchor placement.
   * @private
   * @returns {string} CSS rootMargin value
   */
  _calculateRootMargin() {
    const [anchor, placement] = this.options.anchorPlacement.split('-');
    const offset = this.options.offset;

    return `${anchor === 'top' ? offset : 0}px 0px ${placement === 'bottom' ? offset : 0}px 0px`;
  }

  /**
   * Refreshes observer (lightweight).
   * @public
   */
  refresh() {
    this.observer?.disconnect();
    this.elements = Array.from(document.querySelectorAll(this.options.selector));
    this._setupIntersectionObserver();
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
   * Cleans up observers and resets state.
   * @public
   */
  destroy() {
    this.observer?.disconnect();
    this.mutationObserver?.disconnect();

    this.elements.forEach(el => {
      delete el.dataset[this.options.dataName];
      thresholdCache.delete(el);
    });

    this.elements = [];
    this.initialized = false;

    logger.info(NAME, 'Destroyed');
  }
}

// ============================================================================
// DOMULE API EXPORTS
// ============================================================================

/**
 * DOMule standard init function.
 * Elements can pass options via data attributes.
 * @param {NodeList|HTMLElement[]} elements - Elements with data-requires
 * @returns {string} Status message
 *
 * @example
 * <div data-requires="nok-aos.mjs"
 *      data-aos
 *      data-aos-threshold="75%"
 *      data-aos-once="true">
 */
export function init(elements) {
  if (globalInstance) {
    logger.warn(NAME, 'Global instance exists, use api("refresh")');
    return 'Already initialized';
  }

  // Extract options from first element or use defaults
  const firstEl = elements[0];
  const options = firstEl ? {
    selector: firstEl.dataset.aosSelector || DEFAULTS.selector,
    once: firstEl.dataset.aosOnce === 'true',
    mirror: firstEl.dataset.aosMirror === 'true',
    offset: parseInt(firstEl.dataset.aosOffset) || DEFAULTS.offset,
    threshold: firstEl.dataset.aosThreshold || DEFAULTS.threshold,
  } : {};

  globalInstance = new AOS(options);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => globalInstance.init());
  } else {
    globalInstance.init();
  }

  return `Initialized with ${elements.length} container(s)`;
}

/**
 * Module API for inter-module coordination.
 * @param {string} action - Action name
 * @param {...*} args - Action arguments
 * @returns {*} Action-specific return value
 *
 * @example
 * ModuleRegistry.waitFor('aos')
 *   .then(aos => aos.api('refresh'));
 */
export function api(action, ...args) {
  if (!globalInstance) {
    logger.warn(NAME, 'Not initialized');
    return null;
  }

  switch (action) {
    case 'refresh':
      globalInstance.refresh();
      break;

    case 'refreshHard':
      globalInstance.refreshHard();
      break;

    case 'getElements':
      return globalInstance.elements;

    case 'isTracking':
      return args[0] ? globalInstance.elements.includes(args[0]) : false;

    default:
      logger.warn(NAME, `Unknown action: ${action}`);
      return null;
  }
}

/**
 * DOMule cleanup for SPA unmounting.
 * @public
 */
export function destroy() {
  if (globalInstance) {
    globalInstance.destroy();
    globalInstance = null;
  }
}

// ============================================================================
// STANDALONE API
// ============================================================================

/**
 * Standalone init for non-DOMule usage.
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

    logger.info(NAME, 'Standalone instance initialized.');

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => aos.init());
    } else {
      aos.init();
    }

    return aos;
  }
};