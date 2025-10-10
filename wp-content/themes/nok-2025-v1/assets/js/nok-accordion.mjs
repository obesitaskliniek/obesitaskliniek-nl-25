import {ViewportScroller} from "./domule/util.ensure-visibility.mjs";
/**
 * This is a polyfill for the <details> element,
 * intended for browsers that don't support the CSS property `interpolate-size: allow-keywords`
 *
 * Taken from https://css-tricks.com/how-to-animate-the-details-element-using-waapi/
 * Modified by hnldesign @ 5-2025
 */
export const NAME = 'accordion';

const CSSSupport = CSS ? CSS.supports('interpolate-size', 'allow-keywords') : false;

const AccordionGroups = new Map();
const AccordionInstances = new WeakMap();

/**
 * A group holds:
 *  - busy:     boolean
 *  - accordions: Set<HTMLElement>
 */
function makeGroup() {
  return {
    busy: false,
    accordions: new Set(),
  };
}

class Accordion {
  constructor(el) {
    // Store the <details> element
    this.el = el;

    this.computedStyle = window.getComputedStyle(this.el);
    this.transitionDuration = parseInt(this.computedStyle.getPropertyValue('--animation-duration')) || 750;
    this.transitionEasing = this.computedStyle.getPropertyValue('--animation-timing') || 'cubic-bezier(0.16, 1, 0.3, 1)';

    // Start observing the element
    this._visibilityCorrector = new ViewportScroller(el, {
      behavior: 'smooth',    // or 'auto'
      extraOffset: 20        // e.g. leave 20px of breathing room
    });

    if (!CSSSupport) {
      // Store the <summary> element
      this.summary = el.querySelector('summary');
      // Store the <div class="content"> element
      this.content = el.querySelector('.accordion-content');
      // Store the animation object (so we can cancel it if needed)
      this.animation = null;
      // Store if the element is closing
      this.isClosing = false;
      // Store if the element is expanding
      this.isExpanding = false;
      // Detect user clicks on the summary element
      this.summary.addEventListener('click', (e) => this.toggle(e));
      // Setup state object
      AccordionInstances.set(this.el, this);
      // Get the name, if any, of the accordion
      this.name = this.el.getAttribute('name');

      // new, correct Map‐based code
      this.groupRef = this.name || this.el;
      let group = AccordionGroups.get(this.groupRef);
      if (!group) {
        group = makeGroup();
        AccordionGroups.set(this.groupRef, group);
      }
      group.accordions.add(this.el);
      this.group = group;    // store ref for easy access later

      // Track if the accordion has been opened after initialization
      this.opened = this.el.open || false;
    } else {
      // Handle visibility for native <details> elements
      this.el.addEventListener('toggle', (e) => this.maintainVisibility());
    }
  }

  /**
   * Handle native <details> toggle: after the open transition ends,
   * scroll into view if needed, but cancel if the user scrolls first.
   * @private
   */
  maintainVisibility() {

    const onTransitionEnd = () => {
      if (!this.el.open) return;
      this._visibilityCorrector.ensureVisible();
    };
    // Listen once for transitionend on this element
    this.el.addEventListener('transitionend', onTransitionEnd, { once: true });

    // If user scrolls before transition completes, cancel the visibility ensure
    window.addEventListener('scroll', () => {
      this.el.removeEventListener('transitionend', onTransitionEnd);
    }, { once: true });
  }

  animateHeight(startH, endH, onfinish) {
    // Stop any running animations
    this.animation?.finish();
    // Set group or individual accordion as busy
    this.group.busy = true;
    // Start a WAAPI animation
    this.animation = this.el.animate(
        { height: [startH, endH] },
        { duration: this.transitionDuration, easing: this.transitionEasing, fill: 'forwards' }
    );
    this.animation.onfinish = () => {
      // Set group or individual accordion as no longer busy
      this.group.busy = false;
      // Initiate callback
      onfinish();
    };
  }

  toggle(e) {
    // Stop default behaviour from the browser
    e.preventDefault();
    // Early return if the group is busy
    if (this.group.busy) return;
    // Add an overflow on the <details> to avoid content overflowing
    this.el.style.overflow = 'hidden';
    // Check if the element is being closed or is already closed
    if (this.isClosing || !this.el.open) {
      this.open();
      // Check if the element is being openned or is already open
    } else if (this.isExpanding || this.el.open) {
      this.collapse();
    }
  }

  open() {
    this.currentScrollY = window.scrollY;
    // Check if there are siblings that need to be closed first
    if (this.group.accordions.size > 1) {
      if (this.name) {
        // Temporarily remove name attribute to prevent native browser auto-closing
        this.el.removeAttribute('name');
      }
      for (const otherEl of this.group.accordions) {
        if (otherEl !== this.el && otherEl.open) {
          AccordionInstances.get(otherEl).collapse();
        }
      }
    }

    // Apply a fixed height on the element
    this.el.style.height = `${this.el.offsetHeight}px`;
    // Force the [open] attribute on the details element
    this.el.open = true;
    // Defer expand to second frame to allow layout to update - twice, to fix a layout bug in safari
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => this.expand());
    });
  }

  collapse() {
    // Set the element as "being closed"
    this.isClosing = true;
    this.el.classList.add('accordion-closing');

    // Store the current height of the element
    const startHeight = `${this.el.offsetHeight}px`;
    // Calculate the height of the summary
    const endHeight = `${this.summary.offsetHeight}px`;

    // Start a WAAPI animation
    this.animateHeight(startHeight, endHeight, () => this.onAnimationFinish(false));
  }

  expand() {
    // Set the element as "being expanding"
    this.isExpanding = true;
    this.el.classList.remove('accordion-closing');

    const startHeight = `${this.el.offsetHeight}px`;
    // Calculate the open height of the element (summary height + content height)
    const endHeight = `${this.summary.offsetHeight + this.content.offsetHeight}px`;

    // Start a WAAPI animation
    this.animateHeight(startHeight, endHeight, () => this.onAnimationFinish(true));
  }

  onAnimationFinish(open) {
    // Set the open attribute based on the parameter
    this.el.open = open;
    // Mirror the <details> state in aria-expanded on the <summary>
    this.summary.setAttribute('aria-expanded', String(open));
    // Clear the stored animation
    this.animation = null;
    // Reset isClosing & isExpanding
    this.isClosing = false;
    this.el.classList.remove('accordion-closing');
    this.isExpanding = false;
    // Remove the overflow hidden and the fixed height
    this.el.style.height = this.el.style.overflow = '';
    // Restore group name for native browser functionality
    if (this.name) {
      // Restore name attribute after allowing toggle (optional, for semantics)
      this.el.setAttribute('name', this.name);
    }
    if (open && this.currentScrollY === window.scrollY) {
      // Maintain visibility, if user hasn't scrolled in the meantime
      this._visibilityCorrector.ensureVisible();
    }
  }
}

export function init(elements){
  elements.forEach(element => {
    element.querySelectorAll('details').forEach((details) => {
      new Accordion(details);
    })
  });
  if (CSSSupport) {
    return 'No need for polyfill - browser has native support.'
  }
}