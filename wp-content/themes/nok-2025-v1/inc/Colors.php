<?php
// inc/Colors.php

namespace NOK2025\V1;

/**
 * Colors - Centralized color palette provider
 *
 * Provides standardized color palettes for use in page parts and blocks.
 * Each palette is an array of color options with labels and CSS class values.
 *
 * This replaces manually duplicated select() options across templates,
 * allowing centralized management of available color combinations.
 *
 * Palette entries may include optional dark mode fields:
 * - `darkValue`: CSS classes for dark mode (e.g., 'nok-dark-bg-darkestblue nok-dark-text-white')
 * - `darkLabel`: Human-readable label for the dark option in the editor (e.g., 'Donkerstblauw')
 * - `darkColor`: Hex color for the dark mode swatch (auto-derived from darkValue if omitted)
 *
 * @example Using a color palette in a page part template
 * // In PHPDoc Custom Fields header:
 * // * - bg_color:color-selector(backgrounds)!default(nok-bg-darkblue)
 *
 * @example Getting a palette programmatically
 * $palette = Colors::getPalette('backgrounds');
 * // Returns: [['label' => 'Donkerblauw', 'value' => 'nok-bg-darkblue', 'color' => '#14477c'], ...]
 *
 * @package NOK2025\V1
 */
class Colors {

	/**
	 * Color definitions mapping class names to display colors
	 *
	 * These are the actual colors used to render swatches in the editor.
	 * Values should match what's defined in _nok-colors-v2.scss
	 *
	 * @var array<string, string>
	 */
	private const COLOR_DEFINITIONS = [
		'nok-bg-lightblue'           => '#00b0e4',
		'nok-bg-darkblue'            => '#14477c',
		'nok-bg-darkblue--lighter'   => '#1b5a9a',
		'nok-bg-darkblue--darker'    => '#0e3562',
		'nok-bg-darkerblue'          => '#0b2355',
		'nok-bg-darkerblue--darker'  => '#071840',
		'nok-bg-darkestblue'         => '#00132f',
		'nok-bg-yellow'              => '#ffd41f',
		'nok-bg-greenblue'           => '#35aba5',
		'nok-bg-green'               => '#54b085',
		'nok-bg-body'                => '#f3f4f9',
		'nok-bg-body--darker'        => '#e5e7ef',
		'nok-bg-body--lighter'       => '#ffffff',
		'nok-bg-white'               => '#ffffff',
		'nok-bg-lightgrey'           => '#cccccc',
		'nok-bg-transparent'         => 'transparent',
		'nok-bg-clinics-blauw'       => '#B6BBD6',
		'nok-bg-clinics-oranje'      => '#C27655',

		'nok-text-lightblue'         => '#00b0e4',
		'nok-text-darkblue'          => '#14477c',
		'nok-text-darkerblue'        => '#0b2355',
		'nok-text-darkestblue'       => '#00132f',
		'nok-text-yellow'            => '#ffd41f',
		'nok-text-greenblue'         => '#35aba5',
		'nok-text-white'             => '#ffffff',
		'nok-text-black'             => '#222222',
		'nok-text-contrast'          => 'inherit', // Special: inherits from parent bg
	];

	/**
	 * Get all available palettes
	 *
	 * @return array<string, array> Palette definitions keyed by palette name
	 */
	public static function getPalettes(): array {
		return [
			'backgrounds'             => self::getBackgroundsPalette(),
			'backgrounds-full'        => self::getBackgroundsFullPalette(),
			'backgrounds-simple'      => self::getBackgroundsSimplePalette(),
			'text'                    => self::getTextPalette(),
			'text-extended'           => self::getTextExtendedPalette(),
			'button-backgrounds'      => self::getButtonBackgroundsPalette(),
			'icon-colors'             => self::getIconColorsPalette(),
			'section-colors'          => self::getSectionColorsPalette(),
			'block-colors'            => self::getBlockColorsPalette(),
			'card-colors'             => self::getCardColorsPalette(),
			'badge-colors'            => self::getBadgeColorsPalette(),
			'quote-block-colors'      => self::getQuoteBlockColorsPalette(),
			'accordion-button-colors' => self::getAccordionButtonColorsPalette(),
			'footer-colors'           => self::getFooterColorsPalette(),
			'step-visual-colors'      => self::getStepVisualColorsPalette(),
		];
	}

	/**
	 * Get a specific palette by name
	 *
	 * @param string $name Palette name
	 * @return array Palette options, empty array if palette not found
	 */
	public static function getPalette( string $name ): array {
		$palettes = self::getPalettes();
		return $palettes[ $name ] ?? [];
	}

