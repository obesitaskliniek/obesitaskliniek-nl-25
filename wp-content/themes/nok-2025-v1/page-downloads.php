<?php
/* Template Name: Downloads */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

get_header();

$attachments = Helpers::get_all_non_image_attachments();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

	<nok-section class="nok-bg-darkerblue nok-text-contrast">
		<div class="nok-section__inner">
			<article class="nok-layout-grid nok-layout-grid__1-column nok-align-items-start">
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
							<div class="nok-download-item">
								<span class="nok-download-item__icon">
									<?= Assets::getIcon( 'ui_download' ) ?>
								</span>
								<span class="nok-download-item__info">
									<a href="<?= esc_url( $file['url'] ) ?>"
									   class="nok-download-item__title"
									   download><?= esc_html( $file['title'] ) ?></a>
									<span class="nok-download-item__meta">
										<?= esc_html( $file['filetype'] ) ?>
										<?php if ( $file['filesize'] ) : ?>
											· <?= esc_html( $file['filesize'] ) ?>
										<?php endif; ?>
									</span>
								</span>
								<a href="<?= esc_url( $file['url'] ) ?>"
								   class="nok-download-item__action"
								   download
								   aria-label="<?= esc_attr( sprintf( '%s downloaden', $file['title'] ) ) ?>">
									<?= Assets::getIcon( 'ui_arrow-down' ) ?>
								</a>
								<?php if ( $file['parent_url'] && $file['parent_title'] ) : ?>
									<span class="nok-download-item__source">
										<a href="<?= esc_url( $file['parent_url'] ) ?>">Meer informatie</a>
									</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p>Er zijn momenteel geen downloads beschikbaar.</p>
				<?php endif; ?>
			</article>
		</div>
	</nok-section>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
