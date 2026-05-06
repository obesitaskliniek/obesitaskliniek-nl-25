/**
 * NOK Voorlichting Form - AJAX Dropdown Population
 *
 * Handles dynamic population of location and date/time dropdowns
 * for the general voorlichting registration form.
 *
 * Architecture:
 * - Dropdowns are OUTSIDE the Gravity Form (not submitted to HubSpot)
 * - They serve as UI for selecting a voorlichting post ID
 * - Selected ID and derived event_type are set in hidden fields inside Form 1
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
 *      data-voorlichting-id-field="input_1_22"
 *      data-event-type-field="input_1_23">
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

        // Configuration from data attributes. The hidden voorlichting-id
        // field id is resolved server-side from the GF form's adminLabel
        // (see VoorlichtingForm::field_id) — no fallback here, because a
        // missing data attribute means the form is misconfigured and we
        // shouldn't silently bind to a stale id.
        this.apiUrl = container.dataset.apiUrl;
        this.targetFormSelector = container.dataset.targetForm || '#gform_1';
        this.voorlichtingIdFieldId = container.dataset.voorlichtingIdField || '';
        this.eventTypeFieldId = container.dataset.eventTypeField || '';

        // Find dropdown elements (by fixed IDs from template)
        this.locationSelect = container.querySelector('#voorlichting-location');
        this.datetimeSelect = container.querySelector('#voorlichting-datetime');

        // Find target form and its elements
        this.form = document.querySelector(this.targetFormSelector);
        this.voorlichtingIdInput = this.voorlichtingIdFieldId
            ? this.form?.querySelector(`#${this.voorlichtingIdFieldId}`)
            : null;
        this.eventTypeInput = this.eventTypeFieldId
            ? this.form?.querySelector(`#${this.eventTypeFieldId}`)
            : null;
        this.eventTypeChoiceInputs = this._findGravityChoiceInputs(this.eventTypeFieldId);
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
        if (!this.voorlichtingIdFieldId) {
            console.error(
                'NOK Voorlichting Selector: missing data-voorlichting-id-field on container — ' +
                'GF form 1 is likely misconfigured (no field with adminLabel "voorlichting_id"). ' +
                'Aborting init; admin_notices will surface this in wp-admin.'
            );
            return;
        }

        if (!this.eventTypeFieldId) {
            console.error(
                'NOK Voorlichting Selector: missing data-event-type-field on container — ' +
                'GF form 1 is likely misconfigured (no field with adminLabel "event_type"). ' +
                'Aborting init; admin_notices will surface this in wp-admin.'
            );
            return;
        }

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

        if (!this.voorlichtingIdInput) {
            console.error(
                'NOK Voorlichting Selector: hidden field #' + this.voorlichtingIdFieldId +
                ' not present in target form. Aborting init.'
            );
            return;
        }

        if (!this.eventTypeInput && !this.eventTypeChoiceInputs.length) {
            console.error(
                'NOK Voorlichting Selector: field #' + this.eventTypeFieldId +
                ' not present in target form. Aborting init.'
            );
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
                this._updateVoorlichtingSelection(savedId, matchingEvent.type);

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
            option.dataset.eventType = this._normalizeEventType(event.type);

            if (event.disabled) {
                option.classList.add('nok-text-muted');
            }

            this.datetimeSelect.appendChild(option);
        });

        // If only one option is available and it's not disabled, auto-select it
        const availableOptions = events.filter(e => !e.disabled);
        if (availableOptions.length === 1) {
            this.datetimeSelect.value = availableOptions[0].id;
            this._updateVoorlichtingSelection(availableOptions[0].id, availableOptions[0].type);
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

        // Clear hidden fields and disable form
        this._updateVoorlichtingSelection('', '');
        this._setFormDisabled(true);
    }

    /**
     * Update the hidden voorlichting ID and event type fields in the target form.
     * @param {string|number} id - The voorlichting post ID
     * @param {string} eventType - The event type from the selected REST option
     * @private
     */
    _updateVoorlichtingSelection(id, eventType) {
        if (this.voorlichtingIdInput) {
            this.voorlichtingIdInput.value = id;
        }
        if (this.eventTypeInput) {
            this.eventTypeInput.value = id ? this._normalizeEventType(eventType) : '';
            this._dispatchFieldEvents(this.eventTypeInput);
        }

        if (this.eventTypeChoiceInputs.length) {
            const normalizedType = id ? this._normalizeEventType(eventType) : '';

            this.eventTypeChoiceInputs.forEach(input => {
                const shouldCheck = normalizedType
                    ? this._isEventTypeChoiceMatch(input, normalizedType)
                    : false;

                if (input.checked !== shouldCheck) {
                    input.checked = shouldCheck;
                    this._dispatchFieldEvents(input);
                }
            });
        }
    }

    /**
     * Find Gravity Forms choice inputs for a field id such as input_1_23.
     * Radio/checkbox fields use individual inputs named input_23 rather than
     * a single element with id input_1_23.
     * @param {string} fieldDomId
     * @returns {HTMLInputElement[]}
     * @private
     */
    _findGravityChoiceInputs(fieldDomId) {
        if (!this.form || !fieldDomId) return [];

        const match = fieldDomId.match(/^input_(\d+)_(\d+)$/);
        if (!match) return [];

        const [, formId, fieldId] = match;
        const selector = [
            `input[type="radio"][name="input_${fieldId}"]`,
            `input[type="checkbox"][name="input_${fieldId}"]`,
            `input[type="radio"][id^="choice_${formId}_${fieldId}_"]`,
            `input[type="checkbox"][id^="choice_${formId}_${fieldId}_"]`,
        ].join(',');

        return Array.from(this.form.querySelectorAll(selector));
    }

    /**
     * Check whether a GF choice input represents online/offline.
     * @param {HTMLInputElement} input
     * @param {'online'|'offline'} normalizedType
     * @returns {boolean}
     * @private
     */
    _isEventTypeChoiceMatch(input, normalizedType) {
        const value = String(input.value || '').trim().toLowerCase();
        const label = this._getInputLabel(input).toLowerCase();

        if (value === normalizedType) return true;
        if (normalizedType === 'online') {
            return label.includes('online');
        }

        return ['offline', 'op locatie', 'op-locatie', 'locatie', 'fysiek'].includes(value)
            || label.includes('offline')
            || label.includes('op locatie')
            || label.includes('locatie');
    }

    /**
     * Get label text for a radio/checkbox input.
     * @param {HTMLInputElement} input
     * @returns {string}
     * @private
     */
    _getInputLabel(input) {
        const explicitLabel = input.id
            ? this.form?.querySelector(`label[for="${input.id}"]`)
            : null;
        return explicitLabel?.textContent || input.closest('label')?.textContent || '';
    }

    /**
     * Notify Gravity Forms and browser listeners that a field changed.
     * @param {HTMLElement} field
     * @private
     */
    _dispatchFieldEvents(field) {
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Normalize HubSpot event type into the Gravity Forms/HubSpot field value.
     * @param {string} type
     * @returns {'online'|'offline'}
     * @private
     */
    _normalizeEventType(type) {
        return String(type || '').toLowerCase() === 'online' ? 'online' : 'offline';
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
            const selectedOption = e.target.selectedOptions?.[0];
            this._updateVoorlichtingSelection(
                selectedId,
                selectedOption?.dataset.eventType || ''
            );

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
