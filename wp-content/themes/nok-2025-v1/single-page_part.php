<?php
/**
 * Single template for the Page Part CPT.
 * Allows front‑end preview of each part in isolation.
 */

use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;
$theme_instance = Theme::get_instance();

$id = get_the_ID();
$transient_design_slug = get_transient( "preview_design_slug_{$id}" );

// Get the design slug
$design = get_post_meta( get_the_ID(), 'design_slug', true ) ?: '';

// Check if we're in preview/editing mode
$is_editing = Helpers::is_editing_mode();

// Get all custom field values
$page_part_fields = $theme_instance->get_page_part_fields( get_the_ID(), $design, $is_editing );

// Enqueue the monolithic front‑end CSS bundle (if not already)
wp_enqueue_style( 'nok-components-css' );
wp_enqueue_style( 'nok-backend-css' );

$css_uri = "/template-parts/page-parts/{$design}.css";
if ( file_exists( THEME_ROOT_ABS . $css_uri ) ) {
	wp_enqueue_style( $design, THEME_ROOT . $css_uri, array(), filemtime(THEME_ROOT_ABS . $css_uri), false);
}

get_header();

// Fallback to a default template if none selected
if ( ! $design || ! locate_template( "template-parts/page-parts/{$design}.php" ) ) {
    echo '<p>' . esc_html__( 'No design template found for this Page Part.', THEME_TEXT_DOMAIN ) . '</p>';
} else {

    // Pull in your part template, passing the post
    $theme_instance->include_page_part_template( $design, [
        'post' => get_post(),
        'page_part_fields' => $page_part_fields
    ] );
}

?>



<?php

//get_footer();
