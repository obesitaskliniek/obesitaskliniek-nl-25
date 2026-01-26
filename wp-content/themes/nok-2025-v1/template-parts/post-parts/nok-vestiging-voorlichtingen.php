<?php
/**
 * Post-Part: Vestiging Voorlichtingen
 *
 * Displays upcoming voorlichtingen for a specific vestiging in a carousel format.
 * Can auto-detect vestiging from current post or accept explicit city parameter.
 *
 * @param array $args {
 *     Optional. Arguments to configure the display.
 *
 *     @type string $city    City name to filter voorlichtingen by. If empty, auto-detects from current vestiging post.
 *     @type int    $limit   Maximum number of voorlichtingen to show. Default 3.
 *     @type string $colors  Color classes for the section. Default 'nok-bg-darkerblue nok-text-white'.
 *     @type string $title   Section title. Default 'Voorlichtingen'.
 *     @type bool   $show_all_link Show link to all voorlichtingen for this vestiging. Default true.
 * }
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

// Integration status: See TODO.md MED-002

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

// Parse arguments
$defaults = [
	'city'          => '',
	'limit'         => 3,
	'colors'        => 'nok-bg-darkerblue nok-text-white nok-dark-bg-darkestblue',
	'title'         => 'Voorlichtingen',
	'show_all_link' => true,
];
$args = wp_parse_args($args ?? [], $defaults);

// Auto-detect city from current vestiging post if not provided
$city = $args['city'];
if (empty($city) && get_post_type() === 'vestiging') {
	// Extract city from title (e.g., "NOK Amsterdam" -> "Amsterdam")
	$city = preg_replace('/^NOK\s+/i', '', get_the_title());
}

// Exit early if no city
if (empty($city)) {
	return;
}

// Get upcoming voorlichtingen for this vestiging
$voorlichtingen = Helpers::get_voorlichtingen_for_vestiging($city, $args['limit']);

// Exit if no voorlichtingen found
if (empty($voorlichtingen)) {
	return;
}

$all_voorlichtingen_url = add_query_arg('locatie', urlencode($city), get_post_type_archive_link('voorlichting'));
?>

<nok-section id="voorlichtingen">
	<div class="nok-section__inner--stretched <?= esc_attr($args['colors']); ?>">
		<div class="nok-section__inner">

			<article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
				<header class="nok-layout-flex nok-layout-flex-row nok-justify-content-space-between nok-align-items-center">
					<h2 class="nok-fs-4"><?= esc_html($args['title']); ?></h2>
					<?php if ($args['show_all_link']): ?>
						<a href="<?= esc_url($all_voorlichtingen_url); ?>" class="nok-button nok-bg-transparent nok-text-inherit">
							<?php esc_html_e('Bekijk alle', THEME_TEXT_DOMAIN); ?>
							<?= Assets::getIcon('ui_arrow-right-long'); ?>
						</a>
					<?php endif; ?>
				</header>

				<div class="nok-mt-2 nok-align-self-stretch">
					<div class="nok-layout-grid nok-layout-grid__3-column
						nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true">

						<?php foreach ($voorlichtingen as $post):
							setup_postdata($post);
							$hubspotData = Helpers::setup_hubspot_metadata($post->ID);
							$is_open = $hubspotData['open'];
							$is_online = strtolower($hubspotData['type']) === 'online';
							?>

							<nok-square-block class="nok-bg-darkblue nok-dark-bg-darkestblue nok-text-contrast" data-shadow="false">
								<div class="nok-square-block__heading">
									<h3 class="nok-fs-3 nok-fs-to-md-2">
										<?= esc_html($hubspotData['timestamp']['niceDateFull']); ?>
										<small class="nok-text-yellow">(<?= esc_html($hubspotData['type']); ?>)</small>
									</h3>
								</div>
								<div class="nok-square-block__text">
									<p class="nok-layout-flex-row nok-column-gap-0_5">
										<?= Assets::getIcon('ui_time'); ?>
										<span><?= esc_html($hubspotData['timestamp']['start_time']); ?> - <?= esc_html($hubspotData['timestamp']['end_time']); ?> uur</span>
									</p>
									<p class="nok-layout-flex-row nok-column-gap-0_5">
										<?= Assets::getIcon($is_online ? 'ui_laptop' : 'ui_location'); ?>
										<span><?= $is_online ? esc_html__('Online', THEME_TEXT_DOMAIN) : esc_html(ucfirst($hubspotData['locatie'])); ?></span>
									</p>
									<?php if (!$is_open): ?>
										<p class="nok-text-yellow">
											<?= esc_html(ucfirst($hubspotData['status'])); ?>
										</p>
									<?php endif; ?>
								</div>
								<a role="button" href="<?= esc_url(get_permalink($post->ID)); ?>"
								   class="nok-button nok-justify-self-start w-100 nok-bg-yellow nok-text-contrast"
								   tabindex="0">
									<?php $is_open ? esc_html_e('Aanmelden', THEME_TEXT_DOMAIN) : esc_html_e('Bekijken', THEME_TEXT_DOMAIN); ?>
									<?= Assets::getIcon('ui_arrow-right-long'); ?>
								</a>
							</nok-square-block>

						<?php endforeach; ?>
						<?php wp_reset_postdata(); ?>

					</div>
				</div>

			</article>

		</div>
	</div>
</nok-section>
