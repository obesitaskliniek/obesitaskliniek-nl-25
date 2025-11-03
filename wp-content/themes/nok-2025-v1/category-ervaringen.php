<?php
/**
 * Category Archive Template: Ervaringen
 *
 * Displays an archive of patient experience posts from the 'ervaringen' category.
 * Uses alternating layout pattern matching the provided design.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

get_header('generic');
?>

	<nok-hero class="nok-section">
		<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">
			<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
                <?php Helpers::render_breadcrumbs(); ?>
				<h1 class="nok-fs-giant"><?php single_cat_title(); ?></h1>
				<?php if (category_description()): ?>
					<div class="nok-category-description">
						<?php echo category_description(); ?>
					</div>
				<?php else: ?>
					<p class="nok-intro-text">
						Hier is zijn plek voor onze korte introductietekst die de verdeling van de Nederlandse Obesitas Kliniek. Lorem ipsum dolor sit amet.
					</p>
				<?php endif; ?>
			</header>
		</div>
	</nok-hero>

	<nok-section class="no-aos z-ascend">
		<div class="nok-section__inner nok-pull-up-2">
			<?php if (have_posts()): ?>
				<div class="nok-ervaringen-archive">
					<?php
					$post_index = 0;
					while (have_posts()): the_post();
						$post_index++;
						$is_odd = $post_index % 2 !== 0;

						// Get custom fields
						$naam_patient        = get_post_meta(get_the_ID(), '_naam_patient', true);
						$subnaam_patient     = get_post_meta(get_the_ID(), '_subnaam_patient', true);
						$highlighted_excerpt = get_post_meta(get_the_ID(), '_highlighted_excerpt', true);

						// Get featured image
						$featured_image     = Helpers::get_featured_image();
						$has_featured_image = has_post_thumbnail(get_the_ID()) && $featured_image !== '';

						// Get categories for badges
						$categories = get_the_category();

						if ( isset( $post_meta['_highlighted_excerpt'] ) ) {
							$excerpt = Helpers::strip_all_quotes( rtrim( $post_meta['_highlighted_excerpt'][0], '.' ) ) . '...';
						} else {
							$excerpt = Helpers::get_excerpt( get_the_ID(), 30 );
						}
						?>

						<article class="nok-layout-grid fill-fill nok-mb-section-padding nok-grid-gap-0 nok-rounded-border-large">

							<div class="nok-aspect-to-lg-8x5 nok-order-lg-<?= $is_odd ? '0' : '1'; ?> nok-order-1 <?= $is_odd ? 'nok-bg-darkerblue nok-text-contrast' : 'nok-bg-darkerblue nok-bg-alpha-1 nok-dark-darkblue nok-dark-bg-alpha-4 nok-text-darkerblue'; ?> nok-align-content-center">
								<nok-square-block class="nok-alpha-10 nok-p-3 nok-p-lg-4 nok-order-0 nok-border-radius-0 nok-dark-text-contrast" data-shadow="false">
									<div class="nok-square-block__text">
										<blockquote class="nok-quote nok-fs-3">
											<div class="nok-quote__icon lifted"><?= Assets::getIcon('ui_quote'); ?></div>
											<p class="nok-quote__text"><?php echo esc_html($excerpt); ?></p>
										</blockquote>
									</div>
									<a role="button" href="<?php the_permalink(); ?>" class="nok-button nok-bg-darkblue nok-text-contrast nok-justify-self-start"
									   title="Lees het hele verhaal van <?= esc_attr( $naam_patient ?? 'deze patiënt' ) ?>"
									   tabindex="0">
										<?php esc_html_e('Lees het verhaal', THEME_TEXT_DOMAIN); ?>
										<?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ); ?>
									</a>
								</nok-square-block>
							</div>
							<div class="nok-aspect-to-lg-8x5 nok-order-lg-<?= $is_odd ? '1' : '0'; ?> nok-order-0 nok-layout-grid nok-layout-grid__1-column">

									<div class="nok-p-3 nok-p-lg-4 nok-text-white nok-align-self-end first-row-first-column nok-z-2 nok-pos-rel" style="
									background-image: linear-gradient(transparent 0%, rgba(var(--nok-darkestblue-rgb), 0.5) 50%);
									text-shadow: 0.2em 0.2em 0.4em rgba(var(--nok-darkestblue-rgb), 0.3);">
                                        <?= $naam_patient ? $naam_patient . ' -' : '' ;?>
										<time datetime="<?php echo get_the_date('c'); ?>">
											<?php echo get_the_date('j F Y'); ?>
										</time>
										<h2 class="nok-fs-6"><?php the_title(); ?></h2>
									</div>
									<div class="nok-ervaringen-item__image nok-bg-darkestblue nok-bg-alpha-5 first-row-first-column nok-z-1 nok-pos-rel">
										<a href="<?php the_permalink(); ?>">
											<figure class="nok-image-cover nok-fill-parent">
												<?php echo $featured_image; ?>
											</figure>
										</a>
									</div>

							</div>
						</article>

					<?php endwhile; ?>
				</div>

				<?php
                // Pagination
                the_posts_pagination( array(
                        'class'              => 'nok-navigation-pagination',
                        'mid_size'           => 2,
                        'prev_text'          => Assets::getIcon( 'ui_arrow-left' ) . ' ' . __( '<span class="nok-invisible-to-sm">Vorige</span>', THEME_TEXT_DOMAIN ),
                        'next_text'          => __( '<span class="nok-invisible-to-sm">Volgende</span>', THEME_TEXT_DOMAIN ) . ' ' . Assets::getIcon( 'ui_arrow-right' ),
                        'screen_reader_text' => __( 'Paginanavigatie', THEME_TEXT_DOMAIN ),
                ) );
                ?>

			<?php else: ?>
				<p><?php esc_html_e('Geen ervaringen gevonden.', THEME_TEXT_DOMAIN); ?></p>
			<?php endif; ?>
		</div>
	</nok-section>

<?php
get_footer();