<?php
/**
 * Content template for Vestiging posts
 * Included by content-placeholder-nok-template block
 */

use NOK2025\V1\Helpers;

$post_meta      = get_post_meta( get_the_ID() );
$street         = $post_meta['_street'][0] ?? '';
$housenumber    = $post_meta['_housenumber'][0] ?? '';
$postal_code    = $post_meta['_postal_code'][0] ?? '';
$city           = $post_meta['_city'][0] ?? '';
$phone          = $post_meta['_phone'][0] ?? '';
$email          = $post_meta['_email'][0] ?? '';
$opening_hours  = $post_meta['_opening_hours'][0] ?? '';

$featured_image     = Helpers::get_featured_image();
$blur_image         = Helpers::get_featured_image('nok-image-cover-blur-ghost');
$has_featured_image = has_post_thumbnail( get_the_ID() ) && $featured_image !== '';

if ( $has_featured_image ) {
	$heading_article_class = 'nok-mb-double-section-padding';
	$article_class         = '';
} else {
	$heading_article_class = 'nok-mb-0';
	$article_class         = 'nok-mt-0';
}
?>
	<nok-hero class="nok-section">
		<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

			<header class="nok-section__inner nok-section-narrow nok-mt-0 <?= $heading_article_class; ?>">

				<?php Helpers::render_breadcrumbs(); ?>

				<?php the_title( '<h1 class="nok-fs-giant">', '</h1>' ); ?>

				<?php if ( $street && $city ) : ?>
					<div class="nok-fs-body nok-mt-1">
						<p>
							<strong>Adres:</strong><br>
							<?= esc_html( $street ) ?> <?= esc_html( $housenumber ) ?><br>
							<?= esc_html( $postal_code ) ?> <?= esc_html( $city ) ?>
						</p>
					</div>
				<?php endif; ?>
			</header>
		</div>
	</nok-hero>

	<nok-section class="z-ascend no-aos">
		<article class="nok-section__inner nok-section-narrow nok-text-darkerblue <?= $article_class; ?>">
			<?php if ( $has_featured_image ) : ?>
				<figure class="nok-pull-up-4 nok-mb-section-padding nok-image-cover-blur nok-rounded-border-large nok-subtle-shadow nok-aos nok-aspect-16x9">
					<?= $blur_image; ?>
					<?= $featured_image; ?>
				</figure>
			<?php endif; ?>

			<article class="narrow-paragraphs">
				<?php the_content();?>
			</article>

			<?php if ( $phone || $email || $opening_hours ) : ?>
				<div class="nok-mt-section-padding">
					<h2 class="nok-fs-3">Contact &amp; Openingstijden</h2>

					<?php if ( $phone ) : ?>
						<p>
							<strong>Telefoon:</strong><br>
							<a href="tel:<?= esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?= esc_html( Helpers::format_phone( $phone ) ); ?></a>
						</p>
					<?php endif; ?>

					<?php if ( $email ) : ?>
						<p>
							<strong>E-mail:</strong><br>
							<a href="mailto:<?= esc_attr( $email ); ?>"><?= esc_html( $email ); ?></a>
						</p>
					<?php endif; ?>

					<?php if ( $opening_hours ) : ?>
						<div>
							<strong>Openingstijden:</strong>
							<div class="nok-mt-1">
								<?= Helpers::format_opening_hours( $opening_hours ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</article>
	</nok-section>
