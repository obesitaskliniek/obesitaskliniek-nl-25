/*
  Scrollbar emulator for horizontal scrolling elements.
  This module creates a custom scrollbar for elements with horizontal overflow,
  allowing for better user interaction and visual consistency across browsers.

  It supports features like snapping, autoscrolling, and pointer-based dragging.

  Also, you can control a scroll container using data-scroll-target and data-scroll-action attributes:

  <!-- Basic navigation buttons -->
  <button data-scroll-target="my-scrollcontainer" data-scroll-action="forward">→</button>
  <button data-scroll-target="my-scrollcontainer" data-scroll-action="backward">←</button>

  <!-- More specific actions -->
  <a href="#" data-scroll-target="gallery" data-scroll-action="next">Next Image</a>
  <a href="#" data-scroll-target="gallery" data-scroll-action="first">First Image</a>
  <a href="#" data-scroll-target="gallery" data-scroll-action="last">Last Image</a>

  <!-- Jump to specific slide (for snapping containers) -->
  <button data-scroll-target="carousel" data-scroll-action="0">Slide 1</button>
  <button data-scroll-target="carousel" data-scroll-action="2">Slide 3</button>

  <!-- Disable smooth scrolling for instant movement -->
  <button data-scroll-target="list" data-scroll-action="forward" data-scroll-smooth="false">Skip →</button>
 */

import eventHandler from './modules/hnl.eventhandler.mjs';
import {isVisible} from "./modules/hnl.helpers.mjs";
import mediaInfo from "./modules/helper.media-info.mjs";

export const NAME = "scrollBarEmulator";

function RAFThrottle(callback) {
  let ticking = false;
  return (...args) => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      callback(...args);
      ticking = false;
    });
  };
}

function disableSnapping(scrollElement) {
  scrollElement.dataset.scrollSnapping = 'false';
}
function restoreSnapping(scrollElement) {
  scrollElement.dataset.scrollSnapping = 'true';
}

function restoreSnappingGracefully(scrollElement) {
  const { scrollWidth : scrollSize, scrollLeft : scrollPosition, offsetWidth : scrollerSize } = scrollElement;
  const snapItem = scrollElement.children[0];
  //note: this assumes all items are the same inline size
  const gap = parseInt(window.getComputedStyle(snapItem.parentElement).columnGap, 10) || 0;
  const slideItemSize = snapItem.offsetWidth + gap;
  const closestSnap = Math.round(scrollPosition / slideItemSize);
  const tolerance = 2;
  let timeout = null;

  function waitToRestoreSnapping() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      restoreSnapping(scrollElement);
      scrollElement.removeEventListener('scroll', waitToRestoreSnapping);
    }, scrollPosition % slideItemSize ? 150 : 0);
  }

  if (Math.abs(scrollPosition) < tolerance || Math.abs(scrollPosition + scrollerSize - scrollSize) < tolerance) {
    scrollElement.removeEventListener('scroll', waitToRestoreSnapping);
  } else {
    scrollElement.scrollTo({
      left: closestSnap * slideItemSize,
      behavior: 'smooth'
    });
    scrollElement.addEventListener('scroll', waitToRestoreSnapping);
  }
}

// Simple registry to track scroll containers by ID
const scrollContainers = new Map();

// New function to setup scroll controls
export function setupScrollbarControl(controlElement) {
  const targetId = controlElement.dataset.scrollTarget;
  const action = controlElement.dataset.scrollAction || 'forward';
  const smooth = controlElement.dataset.scrollSmooth !== 'false';

  if (!targetId) {
    console.warn(controlElement, 'Control element missing data-scroll-target attribute');
    return;
  }

  controlElement.addEventListener('click', (e) => {
    e.preventDefault();
    handleScrollAction(targetId, action, smooth);
  });

  // Add keyboard support for buttons
  if (controlElement.tagName === 'BUTTON') {
    controlElement.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        handleScrollAction(targetId, action, smooth);
      }
    });
  }
}

