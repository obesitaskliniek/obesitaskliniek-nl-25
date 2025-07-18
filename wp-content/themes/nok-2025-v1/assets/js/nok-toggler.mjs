/*
Universal single click toggler (c)2025 Klaas Leussink / hnldesign

usage:
<div data-toggles="open" data-target=".nok-nav-control-dropdown">Click me</div>

optionally add data-toggle-permanent="true" to keep the target open even if clicked outside:
<div data-toggles="open" data-target=".nok-nav-control-dropdown" data-toggle-permanent="true">Click me</div>

 */
import {singleClick} from "./modules/hnl.clickhandlers.mjs";

export const NAME = 'simpleToggler';


function swipeToClose(element, closeCallback, direction = 'y', min = -9999, max = 0) {
  let start = 0, current = 0, isDragging = false;
  const touchOnly = false;

  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

  function getCoords(e) {
    let source = e.touches?.[0] || e.changedTouches?.[0] || e;
    return { x: source.clientX, y: source.clientY };
  }

  function drag(e) {
    current = getCoords(e)[direction];
    isDragging = current !== start;
    if (!isDragging) return;
    e.preventDefault();
    if (element.style.userSelect !== "none") element.style.userSelect = "none"; // Prevent selection
    element.style.transition = "none";
    //element.style.transform = `translate${direction.toUpperCase()}(${clamp(current - start, min, max)}px)`;
    element.style.transform = direction === 'x'
        ? `translate3d(${clamp(current - start, min, max)}px, 0, 0)`
        : `translate3d(0, ${clamp(current - start, min, max)}px, 0)`;

  }

  function pointerUp(e) {
    if (isDragging) {
      let threshold = (direction === 'x' ? element.clientWidth : element.clientHeight) / 4;
      element.style.transition = "transform 0.25s ease-out";
      element.removeEventListener('transitionend', resetStyles); // Avoid duplicate calls
      element.addEventListener('transitionend', resetStyles, { once: true });

      //element.style.transform = Math.abs(start - current) > threshold ? "" : `translate${direction.toUpperCase()}(0px)`;
      element.style.transform = Math.abs(start - current) > threshold
          ? ""
          : direction === 'x'
              ? `translate3d(0px, 0, 0)`
              : `translate3d(0, 0px, 0)`;
      if (Math.abs(start - current) > threshold) closeCallback(element);
    }

    document.removeEventListener("touchmove", drag);
    document.removeEventListener("touchend", pointerUp);
    if (!touchOnly) {
      document.removeEventListener("mousemove", drag);
      document.removeEventListener("mouseup", pointerUp);
    }
    isDragging = false;
  }

  function resetStyles() {
    element.style.userSelect = "";
    element.style.transition = "";
    element.style.transform = "";
  }

  function pointerDown(e) {
    isDragging = false;
    start = getCoords(e)[direction];
    document.addEventListener(e.type === "touchstart" ? "touchmove" : "mousemove", drag, { passive: false });
    document.addEventListener(e.type === "touchstart" ? "touchend" : "mouseup", pointerUp, { passive: true });
  }

  element.addEventListener("touchstart", pointerDown);
  if (!touchOnly) element.addEventListener("mousedown", pointerDown);
}

export function init(elements){
  elements.forEach(function(element){
    element.querySelectorAll('[data-toggles]').forEach((toggler) => {
      const toggles = toggler.dataset.toggles;
      const autoHide = toggler.dataset.autohide ?? 0;
      let autoHideTimeout;
      const target = toggler.dataset.target ? (toggler.dataset.target === '_self' ? toggler : (toggler.dataset.target === 'parent' ? toggler.parentNode : document.querySelector(toggler.dataset.target))) : element;
      const hide = toggler.dataset.togglePermanent?.toLowerCase() !== "true";

      function handleClickOutside(event) {
        if (!event || (!target.contains(event.target) && !toggler.contains(event.target))) { //only handle cases where neither the toggler nor its target are clicked
          if (target.classList.contains(toggles)) {
            target.classList.remove(toggles);
            clearTimeout(autoHideTimeout);
          }
          document.removeEventListener("click", handleClickOutside);
        } else if (autoHideTimeout) {
          //restart the autohide timer, to prevent autohide while interaction
          clearTimeout(autoHideTimeout);
          autoHideTimeout = setTimeout(handleClickOutside, (autoHide * 1000));
        }
      }

      singleClick(toggler, ()=>{
        target.classList.toggle(toggles);
        clearTimeout(autoHideTimeout);
        if (hide) document.addEventListener("click", handleClickOutside);
        if (autoHide) {
          autoHideTimeout = setTimeout(handleClickOutside, (autoHide * 1000));
        }
      });

      if (toggler.dataset.swipeClose?.toLowerCase()) {
        const swipeLimits = toggler.dataset.swipeLimits?.split(',').map(Number) || [-9999, 0];
        swipeToClose(document.querySelector(toggler.dataset.swipeClose?.toLowerCase()), () => {
          if (target.classList.contains(toggles)) {
            target.classList.remove(toggles);
            clearTimeout(autoHideTimeout);
          }
        }, toggler.dataset.swipeDirection || 'y', ...swipeLimits);
      }

    })
  })
}