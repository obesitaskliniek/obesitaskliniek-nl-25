/**
 * Universal single click toggler (c) 2025 Klaas Leussink / hnldesign
 *
 * @fileoverview Provides universal toggle functionality with click handling,
 * auto-hide timers, outside-click dismissal, and swipe-to-close gestures.
 * Supports class-based and attribute-based toggles, sets, and unsets with independent targets.
 *
 * @example
 * // Basic class toggle (backward compatible)
 * <div data-toggles="open" data-target=".dropdown">Toggle</div>
 *
 * @example
 * // Explicit class toggle
 * <div data-toggles-class="open,active" data-class-target=".modal">Toggle</div>
 *
 * @example
 * // Set classes (add only, never remove)
 * <div data-sets-class="active,visible" data-class-target=".panel">Activate</div>
 *
 * @example
 * // Unset classes (remove only, never add)
 * <div data-unsets-class="open,active" data-class-target=".modal">Close</div>
 *
 * @example
 * // Set attributes (add only)
 * <div data-sets-attribute="expanded,active"
 *      data-sets-attribute-value="true,true"
 *      data-attribute-target=".sidebar">Open</div>
 *
 * @example
 * // Unset attributes (remove only) - auto-targets elements with matching attr=value
 * <div data-unsets-attribute="expanded,visible"
 *      data-unsets-attribute-value="true,true">Close</div>
 *
 * @example
 * // Combined operations on different targets
 * <div data-sets-class="highlight" data-class-target=".item"
 *      data-unsets-class="open" data-unset-target=".menu">Select Item</div>
 *
 * @example
 * // Permanent toggle (disables outside-click dismissal)
 * <div data-toggles-class="open" data-class-target=".dropdown"
 *      data-toggle-permanent="true">Toggle</div>
 *
 * @example
 * // Toggle only if-present="false" - prevents untoggling when class already present from another toggler
 * <div data-toggles-class="sidebar-open" data-class-target=".navigation"
 *      data-toggles-class-if-present="false">Menu Item A</div>
 * <div data-toggles-class="sidebar-open" data-class-target=".navigation"
 *      data-toggles-class-if-present="false">Menu Item B</div>
 *
 * @example
 * // Independent if-present for classes and attributes
 * <div data-toggles-class="open" data-class-target=".modal"
 *      data-toggles-class-if-present="false"
 *      data-toggles-attribute="expanded" data-toggles-attribute-value="true"
 *      data-attribute-target=".sidebar"
 *      data-toggles-attribute-if-present="false">Toggle</div>
 *
 * @example
 * // Click-outside behavior - defaults to unset-class only
 * <div data-toggles-class="open" data-class-target=".modal">Toggle</div>
 *
 * @example
 * // Click-outside - unset attributes only
 * <div data-toggles-attribute="visible" data-toggles-attribute-value="true"
 *      data-attribute-target=".panel"
 *      data-click-outside="unset-attribute">Toggle</div>
 *
 * @example
 * // Click-outside - unset both classes and attributes
 * <div data-toggles-class="open" data-class-target=".modal"
 *      data-toggles-attribute="expanded" data-toggles-attribute-value="true"
 *      data-attribute-target=".sidebar"
 *      data-click-outside="unset-class,unset-attribute">Toggle</div>
 *
 * @example
 * // Auto-hide after timeout
 * <div data-toggles-class="open,active" data-class-target=".modal"
 *      data-autohide="5">Toggle</div>
 *
 * @example
 * // With swipe-to-close
 * <div data-toggles-class="visible" data-class-target=".panel"
 *      data-swipe-close=".panel" data-swipe-direction="x">Toggle</div>
 *
 * @example
 * // Hover behavior (default) - activates on pointerenter, deactivates on pointerleave
 * <div data-toggles-class="open" data-class-target=".dropdown"
 *      data-on-hover="true">Hover Me</div>
 *
 * @example
 * // Click behavior - explicitly disable hover
 * <div data-toggles-class="open" data-class-target=".dropdown"
 *      data-on-hover="false">Click Me</div>
 */

import {singleClick} from "./domule/modules/hnl.clickhandlers.mjs";
import {logger} from "./domule/core.log.mjs";

export const NAME = 'simpleToggler';

