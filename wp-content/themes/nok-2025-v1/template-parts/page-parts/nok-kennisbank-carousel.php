<?php
/**
 * Template Name: Kennisbank Carousel
 * Description: Displays title, intro, and scrollable carousel of kennisbank articles with featured images
 * Slug: nok-kennisbank-carousel
 * Custom Fields:
 * - colors:select(Transparant::nok-bg-body|Grijs::nok-bg-body--darker gradient-background|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)!page-editable!default(Transparant)
 * - card_colors:select(Wit::nok-bg-white nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-white)!page-editable!default(Wit)
 * - badge_colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-text-darkerblue)!page-editable!default(Blauw)
 * - shuffle:checkbox!default(false)!descr[Willekeurige volgorde?]
 * - show_all_link:checkbox!default(true)!descr[Toon "Alle items" link]
 * - all_link_url:url!default(/kennisbank)!descr[URL voor "Alle items" link]
 * - all_link_text:text!default(Alle items)
 * - show_nav_buttons:checkbox!default(true)!descr[Toon navigatieknoppen voor de carousel]
 * - category_filter:text!descr[Filter op categorie slug, bijv. "medisch" (optioneel, laat leeg voor alle)]
 * - max_items:select(3|6|9|12)!default(6)!descr[Maximum aantal artikelen]
 * - read_more_text:text!default(Lees meer)
 * - show_date:checkbox!default(true)!descr[Toon publicatiedatum]
 * - show_category:checkbox!default(true)!descr[Toon categorie badge]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

// Build taxonomy query if category filter is set
$tax_query = [];
$category_filter = trim($c->category_filter->raw());
if (!empty($category_filter)) {
	$tax_query = [
		[
			'taxonomy' => 'kennisbank_categories',
			'field'    => 'slug',
			'terms'    => $category_filter,
		]
	];
}

// Query kennisbank posts
$query = Helpers::get_latest_custom_posts(
	'kennisbank',
	intval($c->max_items->raw()),
	[],
	$tax_query
);

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
					<h2 class="nok-fs-6"><?= $c->title() ?></h2>

					<?php if ($c->content()) : ?>
						<div class="nok-text-content margin-paragraphs"><?= $c->content(); ?></div>
					<?php endif; ?>

					<?php if ($c->show_all_link->isTrue()) : ?>
						<a href="<?= esc_url($c->all_link_url->raw()) ?>"
						   class="nok-square-block__link nok-mt-1"
						   style="position: relative;">
							<?= strtoupper(esc_html($c->all_link_text->raw())) ?>
							<?= Assets::getIcon('ui_arrow-right-long') ?>
						</a>
					<?php endif; ?>

					<?php if ($c->show_nav_buttons->isTrue()) : ?>
						<div class="nok-button-group nok-mt-2">
							<button class="nok-button nok-bg-lightgrey nok-dark-bg-darkblue nok-text-contrast fill-group-column"
							        data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="backward"
							        aria-label="Vorige artikelen">
								<?= Assets::getIcon('ui_arrow-left-longer') ?>
							</button>
							<button class="nok-button nok-bg-lightgrey nok-dark-bg-darkblue nok-text-contrast fill-group-column"
							        data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="forward"
							        aria-label="Volgende artikelen">
								<?= Assets::getIcon('ui_arrow-right-longer') ?>
							</button>
						</div>
					<?php endif; ?>
				</div>

				<!-- Right column: article carousel (spans 3 columns) -->
				<div class="nok-column-last-3 nok-span-all-columns-to-xl nok-align-self-stretch nok-mt-to-xl-2">
					<div class="nok-layout-grid nok-columns-1 nok-layout-grid__3-column nok-columns-xl-3 nok-columns-md-2
					            nok-scrollable__horizontal columns-to-slides nok-subtle-shadow-compensation"
					     data-scroll-snapping="true"
					     data-draggable="true"
					     data-autoscroll="false"
					     id="<?= $scroller_id; ?>"
						<?= $c->shuffle->isTrue() ? 'data-nok-shuffle' : '' ?>>

						<?php while ($query->have_posts()) : $query->the_post();
							$post_id = get_the_ID();
							$permalink = get_the_permalink();
							$title = get_the_title();
							$excerpt = get_the_excerpt();
							$date = get_the_date('d-m-y');

							// Get featured image URL (uses fallback if none)
							$image_url = Helpers::get_featured_image_uri($post_id, 'medium_large');
							$has_image = has_post_thumbnail($post_id);

							// Get primary category
							$categories = get_the_terms($post_id, 'kennisbank_categories');
							$primary_category = $categories && !is_wp_error($categories) ? $categories[0] : null;
							?>

							<article class="nok-square-block link-bottom <?= $c->card_colors ?>" data-shadow="true">
								<!-- Featured image with badge overlays -->
								<figure class="nok-square-block__image">
									<?php if ($has_image) : ?>
										<img src="<?= esc_url($image_url) ?>"
										     alt="<?= esc_attr($title) ?>"
										     loading="lazy"
										     decoding="async">
									<?php else : ?>
										<div class="nok-square-block__image--placeholder">
                                            <div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
                                                <?= Assets::getIcon('ui_question') ?>
                                            </div>
										</div>
									<?php endif; ?>

									<div class="nok-square-block__badges">
										<?php if ($c->show_category->isTrue() && $primary_category) : ?>
											<span class="nok-badge <?= $c->badge_colors ?>">
												<?= esc_html($primary_category->name) ?>
											</span>
										<?php endif; ?>

										<?php if ($c->show_date->isTrue()) : ?>
											<span class="nok-badge nok-bg-white nok-text-darkerblue">
												<?= esc_html($date) ?>
											</span>
										<?php endif; ?>
									</div>
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
