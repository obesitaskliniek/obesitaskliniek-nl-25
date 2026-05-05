<?php
// inc/VoorlichtingForm.php

namespace NOK2025\V1;

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
	 * Form configuration - single source of truth for Gravity Forms references.
	 *
	 * Uses Form 1 (same as single-voorlichting page). Location/datetime
	 * selection is handled by external dropdowns, not form fields.
	 *
	 * Fields are addressed by their adminLabel (set in GF admin → Field →
	 * Advanced → Admin Field Label) rather than numeric ID. GF renumbers IDs
	 * when fields are deleted/recreated, so storing IDs in code creates a
	 * fragile coupling. adminLabel is admin-only, never shown on the
	 * frontend, and survives field renumbering.
	 *
	 * Referenced by:
	 * - functions.php (gform_validation_1 / gform_after_submission_1 hooks)
	 * - Template data attributes (nok-voorlichting-aanmelden.php)
	 * - JavaScript form handler (nok-voorlichting-form.mjs)
	 */
	public const FORM_ID = 1;
	public const ADMIN_LABEL_VOORLICHTING_ID = 'voorlichting_id';
	public const ADMIN_LABEL_SUBMISSION_ID   = 'submission_id';
	public const ADMIN_LABEL_EVENT_TYPE      = 'event_type';

	/**
	 * Cache of field IDs resolved by adminLabel during the current request.
	 *
	 * Populated by the gform_pre_render filter — the canonical render-time
	 * hook where GF is guaranteed to be fully bootstrapped and $form is
	 * fully resolved. Keyed by adminLabel.
	 *
	 * @var array<string,int>
	 */
	private static array $resolved_field_ids = [];

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_misconfiguration_notice' ] );

		// Capture resolved field IDs every time form 1 is rendered. Cheap
		// (one foreach over the fields array) and guaranteed to fire when
		// the template calls gravity_form( FORM_ID, ... ).
		add_filter( 'gform_pre_render_' . self::FORM_ID, [ self::class, 'capture_field_ids' ] );
	}

	/**
	 * Cache field IDs resolved from adminLabels during form pre-render.
	 *
	 * Hooked on gform_pre_render_<FORM_ID>. Populates self::$resolved_field_ids
	 * keyed by adminLabel; returns $form unchanged. Filter, not action — must
	 * return the form.
	 *
	 * @param array $form
	 * @return array
	 */
	public static function capture_field_ids( $form ): array {
		if ( is_array( $form ) ) {
			foreach ( [
				self::ADMIN_LABEL_VOORLICHTING_ID,
				self::ADMIN_LABEL_SUBMISSION_ID,
				self::ADMIN_LABEL_EVENT_TYPE,
			] as $label ) {
				$id = self::field_id( $form, $label );
				if ( $id !== null ) {
					self::$resolved_field_ids[ $label ] = $id;
				}
			}
		}
		return $form;
	}

	/**
	 * Look up a previously-captured field ID.
	 *
	 * Returns null if gform_pre_render_<FORM_ID> hasn't fired yet for this
	 * request — i.e. before gravity_form() has been called. Callers that
	 * need the ID before render must either render the form first (output
	 * buffer) or fall back to {@see self::load_form()} + field_id().
	 *
	 * @param string $admin_label
	 * @return int|null
	 */
	public static function get_resolved_field_id( string $admin_label ): ?int {
		return self::$resolved_field_ids[ $admin_label ] ?? null;
	}

	/**
	 * Resolve a Gravity Forms field ID by its adminLabel.
	 *
	 * Pure function over a form array — no I/O, trivially testable. Callers
	 * in hook context already receive $form; callers outside hook context
	 * should fetch it via {@see self::load_form()}.
	 *
	 * @param array  $form        GF form array (as returned by GFAPI::get_form_meta).
	 * @param string $admin_label The adminLabel set on the field in GF admin.
	 * @return int|null Field ID, or null if no field matches.
	 */
	public static function field_id( array $form, string $admin_label ): ?int {
		foreach ( $form['fields'] ?? [] as $field ) {
			if ( ( $field->adminLabel ?? null ) === $admin_label ) {
				return (int) $field->id;
			}
		}
		return null;
	}

	/**
	 * Resolve a field ID and log if missing.
	 *
	 * Wraps {@see self::field_id()} with a single error_log call so callsites
	 * stay one line. Returns null on miss; callers decide the user-facing
	 * fallback.
	 *
	 * @param array  $form
	 * @param string $admin_label
	 * @return int|null
	 */
	public static function require_field_id( array $form, string $admin_label ): ?int {
		$id = self::field_id( $form, $admin_label );
		if ( $id === null ) {
			error_log( sprintf(
				'NOK voorlichting form (%d): missing adminLabel "%s"',
				self::FORM_ID,
				$admin_label
			) );
		}
		return $id;
	}

	/**
	 * Load the configured form's metadata.
	 *
	 * Thin wrapper around GFAPI::get_form_meta() that no-ops gracefully if
	 * Gravity Forms is deactivated. GFAPI caches per-request internally
	 * (see GFFormsModel::$_current_forms), so repeated calls are free.
	 *
	 * @return array|null
	 */
	public static function load_form(): ?array {
		if ( ! class_exists( '\GFAPI' ) ) {
			return null;
		}
		try {
			$form = \GFAPI::get_form_meta( self::FORM_ID );
		} catch ( \Throwable $e ) {
			error_log( 'NOK VoorlichtingForm::load_form: ' . $e->getMessage() );
			return null;
		}
		return is_array( $form ) ? $form : null;
	}

	/**
	 * Render an admin notice when form 1 is missing required adminLabels.
	 *
	 * The whole point of resolving fields by adminLabel is to surface
	 * misconfiguration earlier than "submit-time silent failure." Wrapped in
	 * try/catch so a fatal here can never bring down wp-admin — diagnostic
	 * fallback to the PHP error log.
	 *
	 * Skipped on AJAX/cron/REST contexts (not admin page renders) and for
	 * users without manage_options to limit blast radius.
	 *
	 * @return void
	 */
	public function maybe_render_misconfiguration_notice(): void {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		try {
			$form = self::load_form();
			if ( $form === null ) {
				return; // GF inactive or form missing — separate problem.
			}
			$missing = [];
			foreach ( [
				self::ADMIN_LABEL_VOORLICHTING_ID,
				self::ADMIN_LABEL_SUBMISSION_ID,
				self::ADMIN_LABEL_EVENT_TYPE,
			] as $label ) {
				if ( self::field_id( $form, $label ) === null ) {
					$missing[] = $label;
				}
			}
			if ( empty( $missing ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p><strong>Voorlichting-aanmelding is broken:</strong> Gravity Form %d is missing %s with adminLabel %s. Set the Admin Field Label under Field → Advanced.</p></div>',
				(int) self::FORM_ID,
				count( $missing ) === 1 ? 'a field' : 'fields',
				'<code>' . implode( '</code>, <code>', array_map( 'esc_html', $missing ) ) . '</code>'
			);
		} catch ( \Throwable $e ) {
			error_log( 'NOK VoorlichtingForm::maybe_render_misconfiguration_notice: ' . $e->getMessage() );
		}
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
}
