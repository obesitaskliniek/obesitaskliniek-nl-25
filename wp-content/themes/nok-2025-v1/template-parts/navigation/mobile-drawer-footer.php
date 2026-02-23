<?php
/**
 * Mobile Drawer Footer Menu Template
 * Renders utility links at the bottom of the mobile navigation drawer
 *
 * Popup triggers close the sidebar before opening the popup (via popup_unsets_sidebar).
 * CSS classes from menu admin are passed through for button styling (e.g., nok-button).
 *
 * Available variables:
 * @var array $menu_items Flat menu structure (no hierarchy expected)
 * @var string $location Menu location identifier
 */

use NOK2025\V1\Navigation\MenuManager;

// Fallback if no menu assigned
if (empty($menu_items)) {
	if (current_user_can('manage_options')) {
		echo '<div class="nok-nav-menu-items nok-nav-menu-items--compact">';
		echo '<a href="' . esc_url(admin_url('nav-menus.php')) . '" class="nok-nav-menu-item">';
		echo __('Set up mobile drawer menu', THEME_TEXT_DOMAIN);
		echo '</a>';
		echo '</div>';
	}

	return;
}
?>
<div class="nok-nav-menu-items nok-nav-menu-items--compact">
	<?php foreach ($menu_items as $item):
		echo MenuManager::render_menu_link($item, ['popup_unsets_sidebar' => true]);
	endforeach; ?>
</div>
