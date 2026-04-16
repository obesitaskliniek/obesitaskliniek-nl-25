<?php
/**
 * Archive Template: Voorlichtingen (Agenda)
 *
 * Displays upcoming voorlichting (information session) posts in a month-based view.
 * Supports filtering by vestiging via ?locatie= query parameter.
 * Supports month navigation via ?maand= and ?jaar= parameters.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

// Backward compat: ?week= redirects to ?maand=&jaar= (must happen before get_header sends output)
$timezone = new DateTimeZone( 'Europe/Amsterdam' );
$today    = new DateTime( 'now', $timezone );

if ( isset( $_GET['week'] ) && ! isset( $_GET['maand'] ) ) {
    $redirect_date = null;
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['week'] ) ) {
        $redirect_date = new DateTime( $_GET['week'], $timezone );
    } elseif ( preg_match( '/^\d{1,2}$/', $_GET['week'] ) ) {
        $week_year = isset( $_GET['jaar'] ) && preg_match( '/^\d{4}$/', $_GET['jaar'] )
                ? (int) $_GET['jaar']
                : (int) $today->format( 'Y' );
        $redirect_date = new DateTime( 'now', $timezone );
        $redirect_date->setISODate( $week_year, (int) $_GET['week'], 1 );
    }
    if ( $redirect_date ) {
        $filter_loc   = isset( $_GET['locatie'] ) ? sanitize_text_field( $_GET['locatie'] ) : '';
        $redirect_url = get_post_type_archive_link( 'voorlichting' );
        $redirect_url = add_query_arg( [
                'maand' => (int) $redirect_date->format( 'n' ),
                'jaar'  => $redirect_date->format( 'Y' ),
        ], $redirect_url );
        if ( $filter_loc ) {
            $redirect_url = add_query_arg( 'locatie', rawurlencode( $filter_loc ), $redirect_url );
        }
        wp_safe_redirect( $redirect_url, 301 );
        exit;
    }
}

get_header( 'generic' );

// Get vestiging filter from query string (use 'locatie' to avoid conflict with 'vestiging' post type)
$filter_vestiging = isset( $_GET['locatie'] ) ? sanitize_text_field( $_GET['locatie'] ) : '';

// Parse month/year from query params or default to current month
if ( isset( $_GET['maand'] ) && preg_match( '/^(?:1[0-2]|[1-9])$/', $_GET['maand'] )
     && isset( $_GET['jaar'] ) && preg_match( '/^\d{4}$/', $_GET['jaar'] ) ) {
    $month_start = new DateTime( 'now', $timezone );
    $month_start->setDate( (int) $_GET['jaar'], (int) $_GET['maand'], 1 );
} else {
    // Default: 1st of current month
    $month_start = clone $today;
    $month_start->setDate( (int) $today->format( 'Y' ), (int) $today->format( 'n' ), 1 );
}
$month_start->setTime( 0, 0, 0 );

// Calculate month end
$month_end = clone $month_start;
$month_end->modify( 'last day of this month' );
$month_end->setTime( 23, 59, 59 );

// Previous and next month
$prev_month = clone $month_start;
$prev_month->modify( '-1 month' );
$next_month = clone $month_start;
$next_month->modify( '+1 month' );

// Format month for display: "maart 2026"
$date_range = sprintf( '%s %s', Helpers::dutchMonth( (int) $month_start->format( 'n' ) ), $month_start->format( 'Y' ) );

// Hide past events: when viewing current month, start from today
$query_start = clone $month_start;
if ( $month_start->format( 'Y-m' ) === $today->format( 'Y-m' ) ) {
    $query_start = clone $today;
    $query_start->setTime( 0, 0, 0 );
}

// Build query args for the month
$query_args = [
        'post_type'      => 'voorlichting',
        'posts_per_page' => - 1,
        'post_status'    => 'publish',
        'meta_key'       => 'aanvangsdatum_en_tijd',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
                'relation' => 'AND',
                [
                        'key'     => 'aanvangsdatum_en_tijd',
                        'value'   => $query_start->format( 'Y-m-d H:i:s' ),
                        'compare' => '>=',
                        'type'    => 'DATETIME',
                ],
                [
                        'key'     => 'aanvangsdatum_en_tijd',
                        'value'   => $month_end->format( 'Y-m-d H:i:s' ),
                        'compare' => '<=',
                        'type'    => 'DATETIME',
                ],
        ],
];

// Add vestiging filter if specified
if ( $filter_vestiging ) {
    $variants                   = Helpers::get_vestiging_name_variants( $filter_vestiging );
    $query_args['meta_query'][] = [
            'key'     => 'vestiging',
            'value'   => $variants,
            'compare' => 'IN',
    ];
}

// Run query
$voorlichting_query = new WP_Query( $query_args );

// Get all vestigingen for filter dropdown
$vestigingen = get_posts( [
        'post_type'      => 'vestiging',
        'posts_per_page' => - 1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
] );

// Build navigation URLs (cache archive link — called in closure and datepicker)
$archive_link = get_post_type_archive_link( 'voorlichting' );

$build_url = function ( $month_date, $locatie = null ) use ( $archive_link, $filter_vestiging ) {
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

// Base URL without locatie filter (used by "clear filter" dropdown option)
$base_month_url = add_query_arg( [
        'maand' => (int) $month_start->format( 'n' ),
        'jaar'  => $month_start->format( 'Y' ),
], $archive_link );
?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">
            <header class="nok-section__inner nok-my-0">
                <?php Helpers::render_breadcrumbs(); ?>
                <h1 class="nok-fs-giant"><?php esc_html_e( 'Agenda', THEME_TEXT_DOMAIN ); ?></h1>
                <p class="nok-intro-text">
                    <?php
                    $intro = Theme::get_archive_intro( 'voorlichting', '' );
                    if ( $intro ):
                        echo wp_kses_post( $intro );
                    else:
                        esc_html_e( 'Hier is plek voor een korte introductietekst over de agenda en evenementen van de Nederlandse Obesitas Kliniek.', THEME_TEXT_DOMAIN );
                    endif;
                    ?>
                </p>
            </header>
        </div>
    </nok-hero>

    <nok-section class="no-aos collapse-top">
        <div class="nok-section__inner ">

            <!-- Week selector and filters -->
            <div class="nok-agenda-controls
            nok-layout-grid
            nok-align-items-center
            nok-columns-2 nok-columns-to-lg-1
            nok-mb-3 nok-row-gap-1 nok-column-gap-2">
                <div class="nok-agenda-week-selector">
                    <h2 class="nok-fs-6 nok-mb-0">
                        <?php esc_html_e( 'Voorlichtingen in', THEME_TEXT_DOMAIN ); ?>
                        <span class="fw-bold"><?= esc_html( $date_range ); ?></span>
                    </h2>
                </div>

                <div class="nok-form nok-layout-flex-row
                nok-align-items-center
                 nok-justify-content-to-lg-space-between nok-justify-content-end
                 nok-column-gap-1">
                    <!-- Month picker -->
                    <?php
                    // Build URL pattern for datepicker navigation (uses month/year format)
                    $datepicker_url_pattern = add_query_arg( [
                            'maand' => '{maand}',
                            'jaar'  => '{jaar}',
                    ], $archive_link );
                    if ( $filter_vestiging ) {
                        $datepicker_url_pattern = add_query_arg( 'locatie', rawurlencode( $filter_vestiging ), $datepicker_url_pattern );
                    }
                    ?>
                    <div class="nok-datepicker"
                         data-requires="./nok-datepicker.mjs">
                        <button type="button" title="Klik om een andere maand te selecteren"
                                class="nok-datepicker__trigger nok-bg-white nok-text-darkerblue"
                                data-datepicker
                                data-mode="month"
                                data-value="<?= esc_attr( $month_start->format( 'Y-m-d' ) ); ?>"
                                data-url-pattern="<?= esc_attr( $datepicker_url_pattern ); ?>"
                                aria-expanded="false"
                                aria-haspopup="dialog">
                            <?= Assets::getIcon( 'ui_calendar' ); ?>
                            <span class="nok-datepicker__display"><?= esc_html( $date_range ); ?></span>
                        </button>
                    </div>

                    <!-- Filter dropdown -->
                    <?php if ( $vestigingen ): ?>
                        <div class="nok-select-wrapper nok-form-element nok-mb-0">
                            <select class="nok-select nok-bg-white nok-text-darkerblue"
                                    aria-label="<?php esc_attr_e( 'Filter evenementen op locatie', THEME_TEXT_DOMAIN ); ?>"
                                    onchange="if(this.value) window.location.href=this.value">
                                <option value="<?= esc_url( $base_month_url ); ?>" <?= empty( $filter_vestiging ) ? 'selected' : ''; ?>>
                                    <?php esc_html_e( 'Filter evenementen', THEME_TEXT_DOMAIN ); ?>
                                </option>
                                <?php foreach ( $vestigingen as $vestiging ):
                                    $vestiging_title = get_the_title( $vestiging->ID );
                                    $vestiging_city = preg_replace( '/^NOK\s+/i', '', $vestiging_title );
                                    if ( ! $vestiging_city ) {
                                        continue;
                                    }
                                    $filter_normalized = Helpers::normalize_vestiging_name( $filter_vestiging );
                                    $is_selected       = ! empty( $filter_vestiging ) && strtolower( $filter_normalized ) === strtolower( $vestiging_city );
                                    $option_url        = $build_url( $month_start, $vestiging_city );
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

            <?php if ( $voorlichting_query->have_posts() ): ?>
                <div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
                    <?php while ( $voorlichting_query->have_posts() ): $voorlichting_query->the_post();
                        $hubspotData = Helpers::setup_hubspot_metadata( get_the_ID() );
                        $is_open     = $hubspotData['open'];
                        $is_online   = strtolower( $hubspotData['type'] ) === 'online';

                        // Format date (e.g., "Maandag 9 december")
                        $event_date  = new DateTime( $hubspotData['timestamp_raw'], $timezone );
                        $day_names   = [
                                '',
                                'Maandag',
                                'Dinsdag',
                                'Woensdag',
                                'Donderdag',
                                'Vrijdag',
                                'Zaterdag',
                                'Zondag'
                        ];
                        $footer_date = $day_names[ (int) $event_date->format( 'N' ) ] . ' ' . $event_date->format( 'j' ) . ' ' . Helpers::dutchMonth( (int) $event_date->format( 'n' ) );
                        ?>

                        <nok-square-block class="nok-bg-white nok-dark-bg-darkestblue nok-grid-gap-0_5" data-shadow="true">

                            <span class="nok-square-block__banner nok-badge <?= $is_online ? 'nok-bg-lightblue--lighter' : 'nok-bg-green--lighter'; ?> nok-text-darkerblue">
                                <?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html__( 'Op locatie', THEME_TEXT_DOMAIN ); ?>
                            </span>

                            <h2 class="nok-square-block__heading">
                                <a href="<?php the_permalink(); ?>" class="nok-text-darkerblue nok-dark-text-white">
                                    <?= esc_html( ucfirst( $hubspotData['soort'] ) ); ?> <?= esc_html( ucfirst( $hubspotData['locatie'] ) ); ?>
                                </a>
                            </h2>

                            <table class="nok-square-block__text nok-icon-table">
                                <tr>
                                    <td><?= Assets::getIcon( 'ui_calendar' ); ?></td>
                                    <td class="fw-bold"><?= esc_html( $footer_date ); ?></td>
                                </tr>
                                <tr>
                                    <td><?= Assets::getIcon( 'ui_time' ); ?></td>
                                    <td><?= esc_html( $hubspotData['timestamp']['start_time'] ); ?>
                                        - <?= esc_html( $hubspotData['timestamp']['end_time'] ); ?> uur
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= Assets::getIcon( 'ui_location' ); ?></td>
                                    <td><?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html( ucfirst( $hubspotData['locatie'] ) ); ?></td>
                                </tr>
                            </table>

                            <div class="nok-layout-flex nok-layout-flex-row nok-column-gap-0_5">
                                <a href="<?php the_permalink(); ?>#aanmelden"
                                   class="nok-button nok-bg-yellow nok-text-darkerblue w-100 <?= ! $is_open ? 'disabled' : ''; ?>">
                                    <?php esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN ); ?>
                                </a>
                                <a href="<?php the_permalink(); ?>"
                                   class="nok-button nok-bg-lightgrey--lighter nok-text-darkerblue w-100 nok-dark-text-white">
                                    <?php esc_html_e( 'Informatie', THEME_TEXT_DOMAIN ); ?>
                                </a>
                            </div>
                        </nok-square-block>

                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <p class="nok-text-center nok-p-2"><?php esc_html_e( 'Geen voorlichtingen gevonden in deze maand.', THEME_TEXT_DOMAIN ); ?></p>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

            <!-- Month navigation bottom -->
            <nav class="nok-week-navigation nok-layout-flex nok-layout-flex-row nok-justify-content-space-between nok-mt-3 nok-pt-2 nok-border-top"
                 aria-label="<?php esc_attr_e( 'Maand navigatie', THEME_TEXT_DOMAIN ); ?>">
                <a href="<?= esc_url( $build_url( $prev_month ) ); ?>"
                   class="nok-button nok-bg-transparent nok-text-darkerblue">
                    <?= Assets::getIcon( 'ui_arrow-left' ); ?>
                    <?php esc_html_e( 'Vorige maand', THEME_TEXT_DOMAIN ); ?>
                </a>
                <a href="<?= esc_url( $build_url( $next_month ) ); ?>"
                   class="nok-button nok-bg-transparent nok-text-darkerblue">
                    <?php esc_html_e( 'Volgende maand', THEME_TEXT_DOMAIN ); ?>
                    <?= Assets::getIcon( 'ui_arrow-right' ); ?>
                </a>
            </nav>
        </div>
    </nok-section>

<?php
get_footer();
