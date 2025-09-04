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
add_action( 'after_setup_theme', [ NOK2025\V1\Theme::class, 'get_instance' ] );


//register blocks
add_action( 'init', function() {
    foreach ( glob( THEME_ROOT_ABS . '/blocks/*', GLOB_ONLYDIR ) as $dir ) {
        $args = [];

        $render_file = $dir . '/render.php';
        if ( file_exists( $render_file ) ) {
            $render_cb = require $render_file;
            if ( is_callable( $render_cb ) ) {
                $args['render_callback'] = $render_cb;
            }
        }

        register_block_type( $dir, $args );
    }
} );


use NOK2025\V1\Helpers;
define( 'NONCE',         hash('sha256', Helpers::makeRandomString()));
define( 'CACHE_URI_STRING',    (SITE_LIVE ? '' : '?cache=' . hash('sha256', Helpers::makeRandomString())));

// ACF settings
add_action('acf/init', 'my_acf_init');
function my_acf_init() {
	acf_update_setting('remove_wp_meta_box', false);
}

// GRAVITY FORMS settings
add_filter( 'gform_submit_button', 'gravity_button_css', 10, 2 );
function gravity_button_css( $button, $form ) {
	$fragment = WP_HTML_Processor::create_fragment( $button );
	$fragment->next_token();
	$fragment->add_class( 'nok-bg-darkerblue' );
	$fragment->add_class( 'nok-text-contrast' );

	return $fragment->get_updated_html();
}

add_filter( 'gform_required_legend', function( $legend, $form ) {
	return '<small>Velden met een <span class="gfield_required">*</span> zijn verplicht.</small>';
}, 10, 2 );