<?php
/**
 * Content template for Regio posts
 *
 * Similar layout to vestiging content but without own address details.
 * Shows parent vestiging reference in sidebar instead.
 * Included by content-placeholder-nok-template block or as fallback.
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

// Get parent vestiging
$parent_vestiging_id = get_post_meta( get_the_ID(), '_parent_vestiging', true );
$parent_vestiging    = $parent_vestiging_id ? get_post( $parent_vestiging_id ) : null;

// Get parent vestiging meta for sidebar and voorlichtingen
$parent_street     = '';
$parent_housenumber = '';
$parent_postal_code = '';
$parent_city       = '';
$parent_phone      = '';
$parent_email      = '';
$vestiging_city    = '';

if ( $parent_vestiging ) {
	$parent_street      = get_post_meta( $parent_vestiging->ID, '_street', true );
	$parent_housenumber = get_post_meta( $parent_vestiging->ID, '_housenumber', true );
	$parent_postal_code = get_post_meta( $parent_vestiging->ID, '_postal_code', true );
	$parent_city        = get_post_meta( $parent_vestiging->ID, '_city', true );
	$parent_phone       = get_post_meta( $parent_vestiging->ID, '_phone', true );
	$parent_email       = get_post_meta( $parent_vestiging->ID, '_email', true );

	// Extract city from vestiging title for voorlichting matching (e.g., "NOK Beverwijk" -> "Beverwijk")
	$vestiging_city = preg_replace( '/^NOK\s+/i', '', $parent_vestiging->post_title );
}

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
            nok-layout-grid nok-layout-grid nok-columns-6
            nok-grid-gap-section-padding nok-mt-0">
            <article class="nok-order-1 nok-order-xl-1
            nok-column-first-6 nok-column-first-xl-3 nok-column-first-xxl-4
            nok-layout-flex-column
            nok-align-items-start text-start">
                <?php Helpers::the_content_rest(); ?>
            </article>
            <?php if ( $parent_vestiging ) : ?>
            <aside class="nok-order-0 nok-order-xl-2
            nok-span-all-columns nok-column-last-xl-3 nok-column-last-xxl-2
            nok-grid-gap-1 nok-pull-up-xl-3
            nok-align-self-start
            nok-layout-grid nok-columns-1 nok-columns-lg-2 nok-columns-xl-1">
                <nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-alpha-10"
                                  data-shadow="true">
                    <div class="nok-square-block__heading">
                        <h2>Dichtstbijzijnde vestiging</h2>
                    </div>
                    <div class="nok-square-block__text nok-fs-1" style="--flex-gap: 0.5rem;">
                        <p class="nok-fw-bold"><?= esc_html( $parent_vestiging->post_title ); ?></p>
                        <address>
                            <?php if ( $parent_street ) : ?>
                            <span class="nok-layout-flex-row street"><?= esc_html( $parent_street ) ?> <?= esc_html( $parent_housenumber ) ?></span>
                            <span class="nok-layout-flex-row postal-code"><?= esc_html( $parent_postal_code ) ?> <?= esc_html( $parent_city ) ?></span>
                            <?php endif; ?>
                            <?php if ( $parent_phone ) : ?>
                            <span class="nok-layout-flex-row phone"><?= Assets::getIcon('ui_telefoon') ;?> <a href="tel:<?= esc_html( $parent_phone ) ?>" class="nok-hyperlink"><?= $parent_phone; ?></a></span>
                            <?php endif; ?>
                            <?php if ( $parent_email ) : ?>
                            <span class="nok-layout-flex-row email"><?= Assets::getIcon('ui_email') ;?> <a href="mailto:<?= esc_attr( $parent_email ); ?>" class="nok-hyperlink"><?= esc_html( $parent_email ); ?></a></span>
                            <?php endif; ?>
                        </address>
                    </div>
                    <a href="<?= esc_url( get_permalink( $parent_vestiging->ID ) ); ?>" role="button" class="nok-button nok-justify-self-stretch nok-bg-darkblue nok-text-contrast" tabindex="0">
                        Bekijk vestiging <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                    </a>
                </nok-square-block>
            </aside>
            <?php endif; ?>
        </div>
    </nok-section>

<?php
// Voorlichtingen carousel - shows upcoming sessions for parent vestiging
if ( ! empty( $vestiging_city ) ) {
	get_template_part( 'template-parts/post-parts/nok-vestiging-voorlichtingen', null, [
		'city' => $vestiging_city,
	] );
}
?>
