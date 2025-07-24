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

		// Actions

        add_action( 'init', [ $this, 'theme_supports' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets'] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'backend_assets'] );

        add_action( 'customize_register', [ $this, 'register_customizer' ] );
        // ...other hooks

        add_action( 'add_meta_boxes',          [ $this, 'add_design_meta_box' ] );
	    add_action( 'add_meta_boxes', function() {
		    // only on the block editor screen for page_part
		    if ( function_exists( 'is_block_editor' ) && is_block_editor() ) {
			    remove_meta_box( 'page-part-design', 'page_part', 'side' );
		    }
	    });
	    add_action( 'init', [ $this, 'handle_preview_meta' ] );
	    add_action( 'init', [ $this, 'handle_preview_rendering' ] );

        add_action( 'save_post_page_part',     [ $this, 'save_design_meta' ], 10, 2 );
        add_action( 'init',                    [ $this, 'register_design_meta' ] );

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

	                    // Check for temporary preview meta first
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
							<nok-screen-mask class="nok-bg-darkerblue nok-dark-bg-darkerblue--darker nok-z-top halign-center valign-center">
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

	    add_action( 'admin_enqueue_scripts', function( $hook ) {
		    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			    return;
		    }
		    // only load on our CPT
		    $screen = get_current_screen();
		    if ( $screen->post_type !== 'page_part' ) {
			    return;
		    }

		    $asset = require get_theme_file_path( '/assets/js/nok-page-part-preview.asset.php' );
		    wp_enqueue_script(
			    'nok-page-part-live-preview',
			    get_stylesheet_directory_uri() . '/assets/js/nok-page-part-preview.js',
			    $asset['dependencies'],
			    $asset['version']
		    );
	    });

		add_action('admin_head', [$this, 'custom_editor_inline_styles']);

		// Filters

	    add_filter( 'the_content', function( $content ) {
		    $content = str_replace(
			    '<p>',
			    '<p class="wp-block-paragraph">',
			    $content
		    );
		    return $content;
	    } );
	    add_filter( 'show_admin_bar', function( $show ) {
		    // if our preview iframe asked to hide it, turn it off
		    if ( isset( $_GET['hide_adminbar'] ) ) {
			    return false;
		    }
		    return $show;
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

	public function custom_editor_inline_styles(): void {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			echo '<style>
			        .editor-styles-wrapper {
			            min-height: 35vh !important;
			        }
			        .editor-styles-wrapper::after {
			            height: 0 !important;
			        }
			    </style>';
		}
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
                $data['slug'] = sanitize_title($data['name'] ?? basename( $file, '.php' ));
            }
            // ensure CSS handle; default: <slug>
            if ( empty( $data['css'] ) ) {
                $data['css'] = "{$data['slug']}";
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
            } else if ( file_exists (THEME_ROOT_ABS . "/template-parts/page-parts/{$slug}.css" ) ){
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

	public function handle_preview_meta(): void {
		add_action( 'wp_ajax_store_preview_meta', function() {
			$post_id = (int) $_POST['post_id'];
			$design_slug = sanitize_key( $_POST['design_slug'] );

			if ( $post_id && $design_slug ) {
				set_transient( "preview_design_slug_{$post_id}", $design_slug, 300 );
				wp_send_json_success( "Stored design_slug: {$design_slug}" );
			} else {
				wp_send_json_error( 'Missing data' );
			}
		});
	}

	/**
	 * Modify preview rendering to use transient data
	 */
	public function handle_preview_rendering(): void {
		add_action( 'template_redirect', function() {
			// Only on preview pages for page_part posts
			if ( is_preview() && get_post_type() === 'page_part' ) {
				$post_id = get_the_ID();

				// Check for temporary preview meta
				$preview_design = get_transient( "preview_design_slug_{$post_id}" );
				if ( $preview_design ) {
					// Override the meta value for this request
					add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key ) use ( $post_id, $preview_design ) {
						if ( $object_id == $post_id && $meta_key === 'design_slug' ) {
							return [ $preview_design ];
						}
						return $value;
					}, 10, 3 );
				}
			}
		});
	}

    /**
     * Add a dropdown meta-box under "Page Attributes" for selecting the design.
     */
    public function add_design_meta_box(): void {
        add_meta_box(
            'page_part_design',
            __( 'NOK - Page Part Design', THEME_TEXT_DOMAIN ),
            [ $this, 'render_design_meta_box' ],
            'page_part',
            'side',
            'default'
        );
		//live preview
	    add_meta_box(
		    'gutenberg-embedded-preview',
		    __( 'NOK - Live Page Part Preview', THEME_TEXT_DOMAIN ),
		    [ $this, 'gep_render_preview_box'],
		    [ 'page_part' ],     // your CPT slug(s)
		    'normal',            // under the editor
		    'high'
	    );
    }

	public function gep_render_preview_box( \WP_Post $post ) {
		// nonce for future REST calls (if you need to save/fetch autosaves)
		wp_nonce_field( 'gep_preview_nonce', 'gep_preview_nonce' );

		// container for our React app
		echo '<button id="nok-page-part-preview-button" type="button" class="button button-primary">' . esc_html__( 'Refresh Preview', THEME_TEXT_DOMAIN ) . '</button>
		<div><p>Let op: Page Parts zijn niet afzonderlijk publiek benaderbaar (of indexeerbaar) en zijn ontworpen om onderdeel van een pagina te zijn.</p></div>
	    
	    <div id="nok-page-part-preview-root" style="border:1px solid #ddd; min-height:300px"></div>';
	}

	/**
     * Output the <select> of available templates.
     */
    public function render_design_meta_box( \WP_Post $post ): void {
        $registry     = $this->get_page_part_registry();
        $current_slug = get_post_meta( $post->ID, 'design_slug', true );
        wp_nonce_field( 'save_page_part_design', '_page_part_design_nonce' );

        echo '<label for="page_part_design_slug">'
            . __( 'Select a design template', THEME_TEXT_DOMAIN )
            . "</label>\n";
        echo '<select name="page_part_design_slug" id="page_part_design_slug">';
        echo '<option value="">' . esc_html__( '&mdash; Select &mdash;', THEME_TEXT_DOMAIN ) . '</option>';

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
