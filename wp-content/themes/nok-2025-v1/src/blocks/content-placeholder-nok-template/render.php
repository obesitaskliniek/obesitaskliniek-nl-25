<?php
/**
 * Server-side render for NOK Template Content Placeholder
 * Renders category-specific content template
 *
 * @return callable
 */

return function(): string {
	// Get current post from query context
	$post = get_queried_object();

	if (!$post || !($post instanceof WP_Post)) {
		return '<p><!-- Content placeholder: geen post context --></p>';
	}

	// Setup post data for template tags
	setup_postdata($post);

	ob_start();

	// Determine content template based on category
	$categories = get_the_category($post->ID);
	$content_template = null;

	if (!empty($categories)) {
		foreach ($categories as $category) {
			$template_path = "template-parts/single-{$category->slug}-content.php";
			if (locate_template($template_path)) {
				$content_template = $template_path;
				break;
			}
		}
	}

	// Include category-specific template or fallback to default content
	if ($content_template) {
		get_template_part('template-parts/single', "{$categories[0]->slug}-content");
	} else {
		// Fallback: render standard post content
		the_content();
	}

	wp_reset_postdata();

	return ob_get_clean();
};