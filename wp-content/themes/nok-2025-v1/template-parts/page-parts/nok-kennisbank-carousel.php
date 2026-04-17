<?php
/**
 * Template Name: Kennisbank Carousel
 * Description: Titel, intro en scrollbare carrousel van kennisbankartikelen met uitgelichte afbeeldingen
 * Slug: nok-kennisbank-carousel
 * Custom Fields:
 * - colors:color-selector(section-colors)!page-editable!default(nok-bg-body)
 * - card_colors:color-selector(card-colors)!page-editable!default(nok-bg-white nok-text-darkblue)
 * - badge_colors:color-selector(badge-colors)!page-editable!default(nok-bg-darkerblue nok-text-white)
 * - shuffle:checkbox!default(false)!descr[Willekeurige volgorde?]!page-editable
 * - show_all_link:checkbox!default(true)!descr[Toon "Alle items" link]
 * - all_link_url:link!default(/kennisbank)!descr[URL voor "Alle items" link]
 * - all_link_text:text!default(Alle items)
 * - show_nav_buttons:checkbox!default(true)!descr[Toon navigatieknoppen voor de carousel]
 * - category_filter:taxonomy(kennisbank_categories)!descr[Filter op categorieën (laat leeg voor alle)]!page-editable
 * - max_items:select(3|6|9|12)!default(6)!descr[Maximum aantal artikelen]
 * - read_more_text:text!default(Lees meer)
 * - show_date:checkbox!default(true)!descr[Toon publicatiedatum]
 * - show_category:checkbox!default(true)!descr[Toon categorie badge]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

// Build taxonomy query if category filter is set (comma-separated term IDs)
$tax_query = [];
$category_filter = trim($c->category_filter->raw());
if (!empty($category_filter)) {
	// Parse comma-separated term IDs
	$term_ids = array_map('intval', array_filter(explode(',', $category_filter)));
	if (!empty($term_ids)) {
		$tax_query = [
			[
				'taxonomy' => 'kennisbank_categories',
				'field'    => 'term_id',
				'terms'    => $term_ids,
				'operator' => 'IN',
			]
		];
	}
}

// Exclude current kennisbank post to avoid self-reference in carousel
// TemplateRenderer replaces $wp_query, so use $wp_the_query (the untouched original main query)
$exclude = [];
$main_query = $GLOBALS['wp_the_query'] ?? null;
if ($main_query && $main_query->is_singular('kennisbank')) {
	$exclude = [$main_query->get_queried_object_id()];
}
$query_args = [
	'post_type'      => 'kennisbank',
	'posts_per_page' => intval($c->max_items->raw()),
	'post_status'    => 'publish',
	'no_found_rows'  => true,
	'orderby'        => $c->shuffle->isTrue() ? 'rand' : 'date',
	'order'          => 'DESC',
];
if (!empty($tax_query)) {
	$query_args['tax_query'] = $tax_query;
}
$query = new \WP_Query($query_args);

// Exclude current kennisbank post from results
if (!empty($exclude) && $query) {
	$filtered = array_filter($query->posts, fn($p) => !in_array($p->ID, $exclude));
	$query->posts = array_values($filtered);
	$query->post_count = count($query->posts);
}

// Exit early if no posts
if (!$query || !$query->have_posts()) {
	return;
}

$scroller_id = 'kennisbank-carousel-' . wp_unique_id();
?>

<nok-section class="<?= $c->colors ?>">
	<div class="nok-section__inner--stretched">
		<div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">

			<article class="nok-layout-grid nok-layout-grid__4-column nok-align-items-start nok-column-gap-3">

				<!-- Left column: title, content, links, nav buttons -->
				<div class="nok-layout-flex-column nok-align-items-stretch">
					<?php if (!$c->hide_title->isTrue()) : ?>
					<h2 class="nok-fs-6"><?= $c->title() ?></h2>
					<?php endif; ?>

					<?php if ($c->content()) : ?>
						<div class="nok-text-content margin-paragraphs"><?= $c->content(); ?></div>
					<?php endif; ?>

					<?php if ($c->show_all_link->isTrue()) : ?>
						<a href="<?= $c->all_link_url->link() ?>"
						   class="nok-square-block__link nok-mt-1"
						   style="position: relative;">
							<?= strtoupper(esc_html($c->all_link_text->raw())) ?>
							<?= Assets::getIcon('ui_arrow-right-long') ?>
						</a>
					<?php endif; ?>

					<?php if ($c->show_nav_buttons->isTrue()) : ?>
						<div class="nok-button-group nok-mt-2">
							<button class="nok-button nok-bg-darkblue nok-dark-bg-darkblue nok-text-contrast fill-group-column"
							        data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="backward"
							        aria-label="Vorige artikelen">
								<?= Assets::getIcon('ui_arrow-left-long', 'nok-text-yellow') ?>
							</button>
							<button class="nok-button nok-bg-darkblue nok-dark-bg-darkblue nok-text-contrast fill-group-column"
							        data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="forward"
							        aria-label="Volgende artikelen">
								<?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
							</button>
						</div>
					<?php endif; ?>
                    <a href="<?= get_post_type_archive_link('kennisbank'); ?>" role="button" class="nok-button nok-bg-darkblue nok-text-contrast">
                        Bezoek onze kennisbank <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                    </a>
				</div>

				<!-- Right column: article carousel (spans 3 columns) -->
				<div class="nok-column-last-3 nok-span-all-columns-to-xl nok-align-self-stretch nok-mt-to-xl-2">
					<div class="nok-layout-grid nok-columns-1 nok-layout-grid__3-column nok-columns-xl-3 nok-columns-md-2
					            nok-scrollable__horizontal columns-to-slides nok-subtle-shadow-compensation"
					     data-scroll-snapping="true"
					     data-draggable="true"
					     data-autoscroll="false"
					     id="<?= $scroller_id; ?>">

						<?php while ($query->have_posts()) : $query->the_post();
							$post_id = get_the_ID();
							$permalink = get_the_permalink();
							$title = get_the_title();
							$excerpt = get_the_excerpt();
							$date = get_the_date('d-m-y');

							$has_image = has_post_thumbnail($post_id);

							// Get primary category
							$categories = get_the_terms($post_id, 'kennisbank_categories');
							$primary_category = $categories && !is_wp_error($categories) ? $categories[0] : null;
							?>

							<article class="nok-square-block link-bottom <?= $c->card_colors ?>" data-shadow="true">
								<!-- Featured image with badge overlays -->
								<figure class="nok-square-block__image">
									<?php if ($has_image) : ?>
										<?= wp_get_attachment_image(
											get_post_thumbnail_id($post_id),
											'medium_large',
											false,
											[
												'loading'  => 'lazy',
												'decoding' => 'async',
												'sizes'    => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
											]
										); ?>
									<?php else : ?>
										<div class="nok-square-block__image--placeholder">
                                            <div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
                                                <?= Assets::getIcon('ui_question') ?>
                                            </div>
										</div>
									<?php endif; ?>

                                    <?php if ($c->show_category->isTrue() && $primary_category) : ?>
									<div class="nok-square-block__badges">
                                        <span class="nok-badge <?= $c->badge_colors ?>">
                                            <?= esc_html($primary_category->name) ?>
                                        </span>
									</div>
                                    <?php endif; ?>
								</figure>

								<!-- Card content -->
								<h3 class="nok-square-block__heading nok-fs-2 fw-bold">
									<?= esc_html($title) ?>
								</h3>

								<p class="nok-square-block__text nok-fs-1">
									<?= esc_html(wp_trim_words($excerpt, 20, '...')) ?>
								</p>

								<a href="<?= esc_url($permalink) ?>"
								   class="nok-square-block__link"
								   title="<?= esc_attr($title) ?>">
									<?= strtoupper(esc_html($c->read_more_text->raw())) ?>
									<?= Assets::getIcon('ui_arrow-right-long') ?>
								</a>
							</article>

						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>

					</div>
				</div>

			</article>

		</div>
	</div>
</nok-section>
