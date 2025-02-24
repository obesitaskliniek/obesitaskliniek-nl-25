/*
Universal single click toggler (c)2025 Klaas Leussink / hnldesign
 */
import {singleClick} from "./modules/hnl.clickhandlers.mjs";

export const NAME = 'simpleToggler';

export function init(elements){
  elements.forEach(function(element){
    element.querySelectorAll('[data-toggles]').forEach((toggler) => {
      const toggles = toggler.dataset.toggles;
      singleClick(toggler, ()=>{
        element.classList.toggle(toggles);
      });
    })
  })
}