	/**
	 * Get palettes formatted for JavaScript/admin consumption
	 *
	 * Passes darkValue and darkColor to JS alongside existing fields.
	 * Computes darkColor from darkValue when not explicitly set.
	 *
	 * @return array Palettes with structure suitable for React components
	 */
	public static function getColorsForAdmin(): array {
		$palettes = self::getPalettes();
		$result   = [];

		foreach ( $palettes as $name => $options ) {
			$result[ $name ] = array_map( function ( $option ) {
				$entry = [
					'label' => $option['label'],
					'value' => $option['value'],
					'color' => $option['color'],
				];

				if ( isset( $option['darkValue'] ) ) {
					$entry['darkValue'] = $option['darkValue'];
					$entry['darkLabel'] = $option['darkLabel'] ?? $option['label'];
					$entry['darkColor'] = $option['darkColor']
						?? self::resolveDarkColor( $option['darkValue'] );
				}

				return $entry;
			}, $options );
		}

		return $result;
	}

	/**
	 * Resolve a dark mode swatch color from dark CSS classes
	 *
	 * Strips the `nok-dark-` prefix and looks up the corresponding light color
	 * in COLOR_DEFINITIONS. This avoids duplicating color hex values.
	 *
	 * @param string $classes Dark mode CSS class string (e.g., 'nok-dark-bg-darkestblue nok-dark-text-white')
	 * @return string Hex color code or 'transparent'
	 */
	public static function resolveDarkColor( string $classes ): string {
		if ( empty( $classes ) ) {
			return 'transparent';
		}

		// Prioritize background classes for swatch color
		$bg_fallback = null;
		foreach ( explode( ' ', $classes ) as $class ) {
			$class = trim( $class );
			if ( empty( $class ) ) {
				continue;
			}

			$light_equivalent = str_replace( 'nok-dark-', 'nok-', $class );
			if ( isset( self::COLOR_DEFINITIONS[ $light_equivalent ] ) ) {
				// Prefer bg classes for the swatch
				if ( str_contains( $class, '-bg-' ) ) {
					return self::COLOR_DEFINITIONS[ $light_equivalent ];
				}
				$bg_fallback ??= self::COLOR_DEFINITIONS[ $light_equivalent ];
			}
		}

		return $bg_fallback ?? 'transparent';
	}

