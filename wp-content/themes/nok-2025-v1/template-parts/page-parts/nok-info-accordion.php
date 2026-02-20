<?php
/**
 * Template Name: Info Accordion
 * Description: Single column layout with accordion
 * Slug: nok-info-accordion
 * Custom Fields:
 * - colors:color-selector(section-colors)!page-editable!default(nok-bg-darkerblue nok-text-contrast)
 * - accordion_block_colors:color-selector(block-colors)!page-editable!default(nok-bg-transparent)
 * - accordion_open_first:checkbox!default(true)!descr[Open het eerste accordion item standaard]!page-editable
 * - accordion_bordered:checkbox!default(false)!descr[Lijn tussen accordion items?]
 * - accordion_framed:checkbox!default(true)!descr[Voeg een kader toe rondom de accordion items]
 * - accordion_checkmarks:select(Vraagtekens met vinkjes::question-marks|Vinkjes::checkmarks)!descr[Vinkjes of vraagtekens voor de titels plaatsen?]
 * - accordion_checkmarks_colors:select(Blauw::var(--nok-lightblue)|Groen::var(--nok-green)|Geel::var(--nok-yellow)|Lightgroen::var(--nok-greenblue--lighter))!default(Blauw)!descr[Kleur van de vinkjes]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - accordion_items:repeater(title:text,content:textarea,button_url:url,button_text:text)!descr[Voeg accordion items toe]
 * - accordion_posts:post_repeater(kennisbank)!descr[Kies specifieke kennisbank items om te tonen in de accordion]
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
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
            <?php if (!$c->hide_title->isTrue()) : ?>
            <h2 class="nok-fs-6  nok-span-all-columns"><?= $c->title() ?></h2>
            <?php endif; ?>

            <?= $c->content(); ?>

            <?php if ( $c->has( 'accordion_items' ) || $c->has( 'accordion_posts' ) ): ?>
                <div class="nok-layout-grid nok-layout-grid__1-column <?= $c->accordion_bordered->isTrue('nok-grid-gap-0', '' ); ?>" data-requires="./nok-accordion.mjs" data-require-lazy="true">

                    <div class="<?= $c->accordion_block_colors->raw(); ?> nok-subtle-shadow nok-rounded-border-large nok-p-1"
                         style="display:<?= $c->accordion_framed->isTrue('grid', 'contents' ); ?>;">
                        <?php
                        $accordion_group = 'accordion-group';

                        $accordion_data = array_merge($c->accordion_items->json(), $c->accordion_posts->json());

                        foreach ( $accordion_data as $index => $item ) :
                            if (is_int($item)) :
                                $post = get_post($item);
                                if (!$post) continue;

                                $item = [
                                        'title'       => get_the_title($post),
                                        'content'     => get_the_content(null,false,$post),
                                        'button_url'  => get_permalink($post),
                                        'button_text' => 'Lees meer',
                                ];
                            endif;
                            ?>
                            <nok-accordion class="<?= $c->accordion_bordered->isTrue(($index < count($accordion_data) - 1 ? 'nok-border-bottom-1' : '')); ?> <?= $c->accordion_checkmarks->is('checkmarks', 'checkmarks'); ?> <?= $c->accordion_checkmarks->is('question-marks', 'question-marks'); ?>">
                                <details
                                        class="<?= $c->accordion_block_colors->raw() ?> nok-rounded-border"
                                        name="<?= esc_attr( $accordion_group ) ?>" <?= ( $index == 0 && $c->accordion_open_first->isTrue() ) ? 'open' : '' ?>>
                                    <summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-sm-2 fw-bold" style="--checkmark-color:<?= $c->accordion_checkmarks_colors ?>;">
                                        <?= esc_html( $item['title'] ) ?>
                                    </summary>
                                    <?php if ( ! empty( $item['content'] ) ) : ?>
                                    <div class="accordion-content nok-p-2 nok-pt-0">
                                        <div class="<?= ! empty( $item['button_url'] ) ? 'nok-mb-1' : '' ?>">
                                            <?= wp_kses_post( $item['content'] ) ?>
                                        </div>
                                        <?php if ( ! empty( $item['button_url'] ) ) : ?>
                                            <a href="<?= esc_url( $item['button_url'] ) ?>" role="button"
                                               class="nok-button nok-text-contrast nok-bg-darkblue--darker nok-dark-bg-darkestblue nok-visible-xs nok-align-self-stretch fill-mobile"
                                               tabindex="0"><span>
                                                <?= ! empty( trim( $item['button_text'] ) ) ? esc_html( $item['button_text'] ) : $c->accordion_button_text ?></span>
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