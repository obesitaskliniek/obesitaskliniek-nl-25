<?php
/**
 * Template: Search Results
 *
 * Displays search results in a card grid with post type filtering.
 * Follows the archive-kennisbank.php pattern for hero + grid layout.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

get_header('generic');

$search_query  = get_search_query();
$total_results = $wp_query->found_posts;

// Post type filter — active type from query string
$active_type   = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
$allowed_types = ['page', 'post', 'vestiging', 'kennisbank'];

if ($active_type && ! in_array($active_type, $allowed_types, true)) {
	$active_type = '';
}

// Post type pill configuration: slug => [label, badge color class]
$type_config = [
	'page'       => ['Pagina\'s',   'nok-bg-lightblue'],
	'post'       => ['Artikelen',   'nok-bg-yellow'],
	'vestiging'  => ['Vestigingen', 'nok-bg-green'],
	'kennisbank' => ['Kennisbank',  'nok-bg-darkerblue'],
];
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white nok-bg-alpha-10 nok-dark-bg-alpha-10">
		<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
			<?php Helpers::render_breadcrumbs(); ?>

			<h1 class="nok-fs-giant"><?php esc_html_e('Zoekresultaten', THEME_TEXT_DOMAIN); ?></h1>

			<?php if ($search_query): ?>
				<p class="nok-intro-text">
					<?php
					printf(
						/* translators: %1$d: result count, %2$s: search query */
						_n(
							'%1$d resultaat voor &lsquo;%2$s&rsquo;',
							'%1$d resultaten voor &lsquo;%2$s&rsquo;',
							$total_results,
							THEME_TEXT_DOMAIN
						),
						$total_results,
						esc_html($search_query)
					);
					?>
				</p>
			<?php endif; ?>

			<!-- Search refinement form -->
			<form action="<?= esc_url(home_url('/')); ?>" method="get" role="search" class="nok-mt-1" style="max-width: 600px;">
				<input type="search"
				       name="s"
				       class="nok-search-input"
				       value="<?= esc_attr($search_query); ?>"
				       placeholder="<?php esc_attr_e('Zoeken...', THEME_TEXT_DOMAIN); ?>"
				       aria-label="<?php esc_attr_e('Zoekterm', THEME_TEXT_DOMAIN); ?>">
				<?php if ($active_type): ?>
					<input type="hidden" name="post_type" value="<?= esc_attr($active_type); ?>">
				<?php endif; ?>
			</form>

			<?php if ($search_query): ?>
				<!-- Post type filter pills -->
				<nav class="nok-category-pills nok-mt-2" aria-label="<?php esc_attr_e('Filter op type', THEME_TEXT_DOMAIN); ?>">
					<a href="<?= esc_url(add_query_arg('s', rawurlencode($search_query), home_url('/'))); ?>"
					   class="nok-pill <?= $active_type === '' ? 'nok-pill--active' : ''; ?>">
						Alles
					</a>
					<?php foreach ($type_config as $type_slug => $type_info): ?>
						<a href="<?= esc_url(add_query_arg(['s' => rawurlencode($search_query), 'post_type' => $type_slug], home_url('/'))); ?>"
						   class="nok-pill nok-bg-white nok-text-contrast <?= $active_type === $type_slug ? 'nok-pill--active' : ''; ?>">
							<?= esc_html($type_info[0]); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</header>
	</div>
</nok-hero>

