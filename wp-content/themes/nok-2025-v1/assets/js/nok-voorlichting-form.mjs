/**
 * NOK Voorlichting Form - AJAX Dropdown Population
 *
 * Handles dynamic population of location and date/time dropdowns
 * for the general voorlichting registration form.
 *
 * Architecture:
 * - Dropdowns are OUTSIDE the Gravity Form (not submitted to HubSpot)
 * - They serve as UI for selecting a voorlichting post ID
 * - Selected ID is set in a hidden field inside Form 1
 * - Form is disabled until a voorlichting is selected
 *
 * Features:
 * - Fetches voorlichting options via REST API (bypasses page caching)
 * - Populates location dropdown with vestigingen that have upcoming events
 * - Cascading date/time dropdown based on selected location
 * - Shows disabled options for full events (status "vol")
 * - Disables form submit until both dropdowns are selected
 *
 * @module nok-voorlichting-form
 * @version 2.0.0
 */

export const NAME = 'nokVoorlichtingForm';

// ============================================================================
// STATE
// ============================================================================

/** @type {WeakMap<HTMLElement, SelectorInstance>} */
const instances = new WeakMap();

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initialize voorlichting selector(s).
 *
 * @param {HTMLElement[]} elements - Elements containing [data-voorlichting-selector]
 *
 * @example
 * // Auto-init via data attribute
 * <div data-voorlichting-selector
 *      data-api-url="/wp-json/nok-2025-v1/v1/voorlichtingen/options"
 *      data-target-form="#gform_1"
 *      data-voorlichting-id-field="input_1_18">
 *   <select id="voorlichting-location">...</select>
 *   <select id="voorlichting-datetime">...</select>
 * </div>
 */
export function init(elements) {
    elements.forEach(element => {
        if (!(element instanceof HTMLElement)) return;

        // Find all selector containers within element
        const containers = element.matches('[data-voorlichting-selector]')
            ? [element]
            : Array.from(element.querySelectorAll('[data-voorlichting-selector]'));

        containers.forEach(container => {
            if (instances.has(container)) return; // Already initialized
            const instance = new SelectorInstance(container);
            instances.set(container, instance);
        });
    });
}

/**
 * Destroy selector instance(s).
 *
 * @param {HTMLElement[]} [elements] - Specific elements to destroy, or all if omitted
 */
export function destroy(elements) {
    const targets = elements || document.querySelectorAll('[data-voorlichting-selector]');
    targets.forEach(container => {
        const instance = instances.get(container);
        if (instance) {
            instance.destroy();
            instances.delete(container);
        }
    });
}

// ============================================================================
// SELECTOR INSTANCE CLASS
// ============================================================================

class SelectorInstance {
    /**
     * @param {HTMLElement} container - The selector container element
     */
    constructor(container) {
        this.container = container;
        this.controller = new AbortController();

        // Configuration from data attributes
        this.apiUrl = container.dataset.apiUrl;
        this.targetFormSelector = container.dataset.targetForm || '#gform_1';
        this.voorlichtingIdFieldId = container.dataset.voorlichtingIdField || 'input_1_18';

        // Find dropdown elements (by fixed IDs from template)
        this.locationSelect = container.querySelector('#voorlichting-location');
        this.datetimeSelect = container.querySelector('#voorlichting-datetime');

        // Find target form and its elements
        this.form = document.querySelector(this.targetFormSelector);
        this.voorlichtingIdInput = this.form?.querySelector(`#${this.voorlichtingIdFieldId}`);
        this.formFieldset = document.querySelector('[data-voorlichting-form-fieldset]');

        // Data storage
        this.data = null;

        // Initialize
        this._init();
    }

    /**
     * Initialize the selector.
     * @private
     */
    async _init() {
        if (!this.locationSelect || !this.datetimeSelect) {
            console.warn('NOK Voorlichting Selector: Required dropdowns not found', {
                locationSelect: this.locationSelect,
                datetimeSelect: this.datetimeSelect
            });
            return;
        }

        if (!this.form) {
            console.warn('NOK Voorlichting Selector: Target form not found', {
                selector: this.targetFormSelector
            });
            return;
        }

        // Disable form until selection is made
        this._setFormDisabled(true);

        // Show loading state on dropdowns
        this._setLoadingState(true);

        // Fetch data
        try {
            this.data = await this._fetchOptions();
            this._populateLocationDropdown();
            this._bindEvents();
            this._restoreFromHiddenField();
        } catch (error) {
            console.error('NOK Voorlichting Selector: Failed to fetch options', error);
            this._showError('Er is een fout opgetreden bij het laden van de voorlichtingen.');
        } finally {
            this._setLoadingState(false);
        }
    }

