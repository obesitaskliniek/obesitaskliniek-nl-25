/**
 * Search Autocomplete Module
 *
 * Provides search-as-you-type functionality with debounced API calls
 * and keyboard navigation support.
 *
 * @version 1.0.0
 * @author Nederlandse Obesitas Kliniek B.V.
 *
 * @example
 * <nok-search data-requires="./nok-search.mjs" data-max-results="5">
 *   <input type="search" class="nok-search-input" />
 *   <nok-search-results></nok-search-results>
 * </nok-search>
 */

import {debouncedEvent} from "./domule/modules/hnl.debounce.mjs";
import {logger} from "./domule/core.log.mjs";

export const NAME = 'nok-search';

// ============================================================================
// CONSTANTS
// ============================================================================

/**
 * API endpoint for search autocomplete
 * @private
 * @const {string}
 */
const API_ENDPOINT = '/wp-json/nok-2025-v1/v1/search/autocomplete';

/**
 * Debounce delay in milliseconds
 * @private
 * @const {number}
 */
const DEBOUNCE_DELAY = 300;

/**
 * Minimum query length to trigger search
 * @private
 * @const {number}
 */
const MIN_QUERY_LENGTH = 2;

/**
 * Default maximum results to display
 * @private
 * @const {number}
 */
const DEFAULT_MAX_RESULTS = 5;

/**
 * CSS class for selected result
 * @private
 * @const {string}
 */
const SELECTED_CLASS = 'nok-search-result--selected';

/**
 * Keyboard key codes
 * @private
 * @const {Object}
 */
const KEYS = {
    ARROW_UP: 'ArrowUp',
    ARROW_DOWN: 'ArrowDown',
    ENTER: 'Enter',
    ESCAPE: 'Escape',
};

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

/**
 * WeakMap for element-specific state
 * @private
 * @type {WeakMap<HTMLElement, Object>}
 */
const elementState = new WeakMap();

/**
 * Get or create state for an element
 * @private
 * @param {HTMLElement} element
 * @returns {Object}
 */
function getState(element) {
    if (!elementState.has(element)) {
        elementState.set(element, {
            selectedIndex: -1,
            results: [],
            abortController: null,
            observer: null,
            cleanupFunctions: [],
        });
    }
    return elementState.get(element);
}

// ============================================================================
// API FUNCTIONS
// ============================================================================

/**
 * Fetch search results from API
 * @private
 * @param {string} query - Search query
 * @param {number} limit - Maximum results
 * @param {AbortSignal} signal - Abort signal for cancellation
 * @returns {Promise<Object>} Search results
 */
async function fetchResults(query, limit, signal) {
    const url = new URL(API_ENDPOINT, window.location.origin);
    url.searchParams.set('q', query);
    url.searchParams.set('limit', limit.toString());

    const response = await fetch(url.toString(), {signal});

    if (!response.ok) {
        throw new Error(`Search request failed: ${response.status}`);
    }

    return response.json();
}

// ============================================================================
// RENDERING
// ============================================================================

/**
 * Render search results into container
 * @private
 * @param {HTMLElement} container - Results container element
 * @param {Array} results - Search results array
 * @param {number} total - Total results count
 * @param {string} query - Original search query
 */
function renderResults(container, results, total, query) {
    container.innerHTML = '';

    if (results.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'nok-search-no-results';
        noResults.textContent = 'Geen resultaten gevonden';
        container.appendChild(noResults);
        return;
    }

    results.forEach((result, index) => {
        const item = document.createElement('a');
        item.href = result.url;
        item.className = 'nok-search-result';
        item.dataset.index = index.toString();

        const title = document.createElement('span');
        title.className = 'nok-search-result__title';
        title.textContent = result.title;

        const badge = document.createElement('span');
        badge.className = `nok-search-result__badge nok-search-result__badge--${result.type}`;
        badge.textContent = result.type_label;

        item.appendChild(title);
        item.appendChild(badge);
        container.appendChild(item);
    });

    // Add "view all" link if there are more results
    if (total > results.length) {
        const viewAll = document.createElement('a');
        viewAll.href = `/?s=${encodeURIComponent(query)}`;
        viewAll.className = 'nok-search-view-all';
        viewAll.textContent = `Bekijk alle ${total} resultaten`;
        container.appendChild(viewAll);
    }
}

/**
 * Render loading state
 * @private
 * @param {HTMLElement} container - Results container element
 */
function renderLoading(container) {
    container.innerHTML = '<div class="nok-search-loading">Zoeken...</div>';
}

/**
 * Clear results container
 * @private
 * @param {HTMLElement} container - Results container element
 */
function clearResults(container) {
    container.innerHTML = '';
}

// ============================================================================
// KEYBOARD NAVIGATION
// ============================================================================

/**
 * Update selected result visually
 * @private
 * @param {HTMLElement} container - Results container
 * @param {number} index - Index to select (-1 for none)
 */
function updateSelection(container, index) {
    const items = container.querySelectorAll('.nok-search-result');

    items.forEach((item, i) => {
        if (i === index) {
            item.classList.add(SELECTED_CLASS);
            item.scrollIntoView({block: 'nearest'});
        } else {
            item.classList.remove(SELECTED_CLASS);
        }
    });
}

/**
 * Handle keyboard navigation
 * @private
 * @param {KeyboardEvent} event - Keyboard event
 * @param {HTMLElement} searchElement - Search container element
 * @param {HTMLElement} resultsContainer - Results container
 */
