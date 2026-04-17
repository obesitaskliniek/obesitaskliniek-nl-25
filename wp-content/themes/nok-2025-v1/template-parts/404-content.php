<?php
/**
 * Template Part: 404 Content
 *
 * Renders the 404 page content with:
 * - Hero section with breadcrumbs and heading
 * - "Did you mean..." suggestions via Relevanssi (graceful degradation to WP search)
 * - Search form for manual searching
 * - Homepage link
 *
 * URL-to-search-terms parsing: splits the requested path on `/` and `-`,
 * filters out stop words and noise, then queries for relevant content.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

/**
 * Extract search terms from the requested URI path.
 *
 * Splits path segments and hyphenated words, filters stop words,
 * file extensions, and numeric-only segments.
 *
 * @param string $request_uri Raw REQUEST_URI value
 * @return string Sanitized search terms joined by spaces, or empty string
 */
function nok_404_extract_search_terms(string $request_uri): string {
	$path = parse_url($request_uri, PHP_URL_PATH);
	if (!$path) {
		return '';
	}

	// Split on slashes and hyphens, filter empty segments
	$segments = preg_split('/[\/-]+/', $path, -1, PREG_SPLIT_NO_EMPTY);
	if (!$segments) {
		return '';
	}

	// Dutch and English stop words commonly found in URLs
	$stop_words = [
		// Dutch
		'de', 'het', 'een', 'van', 'in', 'op', 'voor', 'met', 'naar', 'en',
		'is', 'aan', 'bij', 'uit', 'om', 'als', 'dan', 'maar', 'die', 'dat',
		'wat', 'wel', 'nog', 'kan', 'meer', 'veel', 'ook', 'niet', 'over',
		// English
		'the', 'a', 'an', 'of', 'in', 'on', 'for', 'with', 'to', 'and',
		'is', 'at', 'by', 'from', 'or', 'as', 'but', 'not', 'this', 'that',
		// Common URL noise
		'www', 'html', 'htm', 'php', 'asp', 'aspx', 'page', 'index',
		'wp', 'wp-content', 'uploads', 'wp-admin', 'wp-includes',
	];

	$terms = [];
	foreach ($segments as $segment) {
		$segment = strtolower(trim($segment));

		// Skip empty, numeric-only, single-char, or file-extension-like segments
		if ($segment === '' || ctype_digit($segment) || mb_strlen($segment) <= 1) {
			continue;
		}

		// Strip common file extensions
		$segment = preg_replace('/\.(html?|php|aspx?|pdf|jpg|png|gif)$/i', '', $segment);
		if ($segment === '') {
			continue;
		}

		if (!in_array($segment, $stop_words, true)) {
			$terms[] = $segment;
		}
	}

	return sanitize_text_field(implode(' ', $terms));
}

// Extract search terms from the current URL
$search_terms = nok_404_extract_search_terms($_SERVER['REQUEST_URI'] ?? '');
$suggestions  = [];

if ($search_terms !== '') {
	$query = new WP_Query([
		'post_type'      => ['page', 'post', 'vestiging', 'kennisbank'],
		'post_status'    => 'publish',
		's'              => $search_terms,
		'posts_per_page' => 5,
		'orderby'        => 'relevance',
		'order'          => 'DESC',
	]);

	// Use Relevanssi if available for better content-aware ranking
	if (function_exists('relevanssi_do_query')) {
		relevanssi_do_query($query);
	}

	if ($query->have_posts()) {
		$suggestions = $query->posts;
	}

	wp_reset_postdata();
}

// Post type display config: slug => [label, badge color class]
$type_config = [
	'page'       => ['Pagina\'s',   'nok-bg-lightblue'],
	'post'       => ['Artikelen',   'nok-bg-yellow'],
	'vestiging'  => ['Vestigingen', 'nok-bg-green'],
	'kennisbank' => ['Kennisbank',  'nok-bg-darkerblue'],
];