    /**
     * Restore dropdown selections from hidden field value (e.g., after validation failure).
     * @private
     */
    _restoreFromHiddenField() {
        const savedId = this.voorlichtingIdInput?.value;
        if (!savedId || !this.data?.events) return;

        // Find which location contains this voorlichting ID
        for (const [locationKey, events] of Object.entries(this.data.events)) {
            const matchingEvent = events.find(e => String(e.id) === String(savedId));
            if (matchingEvent) {
                // Select the location
                this.locationSelect.value = locationKey;

                // Populate datetime dropdown for this location
                this._populateDatetimeDropdown(locationKey);

                // Select the datetime and restore hidden field value
                this.datetimeSelect.value = savedId;
                this._updateVoorlichtingId(savedId);

                // Update placeholder text
                const placeholderOption = this.datetimeSelect.querySelector('option[value=""]');
                if (placeholderOption) {
                    placeholderOption.textContent = 'Selecteer een datum/tijd';
                }

                // Enable form
                this._setFormDisabled(false);
                return;
            }
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

        // Keep placeholder, add new options
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
    }

    /**
     * Populate the datetime dropdown based on selected location.
     * @param {string} locationKey - The selected location key
     * @private
     */
    _populateDatetimeDropdown(locationKey) {
        this._clearDatetimeDropdown();

        if (!locationKey || !this.data?.events?.[locationKey]) {
            this.datetimeSelect.disabled = true;
            return;
        }

        // Enable datetime dropdown
        this.datetimeSelect.disabled = false;

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
            this._setFormDisabled(false);
        }
    }

    /**
     * Clear the datetime dropdown.
     * @private
     */
    _clearDatetimeDropdown() {
        // Reset to just the placeholder
        this.datetimeSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecteer eerst een vestiging';
        this.datetimeSelect.appendChild(placeholder);

        // Clear hidden field and disable form
        this._updateVoorlichtingId('');
        this._setFormDisabled(true);
    }

    /**
     * Update the hidden voorlichting ID field in the target form.
     * @param {string|number} id - The voorlichting post ID
     * @private
     */
    _updateVoorlichtingId(id) {
        if (this.voorlichtingIdInput) {
            this.voorlichtingIdInput.value = id;
        }
    }

    /**
     * Enable or disable the target form via native fieldset disabled attribute.
     * @param {boolean} disabled
     * @private
     */
    _setFormDisabled(disabled) {
        if (this.formFieldset) {
            this.formFieldset.disabled = disabled;
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
            const selectedId = e.target.value;
            this._updateVoorlichtingId(selectedId);

            // Enable form when a valid selection is made
            this._setFormDisabled(!selectedId);
        }, { signal });
    }

    /**
     * Set loading state on dropdown fields.
     * @param {boolean} isLoading
     * @private
     */
    _setLoadingState(isLoading) {
        if (this.locationSelect) {
            this.locationSelect.disabled = isLoading;
            this.locationSelect.classList.toggle('is-loading', isLoading);
        }

        if (this.datetimeSelect) {
            this.datetimeSelect.classList.toggle('is-loading', isLoading);
            if (isLoading) {
                this.datetimeSelect.disabled = true;
            }
            // When not loading, don't change disabled state - let other logic control it
        }
    }

    /**
     * Show error message above the selector.
     * @param {string} message
     * @private
     */
    _showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'nok-alert nok-bg-red--lighter nok-p-1 nok-rounded-border nok-mb-1';
        errorDiv.textContent = message;

        // Insert before the selector
        this.container.parentNode.insertBefore(errorDiv, this.container);
    }

    /**
     * Cleanup and destroy instance.
     */
    destroy() {
        this.controller.abort();

        // Re-enable form on destroy
        this._setFormDisabled(false);
    }
}
