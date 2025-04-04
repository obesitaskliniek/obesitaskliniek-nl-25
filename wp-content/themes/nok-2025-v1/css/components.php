<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
const NOK_THEME_ROOT = 'https://dev.obesitaskliniek.nl/wp-content/themes/nok-2025-v1'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">

    <title>NOK Components</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <link href="../fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="./color_tests-v2.css?cache=<?= time(); ?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./nok-components.css?cache=<?= time(); ?>" crossorigin="anonymous">

    <!-- <link rel="modulepreload" href="../js/mobileConsole/hnl.mobileconsole.js?cache=<?= time(); ?>">
    <script src="../js/mobileConsole/hnl.mobileconsole.js?cache=<?= time(); ?>"></script><!--//-->
    <link rel="modulepreload" href="../js/entrypoint.min.mjs?cache=<?= time(); ?>">
    <script type="module" src="../js/entrypoint.min.mjs?cache=<?= time(); ?>" defer></script>
</head>
<body class="no-js nok-bg-body nok-text-contrast">
<div class="nok-accessibility-helper"></div>

<?php $star = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
  <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
</svg>';
$logo = '<nok-logo><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 463.58 173.6">
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
</svg></nok-logo>'; ?>


<nok-top-navigation class="nok-section" data-requires="./nok-toggler.mjs?cache=<?= time(); ?>">
    <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-1" data-toggles="open"></nok-screen-mask>

    <nok-accessibility-helper class="nok-bg-body nok-text-darkerblue nok-dark-text-contrast nok-nav-control-dropdown" data-requires="./nok-user-prefs.mjs?cache=<?= time(); ?>">
        <h5>Toegankelijkheid</h5>
        <div class="nok-layout-flex-row half-flex-gap">
            Tekstgrootte:
            <button class="nok-button nok-button--small nok-bg-darkerblue nok-text-contrast font-decrease"
                    style="font-variant: all-small-caps;" tabindex="0" data-set-font-size="-0.1">A
            </button>
            <button class="nok-button nok-button--small nok-bg-darkerblue nok-text-contrast font-increase"
                    tabindex="0" data-set-font-size="+0.1">A
            </button>
            <button class="nok-button nok-button--small nok-bg-darkblue nok-dark-bg-lightblue--darker nok-text-contrast"
                    tabindex="0" data-reset-font-size="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                </svg>
            </button>
        </div>
    </nok-accessibility-helper>

    <nok-navigation-mobile>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-white nok-dark-bg-darkestblue nok-text-contrast nok-fill-contrast  nok-z-3">
            <?= $logo; ?>
            <button class="nok-button nok-button--small nok-base-font nok-bg-yellow nok-text-contrast nok-invisible-to-sm"
                    tabindex="0">Gratis voorlichtingsavond
            </button>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z'/>
                </svg>
            </button>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/>
                </svg>
            </button>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0"
                    data-toggles="open" data-target=".nok-nav-control-dropdown"
                    data-swipe-close=".nok-nav-control-dropdown" data-autohide="10">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M11.7 3.7H9.9l-3.3 8.6-2.3-5.7H3.1L.8 12.3H2l.5-1.3h2.3l.5 1.3h3.1l.7-2h3.4l.8 2h1.9l-3.4-8.6ZM2.9 10l.8-2.1.8 2.1H2.9Zm6.7-1.1 1.2-3.2L12 8.9H9.7Z'/>
                </svg>
            </button>
            <button class="nok-button nok-button-menu nok-nav-menu-toggler nok-dark-bg-darkerblue nok-text-contrast no-shadow nok-fill-yellow"
                    tabindex="0"
                    data-toggles="open">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path class="open" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="1.5" d="M2.7 3.9h11m-11 4h11m-11 4h11"/>
                    <path class="closed" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="0.8"
                          d="M2.4 2.8c-.2-.2-.2-.5 0-.7.2-.2.5-.2.7 0l5.1 5.1 5.1-5.1c.2-.2.5-.2.7 0 .2.2.2.5 0 .7L8.9 7.9 14 13c.2.2.2.5 0 .7s-.5.2-.7 0L8.2 8.6l-5.1 5.1c-.2.2-.5.2-.7 0-.2-.2-.2-.5 0-.7l5.1-5.1-5.1-5.1Z"/>
                </svg>
                <!--data-swipe-close=".nok-nav-drawer" data-swipe-direction="x" data-swipe-limits="0,9999"-->
            </button>
        </nok-navigation-top-row>

        <nok-navigation-drawer class="nok-z-2">
            <div class="nok-section__inner--stretched nok-nav-carousel 
            nok-bg-white--darker nok-dark-bg-darkerblue nok-dark-text-white 
            nok-z-2"
                 data-scroll-snapping="true" data-requires="./nok-menu-carousel.mjs">
                <div class="nok-nav-carousel__inner nok-text-darkerblue nok-dark-text-white">
                    <div class="nok-nav-carousel__slide">
                        <div class="nok-nav-menu-items" id="topmenu">
                            <a href="#submenu-behandelingen" class="nok-nav-menu-item nok-nav-menu-item--active">Behandelingen</a>
                            <a href="#submenu-over-nok" class="nok-nav-menu-item">Over NOK</a>
                            <a href="#" class="nok-nav-menu-item">Agenda</a>
                            <a href="#" class="nok-nav-menu-item">Verwijzers</a>
                        </div>
                    </div>
                    <div class="nok-nav-carousel__slide">
                        <div class="nok-nav-menu-items" id="submenu-behandelingen">
                            <a href="#topmenu" class="nok-nav-menu-item nok-nav-menu-item__back">&laquo; Terug naar
                                overzicht</a>
                            <a href="#" class="nok-nav-menu-item">Wat is obesitas?</a>
                            <a href="#" class="nok-nav-menu-item nok-nav-menu-item--active">Onze behandeling van
                                obesitas</a>
                            <a href="#" class="nok-nav-menu-item">Ons behandelprogramma</a>
                            <a href="#" class="nok-nav-menu-item">De operatie</a>
                            <a href="#" class="nok-nav-menu-item">De kosten van de behandeling</a>
                        </div>
                        <div class="nok-nav-menu-items" id="submenu-over-nok">
                            <a href="#topmenu" class="nok-nav-menu-item nok-nav-menu-item__back">&laquo; Terug naar
                                overzicht</a>
                            <a href="#" class="nok-nav-menu-item">Over ons</a>
                            <a href="#" class="nok-nav-menu-item">Team van specialisten</a>
                            <a href="#" class="nok-nav-menu-item">Vestigingen</a>
                            <a href="#" class="nok-nav-menu-item">Ervaringen</a>
                            <a href="#" class="nok-nav-menu-item">Veelgestelde vragen</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nok-section__inner--stretched nok-nav-footer nok-text-contrast nok-bg-darkerblue nok-dark-bg-darkestblue nok-z-1">
                <div class="nok-nav-menu-items nok-nav-menu-items--compact">
                    <a href="#" class="nok-nav-menu-item nok-nav-menu-item--active">Werken bij</a>
                    <a href="#" class="nok-nav-menu-item">Kennisbank</a>
                    <a href="#" class="nok-nav-menu-item">Mijn NOK</a>
                    <a href="#" class="nok-nav-menu-item">NOK App</a>
                    <button class="nok-button nok-button--small nok-base-font nok-bg-yellow nok-mt-1 nok-invisible-sm"
                            tabindex="0">Gratis voorlichtingsavond
                    </button>
                </div>
            </div>
        </nok-navigation-drawer>
    </nok-navigation-mobile>

    <nok-navigation-desktop>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-body nok-dark-bg-darkestblue nok-dark-text-white  nok-z-2">
            <div class="nok-navigation-top-row__inner
            nok-section__inner nok-collapse-y">
                <div>
                    <small class="valign-center">
                        <span class="nok-text-lightblue nok-star-ratings"><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?></span>5/5
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
                <a href="#"
                   data-toggles="open" data-target=".nok-nav-control-dropdown"
                   data-swipe-close=".nok-nav-control-dropdown" data-autohide="10">
                    <span style="font-variant: all-small-caps;">a</span>A
                </a>
            </div>
        </nok-navigation-top-row>
        <nok-navigation-menu-bar class="nok-section__inner nok-collapse-y nok-z-3">
            <div class="nok-navigation-menu-bar__inner
            nok-bg-white nok-dark-bg-darkerblue nok-dark-text-contrast"
                 data-toggles="open">
                <div>
                    <?= $logo; ?>
                </div>
                <div>Behandelingen</div>
                <div>Over NOK</div>
                <div>Agenda</div>
                <div>Verwijzers</div>
                <div>
                    <button class="nok-button nok-base-font nok-bg-yellow" tabindex="0">Gratis
                        voorlichtingsavond
                    </button>
                </div>
            </div>
            <nok-nav-menu-bar-dropdown>
                <div class="dropdown-contents nok-bg-white nok-dark-bg-darkerblue--darker nok-dark-text-contrast">
                    <div class="dropdown-contents-menu">
                        <h3>Behandeling</h3>
                        <div>Wat is obesitas?</div>
                        <div>Onze behandeling van obesitas</div>
                        <div>Ons behandelprogramma</div>
                        <div>De operatie</div>
                        <div>De kosten van de behandeling</div>
                    </div>
                    <nok-square-block class="nok-bg-darkerblue">
                        <h3 class="nok-square-block__heading">
                            Vragen of behoefte aan persoonlijk advies?
                        </h3>
                        <button class="nok-button nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
                            Neem contact op
                            <svg class="nok-text-yellow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25"
                                 width="25" height="25" fill="currentColor">
                                <path d="m17.5 5.999-.707.707 5.293 5.293H1v1h21.086l-5.294 5.295.707.707L24 12.499l-6.5-6.5z"
                                      data-name="Right"/>
                            </svg>
                        </button>
                    </nok-square-block>
                </div>
            </nok-nav-menu-bar-dropdown>
        </nok-navigation-menu-bar>
    </nok-navigation-desktop>

</nok-top-navigation>


</body>
</html>