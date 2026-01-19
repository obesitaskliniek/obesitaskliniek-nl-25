<?php
// inc/Navigation/MenuManager.php

namespace NOK2025\V1\Navigation;

/**
 * MenuManager - WordPress menu registration and hierarchical rendering
 *
 * Handles navigation menu management:
 * - Registers menu locations (primary, mobile, footer)
 * - Builds hierarchical menu tree from flat WordPress menu items
 * - Provides separate rendering for desktop bar, dropdown, and mobile carousel
 * - Template-based rendering with context variables
 *
 * @example Basic usage in theme setup
 * $menu_manager = new MenuManager();
 * $menu_manager->register_hooks();
 *
 * @example Render primary navigation in header template
 * $menu_manager->render_desktop_menu_bar('primary');
 * $menu_manager->render_desktop_dropdown('primary');
 *
 * @example Render mobile menu
 * $menu_manager->render_mobile_carousel('mobile_primary');
 *
 * @package NOK2025\V1\Navigation
 */
class MenuManager {

	public function __construct() {
		// Constructor reserved for future dependencies
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Direct call during theme setup - no hook needed
		$this->register_menus();
	}

	/**
	 * Register theme menu locations
	 *
	 * @return void
	 */
	public function register_menus(): void {
		register_nav_menus( [
			'primary'        => __( 'Primary Navigation', THEME_TEXT_DOMAIN ),
			'mobile_primary' => __( 'Mobile Primary Navigation', THEME_TEXT_DOMAIN ),
			'top_row'        => __( 'Desktop Top Row', THEME_TEXT_DOMAIN ),
			'footer'         => __( 'Footer Navigation', THEME_TEXT_DOMAIN ),
		] );
	}

	/**
	 * Get menu items as hierarchical array
	 *
	 * @param string $location Menu location
	 * @return array<int, array> Hierarchical menu structure
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
	 * @return array<int, array> Hierarchical array with nested children
	 */
	private function build_menu_tree( array $items, int $parent_id = 0 ): array {
		$branch = [];

		foreach ( $items as $item ) {
			if ( $item->menu_item_parent == $parent_id ) {
				$children = $this->build_menu_tree( $items, $item->ID );
				$classes  = is_array( $item->classes ) ? $item->classes : [];

				// Detect header items: URL is # or empty, or has menu-header class
				$is_header = in_array( $item->url, [ '#', '' ], true )
				             || in_array( 'menu-header', $classes, true );

				// Detect popup triggers: URL format #popup-{name} (e.g., #popup-search, #popup-bmi-calculator)
				$is_popup_trigger = false;
				$popup_id         = null;

				if ( preg_match( '/^#(popup-.+)$/', $item->url, $matches ) ) {
					$is_popup_trigger = true;
					$popup_id         = sanitize_html_class( $matches[1] );
				}

				// Determine current page status
				$is_current          = $this->is_menu_item_current( $item );
				$is_current_ancestor = $this->is_menu_item_ancestor( $item, $children );

				$branch[] = [
					'id'                  => $item->ID,
					'title'               => $item->title,
					'url'                 => $item->url,
					'classes'             => $classes,
					'target'              => $item->target,
					'attr_title'          => $item->attr_title,
					'description'         => $item->description,
					'object_id'           => $item->object_id,
					'is_current'          => $is_current,
					'is_current_ancestor' => $is_current_ancestor,
					'is_header'           => $is_header,
					'has_children'        => ! empty( $children ),
					'is_popup_trigger'    => $is_popup_trigger,
					'popup_id'            => $popup_id,
					'children'            => $children,
				];
			}
		}

		return $branch;
	}

	/**
	 * Check if menu item points to current page
	 *
	 * @param object $item Menu item object
	 * @return bool True if item links to current page
	 */
	private function is_menu_item_current( object $item ): bool {
		$current_id = get_queried_object_id();

		// Check by object ID (works for posts, pages, custom post types)
		if ( $item->object_id && (int) $item->object_id === $current_id ) {
			return true;
		}

		// Check by URL comparison for custom links
		if ( $item->type === 'custom' && $item->url ) {
			$current_url  = home_url( $_SERVER['REQUEST_URI'] ?? '' );
			$current_path = wp_parse_url( $current_url, PHP_URL_PATH );
			$item_path    = wp_parse_url( $item->url, PHP_URL_PATH );

			// Normalize: compare paths, ignore trailing slashes
			if ( $current_path && $item_path ) {
				return strcasecmp(
					untrailingslashit( $current_path ),
					untrailingslashit( $item_path )
				) === 0;
			}
		}

		return false;
	}

	/**
	 * Check if menu item is ancestor of current page
	 *
	 * @param object $item Menu item object
	 * @param array $children Processed children array
	 * @return bool True if item is ancestor of current page
	 */
	private function is_menu_item_ancestor( object $item, array $children ): bool {
		foreach ( $children as $child ) {
			if ( $child['is_current'] || $child['is_current_ancestor'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render desktop menu bar (top-level items only)
	 *
	 * @param string $location Menu location
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
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
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
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
	 * Render desktop top row (utility links above main nav)
	 *
	 * Flat menu for secondary links like "Voor verwijzers", "Werken bij", etc.
	 * Supports popup triggers via #popup-{id} URL format.
	 *
	 * @param string $location Menu location
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
	 */
	public function render_top_row( string $location = 'top_row', array $context = [] ): void {
		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) && ! current_user_can( 'manage_options' ) ) {
			return; // Silent fail for non-admins
		}

		$this->load_template( 'top-row', [
			'menu_items' => $menu_items,
			'location'   => $location,
		] + $context );
	}

	/**
	 * Render mobile carousel (full structure)
	 *
	 * @param string $location Menu location (falls back to primary if not set)
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
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
	 * Render footer columns with accordion behavior
	 *
	 * Top-level items with is_header flag render as column headers.
	 * Their children render as links beneath. On mobile, headers become
	 * accordion toggles.
	 *
	 * @param string $location Menu location
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
	 */
	public function render_footer_columns( string $location = 'footer', array $context = [] ): void {
		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) && ! current_user_can( 'manage_options' ) ) {
			return; // Silent fail for non-admins
		}

		$this->load_template( 'footer-columns', [
			'menu_items' => $menu_items,
			'location'   => $location,
		] + $context );
	}

	/**
	 * Load navigation template
	 *
	 * @param string $template_name Template filename without .php
	 * @param array<string, mixed> $context Variables to extract into template scope
	 * @return void
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
	 *
	 * @return void
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