<?php

namespace NOK2025\V1;

/**
 * PostTypes - Custom post type registration and protection
 *
 * Registers theme custom post types:
 * - page_part: Reusable page components for embedding
 * - template_layout: Block editor templates for single post types
 * - vestiging: Clinic locations with address and contact info
 * - regio: SEO landing pages for regions near a vestiging
 *   URL structure: /vestigingen/{vestiging-slug}/{regio-slug}/
 * - kennisbank: Knowledge base with FAQ and informational articles
 *   URL structure: /kennisbank/{category}/{post-slug}/
 * - vragenlijst: Interactive questionnaires (e.g. inclusie-check)
 *   Not public — rendered via post-part templates in popups/pages
 *
 * Protects internal post types from public access:
 * - Returns 404 for page_part and template_layout single views
 * - Prevents search engine indexing
 * - Allows logged-in users (for previews)
 *
 * @example Initialize in theme setup
 * $post_types = new PostTypes();
 *
 * @example Query vestigingen
 * $locations = get_posts(['post_type' => 'vestiging']);
 *
 * @package NOK2025\V1
 */
class PostTypes {
	/** @var array Track post types with archives for dynamic settings pages */
	private static array $archive_post_types = [];
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_kennisbank_permalink_filter' ] );
		add_action( 'init', [ $this, 'register_regio_permalink_filter' ] );
		add_action( 'template_redirect', [ $this, 'protect_post_types' ] );
		add_filter( 'redirect_canonical', [ $this, 'block_page_part_canonical_guess' ], 10, 2 );
		add_filter( 'single_template', [ $this, 'kennisbank_category_template' ] );
		add_filter( 'request', [ $this, 'disambiguate_kennisbank_urls' ] );
		add_filter( 'rest_post_search_query', [ $this, 'exclude_internal_types_from_rest_search' ], 10, 2 );
		add_filter( 'rest_page_part_collection_params', [ $this, 'raise_page_part_per_page_limit' ] );
		add_action( 'admin_notices', [ $this, 'display_vragenlijst_config_notices' ] );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types(): void {
		$this->register_page_part_post_type();
		$this->register_template_layout_post_type();
		$this->register_vestiging_post_type();
		$this->register_regio_post_type();
		$this->register_kennisbank_taxonomy();
		$this->register_kennisbank_post_type();
		$this->register_vragenlijst_post_type();
		$this->track_archive_post_types();
	}