// ============================================================================
// UTILITIES - Class and Attribute Operations
// ============================================================================

/**
 * Class manipulation utilities.
 * @private
 */
const ClassUtils = {
    hasAny: (el, names) => names?.some(n => el.classList.contains(n)) ?? false,
    toggleMultiple: (el, names) => names?.forEach(n => el.classList.toggle(n)),
    addMultiple: (el, names) => names && el.classList.add(...names),
    removeMultiple: (el, names) => names && el.classList.remove(...names),
    findWithClasses: (names) => names ? Array.from(document.querySelectorAll(names.map(c => `.${c}`).join(','))) : []
};

/**
 * Attribute manipulation utilities.
 * @private
 */
const AttrUtils = {
    hasAny: (el, names, vals) => {
        if (!names || !vals) return false;
        return names.some((n, i) => {
            const attrName = 'data-' + n;
            const expectedVal = vals[i] || '';
            return el.getAttribute(attrName) === expectedVal;
        });
    },
    toggleMultiple: (el, names, vals) => {
        if (!names || !vals) return;
        names.forEach((n, i) => {
            const attr = 'data-' + n;
            const val = vals[i] || '';
            if (el.getAttribute(attr) === val) {
                el.removeAttribute(attr);
            } else {
                el.setAttribute(attr, val);
            }
        });
    },
    setMultiple: (el, names, vals) => {
        if (!names || !vals) return;
        names.forEach((n, i) => {
            const attr = 'data-' + n;
            const val = vals[i] || '';
            el.setAttribute(attr, val);
        });
    },
    removeMultiple: (el, names) => {
        if (!names) return;
        names.forEach(n => el.removeAttribute('data-' + n));
    },
    findWithAttrs: (names, vals) => {
        if (!names || !vals) return [];
        const selectors = names.map((n, i) => {
            const attrName = 'data-' + n;
            const attrVal = vals[i] || '';
            return `[${attrName}="${attrVal}"]`;
        });
        return Array.from(document.querySelectorAll(selectors.join(',')));
    }
};

// ============================================================================
// STATE TRACKING - WeakMaps for Memory-Safe Element Association
// ============================================================================

/**
 * Tracks last toggler for each target element (prevents if-present conflicts).
 * Separate maps for class and attribute operations.
 * @private
 */
const lastTogglers = {
    class: new WeakMap(),
    attribute: new WeakMap()
};

/**
 * Cleanup registry for all toggle instances.
 * @private
 */
class ToggleManager {
    constructor() {
        this.instances = new WeakMap();
    }

