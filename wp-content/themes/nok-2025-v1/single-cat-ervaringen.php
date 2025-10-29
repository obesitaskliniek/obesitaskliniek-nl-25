<?php
/**
 * Single post template for Ervaringen category
 * Uses template layout from Customizer with content-placeholder block
 */

use NOK2025\V1\Theme;

get_header('generic');

// Render template layout (if configured)
$layout_id = get_theme_mod('template_layout_ervaringen', 0);

if ($layout_id) {
    $layout = get_post($layout_id);
    if ($layout && $layout->post_status === 'publish') {
        echo apply_filters('the_content', $layout->post_content);
    }
} else {
    // Fallback if no layout configured: use content template directly
    get_template_part('template-parts/single', 'ervaringen-content');
}

get_footer();