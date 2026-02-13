<?php
/**
 * Server-side render callback for Embed NOK Post Part block
 *
 * Renders a post-part template (e.g., BMI calculator) inline within post content.
 * Post parts render without the <section> wrapper that page parts use.
 *
 * Block attributes:
 * - template (string): Post-part template slug (e.g., 'nok-bmi-calculator')
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\Theme;

return function( array $attributes ): string {
	$template = $attributes['template'] ?? '';

	if ( empty( $template ) ) {
		return '<p>' . esc_html__( 'Geen post part geselecteerd.', THEME_TEXT_DOMAIN ) . '</p>';
	}

	// Sanitize to prevent directory traversal
	$template = sanitize_file_name( $template );

	// Verify the template file exists
	$template_path = get_theme_file_path( "template-parts/post-parts/{$template}.php" );
	if ( ! file_exists( $template_path ) ) {
		return '<p>' . sprintf(
			esc_html__( 'Post part template "%s" niet gevonden.', THEME_TEXT_DOMAIN ),
			esc_html( $template )
		) . '</p>';
	}

	ob_start();
	Theme::get_instance()->embed_post_part_template( $template, [] );
	return ob_get_clean();
};
