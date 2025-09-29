/**
 * Universal single click toggler (c) 2025 Klaas Leussink / hnldesign
 *
 * @fileoverview Provides universal toggle functionality with click handling,
 * auto-hide timers, outside-click dismissal, and swipe-to-close gestures.
 *
 * @example
 * // Basic toggle
 * <div data-toggles="open" data-target=".dropdown">Toggle</div>
 *
 * @example
 * // Permanent toggle (no outside click dismissal)
 * <div data-toggles="open" data-target=".dropdown" data-toggle-permanent="true">Toggle</div>
 *
 * @example
 * // Multiple classes with auto-hide
 * <div data-toggles="open,active" data-target=".modal" data-autohide="5">Toggle</div>
 *
 * @example
 * // Untoggle functionality with swipe
 * <div data-untoggles="visible,expanded" data-target=".sidebar"
 *      data-swipe-close=".sidebar" data-swipe-direction="x">Close</div>
 */

import { singleClick } from "./modules/hnl.clickhandlers.mjs";

export const NAME = 'simpleToggler';

/**
 * Manages toggle instances and their cleanup
 */
class ToggleManager {
  constructor() {
    this.instances = new WeakMap();
  }

  /**
   * Registers a toggle instance for cleanup
   */
  register(element, cleanup) {
    if (!this.instances.has(element)) {
      this.instances.set(element, []);
    }
    this.instances.get(element).push(cleanup);
  }

  /**
   * Cleans up all toggle instances for given elements
   */
  cleanup(elements) {
    elements.forEach(element => {
      const cleanupFns = this.instances.get(element);
      if (cleanupFns) {
        cleanupFns.forEach(fn => fn());
        this.instances.delete(element);
      }
    });
  }
}

const toggleManager = new ToggleManager();

/**
 * Utility functions for class manipulation
 */
const ClassUtils = {
  /**
   * Checks if element has any of the specified classes
   * @param {Element} element - Target element
   * @param {string[]} classNames - Array of class names to check
   * @returns {boolean}
   */
  hasAny(element, classNames) {
    return classNames?.some(className => element.classList.contains(className)) ?? false;
  },

  /**
   * Toggles multiple classes on an element
   * @param {Element} element - Target element
   * @param {string[]} classNames - Array of class names to toggle
   */
  toggleMultiple(element, classNames) {
    if (!classNames) return;
    classNames.forEach(className => element.classList.toggle(className));
  },

  /**
   * Removes multiple classes from an element
   * @param {Element} element - Target element
   * @param {string[]} classNames - Array of class names to remove
   */
  removeMultiple(element, classNames) {
    if (!classNames) return;
    element.classList.remove(...classNames);
  }
};

/**
 * Creates a swipe-to-close handler for touch and mouse interactions
 *
 * @param {Element} element - Element to make swipeable
 * @param {Function} closeCallback - Callback to execute on close
 * @param {string} direction - Direction of swipe ('x' or 'y')
 * @param {number} min - Minimum swipe distance
 * @param {number} max - Maximum swipe distance
 * @returns {Function} Cleanup function
 *
 * @example
 * const cleanup = swipeToClose(modal, () => modal.close(), 'y', -300, 0);
 */
