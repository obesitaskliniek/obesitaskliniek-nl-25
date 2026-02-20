/**
 * NOK Lightbox — Full-screen image viewer.
 *
 * Lazy-loaded via `data-requires="./nok-lightbox.mjs"` on image containers
 * that also have `data-lightbox-src` pointing to the full-size image URL.
 *
 * Creates the overlay element on first use and reuses it for subsequent opens.
 * Closes on backdrop click, close button, or Escape key.
 *
 * @module nok-lightbox
 */

export const NAME = 'lightbox';

/** @type {HTMLElement|null} */
let overlay = null;

/** @type {AbortController|null} */
let controller = null;

/**
 * Creates the lightbox overlay element (once, appended to body).
 *
 * @returns {HTMLElement}
 */
function getOverlay() {
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.className = 'nok-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Afbeelding vergroten');

    overlay.innerHTML =
        `<button class="nok-lightbox__close" aria-label="Sluiten" type="button">` +
            `<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">` +
                `<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>` +
            `</svg>` +
        `</button>` +
        `<img class="nok-lightbox__image" alt="" />`;

    document.body.appendChild(overlay);
    return overlay;
}

/**
 * Opens the lightbox with given image source.
 *
 * @param {string} src Full-size image URL
 * @param {string} alt Alt text for the image
 */
function open(src, alt) {
    // Clean up any previous open state
    controller?.abort();

    const el = getOverlay();
    const img = el.querySelector('.nok-lightbox__image');

    // Set up close handlers
    controller = new AbortController();
    const {signal} = controller;

    el.addEventListener('click', handleOverlayClick, {signal});
    document.addEventListener('keydown', handleKeydown, {signal});

    // Load image
    img.alt = alt;
    img.classList.remove('nok-lightbox__image--loaded');
    img.src = src;
    img.onload = () => img.classList.add('nok-lightbox__image--loaded');
    img.onerror = () => close();

    // Trigger open transition on next frame
    requestAnimationFrame(() => {
        el.classList.add('nok-lightbox--open');
        document.body.classList.add('nok-lightbox-active');
        el.querySelector('.nok-lightbox__close')?.focus();
    });
}

/**
 * Closes the lightbox.
 */
function close() {
    if (!overlay) return;

    overlay.classList.remove('nok-lightbox--open');
    document.body.classList.remove('nok-lightbox-active');

    controller?.abort();
    controller = null;

    // Clear image after a short delay (covers transition duration).
    // No transitionend dependency — works with prefers-reduced-motion.
    const img = overlay.querySelector('.nok-lightbox__image');
    if (img) {
        setTimeout(() => {
            if (!overlay.classList.contains('nok-lightbox--open')) {
                img.src = '';
                img.classList.remove('nok-lightbox__image--loaded');
            }
        }, 500);
    }
}

/**
 * @param {MouseEvent} e
 */
function handleOverlayClick(e) {
    // Close when clicking backdrop or close button, not the image itself
    if (e.target.closest('.nok-lightbox__close') || !e.target.closest('.nok-lightbox__image')) {
        close();
    }
}

/**
 * @param {KeyboardEvent} e
 */
function handleKeydown(e) {
    if (e.key === 'Escape') close();
}

/**
 * Initializes lightbox triggers on provided elements.
 *
 * Each element should have `data-lightbox-src` with the full-size image URL.
 * The module adds click handling, keyboard support, and a visual cursor hint.
 *
 * @param {HTMLElement[]} elements Elements with data-lightbox-src
 */
export function init(elements) {
    elements.forEach(element => {
        if (!(element instanceof Element)) return;

        const src = element.dataset.lightboxSrc;
        if (!src) return;

        element.setAttribute('role', 'button');
        element.setAttribute('tabindex', '0');
        element.setAttribute('aria-label', 'Klik om afbeelding te vergroten');
        element.classList.add('nok-lightbox-trigger');

        const alt = element.querySelector('img')?.alt || '';

        element.addEventListener('click', () => open(src, alt));
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open(src, alt);
            }
        });
    });
}

/**
 * Cleanup for SPA compatibility.
 */
export function destroy() {
    close();
    if (overlay) {
        overlay.remove();
        overlay = null;
    }
}
