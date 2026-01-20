/**
 * NOK Voorlichting Form - AJAX Dropdown Population
 *
 * Handles dynamic population of location and date/time dropdowns
 * for the general voorlichting registration form.
 *
 * Features:
 * - Fetches voorlichting options via REST API (bypasses page caching)
 * - Populates location dropdown with vestigingen that have upcoming events
 * - Cascading date/time dropdown based on selected location
 * - Shows disabled options for full events (status "vol")
 * - Sets hidden field with voorlichting post ID for form processing
 *
 * @module nok-voorlichting-form
 * @version 1.0.0
 */

export const NAME = 'nokVoorlichtingForm';

// ============================================================================
// STATE
// ============================================================================

/** @type {WeakMap<HTMLElement, FormInstance>} */
const instances = new WeakMap();

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initialize voorlichting form(s).
 *
 * @param {HTMLElement[]} elements - Elements with [data-voorlichting-form] attribute
 *
 * @example
 * // Auto-init via data attribute
 * <div data-voorlichting-form
 *      data-api-url="/wp-json/nok-2025-v1/v1/voorlichtingen/options"
 *      data-location-field="input_10"
 *      data-datetime-field="input_11"
 *      data-voorlichting-id-field="input_12">
 *   <!-- Gravity Form renders here -->
 * </div>
 */
export function init(elements) {
    elements.forEach(element => {
        if (!(element instanceof HTMLElement)) return;

        // Find all form containers within element
        const containers = element.matches('[data-voorlichting-form]')
            ? [element]
            : Array.from(element.querySelectorAll('[data-voorlichting-form]'));

        containers.forEach(container => {
            if (instances.has(container)) return; // Already initialized
            const instance = new FormInstance(container);
            instances.set(container, instance);
        });
    });
}

/**
 * Destroy form instance(s).
 *
 * @param {HTMLElement[]} [elements] - Specific elements to destroy, or all if omitted
 */
export function destroy(elements) {
    const targets = elements || document.querySelectorAll('[data-voorlichting-form]');
    targets.forEach(container => {
        const instance = instances.get(container);
        if (instance) {
            instance.destroy();
            instances.delete(container);
        }
    });
}

// ============================================================================
// FORM INSTANCE CLASS
// ============================================================================

class FormInstance {
    /**
     * @param {HTMLElement} container - The form container element
     */
    constructor(container) {
        this.container = container;
        this.controller = new AbortController();

        // Configuration from data attributes
        this.apiUrl = container.dataset.apiUrl;
        this.locationFieldId = container.dataset.locationField || 'input_2_10';
        this.datetimeFieldId = container.dataset.datetimeField || 'input_2_11';
        this.voorlichtingIdFieldId = container.dataset.voorlichtingIdField || 'input_2_12';

        // Get form field elements
        this.locationSelect = container.querySelector(`#${this.locationFieldId}`);
        this.datetimeSelect = container.querySelector(`#${this.datetimeFieldId}`);
        this.voorlichtingIdInput = container.querySelector(`#${this.voorlichtingIdFieldId}`);

        // Data storage
        this.data = null;

        // Initialize
        this._init();
    }

    /**
     * Initialize the form.
     * @private
     */
    async _init() {
        if (!this.locationSelect || !this.datetimeSelect) {
            console.warn('NOK Voorlichting Form: Required form fields not found', {
                selectors: {
                    location: this.locationFieldId,
                    date: this.datetimeFieldId,
                },
                locationSelect: this.locationSelect,
                datetimeSelect: this.datetimeSelect
            });
            return;
        }

        // Show loading state
        this._setLoadingState(true);

        // Fetch data
        try {
            this.data = await this._fetchOptions();
            this._populateLocationDropdown();
            this._bindEvents();
        } catch (error) {
            console.error('NOK Voorlichting Form: Failed to fetch options', error);
            this._showError('Er is een fout opgetreden bij het laden van de voorlichtingen.');
        } finally {
            this._setLoadingState(false);
        }
    }

