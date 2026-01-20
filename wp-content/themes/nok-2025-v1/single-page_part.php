<?php
/*
 * Used to render the page part posts inside the POST editor preview
 */
use NOK2025\V1\Theme;
$theme = Theme::get_instance();
$id = get_the_ID();
$design = get_post_meta($id, 'design_slug', true) ?: '';
$page_part_fields = $theme->get_page_part_fields($id, $design, true);

// Enqueue core styles
wp_enqueue_style('nok-components-css');

get_header();

if (!$design || !locate_template("template-parts/page-parts/{$design}.php")) {
	echo '<p>' . esc_html__('No design template found for this Page Part.', THEME_TEXT_DOMAIN) . '</p>';
} else {
	$theme->include_page_part_template($design, [
		'post' => get_post(),
		'page_part_fields' => $page_part_fields
	]);
}

get_footer();