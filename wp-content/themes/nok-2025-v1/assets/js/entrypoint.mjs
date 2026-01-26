/**
 * DOMule dependency - external library
 * Located at: assets/js/domule/
 * Repository: https://github.com/c-kick/DOMule
 *
 * Not in package.json - manually maintained
 */


import events from './domule/core.events.mjs';
import {loadModules} from './domule/core.loader.mjs';
import {logger, DEBUG} from './domule/core.log.mjs';
import {classToggler} from './domule/modules/hnl.classtoggler.mjs';
import {pageScrollPercentage} from "./domule/util.perf.mjs";
import {setupScrollbarControl, setupFakeScrollbar, shuffleChildren} from "./nok-scrollbar.mjs";
import {ViewportScroller} from './domule/util.ensure-visibility.mjs';
import AOS from './nok-aos.mjs';
import {singleClick} from "./domule/modules/hnl.clickhandlers.mjs";

const NAME = 'entryPoint';
const BODY = document.body;

logger.info(NAME, 'Starting up...');

window.exports = "object" == typeof window.exports ? window.exports : {}; //hack for scripts loaded as modules (e.g. AOS)

events.docLoaded(function () {
    //enable transitions once everything's done, to prevent weird animation FOUCs
    document.body.classList.add('__enable-transitions');

    events.addListener('scroll', (e) => {
        //clear the url hash when scrolled back to top
        if (window.scrollY === 0) {
            if (DEBUG) logger.log(NAME, 'Hash cleared');
            history.replaceState(/* state */   {}, /* title */   document.title, /* url */     window.location.pathname + window.location.search);
        }
        //update percentage the page has been scrolled
        const scrolled = Math.round(pageScrollPercentage());
        document.documentElement.style.setProperty('--doc-scrolled', `${(scrolled)}%`);
        document.documentElement.style.setProperty('--doc-scrolled-float', `${scrolled / 100}`);
        document.documentElement.style.setProperty('--scrollbar-width', `${Math.max(0, (window.innerWidth - document.documentElement.clientWidth))}px`);
    })();
})


events.docReady(function () {

    //toggle classes
    classToggler();

    //handle all dynamic module imports
    loadModules(function (e) {
        logger.info(NAME, 'Ready.');
    });

    //https://stackoverflow.com/questions/3885018/active-pseudo-class-doesnt-work-in-mobile-safari
    document.addEventListener('touchstart', function () {
    }, false);

    // Shuffle children of elements marked for randomization
    shuffleChildren(document.querySelectorAll('[data-nok-shuffle]'));

    // sets up fake scrollbars
    document.querySelectorAll('.nok-scrollable__horizontal, .nok-scrollable__vertical').forEach(setupFakeScrollbar);
    // sets up scrollbars as controllers for scrolling
    document.querySelectorAll('[data-scroll-target]').forEach(setupScrollbarControl);

    events.addListener('breakPointChange', (e) => {
        document.querySelectorAll('foreignObject > img').forEach((img) => {
            //fixes the bug where foreignObject responsive (lazy loaded) images do not update their src
            img.style.display = 'none';
            requestAnimationFrame(() => {
                img.style.display = 'block';
            })
        })
    });


    // Handle anchor link scrolling with header offset
    // WeakMap stores scroller instances per element to ensure consistent positioning
    const scrollerMap = new WeakMap();

    const scrollToElement = (el) => {
        if (!el) return;

        let scroller = scrollerMap.get(el);
        if (!scroller) {
            scroller = new ViewportScroller(el, {
                behavior: 'smooth',
                extraOffset: 20
            });
            scrollerMap.set(el, scroller);
        }
        scroller.ensureVisible();
    };

    const scrollToHash = () => {
        const id = location.hash.slice(1);
        const el = id && document.getElementById(id);
        scrollToElement(el);
    };

    // Click handler - intercept anchor clicks for controlled scrolling
    singleClick(document.querySelectorAll('a[href*="#"]'), (e) => {
        const link = e.target;
        if (!link) return;

        const url = new URL(link.href, location.href);

        // Handle href="#" (no target) - just prevent scroll to top
        if (url.hash === '#' || !url.hash) {
            console.log('Should not scroll');
            e.preventDefault();
            return;
        }

        // Only handle same-page anchors - let browser handle cross-page links
        if (url.pathname !== location.pathname) return;

        const el = document.getElementById(url.hash.slice(1));
        if (!el) return;

        e.preventDefault();
        history.pushState(null, '', url.hash);
        scrollToElement(el);
    });

    // Back/forward navigation
    window.addEventListener('popstate', scrollToHash);

    // Initial page load with hash
    if (location.hash) scrollToHash();

    singleClick(document.querySelectorAll('.scroll-to-top'), () => {
        window.scrollTo({
            top: 0, behavior: 'smooth'
        });
    });

    const aos = AOS.init({
        selector: 'nok-section:not(.no-aos),.nok-aos', duration: 600, threshold: 0.35, once: true
    });

});

