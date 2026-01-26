import {singleClick} from "./domule/modules/hnl.clickhandlers.mjs";
import {logger} from "./domule/core.log.mjs";
import {debounceThis, debouncedEvent} from "./domule/modules/hnl.debounce.mjs";

export const NAME = 'simpleToggler';

// ============================================================================
// CONSTANTS
// ============================================================================

/**
 * Selector for finding all toggleable trigger elements.
 * @private
 * @const {string}
 */
const TRIGGER_SELECTOR = '[data-toggles-class],[data-toggles-attribute],' +
    '[data-sets-class],[data-sets-attribute],' +
    '[data-unsets-class],[data-unsets-attribute]';

/**
 * Event type for hover-based triggers.
 * @private
 * @const {string}
 */
const EVENT_HOVER = 'pointerenter';

/**
 * Event type for click-based triggers.
 * @private
 * @const {string}
 */
const EVENT_CLICK = 'click';

/**
 * Event type for pointer movement tracking.
 * @private
 * @const {string}
 */
const EVENT_POINTERMOVE = 'pointermove';

/**
 * Restore state value for unsetting changes.
 * @private
 * @const {string}
 */
const RESTORE_UNSET = 'unset';

/**
 * Restore state value for setting changes.
 * @private
 * @const {string}
 */
const RESTORE_SET = 'set';

/**
 * Custom element tag name for screen mask detection.
 * @private
 * @const {string}
 */
const MASK_TAG = 'nok-screen-mask';

/**
 * Detect if device supports hover interactions.
 * Touch-only devices will be forced to click mode.
 * @private
 * @type {boolean}
 */
const SUPPORTS_HOVER = window.matchMedia('(hover: hover)').matches;

/**
 * Default swipe threshold in pixels.
 * @private
 * @const {number}
 */
const SWIPE_THRESHOLD = 50;

/**
 * Default swipe velocity threshold.
 * @private
 * @const {number}
 */
const SWIPE_VELOCITY = 0.3;

// ============================================================================
// SWIPE GESTURE HANDLER
// ============================================================================
/**
 * Clamps a value between min and max.
 * @private
 */
function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

/**
 * Creates swipe gesture handler for element.
 * @private
 * @param {HTMLElement} element - Element to watch for swipes
 * @param {Function} onSwipe - Callback when valid swipe detected
 * @param {string} direction - 'x', 'y', or 'both'
 * @param {number} threshold - Minimum distance in pixels
 * @param {number} velocity - Minimum velocity
 * @returns {Function} Cleanup function
 */
