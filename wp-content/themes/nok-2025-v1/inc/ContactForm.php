<?php
// inc/ContactForm.php

namespace NOK2025\V1;

/**
 * ContactForm - Gravity Forms integration for contact forms with vestiging routing
 *
 * Provides:
 * - Dynamic dropdown population with vestigingen from CPT
 * - Notification routing based on selected vestiging's email
 *
 * Usage:
 * 1. Create a dropdown field in Gravity Forms
 * 2. Add CSS class "populate-vestigingen" to the field
 * 3. Create a notification named "Vestiging Notification" for routing
 *
 * @package NOK2025\V1
 */
class ContactForm {
	/**
	 * Debug mode override for this form specifically
	 * - null: follows Theme::is_development_mode() (default)
	 * - true: force debug mode ON (all emails to DEBUG_EMAIL)
	 * - false: force debug mode OFF (normal routing)
	 */
	private const DEBUG_MODE = true;

	/**
	 * Debug email - receives all notifications when debug mode is active
	 */
	private const DEBUG_EMAIL = 'it@obesitaskliniek.nl';

	/**
	 * Form ID for the contact form
	 */
	public const FORM_ID = 4;

	/**
	 * Value used for the "Algemeen" (general) option
	 */
	private const ALGEMEEN_VALUE = 'algemeen';

	/**
	 * Fallback email for general inquiries
	 */
	private const ALGEMEEN_EMAIL = 'info@obesitaskliniek.nl';

	/**
	 * CSS class used to identify vestiging dropdown fields
	 */
	private const VESTIGING_FIELD_CLASS = 'populate-vestigingen';

	/**
	 * Notification name that should be routed to vestiging email
	 */
	private const ROUTED_NOTIFICATION_NAME = 'Beheerdersmelding';

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Only register if Gravity Forms is active
		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		// Populate vestiging dropdown - all 4 hooks needed for complete coverage
		add_filter( 'gform_pre_render_' . self::FORM_ID, [ $this, 'populate_vestigingen' ] );
		add_filter( 'gform_pre_validation_' . self::FORM_ID, [ $this, 'populate_vestigingen' ] );
		add_filter( 'gform_pre_submission_filter_' . self::FORM_ID, [ $this, 'populate_vestigingen' ] );
		add_filter( 'gform_admin_pre_render_' . self::FORM_ID, [ $this, 'populate_vestigingen' ] );

		// Route notification to vestiging email
		add_filter( 'gform_notification_' . self::FORM_ID, [ $this, 'route_to_vestiging' ], 10, 3 );

		// Register entry meta for display in Entries section
		add_filter( 'gform_entry_meta', [ $this, 'register_entry_meta' ], 10, 2 );

