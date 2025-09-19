<?php
// inc/Theme.php - Phase 3 Final

namespace NOK2025\V1;

use NOK2025\V1\PostTypes;
use NOK2025\V1\Core\AssetManager;
use NOK2025\V1\PageParts\Registry;
use NOK2025\V1\PageParts\MetaManager;
use NOK2025\V1\PageParts\PreviewSystem;
use NOK2025\V1\PageParts\TemplateRenderer;
use NOK2025\V1\PageParts\RestEndpoints;

final class Theme {
	private static ?Theme $instance = null;

	// Components
	private AssetManager $asset_manager;
	private Registry $registry;
	private MetaManager $meta_manager;
	private PreviewSystem $preview_system;
	private TemplateRenderer $template_renderer;
	private RestEndpoints $rest_endpoints;

	// Settings store (can hold customizer values)
	private array $settings = [];

	// Development mode - set to false for production
	private bool $development_mode = true;

	public function __construct() {
		// Ensure CPTs are registered
		new PostTypes();

		// Initialize components with proper dependencies
		$this->asset_manager = new AssetManager();
		$this->registry = new Registry();
		$this->meta_manager = new MetaManager($this->registry);
		$this->preview_system = new PreviewSystem($this->meta_manager);
		$this->template_renderer = new TemplateRenderer();
		$this->rest_endpoints = new RestEndpoints($this->template_renderer, $this->meta_manager);
	}

	public static function get_instance(): Theme {
		if (self::$instance === null) {
			self::$instance = new self();
			self::$instance->setup_hooks();
		}

		return self::$instance;
	}

	private function setup_hooks(): void {
		// Core theme setup
		add_action('init', [$this, 'theme_supports']);

		// Let components register their hooks
		$this->asset_manager->register_hooks();
		$this->meta_manager->register_hooks();
		$this->preview_system->register_hooks();
		$this->rest_endpoints->register_hooks();

		// Customizer
		add_action('customize_register', [$this, 'register_customizer']);

		// Content filters
		add_filter('the_content', [$this, 'enhance_paragraph_classes'], 1);
		add_filter('show_admin_bar', [$this, 'maybe_hide_admin_bar']);
	}

	// =============================================================================
	// CORE THEME SETUP
	// =============================================================================

	public function theme_supports(): void {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('html5', ['search-form', 'comment-form']);
	}

	public function register_customizer(\WP_Customize_Manager $wp_customize): void {
		\NOK2025\V1\Customizer::register($wp_customize);
	}

	public function get_setting(string $key, $default = null) {
		return get_theme_mod($key, $default);
	}

	public function is_development_mode(): bool {
		return $this->development_mode;
	}

	// =============================================================================
	// DELEGATED METHODS TO COMPONENTS
	// =============================================================================

	/**
	 * Get page part registry (delegated to Registry component)
	 */
	public function get_page_part_registry(): array {
		return $this->registry->get_registry();
	}

	public function get_page_part_fields(int $post_id, string $design, bool $is_editing = false): array {
		return $this->meta_manager->get_page_part_fields($post_id, $design, $is_editing);
	}

	/**
	 * Generate a human-readable label from field name (delegated to Registry)
	 */
	public function generate_field_label(string $field_name): string {
		return $this->registry->generate_field_label($field_name);
	}

	/**
	 * Include a page part template (delegated to TemplateRenderer)
	 */
	public function include_page_part_template(string $design, array $args = []): void {
		$this->template_renderer->include_page_part_template($design, $args);
	}

	/**
	 * Embed a page part template (delegated to TemplateRenderer)
	 */
	public function embed_page_part_template(string $design, array $fields, bool $register_css = false): void {
		$this->template_renderer->embed_page_part_template($design, $fields, $register_css);
	}

	/**
	 * Embed a post part template (delegated to TemplateRenderer)
	 */
	public function embed_post_part_template(string $design, array $fields, bool $register_css = false): void {
		$this->template_renderer->embed_post_part_template($design, $fields, $register_css);
	}

	// =============================================================================
	// CONTENT FILTERS
	// =============================================================================

	/**
	 * Add paragraph classes to content
	 */
	public function enhance_paragraph_classes($content) {
		return str_replace('<p>', '<p class="wp-block-paragraph">', $content);
	}

	/**
	 * Hide admin bar when requested via URL parameter
	 */
	public function maybe_hide_admin_bar($show) {
		if (isset($_GET['hide_adminbar'])) {
			return false;
		}

		return $show;
	}
}