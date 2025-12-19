/**
 * @fileoverview Process Showcase - Tab-based content switcher with autoplay
 * @module nok-process-showcase
 * @version 1.0.0
 * @author hnldesign
 * @since 2025
 *
 * @description
 * Handles tab switching with fade transitions, autoplay cycling, and keyboard
 * navigation. Reads styling classes from data attributes to keep PHP as source
 * of truth for visual states.
 *
 * @example
 * <div data-requires="./nok-process-showcase.mjs"
 *      data-tab-class-active="nok-bg-darkerblue nok-text-white"
 *      data-tab-class-inactive="nok-bg-white nok-text-darkerblue">
 *   <article data-autoplay="true" data-autoplay-interval="5000">
 *     <nav role="tablist">
 *       <button role="tab" aria-selected="true">...</button>
 *     </nav>
 *     <div>
 *       <div role="tabpanel">...</div>
 *     </div>
 *   </article>
 * </div>
 */

import { logger } from './domule/core.log.mjs';
import { ViewportScroller } from './domule/util.ensure-visibility.mjs';

export const NAME = 'process-showcase';

// ============================================================================
// CONSTANTS
// ============================================================================

/** @type {number} Default autoplay interval in milliseconds */
const DEFAULT_INTERVAL = 5000;

/** @type {number} Panel fade transition duration in milliseconds */
const FADE_DURATION = 300;

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

/**
 * Instance tracking for cleanup
 * @type {WeakMap<HTMLElement, ProcessShowcase>}
 * @private
 */
const instances = new WeakMap();

// ============================================================================
// PROCESS SHOWCASE CLASS
// ============================================================================

/**
 * Controller for tab-based process showcase with autoplay.
 */
class ProcessShowcase {
    /**
     * @param {HTMLElement} container - Container element with data attributes
     */
    constructor(container) {
        this.container = container;
        this.article = container.querySelector('article[data-autoplay]');

        if (!this.article) {
            logger.warn(NAME, 'No article with data-autoplay found');
            return;
        }

        // Elements
        this.tabs = Array.from(this.article.querySelectorAll('[role="tab"]'));
        this.panels = Array.from(this.article.querySelectorAll('[role="tabpanel"]'));
        this.panelContainer = this.panels[0]?.parentElement;

        if (this.tabs.length === 0 || this.panels.length === 0) {
            logger.warn(NAME, 'No tabs or panels found');
            return;
        }

        // Class configuration from data attributes
        this.activeClasses = (container.dataset.tabClassActive || '').split(' ').filter(Boolean);
        this.inactiveClasses = (container.dataset.tabClassInactive || '').split(' ').filter(Boolean);
        this.arrowActiveClasses = (container.dataset.arrowClassActive || '').split(' ').filter(Boolean);
        this.arrowInactiveClasses = (container.dataset.arrowClassInactive || '').split(' ').filter(Boolean);

        // Autoplay configuration
        this.autoplayEnabled = this.article.dataset.autoplay === 'true';
        this.autoplayInterval = parseInt(this.article.dataset.autoplayInterval, 10) || DEFAULT_INTERVAL;
        this.autoplayTimer = null;
        this.autoplayStopped = false;

        // Current state
        this.currentIndex = 0;
        this.isUserInteraction = false;

        // Visibility correction (only scrolls if panel not in view)
        this._visibilityCorrector = new ViewportScroller(this.panelContainer || this.panels[0], {
            behavior: 'smooth',
            partial: true,
            extraOffset: 20
        });

        this._init();
    }