		// Display routed email in entry detail sidebar
		add_action( 'gform_entry_detail_sidebar_middle', [ $this, 'display_routed_email_in_sidebar' ], 10, 2 );
	}

	/**
	 * Display routed email in entry detail sidebar
	 *
	 * @param array $form  The form object
	 * @param array $entry The entry object
	 */
	public function display_routed_email_in_sidebar( array $form, array $entry ): void {
		if ( $form['id'] != self::FORM_ID ) {
			return;
		}

		$routed_email = gform_get_meta( $entry['id'], 'routed_to_email' );
		if ( ! $routed_email ) {
			$routed_email = '(niet beschikbaar)';
		}

		?>
		<div class="postbox">
			<h3 class="hndle" style="cursor: default;">
				<span><?php esc_html_e( 'Verzonden naar', THEME_TEXT_DOMAIN ); ?></span>
			</h3>
			<div class="inside">
				<p><strong><?php echo esc_html( $routed_email ); ?></strong></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Register custom entry meta for display in Entries section
	 *
	 * @param array $entry_meta Existing entry meta
	 * @param int   $form_id    Form ID
	 * @return array Modified entry meta
	 */
	public function register_entry_meta( array $entry_meta, int $form_id ): array {
		if ( $form_id !== self::FORM_ID ) {
			return $entry_meta;
		}

		$entry_meta['routed_to_email'] = [
			'label'             => __( 'Verzonden naar', THEME_TEXT_DOMAIN ),
			'is_numeric'        => false,
			'is_default_column' => true,  // Show in entry list by default
			'filter'            => [
				'type'      => 'text',
				'operators' => [ 'contains', 'is' ],
			],
		];

		return $entry_meta;
	}

	/**
	 * Populate dropdown field with vestigingen
	 *
	 * Targets fields with CSS class "populate-vestigingen".
	 * Stores vestiging post ID as value for email lookup during notification routing.
	 *
	 * @param array $form The form object
	 * @return array Modified form object
	 */
	public function populate_vestigingen( array $form ): array {
		foreach ( $form['fields'] as &$field ) {
			// Skip non-select fields or fields without our marker class
			if ( $field->type !== 'select' ) {
				continue;
			}

			if ( strpos( $field->cssClass ?? '', self::VESTIGING_FIELD_CLASS ) === false ) {
				continue;
			}

			$vestigingen = get_posts( [
				'post_type'      => 'vestiging',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			// Start with "Algemeen" option for general inquiries (pre-selected)
			$choices = [
				[
					'text'       => __( 'Algemeen', THEME_TEXT_DOMAIN ),
					'value'      => self::ALGEMEEN_VALUE,
					'isSelected' => true,
				],
			];

			foreach ( $vestigingen as $vestiging ) {
				// Extract city name from title (e.g., "NOK Amsterdam" -> "Amsterdam")
				$label = preg_replace( '/^NOK\s+/i', '', $vestiging->post_title );

				$choices[] = [
					'text'  => esc_html( $label ),
					'value' => (string) $vestiging->ID,
				];
			}

			$field->placeholder = '';  // No placeholder needed - "Algemeen" is pre-selected
			$field->choices     = $choices;
		}

		return $form;
	}

	/**
	 * Route notification to selected vestiging's email address
	 *
	 * Looks up the vestiging from the dropdown field value and sets
	 * the notification recipient to that vestiging's email.
	 *
	 * @param array $notification The notification object
	 * @param array $form         The form object
	 * @param array $entry        The entry data
	 * @return array Modified notification object
	 */
	public function route_to_vestiging( array $notification, array $form, array $entry ): array {
		// Only process the designated notification
		if ( ( $notification['name'] ?? '' ) !== self::ROUTED_NOTIFICATION_NAME ) {
			return $notification;
		}

		// Determine target email
		$target_email = $this->determine_target_email( $form, $entry );

		if ( $target_email ) {
			$notification['to'] = $target_email;

			// Log the routed email to entry meta (visible in Entries section)
			$this->log_routed_email( $entry['id'], $target_email );
		}

		return $notification;
	}

	/**
	 * Determine the target email for routing
	 *
	 * @param array $form  The form object
	 * @param array $entry The entry data
	 * @return string|null Target email or null if routing fails
	 */
	private function determine_target_email( array $form, array $entry ): ?string {
		// Debug mode: override all routing
		if ( $this->is_debug_mode() ) {
			return self::DEBUG_EMAIL;
		}

		// Find the vestiging field ID
		$vestiging_field_id = $this->find_vestiging_field_id( $form );
		if ( ! $vestiging_field_id ) {
			return null;
		}

		// Get the selected value from entry
		$selected_value = rgar( $entry, (string) $vestiging_field_id );
		if ( ! $selected_value ) {
			return null;
		}

		// Handle "Algemeen" option
		if ( $selected_value === self::ALGEMEEN_VALUE ) {
			return self::ALGEMEEN_EMAIL;
		}

		// Must be a vestiging ID - look up email
		if ( ! is_numeric( $selected_value ) ) {
			return null;
		}

		$email = get_post_meta( (int) $selected_value, '_email', true );
		if ( ! is_email( $email ) ) {
			// Fall back to general email if vestiging has no valid email
			return self::ALGEMEEN_EMAIL;
		}

		return $email;
	}

	/**
	 * Log the routed email address to entry meta
	 *
	 * This makes the recipient visible in the Gravity Forms Entries section.
	 *
	 * @param int    $entry_id Entry ID
	 * @param string $email    The email address used for routing
	 */
	private function log_routed_email( int $entry_id, string $email ): void {
		if ( ! function_exists( 'gform_add_meta' ) ) {
			return;
		}

		// Add debug indicator if in debug mode
		$value = $this->is_debug_mode()
			? sprintf( '%s (DEBUG MODE)', $email )
			: $email;

		gform_add_meta( $entry_id, 'routed_to_email', $value );
	}

	/**
	 * Check if debug mode is active
	 *
	 * Uses explicit DEBUG_MODE constant if set, otherwise follows global theme setting.
	 *
	 * @return bool True if debug mode is active
	 */
	private function is_debug_mode(): bool {
		// Explicit override takes precedence
		if ( self::DEBUG_MODE !== null ) {
			return self::DEBUG_MODE;
		}

		// Fall back to global theme development mode
		return Theme::get_instance()->is_development_mode();
	}

	/**
	 * Find the field ID of the vestiging dropdown
	 *
	 * @param array $form The form object
	 * @return int|null Field ID or null if not found
	 */
	private function find_vestiging_field_id( array $form ): ?int {
		foreach ( $form['fields'] as $field ) {
			if ( $field->type !== 'select' ) {
				continue;
			}

			if ( strpos( $field->cssClass ?? '', self::VESTIGING_FIELD_CLASS ) !== false ) {
				return (int) $field->id;
			}
		}

		return null;
	}
}
