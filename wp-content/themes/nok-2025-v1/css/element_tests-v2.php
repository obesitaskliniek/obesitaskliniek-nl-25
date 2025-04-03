<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
const NOK_THEME_ROOT = 'https://dev.obesitaskliniek.nl/wp-content/themes/nok-2025-v1'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

    <title>Element tests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap"
          rel="stylesheet">
    <link href="../fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="./tests.css?cache=<?= time(); ?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./color_tests-v2.css?cache=<?= time(); ?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./element_tests-v2.css?cache=<?= time(); ?>" crossorigin="anonymous">

    <!-- <link rel="modulepreload" href="../js/mobileConsole/hnl.mobileconsole.js?cache=<?= time(); ?>">
    <script src="../js/mobileConsole/hnl.mobileconsole.js?cache=<?= time(); ?>"></script><!--//-->
    <link rel="modulepreload" href="../js/entrypoint.min.mjs?cache=<?= time(); ?>">
    <script type="module" src="../js/entrypoint.min.mjs?cache=<?= time(); ?>" defer></script>

    <!-- Load the module with explicit defer
    <link rel="modulepreload" href="../js/nok-element-library.min.mjs?cache=<?= time(); ?>">
    <script type="module" src="../js/nok-element-library.min.mjs?cache=<?= time(); ?>" defer></script>

    <style>
        :not(:defined) {
            /*visibility: hidden;*/
        }
    </style>-->
</head>
<body class="no-js nok25-bg-body nok25-text-contrast">
<div class="nok25-accessibility-helper"></div>

<?php $star = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
  <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
</svg>';
$logo = '<nok25-logo><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 463.58 173.6">
<title id="svg-title">Nederlandse Obesitas Kliniek</title>
  <desc id="svg-desc">Nederlandse Obesitas Kliniek logo</desc>
