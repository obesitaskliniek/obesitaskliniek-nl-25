<?php
// inc/Theme.php - Phase 3 Final

namespace NOK2025\V1;

use NOK2025\V1\PostTypes;
use NOK2025\V1\Core\AssetManager;
use NOK2025\V1\Navigation\MenuManager;
use NOK2025\V1\PageParts\Registry;
use NOK2025\V1\PageParts\MetaManager;
use NOK2025\V1\PageParts\PreviewSystem;
use NOK2025\V1\PageParts\TemplateRenderer;
use NOK2025\V1\PageParts\RestEndpoints;
use NOK2025\V1\SEO\YoastIntegration;

final class Theme {
	private static ?Theme $instance = null;

	// Components
	private AssetManager $asset_manager;
	private MenuManager $menu_manager;
	private Registry $registry;
	private MetaManager $meta_manager;
	private PreviewSystem $preview_system;
	private TemplateRenderer $template_renderer;
	private RestEndpoints $rest_endpoints;
	private YoastIntegration $yoast_integration;
	private BlockRenderers $block_renderers;

	// Settings store (can hold customizer values)
	private array $settings = [];

	// Development mode - set to false for production
	private bool $development_mode = true;

	public function __construct() {
		// Ensure CPTs are registered
		new PostTypes();

		// Initialize components with proper dependencies
		$this->asset_manager = new AssetManager();
		$this->menu_manager = new MenuManager();
		$this->registry = new Registry();
		$this->meta_manager = new MetaManager($this->registry);
		$this->preview_system = new PreviewSystem($this->meta_manager);
		$this->template_renderer = new TemplateRenderer();
		$this->yoast_integration = new YoastIntegration();
		$this->block_renderers = new BlockRenderers();
		$this->rest_endpoints = new RestEndpoints(
			$this->template_renderer,
			$this->meta_manager
		);
		$this->post_meta_registrar = new PostMeta\MetaRegistrar();
		$this->register_post_custom_fields();
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
		$this->menu_manager->register_hooks();
		$this->meta_manager->register_hooks();
		$this->preview_system->register_hooks();
		$this->rest_endpoints->register_hooks();
		$this->yoast_integration->register_hooks();
		$this->block_renderers->register_hooks();

		// Customizer
		add_action('customize_register', [$this, 'register_customizer']);

		// Content filters
		add_filter('the_content', [$this, 'enhance_paragraph_classes'], 1);
		add_filter('show_admin_bar', [$this, 'maybe_hide_admin_bar']);

		// Template hierarchy
		add_filter('single_template', [$this, 'category_based_single_template']);
		/**
		 * Add fallback alt text to images missing it
		 *
		 * @param array $attr Image attributes
		 * @param WP_Post $attachment Image attachment post
		 * @return array Modified attributes
		 */
		add_filter('wp_get_attachment_image_attributes', function($attr, $attachment) {
			// Only add fallback if alt is missing or empty
			if (empty($attr['alt'])) {
				// Try attachment title
				$attr['alt'] = $attachment->post_title;

				// Still empty? Use attachment filename (cleaned up)
				if (empty($attr['alt'])) {
					$filename = get_attached_file($attachment->ID);
					$attr['alt'] = ucwords(str_replace(['-', '_'], ' ',
						pathinfo($filename, PATHINFO_FILENAME)
					));
				}
			}

			return $attr;
		}, 10, 2);

		/**
		 * Force complete thumbnail regeneration after image editing
		 */
		add_action('wp_save_image_editor_file', function($dummy, $filename, $image, $mime_type, $post_id) {
			if (!$post_id) {
				return;
			}

			// Queue regeneration after save completes
			add_action('shutdown', function() use ($post_id) {
				require_once(ABSPATH . 'wp-admin/includes/image.php');

				$file_path = get_attached_file($post_id);
				if ($file_path) {
					// This regenerates ALL registered sizes from the newly edited original
					wp_update_attachment_metadata(
						$post_id,
						wp_generate_attachment_metadata($post_id, $file_path)
					);
				}
			});
		}, 10, 5);
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
		Customizer::register($wp_customize);
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
	 * Get menu manager instance
	 */
	public function get_menu_manager(): MenuManager {
		return $this->menu_manager;
	}

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

	/**
	 * Enable category-specific single post templates
	 *
	 * Checks for single-cat-{slug}.php before falling back to single.php.
	 *
	 * @param string $template Path to template file
	 * @return string Modified template path
	 */
	public function category_based_single_template(string $template): string {
		if (!is_singular('post')) {
			return $template;
		}

		$categories = get_the_category();
		if (empty($categories)) {
			return $template;
		}

		foreach ($categories as $category) {
			$cat_template = locate_template("single-cat-{$category->slug}.php");
			if ($cat_template) {
				return $cat_template;
			}
		}

		return $template;
	}

	private function register_post_custom_fields(): void {
		// Get category IDs programmatically
		$experience_cat = get_category_by_slug('ervaringen');

		PostMeta\MetaRegistry::register_field('post', 'naam_patient', [
			'type' => 'text',
			'label' => 'Naam patiënt',
			'placeholder' => 'Voer de naam in...',
			'description' => 'De naam wordt gebruikt op verschillende manieren, bijvoorbeeld voor de "Lees het verhaal van <naam>" links',
			'categories' => [$experience_cat->term_id],
		]);

		PostMeta\MetaRegistry::register_field('post', 'highlighted_excerpt', [
			'type' => 'textarea',
			'label' => 'Samenvatting',
			'placeholder' => 'Voer een korte samenvatting van 1-2 zinnen in...',
			'description' => 'Deze samenvatting wordt bijvoorbeeld gebruikt bij het uitlichten van dit ervaringsverhaal.',
			'categories' => [$experience_cat->term_id],
		]);
	}
}