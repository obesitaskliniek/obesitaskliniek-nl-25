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
use NOK2025\V1\Navigation\MenuManager;

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
			<?php foreach ($parent['children'] as $child): ?>
				<div>
					<?= MenuManager::render_menu_link($child); ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-align-self-start">
		<h3 class="nok-square-block__heading">
			Vragen, of behoefte aan persoonlijk advies?
		</h3>
		<a href="/contact/" role="button" class="nok-button nok-bg-darkblue nok-text-contrast" tabindex="0">
			Neem contact op
			<?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
		</a>
        <a href="https://nokclinics.nl" target="_blank" class="nok-button nok-bg-clinics-oranje nok-text-white">
            Behandeling zonder operatie
            <?= Assets::getIcon('ui_arrow-up-right' ); ?>
        </a>
	</nok-square-block>
</div>