// Handle the actual scrolling logic
function handleScrollAction(targetId, action, smooth) {
  const container = scrollContainers.get(targetId);
  if (!container) {
    console.warn(`Scroll container with ID "${targetId}" not found`);
    return;
  }

  const { element: scrollElement, isSnapping } = container;
  const { scrollWidth, clientWidth, scrollLeft } = scrollElement;
  const maxScroll = scrollWidth - clientWidth;

  let targetScroll;

  if (isSnapping) {
    // Calculate snap positions
    const children = Array.from(scrollElement.children);
    if (children.length === 0) return;

    const firstChild = children[0];
    const gap = parseInt(window.getComputedStyle(firstChild.parentElement).columnGap, 10) || 0;
    const slideItemSize = firstChild.offsetWidth + gap;
    const currentIndex = Math.round(scrollLeft / slideItemSize);

    switch (action) {
      case 'forward':
      case 'next':
        const nextIndex = currentIndex + 1 >= children.length ? 0 : currentIndex + 1;
        targetScroll = nextIndex * slideItemSize;
        break;
      case 'backward':
      case 'prev':
      case 'previous':
        const prevIndex = currentIndex <= 0 ? children.length - 1 : currentIndex - 1;
        targetScroll = prevIndex * slideItemSize;
        break;
      case 'first':
        targetScroll = 0;
        break;
      case 'last':
        targetScroll = (children.length - 1) * slideItemSize;
        break;
      default:
        // Numeric index
        const index = parseInt(action, 10);
        if (!isNaN(index) && index >= 0 && index < children.length) {
          targetScroll = index * slideItemSize;
        }
    }
  } else {
    // Non-snapping: scroll by percentage of visible width
    const scrollAmount = clientWidth * 0.8;

    switch (action) {
      case 'forward':
      case 'next':
        targetScroll = scrollLeft + scrollAmount;
        if (targetScroll >= maxScroll) targetScroll = 0; // Loop to start
        break;
      case 'backward':
      case 'prev':
      case 'previous':
        targetScroll = scrollLeft - scrollAmount;
        if (targetScroll <= 0) targetScroll = maxScroll; // Loop to end
        break;
      case 'first':
        targetScroll = 0;
        break;
      case 'last':
        targetScroll = maxScroll;
        break;
      default:
        // Numeric scroll position
        const position = parseInt(action, 10);
        if (!isNaN(position)) {
          targetScroll = Math.min(position, maxScroll);
        }
    }
  }

  if (targetScroll !== undefined) {
    // Handle snapping behavior during programmatic scroll
    if (isSnapping) {
      disableSnapping(scrollElement);
    }

    scrollElement.scrollTo({
      left: Math.max(0, Math.min(targetScroll, maxScroll)),
      behavior: smooth ? 'smooth' : 'auto'
    });

    // Restore snapping after scroll
    if (isSnapping && smooth) {
      setTimeout(() => {
        restoreSnapping(scrollElement);
      }, 500);
    } else if (isSnapping) {
      restoreSnapping(scrollElement);
    }
  }
}

// Optional: Export for programmatic control
export function controlScroll(targetId, action, smooth = true) {
  handleScrollAction(targetId, action, smooth);
}

