<?php
/**
 * Single post template for Kennisbank "blogs" category
 * Uses template layout from Customizer with content-placeholder block
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

get_header('generic');

// Render template layout (if configured)
$layout_id = get_theme_mod('template_layout_kennisbank_blogs', 0);

if ($layout_id) {
    $layout = get_post($layout_id);
    if ($layout && $layout->post_status === 'publish') {
        echo apply_filters('the_content', $layout->post_content);
    }
} else {
    // Fallback if no layout configured: use content template directly
    get_template_part('template-parts/single', 'kennisbank-blogs-content');
}

get_footer();
