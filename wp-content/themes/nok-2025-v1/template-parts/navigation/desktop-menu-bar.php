<?php
/**
 * Desktop Menu Bar Template
 * Renders top-level navigation items for desktop view
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

use NOK2025\V1\Navigation\MenuManager;

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
    [data-active-menu="submenu-<?= esc_attr( $item['id'] ); ?>"] .dropdown-contents-menu[data-submenu-id="submenu-<?= esc_attr( $item['id'] ); ?>"] {
        display: flex;
    }
    <?php endforeach; ?>
</style>

<?php foreach ( $menu_items as $item ):
	// Build extra classes for current-page tracking
	$extra_classes = [];
	if ( $item['is_current'] ) {
		$extra_classes[] = 'is-current-page';
	} elseif ( $item['is_current_ancestor'] ) {
		$extra_classes[] = 'is-current-ancestor';
	}

	// Toggler wrapper attributes for dropdown behavior
	if ( $item['has_children'] ):
		?>
        <div data-sets-class="sidebar-open" data-toggle-event="hover" data-class-target="nok-top-navigation" data-toggle-outside="unset"
             data-sets-attribute="data-active-menu" data-sets-attribute-value="submenu-<?= esc_attr( $item['id'] ); ?>" data-attribute-target="nok-top-navigation"
             aria-expanded="false" aria-haspopup="true">
	<?php else: ?>
        <div data-unsets-class="sidebar-open" data-toggle-event="hover" data-class-target="nok-top-navigation">
	<?php endif; ?>

	<?= MenuManager::render_menu_link( $item, [ 'extra_classes' => $extra_classes ] ); ?>

        </div>
<?php endforeach; ?>
