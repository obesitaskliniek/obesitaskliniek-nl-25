<?php
/**
 * Desktop Top Row Menu Template
 * Renders utility links for the top navigation row (desktop only)
 *
 * Available variables:
 * @var array $menu_items Flat menu structure (no hierarchy expected)
 * @var string $location Menu location identifier
 */

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
	$classes = ['nok-nav-menu-item'];

	if ($item['is_current']) {
		$classes[] = 'nok-nav-menu-item--active';
	}

	if (!empty($item['classes'])) {
		$classes = array_merge($classes, $item['classes']);
	}

	$class_string = implode(' ', array_map('esc_attr', $classes));

	// Popup triggers use # instead of their URL
	$url = !empty($item['is_popup_trigger']) ? '#' : ($item['url'] ?: '#');

	// Build attributes
	$attrs = [];
	if (!empty($item['target'])) {
		$attrs[] = 'target="' . esc_attr($item['target']) . '"';
	}
	if (!empty($item['attr_title'])) {
		$attrs[] = 'title="' . esc_attr($item['attr_title']) . '"';
	}

	$attr_string = !empty($attrs) ? ' ' . implode(' ', $attrs) : '';

	// Popup trigger data-attributes
	$popup_attrs = '';
	if (!empty($item['is_popup_trigger']) && !empty($item['popup_id'])) {
		$popup_attrs = sprintf(
			' data-toggles-class="popup-open" data-class-target="nok-top-navigation"'
			. ' data-toggle-event="click" data-toggles-attribute="data-state"'
			. ' data-toggles-attribute-value="open" data-attribute-target="#%s"',
			esc_attr($item['popup_id'])
		);
	}
	?>
	<a href="<?= esc_url($url); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?><?= $popup_attrs; ?>><?= esc_html($item['title']); ?></a>
<?php endforeach; ?>