    /**
     * Fetch voorlichting options from REST API.
     * @returns {Promise<{locations: Array, events: Object}>}
     * @private
     */
    async _fetchOptions() {
        const response = await fetch(this.apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            signal: this.controller.signal
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return response.json();
    }

    /**
     * Populate the location dropdown with vestigingen.
     * @private
     */
    _populateLocationDropdown() {
        if (!this.data?.locations?.length) {
            this._showError('Er zijn momenteel geen voorlichtingen beschikbaar.');
            return;
        }

        // Clear existing options (except placeholder)
        const placeholder = this.locationSelect.querySelector('option[value=""]');
        this.locationSelect.innerHTML = '';

        // Add placeholder back
        if (placeholder) {
            this.locationSelect.appendChild(placeholder);
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Selecteer een vestiging';
            this.locationSelect.appendChild(option);
        }

        // Add location options
        this.data.locations.forEach(location => {
            const option = document.createElement('option');
            option.value = location.value;
            option.textContent = location.count > 0
                ? `${location.label} (${location.count} gepland)`
                : `${location.label} (geen voorlichting gepland)`;

            // Disable locations with no open events
            if (location.count === 0) {
                option.disabled = true;
            }

            this.locationSelect.appendChild(option);
        });

        // Clear datetime dropdown
        this._clearDatetimeDropdown();
    }

    /**
     * Populate the datetime dropdown based on selected location.
     * @param {string} locationKey - The selected location key
     * @private
     */
    _populateDatetimeDropdown(locationKey) {
        this._clearDatetimeDropdown();

        if (!locationKey || !this.data?.events?.[locationKey]) {
            return;
        }

        const events = this.data.events[locationKey];

        events.forEach(event => {
            const option = document.createElement('option');
            option.value = event.id;
            option.textContent = event.label;
            option.disabled = event.disabled;

            if (event.disabled) {
                option.classList.add('nok-text-muted');
            }

            this.datetimeSelect.appendChild(option);
        });

        // If only one option is available and it's not disabled, auto-select it
        const availableOptions = events.filter(e => !e.disabled);
        if (availableOptions.length === 1) {
            this.datetimeSelect.value = availableOptions[0].id;
            this._updateVoorlichtingId(availableOptions[0].id);
        }
    }

    /**
     * Clear the datetime dropdown.
     * @private
     */
    _clearDatetimeDropdown() {
        const placeholder = this.datetimeSelect.querySelector('option[value=""]');
        this.datetimeSelect.innerHTML = '';

        if (placeholder) {
            this.datetimeSelect.appendChild(placeholder);
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Selecteer eerst een vestiging';
            this.datetimeSelect.appendChild(option);
        }

        // Clear hidden field
        this._updateVoorlichtingId('');
    }

    /**
     * Update the hidden voorlichting ID field.
     * @param {string|number} id - The voorlichting post ID
     * @private
     */
    _updateVoorlichtingId(id) {
        if (this.voorlichtingIdInput) {
            this.voorlichtingIdInput.value = id;
        }
    }

    /**
     * Bind event listeners.
     * @private
     */
    _bindEvents() {
        const signal = this.controller.signal;

        // Location change handler
        this.locationSelect.addEventListener('change', (e) => {
            const locationKey = e.target.value;
            this._populateDatetimeDropdown(locationKey);

            // Update placeholder text
            const placeholderOption = this.datetimeSelect.querySelector('option[value=""]');
            if (placeholderOption) {
                placeholderOption.textContent = locationKey
                    ? 'Selecteer een datum/tijd'
                    : 'Selecteer eerst een vestiging';
            }
        }, { signal });

        // Datetime change handler
        this.datetimeSelect.addEventListener('change', (e) => {
            this._updateVoorlichtingId(e.target.value);
        }, { signal });
    }

    /**
     * Set loading state on form fields.
     * @param {boolean} isLoading
     * @private
     */
    _setLoadingState(isLoading) {
        if (this.locationSelect) {
            this.locationSelect.disabled = isLoading;
            if (isLoading) {
                this.locationSelect.classList.add('is-loading');
            } else {
                this.locationSelect.classList.remove('is-loading');
            }
        }

        if (this.datetimeSelect) {
            this.datetimeSelect.disabled = isLoading;
            if (isLoading) {
                this.datetimeSelect.classList.add('is-loading');
            } else {
                this.datetimeSelect.classList.remove('is-loading');
            }
        }
    }

    /**
     * Show error message in form container.
     * @param {string} message
     * @private
     */
    _showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'nok-alert nok-bg-red--lighter nok-p-1 nok-rounded-border nok-mb-1';
        errorDiv.textContent = message;

        // Insert at top of form
        const form = this.container.querySelector('form');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        } else {
            this.container.insertBefore(errorDiv, this.container.firstChild);
        }
    }

    /**
     * Cleanup and destroy instance.
     */
    destroy() {
        this.controller.abort();
    }
}
