<?php
/**
 * Single template for the Page Part CPT.
 * Allows front‑end preview of each part in isolation.
 */

use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;
$theme_instance = Theme::get_instance();

get_header();

$id = get_the_ID();
$transient_design_slug = get_transient( "preview_design_slug_{$id}" );

// Get the design slug
$design = get_post_meta( get_the_ID(), 'design_slug', true ) ?: '';

// Check if we're in preview/editing mode
$is_editing = Helpers::is_editing_mode();

// Get all custom field values
$registry = $theme_instance->get_page_part_registry();
$current_template_data = $registry[$design] ?? [];
$expected_fields = $current_template_data['custom_fields'] ?? [];

$default_fields = [
    'text' => '(leeg)',
    'url' => '#',
];

foreach ( $expected_fields as $field ) {
    $meta_key = $field['meta_key'];
    $short_field_name = $field['name'];
    $is_text_based = in_array( $field['type'], [ 'text', 'textarea' ], true );

    $actual_meta_value = get_post_meta( get_the_ID(), $meta_key, true);
    $page_part_fields[ $short_field_name ] = empty( $actual_meta_value ) ?
        ($is_editing ?
            ($is_text_based ? Helpers::show_placeholder( $short_field_name ) : ($default_fields[$field['type']] ?? '')) : '') :
        $actual_meta_value;
}
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
        [
            'post' => get_post(),
            'page_part_fields' => $page_part_fields
        ]
    );
} ?>



<?php

get_footer();
