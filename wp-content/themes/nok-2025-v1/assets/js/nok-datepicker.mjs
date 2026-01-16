/**
 * NOK Datepicker Component
 *
 * Lightweight datepicker with week selection support.
 * Integrates with existing NOK form/button styling.
 *
 * @module nok-datepicker
 * @version 1.0.0
 */

export const NAME = 'nokDatepicker';

// ============================================================================
// CONSTANTS
// ============================================================================

const DAYS_SHORT = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
const MONTHS = [
    'januari', 'februari', 'maart', 'april', 'mei', 'juni',
    'juli', 'augustus', 'september', 'oktober', 'november', 'december'
];

const CSS_PREFIX = 'nok-datepicker';
const WEEK_MODE = 'week';
const DATE_MODE = 'date';

// ============================================================================
// STATE
// ============================================================================

/** @type {WeakMap<HTMLElement, DatepickerInstance>} */
const instances = new WeakMap();

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initialize datepicker(s) on element(s).
 *
 * @param {HTMLElement[]} elements - Elements with [data-datepicker] attribute
 *
 * @example
 * // Auto-init via data attribute
 * <button data-datepicker data-mode="week" data-value="2026-01-12">Select week</button>
 *
 * @example
 * // Manual init
 * import { init } from './nok-datepicker.mjs';
 * init([document.querySelector('[data-datepicker]')]);
 */
export function init(elements) {
    elements.forEach(element => {
        if (!(element instanceof HTMLElement)) return;

        // Find all datepicker triggers within container
        const triggers = element.matches('[data-datepicker]')
            ? [element]
            : Array.from(element.querySelectorAll('[data-datepicker]'));

        triggers.forEach(trigger => {
            if (instances.has(trigger)) return; // Already initialized
            const instance = new DatepickerInstance(trigger);
            instances.set(trigger, instance);
        });
    });
}

/**
 * Destroy datepicker instance(s).
 *
 * @param {HTMLElement[]} [elements] - Specific elements to destroy, or all if omitted
 */
export function destroy(elements) {
    const targets = elements || document.querySelectorAll('[data-datepicker]');
    targets.forEach(trigger => {
        const instance = instances.get(trigger);
        if (instance) {
            instance.destroy();
            instances.delete(trigger);
        }
    });
}

// ============================================================================
// DATEPICKER INSTANCE CLASS
// ============================================================================

class DatepickerInstance {
    /**
     * @param {HTMLElement} trigger - The trigger button element
     */
    constructor(trigger) {
        this.trigger = trigger;
        this.mode = trigger.dataset.mode || DATE_MODE;
        this.isOpen = false;
        this.controller = new AbortController();

        // Parse initial value or default to today
        const initialValue = trigger.dataset.value;
        this.selectedDate = initialValue ? new Date(initialValue) : new Date();
        this.viewDate = new Date(this.selectedDate);

        // Callback URL pattern (for server-side navigation)
        this.urlPattern = trigger.dataset.urlPattern || null;

        // Weekdays only mode (disable weekends) - default true
        this.weekdaysOnly = trigger.dataset.weekdaysOnly !== 'false';

        // Create DOM
        this.popup = this._createPopup();
        document.body.appendChild(this.popup);

        // Bind events
        this._bindEvents();

        // Update trigger display
        this._updateTriggerDisplay();
    }

    /**
     * Open the datepicker popup.
     */
    open() {
        if (this.isOpen) return;
        this.isOpen = true;

        // Position popup relative to trigger
        this._positionPopup();

        // Render calendar
        this._renderCalendar();

        // Show popup
        this.popup.classList.add(`${CSS_PREFIX}__popup--open`);
        this.trigger.setAttribute('aria-expanded', 'true');

        // Focus first focusable element
        requestAnimationFrame(() => {
            const firstBtn = this.popup.querySelector('button:not([disabled])');
            firstBtn?.focus();
        });
    }

    /**
     * Close the datepicker popup.
     */
    close() {
        if (!this.isOpen) return;
        this.isOpen = false;

        this.popup.classList.remove(`${CSS_PREFIX}__popup--open`);
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.focus();
    }

