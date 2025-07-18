<?php
// inc/Theme.php

namespace NOK2025\V1;

final class Theme {
    private static ?Theme $instance = null;

    // Settings store (can hold customizer values)
    private array $settings = [];

    public static function get_instance(): Theme {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    private function setup_hooks(): void {
        add_action( 'after_setup_theme', [ $this, 'theme_supports' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'customize_register', [ $this, 'register_customizer' ] );
        // ...other hooks
    }

    public function theme_supports(): void {
        // Add support for post thumbnails, title tag, custom logo…
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', [ 'search-form', 'comment-form' ] );
        // Optionally: block editor settings via theme.json (WP 5.8+)
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'mytheme-style',
            THEME_URI . '/assets/css/main.css',
            [],
            THEME_VERSION
        );
        wp_enqueue_script(
            'mytheme-script',
            THEME_URI . '/assets/js/main.js',
            ['jquery'],
            THEME_VERSION,
            true
        );
    }

    public function register_customizer( \WP_Customize_Manager $wp_customize ): void {
        // Delegate to inc/customizer.php
        \NOK2025\V1\Customizer::register( $wp_customize );
    }

    // Helper to get a “global” setting: wraps get_theme_mod()
    public function get_setting( string $key, $default = null ) {
        return get_theme_mod( $key, $default );
    }
}
