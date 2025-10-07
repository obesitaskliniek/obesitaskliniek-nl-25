<?php
get_header();

use NOK2025\V1\Theme;
$theme = Theme::get_instance();
$theme->embed_page_part_template('nok-header-main', []);