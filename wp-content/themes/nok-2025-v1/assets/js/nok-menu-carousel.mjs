/*
Embedded scroll menu handler (c) 2025 Klaas Leussink / hnldesign
 */
import {singleClick} from "./modules/hnl.clickhandlers.mjs";

export const NAME = 'menuCarousel';

function scrollHandler(e) {
  const atSnappingPoint = ((e.target.scrollWidth > e.target.clientWidth) ? e.target.scrollLeft % e.target.offsetWidth : e.target.scrollTop % e.target.offsetHeight) === 0;
  const timeOut= atSnappingPoint ? 0 : 150;
  e.target.__busy = true;
  e.target.classList.add('is-scrolling');

  clearTimeout(e.target.scrollTimeout); //clear previous timeout

  e.target.scrollTimeout = setTimeout(function() {
    e.target.__busy = false;
    e.target.classList.remove('is-scrolling');
  }, timeOut);
}

export function init(elements){
  elements.forEach(element => {

    element.querySelectorAll('.nok-nav-carousel__slide').forEach(slide => {
      slide.querySelectorAll('a.nok-nav-menu-item').forEach(link => {
        const targetElement = document.getElementById(link.getAttribute("href").slice(1));

        if (targetElement) {
          const closestSlide = targetElement.closest('.nok-nav-carousel__slide');
          singleClick(link, (e) => {
            e.preventDefault();
            if (!element.__busy) {
              //First, we make sure the slide we land on is scrolled correctly. We're not using scrollIntoView for this,
              //as this would cause double scrolls and confuse most browsers' implementation of scroll-behavior
              closestSlide.scrollTo({
                top: targetElement.offsetTop - closestSlide.offsetTop,
                behavior: "instant",
              });
              //Then, when this has been done, we can smoothly slide over to it
              window.requestAnimationFrame(function () {
                closestSlide.scrollIntoView({behavior: "smooth"});
              })
            }
          })
        }
      })

    });

    //Make sure busy handling works
    element.addEventListener('scroll', scrollHandler);
  });
}