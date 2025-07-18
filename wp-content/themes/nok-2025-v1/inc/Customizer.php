<?php
// inc/Customizer.php
namespace NOK2025\V1;

class Customizer {
    public static function register( \WP_Customize_Manager $wp_customize ): void {
        // Create a panel/section
        $wp_customize->add_section( 'nok2025_general', [
            'title'      => __( 'General Settings', THEME_TEXT_DOMAIN ),
            'priority'   => 30,
        ] );

        // Add a setting & control (e.g., accent color)
        $wp_customize->add_setting( 'accent_color', [
            'default'           => '#FF0000',
            'sanitize_callback' => 'sanitize_hex_color',
        ] );

        $wp_customize->add_control( new \WP_Customize_Color_Control(
            $wp_customize,
            'accent_color_control',
            [
                'label'    => __( 'Accent Color', THEME_TEXT_DOMAIN ),
                'section'  => THEME_TEXT_DOMAIN . '_general',
                'settings' => 'accent_color',
            ]
        ) );
    }
}

//usage example
//$accent = \nok2025\v1\Theme::get_instance()->get_setting( 'accent_color', '#FF0000' );
//echo '<style> a { color: ' . esc_attr( $accent ) . '; } </style>';