function handleKeydown(event, searchElement, resultsContainer) {
    const state = getState(searchElement);
    const items = resultsContainer.querySelectorAll('.nok-search-result');
    const maxIndex = items.length - 1;

    switch (event.key) {
        case KEYS.ARROW_DOWN:
            event.preventDefault();
            state.selectedIndex = Math.min(state.selectedIndex + 1, maxIndex);
            updateSelection(resultsContainer, state.selectedIndex);
            break;

        case KEYS.ARROW_UP:
            event.preventDefault();
            state.selectedIndex = Math.max(state.selectedIndex - 1, -1);
            updateSelection(resultsContainer, state.selectedIndex);
            break;

        case KEYS.ENTER:
            if (state.selectedIndex >= 0 && items[state.selectedIndex]) {
                event.preventDefault();
                const selectedItem = items[state.selectedIndex];
                window.location.href = selectedItem.href;
            }
            break;

        case KEYS.ESCAPE:
            event.preventDefault();
            closeSearchPopup(searchElement);
            break;
    }
}

// ============================================================================
// POPUP HANDLING
// ============================================================================

/**
 * Close the search popup
 * @private
 * @param {HTMLElement} searchElement - Search container element
 */
function closeSearchPopup(searchElement) {
    const popup = searchElement.closest('#popup-search');
    if (popup) {
        popup.removeAttribute('data-state');
        const navigation = document.querySelector('nok-top-navigation');
        if (navigation) {
            navigation.classList.remove('popup-open');
        }
    }
}

/**
 * Focus the input when popup opens
 * @private
 * @param {HTMLElement} searchElement - Search container element
 * @param {HTMLInputElement} input - Search input element
 */
function setupPopupObserver(searchElement, input) {
    const popup = searchElement.closest('#popup-search');
    if (!popup) return;

    const state = getState(searchElement);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-state') {
                if (popup.getAttribute('data-state') === 'open') {
                    // Focus input with slight delay for animation
                    setTimeout(() => {
                        input.focus();
                        input.select();
                    }, 100);
                }
            }
        });
    });

    observer.observe(popup, {attributes: true});
    state.observer = observer;
}

// ============================================================================
// SEARCH HANDLER
// ============================================================================

/**
 * Handle search input
 * @private
 * @param {HTMLElement} searchElement - Search container element
 * @param {HTMLInputElement} input - Search input element
 * @param {HTMLElement} resultsContainer - Results container
 * @param {number} maxResults - Maximum results to fetch
 */
function handleSearch(searchElement, input, resultsContainer, maxResults) {
    const state = getState(searchElement);
    const query = input.value.trim();

    // Reset selection on new search
    state.selectedIndex = -1;

    // Cancel any pending request
    if (state.abortController) {
        state.abortController.abort();
    }

    // Clear results if query is too short
    if (query.length < MIN_QUERY_LENGTH) {
        clearResults(resultsContainer);
        state.results = [];
        return;
    }

    // Create new abort controller
    state.abortController = new AbortController();

    // Show loading state
    renderLoading(resultsContainer);

    // Fetch results
    fetchResults(query, maxResults, state.abortController.signal)
        .then((data) => {
            state.results = data.results;
            renderResults(resultsContainer, data.results, data.total, query);
        })
        .catch((error) => {
            if (error.name !== 'AbortError') {
                logger.error(NAME, 'Search failed:', error);
                resultsContainer.innerHTML = '<div class="nok-search-error">Er ging iets mis. Probeer opnieuw.</div>';
            }
        });
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize a single search element
 * @private
 * @param {HTMLElement} element - Search container element
 */
function initSearchElement(element) {
    const input = element.querySelector('.nok-search-input');
    const resultsContainer = element.querySelector('nok-search-results');

    if (!input || !resultsContainer) {
        logger.warn(NAME, 'Missing required child elements (input or results container)');
        return;
    }

    const maxResults = parseInt(element.dataset.maxResults, 10) || DEFAULT_MAX_RESULTS;
    const state = getState(element);

    // Set up debounced search handler
    const cleanupSearch = debouncedEvent(
        input,
        'input',
        () => handleSearch(element, input, resultsContainer, maxResults),
        {delay: DEBOUNCE_DELAY}
    );
    state.cleanupFunctions.push(cleanupSearch);

    // Set up keyboard navigation
    const keydownHandler = (event) => handleKeydown(event, element, resultsContainer);
    input.addEventListener('keydown', keydownHandler);
    state.cleanupFunctions.push(() => input.removeEventListener('keydown', keydownHandler));

    // Set up popup observer for auto-focus
    setupPopupObserver(element, input);

    // Handle result click (close popup)
    const clickHandler = (event) => {
        const result = event.target.closest('.nok-search-result, .nok-search-view-all');
        if (result) {
            // Allow navigation, popup will close naturally
        }
    };
    resultsContainer.addEventListener('click', clickHandler);
    state.cleanupFunctions.push(() => resultsContainer.removeEventListener('click', clickHandler));

    logger.info(NAME, 'Initialized search element', {maxResults});
}

/**
 * Clean up a search element
 * @private
 * @param {HTMLElement} element - Search container element
 */
function destroySearchElement(element) {
    const state = getState(element);

    // Abort any pending requests
    if (state.abortController) {
        state.abortController.abort();
    }

    // Disconnect observer
    if (state.observer) {
        state.observer.disconnect();
    }

    // Run all cleanup functions
    state.cleanupFunctions.forEach((cleanup) => cleanup());

    // Remove state
    elementState.delete(element);
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initialize search module on elements
 * @param {NodeList|Array} elements - Elements to initialize
 * @returns {string} Initialization status message
 */
export function init(elements) {
    elements.forEach((element) => {
        try {
            initSearchElement(element);
        } catch (error) {
            logger.error(NAME, 'Failed to initialize search element:', error);
        }
    });

    return `Initialized ${elements.length} search element(s)`;
}

/**
 * Destroy module and clean up resources
 */
export function destroy() {
    logger.info(NAME, 'Module cleanup initiated');
}
