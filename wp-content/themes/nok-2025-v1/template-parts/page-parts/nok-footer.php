<?php
/**
 * Template Name: Header (Main)
 * Description: Main site header with mobile/desktop navigation, accessibility controls, and BMI calculator popup
 * Slug: nok-header-main
 * Custom Fields:
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Theme;

$theme = Theme::get_instance();
$menu_manager = $theme->get_menu_manager();
$star = Assets::getIcon('ui_star');
$logo = '<nok-logo>' . file_get_contents(THEME_ROOT . '/assets/img/nok-logo.svg') . '</nok-logo>';
?>

<nok-top-navigation class="nok-section" data-requires="./nok-toggler.mjs">
    <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkestblue--darker nok-z-1"
                     data-unsets-class="sidebar-open,popup-open" data-unsets-attribute="state" data-unsets-attribute-value="open" data-no-children="true">

        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue nok-align-items-stretch" id="popup-bmi-calculator">
            <nok-popup-header>
                <nok-popup-title>BMI Calculator</nok-popup-title>
                <button title="Klik om te sluiten" class="nok-button--small" data-unsets-class="popup-open" data-class-target="nok-top-navigation"
                        data-unsets-attribute="state" data-unsets-attribute-value="open" data-attribute-target="#popup-bmi-calculator">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="0.8" d="M2.4 2.8c-.2-.2-.2-.5 0-.7.2-.2.5-.2.7 0l5.1 5.1 5.1-5.1c.2-.2.5-.2.7 0 .2.2.2.5 0 .7L8.9 7.9 14 13c.2.2.2.5 0 .7s-.5.2-.7 0L8.2 8.6l-5.1 5.1c-.2.2-.5.2-.7 0-.2-.2-.2-.5 0-.7l5.1-5.1-5.1-5.1Z"></path>
                    </svg>
                </button>
            </nok-popup-header>
            <nok-popup-body>
				<?php $theme->embed_post_part_template('nok-bmi-calculator', [], true) ?>
            </nok-popup-body>
        </nok-popup>

    </nok-screen-mask>

    <nok-accessibility-helper class="nok-bg-body nok-text-darkerblue nok-dark-text-contrast nok-nav-control-dropdown" data-requires="./nok-user-prefs.mjs" data-require-lazy="true">
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
			<?= $logo ?>
            <button class="nok-button nok-button--small nok-bg-yellow nok-text-contrast nok-invisible-to-sm"
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
                    data-toggles-class="open" data-class-target=".nok-nav-control-dropdown"
                    data-swipe-close=".nok-nav-control-dropdown" data-autohide="10">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path stroke="currentColor"
                          stroke-width="0"
                          d='M11.7 3.7H9.9l-3.3 8.6-2.3-5.7H3.1L.8 12.3H2l.5-1.3h2.3l.5 1.3h3.1l.7-2h3.4l.8 2h1.9l-3.4-8.6ZM2.9 10l.8-2.1.8 2.1H2.9Zm6.7-1.1 1.2-3.2L12 8.9H9.7Z'/>
                </svg>
            </button>
            <button class="nok-button nok-button-menu nok-nav-menu-toggler nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0"
                    data-toggles="sidebar-open" data-class-target="nok-top-navigation">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path class="open" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="1.5" d="M2.7 3.9h11m-11 4h11m-11 4h11"/>
                    <path class="closed" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10"
                          stroke-width="0.8"
                          d="M2.4 2.8c-.2-.2-.2-.5 0-.7.2-.2.5-.2.7 0l5.1 5.1 5.1-5.1c.2-.2.5-.2.7 0 .2.2.2.5 0 .7L8.9 7.9 14 13c.2.2.2.5 0 .7s-.5.2-.7 0L8.2 8.6l-5.1 5.1c-.2.2-.5.2-.7 0-.2-.2-.2-.5 0-.7l5.1-5.1-5.1-5.1Z"/>
                </svg>
            </button>
        </nok-navigation-top-row>

        <nok-navigation-drawer class="nok-z-2">
            <div class="nok-section__inner--stretched nok-nav-carousel
            nok-bg-white--darker nok-dark-bg-darkerblue nok-dark-text-white
            nok-z-2"
                 data-scroll-snapping="true" data-requires="./nok-menu-carousel.mjs" data-require-lazy="true">

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
                    <a href="#" class="nok-nav-menu-item nok-popup-trigger" data-toggles-class="popup-open" data-class-target="nok-top-navigation"
                       data-toggles-attribute="state" data-toggles-attribute-value="open" data-attribute-target="#popup-bmi-calculator">BMI berekenen</a>
                    <a href="#" class="nok-nav-menu-item">Mijn NOK</a>
                    <a href="#" class="nok-nav-menu-item">NOK App</a>
                    <button class="nok-button nok-button--small nok-bg-yellow nok-text-contrast nok-invisible-sm"
                            tabindex="0">Gratis voorlichtingsavond
                    </button>
                </div>
            </div>
        </nok-navigation-drawer>
    </nok-navigation-mobile>

    <nok-navigation-desktop>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-body nok-dark-bg-darkestblue nok-dark-text-white nok-z-2">
            <div class="nok-navigation-top-row__inner
            nok-section__inner nok-my-0">
                <div>
                    <small class="valign-center">
                        <span class="nok-text-lightblue nok-star-ratings"><?= $star ?><?= $star ?><?= $star ?><?= $star ?><?= $star ?></span>5/5
                        op basis van 12.030 beoordelingen
                    </small>
                </div>
                <div>Werken bij</div>
                <div><a href="#" class="nok-nav-menu-item nok-popup-trigger"
                        data-toggles-class="popup-open" data-class-target="nok-top-navigation"
                        data-toggles-attribute="state" data-toggles-attribute-value="open" data-attribute-target="#popup-bmi-calculator">BMI berekenen</a></div>
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
        <nok-navigation-menu-bar class="nok-section__inner nok-my-0 nok-z-3">
            <div class="nok-navigation-menu-bar__inner
            nok-bg-white nok-dark-bg-darkerblue nok-dark-text-contrast">
                <div>
					<?= $logo ?>
                </div>
				<?php $menu_manager->render_desktop_menu_bar('primary') ?>
                <div>Behandelingen</div>
                <div>Over NOK</div>
                <div>Agenda</div>
                <div>Verwijzers</div>
                <div>
                    <button class="nok-button nok-bg-yellow nok-text-contrast" tabindex="0">Gratis
                        voorlichtingsavond
                    </button>
                </div>
            </div>
            <nok-nav-menu-bar-dropdown>
				<?php $menu_manager->render_desktop_dropdown('primary') ?>
            </nok-nav-menu-bar-dropdown>
        </nok-navigation-menu-bar>
    </nok-navigation-desktop>

</nok-top-navigation>