    /**
     * Toggle the datepicker popup.
     */
    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    /**
     * Select a date (or week).
     * @param {Date} date
     */
    select(date) {
        if (this.mode === WEEK_MODE) {
            // Snap to Monday of that week
            this.selectedDate = this._getWeekStart(date);
        } else {
            this.selectedDate = new Date(date);
        }

        this._updateTriggerDisplay();
        this.close();

        // Dispatch change event
        const event = new CustomEvent('datepicker:change', {
            detail: {
                date: this.selectedDate,
                mode: this.mode,
                formatted: this._formatDate(this.selectedDate)
            },
            bubbles: true
        });
        this.trigger.dispatchEvent(event);

        // Navigate if URL pattern provided
        // Supports placeholders: {date}, {week}, {jaar}
        if (this.urlPattern) {
            const isoWeek = this._getISOWeek(this.selectedDate);
            const [year, week] = isoWeek.split('-');
            // Decode URL first (WordPress add_query_arg encodes curly braces)
            const url = decodeURIComponent(this.urlPattern)
                .replace('{date}', this._formatDateISO(this.selectedDate))
                .replace('{week}', parseInt(week, 10).toString()) // Remove leading zero
                .replace('{jaar}', year);
            window.location.href = url;
        }
    }

    /**
     * Cleanup and remove instance.
     */
    destroy() {
        this.controller.abort();
        this.popup.remove();
    }

    // ========================================================================
    // PRIVATE METHODS
    // ========================================================================

    /**
     * Create the popup DOM structure.
     * @returns {HTMLElement}
     * @private
     */
    _createPopup() {
        const popup = document.createElement('div');
        popup.className = `${CSS_PREFIX}__popup`;
        if (this.mode === WEEK_MODE) {
            popup.classList.add(`${CSS_PREFIX}__popup--week-mode`);
        }
        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-modal', 'true');
        popup.setAttribute('aria-label', 'Kies een datum');

        popup.innerHTML = `
            <div class="${CSS_PREFIX}__header">
                <button type="button" class="${CSS_PREFIX}__nav ${CSS_PREFIX}__nav--prev" aria-label="Vorige maand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <span class="${CSS_PREFIX}__title"></span>
                <button type="button" class="${CSS_PREFIX}__nav ${CSS_PREFIX}__nav--next" aria-label="Volgende maand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <div class="${CSS_PREFIX}__calendar"></div>
        `;

        return popup;
    }

