<?php
use NOK2025\V1\Theme;

$theme = Theme::get_instance();
$theme->embed_page_part_template('nok-footer', []);

wp_footer();