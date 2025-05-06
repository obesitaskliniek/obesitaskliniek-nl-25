import {setupFakeScrollbar} from "./modules/hnl.draggable.mjs";

export const NAME = 'autoscroll';

export function init(elements) {
  elements.forEach(el => {
    const base       = Math.max(1000, +el.dataset.interval || 10000);
    const children   = Array.from(el.children);
    const len        = children.length;
    let   lastInteract = 0;
    let   index        = 0;

    // reset idle timer on any user interaction
    ['pointerdown','wheel','touchstart','mouseenter','keydown'].forEach(evt =>
        el.addEventListener(evt, () => { lastInteract = Date.now() }, { passive: true })
    );

    // self-scheduling loop
    (function tick() {
      if (Date.now() - lastInteract >= (base * 2)) {
        children[index].scrollIntoView({
          behavior: 'smooth',
          block:    'nearest',
          inline:   'nearest'
        });
        index = (index + 1) % len;
      }
      setTimeout(tick, base);
    })();
  });
}

