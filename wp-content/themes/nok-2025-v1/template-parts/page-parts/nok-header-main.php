<?php
/**
 * Template Name: Header (Main)
 * Description: A header unit for the top of all pages.
 * Slug: nok-header-main
 * Custom Fields:
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

$theme = Theme::get_instance();
$menu_manager = $theme->get_menu_manager();
$star = Assets::getIcon('ui_star');
$logo = '<nok-logo>' . file_get_contents(THEME_ROOT . '/assets/img/nok-logo.svg') .'</nok-logo>';
?>

<nok-top-navigation class="nok-section" data-requires="./nok-toggler.mjs">
    <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkestblue--darker nok-z-1">

        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue nok-align-items-stretch" id="popup-bmi-calculator">
            <nok-popup-header>
                <nok-popup-title>BMI Calculator</nok-popup-title>
                <button title="Klik om te sluiten" class="nok-button nok-button--small" data-unsets-class="popup-open" data-class-target="nok-top-navigation" data-toggle-event="click"
                        data-unsets-attribute="data-state" data-unsets-attribute-value="open" data-attribute-target="#popup-bmi-calculator">
                    <?= Assets::getIcon('ui_close') ?>
                </button>
            </nok-popup-header>
            <nok-popup-body>
				<?php $theme->embed_post_part_template('nok-bmi-calculator', array(), true); ?>
            </nok-popup-body>
        </nok-popup>

        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue" id="popup-search">
            <nok-popup-header>
                <nok-popup-title>Zoeken</nok-popup-title>
                <button title="Sluiten" class="nok-button nok-button--small"
                        data-unsets-class="popup-open" data-class-target="nok-top-navigation"
                        data-toggle-event="click"
                        data-unsets-attribute="data-state" data-unsets-attribute-value="open"
                        data-attribute-target="#popup-search">
                    <?= Assets::getIcon('ui_close') ?>
                </button>
            </nok-popup-header>
            <nok-popup-body>
                <nok-search data-requires="./nok-search.mjs" data-max-results="5">
                    <input type="search" placeholder="Zoek op trefwoord..." class="nok-search-input" autocomplete="off" />
                    <nok-search-results></nok-search-results>
                </nok-search>
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
                <?= Assets::getIcon('ui_reload') ?>
            </button>
        </div>
    </nok-accessibility-helper>

    <nok-navigation-mobile>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-white nok-dark-bg-darkestblue nok-text-contrast nok-fill-contrast  nok-z-3">
            <a href="<?= esc_url(home_url('/')) ?>"><?= $logo; ?></a>
            <button class="nok-button nok-button--small nok-bg-yellow nok-text-contrast nok-invisible-to-sm"
                    tabindex="0">Gratis voorlichting
            </button>
            <a href="tel:+31888832444" role="button" class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0">
                <?= Assets::getIcon('ui_telefoon') ?>
            </a>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow nok-popup-trigger"
                    tabindex="0"
                    data-toggles-class="popup-open" data-class-target="nok-top-navigation"
                    data-toggle-event="click"
                    data-toggles-attribute="data-state" data-toggles-attribute-value="open"
                    data-attribute-target="#popup-search">
                <?= Assets::getIcon('ui_search') ?>
            </button>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0"
                    data-toggles-class="open" data-class-target=".nok-nav-control-dropdown" data-toggle-event="click"  data-toggle-outside="unset"
                    data-swipe="unset" data-auto-restore="10">
                <?= Assets::getIcon('ui_font_size') ?>
            </button>
            <button class="nok-button nok-button-menu nok-nav-menu-toggler nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0"
                    data-toggles-class="sidebar-open" data-class-target="nok-top-navigation" data-toggle-event="click" data-toggle-outside="unset">
                <?= Assets::getIcon('ui_hamburger') ?>
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
                            <a href="#submenu-behandelingen" class="nok-nav-menu-item">Behandelingen</a>
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
                            <a href="#" class="nok-nav-menu-item">Onze behandeling van
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
                    <a href="https://werkenbijdenok.nl" target="_blank" class="nok-nav-menu-item">Werken bij</a>
                    <a href="#" class="nok-nav-menu-item nok-popup-trigger" data-toggles-class="popup-open" data-class-target="nok-top-navigation" data-toggle-event="click"
                       data-toggles-attribute="data-state" data-toggles-attribute-value="open" data-attribute-target="#popup-bmi-calculator">BMI berekenen</a>
                    <a href="#" class="nok-nav-menu-item">Mijn NOK</a>
                    <a href="#" class="nok-nav-menu-item">NOK App</a>
                    <button class="nok-button nok-button--small nok-bg-yellow nok-text-contrast nok-invisible-sm"
                            tabindex="0">Gratis voorlichting
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
                        <span class="nok-text-lightblue nok-star-ratings"><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?></span>5/5
                        op basis van 12.030 beoordelingen
                    </small>
                </div>
                <a href="/verwijzers" class="nok-nav-menu-item">Voor verwijzers</a>
                <a href="https://werkenbijdenok.nl" target="_blank" class="nok-nav-menu-item">Werken bij</a>
                <div><a href="#" class="nok-nav-menu-item nok-popup-trigger"
                        data-toggles-class="popup-open" data-class-target="nok-top-navigation" data-toggle-event="click"
                        data-toggles-attribute="data-state" data-toggles-attribute-value="open" data-attribute-target="#popup-bmi-calculator">BMI berekenen</a></div>
                <div><a href="tel:+31888832444" class="nok-nav-menu-item">088 88 32 444</a></div>
                <a href="/kennisbank/veelgestelde-vragen" class="nok-nav-menu-item">Veelgestelde vragen</a>
                <a href="#" class="nok-nav-menu-item nok-popup-trigger"
                   data-toggles-class="popup-open" data-class-target="nok-top-navigation"
                   data-toggle-event="click"
                   data-toggles-attribute="data-state" data-toggles-attribute-value="open"
                   data-attribute-target="#popup-search">Zoek</a>
                <a href="#"
                   data-toggles-class="open" data-target=".nok-nav-control-dropdown" data-toggle-event="click" data-toggle-outside="unset"
                   data-swipe="unset" data-auto-restore="10">
                    <?= Assets::getIcon('ui_font_size') ?>
                </a>
            </div>
        </nok-navigation-top-row>
        <nok-navigation-menu-bar class="nok-section__inner nok-my-0 nok-z-3">
            <div class="nok-navigation-menu-bar__inner
            nok-bg-white nok-dark-bg-darkerblue nok-dark-text-contrast">
                <div>
                    <a href="<?= esc_url(home_url('/')) ?>"><?= $logo; ?></a>
                </div>
				<?php $menu_manager->render_desktop_menu_bar('primary'); ?>
                <div>
                    <button class="nok-button nok-bg-yellow nok-text-contrast" tabindex="0">Gratis
                        voorlichting
                    </button>
                </div>
            </div>
            <nok-nav-menu-bar-dropdown>
				<?php $menu_manager->render_desktop_dropdown('primary'); ?>
            </nok-nav-menu-bar-dropdown>
        </nok-navigation-menu-bar>
    </nok-navigation-desktop>
</nok-top-navigation>