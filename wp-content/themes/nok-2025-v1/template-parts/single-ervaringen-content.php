<?php
/**
 * Content template for Ervaringen category posts
 * Included by content-placeholder-nok-template block
 */

use NOK2025\V1\Helpers;

$post_meta           = get_post_meta( get_the_ID() );
$naam_patient        = $post_meta['_naam_patient'][0] ?? '';
$subnaam_patient     = $post_meta['_subnaam_patient'][0] ?? '';

$featured_image     = Helpers::get_featured_image();
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
				<h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading">
SINGLE-ERVARINGEN-CONTENT
				</h2>
				<?php the_title( '<h1 class="nok-fs-giant">', '</h1>' ); ?>
				<div>
					<?php Helpers::the_content_first_paragraph(); ?>
				</div>
			</header>
		</div>
	</nok-hero>

	<nok-section class="z-ascend no-aos">
		<article class="nok-section__inner nok-section-narrow nok-text-darkerblue <?= $article_class; ?>">
			<?php if ( $has_featured_image ) : ?>
				<figure class="nok-pull-up-4 nok-mb-section-padding natural-fit-image nok-rounded-border-large nok-subtle-shadow nok-aos">
					<?= $featured_image; ?>
				</figure>
			<?php endif; ?>
			<article>
				<?php Helpers::the_content_rest(); ?>
			</article>
		</article>
	</nok-section>