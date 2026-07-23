<?php

namespace NOK2025\V1;

use DateTime;
use DateTimeZone;
use WP_Post;
use WP_Query;

/**
 * Agenda - Shared month archive renderer for voorlichting and evenement posts.
 *
 * @package NOK2025\V1
 */
class Agenda {
	private const DATE_META_KEY = 'aanvangsdatum_en_tijd';
	private const AGENDA_PATH   = 'agenda';

	/**
	 * Hide ACF-registered evenement REST endpoints from anonymous visitors.
	 *
	 * @param mixed            $result  Current REST dispatch result.
	 * @param \WP_REST_Server  $server  REST server instance.
	 * @param \WP_REST_Request $request REST request instance.
	 * @return mixed
	 */
	public static function protect_evenement_rest( $result, $server, $request ) {
		if ( is_user_logged_in() ) {
			return $result;
		}

		$route = $request->get_route();
		$is_event_route = $route === '/wp/v2/evenement'
			|| str_starts_with( $route, '/wp/v2/evenement/' )
			|| $route === '/wp/v2/types/evenement';

		if ( ! $is_event_route ) {
			return $result;
		}

		return new \WP_Error(
			'rest_cannot_read_type',
			__( 'Kan berichttype niet bekijken.', THEME_TEXT_DOMAIN ),
			[ 'status' => 401 ]
		);
	}

	/**
	 * Render the site 404 template and stop the request.
	 */
	private static function render_not_found(): void {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		include get_404_template();
		exit;
	}

	/**
	 * Render the public combined agenda route.
	 */
	public static function maybe_render_agenda(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );

		if ( $path !== self::AGENDA_PATH ) {
			return;
		}

		global $wp_query;

		$wp_query->is_404 = false;

		self::register_agenda_seo_filters();

