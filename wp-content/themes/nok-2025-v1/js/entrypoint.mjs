/*
Entrypoint - contains site-general stuff that happens on each pageload. Everything else should be handled by modules.
 */
//todo dynamicImporter fails when bundled (no import)
import eventHandler from './modules/hnl.eventhandler.mjs';
import {dynImports} from './modules/hnl.dynamicimports.mjs';
import {hnlLogger} from './modules/hnl.logger.mjs';
import {classToggler} from './modules/hnl.classtoggler.mjs';
import {pageScrollPercentage} from "./modules/hnl.helpers.mjs";

const NAME = 'entryPoint';
const BODY = document.body;

hnlLogger.info(NAME, 'Starting up...');

window.exports = "object" == typeof window.exports ? window.exports : {}; //hack for scripts loaded as modules (e.g. AOS)

eventHandler.docReady(function(){

  //toggle classes
  classToggler();

  //handle all dynamic module imports
  dynImports(function(e){
    hnlLogger.info(NAME, 'Ready.');
  });

  //https://stackoverflow.com/questions/3885018/active-pseudo-class-doesnt-work-in-mobile-safari
  document.addEventListener('touchstart', function() {},false);

  /*
  //checks if position:sticky elements are, in fact, stuck. Checks on every scroll.
  const stickies = document.body.querySelectorAll('.position-sticky');
  eventHandler.addListener('scroll', function(){
    stickies.forEach(function(el){
      el.classList.toggle('stuck', el.getBoundingClientRect().top === parseInt(window.getComputedStyle(el).top, 10));
    });
  })();
  */

  eventHandler.addListener('scroll', (e) => {
  //clear the url hash when scrolled back to top
    if (window.scrollY === 0) {
      history.pushState("", document.title, window.location.pathname + window.location.search)
    }
    //update percentage the page has been scrolled
    const scrolled = Math.round(pageScrollPercentage());
    document.documentElement.style.setProperty('--doc-scrolled', `${(scrolled)}%`);
    document.documentElement.style.setProperty('--doc-scrolled-float', `${scrolled / 100}`);
  })();
});