import { singleClick } from "./modules/hnl.clickhandlers.mjs";
import eventHandler from "./modules/hnl.eventhandler.mjs";

export const NAME = "userPrefs";

const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
const MIN_FONT_SIZE = 0.8;
const MAX_FONT_SIZE = 1.6;
let normalFontSize = 1; // Default to 1em

function resetFontSize() {
  sessionStorage.removeItem("fontSize");
  applyFontSize(normalFontSize);
}

function changeFontSize(delta) {
  const rootStyles = getComputedStyle(document.documentElement);
  const currentSize = parseFloat(rootStyles.getPropertyValue("--font-size-base")) || normalFontSize;
  const newSize = clamp(currentSize + delta, MIN_FONT_SIZE, MAX_FONT_SIZE);

  if (newSize === normalFontSize) {
    sessionStorage.removeItem("fontSize");
  } else {
    sessionStorage.setItem("fontSize", newSize.toString());
  }

  applyFontSize(newSize);
}

function applyFontSize(fontSize) {
  document.documentElement.style.setProperty("--font-size-base", `${fontSize}em`);

  document.body.classList.toggle("larger-font", fontSize > normalFontSize);
  document.body.classList.toggle("smaller-font", fontSize < normalFontSize);
}

// Restore user preferences on page load
eventHandler.docReady(() => {
  const rootStyles = getComputedStyle(document.documentElement);
  normalFontSize = parseFloat(rootStyles.getPropertyValue("--font-size-base")) || 1;
  const storedFontSize = sessionStorage.getItem("fontSize");
  applyFontSize(storedFontSize ? parseFloat(storedFontSize) : normalFontSize);
});

export function init(elements) {
  elements.forEach(element => {
    element.querySelectorAll("[data-set-font-size]").forEach(button => {
      singleClick(button, () => changeFontSize(parseFloat(button.dataset.setFontSize)));
    });

    element.querySelectorAll("[data-reset-font-size]").forEach(button => {
      singleClick(button, resetFontSize);
    });
  });
}