	/**
	 * Common backgrounds palette
	 *
	 * Used for section and block background colors.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getBackgroundsPalette(): array {
		return [
			[
				'label'     => 'Donkerst blauw',
				'value'     => 'nok-bg-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-lightblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-lightblue'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Body (donkerder)',
				'value'     => 'nok-bg-body--darker',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body--darker'],
			],
			[
				'label'     => 'Transparant',
				'value'     => '',
				'color'     => 'transparent',
			],
		];
	}

	/**
	 * Simple backgrounds palette (bg only)
	 *
	 * Used for achtergrondkleur fields in picture-text blocks.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getBackgroundsSimplePalette(): array {
		return [
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Body (donkerder)',
				'value'     => 'nok-bg-body--darker',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body--darker'],
			],
			[
				'label'     => 'Transparant',
				'value'     => '',
				'color'     => 'transparent',
			],
		];
	}

	/**
	 * Extended backgrounds palette with more color options
	 *
	 * Used for block backgrounds within sections where more variety is needed.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getBackgroundsFullPalette(): array {
		return [
			[
				'label'     => 'Donkerst blauw',
				'value'     => 'nok-bg-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
			[
				'label'     => 'Lichter donkerblauw',
				'value'     => 'nok-bg-darkblue--lighter',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue--lighter'],
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-lightblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-lightblue'],
			],
			[
				'label'     => 'Groenblauw',
				'value'     => 'nok-bg-greenblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-greenblue'],
			],
			[
				'label'     => 'Geel',
				'value'     => 'nok-bg-yellow',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-yellow'],
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Transparant',
				'value'     => '',
				'color'     => 'transparent',
			],
		];
	}

	/**
	 * Text colors palette
	 *
	 * Used for text color selections.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getTextPalette(): array {
		return [
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-text-darkerblue'],
				'darkValue' => 'nok-dark-text-white',
				'darkLabel' => 'Witte tekst',
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-text-white'],
			],
			[
				'label'     => 'Zwart',
				'value'     => 'nok-text-black',
				'color'     => self::COLOR_DEFINITIONS['nok-text-black'],
				'darkValue' => 'nok-dark-text-white',
				'darkLabel' => 'Witte tekst',
			],
			[
				'label'     => 'Contrast',
				'value'     => 'nok-text-contrast',
				'color'     => 'inherit',
			],
		];
	}

	/**
	 * Extended text colors palette
	 *
	 * Includes standard text option for picture-text blocks.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getTextExtendedPalette(): array {
		return [
			[
				'label'     => 'Standaard',
				'value'     => 'nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-text-darkerblue'],
				'darkValue' => 'nok-dark-text-white',
				'darkLabel' => 'Witte tekst',
			],
			[
				'label'     => 'Contrast',
				'value'     => 'nok-text-contrast',
				'color'     => 'inherit',
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-text-white'],
			],
			[
				'label'     => 'Zwart',
				'value'     => 'nok-text-black',
				'color'     => self::COLOR_DEFINITIONS['nok-text-black'],
				'darkValue' => 'nok-dark-text-white',
				'darkLabel' => 'Witte tekst',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-text-darkerblue'],
				'darkValue' => 'nok-dark-text-white',
				'darkLabel' => 'Witte tekst',
			],
		];
	}

	/**
	 * Button backgrounds palette
	 *
	 * Combined background + text color classes for buttons.
	 * Color shown in swatch is the background color.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getButtonBackgroundsPalette(): array {
		return [
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
			[
				'label'     => 'Donkerderblauw',
				'value'     => 'nok-bg-darkerblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-lightblue nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-lightblue'],
			],
			[
				'label'     => 'Groenblauw',
				'value'     => 'nok-bg-greenblue nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-greenblue'],
			],
			[
				'label'     => 'Geel',
				'value'     => 'nok-bg-yellow nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-yellow'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
			[
				'label'     => 'Clinics blauw',
				'value'     => 'nok-bg-clinics-blauw nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-clinics-blauw'],
			],
			[
				'label'     => 'Clinics oranje',
				'value'     => 'nok-bg-clinics-oranje nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-clinics-oranje'],
			],
			[
				'label'     => 'Transparant',
				'value'     => 'nok-bg-transparent nok-text-contrast',
				'color'     => 'transparent',
			],
		];
	}

	/**
	 * Icon colors palette
	 *
	 * Used for icon fill/text colors. No dark mode overrides needed —
	 * icons inherit context from their parent section's dark mode.
	 *
	 * @return array<int, array{label: string, value: string, color: string}>
	 */
	private static function getIconColorsPalette(): array {
		return [
			[
				'label' => 'Donkerblauw',
				'value' => 'nok-text-darkblue',
				'color' => self::COLOR_DEFINITIONS['nok-text-darkblue'],
			],
			[
				'label' => 'Lichtblauw',
				'value' => 'nok-text-lightblue',
				'color' => self::COLOR_DEFINITIONS['nok-text-lightblue'],
			],
			[
				'label' => 'Groenblauw',
				'value' => 'nok-text-greenblue',
				'color' => self::COLOR_DEFINITIONS['nok-text-greenblue'],
			],
			[
				'label' => 'Geel',
				'value' => 'nok-text-yellow',
				'color' => self::COLOR_DEFINITIONS['nok-text-yellow'],
			],
			[
				'label' => 'Wit',
				'value' => 'nok-text-white',
				'color' => self::COLOR_DEFINITIONS['nok-text-white'],
			],
			[
				'label' => 'Zwart',
				'value' => 'nok-text-black',
				'color' => self::COLOR_DEFINITIONS['nok-text-black'],
			],
		];
	}