    /**
     * Initialize event listeners and autoplay
     * @private
     */
    _init() {
        // Tab click handlers
        this.tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                this._stopAutoplay();
                this._switchTo(index, true);
            });
        });

        // Keyboard navigation
        this.article.addEventListener('keydown', (e) => this._handleKeydown(e));

        // Start autoplay if enabled
        if (this.autoplayEnabled) {
            this._startAutoplay();
        }

        logger.info(NAME, `Initialized with ${this.tabs.length} tabs, autoplay: ${this.autoplayEnabled}`);
    }

    /**
     * Switch to tab at given index
     * @private
     * @param {number} index - Target tab index
     * @param {boolean} [isUserInteraction=false] - Whether triggered by user
     */
    _switchTo(index, isUserInteraction = false) {
        if (index === this.currentIndex || index < 0 || index >= this.tabs.length) {
            return;
        }

        this.isUserInteraction = isUserInteraction;

        const oldTab = this.tabs[this.currentIndex];
        const oldPanel = this.panels[this.currentIndex];
        const newTab = this.tabs[index];
        const newPanel = this.panels[index];

        // Update tab states
        this._setTabState(oldTab, false);
        this._setTabState(newTab, true);

        // Fade out old panel, then show new
        this._fadePanel(oldPanel, newPanel);

        // Update ARIA
        oldTab.setAttribute('aria-selected', 'false');
        oldTab.setAttribute('tabindex', '-1');
        newTab.setAttribute('aria-selected', 'true');
        newTab.setAttribute('tabindex', '0');

        this.currentIndex = index;
    }

    /**
     * Update tab button classes
     * @private
     * @param {HTMLElement} tab - Tab button element
     * @param {boolean} isActive - Whether tab should be active
     */
    _setTabState(tab, isActive) {
        const arrow = tab.querySelector('svg, .nok-icon');

        if (isActive) {
            tab.classList.remove(...this.inactiveClasses);
            tab.classList.add(...this.activeClasses, 'active');
            if (arrow && this.arrowActiveClasses.length) {
                arrow.classList.remove(...this.arrowInactiveClasses);
                arrow.classList.add(...this.arrowActiveClasses);
            }
        } else {
            tab.classList.remove(...this.activeClasses, 'active');
            tab.classList.add(...this.inactiveClasses);
            if (arrow && this.arrowInactiveClasses.length) {
                arrow.classList.remove(...this.arrowActiveClasses);
                arrow.classList.add(...this.arrowInactiveClasses);
            }
        }
    }

    /**
     * Fade transition between panels
     * @private
     * @param {HTMLElement} oldPanel - Panel to hide
     * @param {HTMLElement} newPanel - Panel to show
     */
    _fadePanel(oldPanel, newPanel) {
        const shouldEnsureVisible = this.isUserInteraction;

        // Fade out
        oldPanel.style.opacity = '0';
        oldPanel.style.transition = `opacity ${FADE_DURATION}ms ease-out`;

        setTimeout(() => {
            oldPanel.hidden = true;
            oldPanel.style.opacity = '';
            oldPanel.style.transition = '';

            // Fade in
            newPanel.hidden = false;
            newPanel.style.opacity = '0';
            newPanel.style.transition = `opacity ${FADE_DURATION}ms ease-in`;

            // Force reflow
            void newPanel.offsetHeight;

            newPanel.style.opacity = '1';

            setTimeout(() => {
                newPanel.style.opacity = '';
                newPanel.style.transition = '';

                // Ensure panel is visible (only scrolls if needed)
                if (shouldEnsureVisible) {
                    this._visibilityCorrector.ensureVisible();
                }

                this.isUserInteraction = false;
            }, FADE_DURATION);
        }, FADE_DURATION);
    }

    /**
     * Handle keyboard navigation
     * @private
     * @param {KeyboardEvent} e
     */
    _handleKeydown(e) {
        // Only handle when focus is on a tab
        if (!this.tabs.includes(document.activeElement)) {
            return;
        }

        let newIndex = this.currentIndex;

        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowRight':
                e.preventDefault();
                newIndex = (this.currentIndex + 1) % this.tabs.length;
                break;
            case 'ArrowUp':
            case 'ArrowLeft':
                e.preventDefault();
                newIndex = (this.currentIndex - 1 + this.tabs.length) % this.tabs.length;
                break;
            case 'Home':
                e.preventDefault();
                newIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                newIndex = this.tabs.length - 1;
                break;
            default:
                return;
        }

        this._stopAutoplay();
        this._switchTo(newIndex, true);
        this.tabs[newIndex].focus();
    }

    /**
     * Start autoplay cycling
     * @private
     */
    _startAutoplay() {
        if (this.autoplayStopped) return;

        this.autoplayTimer = setInterval(() => {
            const nextIndex = (this.currentIndex + 1) % this.tabs.length;
            this._switchTo(nextIndex, false);
        }, this.autoplayInterval);
    }

    /**
     * Permanently stop autoplay (after user interaction)
     * @private
     */
    _stopAutoplay() {
        if (this.autoplayStopped) return;

        this.autoplayStopped = true;
        clearInterval(this.autoplayTimer);
        this.autoplayTimer = null;

        logger.info(NAME, 'Autoplay stopped due to user interaction');
    }

    /**
     * Cleanup for SPA unmounting
     */
    destroy() {
        clearInterval(this.autoplayTimer);
        instances.delete(this.container);
        logger.info(NAME, 'Destroyed');
    }
}

// ============================================================================
// DOMULE API EXPORTS
// ============================================================================

/**
 * DOMule standard init function.
 * @param {NodeList|HTMLElement[]} elements - Container elements with data-requires
 * @returns {string} Status message
 */
export function init(elements) {
    let count = 0;

    elements.forEach(element => {
        if (!instances.has(element)) {
            const instance = new ProcessShowcase(element);
            instances.set(element, instance);
            count++;
        }
    });

    return `Initialized ${count} process showcase(s)`;
}

/**
 * Cleanup function for SPA unmounting.
 * @param {NodeList|HTMLElement[]} elements - Elements to clean up
 */
export function destroy(elements) {
    elements.forEach(element => {
        instances.get(element)?.destroy();
    });
}

/**
 * Module API for inter-module coordination.
 * @param {string} action - Action to perform
 * @param {*} payload - Action payload
 * @returns {*} Action result
 */
export function api(action, payload) {
    switch (action) {
        case 'instances':
            return instances;
        default:
            logger.warn(NAME, `Unknown API action: ${action}`);
    }
}
