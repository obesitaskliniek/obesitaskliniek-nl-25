<?php
// inc/Navigation/MenuManager.php

namespace NOK2025\V1\Navigation;

class MenuManager {

	public function __construct() {
		// Constructor reserved for future dependencies
	}

	public function register_hooks(): void {
		// Direct call during theme setup - no hook needed
		$this->register_menus();
	}

	/**
	 * Register theme menu locations
	 */
	public function register_menus(): void {
		register_nav_menus( [
			'primary'        => __( 'Primary Navigation', THEME_TEXT_DOMAIN ),
			'mobile_primary' => __( 'Mobile Primary Navigation', THEME_TEXT_DOMAIN ),
			'footer'         => __( 'Footer Navigation', THEME_TEXT_DOMAIN ),
		] );
	}

	/**
	 * Get menu items as hierarchical array
	 *
	 * @param string $location Menu location
	 *
	 * @return array Hierarchical menu structure
	 */
	public function get_menu_tree( string $location ): array {
		$locations = get_nav_menu_locations();

		if ( ! isset( $locations[ $location ] ) ) {
			return [];
		}

		$menu = wp_get_nav_menu_object( $locations[ $location ] );

		if ( ! $menu ) {
			return [];
		}

		$menu_items = wp_get_nav_menu_items( $menu->term_id );

		if ( ! $menu_items ) {
			return [];
		}

		return $this->build_menu_tree( $menu_items );
	}

	/**
	 * Build hierarchical menu tree from flat array
	 *
	 * @param array $items Flat array of menu items
	 * @param int $parent_id Parent item ID
	 *
	 * @return array
	 */
	private function build_menu_tree( array $items, int $parent_id = 0 ): array {
		$branch = [];

		foreach ( $items as $item ) {
			if ( $item->menu_item_parent == $parent_id ) {
				$children = $this->build_menu_tree( $items, $item->ID );

				$branch[] = [
					'id'                  => $item->ID,
					'title'               => $item->title,
					'url'                 => $item->url,
					'classes'             => $item->classes,
					'target'              => $item->target,
					'attr_title'          => $item->attr_title,
					'description'         => $item->description,
					'object_id'           => $item->object_id,
					'is_current'          => in_array( 'current-menu-item', $item->classes ),
					'is_current_ancestor' => in_array( 'current-menu-ancestor', $item->classes ),
					'has_children'        => ! empty( $children ),
					'children'            => $children,
				];
			}
		}

		return $branch;
	}

	/**
	 * Render desktop menu bar (top-level items only)
	 *
	 * @param string $location Menu location
	 * @param array $context Additional variables to pass to template
	 */
	public function render_desktop_menu_bar( string $location = 'primary', array $context = [] ): void {
		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) && ! current_user_can( 'manage_options' ) ) {
			return; // Silent fail for non-admins
		}

		$this->load_template( 'desktop-menu-bar', [
			                                          'menu_items' => $menu_items,
			                                          'location'   => $location,
		                                          ] + $context );
	}

	/**
	 * Render desktop dropdown (all submenus)
	 *
	 * @param string $location Menu location
	 * @param array $context Additional variables to pass to template
	 */
	public function render_desktop_dropdown( string $location = 'primary', array $context = [] ): void {
		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) ) {
			return; // Silent fail - dropdown only shows if menu exists
		}

		$this->load_template( 'desktop-dropdown', [
			                                          'menu_items' => $menu_items,
			                                          'location'   => $location,
		                                          ] + $context );
	}

	/**
	 * Render mobile carousel (full structure)
	 *
	 * @param string $location Menu location (falls back to primary if not set)
	 * @param array $context Additional variables to pass to template
	 */
	public function render_mobile_carousel( string $location = 'mobile_primary', array $context = [] ): void {
		// Fallback to primary if mobile_primary not assigned
		if ( ! has_nav_menu( $location ) && has_nav_menu( 'primary' ) ) {
			$location = 'primary';
		}

		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) && ! current_user_can( 'manage_options' ) ) {
			return; // Silent fail for non-admins
		}

		$this->load_template( 'mobile-menu', [
			                                     'menu_items' => $menu_items,
			                                     'location'   => $location,
		                                     ] + $context );
	}

	/**
	 * Load navigation template
	 *
	 * @param string $template_name Template filename without .php
	 * @param array $context Variables to extract into template scope
	 */
	private function load_template( string $template_name, array $context = [] ): void {
		$template_path = get_template_directory() . "/template-parts/navigation/{$template_name}.php";

		if ( ! file_exists( $template_path ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo "<!-- Navigation template missing: {$template_name}.php -->";
			}

			return;
		}

		// Extract context variables into template scope
		extract( $context, EXTR_SKIP );

		include $template_path;
	}

	/**
	 * Fallback menu when no menu is assigned (for use in templates)
	 */
	public function render_fallback_menu(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="nok-nav-menu-items">';
		echo '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" class="nok-nav-menu-item">';
		echo __( 'Set up navigation menu', THEME_TEXT_DOMAIN );
		echo '</a>';
		echo '</div>';
	}
}