    register(element, cleanup) {
        if (!this.instances.has(element)) {
            this.instances.set(element, []);
        }
        this.instances.get(element).push(cleanup);
    }

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

// ============================================================================
// CONFIGURATION PARSING
// ============================================================================

/**
 * Parses data attributes into structured config.
 * @private
 * @param {HTMLElement} toggler - Toggle trigger element
 * @returns {Object} Parsed configuration
 */
function parseConfig(toggler) {
    const split = (str) => str?.split(',').map(s => s.trim()).filter(Boolean) || null;

    // Deprecation warnings
    if (toggler.dataset.untoggles || toggler.dataset.untogglesClass || toggler.dataset.untogglesAttribute) {
        logger.warn(NAME, 'data-untoggles* is deprecated and non-functional. Use data-unsets* instead.');
        logger.warn(NAME, toggler);
    }

    return {
        classes: {
            toggle: split(toggler.dataset.togglesClass),
            set: split(toggler.dataset.setsClass),
            unset: split(toggler.dataset.unsetsClass),
            target: toggler.dataset.classTarget || toggler.dataset.target || null,
            unsetTarget: toggler.dataset.unsetTarget || null,
            ifPresent: toggler.dataset.togglesClassIfPresent?.toLowerCase()
        },
        attributes: {
            toggle: split(toggler.dataset.togglesAttribute),
            toggleValues: toggler.dataset.togglesAttributeValue?.split(',').map(s => s.trim()) || null,
            set: split(toggler.dataset.setsAttribute),
            setValues: toggler.dataset.setsAttributeValue?.split(',').map(s => s.trim()) || null,
            unset: split(toggler.dataset.unsetsAttribute),
            unsetValues: toggler.dataset.unsetsAttributeValue?.split(',').map(s => s.trim()) || null,
            target: toggler.dataset.attributeTarget || null,
            unsetTarget: toggler.dataset.unsetAttributeTarget || null,
            ifPresent: toggler.dataset.togglesAttributeIfPresent?.toLowerCase()
        },
        legacy: {
            toggle: split(toggler.dataset.toggles)
        },
        behavior: {
            clickOutside: toggler.dataset.clickOutside?.split(',').map(s => s.trim()) || null,
            autoHide: parseInt(toggler.dataset.autohide) || 0,
            permanent: toggler.dataset.togglePermanent?.toLowerCase() === "true",
            noChildren: toggler.dataset.noChildren,
            onHover: toggler.dataset.onHover?.toLowerCase() !== "false"
        },
        swipe: {
            target: toggler.dataset.swipeClose || null,
            direction: toggler.dataset.swipeDirection || 'y',
            limits: toggler.dataset.swipeLimits?.split(',').map(Number) || [-9999, 0]
        }
    };
}

// ============================================================================
// TARGET RESOLUTION
// ============================================================================

/**
 * Resolves selector to DOM element.
 * @private
 * @param {string} selector - CSS selector or keyword (_self, parent)
 * @param {HTMLElement} toggler - Reference element
 * @param {HTMLElement} fallback - Default if selector fails
 * @returns {HTMLElement|null}
 */
function resolveTarget(selector, toggler, fallback) {
    if (!selector) return fallback;
    if (selector === '_self') return toggler;
    if (selector === 'parent') return toggler.parentNode;
    try {
        return document.querySelector(selector);
    } catch {
        return fallback;
    }
}

/**
 * Resolves all targets for toggle operations.
 * Handles auto-targeting (finding elements by class/attr when no target specified).
 * @private
 * @param {Object} config - Parsed configuration
 * @param {HTMLElement} toggler - Toggle trigger
 * @param {HTMLElement} defaultTarget - Fallback target
 * @returns {Object} Resolved targets
 */
function resolveTargets(config, toggler, defaultTarget) {
    const targets = {
        class: {toggle: null, set: null, unset: null},
        attribute: {toggle: null, set: null, unset: null}
    };

    // Legacy fallback
    if (config.legacy.toggle && !config.classes.toggle && !config.attributes.toggle) {
        config.classes.toggle = config.legacy.toggle;
    }

    // Class targets
    if (config.classes.toggle) {
        targets.class.toggle = resolveTarget(config.classes.target || config.attributes.target, toggler, defaultTarget);
    }
    if (config.classes.set) {
        targets.class.set = resolveTarget(config.classes.target, toggler, defaultTarget);
    }
    if (config.classes.unset) {
        // Auto-target: find elements by class if no explicit target
        if (config.classes.unsetTarget || config.classes.target) {
            targets.class.unset = resolveTarget(config.classes.unsetTarget || config.classes.target, toggler, defaultTarget);
        } else {
            targets.class.unset = ClassUtils.findWithClasses(config.classes.unset);
        }
    }

    // Attribute targets
    if (config.attributes.toggle) {
        targets.attribute.toggle = resolveTarget(config.attributes.target, toggler, defaultTarget);
    }
    if (config.attributes.set) {
        targets.attribute.set = resolveTarget(config.attributes.target, toggler, defaultTarget);
    }
    if (config.attributes.unset) {
        // Auto-target: find elements by attribute if no explicit target
        if (config.attributes.unsetTarget || config.attributes.target) {
            targets.attribute.unset = resolveTarget(config.attributes.unsetTarget || config.attributes.target, toggler, defaultTarget);
        } else if (config.attributes.unsetValues) {
            targets.attribute.unset = AttrUtils.findWithAttrs(config.attributes.unset, config.attributes.unsetValues);
        }
    }

    return targets;
}

// ============================================================================
// TOGGLE STATE MANAGEMENT
// ============================================================================

/**
 * Manages toggle state and operations.
 * @private
 */
class ToggleState {
    constructor(config, targets, toggler) {
        this.config = config;
        this.targets = targets;
        this.toggler = toggler;
        this.isAutoTargeted = {
            classUnset: Array.isArray(targets.class.unset),
            attributeUnset: Array.isArray(targets.attribute.unset)
        };
        this._cachedTargets = null;
    }