<nok-section class="no-aos z-ascend">
	<div class="nok-section__inner nok-pull-up-2">

		<?php if (! $search_query): ?>

			<!-- No query entered -->
			<div class="text-center nok-py-4">
				<div class="nok-fs-4 nok-mb-1" style="opacity: 0.4;">
					<?= Assets::getIcon('ui_search'); ?>
				</div>
				<p class="nok-fs-3"><?php esc_html_e('Voer een zoekterm in om te zoeken.', THEME_TEXT_DOMAIN); ?></p>
			</div>

		<?php elseif (have_posts()): ?>

			<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
				<?php while (have_posts()): the_post();
					$post_id   = get_the_ID();
					$permalink = get_the_permalink();
					$title     = get_the_title();
					$post_type = get_post_type();

					// Type-specific display config
					$badge_label = $type_config[$post_type][0] ?? ucfirst($post_type);
					$badge_color = $type_config[$post_type][1] ?? 'nok-bg-lightblue';

					// Featured image
					$has_image = has_post_thumbnail($post_id);
					$image_url = $has_image ? Helpers::get_featured_image_uri($post_id, 'medium_large') : '';

					// Type-specific placeholder icon
					$placeholder_icons = [
						'vestiging'  => 'ui_location',
						'kennisbank' => 'ui_question',
						'post'       => 'ui_info',
						'page'       => 'ui_search',
					];
					$placeholder_icon = $placeholder_icons[$post_type] ?? 'ui_search';

					// Excerpt: vestiging shows address, others show excerpt
					if ($post_type === 'vestiging') {
						$meta        = get_post_meta($post_id);
						$street      = $meta['_street'][0] ?? '';
						$housenumber = $meta['_housenumber'][0] ?? '';
						$postal_code = $meta['_postal_code'][0] ?? '';
						$city        = $meta['_city'][0] ?? '';
						$excerpt     = trim("$street $housenumber, $postal_code $city", ', ');
					} else {
						$excerpt = wp_trim_words(get_the_excerpt(), 20, '...');
					}

					// Link text
					$link_text = ($post_type === 'vestiging') ? 'MEER INFORMATIE' : 'LEES MEER';

					// Badge text color: darkerblue badge needs white text, others use default
					$badge_text_class = ($badge_color === 'nok-bg-darkerblue') ? 'nok-text-white' : 'nok-text-darkblue';
					?>

					<article class="nok-square-block link-bottom nok-bg-white nok-text-darkblue" data-shadow="true">
						<figure class="nok-square-block__image">
							<?php if ($has_image): ?>
								<img src="<?= esc_url($image_url); ?>"
								     alt="<?= esc_attr($title); ?>"
								     loading="lazy"
								     decoding="async">
							<?php else: ?>
								<div class="nok-square-block__image--placeholder">
									<div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
										<?= Assets::getIcon($placeholder_icon); ?>
									</div>
								</div>
							<?php endif; ?>

							<div class="nok-square-block__badges">
								<span class="nok-badge <?= esc_attr($badge_color); ?> <?= esc_attr($badge_text_class); ?>">
									<?= esc_html($badge_label); ?>
								</span>
							</div>
						</figure>

						<h2 class="nok-square-block__heading nok-fs-3">
							<?= esc_html($title); ?>
						</h2>

						<p class="nok-square-block__text nok-fs-1">
							<?= esc_html($excerpt); ?>
						</p>

						<a href="<?= esc_url($permalink); ?>"
						   class="nok-square-block__link"
						   title="<?= esc_attr($title); ?>">
							<?= esc_html($link_text); ?>
							<?= Assets::getIcon('ui_arrow-right-long'); ?>
						</a>
					</article>

				<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination([
				'class'              => 'nok-navigation-pagination',
				'mid_size'           => 2,
				'prev_text'          => Assets::getIcon('ui_arrow-left') . ' ' . __('<span class="nok-invisible-to-sm">Vorige</span>', THEME_TEXT_DOMAIN),
				'next_text'          => __('<span class="nok-invisible-to-sm">Volgende</span>', THEME_TEXT_DOMAIN) . ' ' . Assets::getIcon('ui_arrow-right'),
				'screen_reader_text' => __('Paginanavigatie', THEME_TEXT_DOMAIN),
			]);
			?>

		<?php else: ?>

			<!-- No results found -->
			<div class="text-center nok-py-4">
				<div class="nok-fs-4 nok-mb-1" style="opacity: 0.4;">
					<?= Assets::getIcon('ui_search'); ?>
				</div>
				<p class="nok-fs-3"><?php esc_html_e('Geen resultaten gevonden.', THEME_TEXT_DOMAIN); ?></p>
				<p class="nok-fs-1">
					<?php
					printf(
						/* translators: %s: link to kennisbank */
						__('Probeer een andere zoekterm of bekijk onze <a href="%s">Kennisbank</a>.', THEME_TEXT_DOMAIN),
						esc_url(get_post_type_archive_link('kennisbank'))
					);
					?>
				</p>
			</div>

		<?php endif; ?>
	</div>
</nok-section>

<?php
get_footer();
