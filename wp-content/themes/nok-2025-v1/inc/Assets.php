<?php
namespace NOK2025\V1;

/**
 * Assets - SVG icon management with filesystem and legacy fallback
 *
 * Provides centralized icon management:
 * - Loads SVG icons from filesystem with caching
 * - Supports prefixed naming convention (ui_, nok_)
 * - Injects CSS classes and attributes into SVG elements
 * - Falls back to legacy inline icons during transition
 *
 * @example Basic icon usage
 * echo Assets::getIcon('ui_arrow-right', 'text-primary');
 *
 * @example Icon with custom attributes
 * echo Assets::getIcon('nok_calendar', 'icon-lg', ['width' => '32', 'height' => '32']);
 *
 * @example Get icons by category for admin UI
 * $uiIcons = Assets::getIconsByCategory('ui');
 * foreach ($uiIcons as $name => $svg) {
 *     echo "<div>$name: $svg</div>";
 * }
 *
 * @package NOK2025\V1
 */
class Assets {
	/** @var array<string, string> Legacy icons during transition */
	private array $legacyIcons = [
		'star'               => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="nok-icon %s" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/> </svg>',
		// ... rest of legacy icons
	];

	/** @var array<string, string>|null Cached filesystem icons */
	private static ?array $iconCache = null;

	/** @var string Icons directory path */
	private static string $iconsPath;

	public function __construct() {
		self::$iconsPath = get_template_directory() . '/assets/icons/';
	}

	/**
	 * Get inline SVG icon with optional attributes
	 *
	 * @param string $name Icon name WITH prefix (e.g., 'ui_arrow-right', 'nok_calendar')
	 * @param string $class CSS classes to apply
	 * @param array $attrs Additional SVG attributes ['width' => '24', 'height' => '24']
	 * @return string Inline SVG or empty string if not found
	 */
	public static function getIcon(string $name, string $class = '', array $attrs = []): string {
		$instance = new self();

		// Initialize cache on first call
		if (self::$iconCache === null) {
			self::$iconCache = $instance->loadIconsFromFilesystem();
		}

		// Attempt filesystem lookup
		$svg = self::$iconCache[$name] ?? null;

		// Fallback to legacy if not found
		if ($svg === null) {
			$svg = $instance->legacyIcons[$name] ?? '';
			if ($svg && $class) {
				return sprintf($svg, $class);
			}
			return $svg;
		}

		// Inject attributes into filesystem SVG
		return $instance->injectSvgAttributes($svg, $class, $attrs);
	}

	/**
	 * Get icon by category
	 *
	 * @param string $category 'ui' or 'nok'
	 * @return array<string, string> Icon name (without prefix) => SVG content
	 */
	public static function getIconsByCategory(string $category): array {
		$instance = new self();

		if (self::$iconCache === null) {
			self::$iconCache = $instance->loadIconsFromFilesystem();
		}

		$prefix = strtolower($category) . '_';
		$results = [];

		foreach (self::$iconCache as $name => $svg) {
			if (str_starts_with($name, $prefix)) {
				// Return without prefix for convenience
				$results[substr($name, strlen($prefix))] = $svg;
			}
		}

		return $results;
	}

	/**
	 * Load all icons from filesystem
	 *
	 * @return array<string, string> Icon name => SVG content
	 */
	private function loadIconsFromFilesystem(): array {
		$icons = [];

		if (!is_dir(self::$iconsPath)) {
			return $icons;
		}

		foreach (glob(self::$iconsPath . '*.svg') as $filepath) {
			$filename = basename($filepath, '.svg');

			// Use full filename WITH prefix as key
			$icons[$filename] = file_get_contents($filepath);
		}

		return $icons;
	}

	/**
	 * Inject class and attributes into SVG
	 *
	 * @param string $svg Raw SVG content
	 * @param string $class CSS classes to add
	 * @param array<string, string> $attrs Additional attributes
	 * @return string Modified SVG with injected attributes
	 */
	private function injectSvgAttributes(string $svg, string $class, array $attrs): string {
		$dom = new \DOMDocument();
		@$dom->loadXML($svg);

		$svgElement = $dom->getElementsByTagName('svg')->item(0);
		if (!$svgElement) {
			return $svg;
		}

		// Inject class
		if ($class) {
			$existing = $svgElement->getAttribute('class');
			$svgElement->setAttribute('class', trim("$existing nok-icon $class"));
		} else {
			$svgElement->setAttribute('class', 'nok-icon');
		}

		// Inject additional attributes
		foreach ($attrs as $key => $value) {
			$svgElement->setAttribute($key, $value);
		}

		return $dom->saveXML($svgElement);
	}

	/**
	 * Clear icon cache (useful for development)
	 */
	public static function clearCache(): void {
		self::$iconCache = null;
	}

	/**
	 * Get all available icons grouped by category
	 *
	 * @return array<string, array<string, string>> ['ui' => ['name' => 'svg'], 'nok' => ['name' => 'svg']]
	 */
	public static function getIconsForAdmin(): array {
		$instance = new self();

		if (!is_dir(self::$iconsPath)) {
			return [];
		}

		$icons = ['ui' => [], 'nok' => [], 'logo' => []];

		foreach (glob(self::$iconsPath . '*.svg') as $filepath) {
			$filename = basename($filepath, '.svg');
			$svg = file_get_contents($filepath);

			// Determine category from prefix
			if (str_starts_with($filename, 'ui_')) {
				$icons['ui'][$filename] = $svg;
			} elseif (str_starts_with($filename, 'nok_')) {
				$icons['nok'][$filename] = $svg;
			} elseif (str_starts_with($filename, 'logo_')) {
				$icons['logo'][$filename] = $svg;
			}
		}

		return $icons;
	}
}