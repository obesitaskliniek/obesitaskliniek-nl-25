/**
 * @module helper/ensure-visibility
 * @description
 * A small utility to scroll an element into view only when necessary,
 * accounting for offscreen position, fixed headers, and top-edge obstructions.
 *
 * @example
 * import { ViewportScroller } from './helper.ensure-visibility.mjs';
 *
 * const element = document.querySelector('#target');
 * const scroller = new ViewportScroller(element, {
 *   behavior: 'smooth',   // 'auto' or 'smooth' scrolling
 *   extraOffset: 12       // additional gap (px) below any fixed header
 * });
 *
 * // When you need to ensure visibility:
 * scroller.ensureVisible();
 */
export class ViewportScroller {
    /**
     * @param {Element} el - The DOM element to manage visibility for.
     * @param {Object} [opts]
     * @param {'auto'|'smooth'} [opts.behavior='smooth'] - Scroll behavior.
     * @param {number} [opts.extraOffset=0] - Extra spacing (px) below headers.
     */
    constructor(el, opts = {}) {
        if (!(el instanceof Element)) {
            throw new TypeError('ViewportScroller: el must be a DOM Element');
        }
        const { behavior = 'smooth', extraOffset = 0 } = opts;
        this.el = el;
        this.behavior = behavior;
        this.extraOffset = extraOffset;
    }

    /**
     * Check a few sample points along the top edge and center to ensure
     * nothing opaque (other than ancestors or descendants) covers it.
     * @private
     * @returns {boolean}
     */
    _isUnobstructed() {
        const { left, top, right, bottom } = this.el.getBoundingClientRect();
        const samples = [
            [left + 1, top + 1],
            [right - 1, top + 1],
            [(left + right) / 2, top + 1],
            [(left + right) / 2, (top + bottom) / 2]
        ];
        return samples.every(([x, y]) => {
            const hit = document.elementFromPoint(x, y);
            return (
                hit === this.el ||
                this.el.contains(hit) ||
                hit?.contains(this.el)
            );
        });
    }

    /**
     * Scrolls the element into view if its top or bottom edges are out of the viewport,
     * or if its top edge is hidden behind a fixed header or other obstruction.
     * @public
     */
    ensureVisible() {
        const rect = this.el.getBoundingClientRect();
        const scrollY = window.scrollY;
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;

        // Detect any fixed header at y=1px
        const midX = viewportWidth / 2;
        const headerEl = document.elementFromPoint(midX, 1);
        const headerHeight = (
            headerEl && headerEl !== this.el && !this.el.contains(headerEl)
        )
            ? headerEl.getBoundingClientRect().bottom
            : 0;

        const topOK = rect.top >= headerHeight + this.extraOffset;
        const bottomOK = rect.bottom <= viewportHeight;
        const unobstructed = topOK && this._isUnobstructed();

        // If fully visible and unobstructed, do nothing
        if (topOK && bottomOK && unobstructed) return;

        let targetY = scrollY;

        // If top is above header or viewport
        if (rect.top < headerHeight + this.extraOffset) {
            targetY = scrollY + rect.top - headerHeight - this.extraOffset;
        }
        // If bottom is below viewport
        else if (rect.bottom > viewportHeight) {
            targetY = scrollY + rect.bottom - viewportHeight + this.extraOffset;
        }

        window.scrollTo({
            top: Math.max(0, targetY),
            behavior: this.behavior
        });
    }
}
