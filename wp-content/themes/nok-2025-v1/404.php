<?php
/**
 * Template: 404 Not Found
 *
 * Uses template layout from Customizer with content-placeholder block.
 * Falls back to template-parts/404-content.php if no layout is assigned
 * or the assigned layout is no longer published.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

get_header('generic');

$layout_id = get_theme_mod('template_layout_404', 0);
$rendered  = false;

if ($layout_id) {
	$layout = get_post($layout_id);
	if ($layout && $layout->post_status === 'publish') {
		echo apply_filters('the_content', $layout->post_content);
		$rendered = true;
	}
}

if (!$rendered) {
	get_template_part('template-parts/404', 'content');
}

get_footer();
