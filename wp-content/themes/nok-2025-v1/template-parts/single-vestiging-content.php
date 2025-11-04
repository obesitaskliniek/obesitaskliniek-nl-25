<?php
/**
 * Content template for Vestiging posts
 * Included by content-placeholder-nok-template block
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$post_meta      = get_post_meta( get_the_ID() );
$street         = $post_meta['_street'][0] ?? '';
$housenumber    = $post_meta['_housenumber'][0] ?? '';
$postal_code    = $post_meta['_postal_code'][0] ?? '';
$city           = $post_meta['_city'][0] ?? '';
$phone          = $post_meta['_phone'][0] ?? '';
$email          = $post_meta['_email'][0] ?? '';
$opening_hours  = $post_meta['_opening_hours'][0] ?? '';

$featured_image     = Helpers::get_featured_image_uri( $post );
$has_featured_image = has_post_thumbnail( get_the_ID() ) && $featured_image !== '';

?>
    <nok-hero class="nok-section">
        <div class="nok-section__inner
        nok-layout-grid nok-layout-grid__4-column fill-fill
        nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
    nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10 nok-faded-background" style="--bg-image:url('<?= $featured_image; ?>');">

            <header class="nok-section__inner nok-section-narrow nok-mt-0">

                <?php Helpers::render_breadcrumbs(); ?>

                <?php the_title( '<h1 class="nok-fs-giant">', '</h1>' ); ?>

                <div>
                    <?php Helpers::the_content_first_paragraph(); ?>
                </div>
            </header>
        </div>
    </nok-hero>

    <nok-section class="no-aos z-ascend">
        <div class="nok-section__inner
            nok-layout-grid nok-layout-grid__3-column fill-one
            nok-grid-gap-section-padding nok-mt-0">
            <article>
                <?php Helpers::the_content_rest(); ?>
            </article>
            <aside class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-grid-gap-1 nok-pull-up-lg-3
            nok-layout-flex-column nok-align-items-stretch">
                <nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-alpha-10"
                                  data-shadow="true">
                    <div class="nok-square-block__heading">
                        <?php printf( '<h2>Adresgegevens %s</h2>', get_the_title()); ?>
                    </div>
                    <div class="nok-square-block__text nok-fs-1" style="--flex-gap: 0.5rem;">
                        <address>
                            <span class="nok-layout-flex-row street" id="street"><?= esc_html( $street ) ?> <?= esc_html( $housenumber ) ?></span>
                            <span class="nok-layout-flex-row postal-code" id="zipcode"><?= esc_html( $postal_code ) ?> <?= esc_html( $city ) ?></span>
                            <span class="nok-layout-flex-row phone" id="phone"><?= Assets::getIcon('ui_telefoon') ;?> <a href="tel:<?= esc_html( $phone ) ?>" class="nok-hyperlink"><?= $phone; ?></a></span>
                            <span class="nok-layout-flex-row email" id="email"><?= Assets::getIcon('ui_email') ;?> <a href="mailto:<?= esc_attr( $email ); ?>" class="nok-hyperlink"><?= esc_html( $email ); ?></a></span>
                        </address>
                    </div>
                </nok-square-block>

                <?php if ( $opening_hours ) : ?>
                <nok-square-block class="nok-bg-body nok-text-contrast nok-alpha-10"
                                  data-shadow="true">
                    <div class="nok-square-block__heading">
                        <?php printf( '<h2>Openingstijden</h2>', get_the_title()); ?>
                    </div>
                    <div class="nok-square-block__text">
                        <?= Helpers::format_opening_hours( $opening_hours ); ?>
                    </div>
                </nok-square-block>
                <?php endif; ?>
            </aside>
        </div>
    </nok-section>
