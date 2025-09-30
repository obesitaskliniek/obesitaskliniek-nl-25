/*
Entrypoint - contains site-general stuff that happens on each pageload. Everything else should be handled by modules.
 */
//todo dynamicImporter fails when bundled (no import)
import eventHandler from './modules/hnl.eventhandler.mjs';
import {dynImports} from './modules/hnl.dynamicimports.mjs';
import {hnlLogger} from './modules/hnl.logger.mjs';
import {classToggler} from './modules/hnl.classtoggler.mjs';
import {isVisible, pageScrollPercentage, watchVisibility} from "./modules/hnl.helpers.mjs";
import {setupScrollbarControl, setupFakeScrollbar} from "./nok-scrollbar.mjs";
import AOS from './nok-aos.mjs';

const NAME = 'entryPoint';
const BODY = document.body;

hnlLogger.info(NAME, 'Starting up...');

window.exports = "object" == typeof window.exports ? window.exports : {}; //hack for scripts loaded as modules (e.g. AOS)

eventHandler.docLoaded(function(){
  //enable transitions once everything's done, to prevent weird animation FOUCs
  document.body.classList.add('__enable-transitions');
})


eventHandler.docReady(function(){

  //toggle classes
  classToggler();

  //handle all dynamic module imports
  dynImports(function(e){
    hnlLogger.info(NAME, 'Ready.');
  });

  //https://stackoverflow.com/questions/3885018/active-pseudo-class-doesnt-work-in-mobile-safari
  document.addEventListener('touchstart', function() {},false);

  // sets up fake scrollbars
  document.querySelectorAll('.nok-scrollable__horizontal, .nok-scrollable__vertical').forEach(setupFakeScrollbar);
  // sets up scrollbars as controllers for scrolling
  document.querySelectorAll('[data-scroll-target]').forEach(setupScrollbarControl);

  eventHandler.addListener('breakPointChange', (e) => {
    document.querySelectorAll('foreignObject > img').forEach((img) => {
      //fixes the bug where foreignObject responsive (lazy loaded) images do not update their src
      img.style.display = 'none';
      requestAnimationFrame(() => {
        img.style.display = 'block';
      })
    })
  });

  //universally stop href="#" links from scrolling to top
  document.addEventListener("click", function(event) {
    const link = event.target.closest("a[href='#']");
    if (link) {
      event.preventDefault(); // Prevents page from jumping to the top
    }
  });

  eventHandler.addListener('scroll', (e) => {
  //clear the url hash when scrolled back to top
    if (window.scrollY === 0) {
      history.replaceState(
          /* state */   {},
          /* title */   document.title,
          /* url */     window.location.pathname + window.location.search
      );
    }
    //update percentage the page has been scrolled
    const scrolled = Math.round(pageScrollPercentage());
    document.documentElement.style.setProperty('--doc-scrolled', `${(scrolled)}%`);
    document.documentElement.style.setProperty('--doc-scrolled-float', `${scrolled / 100}`);
    document.documentElement.style.setProperty('--scrollbar-width', `${Math.max(0, (window.innerWidth - document.documentElement.clientWidth))}px`);
  })();


  const aos = AOS.init({
    selector: 'nok-section',
    duration: 600,
    threshold: 0.35,
    once: true
  });

});

