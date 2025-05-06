import {singleClick} from "./modules/hnl.clickhandlers.mjs";
export const NAME = 'accordion';

const accClass = 'nok-accordion';
const accordionContentMap = new WeakMap();

function toggleAccordion(accordion, content, shouldOpen) {
  accordion.classList.toggle('open', shouldOpen);
  content.style.height = shouldOpen ? content.scrollHeight + 'px' : '0';
}

export function init(elements){
  elements.forEach(element => {
    const isSingleInstance = element.classList.contains(accClass);
    /** @type {NodeListOf<HTMLElement>} */
    const accordions = isSingleInstance ? [element] : element.querySelectorAll(`.${accClass}`);

    accordions.forEach(currentAccordion => {
      const toggler = currentAccordion.querySelector(':first-child');
      const content = currentAccordion.querySelector(':last-child');
      accordionContentMap.set(currentAccordion, { toggler, content });

      content.addEventListener('transitionend', e => {
        if (e.propertyName === 'height' && currentAccordion.classList.contains('open')) {
          content.style.height = '';
        }
      });

      singleClick(toggler, () => {
        const isCurrentlyOpen = currentAccordion.classList.contains('open');

        if (!isCurrentlyOpen) {
          accordions.forEach(other => {
            if (other !== currentAccordion && other.classList.contains('open')) {
              const { content: otherContent } = accordionContentMap.get(other);
              toggleAccordion(other, otherContent, false);
            }
          });
        }

        toggleAccordion(currentAccordion, content, !isCurrentlyOpen);
      });
    });
  });
}