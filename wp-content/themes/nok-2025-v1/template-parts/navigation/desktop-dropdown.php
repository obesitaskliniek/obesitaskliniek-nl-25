<?php
/**
 * Desktop Dropdown Template
 * Renders submenu items in dropdown structure
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

use NOK2025\V1\Assets;

// Filter to only parents with children
$parents_with_children = array_filter($menu_items, function($item) {
	return $item['has_children'];
});

// Exit if no submenus exist
if (empty($parents_with_children)) {
	return;
}
?>
<div class="dropdown-contents nok-bg-white nok-dark-bg-darkerblue--darker nok-dark-text-contrast">
	<?php foreach ($parents_with_children as $parent): ?>
		<div class="dropdown-contents-menu nok-ul-list nok-mt-0"
			 data-submenu-id="submenu-<?= esc_attr($parent['id']); ?>">
			<h3><?= esc_html($parent['title']); ?></h3>
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
				<div>
					<a href="<?= esc_url($child['url']); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?>>
						<?= esc_html($child['title']); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<!-- todo: make dynamic -->
	<nok-square-block class="nok-bg-darkerblue">
		<h3 class="nok-square-block__heading">
			Vragen, of behoefte aan persoonlijk advies?
		</h3>
		<button class="nok-button nok-bg-darkblue nok-text-contrast" tabindex="0">
			Neem contact op
			<?= Assets::getIcon('arrow-right', 'nok-text-yellow'); ?>
		</button>
	</nok-square-block>
</div>