<?php
/**
 * Server-side render for NOK Template Content Placeholder
 * Renders post-type or category-specific content template
 *
 * @return callable
 */

return function(): string {
	$post = get_queried_object();

	if (!$post || !($post instanceof WP_Post)) {
		return '<p><!-- Content placeholder: geen post context --></p>';
	}

	setup_postdata($post);
	ob_start();

	$content_template = null;

	// Check for custom post type template first
	if ($post->post_type !== 'post') {
		// For CPTs, check for category-specific template first (e.g., single-kennisbank-blogs-content.php)
		$taxonomies = get_object_taxonomies($post->post_type, 'names');
		foreach ($taxonomies as $taxonomy) {
			$terms = get_the_terms($post->ID, $taxonomy);
			if ($terms && !is_wp_error($terms)) {
				foreach ($terms as $term) {
					$template_path = "template-parts/single-{$post->post_type}-{$term->slug}-content.php";
					if (locate_template($template_path)) {
						$content_template = "{$post->post_type}-{$term->slug}";
						break 2;
					}
				}
			}
		}

		// Fall back to generic CPT template (e.g., single-kennisbank-content.php)
		if (!$content_template) {
			$template_path = "template-parts/single-{$post->post_type}-content.php";
			if (locate_template($template_path)) {
				$content_template = $post->post_type;
			}
		}
	} else {
		// For regular posts, check category-specific templates
		$categories = get_the_category($post->ID);
		if (!empty($categories)) {
			foreach ($categories as $category) {
				$template_path = "template-parts/single-{$category->slug}-content.php";
				if (locate_template($template_path)) {
					$content_template = $category->slug;
					break;
				}
			}
		}
	}

	if ($content_template) {
		get_template_part('template-parts/single', "{$content_template}-content");
	} else {
		the_content();
	}

	wp_reset_postdata();
	return ob_get_clean();
};