<?php
/**
 * Archive Template: Vestigingen
 *
 * Displays an archive of all clinic locations (vestigingen).
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

get_header('generic');

query_posts(array_merge($wp_query->query, ['posts_per_page' => -1]));
?>

	<nok-hero class="nok-section">
		<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">
			<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
				<?php Helpers::render_breadcrumbs(); ?>
				<h1 class="nok-fs-giant"><?php post_type_archive_title(); ?></h1>
                <p class="nok-intro-text">
                    <?php
                    $intro = NOK2025\V1\Theme::get_archive_intro(get_post_type(), '');
                    if ($intro) :
                        echo '<div class="archive-intro">' . wpautop($intro) . '</div>';
                    else : ?>
                    Bekijk hieronder alle vestigingen van de Nederlandse Obesitas Kliniek.
                    <?php endif; ?>
				</p>
			</header>
		</div>
	</nok-hero>

	<nok-section class="no-aos z-ascend">
		<div class="nok-section__inner nok-pull-up-2">
			<?php if (have_posts()): ?>
				<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
					<?php while (have_posts()): the_post();
						// Get vestiging meta fields
						$street        = get_post_meta(get_the_ID(), '_street', true);
						$housenumber   = get_post_meta(get_the_ID(), '_housenumber', true);
						$postal_code   = get_post_meta(get_the_ID(), '_postal_code', true);
						$city          = get_post_meta(get_the_ID(), '_city', true);
						$phone         = get_post_meta(get_the_ID(), '_phone', true);
						$opening_hours = get_post_meta(get_the_ID(), '_opening_hours', true);

						// Get featured image
						$featured_image     = Helpers::get_featured_image();
						$has_featured_image = has_post_thumbnail(get_the_ID()) && $featured_image !== '';
						?>

                        <nok-square-block class="nok-bg-white nok-dark-bg-darkestblue" data-shadow="true">
                            <?php if ($has_featured_image): ?>
                            <div class="nok-square-block__heading_image">
                                <figure class="nok-vestiging-card__image nok-aspect-16x9 nok-image-cover">
                                    <a href="<?php the_permalink(); ?>">
                                        <?= $featured_image; ?>
                                    </a>
                                </figure>
                            </div>
                            <?php endif; ?>

                            <h2 class="nok-square-block__heading">
                                <a href="<?php the_permalink(); ?>" class="nok-text-darkerblue nok-dark-text-white">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php if ($street && $city): ?>
                            <div class="nok-square-block__text">
                                <?= esc_html($street) ?> <?= esc_html($housenumber) ?><br>
                                <?= esc_html($postal_code) ?> <?= esc_html($city) ?>
                                <?php
                                $hours_summary = $opening_hours ? Helpers::format_opening_hours_summary($opening_hours) : '';
                                if ($hours_summary): ?>
                                <br><?= esc_html($hours_summary) ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="nok-layout-flex nok-layout-flex-row nok-column-gap-0_5">
                                <?php if ($phone): ?>
                                    <a href="tel:<?= esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="nok-button nok-button--circle nok-bg-body nok-text-darkerblue">
                                        <?= Assets::getIcon('ui_telefoon') ;?>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php the_permalink(); ?>" class="nok-button nok-button--circle nok-bg-body nok-text-darkerblue">
                                    <?= Assets::getIcon('ui_calendar'); ?>
                                </a>
                                <a href="<?php the_permalink(); ?>" class="nok-button nok-bg-darkblue nok-text-white">
                                    <?php esc_html_e('Meer informatie', THEME_TEXT_DOMAIN); ?>
                                    <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                                </a>
                            </div>
                        </nok-square-block>

					<?php endwhile; ?>
				</div>

				<?php
				// Pagination
				the_posts_pagination(array(
					'class'              => 'nok-navigation-pagination',
					'mid_size'           => 2,
					'prev_text'          => Assets::getIcon('ui_arrow-left') . ' ' . __('<span class="nok-invisible-to-sm">Vorige</span>', THEME_TEXT_DOMAIN),
					'next_text'          => __('<span class="nok-invisible-to-sm">Volgende</span>', THEME_TEXT_DOMAIN) . ' ' . Assets::getIcon('ui_arrow-right'),
					'screen_reader_text' => __('Paginanavigatie', THEME_TEXT_DOMAIN),
				));
				?>

			<?php else: ?>
				<p><?php esc_html_e('Geen vestigingen gevonden.', THEME_TEXT_DOMAIN); ?></p>
			<?php endif; ?>
		</div>
	</nok-section>

<?php
get_footer();
