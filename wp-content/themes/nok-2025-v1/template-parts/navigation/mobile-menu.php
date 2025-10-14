<?php
/**
 * Mobile Menu Template
 * Renders navigation as carousel with slides for mobile view
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

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
				$classes = ['nok-nav-menu-item'];

				if ($item['is_current'] || $item['is_current_ancestor']) {
					$classes[] = 'nok-nav-menu-item--active';
				}

				if (!empty($item['classes'])) {
					$classes = array_merge($classes, $item['classes']);
				}

				$class_string = implode(' ', array_map('esc_attr', $classes));

				// If has children, link to submenu anchor, otherwise use actual URL
				if ($item['has_children']) {
					$url = '#submenu-' . sanitize_title($item['title']);
				} else {
					$url = $item['url'] ?: '#';
				}

				$attrs = [];
				if (!empty($item['target']) && !$item['has_children']) {
					$attrs[] = 'target="' . esc_attr($item['target']) . '"';
				}
				if (!empty($item['attr_title'])) {
					$attrs[] = 'title="' . esc_attr($item['attr_title']) . '"';
				}
				$attr_string = !empty($attrs) ? ' ' . implode(' ', $attrs) : '';
				?>
				<a href="<?= esc_url($url); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?>>
					<?= esc_html($item['title']); ?>
				</a>
			<?php endforeach; ?>
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
						$classes = ['nok-nav-menu-item'];

						if ($child['is_current']) {
							$classes[] = 'nok-nav-menu-item--active';
						}

						if (!empty($child['classes'])) {
							$classes = array_merge($classes, $child['classes']);
						}

						$class_string = implode(' ', array_map('esc_attr', $classes));

						$attrs = [];
						if (!empty($child['target'])) {
							$attrs[] = 'target="' . esc_attr($child['target']) . '"';
						}
						if (!empty($child['attr_title'])) {
							$attrs[] = 'title="' . esc_attr($child['attr_title']) . '"';
						}
						$attr_string = !empty($attrs) ? ' ' . implode(' ', $attrs) : '';
						?>
						<a href="<?= esc_url($child['url']); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?>>
							<?= esc_html($child['title']); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>