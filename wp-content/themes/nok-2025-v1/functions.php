<?php
// functions.php

// Only do this in development!
ini_set( 'display_errors', '1' );
ini_set( 'display_startup_errors', '1' );
error_reporting( E_ALL );

// 1) Define constants for easy paths/URIs
define( 'THEME_ROOT_ABS', get_template_directory() );
define( 'THEME_ROOT', get_template_directory_uri() );
define( 'THEME_VERSION', '1.0.0' );
define( 'THEME_BS_VER', '5.3.3');
define( 'THEME_NAME', 'NOK 2025 V1' );
define( 'THEME_TEXT_DOMAIN', 'nok-2025-v1');
define( 'THEME_MAINTENANCE_MODE', false);
define( 'THEME_COPYRIGHT', '©'.Date('Y').' Nederlandse Obesitas Kliniek B.V. - Alle rechten voorbehouden.');

define( 'USER_LOGGED_IN', function_exists( 'is_user_logged_in' ) && is_user_logged_in() );

define( 'WP_ROOT', function_exists('get_home_path') ? get_home_path() : dirname(dirname(dirname(dirname(dirname(__FILE__))))));

define( 'SITE_BASE_URI', 'https://dev.obesitaskliniek.nl');
define( 'SITE_LIVE', false);



// 2) PSR‑4 autoloader
spl_autoload_register( function( $class ) {
    $prefix   = 'NOK2025\\V1\\';
    $base_dir = __DIR__ . '/inc/';

    // only load classes in our namespace
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    // convert namespace to file path
    $relative_class = substr( $class, strlen( $prefix ) );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );


// 3) Initialize your main theme class
add_action( 'after_setup_theme', [ \NOK2025\V1\Theme::class, 'get_instance' ] );

//register page parts custom post type
add_action( 'init', function() {
    register_post_type( 'page_part', [
        'label'               => __( 'NOK Page Parts', THEME_TEXT_DOMAIN ),
        'public'              => true,
        'show_in_rest'        => true,
        'has_archive'         => false,
        'hierarchical'        => false,
        'supports'            => [ 'title', 'editor' ],
        'show_in_nav_menus'   => false,
        'menu_icon'           => 'welcome-widgets-menus',
        'rewrite'             => [ 'slug' => 'parts' ],
    ] );
} );

//register blocks
add_action( 'init', function() {
    foreach ( glob( THEME_ROOT_ABS . '/blocks/*', GLOB_ONLYDIR ) as $dir ) {
        register_block_type( $dir );
    }
} );

use NOK2025\V1\Helpers;
define( 'NONCE',         hash('sha256', Helpers::makeRandomString()));
define( 'CACHE_URI_STRING',    (SITE_LIVE ? '' : '?cache=' . hash('sha256', Helpers::makeRandomString())));