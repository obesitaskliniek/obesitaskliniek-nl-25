<?php
/**
 * Single template for the Page Part CPT.
 * Allows front‑end preview of each part in isolation.
 */
get_header();

$id = get_the_ID(); $transient_design_slug = get_transient( "preview_design_slug_{$id}" );
?>

<?php
// Get the design slug
$design = get_post_meta( get_the_ID(), 'design_slug', true ) ?: '';

// Fallback to a default template if none selected
if ( ! $design || ! locate_template( "template-parts/page-parts/{$design}.php" ) ) {
    echo '<p>' . esc_html__( 'No design template found for this Page Part.', THEME_TEXT_DOMAIN ) . '</p>';
} else {
    // Enqueue the monolithic front‑end CSS bundle (if not already)
    wp_enqueue_style( 'nok-components-css' );
    wp_dequeue_style( $design );

    // Pull in your part template, passing the post
    get_template_part(
        "template-parts/page-parts/{$design}",
        null,
        [ 'post' => get_post() ]
    );
} ?>



<?php

get_footer();