<path d="M120.05,24.86a11.25,11.25,0,0,0,4.2-9A11.35,11.35,0,0,0,120,6.7c-3-2.43-6.18-2.91-10-2.91h-4.85v24.1h4.78c4,0,7.11-.48,10.18-3M108,6.28H110c3,0,5.64.38,8,2.33a9.42,9.42,0,0,1,.14,14.3c-2.36,2.07-5,2.49-8.13,2.49H108Z" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<polygon points="17.83 6.68 37.91 26.34 37.91 1.16 35.05 1.16 35.05 19.82 14.95 0.13 14.95 25.25 17.83 25.25 17.83 6.68" fill="currentColor"/>
<polygon points="63.78 25.25 77.14 25.25 77.14 22.76 66.65 22.76 66.65 13.3 76.82 13.3 76.82 10.79 66.65 10.79 66.65 3.64 77.14 3.64 77.14 1.15 63.78 1.15 63.78 25.25" fill="currentColor"/>
<polygon points="159.54 22.76 149.05 22.76 149.05 13.3 159.24 13.3 159.24 10.79 149.05 10.79 149.05 3.64 159.54 3.64 159.54 1.15 146.18 1.15 146.18 25.25 159.54 25.25 159.54 22.76" fill="currentColor"/>
<path d="M190.37,17.31h.72l7.78,10.58h3.52l-8.19-10.8c4-.32,6.45-2.94,6.45-6.65,0-5.44-4.55-6.65-9.43-6.65H187.5v24.1h2.87Zm0-11h.85c3.45,0,6.56.38,6.56,4.35,0,3.74-3.28,4.32-6.53,4.32h-.88Z" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<polygon points="233.92 22.76 226.81 22.76 226.81 1.15 223.95 1.15 223.95 25.25 233.92 25.25 233.92 22.76" fill="currentColor"/>
<path d="M269.32,2.64,257.43,27.89h3.14l3.11-6.84h11l3,6.84h3.17Zm-4.48,15.92,4.41-9.66,4.3,9.66Z" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<polygon points="304.67 6.68 324.76 26.34 324.76 1.16 321.89 1.16 321.89 19.82 301.8 0.13 301.8 25.25 304.67 25.25 304.67 6.68" fill="currentColor"/>
<path d="M368.16,24.86a11.31,11.31,0,0,0,4.2-9,11.33,11.33,0,0,0-4.27-9.17c-3-2.43-6.18-2.91-10-2.91h-4.86v24.1H358c4,0,7.1-.48,10.18-3M356.07,6.28h2.07c3,0,5.65.38,8,2.33a9.43,9.43,0,0,1,.14,14.3c-2.36,2.07-5,2.49-8.14,2.49h-2.07Z" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<path d="M403.59,25.82a5.18,5.18,0,0,1-5.17-4.48l-2.8.74a7.85,7.85,0,0,0,8,6.22c4.48,0,8.1-3.22,8.1-7.44,0-3.83-2.84-5.4-6.18-6.77l-1.71-.71c-1.75-.74-4-1.7-4-3.77s2-3.74,4.28-3.74a4.76,4.76,0,0,1,4.44,2.62l2.29-1.38a7.49,7.49,0,0,0-6.67-3.74c-3.83,0-7.2,2.4-7.2,6.14,0,3.46,2.59,4.9,5.6,6.17l1.57.64c2.4,1,4.74,2,4.74,4.79s-2.52,4.71-5.28,4.71" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<polygon points="447.24 22.76 436.75 22.76 436.75 13.3 446.92 13.3 446.92 10.79 436.75 10.79 436.75 3.64 447.24 3.64 447.24 1.15 433.87 1.15 433.87 25.25 447.24 25.25 447.24 22.76" fill="currentColor"/>
<polygon points="32.53 149.45 28.84 149.45 18.36 159.35 18.36 149.45 15.61 149.45 15.61 172.57 18.36 172.57 18.36 162.61 19.05 161.96 29.14 172.57 32.92 172.57 20.98 160.21 32.53 149.45" fill="currentColor"/>
<polygon points="92.18 149.45 89.44 149.45 89.44 172.57 98.97 172.57 98.97 170.17 92.18 170.17 92.18 149.45" fill="currentColor"/>
<rect x="155.62" y="149.44" width="2.73" height="23.12" fill="currentColor"/>
<polygon points="236.58 167.34 217.38 148.46 217.38 172.57 220.12 172.57 220.12 154.75 239.33 173.6 239.33 149.45 236.58 149.45 236.58 167.34" fill="currentColor"/>
<rect x="298.39" y="149.44" width="2.74" height="23.12" fill="currentColor"/>
<polygon points="359.99 172.57 372.75 172.57 372.75 170.17 362.73 170.17 362.73 161.1 372.45 161.1 372.45 158.71 362.73 158.71 362.73 151.83 372.75 151.83 372.75 149.45 359.99 149.45 359.99 172.57" fill="currentColor"/>
<polygon points="448.19 149.45 444.51 149.45 434.03 159.35 434.03 149.45 431.29 149.45 431.29 172.57 434.03 172.57 434.03 162.61 434.71 161.96 444.79 172.57 448.58 172.57 436.64 160.21 448.19 149.45" fill="currentColor"/>
<path d="M39.83,38.53c-24.62,0-37.26,26.14-37.26,51s12.64,51,37.26,51,37.27-26.13,37.27-51-12.66-51-37.27-51m0,73.32c-9.63,0-15.29-10.94-15.29-22.31S30.2,67.23,39.83,67.23,55.11,78.17,55.11,89.54s-5.67,22.31-15.28,22.31" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<path d="M131.82,86c6.91-4.07,9.7-12.47,9.7-19.79,0-20.32-12.63-27.76-33.2-27.76H81.43V140.58h33.93c20.13,0,35.42-9,35.42-29,0-11.24-5.89-23.44-19-25.6M103,54.41h2.8c9.11,0,15.56,2.71,15.56,13.14S116.24,81,105.67,81H103Zm4.55,70.18H103V96.13h3.52c10.88,0,22.63.55,22.63,13.82s-10,14.64-21.6,14.64" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<polygon points="172.38 94.28 210.09 94.28 210.09 74.57 172.38 74.57 172.38 55.5 212.92 55.5 212.92 35.78 154.32 35.78 154.32 137.92 214.28 137.92 214.28 118.2 172.38 118.2 172.38 94.28" fill="currentColor"/>
<path d="M253.11,82.87C243.94,79.52,236,76.63,236,65.1c0-9.19,4.34-14,12.55-14s11.73,4.79,11.73,15.52v1.48h13.42l0-1.5c-.25-18.64-8.74-28.09-25.24-28.09-24.43,0-26.33,21.31-26.33,27.84,0,19.69,12.19,24.29,22.93,28.35,9.2,3.47,17.13,6.46,17.13,18.07,0,9.06-5.46,15.39-13.27,15.39-13.38,0-14.45-9-14.45-20.73V105.9H221.05v1.48c0,16.48,3.23,33.32,27.16,33.32,6.54,0,27.85-2.16,27.85-29.85,0-19.63-12.19-24.07-22.95-28" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<rect x="277.8" y="35.79" width="13.12" height="102.15" fill="currentColor"/>
<polygon points="354.85 35.88 296.71 35.88 296.71 47.99 320.89 47.99 320.89 137.97 330.65 137.97 330.65 47.99 354.85 47.99 354.85 35.88" fill="currentColor"/>
<path d="M375.15,38.53l-33.89,102h7.95l8.88-27.34h41.7l8.89,27.34h8.53l-32.82-102Zm-14,65.92,18-56.28,17.9,56.28Z" transform="translate(-2.57 -2.64)" fill="currentColor"/>
<path d="M442.54,85.34c-10.71-4-20-7.51-20-21,0-10.22,5.56-20.53,18-20.53,11.7,0,18.69,7.42,18.69,19.86v3.76h5.36v-3.5c0-11.76-6.23-25.5-23.8-25.5-18.65,0-23.57,16.59-23.57,25.38,0,16.92,9.75,21.75,23.53,26.74,9.85,3.59,20,7.29,20,21,0,17.32-10.77,23.48-20.84,23.48-12.24,0-19.54-8.52-19.54-22.8v-5.65h-5.36v5.65c0,13,6.56,28.18,25,28.18,18,0,26.08-14.15,26.08-28.18,0-18-12.54-22.77-23.61-26.93" transform="translate(-2.57 -2.64)" fill="currentColor"/>
</svg></nok25-logo>';  ?>

