<?php
// inc/Navigation/MenuManager.php

namespace NOK2025\V1\Navigation;

use NOK2025\V1\Assets;

/**
 * MenuManager - WordPress menu registration and hierarchical rendering
 *
 * Handles navigation menu management:
 * - Registers menu locations (primary, mobile, mobile drawer footer, footer)
 * - Builds hierarchical menu tree from flat WordPress menu items
 * - Provides separate rendering for desktop bar, dropdown, and mobile carousel
 * - Template-based rendering with context variables
 * - Shared link rendering with icon support
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
			'primary'               => __( 'Primary Navigation', THEME_TEXT_DOMAIN ),
			'mobile_primary'        => __( 'Mobile Primary Navigation', THEME_TEXT_DOMAIN ),
			'mobile_drawer_footer'  => __( 'Mobile Drawer Footer', THEME_TEXT_DOMAIN ),
			'top_row'               => __( 'Desktop Top Row', THEME_TEXT_DOMAIN ),
			'footer'                => __( 'Footer Navigation', THEME_TEXT_DOMAIN ),
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
					'icon'                => $item->_nok_menu_icon ?? '',
					'icon_display'        => $item->_nok_menu_icon_display ?? '',
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
	 * Render mobile drawer footer (utility links at bottom of mobile sidebar)
	 *
	 * Flat menu for links like "Werken bij", "BMI berekenen", etc.
	 * Popup triggers close the sidebar before opening the popup.
	 * No fallback to another menu location — if unassigned, admins see a setup link.
	 *
	 * @param string $location Menu location
	 * @param array<string, mixed> $context Additional variables to pass to template
	 * @return void
	 */
	public function render_mobile_drawer_footer( string $location = 'mobile_drawer_footer', array $context = [] ): void {
		$menu_items = $this->get_menu_tree( $location );

		if ( empty( $menu_items ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->load_template( 'mobile-drawer-footer', [
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
	 * Render a single menu item <a> tag with all standard attributes
	 *
	 * Centralizes the repeated link-building logic across navigation templates:
	 * class merging, HTML attributes, popup triggers, icon rendering, and escaping.
	 *
	 * @param array{
	 *     id: int,
	 *     title: string,
	 *     url: string,
	 *     classes: string[],
	 *     target: string,
	 *     attr_title: string,
	 *     is_current: bool,
	 *     is_current_ancestor: bool,
	 *     is_popup_trigger: bool,
	 *     popup_id: string|null,
	 *     has_children: bool,
	 *     icon: string,
	 *     icon_display: string
	 * } $item Menu item from build_menu_tree()
	 * @param array{
	 *     extra_classes?: string[],
	 *     url_override?: string,
	 *     popup_unsets_sidebar?: bool,
	 *     extra_attrs?: string
	 * } $options Rendering options:
	 *     - extra_classes:         Additional CSS classes (e.g., 'is-current-page')
	 *     - url_override:          Override the item URL (e.g., for mobile submenu anchors)
	 *     - popup_unsets_sidebar:  If true, popup triggers also unset sidebar-open (mobile)
	 *     - extra_attrs:           Raw HTML attribute string to append
	 *
	 * Button mode: When 'nok-button' is among the item's CSS classes (set in WP menu admin),
	 * the item renders as a button — the nok-nav-menu-item base class is skipped, and
	 * role="button" + tabindex="0" are added. All other functionality (icons, popups) still works.
	 *
	 * @return string Complete <a> tag HTML
	 */
	public static function render_menu_link( array $item, array $options = [] ): string {
		$item_classes = ! empty( $item['classes'] ) ? array_filter( $item['classes'] ) : [];
		$is_button    = in_array( 'nok-button', $item_classes, true );

		if ( $is_button ) {
			// Button items use their menu-admin CSS classes directly (no nav-menu-item base)
			$classes = $item_classes;
		} else {
		$classes = [ 'nok-nav-menu-item' ];

		if ( $item['is_current'] || $item['is_current_ancestor'] ) {
			$classes[] = 'nok-nav-menu-item--active';
		}

		if ( ! empty( $item['has_children'] ) ) {
			$classes[] = 'nok-nav-menu-item--has-children';
		}

			if ( ! empty( $item_classes ) ) {
				$classes = array_merge( $classes, $item_classes );
			}
		}

		if ( ! empty( $options['extra_classes'] ) ) {
			$classes = array_merge( $classes, $options['extra_classes'] );
		}

		// Determine URL
		if ( isset( $options['url_override'] ) ) {
			$url = $options['url_override'];
		} elseif ( ! empty( $item['is_popup_trigger'] ) ) {
			$url = '#';
		} else {
			$url = $item['url'] ?: '#';
		}

		// Build HTML attributes
		$attrs = [];
		if ( ! empty( $item['target'] ) && empty( $item['is_popup_trigger'] ) && ! isset( $options['url_override'] ) ) {
			$attrs[] = 'target="' . esc_attr( $item['target'] ) . '"';
		}
		$title_attr = ! empty( $item['attr_title'] ) ? $item['attr_title'] : $item['title'];
		$attrs[]    = 'title="' . esc_attr( $title_attr ) . '"';

		if ( $is_button ) {
			$attrs[] = 'role="button"';
			$attrs[] = 'tabindex="0"';
		}

		// Popup trigger data-attributes
		if ( ! empty( $item['is_popup_trigger'] ) && ! empty( $item['popup_id'] ) ) {
			$popup_class_action = ! empty( $options['popup_unsets_sidebar'] )
				? 'data-unsets-class="sidebar-open"'
				: 'data-toggles-class="popup-open"';

			$attrs[] = $popup_class_action . ' data-class-target="nok-top-navigation"'
			           . ' data-toggle-event="click"'
			           . ( ! empty( $options['popup_unsets_sidebar'] ) ? ' data-toggles-class="popup-open"' : '' )
			           . ' data-toggles-attribute="data-state" data-toggles-attribute-value="open"'
			           . ' data-attribute-target="#' . esc_attr( $item['popup_id'] ) . '"';
		}

		if ( ! empty( $options['extra_attrs'] ) ) {
			$attrs[] = $options['extra_attrs'];
		}

		$class_string = implode( ' ', array_map( 'esc_attr', $classes ) );
		$attr_string  = ! empty( $attrs ) ? ' ' . implode( ' ', $attrs ) : '';

		// Build link content: icon + title
		$content = self::render_menu_link_content( $item );

		return '<a href="' . esc_url( $url ) . '" class="' . $class_string . '"' . $attr_string . '>'
		       . $content
		       . '</a>';
	}

	/**
	 * Render the inner content of a menu link (icon + title text)
	 *
	 * @param array $item Menu item from build_menu_tree()
	 *
	 * @return string Escaped title with optional icon SVG
	 */
	private static function render_menu_link_content( array $item ): string {
		$icon_html = '';
		$icon_name = $item['icon'] ?? '';

		if ( $icon_name ) {
			$icon_html = Assets::getIcon( $icon_name, 'nok-nav-menu-icon' );
		}

		$display = $item['icon_display'] ?? '';
		$title   = esc_html( $item['title'] );

		if ( $icon_html && $display === 'replace' ) {
			// Icon replaces visible text; keep text for screen readers
			return $icon_html . '<span class="screen-reader-text">' . $title . '</span>';
		}

		if ( $icon_html ) {
			// Icon alongside text (default)
			return $icon_html . ' ' . $title;
		}

		return $title;
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