    /**
     * Gets current targets (with auto-target refresh).
     * Caches result per operation to avoid repeated DOM queries.
     * @private
     */
    getCurrentTargets() {
        if (this._cachedTargets) return this._cachedTargets;

        this._cachedTargets = {
            class: {
                toggle: this.targets.class.toggle,
                set: this.targets.class.set,
                unset: this.isAutoTargeted.classUnset && this.config.classes.unset
                    ? ClassUtils.findWithClasses(this.config.classes.unset)
                    : this.targets.class.unset
            },
            attribute: {
                toggle: this.targets.attribute.toggle,
                set: this.targets.attribute.set,
                unset: this.isAutoTargeted.attributeUnset && this.config.attributes.unset && this.config.attributes.unsetValues
                    ? AttrUtils.findWithAttrs(this.config.attributes.unset, this.config.attributes.unsetValues)
                    : this.targets.attribute.unset
            }
        };

        return this._cachedTargets;
    }

    /**
     * Clears cached targets (call before operations that may change DOM).
     * @private
     */
    clearCache() {
        this._cachedTargets = null;
    }

    /**
     * Checks if-present logic for given operation type.
     * @private
     * @param {'class'|'attribute'} type - Operation type
     * @returns {boolean} True if toggle should be skipped
     */
    checkIfPresent(type) {
        const targets = this.getCurrentTargets();
        const target = targets[type].toggle;
        if (!target) return false;

        const config = type === 'class' ? this.config.classes : this.config.attributes;
        if (config.ifPresent !== 'false') return false;

        const targetArray = Array.isArray(target) ? target : [target];
        const isActive = targetArray.some(t => {
            if (type === 'class') {
                return ClassUtils.hasAny(t, config.toggle);
            } else {
                return AttrUtils.hasAny(t, config.toggle, config.toggleValues);
            }
        });

        if (!isActive) return false;

        const lastToggler = lastTogglers[type].get(target);
        if (lastToggler === this.toggler) return false;

        // Active from different toggler - skip toggle, update tracking
        lastTogglers[type].set(target, this.toggler);
        return true;
    }

    /**
     * Updates last toggler tracking after successful toggle.
     * @private
     * @param {'class'|'attribute'} type - Operation type
     */
    updateTracking(type) {
        const targets = this.getCurrentTargets();
        const target = targets[type].toggle;
        if (!target) return;

        const config = type === 'class' ? this.config.classes : this.config.attributes;
        if (config.ifPresent !== 'false') return;

        const targetArray = Array.isArray(target) ? target : [target];
        const isActive = targetArray.some(t => {
            if (type === 'class') {
                return ClassUtils.hasAny(t, config.toggle);
            } else {
                return AttrUtils.hasAny(t, config.toggle, config.toggleValues);
            }
        });

        if (isActive) {
            lastTogglers[type].set(target, this.toggler);
        } else {
            lastTogglers[type].delete(target);
        }
    }

    /**
     * Checks if any toggle operation is currently active.
     * @returns {boolean}
     */
    isActive() {
        const targets = this.getCurrentTargets();
        const check = (target, fn) => {
            if (!target) return false;
            return (Array.isArray(target) ? target : [target]).some(fn);
        };

        return check(targets.class.toggle, el =>
            this.config.classes.toggle && ClassUtils.hasAny(el, this.config.classes.toggle)
        ) || check(targets.attribute.toggle, el =>
            this.config.attributes.toggle && AttrUtils.hasAny(el, this.config.attributes.toggle, this.config.attributes.toggleValues)
        );
    }

