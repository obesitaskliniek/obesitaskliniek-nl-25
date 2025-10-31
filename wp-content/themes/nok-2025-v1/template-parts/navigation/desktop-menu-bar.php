<?php
/**
 * Desktop Menu Bar Template
 * Renders top-level navigation items for desktop view
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

// Fallback if no menu assigned
if ( empty( $menu_items ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		echo '<div class="nok-nav-menu-items">';
		echo '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" class="nok-nav-menu-item">';
		echo __( 'Set up navigation menu', THEME_TEXT_DOMAIN );
		echo '</a>';
		echo '</div>';
	}

	return;
}
?>

<style>
    .dropdown-contents-menu {
        display: none;
    }
    <?php foreach ( $menu_items as $item ): ?>
    [data-active-menu="submenu-<?= $item['id']; ?>"] .dropdown-contents-menu[data-submenu-id="submenu-<?= $item['id']; ?>"] {
        display: flex;
    }
    <?php endforeach; ?>
</style>

<?php foreach ( $menu_items as $item ): ?>
    <div <?php if ($item['has_children']): ?>data-toggles="sidebar-open" data-class-target="nok-top-navigation" data-toggles-class-if-present="false" data-click-outside="unset-class"
         data-sets-attribute="active-menu" data-sets-attribute-value="submenu-<?= $item['id']; ?>" data-attribute-target="nok-top-navigation"<?php endif; ?>>
		<?php
		$classes = [ 'nok-nav-menu-item' ];

		// Add active class
		if ( $item['is_current'] || $item['is_current_ancestor'] ) {
			$classes[] = 'nok-nav-menu-item--active';
		}

		// Add custom classes from admin
		if ( ! empty( $item['classes'] ) ) {
			$classes = array_merge( $classes, $item['classes'] );
		}

		$class_string = implode( ' ', array_map( 'esc_attr', $classes ) );

        $url = $item['url'] ?: '#';

		// Build attributes
		$attrs = [];
		if ( ! empty( $item['target'] ) ) {
			$attrs[] = 'target="' . esc_attr( $item['target'] ) . '"';
		}
		if ( ! empty( $item['attr_title'] ) ) {
			$attrs[] = 'title="' . esc_attr( $item['attr_title'] ) . '"';
		}

		$attr_string = ! empty( $attrs ) ? ' ' . implode( ' ', $attrs ) : '';
		?>
        <a href="<?= esc_url( $url ); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?>>
			<?= esc_html( $item['title'] ); ?>
        </a>
    </div>
<?php endforeach; ?>