<nav class="nok25-section nok25-nav
nok25-text-darkerblue"
     data-requires="./nok-toggler.mjs?cache=<?= time(); ?>">
    <div class="nok25-nav-mask nok25-bg-darkerblue nok25-dark-bg-darkerblue--darker nok25-z-1"
         data-toggles="open"></div>
    <div class="nok25-bg-body nok25-text-darkerblue nok25-dark-text-contrast nok25-nav-control-dropdown nok25-bg-blur nok25-bg-alpha-9"
         data-requires="./nok-user-prefs.mjs?cache=<?= time(); ?>">
        <h5>Toegankelijkheid</h5>
        <div class="nok25-nav-control-dropdown__section">
            Tekstgrootte:
            <button class="nok25-button nok25-button--small nok25-bg-darkerblue nok25-text-contrast font-decrease"
                    style="font-variant: all-small-caps;" tabindex="0" data-set-font-size="-0.1">A
            </button>
            <button class="nok25-button nok25-button--small nok25-bg-darkerblue nok25-text-contrast font-increase"
                    tabindex="0" data-set-font-size="+0.1">A
            </button>
            <button class="nok25-button nok25-button--small nok25-bg-darkblue nok25-dark-bg-lightblue--darker nok25-text-contrast"
                    tabindex="0" data-reset-font-size="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                </svg>
            </button>
        </div>
    </div>

    <nok25-mobile-nav>
        <div class="nok25-section__inner--stretched nok25-bg-white nok25-dark-bg-darkestblue nok25-text-contrast nok25-fill-contrast nok25-nav-top-row nok25-z-3">
            <?= $logo; ?>
            <button class="nok25-button nok25-button--small nok25-base-font nok25-bg-yellow nok25-text-contrast nok25-invisible-to-sm"
                    tabindex="0">Gratis voorlichtingsavond
            </button>
            <button class="nok25-button nok25-button-menu nok25-dark-bg-darkerblue nok25-text-contrast no-shadow"
                    tabindex="0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z'/>
                </svg>
            </button>
            <button class="nok25-button nok25-button-menu nok25-dark-bg-darkerblue nok25-text-contrast no-shadow"
                    tabindex="0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/>
                </svg>
            </button>
            <button class="nok25-button nok25-button-menu nok25-dark-bg-darkerblue nok25-text-contrast no-shadow"
                    tabindex="0"
                    data-toggles="open" data-target=".nok25-nav-control-dropdown"
                    data-swipe-close=".nok25-nav-control-dropdown" data-autohide="10">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M11.7 3.7H9.9l-3.3 8.6-2.3-5.7H3.1L.8 12.3H2l.5-1.3h2.3l.5 1.3h3.1l.7-2h3.4l.8 2h1.9l-3.4-8.6ZM2.9 10l.8-2.1.8 2.1H2.9Zm6.7-1.1 1.2-3.2L12 8.9H9.7Z'/>
                </svg>
            </button>
            <button class="nok25-button nok25-button-menu nok25-nav-menu-toggler nok25-dark-bg-darkerblue nok25-text-contrast no-shadow nok25-fill-yellow"
                    tabindex="0"
                    data-toggles="open">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path class="open" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="1.5" d="M2.7 3.9h11m-11 4h11m-11 4h11"/>
                    <path class="closed" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="0.8"
                          d="M2.4 2.8c-.2-.2-.2-.5 0-.7.2-.2.5-.2.7 0l5.1 5.1 5.1-5.1c.2-.2.5-.2.7 0 .2.2.2.5 0 .7L8.9 7.9 14 13c.2.2.2.5 0 .7s-.5.2-.7 0L8.2 8.6l-5.1 5.1c-.2.2-.5.2-.7 0-.2-.2-.2-.5 0-.7l5.1-5.1-5.1-5.1Z"/>
                </svg>
                <!--data-swipe-close=".nok25-nav-drawer" data-swipe-direction="x" data-swipe-limits="0,9999"-->
            </button>
        </div>
        <div class="nok25-z-2 nok25-nav-drawer">
            <div class="nok25-section__inner--stretched nok25-nav-carousel nok25-bg-white--darker nok25-dark-bg-darkerblue nok25-dark-text-white nok25-z-2"
                 data-scroll-snapping="true" data-requires="./nok-menu-carousel.mjs">
                <div class="nok25-nav-carousel__inner nok25-text-darkerblue nok25-dark-text-white">
                    <div class="nok25-nav-carousel__slide">
                        <div class="nok25-nav__menuitems" id="topmenu">
                            <a href="#submenu-behandelingen" class="nok25-nav__menuitem nok25-nav__menuitem--active">Behandelingen</a>
                            <a href="#submenu-over-nok" class="nok25-nav__menuitem">Over NOK</a>
                            <a href="#" class="nok25-nav__menuitem">Agenda</a>
                            <a href="#" class="nok25-nav__menuitem">Verwijzers</a>
                        </div>
                    </div>
                    <div class="nok25-nav-carousel__slide">
                        <div class="nok25-nav__menuitems" id="submenu-behandelingen">
                            <a href="#topmenu" class="nok25-nav__menuitem nok25-nav__menuitem__back">&laquo; Terug naar
                                overzicht</a>
                            <a href="#" class="nok25-nav__menuitem">Wat is obesitas?</a>
                            <a href="#" class="nok25-nav__menuitem nok25-nav__menuitem--active">Onze behandeling van
                                obesitas</a>
                            <a href="#" class="nok25-nav__menuitem">Ons behandelprogramma</a>
                            <a href="#" class="nok25-nav__menuitem">De operatie</a>
                            <a href="#" class="nok25-nav__menuitem">De kosten van de behandeling</a>
                        </div>
                        <div class="nok25-nav__menuitems" id="submenu-over-nok">
                            <a href="#topmenu" class="nok25-nav__menuitem nok25-nav__menuitem__back">&laquo; Terug naar
                                overzicht</a>
                            <a href="#" class="nok25-nav__menuitem">Over ons</a>
                            <a href="#" class="nok25-nav__menuitem">Team van specialisten</a>
                            <a href="#" class="nok25-nav__menuitem">Vestigingen</a>
                            <a href="#" class="nok25-nav__menuitem">Ervaringen</a>
                            <a href="#" class="nok25-nav__menuitem">Veelgestelde vragen</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nok25-section__inner--stretched nok25-nav-footer nok25-text-contrast nok25-bg-darkerblue nok25-dark-bg-darkestblue nok25-z-1">
                <div class="nok25-nav__menuitems nok25-nav__menuitems--compact">
                    <a href="#" class="nok25-nav__menuitem nok25-nav__menuitem--active">Werken bij</a>
                    <a href="#" class="nok25-nav__menuitem">Kennisbank</a>
                    <a href="#" class="nok25-nav__menuitem">Mijn NOK</a>
                    <a href="#" class="nok25-nav__menuitem">NOK App</a>
                    <button class="nok25-button nok25-button--small nok25-base-font nok25-bg-yellow nok25-mt-1 nok25-invisible-sm"
                            tabindex="0">Gratis voorlichtingsavond
                    </button>
                </div>
            </div>
        </div>
    </nok25-mobile-nav>

    <nok25-desktop-nav>
        <div class="nok25-section__inner--stretched nok25-bg-body nok25-dark-bg-darkestblue nok25-dark-text-white nok25-nav-top-row nok25-z-2">
            <div class="nok25-section__inner nok25-collapse-y nok25-nav-top">
                <div>
                    <small class="valign-center">
                        <span class="nok25-text-lightblue nok25-star-ratings"><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?></span>5/5
                        op basis van 12.030 beoordelingen
                    </small>
                </div>
                <div>Werken bij</div>
                <div>Kennisbank</div>
                <div>Mijn NOK</div>
                <div>NOK App</div>
                <div>+31 12345678</div>
                <div>Zoek</div>
                <div>NL</div>
                <a href="#" data-toggles="open" data-target=".nok25-nav-control-dropdown" data-autohide="10">
                    <span style="font-variant: all-small-caps;">a</span>A
                </a>
            </div>
        </div>
        <div class="nok25-section__inner nok25-collapse-y nok25-nav-menubar-row nok25-z-3">
            <div class="nok25-nav-menubar nok25-bg-white nok25-dark-bg-darkerblue nok25-dark-text-contrast"
                 data-toggles="open">
                <div>
                    <?= $logo; ?>
                </div>
                <div>Behandelingen</div>
                <div>Over NOK</div>
                <div>Agenda</div>
                <div>Verwijzers</div>
                <div>
                    <button class="nok25-button nok25-base-font nok25-bg-yellow" tabindex="0">Gratis
                        voorlichtingsavond
                    </button>
                </div>
            </div>
            <div class="nok25-nav-dropdown">
                <div class="nok25-nav-dropdown-contents nok25-bg-white nok25-dark-bg-darkerblue--darker nok25-dark-text-contrast">
                    <div class="nok25-nav-dropdown-contents-menu">
                        <h3>Behandeling</h3>
                        <div>Wat is obesitas?</div>
                        <div>Onze behandeling van obesitas</div>
                        <div>Ons behandelprogramma</div>
                        <div>De operatie</div>
                        <div>De kosten van de behandeling</div>
                    </div>
                    <nok25-square-block class="nok25-bg-darkerblue">
                        <h3 class="nok25-square-block__heading">
                            Vragen of behoefte aan persoonlijk advies?
                        </h3>
                        <button class="nok25-button nok25-base-font nok25-bg-darkblue nok25-text-contrast" tabindex="0">
                            Neem contact op
                            <svg class="nok25-text-yellow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25"
                                 width="25" height="25" fill="currentColor">
                                <path d="m17.5 5.999-.707.707 5.293 5.293H1v1h21.086l-5.294 5.295.707.707L24 12.499l-6.5-6.5z"
                                      data-name="Right"/>
                            </svg>
                        </button>
                    </nok25-square-block>
                </div>
            </div>
        </div>
    </nok25-desktop-nav>