// Placeholder icons per post type
$placeholder_icons = [
	'vestiging'  => 'ui_location',
	'kennisbank' => 'ui_question',
	'post'       => 'ui_info',
	'page'       => 'ui_search',
];
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white nok-bg-alpha-10 nok-dark-bg-alpha-10">
		<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
			<?php Helpers::render_breadcrumbs(); ?>

			<h1 class="nok-fs-giant"><?php esc_html_e('Pagina niet gevonden', THEME_TEXT_DOMAIN); ?></h1>

			<p class="nok-intro-text">
				<?php esc_html_e('De pagina die je zoekt bestaat niet of is verplaatst.', THEME_TEXT_DOMAIN); ?>
			</p>

			<!-- Search form -->
			<form action="<?= esc_url(home_url('/')); ?>" method="get" role="search" class="nok-mt-1" style="max-width: 600px;">
				<input type="search"
				       name="s"
				       class="nok-search-input"
				       value=""
				       placeholder="<?php esc_attr_e('Zoeken...', THEME_TEXT_DOMAIN); ?>"
				       aria-label="<?php esc_attr_e('Zoekterm', THEME_TEXT_DOMAIN); ?>">
			</form>
		</header>
	</div>
</nok-hero>

<nok-section class="no-aos z-ascend">
	<div class="nok-section__inner nok-pull-up-2">

		<?php if (!empty($suggestions)): ?>

			<h2 class="nok-fs-4 nok-mb-2 nok-text-white"><?php esc_html_e('Bedoelde je misschien:', THEME_TEXT_DOMAIN); ?></h2>

			<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
				<?php foreach ($suggestions as $suggestion):
					setup_postdata($suggestion);
					$post_id   = $suggestion->ID;
					$permalink = get_permalink($post_id);
					$title     = get_the_title($post_id);
					$post_type = $suggestion->post_type;

					// Type-specific display config
					$badge_label = $type_config[$post_type][0] ?? ucfirst($post_type);
					$badge_color = $type_config[$post_type][1] ?? 'nok-bg-lightblue';

					$has_image = has_post_thumbnail($post_id);

					// Placeholder icon
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
						$excerpt = wp_trim_words(get_the_excerpt($post_id), 20, '...');
					}

					// Badge text color: darkerblue badge needs white text
					$badge_text_class = ($badge_color === 'nok-bg-darkerblue') ? 'nok-text-white' : 'nok-text-darkblue';
					?>

					<article class="nok-square-block link-bottom nok-bg-white nok-text-darkblue" data-shadow="true">
						<figure class="nok-square-block__image">
							<?php if ($has_image): ?>
								<?= wp_get_attachment_image(
									get_post_thumbnail_id($post_id),
									'medium_large',
									false,
									[
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 992px) 100vw, (max-width: 1200px) 50vw, 33vw',
									]
								); ?>
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

						<h3 class="nok-square-block__heading nok-fs-3">
							<?= esc_html($title); ?>
						</h3>

						<p class="nok-square-block__text nok-fs-1">
							<?= esc_html($excerpt); ?>
						</p>

						<a href="<?= esc_url($permalink); ?>"
						   class="nok-square-block__link"
						   title="<?= esc_attr($title); ?>">
							<?php esc_html_e('LEES MEER', THEME_TEXT_DOMAIN); ?>
							<?= Assets::getIcon('ui_arrow-right-long'); ?>
						</a>
					</article>

				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>

		<?php else: ?>

			<!-- No suggestions available -->
			<div class="text-center nok-py-4">
				<div class="nok-fs-4 nok-mb-1" style="opacity: 0.4;">
					<?= Assets::getIcon('ui_search'); ?>
				</div>
				<p class="nok-fs-3"><?php esc_html_e('Gebruik het zoekveld hierboven om te vinden wat u zoekt.', THEME_TEXT_DOMAIN); ?></p>
			</div>

		<?php endif; ?>

		<p class="nok-mt-2 text-center">
			<a href="<?= esc_url(home_url('/')); ?>" class="nok-button nok-button--primary">
				<?php esc_html_e('Naar de homepagina', THEME_TEXT_DOMAIN); ?>
			</a>
		</p>

	</div>
</nok-section>
