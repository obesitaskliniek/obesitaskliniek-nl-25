<?php
/**
 * Server‑side render for Embed NOK Page Part.
 *
 * @return callable
 */

use NOK2025\V1\Theme;

return function( array $attributes ): string {
    $post_id = absint( $attributes['postId'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'page_part' ) {
        return '<p>' . esc_html__( 'Geen blok geselecteerd.', THEME_TEXT_DOMAIN ) . '</p>';
    }
    $post = get_post( $post_id );
    if ( ! $post ) {
        return '<p>' . esc_html__( 'Part not found.', THEME_TEXT_DOMAIN ) . '</p>';
    }

    $design = get_post_meta( $post_id, 'design_slug', true ) ?: 'header-top-level';

	$theme_instance = Theme::get_instance();

    // Get default page part fields
	$page_part_fields = $theme_instance->get_page_part_fields( $post_id, $design, false );

	// Apply overrides from block attributes
	if (!empty($attributes['overrides']) && is_array($attributes['overrides'])) {
		// Apply featured image override if set
		if (isset($attributes['overrides']['_override_thumbnail_id']) && $attributes['overrides']['_override_thumbnail_id'] !== '') {
			add_filter('post_thumbnail_id', function($thumbnail_id, $post) use ($post_id, $attributes) {
				$check_id = is_object($post) ? $post->ID : $post;
				return ($check_id == $post_id) ? (int)$attributes['overrides']['_override_thumbnail_id'] : $thumbnail_id;
			}, 10, 2);
		}

		$registry = $theme_instance->get_page_part_registry();
		$template_data = $registry[$design] ?? [];
		$custom_fields = $template_data['custom_fields'] ?? [];

		foreach ($custom_fields as $field) {
			if (isset($attributes['overrides'][$field['meta_key']])
			    && $attributes['overrides'][$field['meta_key']] !== '') {
				$page_part_fields[$field['name']] = $attributes['overrides'][$field['meta_key']];
			}
		}
	}

	//Enqueue any part-specific CSS files we find
//	$css_uri = "/template-parts/page-parts/{$design}.css";
//	if ( file_exists( THEME_ROOT_ABS . $css_uri ) ) {
//		wp_enqueue_style( $design, THEME_ROOT . $css_uri, array(), filemtime(THEME_ROOT_ABS . $css_uri), false);
//	}

	ob_start();
	//This is the actual frontend rendering!
	$theme_instance->include_page_part_template( $design, [
		'post' => $post,
		'page_part_fields' => $page_part_fields
	] );
	return ob_get_clean();
};
