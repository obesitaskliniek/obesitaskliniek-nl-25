<?php
// inc/Core/AssetManager.php

namespace NOK2025\V1\Core;

/**
 * AssetManager - Handles CSS and JavaScript asset loading
 *
 * Responsible for:
 * - Registering and enqueuing frontend CSS/JS assets
 * - Registering and enqueuing admin/editor assets
 * - Loading page part preview system scripts
 * - Localizing JavaScript with PHP data (registry, icons, nonces)
 * - Conditional asset loading based on development mode
 * - Minified asset resolution for production
 * - File-based cache busting via timestamps
 * - Custom editor inline styles
 *
 * Development mode (Theme::is_development_mode() = true):
 * - Loads unminified assets (.css)
 * - Uses source file timestamps for versioning
 *
 * Production mode (Theme::is_development_mode() = false):
 * - Attempts to load minified assets (.min.css)
 * - Falls back to unminified if minified not found
 * - Uses minified file timestamps for versioning
 *
 * @example Register hooks on initialization
 * $asset_manager = new AssetManager();
 * $asset_manager->register_hooks();
 *
 * @package NOK2025\V1\Core
 */
class AssetManager {

	/**
	 * Register WordPress action hooks for asset loading
	 *
	 * Hooks into:
	 * - wp_enqueue_scripts: Frontend asset registration
	 * - admin_enqueue_scripts: Admin/editor asset loading
	 * - admin_head: Custom editor inline styles injection
	 *
	 * @return void
	 *
	 * @example Usage in Theme constructor
	 * $this->asset_manager = new AssetManager();
	 * $this->asset_manager->register_hooks();
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'admin_head', [ $this, 'custom_editor_inline_styles' ] );
	}

	/**
	 * Register and enqueue frontend assets
	 *
	 * Registers CSS assets for the public-facing site, respecting development
	 * mode settings for minification and cache busting.
	 *
	 * Colors are now compiled into nok-components.css (single bundle)
	 * to eliminate the extra HTTP request and sequential dependency.
	 *
	 * The main stylesheet is loaded with media="print" and swapped to "all"
	 * on load, so it becomes non-render-blocking. Critical above-the-fold
	 * CSS is inlined in header.php instead.
	 *
	 * @return void
	 */
	public function frontend_assets(): void {
		$theme    = \NOK2025\V1\Theme::get_instance();
		$dev_mode = $theme->is_development_mode();

		$css_url     = $this->resolve_asset_url( '/assets/css/nok-components.css', $dev_mode );
		$css_version = $this->get_asset_version( '/assets/css/nok-components.css', $dev_mode );

		// Register the full stylesheet — loaded deferred (media="print", swapped on load)
		// Critical CSS is inlined in header.php for instant first paint
		wp_enqueue_style(
			'nok-components-css',
			$css_url,
			[],
			$css_version
		);

		// Make it non-render-blocking: media="print" swapped to "all" on load
		add_filter( 'style_loader_tag', function ( $tag, $handle ) {
			if ( $handle !== 'nok-components-css' ) {
				return $tag;
			}

			// Replace media attribute (handles both single and double quotes)
			$deferred_tag = preg_replace(
				'/media=[\'"]all[\'"]/',
				'media="print" onload="this.media=\'all\'"',
				$tag
			);

			// Only add noscript fallback if the replacement actually worked
			if ( $deferred_tag !== $tag ) {
				$deferred_tag .= '<noscript>' . $tag . '</noscript>';
			}

			return $deferred_tag;
		}, 10, 2 );
	}

