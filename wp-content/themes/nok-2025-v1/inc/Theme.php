<?php
// inc/Theme.php

namespace NOK2025\V1;

use NOK2025\V1\PostTypes;

final class Theme {
    private static ?Theme $instance = null;

    // Settings store (can hold customizer values)
    private array $settings = [];

    public function __construct() {
        // ensure CPTs are registered
        new PostTypes();
    }

    public static function get_instance(): Theme {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    private function setup_hooks(): void {
        add_action( 'after_setup_theme', [ $this, 'theme_supports' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets'] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'backend_assets'] );

        add_action( 'customize_register', [ $this, 'register_customizer' ] );
        // ...other hooks

        add_action( 'add_meta_boxes',          [ $this, 'add_design_meta_box' ] );
        add_action( 'save_post_page_part',     [ $this, 'save_design_meta' ], 10, 2 );
        add_action( 'init',                    [ $this, 'register_design_meta' ] );

        add_filter( 'the_content', function( $content ) {
            $content = str_replace(
                '<p>',
                '<p class="wp-block-paragraph">',
                $content
            );
            return $content;
        } );

        add_action( 'rest_api_init', function() {
            register_rest_route(
                'nok-2025-v1/v1',
                '/embed-page-part/(?P<id>\d+)',
                [
                    'methods'  => 'GET',
                    'permission_callback' => '__return_true',
                    'callback'            => function( \WP_REST_Request $request ) {
                        $id   = (int) $request->get_param( 'id' );
                        $post = get_post( $id );
                        if ( ! $post || $post->post_type !== 'page_part' ) {
                            status_header(404);
                            exit;
                        }

                        $args = [ 'post' => $post ];
                        $design = get_post_meta( $id, 'design_slug', true ) ?: 'header-top-level';
                        $css_uris = [
                            get_stylesheet_directory_uri() . '/assets/css/nok-components.css',
                            get_stylesheet_directory_uri() . '/assets/css/color_tests-v2.css',
                            get_stylesheet_directory_uri() . "/template-parts/page-parts/{$design}.css",
	                        get_stylesheet_directory_uri() . '/assets/css/nok-page-parts-editor-styles.css',
                        ];

                        // build your CSS‑links + $html body exactly as above…
                        header( 'Content-Type: text/html; charset=utf-8' );
                        $html = '<!doctype html><html><head><meta charset="utf-8">';
                        foreach ( $css_uris as $uri ) {
                            $html .= '<link rel="stylesheet" href="' . esc_url( $uri ) . '">';
                        }
						//In the REST callback context, WordPress doesn’t think the current user can't edit.
	                    $edit_link = admin_url( "post.php?post={$id}&action=edit" );
	                    $html      .= '</head><body>
							<nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-1 halign-center valign-center">
							<a href="' . $edit_link . '" type="button" target="_blank" class="nok-button nok-align-self-to-sm-stretch fill-group-column nok-bg-darkerblue nok-text-contrast no-shadow" tabindex="0">Bewerken</a>
                            </nok-screen-mask>';
                        ob_start();
                        include get_theme_file_path( "template-parts/page-parts/{$design}.php" );
                        $html .= ob_get_clean();
                        $html .= '</body></html>';
                        print $html;
                        exit;
                    },
                ]
            );
        } );

    }

    public function theme_supports(): void {
        // Add support for post thumbnails, title tag, custom logo…
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', [ 'search-form', 'comment-form' ] );
        // Optionally: block editor settings via theme.json (WP 5.8+)
    }

    public function frontend_assets(): void {
        wp_register_style(
            'nok-components-css',
            THEME_ROOT . '/assets/css/nok-components.css',
            [],
            filemtime( THEME_ROOT_ABS . '/assets/css/nok-components.css')
        );
        wp_register_style(
            'nok-colors-css',
            THEME_ROOT . '/assets/css/color_tests-v2.css',
            [],
            filemtime( THEME_ROOT_ABS . '/assets/css/color_tests-v2.css')
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

    /**
     * Registry of page part templates.
     * Lazy-loaded on first access.
     */
    private ?array $part_registry = null;

    /**
     * Scan all page‑part templates and pull their metadata.
     *
     * @return array Array of [ slug => [ 'name' => ..., 'description' => ..., 'icon' => ..., 'css' => ... ] ]
     */
    private function get_page_part_registry(): array {
        if ( $this->part_registry !== null ) {
            return $this->part_registry;
        }
        $files = glob( THEME_ROOT_ABS . '/template-parts/page-parts/*.php' );
        $this->part_registry = [];

        foreach ( $files as $file ) {
            $data = get_file_data( $file, [
                'name'        => 'Template Name',
                'description' => 'Description',
                'slug'        => 'Slug',
                'icon'        => 'Icon',
                'css'         => 'CSS',
            ] );

            if ( empty( $data['slug'] ) ) {
                // fallback to filename if slug header missing
                $data['slug'] = basename( $file, '.php' );
            }
            // ensure CSS handle; default: page-part-<slug>
            if ( empty( $data['css'] ) ) {
                $data['css'] = "page-part-{$data['slug']}";
            }

            $this->part_registry[ $data['slug'] ] = $data;
        }

        return $this->part_registry;
    }

    public function backend_assets(): void {
        $parts = $this->get_page_part_registry();

        foreach ( $parts as $slug => $meta ) {
            // 1) register CSS
            $css_file = THEME_ROOT_ABS . "/template-parts/page-parts/{$meta['css']}.css";
            if ( file_exists( $css_file ) ) {
                wp_enqueue_style(
                    $meta['css'],
                    THEME_ROOT . "/template-parts/page-parts/{$meta['css']}.css",
                    [],
                    filemtime( $css_file )
                );
            } else {
                // Fallback to a default CSS if not found
                wp_enqueue_style(
                    $slug,
                    THEME_ROOT . "/template-parts/page-parts/{$slug}.css",
                    [],
                    filemtime( THEME_ROOT_ABS . "/template-parts/page-parts/{$slug}.css" )
                );
            }
        }
    }

    /**
     * Register our meta so it’s in the REST API (and Gutenberg can save it).
     */
    public function register_design_meta(): void {
        register_post_meta( 'page_part', 'design_slug', [
            'type'              => 'string',
            'show_in_rest'      => true,
            'single'            => true,
            'sanitize_callback' => 'sanitize_key',
            'default'           => '',
        ] );
    }

    /**
     * Add a dropdown meta-box under "Page Attributes" for selecting the design.
     */
    public function add_design_meta_box(): void {
        add_meta_box(
            'page_part_design',
            __( 'Page Part Design', 'your-text-domain' ),
            [ $this, 'render_design_meta_box' ],
            'page_part',
            'side',
            'default'
        );
    }

    /**
     * Output the <select> of available templates.
     */
    public function render_design_meta_box( \WP_Post $post ): void {
        $registry     = $this->get_page_part_registry();
        $current_slug = get_post_meta( $post->ID, 'design_slug', true );
        wp_nonce_field( 'save_page_part_design', '_page_part_design_nonce' );

        echo '<label for="page_part_design_slug">'
            . __( 'Select a design template', 'your-text-domain' )
            . "</label>\n";
        echo '<select name="page_part_design_slug" id="page_part_design_slug" style="width:100%;">';
        echo '<option value="">' . esc_html__( '&mdash; Select &mdash;', 'your-text-domain' ) . '</option>';

        foreach ( $registry as $slug => $data ) {
            printf(
                "<option value=\"%1\$s\" %2\$s>%3\$s</option>\n",
                esc_attr( $slug ),
                selected( $current_slug, $slug, false ),
                esc_html( $data['name'] )
            );
        }

        echo '</select>';
    }

    /**
     * Save the selected design when the post is saved.
     */
    public function save_design_meta( int $post_id,\WP_Post $post ): void {
        // Verify nonce
        if ( empty( $_POST['_page_part_design_nonce'] )
            || ! wp_verify_nonce( wp_unslash( $_POST['_page_part_design_nonce'] ), 'save_page_part_design' )
        ) {
            return;
        }

        // Only on autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and update.
        $new = isset( $_POST['page_part_design_slug'] )
            ? sanitize_key( wp_unslash( $_POST['page_part_design_slug'] ) )
            : '';

        update_post_meta( $post_id, 'design_slug', $new );
    }


}
