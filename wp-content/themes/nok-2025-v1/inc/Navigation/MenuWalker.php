<?php
// inc/Navigation/MenuWalker.php

namespace NOK2025\V1\Navigation;

/**
 * MenuWalker - Custom WordPress nav menu walker for desktop and mobile layouts
 *
 * Extends Walker_Nav_Menu to provide context-specific menu HTML:
 * - Desktop: Top-level items in grid, submenus in dropdown
 * - Mobile: Carousel-based navigation with slide transitions
 *
 * Key features:
 * - Context-aware output (desktop vs mobile)
 * - Submenu ID generation for carousel anchors
 * - Back button injection for mobile navigation
 * - Active state classes for current pages
 *
 * @example Desktop menu
 * wp_nav_menu([
 *     'walker' => new MenuWalker('desktop'),
 *     'theme_location' => 'primary'
 * ]);
 *
 * @example Mobile menu with carousel
 * wp_nav_menu([
 *     'walker' => new MenuWalker('mobile'),
 *     'theme_location' => 'mobile_primary'
 * ]);
 *
 * @package NOK2025\V1\Navigation
 */
class MenuWalker extends \Walker_Nav_Menu {
	private string $context; // 'desktop' or 'mobile'
	private array $parent_items = [];

	public function __construct(string $context = 'desktop') {
		$this->context = $context;
	}

	/**
	 * Get indentation string based on depth
	 */
	private function indent(int $depth): string {
		return str_repeat('    ', $depth);
	}

	/**
	 * Start level - opening wrapper for menu level
	 */
	public function start_lvl(&$output, $depth = 0, $args = null): void {
		$indent = $this->indent($depth + 1);

		if ($this->context === 'mobile' && $depth === 0) {
			// Mobile: Start a new carousel slide for submenus
			$output .= "\n{$indent}<div class=\"nok-nav-carousel__slide\">";
			$indent .= '    ';
		}

		$output .= "\n{$indent}<div class=\"nok-nav-menu-items\"";

		// Add ID for submenu (used by mobile carousel)
		if ($depth === 0 && !empty($this->parent_items[$depth])) {
			$parent_slug = sanitize_title($this->parent_items[$depth]);
			$output .= ' id="submenu-' . esc_attr($parent_slug) . '"';
		}

		$output .= '>';

		// Add back button for mobile submenus
		if ($this->context === 'mobile' && $depth === 0) {
			$output .= "\n{$indent}    <a href=\"#topmenu\" class=\"nok-nav-menu-item nok-nav-menu-item__back\">";
			$output .= '&laquo; ' . __('Terug naar overzicht', THEME_TEXT_DOMAIN);
			$output .= '</a>';
		}
	}

	/**
	 * End level - closing wrapper for menu level
	 */
	public function end_lvl(&$output, $depth = 0, $args = null): void {
		$indent = $this->indent($depth + 1);
		$output .= "\n{$indent}</div>";

		if ($this->context === 'mobile' && $depth === 0) {
			$output .= "\n{$indent}</div>"; // Close carousel slide
		}
	}

	/**
	 * Start element - opening tag and content for menu item
	 */
	public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void {
		$indent = $this->indent($depth + 2);

		$classes = empty($item->classes) ? [] : (array) $item->classes;
		$classes[] = 'nok-nav-menu-item';

		// Add active class for current page
		if (in_array('current-menu-item', $classes) || in_array('current-menu-ancestor', $classes)) {
			$classes[] = 'nok-nav-menu-item--active';
		}

		$class_string = implode(' ', array_map('esc_attr', $classes));

		// Determine URL
		$url = !empty($item->url) ? $item->url : '#';

		// If item has children and is top-level, link to submenu anchor (mobile) or use # (desktop)
		if ($depth === 0 && in_array('menu-item-has-children', $classes)) {
			$this->parent_items[$depth] = $item->title;

			if ($this->context === 'mobile') {
				$url = '#submenu-' . sanitize_title($item->title);
			} elseif ($this->context === 'desktop') {
				// Desktop parent items might just toggle dropdown
				$url = $item->url ?: '#';
			}
		}

		// Desktop top-level items get wrapped in div
		if ($this->context === 'desktop' && $depth === 0) {
			$output .= "\n" . $this->indent($depth + 1) . '<div>';
		}

		$output .= "\n{$indent}<a href=\"" . esc_url($url) . '" class="' . $class_string . '">';
		$output .= esc_html($item->title);
		$output .= '</a>';
	}

	/**
	 * End element - closing tag for menu item
	 */
	public function end_el(&$output, $item, $depth = 0, $args = null): void {
		if ($this->context === 'desktop' && $depth === 0) {
			$output .= "\n" . $this->indent($depth + 1) . '</div>';
		}
	}

	/**
	 * Before walker output
	 */
	public function walk($elements, $max_depth, ...$args): string {
		$output = '';

		if ($this->context === 'mobile') {
			// Mobile: Wrap everything in carousel structure
			$output .= "\n                    <div class=\"nok-nav-carousel__inner nok-text-darkerblue nok-dark-text-white\">";
			$output .= "\n                        <div class=\"nok-nav-carousel__slide\">";
			$output .= "\n                            <div class=\"nok-nav-menu-items\" id=\"topmenu\">";
		}
		// Desktop doesn't wrap - menu items go directly into template's wrapper

		$output .= parent::walk($elements, $max_depth, ...$args);

		if ($this->context === 'mobile') {
			$output .= "\n                            </div>"; // Close topmenu
			$output .= "\n                        </div>"; // Close first slide
			$output .= "\n                    </div>"; // Close carousel inner
		}

		return $output;
	}
}