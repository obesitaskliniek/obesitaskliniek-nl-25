/**
 * @fileoverview Accordion Component - Polyfills <details> animation for older browsers
 * @module nok-accordion
 * @version 2.0.0
 * @author hnldesign
 * @since 2025
 *
 * @description
 * Progressive enhancement for <details> elements. Uses native CSS `interpolate-size`
 * when available (Chrome 123+, Safari 17.4+), falls back to WAAPI animation for
 * older browsers. Handles grouped accordions (via name attribute), scroll correction,
 * and cleanup for SPA environments.
 *
 * Features:
 * - Native CSS animation when supported
 * - WAAPI fallback for Safari 10.1+, Chrome 61+
 * - Automatic scroll correction on expand
 * - Grouped accordion behavior (closes siblings)
 * - Memory-safe cleanup via WeakMap
 *
 * @example
 * // Basic usage
 * <div data-requires="nok-accordion.mjs">
 *   <details name="faq">
 *     <summary>Question 1</summary>
 *     <div class="accordion-content">Answer</div>
 *   </details>
 * </div>
 */

import {ViewportScroller} from "./domule/util.ensure-visibility.mjs";
import {logger} from "./domule/core.log.mjs";

export const NAME = 'accordion';

// ============================================================================
// CONSTANTS
// ============================================================================

/** @type {number} Default animation duration in milliseconds */
const DEFAULT_DURATION = 750;

/** @type {string} Default easing function for WAAPI animations */
const DEFAULT_EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';

/** @type {boolean} Browser support for native interpolate-size */
const CSS_SUPPORT = CSS ? CSS.supports('interpolate-size', 'allow-keywords') : false;

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

/**
 * Groups of accordions (by name attribute or individual element).
 * Each group tracks busy state to prevent concurrent animations.
 * @type {Map<string|HTMLElement, {busy: boolean, accordions: Set<HTMLElement>}>}
 * @private
 */
const AccordionGroups = new Map();

/**
 * Element-to-instance mapping for quick lookup and automatic cleanup.
 * @type {WeakMap<HTMLElement, Accordion>}
 * @private
 */
const AccordionInstances = new WeakMap();

// ============================================================================
// PRIVATE UTILITIES
// ============================================================================

/**
 * Creates a new accordion group state object.
 * @private
 * @returns {{busy: boolean, accordions: Set<HTMLElement>}}
 */
function makeGroup() {
  return {
    busy: false,
    accordions: new Set(),
  };
}

// ============================================================================
// ACCORDION CLASS
// ============================================================================

/**
 * Accordion controller for <details> elements.
 * Handles animation, grouping, and scroll correction.
 * @private
 */
class Accordion {
  /**
   * @param {HTMLDetailsElement} element - The <details> element to enhance
   */
  constructor(element) {
    this.element = element;

    // Extract configuration from CSS custom properties
    const computedStyle = window.getComputedStyle(this.element);
    this.transitionDuration = parseInt(computedStyle.getPropertyValue('--animation-duration')) || DEFAULT_DURATION;
    this.transitionEasing = computedStyle.getPropertyValue('--animation-timing') || DEFAULT_EASING;

    // Setup scroll correction
    this._visibilityCorrector = new ViewportScroller(element, {
      behavior: 'smooth',
      extraOffset: 20
    });

    if (CSS_SUPPORT) {
      this._initNativeMode();
    } else {
      this._initPolyfillMode();
    }
  }

  /**
   * Initialize for browsers with native interpolate-size support.
   * Only handles scroll correction on toggle.
   * @private
   */
  _initNativeMode() {
    this.element.addEventListener('toggle', () => this._maintainVisibility());
  }

  /**
   * Initialize polyfill mode for older browsers.
   * Sets up WAAPI animations and group management.
   * @private
   */
  _initPolyfillMode() {
    this.summary = this.element.querySelector('summary');
    this.content = this.element.querySelector('.accordion-content');
    this.animation = null;
    this.isClosing = false;
    this.isExpanding = false;

    // Validate required elements
    if (!this.summary || !this.content) {
      logger.warn(NAME, '<details> missing <summary> or .accordion-content');
      return;
    }

    // Register click handler
    this.summary.addEventListener('click', (event) => this._toggle(event));

    // Register instance
    AccordionInstances.set(this.element, this);

    // Setup group management
    this.name = this.element.getAttribute('name');
    this.groupRef = this.name || this.element;

    let group = AccordionGroups.get(this.groupRef);
    if (!group) {
      group = makeGroup();
      AccordionGroups.set(this.groupRef, group);
    }
    group.accordions.add(this.element);
    this.group = group;

    this.opened = this.element.open || false;
  }

  /**
   * Ensures element visibility after native toggle completes.
   * Cancels if user scrolls before transition ends.
   * @private
   */
  _maintainVisibility() {
    const onTransitionEnd = () => {
      if (!this.element.open) return;
      this._visibilityCorrector.ensureVisible();
    };

    this.element.addEventListener('transitionend', onTransitionEnd, {once: true});

    // Cancel if user scrolls before transition completes
    window.addEventListener('scroll', () => {
      this.element.removeEventListener('transitionend', onTransitionEnd);
    }, {once: true});
  }

