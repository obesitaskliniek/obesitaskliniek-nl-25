<?php
/**
 * Desktop Top Row Menu Template
 * Renders utility links for the top navigation row (desktop only)
 *
 * Available variables:
 * @var array $menu_items Flat menu structure (no hierarchy expected)
 * @var string $location Menu location identifier
 */

use NOK2025\V1\Navigation\MenuManager;

// Fallback if no menu assigned
if (empty($menu_items)) {
	if (current_user_can('manage_options')) {
		echo '<a href="' . esc_url(admin_url('nav-menus.php')) . '" class="nok-nav-menu-item">';
		echo __('Set up top row menu', THEME_TEXT_DOMAIN);
		echo '</a>';
	}

	return;
}

foreach ($menu_items as $item):
	echo MenuManager::render_menu_link($item);
endforeach;