    /**
     * Bind all event listeners.
     * @private
     */
    _bindEvents() {
        const signal = this.controller.signal;

        // Trigger click
        this.trigger.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        }, {signal});

        // Navigation buttons
        this.popup.querySelector(`.${CSS_PREFIX}__nav--prev`).addEventListener('click', () => {
            this.viewDate.setMonth(this.viewDate.getMonth() - 1);
            this._renderCalendar();
        }, {signal});

        this.popup.querySelector(`.${CSS_PREFIX}__nav--next`).addEventListener('click', () => {
            this.viewDate.setMonth(this.viewDate.getMonth() + 1);
            this._renderCalendar();
        }, {signal});

        // Day selection (delegated)
        const calendarContainer = this.popup.querySelector(`.${CSS_PREFIX}__calendar`);
        calendarContainer.addEventListener('click', (e) => {
            const dayBtn = e.target.closest(`.${CSS_PREFIX}__day`);
            if (dayBtn && !dayBtn.disabled && dayBtn.dataset.date) {
                const date = new Date(dayBtn.dataset.date);
                this.select(date);
            }
        }, {signal});

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.popup.contains(e.target) && !this.trigger.contains(e.target)) {
                this.close();
            }
        }, {signal});

        // Keyboard navigation
        this.popup.addEventListener('keydown', (e) => this._handleKeydown(e), {signal});

        // Close on escape (global)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        }, {signal});
    }

    /**
     * Handle keyboard navigation within popup.
     * @param {KeyboardEvent} e
     * @private
     */
    _handleKeydown(e) {
        const focusedDay = this.popup.querySelector(`.${CSS_PREFIX}__day:focus`);
        if (!focusedDay) return;

        const currentDate = new Date(focusedDay.dataset.date);
        let newDate = new Date(currentDate);
        let handled = false;

        switch (e.key) {
            case 'ArrowLeft':
                newDate.setDate(newDate.getDate() - 1);
                handled = true;
                break;
            case 'ArrowRight':
                newDate.setDate(newDate.getDate() + 1);
                handled = true;
                break;
            case 'ArrowUp':
                newDate.setDate(newDate.getDate() - 7);
                handled = true;
                break;
            case 'ArrowDown':
                newDate.setDate(newDate.getDate() + 7);
                handled = true;
                break;
            case 'Enter':
            case ' ':
                this.select(currentDate);
                handled = true;
                break;
        }

        if (handled) {
            e.preventDefault();

            // Check if we need to change month
            if (newDate.getMonth() !== this.viewDate.getMonth()) {
                this.viewDate = new Date(newDate);
                this._renderCalendar();
            }

            // Focus the new date
            requestAnimationFrame(() => {
                const newDayBtn = this.popup.querySelector(`[data-date="${this._formatDateISO(newDate)}"]`);
                newDayBtn?.focus();
            });
        }
    }

    /**
     * Position popup relative to trigger.
     * @private
     */
    _positionPopup() {
        const triggerRect = this.trigger.getBoundingClientRect();
        const popupRect = this.popup.getBoundingClientRect();

        let top = triggerRect.bottom + 4;
        let left = triggerRect.left;

        // Adjust if would overflow right edge
        if (left + 280 > window.innerWidth) {
            left = window.innerWidth - 290;
        }

        // Adjust if would overflow bottom
        if (top + 320 > window.innerHeight) {
            top = triggerRect.top - 324;
        }

        this.popup.style.top = `${top + window.scrollY}px`;
        this.popup.style.left = `${left + window.scrollX}px`;
    }

    /**
     * Render the calendar grid for current viewDate.
     * @private
     */
    _renderCalendar() {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();

        // Update title
        this.popup.querySelector(`.${CSS_PREFIX}__title`).textContent =
            `${MONTHS[month]} ${year}`;

        // Get first day of month and how many days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();

        // Monday = 0, Sunday = 6 (ISO week)
        let startDay = firstDay.getDay() - 1;
        if (startDay < 0) startDay = 6;

        // Build calendar with week rows
        const calendarContainer = this.popup.querySelector(`.${CSS_PREFIX}__calendar`);
        calendarContainer.innerHTML = '';

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const selectedWeekStart = this.mode === WEEK_MODE ? this._getWeekStart(this.selectedDate) : null;

        // Add weekday header row
        const headerRow = document.createElement('div');
        headerRow.className = `${CSS_PREFIX}__week ${CSS_PREFIX}__week--header`;
        DAYS_SHORT.forEach(dayName => {
            const span = document.createElement('span');
            span.textContent = dayName;
            headerRow.appendChild(span);
        });
        calendarContainer.appendChild(headerRow);

        // Build weeks array with day data
        let currentWeekRow = null;
        let currentWeekId = null;

        // Add days from previous month to fill first week
        if (startDay > 0) {
            const firstDayDate = new Date(year, month, 1);
            currentWeekId = this._getISOWeek(firstDayDate);
            currentWeekRow = this._createWeekRow(currentWeekId, selectedWeekStart);
            calendarContainer.appendChild(currentWeekRow);

            // Get last day of previous month
            const prevMonthLastDay = new Date(year, month, 0).getDate();

            for (let i = startDay - 1; i >= 0; i--) {
                const prevDate = new Date(year, month - 1, prevMonthLastDay - i);
                const btn = this._createDayButton(prevDate, today, selectedWeekStart, true);
                currentWeekRow.appendChild(btn);
            }
        }

        // Add day buttons for current month
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const weekId = this._getISOWeek(date);

            // Start new week row if needed
            if (weekId !== currentWeekId) {
                currentWeekRow = this._createWeekRow(weekId, selectedWeekStart);
                calendarContainer.appendChild(currentWeekRow);
                currentWeekId = weekId;
            }

            const btn = this._createDayButton(date, today, selectedWeekStart, false);
            currentWeekRow.appendChild(btn);
        }

        // Add days from next month to fill last week
        if (currentWeekRow) {
            const daysInLastRow = currentWeekRow.children.length;
            if (daysInLastRow < 7) {
                for (let i = 1; i <= 7 - daysInLastRow; i++) {
                    const nextDate = new Date(year, month + 1, i);
                    const btn = this._createDayButton(nextDate, today, selectedWeekStart, true);
                    currentWeekRow.appendChild(btn);
                }
            }
        }
    }

    /**
     * Create a day button element.
     * @param {Date} date - The date for this button
     * @param {Date} today - Today's date (for comparison)
     * @param {Date|null} selectedWeekStart - Start of selected week (for week mode)
     * @param {boolean} isOtherMonth - Whether this day is from adjacent month
     * @returns {HTMLButtonElement}
     * @private
     */
    _createDayButton(date, today, selectedWeekStart, isOtherMonth) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `${CSS_PREFIX}__day`;
        btn.textContent = date.getDate();
        btn.dataset.date = this._formatDateISO(date);

        // Other month styling
        if (isOtherMonth) {
            btn.classList.add(`${CSS_PREFIX}__day--other-month`);
        }

        // Disable weekends if weekdaysOnly mode
        const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday
        if (this.weekdaysOnly && (dayOfWeek === 0 || dayOfWeek === 6)) {
            btn.disabled = true;
            btn.classList.add(`${CSS_PREFIX}__day--disabled`);
        }

        // Today marker
        if (date.getTime() === today.getTime()) {
            btn.classList.add(`${CSS_PREFIX}__day--today`);
        }

        // Selected state
        if (this.mode === WEEK_MODE) {
            const weekStart = this._getWeekStart(date);
            if (selectedWeekStart && weekStart.getTime() === selectedWeekStart.getTime()) {
                btn.classList.add(`${CSS_PREFIX}__day--selected`);
            }
        } else {
            if (this._formatDateISO(date) === this._formatDateISO(this.selectedDate)) {
                btn.classList.add(`${CSS_PREFIX}__day--selected`);
            }
        }

        return btn;
    }

    /**
     * Create a week row element.
     * @param {string} weekId - ISO week identifier (e.g., "2026-03")
     * @param {Date|null} selectedWeekStart - Start of selected week (for week mode)
     * @returns {HTMLElement}
     * @private
     */
    _createWeekRow(weekId, selectedWeekStart) {
        const row = document.createElement('div');
        row.className = `${CSS_PREFIX}__week`;
        row.dataset.week = weekId;

        // Mark selected week row
        if (this.mode === WEEK_MODE && selectedWeekStart) {
            const selectedWeekId = this._getISOWeek(selectedWeekStart);
            if (weekId === selectedWeekId) {
                row.classList.add(`${CSS_PREFIX}__week--selected`);
            }
        }

        return row;
    }

    /**
     * Update trigger button display text.
     * @private
     */
    _updateTriggerDisplay() {
        const displayEl = this.trigger.querySelector(`.${CSS_PREFIX}__display`);
        if (displayEl) {
            if (this.mode === WEEK_MODE) {
                const weekStart = this._getWeekStart(this.selectedDate);
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);
                displayEl.textContent = this._formatWeekRange(weekStart, weekEnd);
            } else {
                displayEl.textContent = this._formatDate(this.selectedDate);
            }
        }
    }

    /**
     * Get Monday of the week containing date.
     * @param {Date} date
     * @returns {Date}
     * @private
     */
    _getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    /**
     * Get ISO week identifier (YYYY-WW format).
     * @param {Date} date
     * @returns {string} e.g., "2026-03"
     * @private
     */
    _getISOWeek(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);
        // Set to nearest Thursday (current date + 4 - current day number, making Sunday = 7)
        d.setDate(d.getDate() + 4 - (d.getDay() || 7));
        // Get first day of year
        const yearStart = new Date(d.getFullYear(), 0, 1);
        // Calculate full weeks to nearest Thursday
        const weekNum = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        return `${d.getFullYear()}-${String(weekNum).padStart(2, '0')}`;
    }

    /**
     * Format date as ISO string (YYYY-MM-DD).
     * @param {Date} date
     * @returns {string}
     * @private
     */
    _formatDateISO(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /**
     * Format date for display (e.g., "12 januari 2026").
     * @param {Date} date
     * @returns {string}
     * @private
     */
    _formatDate(date) {
        return `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
    }

    /**
     * Format week range for display.
     * @param {Date} start
     * @param {Date} end
     * @returns {string}
     * @private
     */
    _formatWeekRange(start, end) {
        if (start.getMonth() === end.getMonth()) {
            return `${start.getDate()} - ${end.getDate()} ${MONTHS[start.getMonth()]}`;
        }
        return `${start.getDate()} ${MONTHS[start.getMonth()]} - ${end.getDate()} ${MONTHS[end.getMonth()]}`;
    }
}