    /**
     * Applies toggle/set/unset operations.
     * @param {Object} options - Operation flags
     * @param {boolean} options.skipClassToggle - Skip class toggle
     * @param {boolean} options.skipAttributeToggle - Skip attribute toggle
     */
    apply(options = {}) {
        this.clearCache();
        const targets = this.getCurrentTargets();
        const process = (target, fn) => target && (Array.isArray(target) ? target : [target]).forEach(fn);

        // Toggles
        if (!options.skipClassToggle && this.config.classes.toggle) {
            process(targets.class.toggle, t => ClassUtils.toggleMultiple(t, this.config.classes.toggle));
        }
        if (!options.skipAttributeToggle && this.config.attributes.toggle) {
            process(targets.attribute.toggle, t =>
                AttrUtils.toggleMultiple(t, this.config.attributes.toggle, this.config.attributes.toggleValues)
            );
        }

        // Sets
        if (this.config.classes.set) {
            process(targets.class.set, t => ClassUtils.addMultiple(t, this.config.classes.set));
        }
        if (this.config.attributes.set) {
            process(targets.attribute.set, t =>
                AttrUtils.setMultiple(t, this.config.attributes.set, this.config.attributes.setValues)
            );
        }

        // Unsets
        if (this.config.classes.unset) {
            process(targets.class.unset, t => ClassUtils.removeMultiple(t, this.config.classes.unset));
        }
        if (this.config.attributes.unset) {
            process(targets.attribute.unset, t => AttrUtils.removeMultiple(t, this.config.attributes.unset));
        }
    }

    /**
     * Removes toggle state (for click-outside/swipe-to-close).
     * @param {Object} options - Removal scope
     * @param {boolean} options.class - Remove class toggles
     * @param {boolean} options.attribute - Remove attribute toggles
     */
    remove(options = {class: true, attribute: true}) {
        this.clearCache();
        const targets = this.getCurrentTargets();
        const process = (target, fn) => target && (Array.isArray(target) ? target : [target]).forEach(fn);

        if (options.class && this.config.classes.toggle) {
            process(targets.class.toggle, t => ClassUtils.removeMultiple(t, this.config.classes.toggle));
        }
        if (options.attribute && this.config.attributes.toggle) {
            process(targets.attribute.toggle, t => AttrUtils.removeMultiple(t, this.config.attributes.toggle));
        }
    }