	/**
	 * Load admin and block editor assets
	 *
	 * Conditionally loads assets based on the current admin screen:
	 * - Always: Backend CSS (nok-backend-css)
	 * - Page part editor: Preview system and design selector
	 * - Block editor: Localized preview data and image layout extension
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 *
	 * @example Manually check what would load
	 * // On post.php with page_part post type:
	 * // - nok-backend-css
	 * // - nok-page-part-live-preview
	 * // - nok-page-part-design-selector
	 * // - PagePartDesignSettings localization
	 */
	public function admin_assets( $hook ): void {
		$theme    = \NOK2025\V1\Theme::get_instance();
		$dev_mode = $theme->is_development_mode();

		// Import admin-specific CSS
		wp_register_style(
			'nok-backend-css',
			$this->resolve_asset_url( '/assets/css/nok-backend-css.css', $dev_mode ),
			[],
			$this->get_asset_version( '/assets/css/nok-backend-css.css', $dev_mode )
		);
		wp_enqueue_style( 'nok-backend-css' );

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		$screen = get_current_screen();

		// Load preview assets for page_part posts
		if ( $screen->post_type === 'page_part' ) {
			$this->load_preview_assets();
		}

		// Localize for any block editor
		if ( $screen && $screen->is_block_editor() ) {
			$this->localize_preview_data();
		}

		// Enqueue image layout extension for block editor
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->is_block_editor() ) {
				$asset = require get_theme_file_path( '/assets/js/nok-image-layout-extension.asset.php' );
				wp_enqueue_script(
					'nok-image-layout-extension',
					get_stylesheet_directory_uri() . '/assets/js/nok-image-layout-extension.js',
					$asset['dependencies'],
					$asset['version']
				);

				wp_localize_script(
					'nok-image-layout-extension',
					'nokImageLayouts',
					[
						'variants' => [
							[ 'name' => '', 'label' => 'Auto width', 'icon' => 'format-image' ]
						]
					]
				);
			}
		}

		// Enqueue button extension for block editor
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->is_block_editor() ) {
				$button_asset = require get_theme_file_path( '/assets/js/nok-button-extension.asset.php' );
				wp_enqueue_script(
					'nok-button-extension',
					get_stylesheet_directory_uri() . '/assets/js/nok-button-extension.js',
					$button_asset['dependencies'],
					$button_asset['version']
				);

				// Localize with ui_ icons only
				wp_localize_script(
					'nok-button-extension',
					'nokButtonIcons',
					[
						'ui' => \NOK2025\V1\Assets::getIconsByCategory( 'ui' )
					]
				);
			}
		}
	}

	/**
	 * Load page part preview system assets
	 *
	 * Enqueues JavaScript bundles for the page part preview functionality:
	 * - nok-page-part-live-preview: Live preview refresh system
	 * - nok-page-part-design-selector: React-based design picker UI
	 *
	 * @return void
	 */
	private function load_preview_assets(): void {
		// Preview system script
		$asset = require get_theme_file_path( '/assets/js/nok-page-part-preview.asset.php' );
		wp_enqueue_script(
			'nok-page-part-live-preview',
			get_stylesheet_directory_uri() . '/assets/js/nok-page-part-preview.js',
			$asset['dependencies'],
			$asset['version']
		);

		// React design selector component
		$react_asset = require get_theme_file_path( '/assets/js/nok-page-part-design-selector.asset.php' );
		wp_enqueue_script(
			'nok-page-part-design-selector',
			get_stylesheet_directory_uri() . '/assets/js/nok-page-part-design-selector.js',
			$react_asset['dependencies'],
			$react_asset['version']
		);
	}

	/**
	 * Localize page part data for JavaScript consumption
	 *
	 * Makes PHP data available to JavaScript via wp_localize_script:
	 * - registry: Complete page part registry with field definitions
	 * - blockPartsRegistry: Block parts registry with field definitions
	 * - icons: Available icon set for icon-selector fields
	 * - colorPalettes: Color palettes for color-selector fields
	 * - ajaxurl: WordPress AJAX endpoint URL
	 * - nonce: Security nonce for AJAX requests
	 *
	 * Localizes to:
	 * - nok-page-part-design-selector for page_part editor (non-block context)
	 * - wp-blocks for all block editor contexts (available to all custom blocks)
	 *
	 * @return void
	 */
	private function localize_preview_data(): void {
		$theme    = \NOK2025\V1\Theme::get_instance();
		$registry = $theme->get_page_part_registry();

		// Get block-parts registry
		$block_parts_registry = ( new \NOK2025\V1\PageParts\Registry() )->get_block_parts_registry();

		$data = [
			'registry'           => $registry,
			'blockPartsRegistry' => $block_parts_registry,
			'postParts'          => $this->get_available_post_parts(),
			'icons'              => \NOK2025\V1\Assets::getIconsForAdmin(),
			'colorPalettes'      => \NOK2025\V1\Colors::getColorsForAdmin(),
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'nok_preview_state_nonce' )
		];

		// For page_part editor (non-block context)
		if ( wp_script_is( 'nok-page-part-design-selector', 'enqueued' ) ) {
			wp_localize_script( 'nok-page-part-design-selector', 'PagePartDesignSettings', $data );
		}

		// For block editor - always available via wp-blocks
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && $screen->is_block_editor() ) {
			wp_localize_script( 'wp-blocks', 'PagePartDesignSettings', $data );
		}
	}

	/**
	 * Inject custom inline styles for block editor
	 *
	 * Adds critical inline CSS to improve block editor UX:
	 * - Sets minimum height for editor wrapper (35vh)
	 * - Adjusts bottom padding pseudo-element height (60px)
	 *
	 * Only applies when viewing the block editor screen.
	 *
	 * @return void
	 *
	 * @example What gets injected in block editor
	 * // <style>
	 * //   .editor-styles-wrapper { min-height: 35vh !important; }
	 * //   .editor-styles-wrapper::after { height: 60px !important; }
	 * // </style>
	 */
	public function custom_editor_inline_styles(): void {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			echo '<style>
                .editor-styles-wrapper {
                    min-height: 35vh !important;
                }
                .editor-styles-wrapper::after {
                    height: 60px !important;
                }
            </style>';
		}
	}

	/**
	 * Get available post-part templates for the block editor
	 *
	 * Scans `template-parts/post-parts/` for `.php` files and builds
	 * a list of slug/label pairs for the post-part embed block dropdown.
	 *
	 * @return array<int, array{slug: string, label: string}> Post-part templates
	 */
	private function get_available_post_parts(): array {
		$post_parts_dir = get_theme_file_path( 'template-parts/post-parts' );
		$files          = glob( $post_parts_dir . '/*.php' );

		if ( ! $files ) {
			return [];
		}

		$parts = [];
		foreach ( $files as $file ) {
			$slug  = basename( $file, '.php' );
			$label = ucwords( str_replace( [ 'nok-', '-' ], [ '', ' ' ], $slug ) );

			$parts[] = [
				'slug'  => $slug,
				'label' => $label,
			];
		}

		return $parts;
	}

	/**
	 * Resolve asset URL with minification support
	 *
	 * Returns the appropriate asset URL based on development mode:
	 * - Production: Returns minified version (.min.css) if it exists
	 * - Production: Falls back to unminified if minified not found
	 * - Development: Always returns unminified version
	 *
	 * @param string $asset_path Theme-relative asset path (e.g., '/assets/css/app.css')
	 * @param bool $dev_mode Whether in development mode
	 * @return string Full theme URL to asset
	 *
	 * @example In production with existing minified file
	 * $url = $this->resolve_asset_url('/assets/css/app.css', false);
	 * // Returns: https://example.com/wp-content/themes/nok-2025-v1/assets/css/app.min.css
	 *
	 * @example In development mode
	 * $url = $this->resolve_asset_url('/assets/css/app.css', true);
	 * // Returns: https://example.com/wp-content/themes/nok-2025-v1/assets/css/app.css
	 */
	private function resolve_asset_url( string $asset_path, bool $dev_mode ): string {
		if ( ! $dev_mode ) {
			$minified_path = $this->get_minified_path( $asset_path );
			if ( file_exists( THEME_ROOT_ABS . $minified_path ) ) {
				return THEME_ROOT . $minified_path;
			}
		}

		return THEME_ROOT . $asset_path;
	}

	/**
	 * Get asset version for cache busting
	 *
	 * Returns file modification timestamp for versioning:
	 * - Production: Returns minified file timestamp if it exists
	 * - Production: Falls back to unminified file timestamp
	 * - Development: Always returns unminified file timestamp
	 *
	 * This ensures browser cache is invalidated when assets change.
	 *
	 * @param string $asset_path Theme-relative asset path
	 * @param bool $dev_mode Whether in development mode
	 * @return int Unix timestamp of file modification time
	 *
	 * @example Get version for cache busting
	 * $version = $this->get_asset_version('/assets/css/app.css', false);
	 * // Returns: 1735689600 (timestamp when file was last modified)
	 */
	private function get_asset_version( string $asset_path, bool $dev_mode ): int {
		if ( ! $dev_mode ) {
			$minified_path = $this->get_minified_path( $asset_path );
			if ( file_exists( THEME_ROOT_ABS . $minified_path ) ) {
				return filemtime( THEME_ROOT_ABS . $minified_path );
			}
		}

		return filemtime( THEME_ROOT_ABS . $asset_path );
	}

	/**
	 * Convert asset path to minified version
	 *
	 * Transforms a standard asset path to its minified equivalent by
	 * inserting .min before the file extension.
	 *
	 * @param string $asset_path Original asset path
	 * @return string Minified asset path
	 *
	 * @example Convert CSS path
	 * $min = $this->get_minified_path('/assets/css/app.css');
	 * // Returns: '/assets/css/app.min.css'
	 *
	 * @example Already minified (no change)
	 * $min = $this->get_minified_path('/assets/css/app.min.css');
	 * // Returns: '/assets/css/app.min.min.css' (edge case)
	 */
	private function get_minified_path( string $asset_path ): string {
		return preg_replace( '/\.css$/', '.min.css', $asset_path );
	}
}