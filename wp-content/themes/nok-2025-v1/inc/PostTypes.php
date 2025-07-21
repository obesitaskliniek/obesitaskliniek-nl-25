<?php
namespace NOK2025\V1;

class PostTypes {
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ] );
    }
    public function register_post_types() {
        $labels = [
            'name'               => __( 'Page Parts',           THEME_TEXT_DOMAIN ),
            'singular_name'      => __( 'Page Part',            THEME_TEXT_DOMAIN ),
            'add_new_item'       => __( 'Add New Page Part',    THEME_TEXT_DOMAIN ),
            'edit_item'          => __( 'Edit Page Part',       THEME_TEXT_DOMAIN ),
            'all_items'          => __( 'All Page Parts',       THEME_TEXT_DOMAIN ),
            'view_item'          => __( 'View Page Part',       THEME_TEXT_DOMAIN ),
        ];
        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'supports'           => [ 'title', 'editor', 'thumbnail' ],
            'menu_icon'          => 'dashicons-layout',
            'has_archive'        => false,
        ];
        register_post_type( 'page_part', $args );
    }
}