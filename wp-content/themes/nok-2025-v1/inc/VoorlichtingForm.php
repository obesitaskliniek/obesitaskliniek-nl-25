<?php
// inc/VoorlichtingForm.php

namespace NOK2025\V1;

use DateTime;
use DateTimeZone;
use WP_REST_Response;

/**
 * VoorlichtingForm - REST endpoint and form helpers for voorlichting registration
 *
 * Provides:
 * - REST endpoint for fetching voorlichting options (locations and events)
 * - Data transformation for Gravity Forms population
 * - Cache-control headers to bypass page caching
 *
 * The endpoint returns voorlichtingen grouped by vestiging (location),
 * allowing AJAX-based dropdown population that stays fresh regardless
 * of page caching configuration.
 *
 * @example REST endpoint usage
 * GET /wp-json/nok-2025-v1/v1/voorlichtingen/options
 * Returns: { locations: [...], events: { "amsterdam": [...], ... } }
 *
 * @package NOK2025\V1
 */
class VoorlichtingForm {
	/**
	 * Form configuration - single source of truth for Gravity Forms field IDs
	 *
	 * These constants define the Gravity Forms form and field IDs used for
	 * voorlichting registration. Referenced by:
	 * - Template data attributes (nok-voorlichting-aanmelden.php)
	 * - PHP form hooks (functions.php)
	 * - JavaScript form handler (nok-voorlichting-form.mjs)
	 */
	public const FORM_ID = 2;
	public const FIELD_LOCATION = 21;
	public const FIELD_DATETIME = 22;
	public const FIELD_VOORLICHTING_ID = 18;

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Register REST API endpoints
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		register_rest_route( 'nok-2025-v1/v1', '/voorlichtingen/options', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_form_options' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * REST callback: Get voorlichting options for form dropdowns
	 *
	 * Returns locations with upcoming events and events grouped by location.
	 * Only includes future events with status "open" or "vol" (full events
	 * are shown as disabled options).
	 *
	 * Response structure:
	 * {
	 *   "locations": [
	 *     { "value": "amsterdam", "label": "Amsterdam", "count": 5 }
	 *   ],
	 *   "events": {
	 *     "amsterdam": [
	 *       {
	 *         "id": 123,
	 *         "label": "dinsdag 18 maart - 18:30 uur (Op locatie)",
	 *         "disabled": false,
	 *         "type": "op locatie"
	 *       }
	 *     ]
	 *   }
	 * }
	 *
	 * @return WP_REST_Response
	 */
	public function get_form_options(): WP_REST_Response {
		$locations = [];
		$events    = [];

		// Get all vestigingen with upcoming voorlichtingen
		$vestigingen = get_posts( [
			'post_type'      => 'vestiging',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $vestigingen as $vestiging ) {
			// Extract city name from title (e.g., "NOK Amsterdam" -> "Amsterdam")
			$city = preg_replace( '/^NOK\s+/i', '', $vestiging->post_title );

			// Get upcoming voorlichtingen for this vestiging
			$voorlichtingen = Helpers::get_voorlichtingen_for_vestiging( $city, 20, false );

			if ( empty( $voorlichtingen ) ) {
				continue;
			}

			$location_key = sanitize_title( $city );
			$location_events = [];
			$open_count = 0;

			foreach ( $voorlichtingen as $vl ) {
				$data = Helpers::setup_hubspot_metadata( $vl->ID );

				// Skip cancelled events
				if ( $data['status'] === 'geannuleerd' ) {
					continue;
				}

				$is_disabled = ( $data['status'] !== 'open' );
				$type_label  = strtolower( $data['type'] ) === 'online' ? 'Online' : 'Op locatie';
				$status_suffix = '';

				if ( $data['status'] === 'vol' ) {
					$status_suffix = ' (vol)';
				} elseif ( $data['status'] === 'gesloten' ) {
					$status_suffix = ' (gesloten)';
				}

				$location_events[] = [
					'id'       => $vl->ID,
					'label'    => sprintf(
						'%s - %s uur (%s)%s',
						$data['timestamp']['niceDateFull'],
						$data['timestamp']['start_time'],
						$type_label,
						$status_suffix
					),
					'disabled' => $is_disabled,
					'type'     => strtolower( $data['type'] ),
					'date'     => $data['timestamp_raw'],
				];

				if ( ! $is_disabled ) {
					$open_count++;
				}
			}

			if ( ! empty( $location_events ) ) {
				$locations[] = [
					'value' => $location_key,
					'label' => $city,
					'count' => $open_count,
				];

				$events[ $location_key ] = $location_events;
			}
		}

		// Sort locations by label
		usort( $locations, fn( $a, $b ) => strcmp( $a['label'], $b['label'] ) );

		$response = new WP_REST_Response( [
			'locations' => $locations,
			'events'    => $events,
		], 200 );

		// Add cache-control headers to ensure fresh data
		$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );

		return $response;
	}

	/**
	 * Get Gravity Forms dropdown choices for vestiging field
	 *
	 * Returns choices array formatted for Gravity Forms field population.
	 * Only includes locations with at least one open voorlichting.
	 *
	 * @return array Gravity Forms choices array
	 */
	public static function get_vestiging_choices(): array {
		$choices = [];

		$vestigingen = get_posts( [
			'post_type'      => 'vestiging',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $vestigingen as $vestiging ) {
			$city  = preg_replace( '/^NOK\s+/i', '', $vestiging->post_title );
			$count = Helpers::count_upcoming_voorlichtingen( $city, true );

			if ( $count > 0 ) {
				$choices[] = [
					'text'  => $city,
					'value' => sanitize_title( $city ),
				];
			}
		}

		return $choices;
	}
}
