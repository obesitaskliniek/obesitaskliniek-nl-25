<?php
namespace NOK2025\V1\PostTypes;

echo "POST TYPES\n";

class Parts {
    public static function register(): void {
        register_post_type( 'part', [
            'label'           => __( 'Page Parts', THEME_TEXT_DOMAIN ),
            'public'          => false,       // no standalone archives
            'show_ui'         => true,        // in WP admin
            'show_in_rest'    => true,        // enable Gutenberg editor
            'rest_base'       => 'parts',
            'supports'        => [ 'title', 'editor' ],
            'menu_icon'       => 'admin-post',
        ] );
    }
}

// Hook it up
add_action( 'init', [ Parts::class, 'register' ] );