		status_header( 200 );
		self::render_archive( [
			'post_types'        => [ 'voorlichting', 'evenement' ],
			'archive_link'      => home_url( '/' . self::AGENDA_PATH . '/' ),
			'intro_post_type'   => '',
			'heading'           => __( 'Agenda', THEME_TEXT_DOMAIN ),
			'period_label'      => __( 'Agenda in', THEME_TEXT_DOMAIN ),
			'empty_message'     => __( 'Geen agenda-items gevonden in deze maand.', THEME_TEXT_DOMAIN ),
			'protect_logged_in' => false,
		] );
		exit;
	}

	/**
	 * Register Yoast metadata for the virtual combined agenda route.
	 */
	private static function register_agenda_seo_filters(): void {
		$agenda_url      = home_url( '/' . self::AGENDA_PATH . '/' );
		$seo_title       = __( 'Agenda - Nederlandse Obesitas Kliniek', THEME_TEXT_DOMAIN );
		$seo_description = __( 'Bekijk de agenda van de Nederlandse Obesitas Kliniek met aankomende voorlichtingen en evenementen, online en op locatie.', THEME_TEXT_DOMAIN );

		add_filter( 'wpseo_title', static fn( string $title ): string => $seo_title );
		add_filter( 'wpseo_metadesc', static fn( string $description ): string => $seo_description );
		add_filter( 'wpseo_canonical', static fn( string $canonical ): string => $agenda_url );
		add_filter( 'wpseo_opengraph_title', static fn( string $title ): string => $seo_title );
		add_filter( 'wpseo_opengraph_desc', static fn( string $description ): string => $seo_description );
		add_filter( 'wpseo_opengraph_url', static fn( string $url ): string => $agenda_url );
		add_filter( 'wpseo_twitter_title', static fn( string $title ): string => $seo_title );
		add_filter( 'wpseo_twitter_description', static fn( string $description ): string => $seo_description );

		add_action(
			'wp_head',
			static function () use ( $agenda_url, $seo_title, $seo_description ): void {
				?>
				<meta property="og:locale" content="nl_NL">
				<meta property="og:type" content="website">
				<meta property="og:title" content="<?php echo esc_attr( $seo_title ); ?>">
				<meta property="og:description" content="<?php echo esc_attr( $seo_description ); ?>">
				<meta property="og:url" content="<?php echo esc_url( $agenda_url ); ?>">
				<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<meta name="twitter:card" content="summary">
				<meta name="twitter:title" content="<?php echo esc_attr( $seo_title ); ?>">
				<meta name="twitter:description" content="<?php echo esc_attr( $seo_description ); ?>">
				<?php
			},
			2
		);

		add_filter(
			'wpseo_breadcrumb_links',
			static function ( array $links ): array {
				return [
					[
						'url'  => home_url( '/' ),
						'text' => __( 'Home', THEME_TEXT_DOMAIN ),
					],
					[
						'text' => __( 'Agenda', THEME_TEXT_DOMAIN ),
					],
				];
			},
			20
		);

		add_filter(
			'wpseo_schema_graph',
			static function ( array $graph ) use ( $agenda_url, $seo_title, $seo_description ): array {
				$webpage_id     = $agenda_url . '#webpage';
				$breadcrumb_id  = $agenda_url . '#breadcrumb';
				$has_webpage    = false;
				$has_breadcrumb = false;

				foreach ( $graph as &$piece ) {
					$types = (array) ( $piece['@type'] ?? [] );

					if ( in_array( 'WebPage', $types, true ) || in_array( 'CollectionPage', $types, true ) ) {
						$has_webpage         = true;
						$piece['@type']       = 'CollectionPage';
						$piece['@id']         = $webpage_id;
						$piece['url']         = $agenda_url;
						$piece['name']        = $seo_title;
						$piece['description'] = $seo_description;
						$piece['breadcrumb']  = [ '@id' => $breadcrumb_id ];
					}

					if ( in_array( 'BreadcrumbList', $types, true ) ) {
						$has_breadcrumb           = true;
						$piece['@id']             = $breadcrumb_id;
						$piece['itemListElement'] = [
							[
								'@type'    => 'ListItem',
								'position' => 1,
								'name'     => __( 'Home', THEME_TEXT_DOMAIN ),
								'item'     => home_url( '/' ),
							],
							[
								'@type'    => 'ListItem',
								'position' => 2,
								'name'     => __( 'Agenda', THEME_TEXT_DOMAIN ),
								'item'     => $agenda_url,
							],
						];
					}
				}

				unset( $piece );

				if ( ! $has_webpage ) {
					$graph[] = [
						'@type'       => 'CollectionPage',
						'@id'         => $webpage_id,
						'url'         => $agenda_url,
						'name'        => $seo_title,
						'description' => $seo_description,
						'isPartOf'    => [ '@id' => home_url( '/#website' ) ],
						'breadcrumb'  => [ '@id' => $breadcrumb_id ],
						'inLanguage'  => get_bloginfo( 'language' ),
					];
				}

				if ( ! $has_breadcrumb ) {
					$graph[] = [
						'@type'           => 'BreadcrumbList',
						'@id'             => $breadcrumb_id,
						'itemListElement' => [
							[
								'@type'    => 'ListItem',
								'position' => 1,
								'name'     => __( 'Home', THEME_TEXT_DOMAIN ),
								'item'     => home_url( '/' ),
							],
							[
								'@type'    => 'ListItem',
								'position' => 2,
								'name'     => __( 'Agenda', THEME_TEXT_DOMAIN ),
								'item'     => $agenda_url,
							],
						],
					];
				}

				return $graph;
			},
			99
		);
	}

	/**
	 * Render a filtered month archive for one or more agenda post types.
	 *
	 * @param array<string, mixed> $args Renderer arguments.
	 */
	public static function render_archive( array $args = [] ): void {
		$defaults = [
			'post_types'        => [ 'voorlichting' ],
			'archive_link'      => '',
			'intro_post_type'   => 'voorlichting',
			'heading'           => __( 'Agenda', THEME_TEXT_DOMAIN ),
			'period_label'      => __( 'Voorlichtingen in', THEME_TEXT_DOMAIN ),
			'empty_message'     => __( 'Geen voorlichtingen gevonden in deze maand.', THEME_TEXT_DOMAIN ),
			'protect_logged_in' => false,
		];
		$args     = array_merge( $defaults, $args );

		if ( $args['protect_logged_in'] && ! is_user_logged_in() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			get_header( 'generic' );
			get_template_part( 'template-parts/404-content' );
			get_footer();
			return;
		}

		$post_types = self::normalize_post_types( $args['post_types'] );
		if ( empty( $post_types ) ) {
			$post_types = [ 'voorlichting' ];
		}

		$timezone = new DateTimeZone( 'Europe/Amsterdam' );
		$today    = new DateTime( 'now', $timezone );
		$archive_link = $args['archive_link'] ?: self::archive_link_for_post_types( $post_types );

		self::maybe_redirect_legacy_week_param( $archive_link, $timezone, $today );

		get_header( 'generic' );

		$filter_vestiging = isset( $_GET['locatie'] ) ? sanitize_text_field( wp_unslash( $_GET['locatie'] ) ) : '';
		$month_start      = self::month_start_from_request( $timezone, $today );
		$month_end        = clone $month_start;
		$month_end->modify( 'last day of this month' );
		$month_end->setTime( 23, 59, 59 );

		$prev_month = clone $month_start;
		$prev_month->modify( '-1 month' );
		$next_month = clone $month_start;
		$next_month->modify( '+1 month' );
		$date_range = sprintf(
			'%s %s',
			Helpers::dutchMonth( (int) $month_start->format( 'n' ) ),
			$month_start->format( 'Y' )
		);

		$query = new WP_Query(
			self::query_args( $post_types, $month_start, $month_end, $today, $filter_vestiging )
		);

		$location_options = self::location_filter_options( $post_types, $today );

		$build_url      = self::url_builder( $archive_link, $filter_vestiging );
		$base_month_url = add_query_arg( [
			'maand' => (int) $month_start->format( 'n' ),
			'jaar'  => $month_start->format( 'Y' ),
		], $archive_link );

		self::render_archive_markup(
			$query,
			$location_options,
			$timezone,
			[
				'archive_link'      => $archive_link,
				'base_month_url'    => $base_month_url,
				'build_url'         => $build_url,
				'date_range'        => $date_range,
				'empty_message'     => (string) $args['empty_message'],
				'filter_vestiging'  => $filter_vestiging,
				'heading'           => (string) $args['heading'],
				'intro_post_type'   => (string) $args['intro_post_type'],
				'month_start'       => $month_start,
				'next_month'        => $next_month,
				'period_label'      => (string) $args['period_label'],
				'prev_month'        => $prev_month,
			]
		);

		wp_reset_postdata();
		get_footer();
	}

	/**
	 * Render a single event page with the configured registration form.
	 */
	public static function render_single_event(): void {
		get_header( 'generic' );

		$post = get_post();
		if ( ! $post ) {
			get_template_part( 'template-parts/404-content' );
			get_footer();
			return;
		}

		$event_data   = self::event_data( $post );
		$archive_url  = home_url( '/agenda/' );
		$form_id      = self::event_form_id( $post->ID );
        $is_open      = $event_data['open'];
		$can_register = $form_id > 0 && $is_open;
		$timezone     = new DateTimeZone( 'Europe/Amsterdam' );
		?>

        <nok-hero class="nok-section">
            <div class="nok-section__inner nok-hero__inner
                nok-layout-grid nok-layout-grid__3-column fill-one
                nok-m-0 nok-border-radius-to-sm-0
                nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10 nok-subtle-shadow">
                <div class="article">
                    <?php Helpers::render_breadcrumbs(); ?>
                    <h1 class="nok-fs-6"><?php the_title(); ?></h1>
                    <div>
                        <?= wp_kses_post( ucfirst( $event_data['intro'] ?? '' ) ) ?>
                        <?php if ( ! $is_open ) : ?>
                            <div class="nok-alert nok-bg-yellow nok-p-1 nok-mt-1 nok-rounded-border nok-bg-alpha-10" role="alert">
                                <p>
                                    Helaas, dit evenement is <?= esc_html( $event_data['status'] ); ?>! Aanmelden is daarom niet (meer) mogelijk.
                                    Bekijk onze <a class="nok-hyperlink" href="<?= esc_url( $archive_url ); ?>">agenda</a> voor alle evenementen & voorlichtingen.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nok-hero>

        <nok-section class="no-aos z-ascend">
            <div class="nok-section__inner">
                <article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3">

                    <!-- Main content -->
                    <div class="baseline-grid nok-order-1 nok-order-lg-0" data-requires="./domule/modules/hnl.baseline-grid.mjs">
                        <?= wp_kses_post( $event_data['intro_lang'] ); ?>
                        <?= wp_kses_post( $event_data['onderwerpen'] ); ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-gap-1 nok-layout-flex nok-layout-flex-column nok-align-items-stretch">
                        <?php self::render_card( $post, $timezone, false , false, true, false ); ?>
                    </div>

                </article>
            </div>
        </nok-section>

		<nok-section class="nok-bg-body--darker">
			<div class="nok-section__inner">
				<h2 id="aanmelden" class="nok-mb-1"><?php esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN ); ?></h2>
				<nok-square-block class="nok-bg-body nok-text-contrast" data-shadow="true">
					<?php if ( $can_register && function_exists( 'gravity_form' ) ) : ?>
						<?php gravity_form( $form_id, false, false ); ?>
					<?php elseif ( !self::is_registrable( $post, $event_data ) ) : ?>
						<p><?php esc_html_e( 'Inschrijven voor dit evenement is niet nodig. U bent van harte welkom op de aangegeven datum en tijd.', THEME_TEXT_DOMAIN ); ?></p>
					<?php else : ?>
						<p>
							<?php esc_html_e( 'Aanmelden voor dit evenement is niet meer mogelijk.', THEME_TEXT_DOMAIN ); ?>
							<a class="nok-hyperlink" href="<?= esc_url( $archive_url ); ?>">
								<?php esc_html_e( 'Bekijk onze volledige agenda.', THEME_TEXT_DOMAIN ); ?>
							</a>
						</p>
					<?php endif; ?>
				</nok-square-block>
			</div>
		</nok-section>
		<?php
		get_footer();
	}

	/**
	 * @param string[]|string $post_types
	 * @return string[]
	 */
	private static function normalize_post_types( $post_types ): array {
		$post_types = is_array( $post_types ) ? $post_types : [ $post_types ];

		return array_values( array_filter( array_map( 'sanitize_key', $post_types ), 'post_type_exists' ) );
	}

	/**
	 * @param string[] $post_types
	 */
	private static function archive_link_for_post_types( array $post_types ): string {
		if ( count( $post_types ) === 1 ) {
			$link = get_post_type_archive_link( $post_types[0] );
			if ( $link ) {
				return $link;
			}
		}

		return home_url( '/' . self::AGENDA_PATH . '/' );
	}

	private static function maybe_redirect_legacy_week_param( string $archive_link, DateTimeZone $timezone, DateTime $today ): void {
		if ( ! isset( $_GET['week'] ) || isset( $_GET['maand'] ) ) {
			return;
		}

		$week          = sanitize_text_field( wp_unslash( $_GET['week'] ) );
		$redirect_date = null;
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $week ) ) {
			$redirect_date = new DateTime( $week, $timezone );
		} elseif ( preg_match( '/^\d{1,2}$/', $week ) ) {
			$year = isset( $_GET['jaar'] ) && preg_match( '/^\d{4}$/', (string) $_GET['jaar'] )
				? (int) $_GET['jaar']
				: (int) $today->format( 'Y' );
			$redirect_date = new DateTime( 'now', $timezone );
			$redirect_date->setISODate( $year, (int) $week, 1 );
		}

		if ( ! $redirect_date ) {
			return;
		}

		$redirect_url = add_query_arg( [
			'maand' => (int) $redirect_date->format( 'n' ),
			'jaar'  => $redirect_date->format( 'Y' ),
		], $archive_link );

		if ( isset( $_GET['locatie'] ) ) {
			$locatie = sanitize_text_field( wp_unslash( $_GET['locatie'] ) );
			if ( $locatie !== '' ) {
				$redirect_url = add_query_arg( 'locatie', rawurlencode( $locatie ), $redirect_url );
			}
		}

		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}

	private static function month_start_from_request( DateTimeZone $timezone, DateTime $today ): DateTime {
		if (
			isset( $_GET['maand'], $_GET['jaar'] )
			&& preg_match( '/^(?:1[0-2]|[1-9])$/', (string) $_GET['maand'] )
			&& preg_match( '/^\d{4}$/', (string) $_GET['jaar'] )
		) {
			$month_start = new DateTime( 'now', $timezone );
			$month_start->setDate( (int) $_GET['jaar'], (int) $_GET['maand'], 1 );
		} else {
			$month_start = clone $today;
			$month_start->setDate( (int) $today->format( 'Y' ), (int) $today->format( 'n' ), 1 );
		}

		$month_start->setTime( 0, 0, 0 );

		return $month_start;
	}

	/**
	 * @param string[] $post_types
	 * @return array<string, mixed>
	 */
	private static function query_args( array $post_types, DateTime $month_start, DateTime $month_end, DateTime $today, string $filter_vestiging ): array {
		$query_start = clone $month_start;
		if ( $month_start->format( 'Y-m' ) === $today->format( 'Y-m' ) ) {
			$query_start = clone $today;
			$query_start->setTime( 0, 0, 0 );
		}

		$args = [
			'post_type'      => count( $post_types ) === 1 ? $post_types[0] : $post_types,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => self::DATE_META_KEY,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => self::DATE_META_KEY,
					'value'   => $query_start->format( 'Y-m-d H:i:s' ),
					'compare' => '>=',
					'type'    => 'DATETIME',
				],
				[
					'key'     => self::DATE_META_KEY,
					'value'   => $month_end->format( 'Y-m-d H:i:s' ),
					'compare' => '<=',
					'type'    => 'DATETIME',
				],
			],
		];

		if ( $filter_vestiging !== '' ) {
			$args['meta_query'][] = self::location_filter_meta_query( $post_types, $filter_vestiging );
		}

		return $args;
	}

	/**
	 * Get upcoming agenda items, optionally filtered by a vestiging city.
	 *
	 * @param int         $limit Maximum number of results, or -1 for all.
	 * @param string|null $city  City name to filter by, or null for all locations.
	 * @return WP_Post[] Upcoming voorlichtingen and evenementen ordered by start time.
	 */
	public static function get_upcoming_items( int $limit = 6, ?string $city = null ): array {
		$post_types = [ 'voorlichting', 'evenement' ];
		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => self::DATE_META_KEY,
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
		];

		if ( $city !== null && $city !== '' ) {
			$meta_query[] = self::location_filter_meta_query( $post_types, $city );
		}

		return get_posts( [
			'post_type'      => $post_types,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_key'       => self::DATE_META_KEY,
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		] );
	}

	/**
	 * Build the location meta query for the selected archive post types.
	 *
	 * Voorlichtingen store their city in `vestiging`; evenementen store their location
	 * in the `[Locatie]` ACF fields, primarily `locatie_plaats`.
	 *
	 * @param string[] $post_types
	 * @return array<string, mixed>
	 */
	private static function location_filter_meta_query( array $post_types, string $filter_vestiging ): array {
		$variants = self::location_filter_variants( $filter_vestiging );
		$clauses  = [];

		if ( in_array( 'voorlichting', $post_types, true ) ) {
			$clauses[] = [
				'key'     => 'vestiging',
				'value'   => $variants,
				'compare' => 'IN',
			];
		}

		if ( in_array( 'evenement', $post_types, true ) ) {
			$clauses[] = [
				'key'     => 'locatie_plaats',
				'value'   => $variants,
				'compare' => 'IN',
			];
			$clauses[] = [
				'key'     => 'locatie_naam',
				'value'   => self::location_name_filter_variants( $variants ),
				'compare' => 'IN',
			];
		}

		if ( count( $clauses ) === 1 ) {
			return $clauses[0];
		}

		return array_merge( [ 'relation' => 'OR' ], $clauses );
	}

	/**
	 * @return string[]
	 */
	private static function location_filter_variants( string $location ): array {
		$variants = Helpers::get_vestiging_name_variants( $location );
		$variants[] = Helpers::normalize_vestiging_name( $location );

		return self::unique_location_labels( $variants );
	}

	/**
	 * @param string[] $variants
	 * @return string[]
	 */
	private static function location_name_filter_variants( array $variants ): array {
		$names = $variants;

		foreach ( $variants as $variant ) {
			$variant = trim( $variant );
			if ( $variant !== '' && ! preg_match( '/^NOK\s+/i', $variant ) ) {
				$names[] = 'NOK ' . $variant;
			}
		}

		return self::unique_location_labels( $names );
	}

	/**
	 * @param string[] $post_types
	 * @return string[]
	 */
	private static function location_filter_options( array $post_types, DateTime $today ): array {
		$options = [];

		if ( in_array( 'voorlichting', $post_types, true ) ) {
			$vestigingen = get_posts( [
				'post_type'      => 'vestiging',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			foreach ( $vestigingen as $vestiging ) {
				self::add_location_filter_option( $options, get_the_title( $vestiging->ID ) );
			}
		}

		if ( in_array( 'evenement', $post_types, true ) ) {
			$events = get_posts( [
				'post_type'      => 'evenement',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_key'       => self::DATE_META_KEY,
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => [
					[
						'key'     => self::DATE_META_KEY,
						'value'   => $today->format( 'Y-m-d H:i:s' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					],
				],
			] );

			foreach ( $events as $event ) {
				if ( ! $event instanceof WP_Post ) {
					continue;
				}

				$location = self::event_location_data( $event );
				self::add_location_filter_option( $options, $location['city'] !== '' ? $location['city'] : $location['label'] );
			}
		}

		natcasesort( $options );

		return array_values( $options );
	}

	/**
	 * @param array<string, string> $options
	 */
	private static function add_location_filter_option( array &$options, ?string $location ): void {
		$location = trim( (string) $location );
		if ( $location === '' ) {
			return;
		}

		$location = preg_replace( '/^NOK\s+/i', '', $location ) ?? $location;
		$key      = strtolower( Helpers::normalize_vestiging_name( $location ) );

		$options[ $key ] = Helpers::normalize_vestiging_name( $location );
	}

	/**
	 * @param string[] $labels
	 * @return string[]
	 */
	private static function unique_location_labels( array $labels ): array {
		$unique = [];

		foreach ( $labels as $label ) {
			$label = trim( (string) $label );
			if ( $label === '' ) {
				continue;
			}

			$unique[ strtolower( $label ) ] = $label;
		}

		return array_values( $unique );
	}

	/**
	 * @return callable(DateTime, ?string): string
	 */
	private static function url_builder( string $archive_link, string $filter_vestiging ): callable {
		return static function ( DateTime $month_date, ?string $locatie = null ) use ( $archive_link, $filter_vestiging ): string {
			$url = add_query_arg( [
				'maand' => (int) $month_date->format( 'n' ),
				'jaar'  => $month_date->format( 'Y' ),
			], $archive_link );
			$loc = $locatie ?? $filter_vestiging;
			if ( $loc ) {
				$url = add_query_arg( 'locatie', rawurlencode( $loc ), $url );
			}

			return $url;
		};
	}

	/**
	 * @param string[]             $location_options
	 * @param array<string, mixed> $context
	 */
	private static function render_archive_markup( WP_Query $query, array $location_options, DateTimeZone $timezone, array $context ): void {
		$datepicker_url_pattern = add_query_arg( [
			'maand' => '{maand}',
			'jaar'  => '{jaar}',
		], $context['archive_link'] );
		if ( $context['filter_vestiging'] ) {
			$datepicker_url_pattern = add_query_arg( 'locatie', rawurlencode( $context['filter_vestiging'] ), $datepicker_url_pattern );
		}
		?>
		<nok-hero class="nok-section">
			<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0 nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">
				<header class="nok-section__inner nok-my-0">
					<?php Helpers::render_breadcrumbs(); ?>
					<h1 class="nok-fs-giant"><?= esc_html( $context['heading'] ); ?></h1>
					<p class="nok-intro-text">
						<?php
						$intro = Theme::get_archive_intro( $context['intro_post_type'], '' );
						if ( $intro ) {
							echo wp_kses_post( $intro );
						} else {
							esc_html_e( 'Op deze pagina vind je alle geplande voorlichtingen en evenementen van de Nederlandse Obesitas Kliniek.', THEME_TEXT_DOMAIN );
						}
						?>
					</p>
				</header>
			</div>
		</nok-hero>

		<nok-section class="no-aos collapse-top">
			<div class="nok-section__inner">
				<div class="nok-agenda-controls nok-layout-grid nok-align-items-center nok-columns-2 nok-columns-to-lg-1 nok-mb-3 nok-row-gap-1 nok-column-gap-2">
					<div class="nok-agenda-week-selector">
						<h2 class="nok-fs-6 nok-mb-0">
							<?= esc_html( $context['period_label'] ); ?>
							<span class="fw-bold"><?= esc_html( $context['date_range'] ); ?></span>
						</h2>
					</div>

					<div class="nok-form nok-layout-flex-row nok-align-items-center nok-justify-content-to-lg-space-between nok-justify-content-end nok-column-gap-1">
						<div class="nok-datepicker" data-requires="./nok-datepicker.mjs">
							<button type="button"
							        title="<?= esc_attr__( 'Klik om een andere maand te selecteren', THEME_TEXT_DOMAIN ); ?>"
							        class="nok-datepicker__trigger nok-bg-white nok-text-darkerblue"
							        data-datepicker
							        data-mode="month"
							        data-value="<?= esc_attr( $context['month_start']->format( 'Y-m-d' ) ); ?>"
							        data-url-pattern="<?= esc_attr( $datepicker_url_pattern ); ?>"
							        aria-expanded="false"
							        aria-haspopup="dialog">
								<?= Assets::getIcon( 'ui_calendar' ); ?>
								<span class="nok-datepicker__display"><?= esc_html( $context['date_range'] ); ?></span>
							</button>
						</div>

						<?php if ( $location_options ) : ?>
							<div class="nok-select-wrapper nok-form-element nok-mb-0">
								<select class="nok-select nok-bg-white nok-text-darkerblue"
								        aria-label="<?php esc_attr_e( 'Filter evenementen op locatie', THEME_TEXT_DOMAIN ); ?>"
								        onchange="if(this.value) window.location.href=this.value">
									<option value="<?= esc_url( $context['base_month_url'] ); ?>" <?= empty( $context['filter_vestiging'] ) ? 'selected' : ''; ?>>
										<?php esc_html_e( 'Filter evenementen', THEME_TEXT_DOMAIN ); ?>
									</option>
									<?php foreach ( $location_options as $vestiging_city ) :
										$filter_normalized = Helpers::normalize_vestiging_name( $context['filter_vestiging'] );
										$is_selected       = ! empty( $context['filter_vestiging'] ) && strtolower( $filter_normalized ) === strtolower( $vestiging_city );
										$option_url        = $context['build_url']( $context['month_start'], $vestiging_city );
										?>
										<option value="<?= esc_url( $option_url ); ?>" <?= $is_selected ? 'selected' : ''; ?>>
											<?= esc_html( $vestiging_city ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $query->have_posts() ) : ?>
					<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
							<?php self::render_card( get_post(), $timezone, true, true, false, true ); ?>
						<?php endwhile; ?>
					</div>
				<?php else : ?>
					<p class="nok-text-center nok-p-2"><?= esc_html( $context['empty_message'] ); ?></p>
				<?php endif; ?>

				<nav class="nok-week-navigation nok-layout-flex nok-layout-flex-row nok-justify-content-space-between nok-mt-3 nok-pt-2 nok-border-top"
				     aria-label="<?php esc_attr_e( 'Maand navigatie', THEME_TEXT_DOMAIN ); ?>">
					<a href="<?= esc_url( $context['build_url']( $context['prev_month'] ) ); ?>" class="nok-button nok-bg-transparent nok-text-darkerblue">
						<?= Assets::getIcon( 'ui_arrow-left' ); ?>
						<?php esc_html_e( 'Vorige maand', THEME_TEXT_DOMAIN ); ?>
					</a>
					<a href="<?= esc_url( $context['build_url']( $context['next_month'] ) ); ?>" class="nok-button nok-bg-transparent nok-text-darkerblue">
						<?php esc_html_e( 'Volgende maand', THEME_TEXT_DOMAIN ); ?>
						<?= Assets::getIcon( 'ui_arrow-right' ); ?>
					</a>
				</nav>
			</div>
		</nok-section>
		<?php
	}

	/**
	 * Render a card for a voorlichting or evenement.
	 *
	 * @param WP_Post|null $post           Agenda item.
	 * @param DateTimeZone $timezone       Display timezone.
	 * @param bool         $show_info_link Whether to show the information link.
	 * @param bool         $show_title     Whether to show the item title.
	 * @param bool         $pull_up        Whether to apply the pull-up layout class.
	 * @param bool         $compact        Whether to use compact location details.
	 * @param int          $heading_level  Heading level for the item title.
	 */
	public static function render_card( ?WP_Post $post, DateTimeZone $timezone, bool $show_info_link = true, bool $show_title = false, bool $pull_up = false, bool $compact = true, int $heading_level = 2 ): void {
		if ( ! $post ) {
			return;
		}

		$heading_tag = in_array( $heading_level, [ 2, 3 ], true ) ? 'h' . $heading_level : 'h2';
		$event_data = self::event_data( $post );
		$event_date = new DateTime( $event_data['timestamp_raw'], $timezone );
		$day_names  = [
			'',
			__( 'Maandag', THEME_TEXT_DOMAIN ),
			__( 'Dinsdag', THEME_TEXT_DOMAIN ),
			__( 'Woensdag', THEME_TEXT_DOMAIN ),
			__( 'Donderdag', THEME_TEXT_DOMAIN ),
			__( 'Vrijdag', THEME_TEXT_DOMAIN ),
			__( 'Zaterdag', THEME_TEXT_DOMAIN ),
			__( 'Zondag', THEME_TEXT_DOMAIN ),
		];
		$footer_date = $day_names[ (int) $event_date->format( 'N' ) ] . ' ' . $event_date->format( 'j' ) . ' ' . Helpers::dutchMonth( (int) $event_date->format( 'n' ) );
		$is_online   = strtolower( $event_data['type'] ) === 'online';
        $is_open     = $event_data['open'];
		$title       = self::card_title( $post, $event_data );
		?>
		<nok-square-block class="nok-bg-white nok-dark-bg-darkestblue nok-grid-gap-0_5 <?= $pull_up ? 'nok-pull-up-lg-3' : '' ?>" data-shadow="true">
			<span class="nok-square-block__banner nok-badge <?= $is_online ? 'nok-bg-lightblue--lighter' : 'nok-bg-green--lighter'; ?> nok-text-darkerblue">
				<?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html__( 'Op locatie', THEME_TEXT_DOMAIN ); ?>
			</span>

            <?php if ( $show_title ) : ?>
			<<?= esc_attr( $heading_tag ); ?> class="nok-square-block__heading">
				<a href="<?= esc_url( get_permalink( $post ) ); ?>" class="nok-text-darkerblue nok-dark-text-white">
					<?= esc_html( $title ); ?>
				</a>
			</<?= esc_attr( $heading_tag ); ?>>
            <?php endif; ?>

			<table class="nok-square-block__text nok-icon-table">
				<tr>
					<td><?= Assets::getIcon( 'ui_calendar' ); ?></td>
					<td class="fw-bold"><?= esc_html( $footer_date ); ?></td>
				</tr>
				<tr>
					<td><?= Assets::getIcon( 'ui_time' ); ?></td>
					<td><?= esc_html( $event_data['timestamp']['start_time'] ); ?> - <?= esc_html( $event_data['timestamp']['end_time'] ); ?> uur</td>
				</tr>
				<?php self::render_location_rows( $post, $event_data, $is_online, $compact ); ?>
			</table>

            <!-- <?= $event_data['inschrijving']; ?> -->

            <div class="nok-layout-flex nok-layout-flex-row nok-column-gap-0_5">
                 <?php if ( self::is_registrable( $post, $event_data ) ) : ?>
                <a href="<?php the_permalink(); ?>#aanmelden"
                   class="nok-button nok-bg-yellow nok-text-darkerblue w-100 <?= ! $is_open ? 'disabled' : ''; ?>">
                    <?php if(!$is_open) {
                        esc_html_e( $event_data['inschrijving'], THEME_TEXT_DOMAIN );
                    } else {
                        esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN );
                    }?>
                </a>
                <?php endif; ?>
                <?php if ( $show_info_link ) : ?>
                <a href="<?php the_permalink(); ?>"
                   class="nok-button nok-bg-lightgrey--lighter nok-text-darkerblue w-100 nok-dark-text-white">
                    <?php esc_html_e( 'Informatie', THEME_TEXT_DOMAIN ); ?>
                </a>
                <?php endif; ?>
            </div>
		</nok-square-block>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function event_data( WP_Post $post ): array {
		$meta = get_post_meta( $post->ID );
		if (
			$post->post_type === 'voorlichting'
			&& isset( $meta[ self::DATE_META_KEY ][0], $meta['duur'][0], $meta['type'][0], $meta['vestiging'][0], $meta['inschrijvingsstatus'][0] )
		) {
			return Helpers::setup_hubspot_metadata( $post->ID );
		}

		$location    = self::event_location_data( $post );
		$date_string = $meta[ self::DATE_META_KEY ][0] ?? current_time( 'mysql' );
		$date        = new DateTime( $date_string, new DateTimeZone( 'Europe/Amsterdam' ) );
		$duration    = isset( $meta['duur'][0] ) ? (int) $meta['duur'][0] : 60;
		$status      = strtolower( (string) ( $meta['inschrijvingsstatus'][0] ?? 'open' ) );
		$is_past     = $date < new DateTime( 'now', new DateTimeZone( 'Europe/Amsterdam' ) );

		return [
			'data_raw'      => $meta,
			'timestamp'     => Helpers::getDateParts( $date, $duration ),
			'timestamp_raw' => $date_string,
			'soort'         => $post->post_type,
			'type'          => $location['type'] !== '' ? strtolower( $location['type'] ) : strtolower( (string) ( $meta['type'][0] ?? 'locatie' ) ),
			'locatie'       => $location['label'],
			'duur'          => $duration,
			'intro'         => (string) ( $meta['intro_kort'][0] ?? '' ),
			'intro_lang'    => (string) ( $meta['intro_lang'][0] ?? '' ),
			'onderwerpen'   => (string) ( $meta['onderwerpen'][0] ?? '' ),
			'past'          => $is_past,
			'open'          => $status === 'open' && ! $is_past,
            'inschrijving'  => (string) ( $meta['inschrijvingsstatus'][0] ?? '' ),
			'status'        => $is_past ? 'geweest' : $status,
		];
	}

	/**
	 * Get display-ready location data for Event posts.
	 *
	 * @return array<string, string>
	 */
	private static function event_location_data( WP_Post $post ): array {
		$name        = trim( (string) get_post_meta( $post->ID, 'locatie_naam', true ) );
		$city        = trim( (string) get_post_meta( $post->ID, 'locatie_plaats', true ) );
		$street      = trim( (string) get_post_meta( $post->ID, 'locatie_straat_nr', true ) );
		$postal_code = trim( (string) get_post_meta( $post->ID, 'locatie_postcode', true ) );
		$type        = trim( (string) get_post_meta( $post->ID, 'locatie_type', true ) );

		if ( $name !== '' ) {
			$label = $name;
		} elseif ( $city !== '' ) {
			$label = $city;
		} else {
			$label = trim( (string) get_post_meta( $post->ID, 'vestiging', true ) );
		}

		return [
			'type'        => $type,
			'label'       => $label,
			'name'        => $name,
			'street'      => $street,
			'postal_code' => $postal_code,
			'city'        => $city,
			'phone'       => trim( (string) get_post_meta( $post->ID, 'locatie_telefoonnummer', true ) ),
			'email'       => trim( (string) get_post_meta( $post->ID, 'locatie__e_mail', true ) ?? (string) get_post_meta( $post->ID, 'locatie_e_mail', true ) ), //typo by indicia
			'maps_url'    => trim( (string) get_post_meta( $post->ID, 'locatie_google_maps_link', true ) ),
		];
	}

	/**
	 * Render the agenda card location rows.
	 *
	 * @param array<string, mixed> $event_data
	 */
	private static function render_location_rows( WP_Post $post, array $event_data, bool $is_online, bool $compact ): void {
		if ( $is_online ) {
			?>
			<tr>
				<td><?= Assets::getIcon( 'ui_location' ); ?></td>
				<td><?php esc_html_e( 'Online', THEME_TEXT_DOMAIN ); ?></td>
			</tr>
			<?php
			return;
		}

		if ( $compact ) {
			?>
			<tr>
				<td><?= Assets::getIcon( 'ui_location' ); ?></td>
				<td><?= esc_html( sprintf( __( ($post->post_type == 'voorlichting' ? 'Vestiging %s' : '%s'), THEME_TEXT_DOMAIN ), ucfirst( $event_data['locatie'] ) ) ); ?></td>
			</tr>
			<?php
			return;
		}

		$location = self::event_location_data( $post );
		$city_line = trim( $location['postal_code'] . ' ' . $location['city'] );
		?>
		<?php if ( $location['label'] !== '' ) : ?>
			<tr>
				<td><?= Assets::getIcon( 'ui_location' ); ?></td>
				<td><?= esc_html( $location['label'] ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( $location['street'] !== '' || $city_line !== '' ) : ?>
			<tr>
				<td></td>
				<td>
					<?php if ( $location['street'] !== '' ) : ?>
						<?= esc_html( $location['street'] ); ?>
					<?php endif; ?>
					<?php if ( $location['street'] !== '' && $city_line !== '' ) : ?>
						<br>
					<?php endif; ?>
					<?php if ( $city_line !== '' ) : ?>
						<?= esc_html( $city_line ); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>

        <?php if ( $location['maps_url'] !== '' ) : ?>
            <tr>
                <td></td>
                <td>
                    <a class="nok-hyperlink" href="<?= esc_url( $location['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Bekijk op Google Maps', THEME_TEXT_DOMAIN ); ?>
                    </a>
                </td>
            </tr>
        <?php endif; ?>

		<?php if ( $location['phone'] !== '' ) :
			$phone_href = preg_replace( '/[^0-9+]/', '', $location['phone'] );
			?>
			<tr>
				<td><?= Assets::getIcon( 'ui_telefoon' ); ?></td>
				<td>
					<a class="nok-hyperlink" href="tel:<?= esc_attr( $phone_href ); ?>">
						<?= esc_html( Helpers::format_phone( $location['phone'] ) ); ?>
					</a>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( $location['email'] !== '' ) : ?>
			<tr>
				<td><?= Assets::getIcon( 'ui_email' ); ?></td>
				<td>
					<a class="nok-hyperlink" href="mailto:<?= esc_attr( sanitize_email( $location['email'] ) ); ?>">
						<?= esc_html( antispambot( $location['email'] ) ); ?>
					</a>
				</td>
			</tr>
		<?php endif; ?>

		<?php
	}

	/**
	 * @param array<string, mixed> $event_data
	 */
	private static function card_title( WP_Post $post, array $event_data ): string {
		if ( $post->post_type === 'voorlichting' ) {
			return trim( ucfirst( $event_data['soort'] ) . ' ' . ucfirst( $event_data['locatie'] ) );
		}

		return get_the_title( $post );
	}

	/**
	 * @param array<string, mixed> $event_data
	 */
	private static function is_registrable( WP_Post $post, array $event_data ): bool {
		#if ( ! $event_data['open'] ) {
		#	return false;
		#}

		if ( $post->post_type === 'evenement' ) {
			return self::event_form_id( $post->ID ) > 0;
		}

		return true;
	}

	private static function event_form_id( int $post_id ): int {
		return match ( self::event_form_label( $post_id ) ) {
			'algemeen' => 10,
			'zorgverlener' => 9,
			default => 0,
		};
	}

	private static function event_form_label( int $post_id ): string {
		$value = strtolower( trim( (string) get_post_meta( $post_id, 'event_type_formulier', true ) ) );
		$value = str_replace( [ '(', ')' ], '', $value );

		if ( str_contains( $value, 'zorgverlener' ) ) {
			return 'zorgverlener';
		}

		if ( str_contains( $value, 'algemeen' ) ) {
			return 'algemeen';
		}

		return 'geen';
	}
}
