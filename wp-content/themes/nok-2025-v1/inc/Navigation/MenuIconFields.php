<?php
// inc/Navigation/MenuIconFields.php

namespace NOK2025\V1\Navigation;

use NOK2025\V1\Assets;

/**
 * MenuIconFields - Add icon selection to WordPress menu items
 *
 * Adds two custom fields to the native WordPress menu editor:
 * - Icon: select from the theme's SVG icon library
 * - Display mode: show icon alongside text or replace text entirely
 *
 * Uses core hooks introduced in WordPress 5.4:
 * - wp_nav_menu_item_custom_fields (render fields in admin)
 * - wp_update_nav_menu_item (save meta on save)
 * - wp_setup_nav_menu_item (attach meta to item objects)
 *
 * @package NOK2025\V1\Navigation
 */
class MenuIconFields {

	/** @var string Meta key for icon name */
	private const META_ICON = '_nok_menu_icon';

	/** @var string Meta key for display mode */
	private const META_DISPLAY = '_nok_menu_icon_display';

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'render_fields' ], 10, 2 );
		add_action( 'wp_update_nav_menu_item', [ $this, 'save_fields' ], 10, 2 );
		add_filter( 'wp_setup_nav_menu_item', [ $this, 'attach_meta' ] );
	}

	/**
	 * Render icon fields in the menu editor
	 *
	 * @param string $item_id Menu item ID (numeric string)
	 * @param \WP_Post $menu_item Menu item data object
	 *
	 * @return void
	 */
	public function render_fields( $item_id, $menu_item ): void {
		$current_icon    = get_post_meta( $menu_item->ID, self::META_ICON, true );
		$current_display = get_post_meta( $menu_item->ID, self::META_DISPLAY, true );

		// Cache icons list across menu items to avoid repeated filesystem reads
		static $icons = null;
		if ( $icons === null ) {
			$icons = Assets::getIconsForAdmin();
		}
		?>
		<p class="field-nok-icon description description-wide">
			<label for="edit-menu-item-nok-icon-<?= esc_attr( $item_id ); ?>">
				<?= esc_html__( 'Icon', THEME_TEXT_DOMAIN ); ?>
			</label>
			<select id="edit-menu-item-nok-icon-<?= esc_attr( $item_id ); ?>"
			        name="menu-item-nok-icon[<?= esc_attr( $item_id ); ?>]"
			        class="widefat">
				<option value=""><?= esc_html__( '— Geen icoon —', THEME_TEXT_DOMAIN ); ?></option>
				<?php
				$category_labels = [
					'ui'  => __( 'UI Icons', THEME_TEXT_DOMAIN ),
					'nok' => __( 'NOK Icons', THEME_TEXT_DOMAIN ),
				];

				foreach ( $category_labels as $category => $label ):
					if ( empty( $icons[ $category ] ) ) {
						continue;
					}
					?>
					<optgroup label="<?= esc_attr( $label ); ?>">
						<?php foreach ( $icons[ $category ] as $name => $svg ):
							// $name includes prefix (e.g., 'ui_telefoon')
							$display_name = str_replace( [ 'ui_', 'nok_' ], '', $name );
							$display_name = str_replace( [ '-', '_' ], ' ', $display_name );
							?>
							<option value="<?= esc_attr( $name ); ?>"
								<?php selected( $current_icon, $name ); ?>>
								<?= esc_html( ucfirst( $display_name ) ); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="field-nok-icon-display description description-wide">
			<label for="edit-menu-item-nok-icon-display-<?= esc_attr( $item_id ); ?>">
				<?= esc_html__( 'Icon weergave', THEME_TEXT_DOMAIN ); ?>
			</label>
			<select id="edit-menu-item-nok-icon-display-<?= esc_attr( $item_id ); ?>"
			        name="menu-item-nok-icon-display[<?= esc_attr( $item_id ); ?>]"
			        class="widefat">
				<option value="" <?php selected( $current_display, '' ); ?>>
					<?= esc_html__( 'Naast tekst (standaard)', THEME_TEXT_DOMAIN ); ?>
				</option>
				<option value="replace" <?php selected( $current_display, 'replace' ); ?>>
					<?= esc_html__( 'Vervangt tekst (alleen icoon)', THEME_TEXT_DOMAIN ); ?>
				</option>
			</select>
		</p>
		<?php
	}

	/**
	 * Save icon meta fields when menu item is updated
	 *
	 * @param int $menu_id Nav menu ID
	 * @param int $menu_item_db_id Menu item post ID
	 *
	 * @return void
	 */
	public function save_fields( $menu_id, $menu_item_db_id ): void {
		// Icon name
		$icon = $_POST['menu-item-nok-icon'][ $menu_item_db_id ] ?? '';
		$icon = sanitize_text_field( $icon );

		if ( $icon ) {
			update_post_meta( $menu_item_db_id, self::META_ICON, $icon );
		} else {
			delete_post_meta( $menu_item_db_id, self::META_ICON );
		}

		// Display mode
		$display = $_POST['menu-item-nok-icon-display'][ $menu_item_db_id ] ?? '';
		$display = in_array( $display, [ 'replace' ], true ) ? $display : '';

		if ( $display ) {
			update_post_meta( $menu_item_db_id, self::META_DISPLAY, $display );
		} else {
			delete_post_meta( $menu_item_db_id, self::META_DISPLAY );
		}
	}

	/**
	 * Attach icon meta to menu item objects during setup
	 *
	 * This runs when WordPress loads menu items via wp_get_nav_menu_items(),
	 * making the icon data available as properties on the WP_Post object.
	 *
	 * @param object $menu_item Menu item post object
	 *
	 * @return object Modified menu item with icon properties
	 */
	public function attach_meta( object $menu_item ): object {
		if ( ! isset( $menu_item->ID ) ) {
			return $menu_item;
		}

		$menu_item->_nok_menu_icon         = get_post_meta( $menu_item->ID, self::META_ICON, true ) ?: '';
		$menu_item->_nok_menu_icon_display = get_post_meta( $menu_item->ID, self::META_DISPLAY, true ) ?: '';

		return $menu_item;
	}
}