export function setupFakeScrollbar(scrollElement) {
  if (scrollElement.id) {
    scrollContainers.set(scrollElement.id, {
      element: scrollElement,
      isSnapping: scrollElement.dataset.scrollSnapping === 'true'
    });
  }

  const SNAPPING = scrollElement.dataset.scrollSnapping === 'true';

  // build the new nodes
  const scrollbarTrack = document.createElement('div');
  scrollbarTrack.className = 'fake-scrollbar align-self-stretch';

  const scrollbarThumb = document.createElement('div');
  scrollbarThumb.className = 'fake-scrollbar-thumb';

  // assemble & insert
  scrollbarTrack.appendChild(scrollbarThumb);
  scrollElement.parentNode.insertBefore(
      scrollbarTrack,
      scrollElement.nextSibling
  );

  const cssSScrollStyle = window.getComputedStyle(scrollElement).overflowX;

  if (cssSScrollStyle === 'hidden') {
    scrollbarTrack.style.visibility = 'hidden';
    return;
  }

  // Object to store styles and dimensions
  const style = { width: null, transform: null, clientWidth: null, scrollWidth: null, scrollLeft: null };

  function updateSelf() {
    const { scrollWidth, clientWidth, scrollLeft } = scrollElement;

    // Avoid unnecessary updates if dimensions haven't changed
    if (style.clientWidth === clientWidth && style.scrollWidth === scrollWidth && style.scrollLeft === scrollLeft) {
      return;
    }

    // Hide thumb if nothing is overflowing (simulates native)
    scrollbarThumb.style.visibility = (scrollWidth <= clientWidth) ? 'hidden' : '';
    scrollbarTrack.style.visibility = (scrollWidth <= clientWidth) ? (cssSScrollStyle === 'auto' ? 'hidden' : '') : '';

    style.clientWidth = clientWidth;
    style.scrollWidth = scrollWidth;

    style.maxScroll = scrollWidth - clientWidth;
    if (style.maxScroll <= 0) {
      if (style.width !== "0px") {
        scrollbarThumb.style.width = "0px"; // Hide if no overflow
        style.width = "0px";
      }
      return;
    }

    const thumbWidth = Math.round((clientWidth / scrollWidth) * clientWidth);
    const thumbPosition = Math.round(((scrollLeft / style.maxScroll) * (clientWidth - thumbWidth)) * 10) / 10;
    const newStyles = {};

    // Only update transform if scrollLeft changed
    if (style.scrollLeft !== scrollLeft) {
      style.scrollLeft = scrollLeft;
      newStyles.transform = style.transform = `translate3d(${thumbPosition}px, 0, 0)`; // Use translate3d for better performance
    }

    // Only update width if necessary
    if (style.width !== `${thumbWidth}px`) {
      newStyles.width = style.width = `${thumbWidth}px`;
    }

    if (Object.keys(newStyles).length > 0) {
      Object.assign(scrollbarThumb.style, newStyles);
    }

    // Update own extended dimensions
    //style.dimensions = scrollbarThumb.getBoundingClientRect();
  }

  // Throttle the update function
  const updateScrollbar = RAFThrottle(updateSelf);

  eventHandler.addListener('docShift', updateScrollbar);
  scrollElement.addEventListener('scroll', updateScrollbar, { passive: true });

  function handlePointerUp(el, pointerId, moveFn, upFn, snapping) {
    if (snapping) restoreSnappingGracefully(scrollElement);
    scrollElement.style.scrollBehavior = '';
    scrollElement.classList.remove('being-scrolled', 'grabbed-scrollbar');
    el.releasePointerCapture(pointerId);
    el.removeEventListener('pointermove', moveFn);
    el.removeEventListener('pointerup',   upFn);
    el.removeEventListener('pointercancel', upFn);
  }

  function bindMoveEvents(el, move, cancel) {
    el.addEventListener('pointermove',  move);
    el.addEventListener('pointerup',    cancel);
    el.addEventListener('pointercancel', cancel);
  }

  scrollbarTrack.addEventListener('pointerdown', (downEvt) => {
    downEvt.preventDefault();             // don’t let native text-select or stray clicks interfere

    //stop scroll snapping from messing up scrolling
    if (SNAPPING) { disableSnapping(scrollElement); }
    //stop smooth scrolling from messing up scrolling
    scrollElement.style.scrollBehavior = 'auto';
    scrollElement.classList.add('being-scrolled');
    scrollElement.classList.add('grabbed-scrollbar');

    // capture initial positions
    const startX = downEvt.clientX;
    let startScroll = scrollElement.scrollLeft;
    const { scrollWidth, clientWidth } = scrollElement;

    // compute the draggables’ ranges
    const trackRect = scrollbarTrack.getBoundingClientRect();
    const thumbRect = scrollbarThumb.getBoundingClientRect();
    const maxScroll = scrollWidth - clientWidth;
    const maxThumbOffset = trackRect.width - thumbRect.width;

    if (scrollbarTrack === downEvt.target) {
      //if track is clicked, immediately scroll to that position and continue dragging
      scrollElement.dataset.scrollSnapping = "false";

      const clickX     = downEvt.clientX - trackRect.left;
      //const clickY     = downEvt.clientY - trackRect.top; // todo: make suitable for vertical scrolling
      const halfThumb  = thumbRect.width / 2;
      // clamp the thumb’s left edge inside [0, maxThumbOffset]:
      const thumbOff   = Math.min(
          Math.max(clickX - halfThumb, 0),
          maxThumbOffset
      );
      const newScroll  = (thumbOff / maxThumbOffset) * maxScroll;
      scrollElement.scrollLeft = startScroll = newScroll;
    }

    // capture subsequent moves on the track itself
    const track = downEvt.currentTarget;
    track.setPointerCapture(downEvt.pointerId);

    // move handler: map deltaX → new scrollLeft
    function onPointerMove(moveEvt) {
      const deltaX = moveEvt.clientX - startX;
      // where the thumb *would* be, clamped to [0 .. maxThumbOffset]
      const thumbPos = Math.min(
          Math.max((startScroll / maxScroll) * maxThumbOffset + deltaX, 0),
          maxThumbOffset
      );
      // drive the real scroll
      scrollElement.scrollLeft = (thumbPos / maxThumbOffset) * maxScroll;
      // updateScrollbar() will fire via the scroll listener
    }

    function onPointerUp(upEvt) {
      handlePointerUp(track, upEvt.pointerId, onPointerMove, onPointerUp, SNAPPING);
    }

    bindMoveEvents(track, onPointerMove, onPointerUp);
  });



  // ────────────────────────────────────────────────────────────────────────────────
  // “Draggable” mode: makes the content itself touch-draggable on desktop
  // ────────────────────────────────────────────────────────────────────────────────
  if (scrollElement.dataset.draggable === 'true' && window.PointerEvent) {
    scrollElement.addEventListener('pointerdown', contentPointerDown, { passive: false });
  }

  function contentPointerDown(e) {
    // only mice; leave real touch alone
    if (e.pointerType !== 'mouse') return;
    // get the original hit target (works even through shadow DOM):
    const origTgt = e.composedPath ? e.composedPath()[0] : e.target;
    // just in case
    if (!(origTgt instanceof Element)) return;
    // look for the nearest <a> *or* <button> on or above it
    const control = origTgt.closest('a, button');
    // don't handle events that started on buttons or anchors.
    if (control) return;

    e.preventDefault();

    //stop scroll snapping from messing up scrolling
    if (SNAPPING) { disableSnapping(scrollElement); }
    //stop smooth scrolling from messing up scrolling
    scrollElement.style.scrollBehavior = 'auto';
    scrollElement.classList.add('being-scrolled');

    const startX      = e.clientX;
    const startScroll = scrollElement.scrollLeft;

    // capture moves on this element
    scrollElement.setPointerCapture(e.pointerId);

    function onMove(moveEvt) {
      const deltaX = moveEvt.clientX - startX;
      // drag the content (invert because dragging right scrolls left)
      scrollElement.scrollLeft = startScroll - deltaX;
    }

    function onUp(upEvt) {
      handlePointerUp(scrollElement, upEvt.pointerId, onMove, onUp, SNAPPING);
    }

    bindMoveEvents(scrollElement, onMove, onUp);
  }



  // ────────────────────────────────────────────────────────────────────────────────
  // “Autoscroll” mode: when idle, snap-scroll every N ms
  // ────────────────────────────────────────────────────────────────────────────────
  if (scrollElement.dataset.autoscroll === 'true' && !mediaInfo('prefers-reduced-motion')) {
    const interval   = Math.max(1000, +scrollElement.dataset.interval || 10000);
    const children   = Array.from(scrollElement.children);
    let timerId = null; let waitTimer = null;

    function getNextChildIndex() {
      //figure out where we are: get the first (left-most) visible child idx
      const currentChildIndex = children.indexOf(children.find(child =>
          child.offsetLeft + child.offsetWidth > scrollElement.scrollLeft
      ));
      return currentChildIndex + 1 <= children.length ? currentChildIndex + 1 : 0;
    }

    function go() {
      clearInterval(timerId);
      timerId = setInterval(() => {
        isVisible(scrollElement, function(visible) {
          if (visible && scrollElement.dataset.autoscroll === 'true') {
            const reachedEnd = Math.abs(scrollElement.scrollLeft + scrollElement.offsetWidth - scrollElement.scrollWidth) < 10;
            scrollElement.scrollLeft = reachedEnd ? 0 : children[getNextChildIndex()].offsetLeft;
          }
        })
      }, interval);
    }

    [scrollElement, scrollbarTrack].forEach(el => {
      ['pointerdown', 'wheel', 'touchstart', 'mouseenter', 'keydown'].forEach(evt =>
          el.addEventListener(evt, () => {
            clearInterval(timerId);
          }, { passive: true })
      );
      ['mouseleave'].forEach(evt =>
          el.addEventListener(evt, () => {
            clearInterval(waitTimer);
            waitTimer = setTimeout(go, interval);
          }, { passive: true })
      );
    })
    eventHandler.addListener('docShift', () => {
      //will only restart autoscrolling when the user has scrolled past the element, and it has gone out of view
      isVisible(scrollElement, function(visible) {
        if (!timerId && !visible) {
          clearInterval(waitTimer);
          waitTimer = setTimeout(go, interval);
        }
      })
    });

    //initialize run
    go();

  }

  updateScrollbar(); // Initial update
}