function swipeToClose(element, closeCallback, direction = 'y', min = -9999, max = 0) {
  if (!element) {
    console.warn('[ToggleModule] Swipe element not found');
    return () => {};
  }

  let start = 0, current = 0, isDragging = false;
  const touchOnly = false;
  let animationFrame = null;

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  function getCoords(e) {
    const source = e.touches?.[0] || e.changedTouches?.[0] || e;
    return { x: source.clientX, y: source.clientY };
  }

  function updateTransform(delta) {
    if (animationFrame) return;

    animationFrame = requestAnimationFrame(() => {
      const clampedDelta = clamp(delta, min, max);
      element.style.transform = direction === 'x'
          ? `translate3d(${clampedDelta}px, 0, 0)`
          : `translate3d(0, ${clampedDelta}px, 0)`;
      animationFrame = null;
    });
  }

  function drag(e) {
    current = getCoords(e)[direction];
    isDragging = current !== start;
    if (!isDragging) return;

    e.preventDefault();
    if (element.style.userSelect !== "none") {
      element.style.userSelect = "none";
    }
    element.style.transition = "none";
    updateTransform(current - start);
  }

  function pointerUp(e) {
    if (animationFrame) {
      cancelAnimationFrame(animationFrame);
      animationFrame = null;
    }

    if (isDragging) {
      const threshold = (direction === 'x' ? element.clientWidth : element.clientHeight) / 4;
      const shouldClose = Math.abs(start - current) > threshold;

      element.style.transition = "transform 0.25s ease-out";
      element.removeEventListener('transitionend', resetStyles);
      element.addEventListener('transitionend', resetStyles, { once: true });

      element.style.transform = shouldClose
          ? ""
          : direction === 'x' ? `translate3d(0, 0, 0)` : `translate3d(0, 0, 0)`;

      if (shouldClose) closeCallback(element);
    }

    cleanup();
    isDragging = false;
  }

  function resetStyles() {
    element.style.userSelect = "";
    element.style.transition = "";
    element.style.transform = "";
  }

  function pointerDown(e) {
    isDragging = false;
    start = getCoords(e)[direction];

    const moveEvent = e.type === "touchstart" ? "touchmove" : "mousemove";
    const endEvent = e.type === "touchstart" ? "touchend" : "mouseup";

    document.addEventListener(moveEvent, drag, { passive: false });
    document.addEventListener(endEvent, pointerUp, { passive: true });
  }

  function cleanup() {
    document.removeEventListener("touchmove", drag);
    document.removeEventListener("touchend", pointerUp);
    document.removeEventListener("mousemove", drag);
    document.removeEventListener("mouseup", pointerUp);
  }

  element.addEventListener("touchstart", pointerDown);
  if (!touchOnly) element.addEventListener("mousedown", pointerDown);

  return () => {
    element.removeEventListener("touchstart", pointerDown);
    element.removeEventListener("mousedown", pointerDown);
    cleanup();
    if (animationFrame) {
      cancelAnimationFrame(animationFrame);
    }
  };
}

/**
 * Resolves target element based on selector string
 *
 * @param {string} targetSelector - Target selector or special keywords
 * @param {Element} toggler - The toggler element
 * @param {Element} fallback - Fallback element if no target found
 * @returns {Element|null}
 */
function resolveTarget(targetSelector, toggler, fallback) {
  if (!targetSelector) return fallback;

  switch (targetSelector) {
    case '_self': return toggler;
    case 'parent': return toggler.parentNode;
    default:
      try {
        return document.querySelector(targetSelector);
      } catch (error) {
        console.warn(`[ToggleModule] Invalid selector: ${targetSelector}`);
        return fallback;
      }
  }
}

/**
 * Creates a toggle handler for a single toggler element
 *
 * @param {Element} toggler - The toggle trigger element
 * @param {Element} defaultTarget - Default target if no specific target defined
 * @returns {Function} Cleanup function
 */