</nav>

<section class="nok25-section">
    <div class="nok25-section__inner nok25-collapse-y nok25-hero
    nok25-bg-white nok25-dark-bg-darkestblue nok25-text-darkerblue nok25-dark-text-white nok25-bg-alpha-6 nok25-dark-bg-alpha-10
    nok25-pb-to-lg-grid-gap nok25-border-radius-to-sm-0">

        <article>
            <h2 class="nok25-text-lightblue nok25-dark-text-yellow nok25-hero__pre-heading nok25-fs-3">
                #1 Obesitas Kliniek van Nederland
            </h2>
            <h1 class="nok25-section__heading nok25-hero__heading nok25-fs-6">
                Verander je leven, verander je gewicht
            </h1>
            <p class="nok25-usp-block__description">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos
                vero.
            </p>
            <div class="nok25-button-group">
                <button class="nok25-button nok25-align-self-to-sm-stretch fill-group-column nok25-bg-darkerblue nok25-text-contrast" tabindex="0">De
                    behandeling
                </button>
                <a class="nok25-hyperlink nok25-align-self-to-sm-stretch fw-bold" href="#">Kom ik in aanmerking?</a>
            </div>
        </article>
        <figure>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                 viewBox="0 0 1199.9 1065.5">
                <defs>
                    <linearGradient id="c" x1="899.81" x2="1920.52" y1="1367.93" y2="-128.38"
                                    gradientTransform="rotate(-45 802.663 961.106)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset=".5" stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset="1" stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                    </linearGradient>
                    <linearGradient id="b" x1="881.9" x2="1940.53" y1="1391.88" y2="-160.02"
                                    gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset=".5" stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset="1" stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                    </linearGradient>
                    <linearGradient id="a" x1="865.07" x2="1963.13" y1="1419.45" y2="-190.25"
                                    gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset=".5" stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        <stop offset="1" stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                    </linearGradient>

                    <filter id="luminosity-noclip" x="-234.9" y="-148" width="1685.7" height="1213.5"
                            color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse">
                        <feFlood flood-color="#fff" result="bg"/>
                        <feBlend in="SourceGraphic" in2="bg"/>
                    </filter>
                    <mask id="image-mask" x="-234.9" y="-148" width="1685.7" height="1213.5" maskUnits="userSpaceOnUse">
                        <g style="filter: url(#luminosity-noclip)">
                            <path id="mask-path"
                                  d="M418-143.6c2.7-1.6,5.5-3,8.2-4.4-2.8,1.4-5.6,2.8-8.3,4.2l-.5-1-652.3,2.5v1207.8h1685.7v-65.4c-283.3-.3-530.7-.6-529.9-.9-.8.2-1.7.5-2.4.7h0c-1.6.4-3.2.9-4.8,1.3-3.6,1-7.2,2-10.8,2.8-210.2,51.2-429-87.2-581.7-408.1C154.1,245.1,205.1-25.5,418-143.6Z"/>
                        </g>
                    </mask>
                </defs>
                <foreignObject width="100%" height="100%" mask="url(#image-mask)">
                    <?php $testimg = 'https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%20' . str_pad(rand(1, 59), 2, 0, STR_PAD_LEFT); ?>
                    <img src="<?= $testimg; ?>:400x0-25-0-0-center-0.jpg" width="1920" height="1281"
                         srcset="<?= $testimg; ?>:1920x0-65-0-0-center-0.jpg 1920w,
                                 <?= $testimg; ?>:768x0-65-0-0-center-0.jpg 768w,
                                 <?= $testimg; ?>:320x0-65-0-0-center-0.jpg 320w,
                                 <?= $testimg; ?>:150x0-65-0-0-center-0.jpg 150w" sizes="(max-width: 575px) 100vw,
                                     (min-width: 575px) 75vw,
                                     (min-width: 768px) 84vw,
                                     (min-width: 996px) 84vw,
                                     (min-width: 1200px) 84vw" loading="lazy">
                </foreignObject>
                <path id="d" fill="oklch(from var(--base-layer) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"
                      d="M137.3,682.5C-22.9,346.2,75,36.7,415.1-141.8,30.2,58.5-79.7,385.6,75.6,711.9c151.5,318,459.6,442.1,846.1,287.2-.8.2-1.7.5-2.4.7-340.4,131.3-626.9,8.6-782.1-317.3h0Z"/>
                <path id="c" fill="url(#c)"
                      d="M137.3,682.5c155.2,325.9,441.8,448.6,782.1,317.3-1.6.4-3.2.9-4.8,1.3-293.6,106-557.6-15.9-715.7-347.9C33.9,306.8,120,14.6,415.7-142.1c-.2.1-.4.2-.6.3C75,36.7-22.9,346.2,137.3,682.5Z"/>
                <path id="b" fill="url(#b)"
                      d="M260.6,623.8C90.7,267.3,165-7.5,416.1-142.3c-.2,0-.3.2-.5.2C120,14.6,33.9,306.8,198.9,653.2c158.1,332,422.1,454,715.7,347.9-3.6,1-7.2,2-10.8,2.8-245.8,76.4-484.1-46.2-643.3-380.1h0Z"/>
                <path id="a" fill="url(#a)"
                      d="M260.6,623.8c159.1,334.1,397.5,456.5,643.3,380.1-210.2,51.2-429-87.2-581.7-408.1C152.9,240.6,207.4-32.3,427.1-148c-3.6,1.9-7.3,3.7-11,5.6C165-7.5,90.7,267.3,260.6,623.8Z"/>
            </svg>

        </figure>

        <section class="nok25-bg-body--lighter nok25-dark-bg-darkerblue nok25-bg-blur--large nok25-bg-alpha-6">
            <div class="nok25-fs-buttons nok25-usp nok25-invisible-to-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="nok25-text-lightblue" viewBox="0 0 16 16">
                    <path d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936q-.002-.165.008-.329h4.078v-.927H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.6 6.6 0 0 0 10.341 2c-2.928 0-4.82 1.569-5.244 4.3H4v.928h1.01v1.265H4v.928z"/>
                </svg>
                Vergoed door je zorgverzekering
            </div>
            <div class="nok25-fs-buttons nok25-usp nok25-invisible-to-xl">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                     class="nok25-text-lightblue">
                    <path d="M3.5 5.1c.7-.3 1.1-.9 1.1-1.7s-.8-1.9-2-1.9-1.9.5-2.2.9l.6 1c.4-.4.8-.6 1.3-.6s.9.3.9.7c0 .8-.8.9-1.4.9h-.2v1.2h.3c1 0 1.6.3 1.6 1s-.4.9-1.1.9-1.1-.4-1.3-.6L.5 8c.2.2.8.8 2.1.8s1.4-.2 1.8-.6c.4-.4.7-.9.7-1.5 0-.9-.4-1.6-1.3-1.8Zm6.4-2.7c-.6-.7-1.4-.9-2-.9s-1.3.1-2 .9C5.3 3.1 5 4 5 5.3s.3 2.2.9 2.9c.6.7 1.4.9 2 .9s1.3-.1 2-.9c.6-.7.9-1.6.9-2.9s-.3-2.2-.9-2.9Zm-.6 2.8c0 1.5-.5 2.4-1.4 2.4s-1.4-.8-1.4-2.4.5-2.4 1.4-2.4 1.4.8 1.4 2.4Zm6.6-.2h-1.7V3.3h-1.1V5h-1.7v1.1h1.7v1.8h1.1V6.1h1.7V5Z"/>
                    <rect width=".7" height=".6" x="5.1" y="11" rx="0" ry="0"/>
                    <path d="M5.7 11.8h-.6V14c0 .3 0 .3-.2.3h-.1v.5h.3c.5 0 .8-.3.8-.8v-2.2Zm1.2-.1c-.4 0-.7.1-.9.4l.2.4c0-.1.3-.2.5-.2s.4.1.4.4h-.3c-.6 0-.9.3-.9.7s.2.7.7.7.5-.1.6-.2v.2h.5v-1.2c0-.6-.3-1-.9-1Zm.3 1.4v.2s-.1.1-.4.1-.2 0-.2-.2.1-.2.4-.2h.2ZM9 11.7c-.4 0-.7.1-.9.4l.2.4c0-.1.3-.2.5-.2s.4.1.4.4h-.3c-.6 0-.9.3-.9.7s.2.7.7.7.5-.1.6-.2v.2h.5v-1.2c0-.6-.3-1-.9-1Zm.3 1.4v.2s-.1.1-.4.1-.2 0-.2-.2.1-.2.4-.2h.2Zm2.2-1.4c-.4 0-.5.1-.6.3v-.2h-.5v2.1h.6v-1.4c0-.2.3-.3.5-.3v-.5Z"/>
                </svg>
                Meer dan 30 jaar ervaring
            </div>
            <div class="nok25-fs-buttons nok25-usp nok25-invisible-to-xxxl">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"
                     class="nok25-text-lightblue">
                    <path d="M8.5 5.034v1.1l.953-.55.5.867L9 7l.953.55-.5.866-.953-.55v1.1h-1v-1.1l-.953.55-.5-.866L7 7l-.953-.55.5-.866.953.55v-1.1zM13.25 9a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM13 11.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25zm.25 1.75a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zm-11-4a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5A.25.25 0 0 0 3 9.75v-.5A.25.25 0 0 0 2.75 9zm0 2a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM2 13.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25z"/>
                    <path d="M5 1a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1a1 1 0 0 1 1 1v4h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h3V3a1 1 0 0 1 1-1zm2 14h2v-3H7zm3 0h1V3H5v12h1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1zm0-14H6v1h4zm2 7v7h3V8zm-8 7V8H1v7z"/>
                </svg>
                Samenwerking met de beste ziekenhuizen
            </div>
            <button class="nok25-button nok25-base-font nok25-bg-white nok25-text-darkerblue nok25-visible-xs align-self-stretch"
                    tabindex="0">Vind een vestiging
            </button>
        </section>
    </div>