    /**
     * Checks if element is within any target.
     * @param {HTMLElement} element - Element to check
     * @returns {boolean}
     */
    containsElement(element) {
        if (!element) return false;
        const targets = this.getCurrentTargets();
        const check = (target) => {
            if (!target) return false;
            return (Array.isArray(target) ? target : [target]).some(t =>
                t === element || t.contains(element)
            );
        };
        return check(targets.class.toggle) || check(targets.class.set) || check(targets.class.unset) ||
            check(targets.attribute.toggle) || check(targets.attribute.set) || check(targets.attribute.unset);
    }
}

// ============================================================================
// GESTURE HANDLING - Swipe-to-Close
// ============================================================================

/**
 * Creates swipe-to-close gesture handler.
 * @private
 * @param {HTMLElement} element - Element to attach gesture
 * @param {Function} closeCallback - Called on successful swipe
 * @param {string} direction - 'x' or 'y' axis
 * @param {number} min - Minimum position
 * @param {number} max - Maximum position
 * @returns {Function} Cleanup function
 */
function createSwipeHandler(element, closeCallback, direction = 'y', min = -9999, max = 0) {
    if (!element) return () => {};

    let start = 0, current = 0, isDragging = false, animationFrame = null;
    const clamp = (v, mn, mx) => Math.min(mx, Math.max(mn, v));
    const getCoords = (e) => (e.touches?.[0] || e.changedTouches?.[0] || e);

    const updateTransform = (delta) => {
        if (animationFrame) return;
        animationFrame = requestAnimationFrame(() => {
            element.style.transform = direction === 'x'
                ? `translate3d(${clamp(delta, min, max)}px, 0, 0)`
                : `translate3d(0, ${clamp(delta, min, max)}px, 0)`;
            animationFrame = null;
        });
    };

    const drag = (e) => {
        current = getCoords(e)[direction];
        isDragging = current !== start;
        if (!isDragging) return;
        e.preventDefault();
        element.style.userSelect = "none";
        element.style.transition = "none";
        updateTransform(current - start);
    };

    const pointerUp = () => {
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        if (isDragging) {
            const threshold = (direction === 'x' ? element.clientWidth : element.clientHeight) / 4;
            const shouldClose = Math.abs(start - current) > threshold;
            element.style.transition = "transform 0.25s ease-out";
            element.addEventListener('transitionend', () => {
                element.style.userSelect = "";
                element.style.transition = "";
                element.style.transform = "";
            }, {once: true});
            element.style.transform = shouldClose ? "" : "translate3d(0, 0, 0)";
            if (shouldClose) closeCallback(element);
        }
        cleanup();
        isDragging = false;
    };

    const pointerDown = (e) => {
        isDragging = false;
        start = getCoords(e)[direction];
        document.addEventListener("pointermove", drag, {passive: false});
        document.addEventListener("pointerup", pointerUp, {passive: true});
    };

    const cleanup = () => {
        document.removeEventListener("pointermove", drag);
        document.removeEventListener("pointerup", pointerUp);
    };

    element.addEventListener("pointerdown", pointerDown);

    return () => {
        element.removeEventListener("pointerdown", pointerDown);
        cleanup();
        if (animationFrame) cancelAnimationFrame(animationFrame);
    };
}

// ============================================================================
// MAIN TOGGLE HANDLER - Orchestrates All Behaviors
// ============================================================================

/**
 * Creates complete toggle handler for element.
 * @private
 * @param {HTMLElement} toggler - Toggle trigger element
 * @param {HTMLElement} defaultTarget - Fallback target
 * @returns {Function} Cleanup function
 */
function createToggleHandler(toggler, defaultTarget) {
    const config = parseConfig(toggler);
    const targets = resolveTargets(config, toggler, defaultTarget);
    const state = new ToggleState(config, targets, toggler);

    // Early exit if no operations configured
    if (!config.classes.toggle && !config.classes.set && !config.classes.unset &&
        !config.attributes.toggle && !config.attributes.set && !config.attributes.unset) {
        return () => {};
    }

    // Early exit if no valid targets
    if (!targets.class.toggle && !targets.class.set && !targets.class.unset &&
        !targets.attribute.toggle && !targets.attribute.set && !targets.attribute.unset) {
        return () => {};
    }

    const hasToggleAction = config.classes.toggle || config.attributes.toggle;
    let autoHideTimeout = null;
    let isOutsideListenerAttached = false;
    const cleanupFunctions = [];

    /**
     * Handles click-outside dismissal.
     * @private
     */
    const handleClickOutside = (event) => {
        const clickedInside = toggler.contains(event?.target) || state.containsElement(event?.target);

        if (!event || !clickedInside) {
            const shouldRemoveClass = !config.behavior.clickOutside ||
                config.behavior.clickOutside.includes('unset-class');
            const shouldRemoveAttr = config.behavior.clickOutside?.includes('unset-attribute') || false;

            if (shouldRemoveClass || shouldRemoveAttr) {
                state.remove({class: shouldRemoveClass, attribute: shouldRemoveAttr});
                clearTimeout(autoHideTimeout);
                autoHideTimeout = null;
                document.removeEventListener("click", handleClickOutside);
                isOutsideListenerAttached = false;
            }
        } else if (autoHideTimeout) {
            // Reset auto-hide timer on inside click
            clearTimeout(autoHideTimeout);
            autoHideTimeout = setTimeout(handleClickOutside, config.behavior.autoHide * 1000);
        }
    };

    const handleToggleClick = (event) => {
        const childClick = toggler.contains(event.target) && event.target !== toggler;
        if (!toggler.contains(event.target) || (config.behavior.noChildren && childClick)) return;

        // Check if-present logic
        const skipClassToggle = state.checkIfPresent('class');
        const skipAttributeToggle = state.checkIfPresent('attribute');

        // Apply operations
        state.apply({skipClassToggle, skipAttributeToggle});

        // Update tracking
        if (!skipClassToggle) state.updateTracking('class');
        if (!skipAttributeToggle) state.updateTracking('attribute');

        // Cleanup timers
        clearTimeout(autoHideTimeout);
        autoHideTimeout = null;

        // Setup click-outside listener
        if (!config.behavior.permanent && hasToggleAction && state.isActive() && !isOutsideListenerAttached) {
            setTimeout(() => {
                document.addEventListener("click", handleClickOutside);
                isOutsideListenerAttached = true;
            }, 0);
        }

        // Setup auto-hide timer
        if (config.behavior.autoHide > 0 && state.isActive()) {
            autoHideTimeout = setTimeout(handleClickOutside, config.behavior.autoHide * 1000);
        }
    };

    /**
     * Handles hover enter (activates toggle).
     * @private
     */
    const handleHoverEnter = (event) => {
        const childHover = toggler.contains(event.target) && event.target !== toggler;
        if (config.behavior.noChildren && childHover) return;

        // Check if-present logic
        const skipClassToggle = state.checkIfPresent('class');
        const skipAttributeToggle = state.checkIfPresent('attribute');

        // Apply operations
        state.apply({skipClassToggle, skipAttributeToggle});

        // Update tracking
        if (!skipClassToggle) state.updateTracking('class');
        if (!skipAttributeToggle) state.updateTracking('attribute');
    };

    /**
     * Handles hover leave (deactivates toggle).
     * Uses same logic as click-outside to verify we're truly leaving the interactive area.
     * @private
     */
    const handleHoverLeave = (event) => {
        // Check if pointer is moving to a related target (where we're going)
        const relatedTarget = event.relatedTarget;

        // Check if we're still inside the toggler or any target elements
        const stillInside = (relatedTarget && (
            toggler.contains(relatedTarget) ||
            state.containsElement(relatedTarget)
        ));

        // Only remove toggle state if we're truly leaving the entire interactive area
        if (!stillInside) {
            const shouldRemoveClass = !config.behavior.clickOutside ||
                config.behavior.clickOutside.includes('unset-class');
            const shouldRemoveAttr = config.behavior.clickOutside?.includes('unset-attribute') || false;

            state.remove({class: shouldRemoveClass, attribute: shouldRemoveAttr});
        }
    };

    // Register event handlers based on behavior
    if (config.behavior.onHover) {
        // Hover mode: use pointerenter/pointerleave
        toggler.addEventListener('pointerenter', handleHoverEnter);
        toggler.addEventListener('pointerleave', handleHoverLeave);

        cleanupFunctions.push(() => {
            toggler.removeEventListener('pointerenter', handleHoverEnter);
            toggler.removeEventListener('pointerleave', handleHoverLeave);
        });
    } else {
        // Click mode: use singleClick handler
        cleanupFunctions.push(singleClick(toggler, handleToggleClick));
    }

    // Register swipe handler
    if (config.swipe.target) {
        const swipeTarget = document.querySelector(config.swipe.target);
        if (swipeTarget) {
            cleanupFunctions.push(createSwipeHandler(
                swipeTarget,
                () => {
                    if (state.isActive()) {
                        state.remove();
                        clearTimeout(autoHideTimeout);
                        autoHideTimeout = null;
                    }
                },
                config.swipe.direction,
                ...config.swipe.limits
            ));
        }
    }

    // Return cleanup function
    return () => {
        clearTimeout(autoHideTimeout);
        document.removeEventListener("click", handleClickOutside);
        cleanupFunctions.forEach(cleanup => cleanup());
    };
}

// ============================================================================
// INITIALISATION
// ============================================================================

/**
 * Initializes toggle functionality for elements.
 * @param {HTMLElement[]} elements - Container elements
 */
export function init(elements) {
    if (!Array.isArray(elements)) return;
    toggleManager.cleanup(elements);

    elements.forEach(element => {
        if (!(element instanceof Element)) return;
        try {
            element.querySelectorAll(
                '[data-toggles],[data-toggles-class],[data-toggles-attribute],' +
                '[data-sets],[data-sets-class],[data-sets-attribute],' +
                '[data-unsets],[data-unsets-class],[data-unsets-attribute],' +
                '[data-untoggles],[data-untoggles-class],[data-untoggles-attribute]'
            ).forEach(toggler => {
                toggleManager.register(element, createToggleHandler(toggler, element));
            });
        } catch (error) {
            logger.error(NAME, 'Error initializing togglers:', error);
        }
    });
}

/**
 * Cleans up toggle handlers for elements.
 * @param {HTMLElement[]} elements - Elements to cleanup
 */
export function cleanup(elements) {
    toggleManager.cleanup(elements);
}

/**
 * Destroys all toggle instances (for SPA unmounting).
 */
export function destroy() {
    // Clear WeakMaps (they'll GC automatically, but explicit is cleaner)
    lastTogglers.class = new WeakMap();
    lastTogglers.attribute = new WeakMap();

    logger.info(NAME, 'Module destroyed');
}