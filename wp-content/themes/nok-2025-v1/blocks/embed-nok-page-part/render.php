<?php
/**
 * Server-side render callback for Embed NOK Page Part block
 *
 * Renders a page_part custom post type using its registered template and fields.
 * Supports field value overrides, featured image override, and generic title/content
 * overrides per block instance for SEO duplicate content prevention.
 *
 * Block attributes:
 * - postId (int): Page part post ID to render
 * - overrides (array): Field value overrides keyed by meta_key
 *   - Example: ['nok-hero_title' => 'Custom Title']
 *   - Special: '_override_thumbnail_id' for featured image override
 *   - Special: '_override_title' for post title override
 *   - Special: '_override_content' for post content override
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\Theme;

return function( array $attributes ): string {
	$post_id = absint( $attributes['postId'] ?? 0 );
	if ( ! $post_id || get_post_type( $post_id ) !== 'page_part' ) {
		// Only show message for logged-in users (editors), hide from visitors
		return is_user_logged_in()
			? '<p>' . esc_html__( 'Geen blok geselecteerd.', THEME_TEXT_DOMAIN ) . '</p>'
			: '';
	}
	$post = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		// Only show message for logged-in users, hide from visitors
		return is_user_logged_in()
			? '<p>' . esc_html__( 'Page part niet gevonden of niet gepubliceerd.', THEME_TEXT_DOMAIN ) . '</p>'
			: '';
	}

	$design = get_post_meta( $post_id, 'design_slug', true ) ?: 'header-top-level';

	$theme_instance = Theme::get_instance();

	// Get default page part fields
	$page_part_fields = $theme_instance->get_page_part_fields( $post_id, $design, false );

	// Initialize generic overrides array
	$generic_overrides = [];

	// Apply overrides from block attributes
	if ( ! empty( $attributes['overrides'] ) && is_array( $attributes['overrides'] ) ) {

		// Extract generic overrides (title/content) - always available for any template
		if ( isset( $attributes['overrides']['_override_title'] ) && $attributes['overrides']['_override_title'] !== '' ) {
			$generic_overrides['_override_title'] = sanitize_text_field( $attributes['overrides']['_override_title'] );
		}

		if ( isset( $attributes['overrides']['_override_content'] ) && $attributes['overrides']['_override_content'] !== '' ) {
			$generic_overrides['_override_content'] = wp_kses_post( $attributes['overrides']['_override_content'] );
		}

		// Apply featured image override if set
		if ( isset( $attributes['overrides']['_override_thumbnail_id'] ) && $attributes['overrides']['_override_thumbnail_id'] !== '' ) {
			add_filter( 'post_thumbnail_id', function( $thumbnail_id, $post ) use ( $post_id, $attributes ) {
				$check_id = is_object( $post ) ? $post->ID : $post;
				return ( $check_id == $post_id ) ? (int) $attributes['overrides']['_override_thumbnail_id'] : $thumbnail_id;
			}, 10, 2 );
		}

		// Apply template-specific field overrides
		$registry = $theme_instance->get_page_part_registry();
		$template_data = $registry[ $design ] ?? [];
		$custom_fields = $template_data['custom_fields'] ?? [];

		foreach ( $custom_fields as $field ) {
			if ( isset( $attributes['overrides'][ $field['meta_key'] ] )
			     && $attributes['overrides'][ $field['meta_key'] ] !== '' ) {
				$page_part_fields[ $field['name'] ] = $attributes['overrides'][ $field['meta_key'] ];
			}
		}
	}

	ob_start();
	// Frontend rendering with generic overrides support
	$theme_instance->include_page_part_template( $design, [
		'post'              => $post,
		'page_part_fields'  => $page_part_fields,
		'generic_overrides' => $generic_overrides,
	] );

	return ob_get_clean();
};