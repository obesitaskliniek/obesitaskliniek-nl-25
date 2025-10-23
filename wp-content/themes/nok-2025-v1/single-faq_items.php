<?php
/* Template Name: Event */

get_header( 'faq_items' );

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();

function nok_cat_slug_to_icon($slug) {
    switch($slug) {
        case 'algemeen' :
            return 'ui_question';
        case 'medisch' :
            return 'nok_leefstijl';
        case 'naasten' :
            return 'nok_sociaal';
        case 'afvallen' :
            return 'nok_weegschaal';
        default:
            break;
    }
}

function nok_get_categories($as_title = false) : string {
    $terms = get_the_terms( get_the_ID(), 'faq_categories' );
    if ( !$terms || is_wp_error( $terms ) ) {
        return '';
    }

    if ($as_title) {
        $prefix = count( $terms ) === 1 ? 'Categorie: ' : 'Categorieën: ';
        return $prefix . implode(', ', wp_list_pluck($terms, 'name'));
    }

    return Assets::getIcon( nok_cat_slug_to_icon($terms[0]->slug ) ) . ' '
           . get_the_term_list( get_the_ID(), 'faq_categories', '', ', ' );
}

?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner
        nok-layout-grid nok-layout-grid__3-column fill-one
        nok-m-0 nok-border-radius-to-sm-0
        nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white
        nok-bg-alpha-10 nok-dark-bg-alpha-10 nok-subtle-shadow">
            <div class="article">
                <h2 class="nok-fs-2 nok-fs-to-md-1" title="<?= nok_get_categories(true); ?>">
                    <?= nok_get_categories(); ?>
                </h2>
                <?php the_title( '<h1 class="nok-fs-6">', '</h1>' ); ?>
            </div>

        </div>
    </nok-hero>

    <nok-section class="no-aos">
        <div class="nok-section__inner
        nok-layout-grid nok-layout-grid__3-column fill-one nok-grid-gap-section-padding">
            <article class="baseline-grid" title="Vraag: <?= get_the_title(); ?>" data-requires="./domule/modules/hnl.baseline-grid.mjs">
                <?php the_content(); ?>
            </article>
            <aside class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-grid-gap-1">
                <nok-square-block class="nok-bg-white nok-alpha-10 nok-pull-up-lg-3" data-shadow="true">
                    <div class="nok-square-block__text nok-fs-1">
                        Is je vraag niet beantwoord of wil je meer informatie over een bepaald
                        onderwerp?
                    </div>
                    <a role="button" href="" class="nok-button nok-justify-self-start w-100
                 nok-bg-darkblue nok-text-contrast" tabindex="0">
                        Bekijk alle vragen <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
                    </a>
                    <a role="button" href="" class="nok-button nok-justify-self-start w-100
                 nok-bg-yellow nok-text-contrast" tabindex="0">
                        Neem contact op <?= Assets::getIcon( 'ui_telefoon', 'nok-text-darkblue' ) ?>
                    </a>
                </nok-square-block>
            </aside>
        </div>
    </nok-section>

<?php
$faq_items = yarpp_get_related( array(), get_the_ID() );
if ( $faq_items ) :
    $accordion_id = 'gerelateerde-vragen-' . sanitize_title( get_the_title() );
    ?>
    <nok-section class="nok-bg-body--darker gradient-background nok-text-darkblue">
        <div class="nok-section__inner">
            <h2 class="nok-fs-5"><?= __( 'Gerelateerde vragen', 'yet-another-related-posts-plugin' ); ?></h2>
            <div class="nok-layout-grid nok-layout-grid__1-column nok-mt-1" data-requires="./nok-accordion.mjs"
                 data-require-lazy="true">
                <?php
                foreach ( $faq_items as $post ) {

                    $post_id       = $post->ID;
                    $item_title    = get_the_title( $post_id );
                    $items_cats    = get_the_terms( $post_id, 'faq_categories' );
                    $item_category = $items_cats[0]->name;
                    $cat_slug      = $items_cats[0]->slug;
                    $item_id       = $cat_slug . '-' . $post_id; ?>
                    <nok-accordion>
                        <details class="nok-bg-body nok-dark-bg-darkblue nok-dark-text-contrast nok-rounded-border"
                                 name="<?= $accordion_id; ?>">
                            <summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-lg-3 fw-bold">
                                <?= $item_title; ?>
                            </summary>
                            <div class="accordion-content nok-p-2 nok-pt-0">
                                <article title="<?= $item_title; ?>">
                                    <?php the_excerpt(); ?>
                                </article>
                                <footer class="nok-mt-1">
                                    <a role="button" href="<?= get_the_permalink( $post_id ); ?>"
                                       class="nok-button nok-button--small nok-justify-self-start nok-bg-darkblue nok-text-contrast"
                                       tabindex="0">Lees meer <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
                                    </a>
                                    <a role="button" href="/veelgestelde-vragen"
                                       class="nok-button nok-button--small nok-justify-self-start nok-bg-yellow nok-text-contrast"
                                       tabindex="0"
                                       title="Niet gevonden wat je zocht? Bekijk hier alle vragen."><?= Assets::getIcon( 'ui_question' ); ?> Alle vragen <?= Assets::getIcon( 'ui_arrow-right-long' ) ?>
                                    </a>
                                </footer>
                            </div>
                        </details>
                    </nok-accordion>
                <?php }
                wp_reset_postdata(); ?>
            </div>
        </div>
    </nok-section>
<?php endif; ?>

<?php
get_footer();