	/**
	 * Register the page_part custom post type
	 */
	private function register_page_part_post_type(): void {
		$labels = [
			'name'               => __( 'Page Parts', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Page Part', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Add New Page Part', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Edit Page Part', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'New Page Part', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'View Page Part', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'View Page Parts', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Search Page Parts', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'No page parts found.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'No page parts found in Trash.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'All Page Parts', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Page Part Archives', THEME_TEXT_DOMAIN ),
			'attributes'         => __( 'Page Part Attributes', THEME_TEXT_DOMAIN ),
		];

		// Page parts are only publicly queryable for logged-in users — the
		// iframe preview in the page-part post editor needs the canonical URL
		// to be resolvable. Anonymous visits should 404 rather than 301 through
		// the CPT, which previously masked broken content links (see 1.6 in
		// plan-seo-technical.md).
		//
		// exclude_from_search is also true so that WordPress's
		// redirect_guess_404_permalink() (the "did you mean?" rescue) does not
		// consider page_part slugs when trying to rescue a would-be 404. This
		// is reinforced by a redirect_canonical filter below that defensively
		// blocks any guessed URL targeting /page-part/*.
		$args = [
			'labels'              => $labels,
			'description'         => __( 'Reusable page components that can be embedded into pages.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => is_user_logged_in(),
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'page-parts',
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-layout',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields'
			],
			'taxonomies'          => [ 'category', 'post_tag' ],
			'has_archive'         => false,
			'rewrite'             => [
				'slug'       => 'page-part',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'page_part', $args );
	}

	/**
	 * Register the template_layout custom post type
	 *
	 * Template layouts are block editor compositions used to define
	 * the structure of single post templates (e.g., single-cat-ervaringen.php).
	 * They allow admins to configure template zones using page part blocks
	 * without hard-coding post IDs in template files.
	 */
	private function register_template_layout_post_type(): void {
		$labels = [
			'name'               => __( 'Template Layouts', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Template Layout', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Add New Template Layout', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Edit Template Layout', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'New Template Layout', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'View Template Layout', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'View Template Layouts', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Search Template Layouts', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'No template layouts found.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'No template layouts found in Trash.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'All Template Layouts', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Block editor layouts for configuring single post templates.', THEME_TEXT_DOMAIN ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'template-layouts',
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-schedule',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'revisions',
			],
			'has_archive'         => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'template_layout', $args );
	}

	/**
	 * Register the vestiging custom post type
	 *
	 * Vestigingen (locations/offices) represent physical clinic locations.
	 * Each vestiging has address, contact info, and opening hours.
	 *
	 * Meta fields (registered in Theme.php):
	 * - _street, _housenumber, _postal_code, _city
	 * - _phone (formatted via Helpers::format_phone())
	 * - _email
	 * - _opening_hours (JSON, formatted via Helpers::format_opening_hours())
	 *
	 * URLs:
	 * - Archive: /vestigingen/
	 * - Single: /vestigingen/{slug}/
	 *
	 * Templates:
	 * - archive-vestiging.php
	 * - template-parts/single-vestiging-content.php
	 */
	private function register_vestiging_post_type(): void {
		$labels = [
			'name'               => __( 'Vestigingen', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Vestiging', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuwe vestiging toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Vestiging bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuwe vestiging', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Vestiging bekijken', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'Vestigingen bekijken', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Vestigingen zoeken', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'Geen vestigingen gevonden.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'Geen vestigingen gevonden in prullenbak.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'Alle vestigingen', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Vestiging archieven', THEME_TEXT_DOMAIN ),
			'attributes'         => __( 'Vestiging attributen', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Kliniek vestigingen met adres- en contactgegevens.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'vestigingen',
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-location-alt',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'custom-fields',
			],
			'has_archive'         => 'vestigingen',
			'rewrite'             => [
				'slug'       => 'vestigingen',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'vestiging', $args );

		// Track for archive settings
		if ($args['has_archive']) {
			self::$archive_post_types['vestiging'] = [
				'slug' => is_string($args['has_archive']) ? $args['has_archive'] : 'vestiging',
				'label' => $labels['name'],
			];
		}
	}

	/**
	 * Register the regio custom post type
	 *
	 * Regio's are SEO landing pages for regions near a vestiging.
	 * Each regio links to a parent vestiging via the _parent_vestiging meta field.
	 * Unlike vestigingen, regio's have no address or contact info of their own.
	 *
	 * Meta fields (registered in Theme.php):
	 * - _parent_vestiging (post_select → vestiging)
	 *
	 * URLs (via custom rewrite rules):
	 * - Single: /vestigingen/{vestiging-slug}/{regio-slug}/
	 *
	 * Templates:
	 * - single-regio.php
	 * - template-parts/single-regio-content.php
	 */
	private function register_regio_post_type(): void {
		$labels = [
			'name'               => __( "Regio's", THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Regio', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuwe regio toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Regio bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuwe regio', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Regio bekijken', THEME_TEXT_DOMAIN ),
			'view_items'         => __( "Regio's bekijken", THEME_TEXT_DOMAIN ),
			'search_items'       => __( "Regio's zoeken", THEME_TEXT_DOMAIN ),
			'not_found'          => __( "Geen regio's gevonden.", THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( "Geen regio's gevonden in prullenbak.", THEME_TEXT_DOMAIN ),
			'all_items'          => __( "Regio's", THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Regio archieven', THEME_TEXT_DOMAIN ),
			'attributes'         => __( 'Regio attributen', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( "SEO-landingspagina's voor regio's rondom vestigingen.", THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=vestiging',
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'regios',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'custom-fields',
			],
			'has_archive'         => false,
			'rewrite'             => false, // Custom rewrite rules in register_regio_permalink_filter()
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'regio', $args );
	}

	/**
	 * Register the kennisbank_categories taxonomy
	 *
	 * Must be registered BEFORE the kennisbank post type for rewrite rules to work.
	 */
	private function register_kennisbank_taxonomy(): void {
		$labels = [
			'name'              => __( 'Kennisbank Categorieën', THEME_TEXT_DOMAIN ),
			'singular_name'     => __( 'Kennisbank Categorie', THEME_TEXT_DOMAIN ),
			'search_items'      => __( 'Zoek categorieën', THEME_TEXT_DOMAIN ),
			'all_items'         => __( 'Alle categorieën', THEME_TEXT_DOMAIN ),
			'parent_item'       => __( 'Bovenliggende categorie', THEME_TEXT_DOMAIN ),
			'parent_item_colon' => __( 'Bovenliggende categorie:', THEME_TEXT_DOMAIN ),
			'edit_item'         => __( 'Categorie bewerken', THEME_TEXT_DOMAIN ),
			'update_item'       => __( 'Categorie bijwerken', THEME_TEXT_DOMAIN ),
			'add_new_item'      => __( 'Nieuwe categorie toevoegen', THEME_TEXT_DOMAIN ),
			'new_item_name'     => __( 'Nieuwe categorienaam', THEME_TEXT_DOMAIN ),
			'menu_name'         => __( 'Categorieën', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'kennisbank_categories',
			'rewrite'           => [
				'slug'         => 'kennisbank',
				'with_front'   => false,
				'hierarchical' => true,
			],
		];

		register_taxonomy( 'kennisbank_categories', [ 'kennisbank' ], $args );
	}

	/**
	 * Register the kennisbank custom post type
	 *
	 * Kennisbank (knowledge base) contains FAQ items and informational articles.
	 * URL structure: /kennisbank/{category}/{post-slug}/
	 *
	 * Previously registered via ACF - migrated to theme for custom permalink structure.
	 */
	private function register_kennisbank_post_type(): void {
		$labels = [
			'name'               => __( 'Kennisbank', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Kennisbank item', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuw item toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Item bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuw item', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Item bekijken', THEME_TEXT_DOMAIN ),
			'view_items'         => __( 'Items bekijken', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Items zoeken', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'Geen items gevonden.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'Geen items gevonden in prullenbak.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'Alle items', THEME_TEXT_DOMAIN ),
			'archives'           => __( 'Kennisbank archief', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'In de kennisbank vind je naast veelgestelde vragen ook veel extra informatie over de behandeling van obesitas.', THEME_TEXT_DOMAIN ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'kennisbank',
			'menu_position'       => 7,
			'menu_icon'           => 'dashicons-excerpt-view',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields',
			],
			'taxonomies'          => [ 'kennisbank_categories' ],
			'has_archive'         => 'kennisbank',
			'rewrite'             => [
				'slug'       => 'kennisbank/%kennisbank_categories%',
				'with_front' => false,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'kennisbank', $args );

		// Track for archive settings
		self::$archive_post_types['kennisbank'] = [
			'slug'  => 'kennisbank',
			'label' => $labels['name'],
		];
	}

	/**
	 * Register the vragenlijst custom post type
	 *
	 * Vragenlijsten are interactive questionnaires (e.g., the inclusie-check).
	 * Not public — rendered via post-part templates embedded in popups or pages.
	 * Config stored as JSON in _vl_config meta field.
	 */
	private function register_vragenlijst_post_type(): void {
		$labels = [
			'name'               => __( 'Vragenlijsten', THEME_TEXT_DOMAIN ),
			'singular_name'      => __( 'Vragenlijst', THEME_TEXT_DOMAIN ),
			'add_new_item'       => __( 'Nieuwe vragenlijst toevoegen', THEME_TEXT_DOMAIN ),
			'edit_item'          => __( 'Vragenlijst bewerken', THEME_TEXT_DOMAIN ),
			'new_item'           => __( 'Nieuwe vragenlijst', THEME_TEXT_DOMAIN ),
			'view_item'          => __( 'Vragenlijst bekijken', THEME_TEXT_DOMAIN ),
			'search_items'       => __( 'Vragenlijsten zoeken', THEME_TEXT_DOMAIN ),
			'not_found'          => __( 'Geen vragenlijsten gevonden.', THEME_TEXT_DOMAIN ),
			'not_found_in_trash' => __( 'Geen vragenlijsten gevonden in prullenbak.', THEME_TEXT_DOMAIN ),
			'all_items'          => __( 'Alle vragenlijsten', THEME_TEXT_DOMAIN ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Interactieve vragenlijsten voor zelfbeoordeling.', THEME_TEXT_DOMAIN ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'vragenlijsten',
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-forms',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [
				'title',
				'editor',
				'revisions',
				'custom-fields',
			],
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		];

		register_post_type( 'vragenlijst', $args );

		register_post_meta( 'vragenlijst', '_vl_config', [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => [ $this, 'sanitize_vragenlijst_config' ],
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		] );
	}

	/**
	 * Sanitize and validate the _vl_config JSON meta field
	 *
	 * Validates structure on every save. On failure, stores a transient
	 * admin notice but does NOT prevent the save — editors can fix errors
	 * iteratively.
	 *
	 * @param mixed $value Raw meta value (JSON string)
	 *
	 * @return string Sanitized JSON string
	 */
	public function sanitize_vragenlijst_config( $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$config = json_decode( $value, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->store_vragenlijst_notice( 'Ongeldige JSON: ' . json_last_error_msg() );

			return is_string( $value ) ? $value : '';
		}

		$errors         = [];
		$valid_types    = [ 'text', 'number', 'radio', 'select', 'checkbox', 'info' ];
		$valid_styles   = [ 'positive', 'neutral', 'negative' ];
		$valid_ops      = [ '==', '!=', '>', '>=', '<', '<=' ];
		$valid_actions  = [ 'skip_to' ];

		// --- Validate questions ---
		if ( empty( $config['questions'] ) || ! is_array( $config['questions'] ) ) {
			$errors[] = 'Minstens één vraag is vereist (questions array)';
		} else {
			$question_ids = [];
			foreach ( $config['questions'] as $i => $q ) {
				$pos = 'Vraag ' . ( $i + 1 );

				if ( empty( $q['id'] ) || ! is_string( $q['id'] ) ) {
					$errors[] = "{$pos}: 'id' is verplicht (string)";
				} elseif ( in_array( $q['id'], $question_ids, true ) ) {
					$errors[] = "{$pos}: duplicaat id '{$q['id']}'";
				} else {
					$question_ids[] = $q['id'];
				}

				if ( empty( $q['type'] ) || ! in_array( $q['type'], $valid_types, true ) ) {
					$errors[] = "{$pos}: ongeldig of ontbrekend type";
				}

				if ( empty( $q['label'] ) && ( $q['type'] ?? '' ) !== 'info' ) {
					$errors[] = "{$pos}: 'label' is verplicht";
				}

				if ( in_array( $q['type'] ?? '', [ 'radio', 'select' ], true ) ) {
					if ( empty( $q['options'] ) ) {
						$errors[] = "{$pos}: 'options' is verplicht voor type '{$q['type']}'";
					} else {
						foreach ( $q['options'] as $opt_i => $opt ) {
							if ( ! isset( $opt['value'] ) || $opt['value'] === '' ) {
								$errors[] = "{$pos}: optie " . ( $opt_i + 1 ) . " heeft een lege waarde";
							}
						}
					}
				}

				// Validate branch target references
				if ( ! empty( $q['branch'] ) ) {
					$branch = $q['branch'];
					if ( ! empty( $branch['op'] ) && ! in_array( $branch['op'], $valid_ops, true ) ) {
						$errors[] = "{$pos}: branch operator '{$branch['op']}' is ongeldig";
					}
					if ( ! empty( $branch['action'] ) && ! in_array( $branch['action'], $valid_actions, true ) ) {
						$errors[] = "{$pos}: branch action '{$branch['action']}' is ongeldig";
					}
				}
			}
		}

		// --- Validate results ---
		$result_ids  = [];
		$has_default = false;

		if ( empty( $config['results'] ) || ! is_array( $config['results'] ) ) {
			$errors[] = 'Minstens één resultaat is vereist (results array)';
		} else {
			foreach ( $config['results'] as $i => $r ) {
				$pos = 'Resultaat ' . ( $i + 1 );

				if ( empty( $r['id'] ) || ! is_string( $r['id'] ) ) {
					$errors[] = "{$pos}: 'id' is verplicht (string)";
				} elseif ( in_array( $r['id'], $result_ids, true ) ) {
					$errors[] = "{$pos}: duplicaat id '{$r['id']}'";
				} else {
					$result_ids[] = $r['id'];
				}

				if ( empty( $r['title'] ) ) {
					$errors[] = "{$pos}: 'title' is verplicht";
				}

				if ( ! isset( $r['condition'] ) ) {
					$errors[] = "{$pos}: 'condition' is verplicht";
				} elseif ( $r['condition'] === 'default' ) {
					$has_default = true;
				}

				if ( ! empty( $r['style'] ) && ! in_array( $r['style'], $valid_styles, true ) ) {
					$errors[] = "{$pos}: ongeldige style '{$r['style']}'";
				}

				// Sanitize body HTML
				if ( ! empty( $r['body'] ) ) {
					$config['results'][ $i ]['body'] = wp_kses_post( $r['body'] );
				}

				// Validate CTA URL — only relative paths or same-domain URLs allowed
				if ( ! empty( $r['cta_url'] ) ) {
					$url = $r['cta_url'];
					if ( ! str_starts_with( $url, '/' ) ) {
						$host = wp_parse_url( $url, PHP_URL_HOST );
						if ( $host && ! str_ends_with( $host, 'obesitaskliniek.nl' ) ) {
							$config['results'][ $i ]['cta_url'] = '';
							$errors[]                           = "{$pos}: externe URL's zijn niet toegestaan in CTA";
						}
					}
				}
			}

			if ( ! $has_default ) {
				$errors[] = "Precies één resultaat moet condition: \"default\" hebben";
			}
		}

		// Validate branch targets reference existing question/result IDs
		$all_ids = array_merge( $question_ids ?? [], $result_ids );
		foreach ( ( $config['questions'] ?? [] ) as $i => $q ) {
			if ( ! empty( $q['branch']['target'] ) ) {
				$target = $q['branch']['target'];
				if ( is_string( $target ) && ! in_array( $target, $all_ids, true ) ) {
					$errors[] = 'Vraag ' . ( $i + 1 ) . ": branch target '{$target}' verwijst naar onbekend ID";
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$this->store_vragenlijst_notice( implode( '; ', $errors ) );
		}

		// Re-encode to persist sanitized body HTML
		return wp_json_encode( $config );
	}

	/**
	 * Store a vragenlijst config validation notice as a user transient
	 *
	 * @param string $message Error description
	 */
	private function store_vragenlijst_notice( string $message ): void {
		set_transient(
			'vragenlijst_config_errors_' . get_current_user_id(),
			$message,
			60
		);
	}

	/**
	 * Display vragenlijst config validation notices in wp-admin
	 */
	public function display_vragenlijst_config_notices(): void {
		$transient_key = 'vragenlijst_config_errors_' . get_current_user_id();
		$message       = get_transient( $transient_key );

		if ( $message ) {
			delete_transient( $transient_key );
			echo '<div class="notice notice-warning is-dismissible"><p><strong>Vragenlijst configuratie:</strong> '
			     . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Register permalink filter and rewrite rules for kennisbank posts
	 *
	 * Supports hierarchical category URLs:
	 * - /kennisbank/{parent}/{child}/{post}/ (3 segments)
	 * - /kennisbank/{category}/{post}/ (2 segments, for top-level categories)
	 *
	 * Pagination rules must come BEFORE post rules because the generic
	 * post rules would otherwise match pagination URLs. For example,
	 * /kennisbank/veelgestelde-vragen/page/2/ has 3 segments and would
	 * match the 3-segment post rule, setting kennisbank=2 → 404.
	 *
	 * Rule order (most specific first):
	 * 1. Child category pagination: /kennisbank/{parent}/{child}/page/{n}/
	 * 2. Top-level category pagination: /kennisbank/{category}/page/{n}/
	 * 3. Main archive pagination: /kennisbank/page/{n}/
	 * 4. Post in child category: /kennisbank/{parent}/{child}/{post}/
	 * 5. Post in top-level category: /kennisbank/{category}/{post}/
	 */
	public function register_kennisbank_permalink_filter(): void {
		add_filter( 'post_type_link', [ $this, 'kennisbank_permalink' ], 10, 2 );

		// --- Pagination rules (must come BEFORE generic post rules) ---

		// Child category archive pagination: /kennisbank/{parent}/{child}/page/{n}/
		add_rewrite_rule(
			'^kennisbank/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$',
			'index.php?kennisbank_categories=$matches[1]/$matches[2]&paged=$matches[3]',
			'top'
		);

		// Top-level category archive pagination: /kennisbank/{category}/page/{n}/
		add_rewrite_rule(
			'^kennisbank/([^/]+)/page/?([0-9]{1,})/?$',
			'index.php?kennisbank_categories=$matches[1]&paged=$matches[2]',
			'top'
		);

		// Main archive pagination: /kennisbank/page/{n}/
		add_rewrite_rule(
			'^kennisbank/page/?([0-9]{1,})/?$',
			'index.php?post_type=kennisbank&paged=$matches[1]',
			'top'
		);

		// --- Post rules ---

		// Hierarchical categories: /kennisbank/{parent}/{child}/{post-slug}/
		// Must come BEFORE the 2-segment rule
		add_rewrite_rule(
			'^kennisbank/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?kennisbank=$matches[3]',
			'top'
		);

		// Top-level categories: /kennisbank/{category}/{post-slug}/
		add_rewrite_rule(
			'^kennisbank/([^/]+)/([^/]+)/?$',
			'index.php?kennisbank=$matches[2]',
			'top'
		);
	}

	/**
	 * Register permalink filter and rewrite rule for regio posts
	 *
	 * Generates nested URLs under vestigingen:
	 * - /vestigingen/{vestiging-slug}/{regio-slug}/
	 *
	 * Pagination rule must come BEFORE the post rule because
	 * /vestigingen/page/2/ has 2 segments and would otherwise
	 * match the regio post rule, setting regio=2 → 404.
	 */
	public function register_regio_permalink_filter(): void {
		add_filter( 'post_type_link', [ $this, 'regio_permalink' ], 10, 2 );

		// Vestigingen archive pagination: /vestigingen/page/{n}/
		add_rewrite_rule(
			'^vestigingen/page/?([0-9]{1,})/?$',
			'index.php?post_type=vestiging&paged=$matches[1]',
			'top'
		);

		// Rewrite rule: /vestigingen/{vestiging-slug}/{regio-slug}/
		add_rewrite_rule(
			'^vestigingen/([^/]+)/([^/]+)/?$',
			'index.php?regio=$matches[2]',
			'top'
		);
	}

	/**
	 * Filter regio permalinks to nest under parent vestiging
	 *
	 * Generates URLs like /vestigingen/beverwijk/alkmaar/
	 * where "beverwijk" is the parent vestiging slug and "alkmaar" is the regio slug.
	 *
	 * @param string   $permalink The post permalink
	 * @param \WP_Post $post      The post object
	 * @return string Modified permalink
	 */
	public function regio_permalink( string $permalink, \WP_Post $post ): string {
		if ( $post->post_type !== 'regio' ) {
			return $permalink;
		}

		$parent_vestiging_id = get_post_meta( $post->ID, '_parent_vestiging', true );
		if ( $parent_vestiging_id ) {
			$parent = get_post( $parent_vestiging_id );
			if ( $parent ) {
				return home_url( '/vestigingen/' . $parent->post_name . '/' . $post->post_name . '/' );
			}
		}

		// Fallback if no parent vestiging set
		return home_url( '/vestigingen/' . $post->post_name . '/' );
	}

	/**
	 * Disambiguate kennisbank URLs between taxonomy archives and posts
	 *
	 * The 2-segment rewrite rule (kennisbank/{a}/{b}) matches both:
	 * - Child category archives: /kennisbank/parent-cat/child-cat/
	 * - Posts in top-level categories: /kennisbank/category/post-slug/
	 *
	 * This filter checks if the matched slug is a taxonomy term. If so,
	 * it rewrites the query to target the taxonomy archive instead of a post.
	 *
	 * For hierarchical taxonomies, WordPress needs the full path (parent/child)
	 * in the query var, so we extract it from the request URI.
	 *
	 * @param array $query_vars The parsed query variables
	 * @return array Modified query variables
	 */
	public function disambiguate_kennisbank_urls( array $query_vars ): array {
		// Only process if this looks like a kennisbank post query
		if ( empty( $query_vars['kennisbank'] ) ) {
			return $query_vars;
		}

		$slug = $query_vars['kennisbank'];

		// Check if this slug is a kennisbank_categories term
		$term = get_term_by( 'slug', $slug, 'kennisbank_categories' );

		if ( $term && ! is_wp_error( $term ) ) {
			// It's a taxonomy term, not a post - rewrite to taxonomy query
			// Must unset all post-related query vars to prevent post lookup
			unset( $query_vars['kennisbank'] );
			unset( $query_vars['post_type'] );
			unset( $query_vars['name'] );

			// For hierarchical taxonomies, extract full path from request URI
			// URL: /kennisbank/parent/child/ → taxonomy path: parent/child
			$uri = $_SERVER['REQUEST_URI'] ?? '';
			if ( preg_match( '#^/kennisbank/(.+?)/?$#', $uri, $matches ) ) {
				$query_vars['kennisbank_categories'] = $matches[1];
			} else {
				$query_vars['kennisbank_categories'] = $slug;
			}
		}

		return $query_vars;
	}

	/**
	 * Filter kennisbank permalinks to include full category path
	 *
	 * Builds hierarchical category path for URLs like:
	 * /kennisbank/veelgestelde-vragen/afvallen/{post-slug}/
	 *
	 * @param string   $permalink The post permalink
	 * @param \WP_Post $post      The post object
	 * @return string Modified permalink
	 */
	public function kennisbank_permalink( string $permalink, \WP_Post $post ): string {
		if ( $post->post_type !== 'kennisbank' ) {
			return $permalink;
		}

		$terms = get_the_terms( $post->ID, 'kennisbank_categories' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			// Use the first (primary) category
			$term = $terms[0];
			// Build full hierarchical path (parent/child)
			$category_slug = $this->get_term_hierarchy_path( $term );
		} else {
			// Fallback for uncategorized posts
			$category_slug = 'uncategorized';
		}

		return str_replace( '%kennisbank_categories%', $category_slug, $permalink );
	}

	/**
	 * Build hierarchical path for a term (parent/child/grandchild)
	 *
	 * @param \WP_Term $term The term to build path for
	 * @return string Slash-separated path of term slugs
	 */
	private function get_term_hierarchy_path( \WP_Term $term ): string {
		$path = [ $term->slug ];

		// Walk up the parent chain
		$parent_id = $term->parent;
		while ( $parent_id > 0 ) {
			$parent = get_term( $parent_id, $term->taxonomy );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			array_unshift( $path, $parent->slug );
			$parent_id = $parent->parent;
		}

		return implode( '/', $path );
	}

	/**
	 * Load category-specific single template for kennisbank posts
	 *
	 * Allows different templates per category:
	 * - single-kennisbank-blogs.php (for "blogs" category)
	 * - single-kennisbank-medisch.php (for "medisch" category)
	 * - single-kennisbank.php (fallback)
	 *
	 * @param string $template Default template path
	 * @return string Modified template path if category-specific template exists
	 */
	public function kennisbank_category_template( string $template ): string {
		if ( ! is_singular( 'kennisbank' ) ) {
			return $template;
		}

		$post = get_queried_object();
		$terms = get_the_terms( $post->ID, 'kennisbank_categories' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return $template;
		}

		// Try to find a category-specific template
		$category_slug = $terms[0]->slug;
		$category_template = locate_template( "single-kennisbank-{$category_slug}.php" );

		if ( $category_template ) {
			return $category_template;
		}

		return $template;
	}

	/**
	 * Protect page_part and template_layout posts from direct public access
	 *
	 * Page parts are embedded components, not standalone pages. For page_part
	 * URLs, attempts a 301 redirect to the first published page that uses the
	 * page part (with #section-id fragment for deep linking). Falls back to 404
	 * for orphaned page parts and template_layout posts.
	 */
	/**
	 * Block WordPress's 404 permalink guess from rescuing requests via page_part slugs
	 *
	 * WordPress's redirect_guess_404_permalink() (called from redirect_canonical)
	 * attempts to rescue would-be 404s by matching the last URL segment against
	 * any publicly-viewable post's slug. Before this filter, broken content links
	 * like /behandeling/behandelprogramma/behandelteam/psycholoog/ were silently
	 * redirected to /page-part/psycholoog/ (and then on to the final page),
	 * creating 2-hop redirect chains and hiding the broken links from auditors.
	 *
	 * Setting exclude_from_search on page_part should already prevent this, but
	 * we also filter redirect_canonical as a defensive second layer in case
	 * another code path reintroduces a /page-part/* guess.
	 *
	 * @param string|false $redirect_url  URL redirect_canonical wants to send us to.
	 * @param string       $requested_url The URL that was originally requested.
	 * @return string|false Original URL, or false to suppress the redirect.
	 */
	public function block_page_part_canonical_guess( $redirect_url, $requested_url ) {
		if ( is_string( $redirect_url ) && str_contains( $redirect_url, '/page-part/' ) ) {
			return false;
		}
		return $redirect_url;
	}

	public function protect_post_types(): void {
		global $pagenow, $wp_query;

		// Skip protection in admin, AJAX, CRON, and REST contexts
		if ( is_admin()
		     || defined( 'DOING_AJAX' )
		     || defined( 'DOING_CRON' )
		     || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return;
		}

		// Allow logged-in users to access page parts (for previews, etc.)
		if ( is_user_logged_in() ) {
			return;
		}

		// Allow WordPress login and registration pages
		if ( in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		// Define protected post types
		$protected_post_types = [ 'page_part', 'template_layout' ];

		// Protect single views
		if ( is_singular( $protected_post_types ) ) {

			// For page_part: redirect to first published page that uses it.
			// Uses 301 (permanent) because page part → page relationships are stable.
			// Browsers and CDNs cache 301s aggressively — this is intentional.
			if ( is_singular( 'page_part' ) ) {
				$redirect_url = $this->get_page_part_redirect_url( get_the_ID() );
				if ( $redirect_url ) {
					wp_safe_redirect( $redirect_url, 301 );
					exit;
				}
			}

			// Fallback: 404 for orphaned page parts and template_layout
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}

		// Protect archive pages
		if ( is_post_type_archive( $protected_post_types ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
	}

	/**
	 * Find the redirect URL for a page part based on its first published usage
	 *
	 * Searches post_content for the Gutenberg embed block pattern to find pages
	 * that use this page part. Returns the permalink of the first published page,
	 * with the page part's section ID as a URL fragment for deep linking.
	 *
	 * Uses RLIKE with a boundary pattern (`[,}\s]` after the ID) to prevent
	 * false positives (e.g., ID 12 matching "postId":123).
	 *
	 * @param int $post_id Page part post ID
	 *
	 * @return string|null Redirect URL with #fragment, or null if orphaned
	 */
	private function get_page_part_redirect_url( int $post_id ): ?string {
		global $wpdb;

		// Find first published page/post that embeds this page part.
		// RLIKE boundary ensures "postId":12 doesn't match "postId":123.
		// ORDER prefers pages over posts, then alphabetical for determinism.
		$target_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID
				 FROM {$wpdb->posts}
				 WHERE post_content RLIKE %s
				 AND post_type IN ('page', 'post')
				 AND post_status = 'publish'
				 ORDER BY post_type ASC, post_title ASC
				 LIMIT 1",
				'"postId":' . $post_id . '[,}[:space:]]'
			)
		);

		if ( ! $target_id ) {
			return null;
		}

		$permalink = get_permalink( (int) $target_id );
		if ( ! $permalink ) {
			return null;
		}

		// Resolve section ID for #fragment: _section_id meta → post slug fallback
		// Mirrors TemplateRenderer::resolve_section_id() (minus per-embed override,
		// which is unavailable from the page part side)
		$section_id = get_post_meta( $post_id, '_section_id', true );
		if ( $section_id === '' || $section_id === false ) {
			$page_part = get_post( $post_id );
			$section_id = $page_part ? $page_part->post_name : '';
		}

		if ( $section_id !== '' ) {
			$permalink .= '#' . sanitize_title( $section_id );
		}

		return $permalink;
	}

	/**
	 * Raise per_page limit for page_part REST endpoint
	 *
	 * WordPress REST API caps per_page at 100 by default. Page parts need
	 * a higher limit so the embed block selector can load all available parts.
	 *
	 * @param array $params Collection parameters.
	 *
	 * @return array Modified parameters with raised per_page maximum.
	 */
	public function raise_page_part_per_page_limit( array $params ): array {
		if ( isset( $params['per_page'] ) ) {
			$params['per_page']['maximum'] = 999;
		}
		return $params;
	}

	/**
	 * Exclude internal post types from REST search results
	 *
	 * Prevents page_part and template_layout from appearing in Gutenberg's
	 * link autocomplete, which uses the /wp/v2/search endpoint.
	 *
	 * Uses rest_post_search_query instead of exclude_from_search because
	 * the latter also prevents Gutenberg's getEntityRecords() from listing
	 * page parts in the embed block dropdown.
	 *
	 * @param array<string, mixed> $query_args WP_Query arguments for the search
	 * @param \WP_REST_Request     $request    The REST request
	 * @return array<string, mixed> Modified query arguments
	 */
	public function exclude_internal_types_from_rest_search( array $query_args, \WP_REST_Request $request ): array {
		$excluded = [ 'page_part', 'template_layout', 'vragenlijst' ];

		if ( ! empty( $query_args['post_type'] ) && is_array( $query_args['post_type'] ) ) {
			$query_args['post_type'] = array_values( array_diff( $query_args['post_type'], $excluded ) );
		}

		return $query_args;
	}

	/**
	 * Track all registered post types that have archives
	 *
	 * Iterates through all registered post types and builds registry
	 * for dynamic archive settings page generation.
	 *
	 * @return void
	 */
	private function track_archive_post_types(): void {
		$post_types = get_post_types(['_builtin' => false], 'objects');

		foreach ($post_types as $post_type => $post_type_obj) {
			if ($post_type_obj->has_archive) {
				self::$archive_post_types[$post_type] = [
					'slug' => is_string($post_type_obj->has_archive)
						? $post_type_obj->has_archive
						: $post_type,
					'label' => $post_type_obj->labels->name,
				];
			}
		}
	}

	/**
	 * Get all registered post types with archives
	 *
	 * @return array Associative array of post_type => ['slug', 'label']
	 */
	public static function get_archive_post_types(): array {
		return self::$archive_post_types;
	}
}