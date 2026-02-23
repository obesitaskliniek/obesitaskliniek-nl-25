<?php
/**
 * Mobile Menu Template
 * Renders navigation as carousel with slides for mobile view
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

use NOK2025\V1\Navigation\MenuManager;

// Fallback if no menu assigned
if (empty($menu_items)) {
	if (current_user_can('manage_options')) {
		echo '<div class="nok-nav-carousel__inner nok-text-darkerblue nok-dark-text-white">';
		echo '<div class="nok-nav-carousel__slide">';
		echo '<div class="nok-nav-menu-items" id="topmenu">';
		echo '<a href="' . esc_url(admin_url('nav-menus.php')) . '" class="nok-nav-menu-item">';
		echo __('Set up navigation menu', THEME_TEXT_DOMAIN);
		echo '</a>';
		echo '</div></div></div>';
	}
	return;
}

// Separate parents with children from standalone items
$parents_with_children = array_filter($menu_items, function($item) {
	return $item['has_children'];
});
?>
<div class="nok-nav-carousel__inner nok-text-darkerblue nok-dark-text-white">
	<div class="nok-nav-carousel__slide">
		<div class="nok-nav-menu-items" id="topmenu">
			<?php foreach ($menu_items as $item):
				$options = [ 'popup_unsets_sidebar' => true ];

				// Items with children link to their submenu slide
				if ($item['has_children'] && empty($item['is_popup_trigger'])) {
					$options['url_override'] = '#submenu-' . sanitize_title($item['title']);
				}

				echo MenuManager::render_menu_link($item, $options);
			endforeach; ?>
		</div>
	</div>
	<?php if (!empty($parents_with_children)): ?>
		<div class="nok-nav-carousel__slide">
			<?php foreach ($parents_with_children as $parent):
				$submenu_id = 'submenu-' . sanitize_title($parent['title']);
				?>
				<div class="nok-nav-menu-items" id="<?= esc_attr($submenu_id); ?>">
					<a href="#topmenu" class="nok-nav-menu-item nok-nav-menu-item__back">
						&laquo; <?= __('Terug naar overzicht', THEME_TEXT_DOMAIN); ?>
					</a>
					<?php foreach ($parent['children'] as $child):
						echo MenuManager::render_menu_link($child, [ 'popup_unsets_sidebar' => true ]);
					endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
