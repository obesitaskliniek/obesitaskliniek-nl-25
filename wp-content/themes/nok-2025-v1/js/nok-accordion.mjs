export const NAME = 'accordion';

const CSSSupport = typeof CSS !== 'undefined'
    && CSS.supports('interpolate-size', 'allow-keywords');

const AccordionGroups = new Map();
const transition = {
  duration: 750,
  easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
};

class Accordion {
  constructor(detailsEl) {
    this.el           = detailsEl;
    this.summary      = detailsEl.querySelector('summary');
    this.content      = detailsEl.querySelector('.accordion-content');
    this.name         = detailsEl.getAttribute('name');        // original name, or null
    this.groupKey     = this.name || detailsEl;
    this.group        = this._getOrCreateGroup(this.groupKey);
    this.animation    = null;
    this.isBusy       = false;
    this.forcedByBP   = false;                                 // track if we’re in “forced” mode

    // register
    this.group.add(this);
    this.summary.addEventListener('click', e => this.toggle(e));

    // breakpoint-driven open/close
    const bp = detailsEl.dataset.openedAt;
    if (bp) {
      document.addEventListener('breakPointChange', e => {
        const shouldOpen = e.detail.matchesAll.includes(bp);

        if (shouldOpen && !this.el.open) {
          this._instantOpen();
        }
        if (!shouldOpen && this.forcedByBP) {
          this._instantClose();
        }
      });

      // initial kick-start based on the current global breakpoint snapshot
      if (window.__BREAKPOINT__.matchesAll.includes(bp)) {
        this._instantOpen();
      }
    }
  }

  _getOrCreateGroup(key) {
    if (!AccordionGroups.has(key)) AccordionGroups.set(key, new Set());
    return AccordionGroups.get(key);
  }

  _instantOpen() {
    // remove native name to avoid mutual‐exclusion
    if (this.name) this.el.removeAttribute('name');
    this.forcedByBP = true;
    this._finish(true);
  }
  _instantClose() {
    this.forcedByBP = false;
    this._finish(false);
    // restore the original name so native behavior is back in play
    if (this.name) this.el.setAttribute('name', this.name);
  }

  toggle(e) {
    e.preventDefault();
    if (this.groupBusy()) return;
    this.el.style.overflow = 'hidden';

    const isOpening = !this.el.open && !this.isBusy;
    if (isOpening)     this._closeOthersThenOpen();
    else               this._animate(false);

  }

  _closeOthersThenOpen() {
    if (this.group.size > 1) {
      // temporarily disable native mutual-exclusion
      if (this.name) this.el.removeAttribute('name');
      for (const other of this.group) {
        if (other !== this && other.el.open) {
          other._animate(false);
        }
      }
    }
    this._animate(true);
  }

  groupBusy() {
    for (const acc of this.group) {
      if (acc.isBusy) return true;
    }
    return false;
  }

  _animate(expand) {
    // cancel any running animation
    this.animation?.finish();
    this.isBusy = true;

    // prepare heights
    const startH = `${this.el.offsetHeight}px`;
    if (expand) {
      this.el.open = true;
      // Force layout
      this.content.offsetHeight;
    }
    const endH = expand
        ? `${this.summary.offsetHeight + this.content.offsetHeight}px`
        : `${this.summary.offsetHeight}px`;

    this.animation = this.el.animate(
        { height: [ startH, endH ] },
        { duration: transition.duration,
          easing:   transition.easing,
          fill:     'forwards' }
    );

    this.animation.onfinish = () => {
      this._finish(expand);
    };
  }

  _finish(open) {
    window.requestAnimationFrame(() =>{
      this.el.open = open;
      this.summary.setAttribute('aria-expanded', open);
      this.el.style.height   = '';
      this.el.style.overflow = '';

      // only restore name when _not_ forced open
      if (!this.forcedByBP && this.name) {
        this.el.setAttribute('name', this.name);
      }

      this.isBusy    = false;
      this.animation = null;
    })
  }
}

export function init(elements) {
  if (CSSSupport) {
    return 'Browser supports <details> natively—no script needed.';
  }
  elements.forEach(root =>
      root.querySelectorAll('details').forEach(d => new Accordion(d))
  );
}
