<?php
/**
 * Template Name: Header (Main)
 * Description: Hoofdnavigatie bovenaan elke pagina.
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
$logo = '<nok-logo>' . file_get_contents(THEME_ROOT_ABS . '/assets/img/nok-logo.svg') .'</nok-logo>';
?>

<nok-top-navigation class="nok-section" role="navigation" aria-label="<?php esc_attr_e( 'Hoofdnavigatie', 'nok' ); ?>">
    <nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkestblue--darker">

        <!-- POPUP: BMI CALCULATOR -->
        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue nok-align-items-stretch" id="popup-bmi-calculator">
            <nok-popup-header>
                <nok-popup-title>BMI Calculator</nok-popup-title>
                <button title="Klik om te sluiten" aria-label="Sluiten" class="nok-button nok-button--small" data-unsets-class="popup-open" data-class-target="nok-top-navigation" data-toggle-event="click"
                        data-unsets-attribute="data-state" data-unsets-attribute-value="open" data-attribute-target="#popup-bmi-calculator">
                    <?= Assets::getIcon('ui_close') ?>
                </button>
            </nok-popup-header>
            <nok-popup-body>
				<?php $theme->embed_post_part_template('nok-bmi-calculator', array(), true); ?>
            </nok-popup-body>
        </nok-popup>

        <!-- POPUP: SEARCH -->
        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue" id="popup-search">
            <nok-popup-header>
                <nok-popup-title>Zoeken</nok-popup-title>
                <button title="Sluiten" aria-label="Sluiten" class="nok-button nok-button--small"
                        data-unsets-class="popup-open" data-class-target="nok-top-navigation"
                        data-toggle-event="click"
                        data-unsets-attribute="data-state" data-unsets-attribute-value="open"
                        data-attribute-target="#popup-search">
                    <?= Assets::getIcon('ui_close') ?>
                </button>
            </nok-popup-header>
            <nok-popup-body>
                <nok-search data-requires="./nok-search.mjs" data-max-results="5">
                    <input type="search" placeholder="Zoek op trefwoord..." class="nok-search-input" autocomplete="off" aria-label="Zoeken" />
                    <nok-search-results></nok-search-results>
                </nok-search>
            </nok-popup-body>
        </nok-popup>

        <!-- POPUPS: VRAGENLIJSTEN (one per published vragenlijst CPT post) -->
        <?php
        $vl_posts = get_posts([
            'post_type'      => 'vragenlijst',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        foreach ($vl_posts as $vl_post) :
            $vl_popup_id = 'popup-vragenlijst-' . $vl_post->post_name;
        ?>
        <nok-popup class="nok-bg-body nok-dark-bg-darkerblue" id="<?= esc_attr($vl_popup_id) ?>"
                   data-on-close="reset">
            <nok-popup-header>
                <nok-popup-title><?= esc_html(get_the_title($vl_post)) ?></nok-popup-title>
                <button title="Sluiten" aria-label="Sluiten" class="nok-button nok-button--small"
                        data-unsets-class="popup-open" data-class-target="nok-top-navigation"
                        data-toggle-event="click"
                        data-unsets-attribute="data-state" data-unsets-attribute-value="open"
                        data-attribute-target="#<?= esc_attr($vl_popup_id) ?>">
                    <?= Assets::getIcon('ui_close') ?>
                </button>
            </nok-popup-header>
            <nok-popup-body>
                <?php $theme->embed_post_part_template('nok-vragenlijst', [
                    'vragenlijst_slug' => $vl_post->post_name
                ], true); ?>
            </nok-popup-body>
        </nok-popup>
        <?php endforeach; ?>

    </nok-screen-mask>

    <nok-accessibility-helper class="nok-bg-body nok-text-darkerblue nok-dark-text-contrast nok-nav-control-dropdown" data-requires="./nok-user-prefs.mjs" data-require-lazy="true">
        <h5>Toegankelijkheid</h5>
        <div class="nok-layout-flex-row half-flex-gap">
            Tekstgrootte:
            <button class="nok-button nok-button--small nok-bg-darkerblue nok-text-contrast font-decrease"
                    aria-label="Tekstgrootte verkleinen"
                    style="font-variant: all-small-caps;" tabindex="0" data-set-font-size="-0.1">A
            </button>
            <button class="nok-button nok-button--small nok-bg-darkerblue nok-text-contrast font-increase"
                    aria-label="Tekstgrootte vergroten"
                    tabindex="0" data-set-font-size="+0.1">A
            </button>
            <button class="nok-button nok-button--small nok-bg-darkblue nok-dark-bg-lightblue--darker nok-text-contrast"
                    aria-label="Tekstgrootte herstellen"
                    tabindex="0" data-reset-font-size="true">
                <?= Assets::getIcon('ui_reload') ?>
            </button>
        </div>
    </nok-accessibility-helper>

    <nok-navigation-mobile>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-white nok-dark-bg-darkestblue nok-text-contrast nok-fill-contrast  nok-z-3">
            <a href="<?= esc_url(home_url('/')) ?>" aria-label="Nederlandse Obesitas Kliniek - Home" title="Nederlandse Obesitas Kliniek - Home"><?= $logo; ?></a>
            <a href="/aanmelden-gratis-voorlichting#nok-voorlichting-form" role="button" class="nok-button nok-button--small nok-bg-yellow nok-text-contrast nok-invisible-to-sm"
                    tabindex="0">Gratis voorlichting
            </a>
            <a href="tel:+31888832444" role="button" aria-label="Bel ons: 088 883 2444" class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    tabindex="0">
                <?= Assets::getIcon('ui_telefoon') ?>
            </a>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow nok-popup-trigger"
                    aria-label="Zoeken"
                    tabindex="0"
                    data-toggles-class="popup-open" data-class-target="nok-top-navigation"
                    data-toggle-event="click"
                    data-toggles-attribute="data-state" data-toggles-attribute-value="open"
                    data-attribute-target="#popup-search">
                <?= Assets::getIcon('ui_search') ?>
            </button>
            <button class="nok-button nok-button-menu nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    aria-label="Tekstgrootte aanpassen"
                    tabindex="0"
                    data-toggles-class="open" data-class-target=".nok-nav-control-dropdown" data-toggle-event="click"  data-toggle-outside="unset"
                    data-swipe="unset" data-auto-restore="10">
                <?= Assets::getIcon('ui_font_size') ?>
            </button>
            <button class="nok-button nok-button-menu nok-nav-menu-toggler nok-dark-bg-darkerblue nok-text-contrast no-shadow"
                    aria-label="Menu openen"
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

				<?php $menu_manager->render_mobile_carousel('mobile_primary'); ?>
            </div>
            <div class="nok-section__inner--stretched nok-nav-footer nok-text-contrast nok-bg-darkerblue nok-dark-bg-darkestblue nok-z-1">
				<?php $menu_manager->render_mobile_drawer_footer('mobile_drawer_footer'); ?>
            </div>
        </nok-navigation-drawer>
    </nok-navigation-mobile>

    <nok-navigation-desktop>
        <nok-navigation-top-row class="nok-section__inner--stretched  nok-bg-body nok-dark-bg-darkestblue nok-dark-text-white nok-z-2">
            <div class="nok-navigation-top-row__inner
            nok-section__inner nok-my-0">
                <div><!--
                    <small class="valign-center">
                        <span class="nok-text-lightblue nok-star-ratings"><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?><?= $star; ?></span>5/5
                        op basis van 12.030 beoordelingen
                    </small>-->
                </div>
                <?php $menu_manager->render_top_row('top_row'); ?>
                <a href="#" class="nok-nav-menu-item" role="button" aria-label="Tekstgrootte aanpassen" title="Tekstgrootte aanpassen"
                   data-toggles-class="open" data-class-target=".nok-nav-control-dropdown" data-toggle-event="click" data-toggle-outside="unset"
                   data-swipe="unset" data-auto-restore="10">
                    <?= Assets::getIcon('ui_font_size') ?>
                </a>
            </div>
        </nok-navigation-top-row>
        <nok-navigation-menu-bar class="nok-section__inner nok-my-0 nok-z-3">
            <div class="nok-navigation-menu-bar__inner
            nok-bg-white nok-dark-bg-darkerblue nok-dark-text-contrast">
                <div>
                    <a href="<?= esc_url(home_url('/')) ?>" aria-label="Nederlandse Obesitas Kliniek - Home"><?= $logo; ?></a>
                </div>
				<?php $menu_manager->render_desktop_menu_bar('primary'); ?>
                <div>
                    <a href="/aanmelden-gratis-voorlichting#nok-voorlichting-form" role="button" class="nok-button nok-bg-yellow nok-text-contrast" tabindex="0">Gratis voorlichting</a>
                </div>
            </div>
            <nok-nav-menu-bar-dropdown>
				<?php $menu_manager->render_desktop_dropdown('primary'); ?>
            </nok-nav-menu-bar-dropdown>
        </nok-navigation-menu-bar>
    </nok-navigation-desktop>
</nok-top-navigation>