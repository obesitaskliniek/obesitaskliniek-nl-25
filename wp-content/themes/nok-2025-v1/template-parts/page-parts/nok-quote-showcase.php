<?php
/**
 * Template Name: Quote Showcase
 * Description: Two-column layout with quote carousel and accordion items
 * Slug: nok-quote-showcase
 * Custom Fields:
 * - layout:select(left|right|accordion-left)!page-editable!default(left)
 * - colors:select(Transparant::nok-bg-body|Grijs::nok-bg-body--darker gradient-background|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(nok-bg-body)
 * - block_colors:select(Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast)
 * - quote_block_colors:select(Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast)
 * - quote_items:repeater(quote:text,name:text,subname:text)
 * - quote_posts:post_repeater(post:ervaringen)
 * - random_quotes:checkbox(false)
 * - accordion_open_first:checkbox!default(true)
 * - accordion_items:repeater(title:text,content:textarea,button_text:text,button_url:url)
 * - accordion_button_text:text!default(Lees meer)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

$left = $c->layout->is( 'left' );

function get_quotes_from_posts( $posts ) {
    $quote_items = array();
    foreach ( $posts as $post_id ) {
        // Get the post meta
        $post       = get_post( $post_id );
        $post_meta  = get_post_meta( $post_id );
        $quote_item = array(
                'quote'     => isset( $post_meta['_highlighted_quote_1'] ) ? ( ( rand( 0, 1 ) || ! isset( $post_meta['_highlighted_quote_2'] ) ) ? $post_meta['_highlighted_quote_1'][0] : $post_meta['_highlighted_quote_2'][0] ) : get_the_title( $post ),
                 //todo: random quote selection should be excluded from cache, if possible
                'excerpt'   => isset($post_meta['_highlighted_excerpt']) ? rtrim($post_meta['_highlighted_excerpt'][0], '.') . '...' : Helpers::get_excerpt( $post_id, 30 ),
                'name'      => $post_meta['_naam_patient'][0] ?? 'Anonieme patiënt',
                'subnaam'   => $post_meta['_subnaam_patient'][0] ?? '',
                'link_url'  => get_permalink( $post_id ),
                'image_url' => Helpers::get_featured_image_uri( $post )
        );
        array_push( $quote_items, $quote_item );
    }

    return $quote_items;
}

?>

<nok-section class="<?= $c->colors ?> gradient-background">
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
            <div class="nok-layout-flex-column nok-align-items-stretch" style="order:<?= $left ? '1' : '2' ?>">
                <?php
                if ( ! $c->layout->is( 'accordion-left' ) ) {
                    the_title( '<h1>', '</h1>' );
                }
                ?>
                <div><?php the_content(); ?></div>

                <?php if ( $c->has( 'quote_items' ) || $c->has( 'quote_posts' ) ):
                    $quote_posts = get_quotes_from_posts( $c->quote_posts->json() );
                    $qoute_items = $c->quote_items->json();
                    $quote_data  = array_merge( $quote_posts, $qoute_items );
                    if ( $c->has( 'quote_items' ) ) {
                        shuffle( $quote_data );
                    } ?>

                    <?php get_template_part( 'template-parts/post-parts/nok-scrollable-quote-block', null,
                        array(
                                'quotes'      => $quote_data,
                                'block_color' => $c->quote_block_colors
                        )
                ) ?>
                <?php endif; ?>

            </div>

            <?php if ( $c->has( 'accordion_items' ) ): ?>
                <!-- Component: accordion items -->
                <div class="nok-layout-grid nok-layout-grid__1-column"
                     data-requires="./nok-accordion.mjs" data-require-lazy="true"
                     style="order:<?= $left ? '2' : '1' ?>">
                    <?php
                    if ( $c->layout->is( 'accordion-left' ) ) {
                        the_title( '<h1>', '</h1>' );
                    }
                    ?>

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
                            <details class="<?= $c->block_colors ?> nok-rounded-border nok-text-contrast"
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

            <?php endif; ?>
        </article>
    </div>
</nok-section>