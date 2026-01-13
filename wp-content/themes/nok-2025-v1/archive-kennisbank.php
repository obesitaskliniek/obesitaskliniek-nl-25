<?php
/**
 * Archive Template: Kennisbank
 *
 * Displays kennisbank posts in a grid layout with article cards.
 * Used for both post type archive (/kennisbank/) and taxonomy archives (/kennisbank/{category}/).
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

get_header('generic');

// Modify query for pagination
query_posts(array_merge($wp_query->query, ['posts_per_page' => 12]));

// Determine if we're on a taxonomy archive
$is_taxonomy_archive = is_tax('kennisbank_categories');
$current_term = $is_taxonomy_archive ? get_queried_object() : null;
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white nok-bg-alpha-10 nok-dark-bg-alpha-10">
		<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
			<?php Helpers::render_breadcrumbs(); ?>

			<?php if ($is_taxonomy_archive && $current_term): ?>
				<h1 class="nok-fs-giant"><?= esc_html($current_term->name); ?></h1>
				<?php if ($current_term->description): ?>
					<p class="nok-intro-text"><?= wp_kses_post($current_term->description); ?></p>
				<?php endif; ?>
			<?php else: ?>
				<h1 class="nok-fs-giant"><?php post_type_archive_title(); ?></h1>
				<?php
				$intro = NOK2025\V1\Theme::get_archive_intro('kennisbank', '');
				if ($intro):
					echo '<p class="nok-intro-text">' . wp_kses_post($intro) . '</p>';
				endif;
				?>
			<?php endif; ?>

            <?php
            // Category pill navigation
            $all_categories = get_terms([
                    'taxonomy'   => 'kennisbank_categories',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
            ]);

            if ($all_categories && !is_wp_error($all_categories)):
                $archive_url = get_post_type_archive_link('kennisbank');
                $current_slug = $current_term ? $current_term->slug : null;
                ?>
                <nav class="nok-category-pills nok-mb-2" aria-label="<?php esc_attr_e('Filter op categorie', THEME_TEXT_DOMAIN); ?>">
                    <a href="<?= esc_url($archive_url); ?>"
                       class="nok-pill <?= !$is_taxonomy_archive ? 'nok-pill--active' : ''; ?>">
                        Alles
                    </a>
                    <?php foreach ($all_categories as $category): ?>
                        <a href="<?= esc_url(get_term_link($category)); ?>"
                           class="nok-pill <?= $current_slug === $category->slug ? 'nok-pill--active' : ''; ?>">
                            <?= esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
		</header>
	</div>
</nok-hero>

<nok-section class="no-aos z-ascend">
	<div class="nok-section__inner nok-pull-up-2">

		<?php if (have_posts()): ?>
			<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
				<?php while (have_posts()): the_post();
					$post_id = get_the_ID();
					$permalink = get_the_permalink();
					$title = get_the_title();
					$excerpt = get_the_excerpt();
					$date = get_the_date('d-m-y');

					// Get featured image
					$image_url = Helpers::get_featured_image_uri($post_id, 'medium_large');
					$has_image = has_post_thumbnail($post_id);

					// Get primary category
					$categories = get_the_terms($post_id, 'kennisbank_categories');
					$primary_category = $categories && !is_wp_error($categories) ? $categories[0] : null;
					?>

					<article class="nok-square-block link-bottom nok-bg-white nok-text-darkblue" data-shadow="true">
						<!-- Featured image with badge overlays -->
						<figure class="nok-square-block__image">
							<?php if ($has_image): ?>
								<img src="<?= esc_url($image_url); ?>"
								     alt="<?= esc_attr($title); ?>"
								     loading="lazy"
								     decoding="async">
							<?php else: ?>
								<div class="nok-square-block__image--placeholder">
									<div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
										<?= Assets::getIcon('ui_question'); ?>
									</div>
								</div>
							<?php endif; ?>

							<div class="nok-square-block__badges">
								<?php if ($primary_category): ?>
									<span class="nok-badge nok-bg-darkerblue nok-text-white">
										<?= esc_html($primary_category->name); ?>
									</span>
								<?php endif; ?>
								<span class="nok-badge nok-bg-white nok-text-darkerblue">
									<?= esc_html($date); ?>
								</span>
							</div>
						</figure>

						<!-- Card content -->
						<h2 class="nok-square-block__heading nok-fs-3">
							<?= esc_html($title); ?>
						</h2>

						<p class="nok-square-block__text nok-fs-1">
							<?= esc_html(wp_trim_words($excerpt, 20, '...')); ?>
						</p>

						<a href="<?= esc_url($permalink); ?>"
						   class="nok-square-block__link"
						   title="<?= esc_attr($title); ?>">
							LEES MEER
							<?= Assets::getIcon('ui_arrow-right-long'); ?>
						</a>
					</article>

				<?php endwhile; ?>
			</div>

			<?php
			// Pagination
			the_posts_pagination([
				'class'              => 'nok-navigation-pagination',
				'mid_size'           => 2,
				'prev_text'          => Assets::getIcon('ui_arrow-left') . ' ' . __('<span class="nok-invisible-to-sm">Vorige</span>', THEME_TEXT_DOMAIN),
				'next_text'          => __('<span class="nok-invisible-to-sm">Volgende</span>', THEME_TEXT_DOMAIN) . ' ' . Assets::getIcon('ui_arrow-right'),
				'screen_reader_text' => __('Paginanavigatie', THEME_TEXT_DOMAIN),
			]);
			?>

		<?php else: ?>
			<p><?php esc_html_e('Geen artikelen gevonden.', THEME_TEXT_DOMAIN); ?></p>
		<?php endif; ?>
	</div>
</nok-section>

<?php
wp_reset_query();
get_footer();