	/**
	 * Section colors palette
	 *
	 * Pre-composed section schemes with background + text combinations.
	 * Used for main section-level colors.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getSectionColorsPalette(): array {
		return [
			[
				'label'     => 'Transparant',
				'value'     => 'nok-bg-body nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
				'darkValue' => 'nok-dark-text-contrast',
				'darkLabel' => 'Contrasttekst',
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Grijs',
				'value'     => 'nok-bg-body--darker gradient-background nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body--darker'],
				'darkValue' => 'nok-dark-text-contrast',
				'darkLabel' => 'Contrasttekst',
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
			[
				'label'     => 'Geel',
				'value'     => 'nok-bg-yellow nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-yellow'],
			],
		];
	}

	/**
	 * Block colors palette
	 *
	 * Combined bg + text for content blocks within sections (accordions, cards, etc.)
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getBlockColorsPalette(): array {
		return [
			[
				'label'     => 'Transparant',
				'value'     => 'nok-bg-transparent',
				'color'     => 'transparent',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Lichter blauw',
				'value'     => 'nok-bg-darkblue--darker nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue--darker'],
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkerblue--darker nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue--darker'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Grijs',
				'value'     => 'nok-bg-body--darker gradient-background nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body--darker'],
				'darkValue' => 'nok-dark-text-contrast',
				'darkLabel' => 'Contrasttekst',
			],
			[
				'label'     => 'Geel',
				'value'     => 'nok-bg-yellow nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-yellow'],
			],
			[
				'label'     => 'Lichtgrijs',
				'value'     => 'nok-bg-lightgrey nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-lightgrey'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
		];
	}

	/**
	 * Card colors palette
	 *
	 * Simple bg + text combinations for cards and smaller blocks.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getCardColorsPalette(): array {
		return [
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Wit (donker)',
				'value'     => 'nok-bg-white nok-text-darkestblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
		];
	}

	/**
	 * Badge colors palette
	 *
	 * For badges and labels on cards.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getBadgeColorsPalette(): array {
		return [
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue nok-text-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
		];
	}

	/**
	 * Quote block colors palette
	 *
	 * For quote blocks in carousels and showcases.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getQuoteBlockColorsPalette(): array {
		return [
			[
				'label'     => 'Body',
				'value'     => 'nok-bg-body nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-body'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkestblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkblue nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue'],
			],
		];
	}

	/**
	 * Accordion button colors palette
	 *
	 * For buttons inside accordion items.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getAccordionButtonColorsPalette(): array {
		return [
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkblue--darker nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkblue--darker'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkerblue--darker nok-text-contrast',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue--darker'],
			],
		];
	}

	/**
	 * Footer colors palette
	 *
	 * Specific colors for the footer component.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getFooterColorsPalette(): array {
		return [
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white nok-text-darkblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue nok-dark-text-white',
				'darkLabel' => 'Donkerstblauw + wit',
			],
			[
				'label'     => 'Donkerblauw',
				'value'     => 'nok-bg-darkestblue nok-text-white--darker',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkestblue'],
			],
		];
	}

	/**
	 * Step visual colors palette
	 *
	 * Simple bg-only colors for step visual sections.
	 *
	 * @return array<int, array{label: string, value: string, color: string, darkValue?: string, darkColor?: string}>
	 */
	private static function getStepVisualColorsPalette(): array {
		return [
			[
				'label'     => 'Blauw',
				'value'     => 'nok-bg-darkerblue',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-darkerblue'],
			],
			[
				'label'     => 'Wit',
				'value'     => 'nok-bg-white',
				'color'     => self::COLOR_DEFINITIONS['nok-bg-white'],
				'darkValue' => 'nok-dark-bg-darkestblue',
				'darkLabel' => 'Donkerstblauw',
			],
		];
	}

	/**
	 * Get chart-suitable colors as hex values
	 *
	 * Returns a curated set of visually distinct brand colors for use in
	 * Chart.js datasets. Unlike CSS-class-based palettes, these return raw
	 * hex values for programmatic use.
	 *
	 * @return array<int, array{label: string, hex: string}> Color entries
	 */
	public static function getChartColors(): array {
		return [
			[ 'label' => 'Donkerblauw',    'hex' => self::COLOR_DEFINITIONS['nok-bg-darkblue'] ],
			[ 'label' => 'Lichtblauw',     'hex' => self::COLOR_DEFINITIONS['nok-bg-lightblue'] ],
			[ 'label' => 'Groenblauw',     'hex' => self::COLOR_DEFINITIONS['nok-bg-greenblue'] ],
			[ 'label' => 'Geel',           'hex' => self::COLOR_DEFINITIONS['nok-bg-yellow'] ],
			[ 'label' => 'Groen',          'hex' => self::COLOR_DEFINITIONS['nok-bg-green'] ],
			[ 'label' => 'Donkerst blauw', 'hex' => self::COLOR_DEFINITIONS['nok-bg-darkerblue'] ],
			[ 'label' => 'Clinics blauw',  'hex' => self::COLOR_DEFINITIONS['nok-bg-clinics-blauw'] ],
			[ 'label' => 'Clinics oranje', 'hex' => self::COLOR_DEFINITIONS['nok-bg-clinics-oranje'] ],
		];
	}

	/**
	 * Resolve a color class to its display color
	 *
	 * Extracts the primary background color from a class string that may contain
	 * multiple classes (e.g., "nok-bg-white nok-text-darkblue" returns "#ffffff")
	 *
	 * @param string $classes CSS class string
	 * @return string Hex color code or 'transparent'
	 */
	public static function resolveColor( string $classes ): string {
		if ( empty( $classes ) ) {
			return 'transparent';
		}

		// Split into individual classes and find the first bg class
		$class_list = explode( ' ', $classes );
		foreach ( $class_list as $class ) {
			$class = trim( $class );
			if ( isset( self::COLOR_DEFINITIONS[ $class ] ) ) {
				return self::COLOR_DEFINITIONS[ $class ];
			}
		}

		return 'transparent';
	}
}