  /**
   * Animates height change using WAAPI.
   * @private
   * @param {string} startHeight - Starting height (e.g., '100px')
   * @param {string} endHeight - Target height (e.g., '200px')
   * @param {Function} onfinish - Callback when animation completes
   */
  _animateHeight(startHeight, endHeight, onfinish) {
    this.animation?.finish();
    this.group.busy = true;

    this.animation = this.element.animate(
        {height: [startHeight, endHeight]},
        {
          duration: this.transitionDuration,
          easing: this.transitionEasing,
          fill: 'forwards'
        }
    );

    this.animation.onfinish = () => {
      this.group.busy = false;
      onfinish();
    };
  }

  /**
   * Toggles accordion open/closed state.
   * @private
   * @param {Event} event - Click event from summary
   */
  _toggle(event) {
    event.preventDefault();

    if (this.group.busy) return;

    this.element.style.overflow = 'hidden';

    if (this.isClosing || !this.element.open) {
      this._open();
    } else if (this.isExpanding || this.element.open) {
      this._collapse();
    }
  }

  /**
   * Opens the accordion with animation.
   * Closes siblings if part of named group.
   * @private
   */
  _open() {
    this.currentScrollY = window.scrollY;

    // Close siblings in named groups
    if (this.group.accordions.size > 1) {
      if (this.name) {
        this.element.removeAttribute('name');
      }

      for (const otherElement of this.group.accordions) {
        if (otherElement !== this.element && otherElement.open) {
          AccordionInstances.get(otherElement)?._collapse();
        }
      }
    }

    this.element.style.height = `${this.element.offsetHeight}px`;
    this.element.open = true;

    // Double-frame defer fixes Safari layout bug
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => this._expand());
    });
  }

  /**
   * Collapses the accordion with animation.
   * @private
   */
  _collapse() {
    this.isClosing = true;
    this.element.classList.add('accordion-closing');

    const startHeight = `${this.element.offsetHeight}px`;
    const endHeight = `${this.summary.offsetHeight}px`;

    this._animateHeight(startHeight, endHeight, () => this._onAnimationFinish(false));
  }

  /**
   * Expands the accordion with animation.
   * @private
   */
  _expand() {
    this.isExpanding = true;
    this.element.classList.remove('accordion-closing');

    const startHeight = `${this.element.offsetHeight}px`;
    const endHeight = `${this.summary.offsetHeight + this.content.offsetHeight}px`;

    this._animateHeight(startHeight, endHeight, () => this._onAnimationFinish(true));
  }

  /**
   * Cleanup after animation completes.
   * @private
   * @param {boolean} isOpen - Final open state
   */
  _onAnimationFinish(isOpen) {
    this.element.open = isOpen;
    this.summary.setAttribute('aria-expanded', String(isOpen));

    this.animation = null;
    this.isClosing = false;
    this.isExpanding = false;
    this.element.classList.remove('accordion-closing');

    this.element.style.height = '';
    this.element.style.overflow = '';

    if (this.name) {
      this.element.setAttribute('name', this.name);
    }

    if (isOpen && this.currentScrollY === window.scrollY) {
      this._visibilityCorrector.ensureVisible();
    }
  }

  /**
   * Cleanup method for SPA unmounting.
   * Removes listeners and group references.
   */
  destroy() {
    if (!CSS_SUPPORT) {
      this.summary?.removeEventListener('click', this._toggle);
      this.animation?.cancel();
      this.group?.accordions.delete(this.element);

      if (this.group?.accordions.size === 0) {
        AccordionGroups.delete(this.groupRef);
      }

      AccordionInstances.delete(this.element);
    }
  }
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initializes accordion functionality for all <details> elements within containers.
 *
 * @param {NodeList|HTMLElement[]} elements - Container elements with <details> children
 * @returns {string|undefined} Status message
 *
 * @example
 * // HTML structure
 * <div data-requires="nok-accordion.mjs">
 *   <details name="group1">
 *     <summary>Item 1</summary>
 *     <div class="accordion-content">Content 1</div>
 *   </details>
 *   <details name="group1">
 *     <summary>Item 2</summary>
 *     <div class="accordion-content">Content 2</div>
 *   </details>
 * </div>
 */
export function init(elements) {
  elements.forEach(element => {
    element.querySelectorAll('details').forEach((details) => {
      new Accordion(details);
    });
  });

  if (CSS_SUPPORT) {
    logger.info(NAME, 'Using native CSS interpolate-size');
    return 'Native CSS support detected';
  }

  logger.info(NAME, 'Using WAAPI polyfill');
}

/**
 * Cleanup function for SPA unmounting.
 * Removes all listeners and clears group state.
 *
 * @example
 * // On route change
 * import {destroy} from './nok-accordion.mjs';
 * destroy();
 */
export function destroy() {
  for (const [groupRef, group] of AccordionGroups.entries()) {
    for (const element of group.accordions) {
      AccordionInstances.get(element)?.destroy();
    }
  }
  AccordionGroups.clear();

  logger.info(NAME, 'Cleanup complete');
}