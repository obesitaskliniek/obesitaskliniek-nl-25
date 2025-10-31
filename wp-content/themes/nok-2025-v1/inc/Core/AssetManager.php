<?php
// inc/Core/AssetManager.php

namespace NOK2025\V1\Core;

class AssetManager {

	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'admin_head', [ $this, 'custom_editor_inline_styles' ] );
	}

	public function frontend_assets(): void {
		$theme    = \NOK2025\V1\Theme::get_instance();
		$dev_mode = $theme->is_development_mode();

		wp_register_style(
			'nok-components-css',
			$this->resolve_asset_url( '/assets/css/nok-components.css', $dev_mode ),
			[],
			$this->get_asset_version( '/assets/css/nok-components.css', $dev_mode )
		);

		wp_register_style(
			'nok-colors-css',
			$this->resolve_asset_url( '/assets/css/color_tests-v2.css', $dev_mode ),
			[],
			$this->get_asset_version( '/assets/css/color_tests-v2.css', $dev_mode )
		);
	}

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
	}

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

	private function localize_preview_data(): void {
		$theme    = \NOK2025\V1\Theme::get_instance();
		$registry = $theme->get_page_part_registry();

		$data = [
			'registry' => $registry,
			'icons'    => \NOK2025\V1\Assets::getIconsForAdmin(),
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nok_preview_state_nonce' )
		];

		// For page_part editor
		if ( wp_script_is( 'nok-page-part-design-selector', 'enqueued' ) ) {
			wp_localize_script( 'nok-page-part-design-selector', 'PagePartDesignSettings', $data );
		}

		// For block editor (pages)
		$block_handle = 'nok2025-embed-nok-page-part-editor-script';
		if ( wp_script_is( $block_handle, 'enqueued' ) || wp_script_is( $block_handle, 'registered' ) ) {
			wp_localize_script( $block_handle, 'PagePartDesignSettings', $data );
		}
	}

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
	 * Resolve asset URL, preferring minified version in production
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
	 * Get asset version, using minified file timestamp if available
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
	 */
	private function get_minified_path( string $asset_path ): string {
		return preg_replace( '/\.css$/', '.min.css', $asset_path );
	}
}