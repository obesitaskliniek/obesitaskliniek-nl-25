<?php
/**
 * Template Name: Quote Showcase
 * Description: Two-column layout with quote carousel and accordion items
 * Slug: nok-quote-showcase
 * Custom Fields:
 * - quote_posts:post_repeater(post:ervaringen)!descr[Kies specifieke ervaringsverhalen om te tonen in de quote showcase]
 * - quote_items:repeater(quote:text,name:text,subname:text)!descr[Voeg handmatige quotes toe om te tonen in de quote showcase]
 * - random_quotes:checkbox!default(true)!descr[Vul aan met willekeurige ervaringen indien minder dan 5 quotes aanwezig zijn]
 * - accordion_open_first:checkbox!default(true)!descr[Open het eerste accordion item standaard]
 * - accordion_framed:checkbox!default(true)!descr[Voeg een kader toe rondom de accordion items]
 * - accordion_items:repeater(title:text,content:textarea,button_text:text,button_url:url)!descr[Voeg accordion items toe die naast de quote showcase getoond worden]
 * - accordion_button_text:text!default(Lees meer)!descr[Standaardtekst voor de knop (als die er is) in een accordion item]
 *  - layout:select(quotes-left|quotes-right|accordion-left-title-top)!page-editable!default(left)
 *  - colors:select(Transparant::nok-bg-body|Grijs::nok-bg-body--darker gradient-background|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(Transparant)
 *  - circle_color:select(Blauw::var(--nok-darkerblue)|Wit::var(--nok-darkerblue)|Automatisch-lichter::oklch(from var(--bg-color) calc(l * 1.2) c h / 1)|Automatisch-donkerder::oklch(from var(--bg-color) calc(l * .8) c h / 1)|Uit::transparent)!page-editable!default(Uit)
 *  - accordion_block_colors:select(Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(Wit)
 *  - quote_block_colors:select(Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(Wit)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;


if ($c->layout->is( 'quotes-left' )) {
    $quote_column_order = '1';
    $quote_column_class = 'nok-column-first-xxl-2 nok-column-first-xl-3';
    $accordion_column_order = '2';
    $accordion_column_class = 'nok-column-last-xl-3';
} elseif ( $c->layout->is( 'quotes-right' ) || $c->layout->is( 'accordion-left-title-top' ) ) {
    $quote_column_order = '2';
    $quote_column_class = 'nok-column-last-xxl-2 nok-column-last-xl-3';
    $accordion_column_order = '1';
    $accordion_column_class = 'nok-column-first-xl-3';
} else {
    $quote_column_order = '1';
    $quote_column_class = 'nok-column-first-xxl-2 nok-column-first-xl-3';
    $accordion_column_order = '2';
    $accordion_column_class = 'nok-column-last-xl-3';
}

// Circle color as CSS custom property
$circle_style = $c->circle_color->css_var('circle-background-color');

// Circle offset calculation based on layout
$circle_offset = "--circle-offset:" . $c->layout->is('left', 'calc(50vw - (var(--section-max-width) * 0.35))', 'calc(50vw + (var(--section-max-width) * 0.25))');

?>

<nok-section class="circle <?= $c->colors ?> gradient-background"
             style="<?= $circle_style ?>; <?= $circle_offset ?>;">

    <div class="nok-section__inner">
        <!--<article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">-->
        <article class="nok-layout-grid nok-columns-1 nok-columns-xl-6 nok-columns-xxl-5 nok-align-items-start nok-column-gap-3">
            <?php
            if ( $c->layout->is( 'accordion-left-title-top' ) ) {
                the_title( ' <div class="nok-span-all-columns nok-mb-1"><h2 class="nok-fs-5 nok-mb-0_5">', '</h2></div>' );
            }
            ?>

            <div class="nok-layout-flex-column nok-align-items-stretch <?= $quote_column_class; ?>" style="order:<?= $quote_column_order; ?>">
                <?php
                if ( ! $c->layout->is( 'accordion-left-title-top' ) ) {
                    the_title( '<h2 class="nok-fs-5">', '</h2>' );
                }
                ?>

                <?php
                if (get_the_content() !== '') : ?>
                    <div><?php the_content(); ?></div>
                <?php endif; ?>

                <?php if ( $c->has( 'quote_items' ) || $c->has( 'quote_posts' ) ):
                    // Build complete quote collection using Helpers
                    $quote_data = Helpers::build_quote_collection(
                            $c->quote_posts->json(),
                            $c->quote_items->json(),
                            $c->random_quotes->isTrue(),
                            5
                    );
                    get_template_part( 'template-parts/post-parts/nok-scrollable-quote-block', null,
                            array(
                                    'class'         => $c->layout->is( 'accordion-left-title-top', 'nok-mt-5'),
                                    'quotes'      => $quote_data,
                                    'block_color' => $c->quote_block_colors->raw()
                            )
                    );
                endif; ?>

            </div>

            <?php if ( $c->has( 'accordion_items' ) ): ?>
                <div class="nok-layout-grid nok-layout-grid__1-column module-loaded <?= $accordion_column_class; ?>"
                     data-requires="./nok-accordion.mjs" data-require-lazy="true"
                     style="order:<?= $accordion_column_order; ?>">

                    <div class="<?= $c->accordion_block_colors->raw(); ?> nok-subtle-shadow nok-rounded-border-large nok-p-1"
                         style="<?= $c->accordion_framed->isTrue('', 'display:contents;' ); ?>">
                        <?php
                        $accordion_group = 'accordion-group';

                        $accordion_data = $c->accordion_items->json( [
                                [
                                        'title'       => 'Titel 1',
                                        'content'     => 'Tekst 1',
                                        'button_text' => 'Button tekst',
                                        'button_url'  => '#'
                                ],
                                [
                                        'title'       => 'Titel 2',
                                        'content'     => 'Tekst 2',
                                        'button_text' => 'Button tekst',
                                        'button_url'  => '#'
                                ]
                        ] );

                        foreach ( $accordion_data as $index => $item ) : ?>
                            <nok-accordion>
                                <details
                                        class="<?= $c->accordion_block_colors->raw() ?> nok-rounded-border nok-text-contrast"
                                        name="<?= esc_attr( $accordion_group ) ?>" <?= ( $index == 0 && $c->accordion_open_first->isTrue() ) ? 'open' : '' ?>>
                                    <summary class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold">
                                        <?= esc_html( $item['title'] ) ?>
                                    </summary>
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
                                </details>
                            </nok-accordion>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endif; ?>
        </article>
    </div>
</nok-section>