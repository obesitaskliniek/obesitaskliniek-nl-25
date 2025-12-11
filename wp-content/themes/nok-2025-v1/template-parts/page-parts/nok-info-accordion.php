<?php
/**
 * Template Name: Info Accordion
 * Description: Single column layout with accordion
 * Slug: nok-info-accordion
 * Custom Fields:
 * - colors:select(Transparant::nok-bg-body nok-text-darkerblue nok-dark-text-contrast|Grijs::nok-bg-body--darker gradient-background nok-text-darkerblue nok-dark-text-contrast|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(Blauw)
 * - accordion_open_first:checkbox!default(true)!descr[Open het eerste accordion item standaard]!page-editable
 * - accordion_bordered:checkbox!default(false)!descr[Lijn tussen accordion items?]
 * - accordion_framed:checkbox!default(true)!descr[Voeg een kader toe rondom de accordion items]
 * - accordion_checkmarks:select(Vraagtekens met vinkjes::question-marks|Vinkjes::checkmarks)!descr[Vinkjes of vraagtekens voor de titels plaatsen?]
 * - accordion_checkmarks_colors:select(Blauw::var(--nok-lightblue)|Groen::var(--nok-green)|Geel::var(--nok-yellow)|Lightgroen::var(--nok-greenblue--lighter))!default(Blauw)!descr[Kleur van de vinkjes]
 * - accordion_items:repeater(title:text,content:textarea,button_url:url,button_text:text)!descr[Voeg accordion items toe]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

?>

<nok-section class="<?= $c->colors ?> gradient-background">

    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
        <article class="nok-layout-grid nok-layout-grid__1-column nok-align-items-start">
            <h2 class="nok-fs-6  nok-span-all-columns"><?= $c->title() ?></h2>

            <?= $c->content(); ?>

            <?php if ( $c->has( 'accordion_items' ) ): ?>
                <div class="nok-layout-grid nok-layout-grid__1-column <?= $c->accordion_bordered->isTrue('nok-grid-gap-0', '' ); ?>" data-requires="./nok-accordion.mjs" data-require-lazy="true">

                    <div class="<?= $c->accordion_block_colors->raw(); ?> nok-subtle-shadow nok-rounded-border-large nok-p-1"
                         style="display:<?= $c->accordion_framed->isTrue('grid', 'contents' ); ?>;">
                        <?php
                        $accordion_group = 'accordion-group';

                        $accordion_data = $c->accordion_items->json();

                        foreach ( $accordion_data as $index => $item ) :?>
                            <nok-accordion class="<?= $c->accordion_bordered->isTrue(($index < count($accordion_data) - 1 ? 'nok-border-bottom-1' : '')); ?> <?= $c->accordion_checkmarks->is('checkmarks', 'checkmarks'); ?> <?= $c->accordion_checkmarks->is('question-marks', 'question-marks'); ?>">
                                <details
                                        class="<?= $c->accordion_block_colors->raw() ?> nok-rounded-border"
                                        name="<?= esc_attr( $accordion_group ) ?>" <?= ( $index == 0 && $c->accordion_open_first->isTrue() ) ? 'open' : '' ?>>
                                    <summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-sm-2 fw-bold" style="--checkmark-color:<?= $c->accordion_checkmarks_colors ?>;">
                                        <?= esc_html( $item['title'] ) ?>
                                    </summary>
                                    <?php if ( ! empty( $item['content'] ) ) : ?>
                                    <div class="accordion-content nok-p-2 nok-pt-0">
                                        <p class="<?= ! empty( $item['button_url'] ) ? 'nok-mb-1' : '' ?>">
                                            <?= wp_kses_post( $item['content'] ) ?>
                                        </p>
                                        <?php if ( ! empty( $item['button_url'] ) ) : ?>
                                            <a href="<?= esc_url( $item['button_url'] ) ?>" role="button"
                                               class="nok-button nok-text-contrast nok-bg-darkblue--darker nok-dark-bg-darkestblue nok-visible-xs nok-align-self-stretch fill-mobile"
                                               tabindex="0">
                                                <?= ! empty( trim( $item['button_text'] ) ) ? esc_html( $item['button_text'] ) : $c->accordion_button_text ?>
                                                <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </details>
                            </nok-accordion>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endif; ?>
        </article>
    </div>
</nok-section>