function createToggleHandler(toggler, defaultTarget) {
  const toggles = toggler.dataset.toggles?.split(',').map(s => s.trim()).filter(Boolean) || null;
  const untoggles = toggler.dataset.untoggles?.split(',').map(s => s.trim()).filter(Boolean) || null;

  if (!toggles && !untoggles) {
    console.warn('[ToggleModule] No toggle or untoggle classes specified');
    return () => {};
  }

  const autoHide = parseInt(toggler.dataset.autohide) || 0;
  const target = resolveTarget(toggler.dataset.target, toggler, defaultTarget);
  const isPermanent = toggler.dataset.togglePermanent?.toLowerCase() === "true";

  if (!target) {
    console.warn('[ToggleModule] Target element not found');
    return () => {};
  }

  let autoHideTimeout = null;
  const cleanupFunctions = [];

  /**
   * Handles clicks outside the toggle area
   */
  function handleClickOutside(event) {
    const clickedInsideTarget = target.contains(event?.target);
    const clickedInsideToggler = toggler.contains(event?.target);

    if (!event || (!clickedInsideTarget && !clickedInsideToggler)) {
      let shouldHide = false;

      if (toggles && ClassUtils.hasAny(target, toggles)) {
        ClassUtils.removeMultiple(target, toggles);
        shouldHide = true;
      } else if (untoggles && ClassUtils.hasAny(target, untoggles)) {
        ClassUtils.removeMultiple(target, untoggles);
        shouldHide = true;
      }

      if (shouldHide) {
        clearTimeout(autoHideTimeout);
        autoHideTimeout = null;
        document.removeEventListener("click", handleClickOutside);
      }
    } else if (autoHideTimeout && (clickedInsideTarget || clickedInsideToggler)) {
      // Restart autohide timer on interaction
      clearTimeout(autoHideTimeout);
      autoHideTimeout = setTimeout(handleClickOutside, autoHide * 1000);
    }
  }

  /**
   * Main toggle click handler
   */
  function handleToggleClick() {
    const childClick = toggler.contains(event.target) && event.target !== toggler;
    //Only direct clicks, or if 'data-no-children="true"' and click is on a child
    if (!toggler.contains(event.target) || (toggler.dataset.noChildren && childClick)) {
      return;
    }

    if (toggles) {
      ClassUtils.toggleMultiple(target, toggles);
    } else if (untoggles && ClassUtils.hasAny(target, untoggles)) {
      ClassUtils.removeMultiple(target, untoggles);
    }

    clearTimeout(autoHideTimeout);
    autoHideTimeout = null;

    // Set up outside click handling if not permanent
    if (!isPermanent) {
      document.addEventListener("click", handleClickOutside);
    }

    // Set up auto-hide timer
    if (autoHide > 0) {
      autoHideTimeout = setTimeout(handleClickOutside, autoHide * 1000);
    }
  }

  // Register click handler
  const clickCleanup = singleClick(toggler, handleToggleClick);
  cleanupFunctions.push(clickCleanup);

  // Set up swipe functionality if specified
  if (toggler.dataset.swipeClose) {
    const swipeTarget = document.querySelector(toggler.dataset.swipeClose);
    if (swipeTarget) {
      const swipeLimits = toggler.dataset.swipeLimits?.split(',').map(Number) || [-9999, 0];
      const swipeDirection = toggler.dataset.swipeDirection || 'y';

      const swipeCleanup = swipeToClose(
          swipeTarget,
          () => {
            if (toggles && ClassUtils.hasAny(target, toggles)) {
              ClassUtils.removeMultiple(target, toggles);
              clearTimeout(autoHideTimeout);
              autoHideTimeout = null;
            }
          },
          swipeDirection,
          ...swipeLimits
      );
      cleanupFunctions.push(swipeCleanup);
    }
  }

  // Return cleanup function
  return () => {
    clearTimeout(autoHideTimeout);
    document.removeEventListener("click", handleClickOutside);
    cleanupFunctions.forEach(cleanup => cleanup());
  };
}

/**
 * Initializes toggle functionality for given elements
 *
 * @param {Element[]} elements - Array of container elements to search for togglers
 *
 * @example
 * // Initialize on document body
 * init([document.body]);
 *
 * @example
 * // Initialize on specific containers
 * const containers = document.querySelectorAll('.toggle-container');
 * init(Array.from(containers));
 */
export function init(elements) {
  if (!Array.isArray(elements)) {
    console.warn('[ToggleModule] Expected array of elements');
    return;
  }

  // Clean up existing instances
  toggleManager.cleanup(elements);

  elements.forEach(element => {
    if (!(element instanceof Element)) {
      console.warn('[ToggleModule] Invalid element provided');
      return;
    }

    try {
      const togglers = element.querySelectorAll('[data-toggles],[data-untoggles]');

      togglers.forEach(toggler => {
        const cleanup = createToggleHandler(toggler, element);
        toggleManager.register(element, cleanup);
      });
    } catch (error) {
      console.error('[ToggleModule] Error initializing togglers:', error);
    }
  });
}

/**
 * Cleanup function for manual cleanup of toggle instances
 *
 * @param {Element[]} elements - Elements to cleanup
 *
 * @example
 * cleanup([document.body]);
 */
export function cleanup(elements) {
  toggleManager.cleanup(elements);
}