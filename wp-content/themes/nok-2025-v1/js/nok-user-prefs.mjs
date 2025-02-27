import {singleClick} from "./modules/hnl.clickhandlers.mjs";
import eventHandler from "./modules/hnl.eventhandler.mjs";

export const NAME = 'userPrefs';

const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

function resetFontSize() {
  document.documentElement.style.fontSize = null;
  sessionStorage.removeItem('fontSize');
}
function changeFontSize(delta) {
  const minMax = [6,24];
  const currentSize = parseInt(window.getComputedStyle(document.documentElement).fontSize, 10);
  const newSize =`${clamp(currentSize + delta, ...minMax)}px`;
  sessionStorage.setItem('fontSize', newSize);
  document.documentElement.style.fontSize = newSize;
}

//autorun restore
eventHandler.docReady(function(){
  document.documentElement.style.fontSize = sessionStorage.getItem('fontSize');
});

export function init(elements){
  elements.forEach(function(element){
    element.querySelectorAll('[data-set-font-size]').forEach(function(button){
      singleClick(button, ()=>{
        changeFontSize(parseInt(button.dataset.setFontSize, 10));
      })
    });
    element.querySelectorAll('[data-reset-font-size]').forEach(function(button){
      singleClick(button, resetFontSize)
    });
  })
}