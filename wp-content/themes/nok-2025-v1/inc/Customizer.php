<?php
// inc/Customizer.php
namespace NOK2025\V1;

/**
 * Customizer - WordPress Customizer settings registration
 *
 * Registers theme customization options:
 * - General settings (accent color)
 * - Template layout assignments for custom post types
 * - Dynamic dropdown population from template_layout posts
 *
 * @example Register in theme setup
 * add_action('customize_register', [Customizer::class, 'register']);
 *
 * @example Access settings in templates
 * $accent = get_theme_mod('accent_color', '#FF0000');
 * $layout_id = get_theme_mod('template_layout_ervaringen', 0);
 *
 * @package NOK2025\V1
 */
class Customizer {

	private static function get_template_layout_choices(): array {
		$choices = [0 => '— None —'];
		$layouts = get_posts([
			'post_type' => 'template_layout',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish',
		]);

		foreach ($layouts as $layout) {
			$choices[$layout->ID] = $layout->post_title;
		}

		return $choices;
	}

	public static function register( \WP_Customize_Manager $wp_customize ): void {
		// General section
		$wp_customize->add_section( 'nok2025_general', [
			'title'      => __( 'General Settings', THEME_TEXT_DOMAIN ),
			'priority'   => 30,
		] );

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

		// Template Layouts section
		$wp_customize->add_section('template_layouts', [
			'title' => __('Template Layouts', THEME_TEXT_DOMAIN),
			'priority' => 31,
			'description' => __('Configure template layouts for different post types. Create and edit layouts in the Template Layouts admin menu.', THEME_TEXT_DOMAIN),
		]);

		// Ervaringen template layout
		$wp_customize->add_setting('template_layout_ervaringen', [
			'default' => 0,
			'sanitize_callback' => 'absint',
		]);

		$wp_customize->add_control('template_layout_ervaringen', [
			'label' => __('Ervaringen Template', THEME_TEXT_DOMAIN),
			'description' => sprintf(
				'<a href="%s" target="_blank">%s</a> | <a href="%s" target="_blank">%s</a>',
				admin_url('post-new.php?post_type=template_layout'),
				__('Create New Layout', THEME_TEXT_DOMAIN),
				admin_url('edit.php?post_type=template_layout'),
				__('Manage Layouts', THEME_TEXT_DOMAIN)
			),
			'section' => 'template_layouts',
			'type' => 'select',
			'choices' => self::get_template_layout_choices(),
		]);

		// Vestiging template layout
		$wp_customize->add_setting('template_layout_vestiging', [
			'default' => 0,
			'sanitize_callback' => 'absint',
		]);

		$wp_customize->add_control('template_layout_vestiging', [
			'label' => __('Vestiging Template', THEME_TEXT_DOMAIN),
			'description' => sprintf(
				'<a href="%s" target="_blank">%s</a> | <a href="%s" target="_blank">%s</a>',
				admin_url('post-new.php?post_type=template_layout'),
				__('Create New Layout', THEME_TEXT_DOMAIN),
				admin_url('edit.php?post_type=template_layout'),
				__('Manage Layouts', THEME_TEXT_DOMAIN)
			),
			'section' => 'template_layouts',
			'type' => 'select',
			'choices' => self::get_template_layout_choices(),
		]);
	}
}