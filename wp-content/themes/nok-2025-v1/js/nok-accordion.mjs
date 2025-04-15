import {singleClick} from "./modules/hnl.clickhandlers.mjs";
import eventHandler from "./modules/hnl.eventhandler.mjs";

export const NAME = 'accordion';

const accClass = 'nok-accordion';

function toggleAccordion(accordion) {
  const isOpen = accordion.classList.contains('open');
  accordion.classList.toggle('open', !isOpen);
  updateAccordionHeight(accordion);
}

function updateAccordionHeight(accordion) {
  const content = accordion.querySelector(':last-child');
  content.style.height = (accordion.classList.contains('open') ? content.scrollHeight : 0) + 'px';
}

export function init(elements){
  elements.forEach(function(element){
    const isSingleInstance = element.classList.contains(accClass);
    const accordions = isSingleInstance ? [element] : element.querySelectorAll(`.${accClass}`);

    accordions.forEach(accordion => {
      const toggler = accordion.querySelector(':first-child');

      eventHandler.addListener('docShift', () => {
        updateAccordionHeight(accordion);
      });

      singleClick(toggler, ()=>{
        // Close all other accordions in the same scope
        if (!accordion.classList.contains('open')) {
          accordions.forEach(other => {
            if (other !== accordion && other.classList.contains('open')) {
              toggleAccordion(other);
            }
          });
        }
        toggleAccordion(accordion);
      });
    });
  });
}