function createSwipeHandler(element, onSwipe, direction = 'y', threshold = SWIPE_THRESHOLD, velocity = SWIPE_VELOCITY) {
    let start = 0, current = 0, isDragging = false, animationFrame = null;
    const clamp = (v, mn, mx) => Math.min(mx, Math.max(mn, v));
    const getCoords = (e) => (e.touches?.[0] || e.changedTouches?.[0] || e);

    // Determine axis and bounds based on direction
    const axis = direction === 'y' ? 'clientY' : 'clientX';
    const getMin = () => direction === 'y' ? -element.clientHeight : -element.clientWidth;
    const getMax = () => 0;

    const updateTransform = (delta) => {
        if (animationFrame) return;
        animationFrame = requestAnimationFrame(() => {
            element.style.transform = direction === 'x'
                ? `translate3d(${clamp(delta, getMin(), getMax())}px, 0, 0)`
                : `translate3d(0, ${clamp(delta, getMin(), getMax())}px, 0)`;
            animationFrame = null;
        });
    };

    const drag = (e) => {
        current = getCoords(e)[axis];
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
            const distanceMoved = Math.abs(start - current);
            const timeTaken = Date.now() - startTime;
            const gestureVelocity = distanceMoved / timeTaken;

            const shouldTrigger = distanceMoved > threshold && gestureVelocity > velocity;

            element.style.transition = "transform 0.25s ease-out";
            element.addEventListener('transitionend', () => {
                element.style.userSelect = "";
                element.style.transition = "";
                element.style.transform = "";
            }, {once: true});

            element.style.transform = shouldTrigger ? "" : "translate3d(0, 0, 0)";
            if (shouldTrigger) onSwipe();
        }
        cleanup();
        isDragging = false;
    };

    let startTime = 0;
    const pointerDown = (e) => {
        isDragging = false;
        start = getCoords(e)[axis];
        startTime = Date.now();
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
// STATE MANAGEMENT
// ============================================================================

/**
 * Tracks AbortControllers for each trigger element.
 * WeakMap ensures automatic cleanup when triggers are removed from DOM.
 * @private
 * @type {WeakMap<HTMLElement, AbortController>}
 */
const triggerControllers = new WeakMap();

/**
 * Tracks swipe cleanup functions for each controller.
 * WeakMap ensures automatic cleanup when controllers are garbage collected.
 * @private
 * @type {WeakMap<AbortController, Function[]>}
 */
const swipeCleanups = new WeakMap();

/**
 * Tracks auto-restore timeout IDs for each controller.
 * WeakMap ensures automatic cleanup when controllers are garbage collected.
 * @private
 * @type {WeakMap<AbortController, number>}
 */
const autoRestoreTimeouts = new WeakMap();

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initializes toggle functionality for elements.
 *
 * Scans container elements for triggers with data-toggles-*, data-sets-*, or
 * data-unsets-* attributes and attaches appropriate event handlers.
 *
 * @param {HTMLElement[]} elements - Container elements to scan for triggers
 *
 * @example
 * // Initialize togglers in specific container
 * init([document.querySelector('.my-container')]);
 *
 * @example
 * // Initialize all togglers on page
 * init([document.body]);
 */
export function init(elements) {
    elements.forEach(element => {
        if (!(element instanceof Element)) return;
        try {
            // Check if the element itself is a toggler
            if (element.matches(TRIGGER_SELECTOR)) {
                createToggleHandler(element);
            }
            // Also check for child togglers
            element.querySelectorAll(TRIGGER_SELECTOR).forEach(toggler => {
                createToggleHandler(toggler);
            });
        } catch (error) {
            logger.error(NAME, 'Error initializing togglers');
            logger.error(NAME, error);
        }
    });
}

/**
 * Cleanup function for SPA compatibility.
 * Aborts all active toggle controllers to prevent memory leaks.
 *
 * @example
 * // Call before route change in SPA
 * import {destroy} from './nok-toggler.mjs';
 * destroy();
 */
export function destroy() {
    // Note: WeakMap doesn't provide iteration, but controllers will be
    // garbage collected when trigger elements are removed from DOM.
    // This function is provided for explicit cleanup if needed.
    logger.info(NAME, 'Cleanup initiated (controllers will be garbage collected)');
}

// ============================================================================
// PRIVATE FUNCTIONS
// ============================================================================

/**
 * Creates and attaches toggle handler to a trigger element.
 *
 * Parses data attributes to determine:
 * - Target elements to manipulate
 * - Actions to perform (toggle/add/remove classes/attributes)
 * - Trigger event type (click/hover)
 * - Restore behavior on outside interaction
 * - Swipe-to-restore behavior
 *
 * @private
 * @param {HTMLElement} trigger - Element that triggers toggle actions
 */
function createToggleHandler(trigger) {
    const dataset = trigger.dataset;

    // Determine event type based on configuration
    const triggerEvent = dataset.toggleEvent === 'hover' ? EVENT_HOVER : EVENT_CLICK;
    const restoreState = dataset.toggleOutside ?? null;
    const swipeRestore = dataset.swipe === RESTORE_UNSET;
    const autoRestore = dataset.autoRestore ? parseInt(dataset.autoRestore, 10) * 1000 : null; // Convert to ms

    // Parse target selectors
    const targets = {
        class: dataset.classTarget ?? dataset.target ?? null,
        attr: dataset.attributeTarget ?? null,
    };

    // Validate at least one target exists
    if (!targets.class && !targets.attr) {
        logger.warn(NAME, 'Element has no targets, skipping');
        logger.warn(NAME, trigger);
        return;
    }

    // Parse action configurations
    const actions = {
        toggle: {
            class: dataset.togglesClass?.split(',') ?? null,
            attr: [dataset.togglesAttribute ?? null, dataset.togglesAttributeValue ?? null],
        },
        add: {
            class: dataset.setsClass?.split(',') ?? null,
            attr: [dataset.setsAttribute ?? null, dataset.setsAttributeValue ?? null],
        },
        remove: {
            class: dataset.unsetsClass?.split(',') ?? null,
            attr: [dataset.unsetsAttribute ?? null, dataset.unsetsAttributeValue ?? null],
        }
    };

    // Attach event listener (update to pass autoRestore)
    trigger.addEventListener(triggerEvent, (e) => {
        e.preventDefault();
        handleTriggerEvent(e, trigger, dataset, actions, targets, restoreState, swipeRestore, autoRestore, triggerEvent);
    }, {passive: false});
}

/**
 * Handles trigger event and executes configured actions.
 *
 * @private
 * @param {Event} e - Trigger event
 * @param {HTMLElement} trigger - Trigger element
 * @param {DOMStringMap} dataset - Trigger's dataset
 * @param {Object} actions - Parsed action configuration
 * @param {Object} targets - Target selectors
 * @param {string|null} restoreState - Restore behavior setting
 * @param {boolean} swipeRestore - Whether swipe-to-restore is enabled
 * @param {string} triggerEvent - Event type that triggered this handler
 */
function handleTriggerEvent(e, trigger, dataset, actions, targets, restoreState, swipeRestore, autoRestore, triggerEvent) {
    // Check if clicks/touches on children should be ignored
    if (dataset.noChildren && e?.target !== trigger && trigger.contains(e?.target)) {
        return;
    }

    // Abort any existing controller for this trigger
    const existingController = triggerControllers.get(trigger);
    if (existingController) {
        cleanupController(existingController);
        existingController.abort();
    }

    // Create new controller for this interaction
    const controller = new AbortController();
    triggerControllers.set(trigger, controller);

    let tgtElementsArray = [];
    let undoStack = [];

    // Process all configured actions
    for (const [method, types] of Object.entries(actions)) {
        for (const [actionType, value] of Object.entries(types)) {
            // Skip if no value configured
            if (!value || (Array.isArray(value) && value.every(v => !v))) {
                continue;
            }

            // Get target elements (cache once per action type)
            const targetSelector = targets[actionType];
            if (!targetSelector) continue;

            const targetElements = document.querySelectorAll(targetSelector);
            if (targetElements.length === 0) continue;

            // Accumulate all target elements for outside click detection
            tgtElementsArray = [...tgtElementsArray, ...Array.from(targetElements)];

            // Execute action and collect undo operations
            const undoOps = executeAction(targetElements, actionType, method, value, restoreState);
            undoStack.push(...undoOps);
        }
    }

    // Set up swipe-to-restore behavior if enabled
    if (swipeRestore && tgtElementsArray.length > 0) {
        setupSwipeRestore(tgtElementsArray, undoStack, controller);
    }

    // Set up outside interaction restore behavior if configured
    if (restoreState) {
        setupRestoreBehavior(trigger, tgtElementsArray, undoStack, controller, triggerEvent);
    }

    // Set up auto-restore behavior if configured
    if (autoRestore) {
        setupAutoRestore(undoStack, controller, autoRestore);
    }

    // Set up swipe-to-restore behavior if enabled
    if (swipeRestore && tgtElementsArray.length > 0) {
        setupSwipeRestore(tgtElementsArray, undoStack, controller);
    }

    // Set up outside interaction restore behavior if configured
    if (restoreState) {
        setupRestoreBehavior(trigger, tgtElementsArray, undoStack, controller, triggerEvent);
    }
}

/**
 * Executes a toggle action on target elements.
 *
 * @private
 * @param {NodeList} targetElements - Elements to modify
 * @param {string} actionType - Type of action ('class' or 'attr')
 * @param {string} method - Method to apply ('toggle', 'add', 'remove')
 * @param {Array|string} value - Value(s) to apply
 * @param {string|null} restoreState - Restore behavior setting
 * @returns {Array} Array of undo operations
 */
function executeAction(targetElements, actionType, method, value, restoreState) {
    const undoOps = [];

    if (actionType === 'class') {
        targetElements.forEach(el => {
            const result = el.classList[method](...value);

            // Determine undo operation based on what actually happened
            let undoMethod;
            if (restoreState === RESTORE_UNSET) {
                undoMethod = 'remove';
            } else if (method === 'toggle') {
                undoMethod = result ? 'remove' : 'add';
            } else if (method === 'add') {
                undoMethod = 'remove';
            } else {
                undoMethod = 'add';
            }

            undoOps.push({
                target: el.classList,
                method: undoMethod,
                value: value
            });
        });
    } else if (actionType === 'attr') {
        targetElements.forEach(target => {
            const attrName = value[0];
            const attrValue = value[1];
            const hasAttribute = target.hasAttribute(attrName);

            // Determine if we should remove or set attribute
            const shouldRemove = (method === 'remove') ||
                (method === 'toggle' && hasAttribute);

            if (shouldRemove) {
                const oldValue = target.getAttribute(attrName);
                target.removeAttribute(attrName);

                if (restoreState !== RESTORE_SET) {
                    undoOps.push({
                        target,
                        method: 'setAttribute',
                        value: [attrName, oldValue]
                    });
                }
            } else {
                target.setAttribute(attrName, attrValue);

                if (restoreState !== RESTORE_UNSET) {
                    undoOps.push({
                        target,
                        method: 'removeAttribute',
                        value: [attrName]
                    });
                }
            }
        });
    }

    return undoOps;
}

/**
 * Sets up swipe-to-restore behavior on target elements.
 *
 * @private
 * @param {Array<HTMLElement>} targetElements - Elements to watch for swipes
 * @param {Array} undoStack - Stack of undo operations
 * @param {AbortController} controller - Controller for cleanup
 */
function setupSwipeRestore(targetElements, undoStack, controller) {
    const cleanupFunctions = [];

    /**
     * Executes undo stack and cleans up when swipe detected.
     * @private
     */
    function handleSwipe() {
        executeUndoStack(undoStack);
        cleanupController(controller);
        controller.abort();
    }

    // Attach swipe handler to each target element
    targetElements.forEach(element => {
        const cleanup = createSwipeHandler(element, handleSwipe);
        cleanupFunctions.push(cleanup);
    });

    // Store cleanup functions for later removal
    swipeCleanups.set(controller, cleanupFunctions);
}

/**
 * Sets up auto-restore behavior after specified delay.
 *
 * @private
 * @param {Array} undoStack - Stack of undo operations
 * @param {AbortController} controller - Controller for cleanup
 * @param {number} delay - Delay in milliseconds before auto-restore
 */
function setupAutoRestore(undoStack, controller, delay) {
    const timeoutId = setTimeout(() => {
        executeUndoStack(undoStack);
        cleanupController(controller);
        controller.abort();
    }, delay);

    autoRestoreTimeouts.set(controller, timeoutId);
}

/**
 * Sets up event listeners for restoring state on outside interaction.
 *
 * @private
 * @param {HTMLElement} trigger - Original trigger element
 * @param {Array<HTMLElement>} targetElements - All affected target elements
 * @param {Array} undoStack - Stack of undo operations
 * @param {AbortController} controller - Controller for cleanup
 * @param {string} triggerEvent - Type of event that triggered actions
 */
function setupRestoreBehavior(trigger, targetElements, undoStack, controller, triggerEvent) {
    /**
     * Checks if pointer/click is outside relevant elements and restores state.
     * @private
     */
    function checkOutsideInteraction(e) {
        const isSelf = e?.target === trigger || trigger.contains(e?.target);
        const isTarget = targetElements.some(node => node === e?.target || node.contains(e?.target));
        const isMask = e?.target?.tagName?.toLowerCase() === MASK_TAG;

        if (!isSelf && !isTarget || isMask) {
            executeUndoStack(undoStack);
            cleanupController(controller);
            controller.abort();
        }
    }

    // Attach appropriate listener based on trigger type
    if (triggerEvent === EVENT_HOVER) {
        document.body.addEventListener(
            EVENT_POINTERMOVE,
            debounceThis(checkOutsideInteraction, {execWhile: true, execDone: false}),
            {signal: controller.signal}
        );
    } else {
        document.body.addEventListener(
            EVENT_CLICK,
            checkOutsideInteraction,
            {signal: controller.signal}
        );
    }
}

/**
 * Executes all operations in the undo stack.
 *
 * @private
 * @param {Array} undoStack - Stack of undo operations to execute
 */
function executeUndoStack(undoStack) {
    undoStack.forEach(operation => {
        const {target, method, value} = operation;
        if (method === 'setAttribute') {
            target.setAttribute(...value);
        } else if (method === 'removeAttribute') {
            target.removeAttribute(...value);
        } else {
            // classList methods
            target[method](...value);
        }
    });
}

/**
 * Cleans up swipe handlers associated with a controller.
 *
 * @private
 * @param {AbortController} controller - Controller to clean up
 */
function cleanupController(controller) {
    // Clear swipe handlers
    const cleanupFunctions = swipeCleanups.get(controller);
    if (cleanupFunctions) {
        cleanupFunctions.forEach(cleanup => cleanup());
        swipeCleanups.delete(controller);
    }

    // Clear auto-restore timeout
    const timeoutId = autoRestoreTimeouts.get(controller);
    if (timeoutId) {
        clearTimeout(timeoutId);
        autoRestoreTimeouts.delete(controller);
    }
}