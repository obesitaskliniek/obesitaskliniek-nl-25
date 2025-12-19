<?php
/**
 * Template Name: Process Showcase
 * Description: Tab-based showcase with vertical navigation and content panel with autoplay
 * Slug: nok-process-showcase
 * Custom Fields:
 *  - autoplay:checkbox!default(true)!descr[Automatisch wisselen tussen items]
 *  - autoplay_interval:select(3 seconden::3000|5 seconden::5000|7 seconden::7000|10 seconden::10000)!default(5000)!descr[Interval voor automatisch wisselen]
 *  - colors:select(Transparant::nok-bg-body nok-text-darkerblue|Grijs::nok-bg-body--darker gradient-background nok-text-darkerblue|Geel::nok-bg-yellow nok-text-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(Transparant)
 *  - block_item_colors:select(Blauw::nok-bg-darkerblue nok-text-white|Lichter blauw::nok-bg-darkblue--darker nok-text-white|Donkerblauw::nok-bg-darkerblue--darker nok-text-white|Geel::nok-bg-yellow nok-text-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Grijs::nok-bg-lightgrey nok-text-darkblue)!page-editable!default(Wit)
 *  - circle_color:select(Blauw::var(--nok-darkerblue)|Wit::var(--nok-darkerblue)|Geel::var(--nok-yellow--darker)|Automatisch-lichter::oklch(from var(--bg-color) calc(l * 1.1) c h / 1)|Automatisch-donkerder::oklch(from var(--bg-color) calc(l * .9) c h / 1)|Uit::transparent)!page-editable!default(Uit)
 *  - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - items:repeater(tab_title:text,panel_title:text,panel_content:textarea,button_text:text,button_url:url)!descr[Voeg proces stappen toe]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;

// Circle color as CSS custom property
$circle_style  = $c->circle_color->css_var( 'circle-background-color' );
$circle_offset = '--circle-offset:calc(50vw + (var(--section-max-width) * 0.05));--circle-top:calc(1 * var(--nok-spacing-section-ends, 1.5rem))';

// Get items from repeater
$items = $c->items->json( [] );

// Generate unique ID for this instance
$showcase_id = 'process-showcase-' . wp_unique_id();

// Tab button class sets (PHP = source of truth)
$tab_active_class     = 'nok-bg-darkerblue nok-text-white';
$tab_inactive_class   = $c->block_item_colors->attr();
$arrow_active_class   = 'nok-text-yellow';
$arrow_inactive_class = 'nok-text-darkblue';
?>

<nok-section class="circle <?= $c->colors ?>"
             style="<?= $circle_style ?>; <?= $circle_offset ?>;">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue( 'nok-section-narrow' ); ?>"
         data-requires="./nok-process-showcase.mjs"
         data-tab-class-active="<?= esc_attr( $tab_active_class ); ?>"
         data-tab-class-inactive="<?= esc_attr( $tab_inactive_class ); ?>"
         data-arrow-class-active="<?= esc_attr( $arrow_active_class ); ?>"
         data-arrow-class-inactive="<?= esc_attr( $arrow_inactive_class ); ?>"
         data-require-lazy="true">

        <article class="nok-layout-grid nok-layout-grid__1-column"
                 id="<?= esc_attr( $showcase_id ) ?>"
                 data-autoplay="<?= $c->autoplay->isTrue( 'true', 'false' ) ?>"
                 data-autoplay-interval="<?= $c->autoplay_interval->attr() ?>">

            <h2 class="nok-fs-6 nok-mb-2"><?= $c->title() ?></h2>

            <?= $c->content(); ?>

            <?php if ( ! empty( $items ) ) : ?>
                <div class="nok-layout-grid nok-layout-grid__2-column nok-grid-gap-3">

                    <!-- Tab navigation (left column) -->
                    <nav class="nok-layout-flex-column" role="tablist"
                         aria-label="<?= esc_attr( $c->title() ) ?>">
                        <?php foreach ( $items as $index => $item ) :
                            $is_active = ( $index === 0 );
                            $tab_id = $showcase_id . '-tab-' . $index;
                            $panel_id = $showcase_id . '-panel-' . $index;
                            ?>
                            <button role="tab"
                                    id="<?= esc_attr( $tab_id ) ?>"
                                    class="nok-button nok-button--large nok-rounded-border-large
							               <?= $is_active ? $tab_active_class : $tab_inactive_class; ?>"
                                    style="justify-content: space-between; width: 100%;"
                                    aria-selected="<?= $is_active ? 'true' : 'false' ?>"
                                    aria-controls="<?= esc_attr( $panel_id ) ?>"
                                    tabindex="<?= $is_active ? '0' : '-1' ?>">
                                <span><?= esc_html( $item['tab_title'] ?? '' ) ?></span>
                                <?= Assets::getIcon( 'ui_arrow-right-long', ( $is_active ? $arrow_active_class : $arrow_inactive_class ) ) ?>
                            </button>
                        <?php endforeach; ?>
                    </nav>

                    <!-- Content panels (right column) -->
                    <div class="nok-column-span-to-lg-1">
                        <?php foreach ( $items as $index => $item ) :
                            $is_active = ( $index === 0 );
                            $tab_id = $showcase_id . '-tab-' . $index;
                            $panel_id = $showcase_id . '-panel-' . $index;
                            ?>
                            <div role="tabpanel"
                                 id="<?= esc_attr( $panel_id ) ?>"
                                 class="nok-layout-flex-column nok-mx-0 nok-ms-xl-5 nok-align-items-start"
                                 aria-labelledby="<?= esc_attr( $tab_id ) ?>"
                                    <?= $is_active ? '' : 'hidden' ?>>

                                <h3 class="nok-fs-5"><?= esc_html( $item['panel_title'] ?? '' ) ?></h3>

                                <div class="nok-text-content">
                                    <?= wp_kses_post( wpautop( $item['panel_content'] ?? '' ) ) ?>
                                </div>

                                <?php if ( ! empty( $item['button_url'] ) ) : ?>
                                    <a role="button"
                                       href="<?= esc_url( $item['button_url'] ) ?>"
                                       class="nok-button nok-bg-darkerblue nok-text-white nok-align-self-start">
                                        <span><?= esc_html( $item['button_text'] ?? 'Lees meer' ) ?></span>
                                        <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endif; ?>

        </article>
    </div>
</nok-section>