<?php
/* Template Name: Downloads */

use NOK2025\V1\Helpers;

get_header('generic');

$attachments = Helpers::get_all_non_image_attachments();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

	<nok-section class="nok-bg-darkerblue nok-text-contrast">
		<div class="nok-section__inner">
			<div class="nok-layout-grid nok-layout-grid__1-column nok-align-items-start">
				<?php Helpers::render_breadcrumbs(); ?>

				<?php the_title( '<h1 class="nok-fs-6">', '</h1>' ); ?>

				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="nok-layout-grid nok-layout-grid__1-column">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $attachments ) ) : ?>
					<div class="nok-downloads-list nok-downloads-list--with-sources">
						<?php foreach ( $attachments as $file ) : ?>
							<?= Helpers::render_download_item( $file, true ) ?>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p>Er zijn momenteel geen downloads beschikbaar.</p>
				<?php endif; ?>
			</div>
		</div>
	</nok-section>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