</section>

<section class="nok25-section nok25-text-contrast">
    <div class="nok25-section__inner">
        <div class="nok-layout-grid nok-layout-grid__2-column">
            <div class="nok25-order-2 nok25-order-lg-1">
                <svg class="mockup-image">
                    <line x1="0" y1="100%" x2="100%" y2="0" stroke="currentColor"></line>
                    <line x1="0" y1="0" x2="100%" y2="100%" stroke="currentColor"></line>
                </svg>
            </div>
            <div class="nok25-order-1 nok25-order-lg-2">
                text
            </div>
        </div>
    </div>
</section>

<section class="nok25-section nok25-bg-darkerblue nok25-text-contrast">
    <div class="nok25-section__inner nok25-usp-block">
        <div class="nok25-usp-block__inner nok-layout-grid nok-layout-grid__3-column">
            <div class="nok-layout-grid nok-layout-grid__3-column fill-one align-self-stretch">
                <h1 class="nok25-section__heading align-center">
                    USP Block
                </h1>
                <p class="nok25-usp-block__description align-center">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos
                    vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas, quia
                    reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                </p>
            </div>
            <div class="nok25-usp-block__scroller nok25-draggable nok25-mt-grid-gap" data-requires="./modules/hnl.draggable"
                 data-snap-items="nok25-draggable-slider-item" data-scroll-snapping="true">
                <div class="nok25-usp-block__blocks">
                    <?php $x = 6;
                    while ($x--) : ?>
                        <nok25-square-block class="nok25-draggable-slider-item">
                            <div class="nok25-square-block__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 13" fill="currentColor">
                                    <path d="M4.578.004c-.398.02-.777.094-1.133.223a3.803 3.803 0 0 0-2.058 1.746A4.127 4.127 0 0 0 .956 3.2a4.602 4.602 0 0 0-.047.976c.042.58.209 1.145.488 1.656.059.106.153.16.27.16.137 0 .25-.09.285-.226a.318.318 0 0 0 0-.149q-.008-.031-.082-.176a3.474 3.474 0 0 1-.367-1.77c.023-.335.082-.62.187-.913.36-1.02 1.2-1.79 2.239-2.051.187-.047.359-.078.562-.094.11-.008.41-.008.52 0a3.361 3.361 0 0 1 2.034.895c.024.023.305.3.626.625.418.422.586.586.601.597.054.034.117.05.18.047.07 0 .117-.011.172-.05.016-.008.226-.22.617-.614.328-.328.617-.617.645-.64.272-.248.583-.448.921-.594.352-.149.7-.234 1.098-.27.078-.004.395-.004.477 0a3.249 3.249 0 0 1 3.011 3.227c.014.864-.3 1.702-.879 2.344-.05.058-.449.46-1.761 1.777-3.102 3.113-4.301 4.316-4.309 4.316 0 0-.07-.066-.152-.148-.086-.086-.3-.3-.48-.477-.829-.828-1.876-1.87-2.899-2.902-.328-.324-.461-.457-.48-.473a.275.275 0 0 0-.098-.043.361.361 0 0 0-.145.004.298.298 0 0 0-.215.22.452.452 0 0 0 0 .144c.012.04.03.079.055.113.04.047 3.996 4.004 4.192 4.191.045.049.105.08.171.09.04.008.09.008.13-.004a.456.456 0 0 0 .109-.05c.035-.032.433-.43 4.203-4.211a212.272 212.272 0 0 0 2.11-2.125 4.02 4.02 0 0 0 1.035-3.055 3.879 3.879 0 0 0-.872-2.16A3.844 3.844 0 0 0 12.144 0c-.886 0-1.753.3-2.453.852-.18.14-.222.183-.758.722l-.484.485-.433-.434a19.35 19.35 0 0 0-.637-.629 4.002 4.002 0 0 0-2.39-.988 7.13 7.13 0 0 0-.411-.004Z"/>
                                    <path d="M6.996 7.805c0 .004.027.058-.55-1.125-.29-.594-.532-1.09-.54-1.098a.31.31 0 0 0-.172-.133.37.37 0 0 0-.16 0 .324.324 0 0 0-.133.078c-.011.012-.226.301-.48.649l-.461.625-2.121.004c-2.012 0-2.13 0-2.149.008a.29.29 0 0 0-.136.074.265.265 0 0 0-.086.136C0 7.051 0 7.063 0 7.105a.297.297 0 0 0 .219.293l.027.008h4.453l.027-.008a.3.3 0 0 0 .137-.082c.012-.011.18-.238.38-.507.194-.27.358-.489.358-.489.004.004.274.555.602 1.227.328.676.602 1.234.61 1.246.044.065.11.11.187.129a.337.337 0 0 0 .125 0 .3.3 0 0 0 .207-.168c.008-.016.23-.742.559-1.824.3-.989.546-1.797.55-1.797l.375 1.047c.124.357.252.712.383 1.066a.32.32 0 0 0 .106.133c.029.019.06.033.093.043.024.008.059.008.903.008.863 0 .879 0 .914-.008a.3.3 0 0 0 .219-.395.31.31 0 0 0-.215-.195c-.02-.004-.075-.004-.782-.004-.71-.004-.754-.004-.757-.008 0-.004-.223-.625-.493-1.379-.27-.753-.496-1.382-.504-1.398a.274.274 0 0 0-.136-.137.254.254 0 0 0-.121-.031c-.032 0-.047 0-.075.008a.3.3 0 0 0-.207.168L6.996 7.805Z"/>
                                </svg>
                            </div>
                            <h2 class="nok25-square-block__heading">
                                Een titeltekst met variabele lengte <?= $x; ?>
                            </h2>
                            <p class="nok25-square-block__text">
                                Aenean ac feugiat nibh. Praesent venenatis non nibh vitae pretium. Suspendisse euismod
                                blandit lorem vel mattis. Pellentesque ultrices velit at nisl placerat faucibus.
                            </p>
                            <a class="nok25-square-block__link" href="#">Read more
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" width="25" height="25"
                                     fill="currentColor">
                                    <path d="m17.5 5.999-.707.707 5.293 5.293H1v1h21.086l-5.294 5.295.707.707L24 12.499l-6.5-6.5z"
                                          data-name="Right"/>
                                </svg>
                            </a>
                        </nok25-square-block>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="fake-scrollbar align-self-stretch">
                <div class="fake-scrollbar-thumb"></div>
            </div>
        </div>
    </div>
</section>

</body>
</html>