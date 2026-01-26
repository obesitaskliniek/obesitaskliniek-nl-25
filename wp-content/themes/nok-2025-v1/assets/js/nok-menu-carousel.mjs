/**
 * @fileoverview Embedded Menu Carousel - Horizontal scroll navigation with smooth slide transitions
 * @module nok-menu-carousel
 * @version 2.0.0
 * @author Klaas Leussink / hnldesign
 * @since 2025
 *
 * @description
 * Handles horizontal menu carousels with scroll-snap behavior and smooth navigation
 * to target elements within slides. Prevents double-scroll issues by separating
 * slide positioning from viewport scrolling.
 *
 * @example
 * <div class="nok-nav-carousel" data-requires="./nok-menu-carousel.mjs">
 *   <div class="nok-nav-carousel__slide">
 *     <a class="nok-nav-menu-item" href="#section1">Section 1</a>
 *   </div>
 * </div>
 */

import {singleClick} from "./domule/modules/hnl.clickhandlers.mjs";
import {logger} from "./domule/core.log.mjs";
import {debounceThis} from "./domule/util.debounce.mjs";

export const NAME = 'menuCarousel';

// ============================================================================
// CONSTANTS
// ============================================================================

/** @const {number} Time to wait after scroll before considering it stopped (ms) */
const SCROLL_STOP_THRESHOLD = 150;

// ============================================================================
// MODULE STATE
// ============================================================================

/**
 * Tracks carousel scroll state to prevent navigation during scrolling.
 * Using WeakMap prevents memory leaks when elements are removed from DOM.
 * @type {WeakMap<HTMLElement, {busy: boolean, timeoutId: number}>}
 * @private
 */
const carouselState = new WeakMap();

/**
 * Stores scroll event handlers for cleanup.
 * @type {WeakMap<HTMLElement, Function>}
 * @private
 */
const scrollHandlers = new WeakMap();

// ============================================================================
// PRIVATE UTILITIES
// ============================================================================

/**
 * Determines if carousel has scrolled to a snap point.
 * Snap detection differs for horizontal vs vertical scroll.
 *
 * @private
 * @param {HTMLElement} carousel - The carousel element
 * @returns {boolean} True if at a snap point
 */
function isAtSnapPoint(carousel) {
    const isHorizontal = carousel.scrollWidth > carousel.clientWidth;

    if (isHorizontal) {
        return carousel.scrollLeft % carousel.offsetWidth === 0;
    }
    return carousel.scrollTop % carousel.offsetHeight === 0;
}

/**
 * Handles scroll events on carousel.
 * Sets busy state during scroll and clears it after stop threshold.
 * Uses debouncing to reduce class toggle frequency.
 *
 * @private
 * @param {Event} e - Scroll event
 */
function handleScroll(e) {
    const carousel = e.target;
    const state = carouselState.get(carousel);

    if (!state) return;

    const atSnap = isAtSnapPoint(carousel);
    const timeout = atSnap ? 0 : SCROLL_STOP_THRESHOLD;

    // Mark busy and add visual class
    state.busy = true;
    carousel.classList.add('is-scrolling');

    // Clear previous timeout
    if (state.timeoutId) {
        clearTimeout(state.timeoutId);
    }

    // Set new timeout to clear busy state
    state.timeoutId = setTimeout(() => {
        state.busy = false;
        carousel.classList.remove('is-scrolling');
    }, timeout);
}

/**
 * Navigates to target element within carousel.
 * Two-phase scroll prevents browser confusion:
 * 1. Instant scroll within slide to position target
 * 2. Smooth scroll carousel viewport to slide
 *
 * @private
 * @param {Event} e - Click event
 * @param {HTMLElement} carousel - Parent carousel element
 * @param {HTMLElement} targetElement - Element to scroll to
 * @param {HTMLElement} closestSlide - Slide containing target
 */
function navigateToTarget(e, carousel, targetElement, closestSlide) {
    e.preventDefault();

    const state = carouselState.get(carousel);
    if (!state || state.busy) {
        logger.info(NAME, 'Navigation blocked: carousel is scrolling');
        return;
    }

    // Phase 1: Position target within slide (instant)
    closestSlide.scrollTo({
        top: targetElement.offsetTop - closestSlide.offsetTop,
        behavior: 'instant'
    });

    // Phase 2: Scroll carousel to slide (smooth)
    requestAnimationFrame(() => {
        closestSlide.scrollIntoView({behavior: 'smooth'});
    });
}

/**
 * Sets up click handlers for all menu links within a slide.
 *
 * @private
 * @param {HTMLElement} carousel - Parent carousel element
 * @param {HTMLElement} slide - Slide to process
 */
function setupSlide(carousel, slide) {
    //@todo: this doesn't understand popup links yet, it seems.
    const links = slide.querySelectorAll('a.nok-nav-menu-item');

    links.forEach(link => {
        const href = link.getAttribute('href');

        if (!href || !href.startsWith('#')) {
            logger.warn(NAME, 'Invalid link href (must start with #):', link);
            return;
        }

        const targetElement = document.getElementById(href.slice(1));

        if (!targetElement) {
            logger.warn(NAME, `Target not found: ${href}`);
            logger.warn(NAME, link);
            return;
        }

        const closestSlide = targetElement.closest('.nok-nav-carousel__slide');

        if (!closestSlide) {
            logger.warn(NAME, `Target ${href} not inside a slide`);
            return;
        }

        singleClick(link, (e) => {
            navigateToTarget(e, carousel, targetElement, closestSlide);
        });
    });
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initializes menu carousel navigation.
 * Sets up scroll handlers and link click navigation for each carousel.
 *
 * @param {HTMLElement[]} elements - Carousel elements with data-requires
 * @returns {string} Initialization status
 *
 * @example
 * // Automatic initialization via data-requires
 * <div class="nok-nav-carousel" data-requires="./nok-menu-carousel.mjs">
 *   <div class="nok-nav-carousel__slide">
 *     <a class="nok-nav-menu-item" href="#target">Link</a>
 *   </div>
 * </div>
 */
export function init(elements) {
    elements.forEach(carousel => {
        // Initialize state
        carouselState.set(carousel, {
            busy: false,
            timeoutId: null
        });

        // Create debounced scroll handler
        const debouncedScroll = debounceThis(handleScroll, {
            threshold: 50,
            execStart: true,
            execWhile: true,
            execDone: true
        });

        // Store handler for cleanup
        scrollHandlers.set(carousel, debouncedScroll);

        // Attach scroll listener
        carousel.addEventListener('scroll', debouncedScroll);

        // Setup all slides
        const slides = carousel.querySelectorAll('.nok-nav-carousel__slide');
        slides.forEach(slide => setupSlide(carousel, slide));

        logger.info(NAME, `Initialized carousel with ${slides.length} slide(s)`);
    });

    return `Initialized ${elements.length} carousel(s)`;
}

/**
 * Cleanup function for SPA unmounting or module destruction.
 * Removes all event listeners and clears state maps.
 *
 * @example
 * // In SPA route change
 * import {destroy} from './nok-menu-carousel.mjs';
 * destroy();
 */
export function destroy() {
    // Remove all scroll listeners
    for (const [carousel, handler] of scrollHandlers.entries()) {
        carousel.removeEventListener('scroll', handler);

        // Clear any pending timeouts
        const state = carouselState.get(carousel);
        if (state?.timeoutId) {
            clearTimeout(state.timeoutId);
        }
    }

    // Clear maps (WeakMaps would auto-clear, but explicit for consistency)
    scrollHandlers.clear();

    logger.info(NAME, 'Module destroyed');
}