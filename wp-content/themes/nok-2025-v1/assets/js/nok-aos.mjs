/***
 * Lightweight scroll animation observer using IntersectionObserver API.
 * Marks elements as visible/invisible via data attributes based on viewport intersection.
 * Supports dynamic thresholds, per-element configuration, and automatic tall-element handling.
 *
 * @version 1.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / Klaas Leussink / hnldesign
 * @copyright 2025 Klaas Leussink / hnldesign
 *
 * @param {Object} options - Configuration options
 * @param {string} [options.selector='[data-aos]'] - Element selector
 * @param {string} [options.dataName='visible'] - Data attribute name for visibility state
 * @param {number} [options.offset=120] - Trigger offset in pixels
 * @param {boolean} [options.once=false] - Animate only once
 * @param {boolean} [options.mirror=false] - Reverse animation on scroll up
 * @param {string} [options.anchorPlacement='top-bottom'] - Trigger anchor point (top|center|bottom - top|center|bottom)
 * @param {boolean} [options.disableMutationObserver=false] - Disable dynamic element detection
 * @param {number|string} [options.threshold=0.1] - Visibility threshold (0-1 or '0%-100%')
 *
 * @example
 * import AOS from './nok-aos.mjs';
 * const aos = AOS.init({ threshold: 0.5, once: true });
 *
 * // HTML: <div data-aos data-aos-threshold="75%">Content</div>
 */
class AOS {
  constructor(options = {}) {
    this.options = {
      selector: options.selector ?? '[data-aos]',
      dataName: options.dataName ?? 'visible',
      offset: options.offset ?? 120,
      once: options.once ?? false,
      mirror: options.mirror ?? false,
      anchorPlacement: options.anchorPlacement ?? 'top-bottom',
      disableMutationObserver: options.disableMutationObserver ?? false,
      threshold: options.threshold ?? 0.1,
    };

    this.elements = [];
    this.observer = null;
    this.mutationObserver = null;
    this.initialized = false;
  }

  init() {
    if (this.initialized) return;

    this.elements = Array.from(document.querySelectorAll(this.options.selector));
    if (!this.elements.length) return;

    this.elements.forEach(el => {
      el.classList.add('nok-aos');
      el.dataset[this.options.dataName] = 'false';
      if (!el.dataset.aosOnce) el.dataset.aosOnce = this.options.once;
    });

    this.setupIntersectionObserver();
    if (!this.options.disableMutationObserver) this.setupMutationObserver();

    this.initialized = true;
  }

  setupIntersectionObserver() {
    this.observer = new IntersectionObserver(
        entries => {
          entries.forEach(entry => {
            const el = entry.target;
            const requestedThreshold = this.parseThreshold(el.dataset.aosThreshold ?? this.options.threshold);
            const maxRatio = Math.min(1, (entry.rootBounds?.height ?? window.innerHeight) / entry.boundingClientRect.height);
            const effectiveThreshold = Math.min(requestedThreshold, maxRatio * 0.99);

            if (entry.intersectionRatio >= effectiveThreshold) {
              el.dataset[this.options.dataName] = 'true';
            } else if (this.options.mirror && el.dataset.aosOnce !== 'true') {
              el.dataset[this.options.dataName] = 'false';
            }
          });
        },
        {
          rootMargin: this.calculateRootMargin(),
          threshold: Array.from({ length: 101 }, (_, i) => i / 100)
        }
    );

    this.elements.forEach(el => this.observer.observe(el));
  }

  parseThreshold(value) {
    return typeof value === 'string' && value.endsWith('%')
        ? parseFloat(value) / 100
        : parseFloat(value);
  }

  setupMutationObserver() {
    this.mutationObserver = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
          if (node.nodeType !== 1) return;

          if (node.matches(this.options.selector)) {
            this.addElement(node);
          }
          node.querySelectorAll(this.options.selector).forEach(el => this.addElement(el));
        });
      });
    });

    this.mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  addElement(el) {
    if (this.elements.includes(el)) return;

    el.classList.add('nok-aos');
    el.dataset[this.options.dataName] = 'false';
    if (!el.dataset.aosOnce) el.dataset.aosOnce = this.options.once;

    this.elements.push(el);
    this.observer?.observe(el);
  }

  calculateRootMargin() {
    const [anchor, placement] = this.options.anchorPlacement.split('-');
    const offset = this.options.offset;

    return `${anchor === 'top' ? offset : 0}px 0px ${placement === 'bottom' ? offset : 0}px 0px`;
  }

  refresh() {
    this.observer?.disconnect();
    this.elements = Array.from(document.querySelectorAll(this.options.selector));
    this.setupIntersectionObserver();
  }

  refreshHard() {
    this.destroy();
    this.init();
  }

  destroy() {
    this.observer?.disconnect();
    this.mutationObserver?.disconnect();

    this.elements.forEach(el => delete el.dataset[this.options.dataName]);
    this.elements = [];
    this.initialized = false;
  }
}

export function init(options = {}) {
  const aos = new AOS(options);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => aos.init());
  } else {
    aos.init();
  }

  return aos;
}

export default { init };