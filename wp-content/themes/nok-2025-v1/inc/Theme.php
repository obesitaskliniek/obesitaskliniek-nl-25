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
use NOK2025\V1\SEO\PagePartSchema;
use NOK2025\V1\VoorlichtingForm;
use NOK2025\V1\ContactForm;
use WP_Post;

/**
 * Theme - Main theme orchestrator using singleton pattern
 *
 * Coordinates all theme components and subsystems:
 * - Asset management (CSS/JS loading with dev/prod modes)
 * - Page parts system (template registry, meta fields, rendering, preview)
 * - Navigation menu rendering with custom walkers
 * - Block rendering and registration
 * - Yoast SEO integration for page parts
 * - Post meta field registration
 * - Content filters and template hierarchy customization
 *
 * ARCHITECTURAL DECISION: Singleton Pattern
 * =========================================
 * This class uses the singleton pattern (get_instance()) despite it being generally
 * considered an anti-pattern in modern PHP. This is an intentional decision for
 * the following reasons:
 *
 * 1. WordPress ecosystem compatibility: WordPress lacks a dependency injection
 *    container. The singleton ensures hooks are registered exactly once, preventing
 *    duplicate action/filter registration which would cause bugs.
 *
 * 2. Theme lifecycle: This is a single-deployment theme, not a reusable library.
 *    The testability concerns that make singletons problematic in libraries are
 *    less relevant here.
 *
 * 3. Refactoring cost: Converting to DI would require significant architecture
 *    changes with minimal practical benefit. The theme works correctly as-is.
 *
 * If you're building a plugin or reusable library, DO use dependency injection
 * with a container (e.g., PHP-DI, League Container). For single-deployment themes,
 * the singleton is an acceptable pragmatic choice.
 *
 * Future consideration: If WordPress core adds DI support, this could be refactored.
 *
 * @example Get theme instance and access registry
 * $theme = Theme::get_instance();
 * $registry = $theme->get_page_part_registry();
 *
 * @example Check development mode for conditional logic
 * $theme = Theme::get_instance();
 * if ($theme->is_development_mode()) {
 *     // Load unminified assets, enable debugging
 * }
 *
 * @example Render a page part template
 * $theme = Theme::get_instance();
 * $theme->embed_page_part_template('nok-hero', [
 *     'title' => 'Welcome',
 *     'subtitle' => 'To our site'
 * ]);
 *
 * @example Get customizer setting
 * $theme = Theme::get_instance();
 * $logo_url = $theme->get_setting('custom_logo_url', 'default.png');
 *
 * @package NOK2025\V1
 */
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
    private PagePartSchema $page_part_schema;
    private BlockRenderers $block_renderers;
    private VoorlichtingForm $voorlichting_form;
    private ContactForm $contact_form;

    // Settings store (can hold customizer values)
    private array $settings = [];

    // Development mode - set to false for production
    private bool $development_mode = true;

    public function __construct() {
        // Ensure CPTs are registered
        new PostTypes();

        // Initialize components with proper dependencies
        $this->asset_manager       = new AssetManager();
        $this->menu_manager        = new MenuManager();
        $this->registry            = new Registry();
        $this->meta_manager        = new MetaManager( $this->registry );
        $this->preview_system      = new PreviewSystem( $this->meta_manager );
        $this->template_renderer   = new TemplateRenderer();
        $this->yoast_integration   = new YoastIntegration();
        $this->page_part_schema    = new PagePartSchema( $this->registry );
        $this->block_renderers     = new BlockRenderers();
        $this->voorlichting_form   = new VoorlichtingForm();
        $this->contact_form        = new ContactForm();
        $this->rest_endpoints      = new RestEndpoints(
                $this->template_renderer,
                $this->meta_manager
        );
        $this->post_meta_registrar = new PostMeta\MetaRegistrar();
        $this->register_post_custom_fields();
    }

    /**
     * Get singleton instance of Theme
     *
     * Returns the single instance of the Theme class, creating it if necessary.
     * This ensures only one Theme instance exists throughout the application lifecycle.
     *
     * @return Theme The singleton Theme instance
     *
     * @example Basic usage
     * $theme = Theme::get_instance();
     *
     * @example Access registry through instance
     * $registry = Theme::get_instance()->get_page_part_registry();
     */
    public static function get_instance(): Theme {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }

        return self::$instance;
    }

    private function setup_hooks(): void {
        // Core theme setup
        add_action( 'init', [ $this, 'theme_supports' ] );

        // Let components register their hooks
        $this->asset_manager->register_hooks();
        $this->menu_manager->register_hooks();
        $this->meta_manager->register_hooks();
        $this->preview_system->register_hooks();
        $this->template_renderer->register_hooks();
        $this->rest_endpoints->register_hooks();
        $this->yoast_integration->register_hooks();
        $this->page_part_schema->register_hooks();
        $this->block_renderers->register_hooks();
        $this->voorlichting_form->register_hooks();
        $this->contact_form->register_hooks();
        PageParts\Registry::register_invalidation_hooks();


        // Register archive settings AFTER init (priority 20)
        add_action( 'init', [ $this, 'register_archive_settings' ], 20 );
        // Customizer
        add_action( 'customize_register', [ $this, 'register_customizer' ] );

        // Content filters
        add_filter( 'the_content', [ $this, 'enhance_paragraph_classes' ], 1 );
        add_filter( 'the_content', [ $this, 'process_page_part_tokens' ], 5 );
        add_filter( 'show_admin_bar', [ $this, 'maybe_hide_admin_bar' ] );

        // Template hierarchy
        add_filter( 'single_template', [ $this, 'category_based_single_template' ] );
        /**
         * Add fallback alt text to images missing it
         *
         * @param array $attr Image attributes
         * @param WP_Post $attachment Image attachment post
         *
         * @return array Modified attributes
         */
        add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
            // Only add fallback if alt is missing or empty
            if ( empty( $attr['alt'] ) ) {
                // Try attachment title
                $attr['alt'] = $attachment->post_title;

                // Still empty? Use attachment filename (cleaned up)
                if ( empty( $attr['alt'] ) ) {
                    $filename    = get_attached_file( $attachment->ID );
                    $attr['alt'] = ucwords( str_replace( [ '-', '_' ], ' ',
                            pathinfo( $filename, PATHINFO_FILENAME )
                    ) );
                }
            }

            return $attr;
        }, 10, 2 );

        /**
         * Force complete thumbnail regeneration after image editing
         */
        add_action( 'wp_save_image_editor_file', function ( $dummy, $filename, $image, $mime_type, $post_id ) {
            if ( ! $post_id ) {
                return;
            }

            // Queue regeneration after save completes
            add_action( 'shutdown', function () use ( $post_id ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );

                $file_path = get_attached_file( $post_id );
                if ( $file_path ) {
                    // This regenerates ALL registered sizes from the newly edited original
                    wp_update_attachment_metadata(
                            $post_id,
                            wp_generate_attachment_metadata( $post_id, $file_path )
                    );
                }
            } );
        }, 10, 5 );
    }

    // =============================================================================
    // CORE THEME SETUP
    // =============================================================================

    /**
     * Register WordPress theme features and capabilities
     *
     * Enables core WordPress features:
     * - title-tag: Automatic <title> tag generation
     * - post-thumbnails: Featured image support
     * - html5: HTML5 markup for forms
     *
     * @return void
     *
     * @example Usage (automatically called via 'init' hook)
     * // No direct usage needed - hooked via setup_hooks()
     * // add_action('init', [$this, 'theme_supports']);
     */
    public function theme_supports(): void {
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', [ 'search-form', 'comment-form' ] );
    }

    /**
     * Register theme customizer settings
     *
     * Delegates customizer registration to the Customizer class.
     *
     * @param \WP_Customize_Manager $wp_customize WordPress customizer manager instance
     *
     * @return void
     *
     * @example Usage (automatically called via 'customize_register' hook)
     * // Hooked via setup_hooks()
     * // add_action('customize_register', [$this, 'register_customizer']);
     */
    public function register_customizer( \WP_Customize_Manager $wp_customize ): void {
        Customizer::register( $wp_customize );
    }

    /**
     * Get theme customizer setting value
     *
     * Retrieves a theme modification value with optional default fallback.
     *
     * @param string $key The setting key to retrieve
     * @param mixed $default Optional. Default value if setting not found. Default null.
     *
     * @return mixed The setting value or default
     *
     * @example Get logo URL with fallback
     * $logo = $theme->get_setting('custom_logo_url', 'default-logo.png');
     *
     * @example Check if feature is enabled
     * $enabled = $theme->get_setting('feature_enabled', false);
     */
    public function get_setting( string $key, $default = null ) {
        return get_theme_mod( $key, $default );
    }

    /**
     * Check if theme is in development mode
     *
     * Returns whether the theme is running in development mode.
     * In development mode, unminified assets are loaded and debugging is enabled.
     *
     * @return bool True if in development mode, false otherwise
     *
     * @example Conditional asset loading
     * if ($theme->is_development_mode()) {
     *     wp_enqueue_script('app', 'app.js');
     * } else {
     *     wp_enqueue_script('app', 'app.min.js');
     * }
     */
    public function is_development_mode(): bool {
        return $this->development_mode;
    }

    // =============================================================================
    // DELEGATED METHODS TO COMPONENTS
    // =============================================================================

    /**
     * Get menu manager instance
     *
     * Returns the MenuManager component responsible for custom menu rendering
     * with walkers and menu item customization.
     *
     * @return MenuManager The menu manager instance
     *
     * @example Render primary navigation
     * $menu_manager = $theme->get_menu_manager();
     * $menu_manager->render('primary', ['container' => 'nav']);
     */
    public function get_menu_manager(): MenuManager {
        return $this->menu_manager;
    }

    /**
     * Get page part registry
     *
     * Returns the complete registry of all registered page parts with their
     * field definitions, categories, and metadata. Delegated to Registry component.
     *
     * @return array Associative array of page part definitions keyed by design slug
     *
     * @example Get all registered page parts
     * $registry = $theme->get_page_part_registry();
     * foreach ($registry as $design => $config) {
     *     echo "Page part: {$design}\n";
     * }
     *
     * @example Check if page part exists
     * $registry = $theme->get_page_part_registry();
     * if (isset($registry['nok-hero'])) {
     *     // Hero page part is registered
     * }
     */
    public function get_page_part_registry(): array {
        return $this->registry->get_registry();
    }

    /**
     * Get page part field values for a specific post
     *
     * Retrieves all field values for a page part instance, with optional
     * editing context for displaying additional editor-specific data.
     *
     * @param int $post_id The post ID containing the page part
     * @param string $design The page part design slug (e.g., 'nok-hero')
     * @param bool $is_editing Optional. Whether in editing context. Default false.
     *
     * @return array Associative array of field values keyed by field name
     *
     * @example Get hero fields for current post
     * $fields = $theme->get_page_part_fields(get_the_ID(), 'nok-hero');
     * echo $fields['title']; // Output hero title
     *
     * @example Get fields in editor context
     * $fields = $theme->get_page_part_fields($post_id, 'nok-cta', true);
     * // Returns additional metadata for editor UI
     */
    public function get_page_part_fields( int $post_id, string $design, bool $is_editing = false ): array {
        return $this->meta_manager->get_page_part_fields( $post_id, $design, $is_editing );
    }

    /**
     * Generate human-readable label from field name
     *
     * Converts field names (snake_case or kebab-case) into properly formatted
     * labels for display in forms and UI. Delegated to Registry component.
     *
     * @param string $field_name Field name to convert (e.g., 'hero_title' or 'cta-button')
     *
     * @return string Human-readable label (e.g., 'Hero Title' or 'CTA Button')
     *
     * @example Generate label for form field
     * echo $theme->generate_field_label('background_color');
     * // Output: "Background Color"
     *
     * @example Generate label with acronym
     * echo $theme->generate_field_label('cta_url');
     * // Output: "CTA URL"
     */
    public function generate_field_label( string $field_name ): string {
        return $this->registry->generate_field_label( $field_name );
    }

    /**
     * Include a page part template
     *
     * Includes a page part template file with provided arguments available
     * as local variables. Template file must exist in page-parts/ directory.
     * Delegated to TemplateRenderer component.
     *
     * @param string $design Page part design slug (e.g., 'nok-hero')
     * @param array $args Optional. Variables to extract into template scope. Default [].
     *
     * @return void
     *
     * @example Include hero template with custom args
     * $theme->include_page_part_template('nok-hero', [
     *     'custom_class' => 'hero--large',
     *     'show_overlay' => true
     * ]);
     */
    public function include_page_part_template( string $design, array $args = [] ): void {
        $this->template_renderer->include_page_part_template( $design, $args );
    }

    /**
     * Embed a page part template with field data
     *
     * Renders a page part template using provided field data without requiring
     * a post context. Useful for previews and standalone components.
     * Delegated to TemplateRenderer component.
     *
     * @param string $design Page part design slug (e.g., 'nok-cta')
     * @param array $fields Field values to pass to template
     * @param bool $register_css Optional. Whether to register component CSS. Default false.
     *
     * @return void
     *
     * @example Embed CTA without post context
     * $theme->embed_page_part_template('nok-cta', [
     *     'title' => 'Get Started',
     *     'button_text' => 'Sign Up',
     *     'button_url' => '/register'
     * ], true);
     *
     * @example Embed hero for preview
     * $theme->embed_page_part_template('nok-hero', $preview_fields);
     */
    public function embed_page_part_template( string $design, array $fields, bool $register_css = false ): void {
        $this->template_renderer->embed_page_part_template( $design, $fields, $register_css );
    }

    /**
     * Embed a post part template with field data
     *
     * Renders a post part template (for individual post/article components)
     * using provided field data. Similar to page parts but specific to posts.
     * Delegated to TemplateRenderer component.
     *
     * @param string $design Post part design slug (e.g., 'post-header')
     * @param array $fields Field values to pass to template
     * @param bool $register_css Optional. Whether to register component CSS. Default false.
     *
     * @return void
     *
     * @example Embed post header
     * $theme->embed_post_part_template('post-header', [
     *     'title' => $post->post_title,
     *     'author' => get_the_author(),
     *     'date' => get_the_date()
     * ], true);
     */
    public function embed_post_part_template( string $design, array $fields, bool $register_css = false ): void {
        $this->template_renderer->embed_post_part_template( $design, $fields, $register_css );
    }

    // =============================================================================
    // CONTENT FILTERS
    // =============================================================================

    /**
     * Add WordPress block classes to paragraph tags
     *
     * Enhances classic editor content by adding 'wp-block-paragraph' class
     * to all <p> tags, ensuring consistent styling with Gutenberg blocks.
     *
     * @param string $content The post content HTML
     *
     * @return string Modified content with paragraph classes
     *
     * @example Usage (automatically applied via 'the_content' filter)
     * // Input: <p>Hello world</p>
     * // Output: <p class="wp-block-paragraph">Hello world</p>
     */
    public function enhance_paragraph_classes( $content ) {
        return str_replace( '<p>', '<p class="wp-block-paragraph">', $content );
    }

    /**
     * Process tokens in page part content
     *
     * Only processes tokens when rendering page_part post type to avoid
     * overhead on regular posts/pages.
     *
     * @param string $content Post content
     *
     * @return string Content with tokens replaced
     */
    public function process_page_part_tokens( string $content ): string {
        global $post;

        // Only process page_part post type
        if ( ! $post || get_post_type( $post ) !== 'page_part' ) {
            return $content;
        }

        return $this->template_renderer->process_content_tokens( $content );
    }

    /**
     * Conditionally hide admin bar based on URL parameter
     *
     * Allows hiding the WordPress admin bar by adding ?hide_adminbar
     * to the URL. Useful for clean screenshots and presentations.
     *
     * @param bool $show Whether to show the admin bar
     *
     * @return bool Modified show value
     *
     * @example Hide admin bar via URL
     * // Visit: https://example.com/page?hide_adminbar
     * // Admin bar will be hidden
     */
    public function maybe_hide_admin_bar( $show ) {
        if ( isset( $_GET['hide_adminbar'] ) ) {
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
     *
     * @return string Modified template path
     */
    public function category_based_single_template( string $template ): string {
        if ( ! is_singular( 'post' ) ) {
            return $template;
        }

        $categories = get_the_category();
        if ( empty( $categories ) ) {
            return $template;
        }

        foreach ( $categories as $category ) {
            $cat_template = locate_template( "single-cat-{$category->slug}.php" );
            if ( $cat_template ) {
                return $cat_template;
            }
        }

        return $template;
    }

    /**
     * Register custom meta fields for post types
     *
     * Registers meta fields for:
     * - Post type (ervaringen category): naam_patient, highlighted_excerpt, behandeld_door
     * - Vestiging CPT: street, housenumber, postal_code, city, phone, email, opening_hours
     * - Regio CPT: parent_vestiging
     *
     * Fields are registered via MetaRegistry and automatically integrated with:
     * - WordPress REST API (show_in_rest)
     * - Block editor meta panels (via MetaRegistrar)
     * - Custom sanitization callbacks per field type
     *
     * @return void
     */
    private function register_post_custom_fields(): void {
        // Get category IDs programmatically
        $experience_cat = get_category_by_slug( 'ervaringen' );

        PostMeta\MetaRegistry::register_field( 'post', 'naam_patient', [
                'type'        => 'text',
                'label'       => 'Naam patiënt',
                'placeholder' => 'Voer de naam in...',
                'description' => 'De naam wordt gebruikt op verschillende manieren, bijvoorbeeld voor de "Lees het verhaal van <naam>" links',
                'categories'  => [ $experience_cat->term_id ],
        ] );

        PostMeta\MetaRegistry::register_field( 'post', 'highlighted_excerpt', [
                'type'        => 'textarea',
                'label'       => 'Samenvatting',
                'placeholder' => 'Voer een korte samenvatting van 1-2 zinnen in...',
                'description' => 'Deze samenvatting wordt bijvoorbeeld gebruikt bij het uitlichten van dit ervaringsverhaal. Als dit veld leeg is worden de eerste 55 woorden van de tekst gebruikt.',
                'categories'  => [ $experience_cat->term_id ],
        ] );

        PostMeta\MetaRegistry::register_field( 'post', 'behandeld_door', [
                'type'        => 'post_select',
                'post_type'   => 'vestiging',
                'label'       => 'Behandelende vestiging',
                'placeholder' => 'Onbekend / Niet van toepassing',
                'description' => 'Selecteer de vestiging waar deze patiënt behandeld is, of laat dit veld op "Onbekend" staan als dit niet van toepassing is.',
                'categories'  => [ $experience_cat->term_id ],
        ] );

        // Vestiging meta fields
        PostMeta\MetaRegistry::register_field( 'vestiging', 'street', [
                'type'        => 'text',
                'label'       => 'Straat',
                'placeholder' => 'Voer straatnaam in...',
                'description' => 'De straatnaam van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'housenumber', [
                'type'        => 'text',
                'label'       => 'Huisnummer',
                'placeholder' => 'Voer huisnummer in...',
                'description' => 'Het huisnummer van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'postal_code', [
                'type'        => 'text',
                'label'       => 'Postcode',
                'placeholder' => 'Voer postcode in...',
                'description' => 'De postcode van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'city', [
                'type'        => 'text',
                'label'       => 'Stad',
                'placeholder' => 'Voer plaatsnaam in...',
                'description' => 'De plaatsnaam van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'phone', [
                'type'        => 'tel',
                'label'       => 'Telefoonnummer',
                'placeholder' => 'Voer telefoonnummer in...',
                'description' => 'Het telefoonnummer van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'email', [
                'type'        => 'email',
                'label'       => 'E-mailadres',
                'placeholder' => 'Voer e-mailadres in...',
                'description' => 'Het e-mailadres van deze vestiging',
        ] );

        PostMeta\MetaRegistry::register_field( 'vestiging', 'opening_hours', [
                'type'        => 'opening_hours',
                'label'       => 'Openingstijden',
                'description' => 'Stel hier de standaard openingstijden in. Je kunt alle weekdagen instellen op een vaste
			opening- en sluitingstijd, en daarvan afwijken door een individuele dag aan te vinken en daar andere waardes
			in te vullen. Of selecteer enkel de dagen waarop de vestiging geopend is.',
        ] );

        // Regio meta fields
        PostMeta\MetaRegistry::register_field( 'regio', 'parent_vestiging', [
                'type'        => 'post_select',
                'post_type'   => 'vestiging',
                'label'       => 'Bovenliggende vestiging',
                'placeholder' => 'Selecteer een vestiging...',
                'description' => 'De dichtstbijzijnde vestiging voor deze regio. Bepaalt de URL-structuur (/vestigingen/{vestiging}/{regio}/) en welke adresgegevens en voorlichtingen worden getoond.',
        ] );
    }

    /**
     * Register archive settings pages for all post types with archives
     *
     * Automatically creates Settings submenu for each CPT with has_archive=true.
     * Stores intro text as {post_type}_archive_intro option.
     *
     * @return void
     */
    public function register_archive_settings(): void {
        $archive_types = PostTypes::get_archive_post_types();

        foreach ( $archive_types as $post_type => $config ) {
            // Register admin menu page
            add_action( 'admin_menu', function () use ( $post_type, $config ) {
                add_submenu_page(
                        "edit.php?post_type={$post_type}",
                        __( 'Instellingen', THEME_TEXT_DOMAIN ),
                        __( 'Instellingen', THEME_TEXT_DOMAIN ),
                        'manage_options',
                        "{$post_type}-settings",
                        function () use ( $post_type, $config ) {
                            // Use specialized renderer for kennisbank
                            if ( $post_type === 'kennisbank' ) {
                                $this->render_kennisbank_settings_page( $config );
                            } else {
                                $this->render_archive_settings_page( $post_type, $config );
                            }
                        }
                );
            } );

            // Register setting
            add_action( 'admin_init', function () use ( $post_type ) {
                register_setting(
                        "{$post_type}_archive_settings",
                        "{$post_type}_archive_intro",
                        [
                                'type'              => 'string',
                                'sanitize_callback' => 'wp_kses_post',
                                'default'           => '',
                        ]
                );
            } );
        }

    }

    /**
     * Render archive settings page for any post type
     *
     * @param string $post_type The post type slug
     * @param array $config Configuration array with 'slug' and 'label' keys
     *
     * @return void
     */
    private function render_archive_settings_page( string $post_type, array $config ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $option_name = "{$post_type}_archive_intro";
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( "{$post_type}_archive_settings" ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr( $option_name ); ?>">
                                <?php _e( 'Introductietekst', THEME_TEXT_DOMAIN ); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                    get_option( $option_name, '' ),
                                    $option_name,
                                    [
                                            'textarea_rows' => 10,
                                            'media_buttons' => false,
                                    ]
                            );
                            ?>
                            <p class="description">
                                <?php
                                printf(
                                        __( 'Deze tekst wordt getoond bovenaan de pagina /%s/', THEME_TEXT_DOMAIN ),
                                        esc_html( $config['slug'] )
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get archive intro text for a post type
     *
     * @param string $post_type Post type slug
     * @param string $fallback Fallback text if option not set
     *
     * @return string Archive intro text
     */
    public static function get_archive_intro( string $post_type, string $fallback = '' ): string {
        return get_option( "{$post_type}_archive_intro", $fallback );
    }

    /**
     * Render specialized settings page for Kennisbank
     *
     * Includes:
     * - Kennisbank archive intro text
     *
     * @param array $config Configuration array with 'slug' and 'label' keys
     * @return void
     */
    private function render_kennisbank_settings_page( array $config ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'kennisbank_archive_settings' ); ?>

                <h2><?php _e( 'Kennisbank Archief', THEME_TEXT_DOMAIN ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kennisbank_archive_intro">
                                <?php _e( 'Introductietekst', THEME_TEXT_DOMAIN ); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                get_option( 'kennisbank_archive_intro', '' ),
                                'kennisbank_archive_intro',
                                [ 'textarea_rows' => 6, 'media_buttons' => false ]
                            );
                            ?>
                            <p class="description">
                                <?php _e( 'Getoond bovenaan /kennisbank/', THEME_TEXT_DOMAIN ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}