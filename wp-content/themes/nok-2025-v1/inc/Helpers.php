<?php
// inc/Helpers.php
namespace NOK2025\V1;


use DateInterval;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use WP_Query;

/**
 * Helpers - Static utility methods for theme-wide functionality
 *
 * Provides helper functions for:
 * - Date and time formatting (Dutch locale)
 * - Phone number formatting (Dutch conventions)
 * - Opening hours display
 * - Quote extraction and collection from experience posts
 * - Content splitting and injection
 * - Image handling with fallbacks
 * - Breadcrumb rendering with Yoast integration
 * - Security headers and CSP generation
 *
 * All methods are static for convenient access throughout the theme.
 *
 * @example Format phone number
 * echo Helpers::format_phone('0201234567');
 * // Outputs: 020 - 123 45 67
 *
 * @example Build quote collection
 * $quotes = Helpers::build_quote_collection(
 *     explicit_posts: $context->quote_posts->json(),
 *     pad_with_random: true,
 *     minimum_count: 5
 * );/sti
 *
 * @example Render breadcrumbs
 * Helpers::render_breadcrumbs();
 *
 * @package NOK2025\V1
 */
class Helpers {
	/**
	 * Generate cryptographically secure random string
	 *
	 * Used for nonces, CSP tokens, cache busters, etc.
	 * Uses random_bytes() which is cryptographically secure (CSPRNG).
	 *
	 * @param int $bits Number of bits of randomness (default 256)
	 * @return string Random binary string
	 */
	public static function makeRandomString( int $bits = 256 ): string {
		$bytes = (int) ceil( $bits / 8 );
		try {
			return random_bytes( $bytes );
		} catch ( \Exception $e ) {
			// Fallback for edge cases where random_bytes fails (extremely rare)
			// Log the error and use wp_generate_password as fallback
			error_log( 'NOK Theme: random_bytes() failed: ' . $e->getMessage() );
			return wp_generate_password( $bytes, true, true );
		}
	}

	/**
	 * Show field value or placeholder when editing
	 *
	 * @param string $value The field value
	 *
	 * @return string
	 */
	public static function show_placeholder( string $value ): string {
		$theme_instance = Theme::get_instance();
		if ( ! empty( $value ) ) {
			return '<span class="placeholder-field" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#999;font-style:italic;max-width: 100cqi;font-size:14px;">Vul "' . $theme_instance->generate_field_label( $value ) . '" in</span>';
		} else {
			return '';
		}
	}

	/**
	 * Check if we're in preview/editing mode
	 *
	 * @param int|null $post_id Post ID to check permissions for
	 *
	 * @return bool
	 */
	public static function is_editing_mode( ?int $post_id = null ): bool {
		$post_id = $post_id ?: get_the_ID();

		return is_preview() || ( is_user_logged_in() && current_user_can( 'edit_post', $post_id ) );
	}

	/**
	 * Get featured image URL
	 *
	 * @param string $size Image size slug (default: 'large')
	 * @param bool   $icon Whether to use icon representation
	 *
	 * @return string Image URL or fallback URL
	 */
	public static function get_featured_image_uri( $post, string $size = 'large', bool $icon = false ): string {
		if ( has_post_thumbnail( $post ) ) {
			return wp_get_attachment_image_url(
				get_post_thumbnail_id( $post ),
				$size,
				$icon
			) ?: '';
		}

		return 'https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:300x0-25-0-0-center-0.jpg';
	}

	/**
	 * Renders a video thumbnail with popup trigger.
	 *
	 * At least one of $webm_url or $mp4_url must be provided.
	 * Uses separate data attributes to avoid JSON escaping issues.
	 *
	 * @param string      $webm_url    Video URL (WebM format). Can be empty if MP4 provided.
	 * @param string      $mp4_url     Video URL (MP4 format). Can be empty if WebM provided.
	 * @param string      $poster_url  Poster image URL (required for thumbnail display).
	 * @param string      $title       Video title (for popup header and accessibility).
	 * @param string|null $classes     Additional CSS classes.
	 *
	 * @return string HTML output, or empty string if no valid video sources.
	 */
	public static function render_video_thumbnail(
		string $webm_url,
		string $mp4_url,
		string $poster_url,
		string $title = '',
		?string $classes = null
	): string {
		// Validate: at least one video source required
		if ( empty( $webm_url ) && empty( $mp4_url ) ) {
			return '';
		}

		// Validate: poster required for thumbnail
		if ( empty( $poster_url ) ) {
			return '';
		}

		$icon = Assets::getIcon( 'ui_play' );

		// Build data attributes conditionally (avoid empty attributes)
		$data_attrs = [];
		if ( ! empty( $webm_url ) ) {
			$data_attrs[] = sprintf( 'data-video-webm="%s"', esc_url( $webm_url ) );
		}
		if ( ! empty( $mp4_url ) ) {
			$data_attrs[] = sprintf( 'data-video-mp4="%s"', esc_url( $mp4_url ) );
		}
		$data_attrs[] = sprintf( 'data-video-title="%s"', esc_attr( $title ) );

		return sprintf(
			'<div class="nok-video-thumbnail %s"
              %s
              tabindex="0"
              role="button"
              aria-label="Video afspelen: %s"
              data-toggles-class="popup-open"
              data-class-target="nok-top-navigation"
              data-toggles-attribute="data-state"
              data-toggles-attribute-value="open"
              data-attribute-target="#popup-video">
            <img class="nok-video-thumbnail__poster" src="%s" alt="" loading="lazy">
            <div class="nok-video-thumbnail__play" aria-hidden="true">
                <span class="nok-video-background__play-icon">%s</span>
            </div>
        </div>',
			esc_attr( $classes ?? '' ),
			implode( "\n              ", $data_attrs ),
			esc_attr( $title ),
			esc_url( $poster_url ),
			$icon
		);
	}

	public static function get_featured_image( $class = null ): string {
		if ( has_post_thumbnail() ) {
			$featuredImage = wp_get_attachment_image(
				get_post_thumbnail_id(),
				'large',
				false,
				[
					'loading'  => 'eager',
					'decoding' => 'async',
					'class'    => trim( ( $class ?? '' ) . " featured-image" ),
					'sizes'    => '(max-width: 1200px) 100vw, 1200px',
				]
			);
		} else {
			$featuredImage = '<img ' . ( $class ? "class='{$class}'" : '' ) . ' src="https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:100x0-25-0-0-center-0.jpg" 
					srcset="https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:1920x0-65-0-0-center-0.jpg 1920w,
                     https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:768x0-65-0-0-center-0.jpg 768w,
                     https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:320x0-65-0-0-center-0.jpg 320w,
                     https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:150x0-65-0-0-center-0.jpg 150w" sizes="(max-width: 575px) 100vw,
                         (min-width: 575px) 75vw,
                         (min-width: 768px) 84vw,
                         (min-width: 996px) 84vw,
                         (min-width: 1200px) 84vw" loading="eager" decoding="async">';
		}

		return $featuredImage;
	}

	/**
	 * @throws \Exception
	 */
	public static function getDateParts( $date, int $minutes = 0 ): array {
		$formatter = new IntlDateFormatter( 'nl_NL', IntlDateFormatter::NONE, IntlDateFormatter::NONE );

		// Calculate end time by adding minutes
		$endDate = clone $date;
		$endDate->add( new DateInterval( 'PT' . abs( $minutes ) . 'M' ) );
		if ( $minutes < 0 ) {
			$endDate = clone $date;
			$endDate->sub( new DateInterval( 'PT' . abs( $minutes ) . 'M' ) );
		}

		return [
			'day_number'   => $date->format( 'J' ),
			'day_name'     => $formatter->setPattern( 'EEEE' ) ? $formatter->format( $date ) : null,
			'day_short'    => $formatter->setPattern( 'EEE' ) ? $formatter->format( $date ) : null,
			'month_number' => $date->format( 'n' ),
			'month_name'   => $formatter->setPattern( 'MMMM' ) ? $formatter->format( $date ) : null,
			'month_short'  => $formatter->setPattern( 'MMM' ) ? $formatter->format( $date ) : null,
			'year'         => $date->format( 'Y' ),
			'hour'         => $date->format( 'G' ),
			'minute'       => $date->format( 'i' ),
			'niceDateFull' => $formatter->setPattern( 'EEEE d MMMM' ) ? $formatter->format( $date ) : null,
			'start_time'   => $date->format( 'G:i' ),
			'end_time'     => $endDate->format( 'G:i' )
		];
	}

	public static function minutesToTime( int $minutes ): string {
		$hours = intval( $minutes / 60 );
		$mins  = $minutes % 60;

		return sprintf( '%d:%02d', $hours, $mins );
	}

	public static function minutesToDutchRounded( int $minutes ): string {
		// Round to nearest 30 minutes
		$roundedMinutes = round( $minutes / 30 ) * 30;
		// Cap at 8 hours (480 minutes)
		$roundedMinutes = min( $roundedMinutes, 480 );

		$hours = $roundedMinutes / 60;

		return match ( $hours ) {
			0.0 => '0 minuten',
			0.5 => 'een half uur',
			1.0 => 'een uur',
			1.5 => 'anderhalf uur',
			2.0 => 'twee uur',
			2.5 => 'twee en een half uur',
			3.0 => 'drie uur',
			3.5 => 'drie en een half uur',
			4.0 => 'vier uur',
			4.5 => 'vier en een half uur',
			5.0 => 'vijf uur',
			5.5 => 'vijf en een half uur',
			6.0 => 'zes uur',
			6.5 => 'zes en een half uur',
			7.0 => 'zeven uur',
			7.5 => 'zeven en een half uur',
			8.0 => 'acht uur',
			default => 'acht uur'
		};
	}

	public static function classFirstP( string $string, string $class ): string {
		// Check if string contains any <p> tags
		if ( ! preg_match( '/<p(\s[^>]*)?>/i', $string ) ) {
			// No paragraph tags found - wrap entire content in <p> with class
			return '<p class="' . htmlspecialchars( $class, ENT_QUOTES ) . '">' . $string . '</p>';
		}

		// Paragraph tags exist - apply class to first one using original logic
		return preg_replace( '/<p(\s[^>]*)?>/i', '<p$1 class="' . htmlspecialchars( $class, ENT_QUOTES ) . '">', $string, 1 );
	}

	/**
	 * Query and loop through the last n posts from a custom post type
	 *
	 * @param string $post_type Custom post type slug
	 * @param int $count Number of posts to retrieve
	 * @param array $meta_query Optional meta query parameters
	 * @param array $tax_query Optional taxonomy query parameters
	 *
	 * @return WP_Query|false   Query object or false on failure
	 */
	/**
	 * Get Dutch month name
	 *
	 * @param int $month Month number (1-12)
	 * @return string Dutch month name (lowercase)
	 */
	public static function dutchMonth( int $month ): string {
		$months = [
			1  => 'januari',
			2  => 'februari',
			3  => 'maart',
			4  => 'april',
			5  => 'mei',
			6  => 'juni',
			7  => 'juli',
			8  => 'augustus',
			9  => 'september',
			10 => 'oktober',
			11 => 'november',
			12 => 'december',
		];
		return $months[ $month ] ?? '';
	}

	public static function get_latest_custom_posts( $post_type, $count, $meta_query = [], $tax_query = [], $timestamp_field = null ): WP_Query|bool {
		// Validate post type exists
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		$args = [
			'post_type'              => $post_type,
			'posts_per_page'         => absint( $count ),
			'post_status'            => 'publish',
			'no_found_rows'          => true,           // Skip pagination count query
			'update_post_meta_cache' => false,  // Skip meta cache if not needed
			'update_post_term_cache' => false,  // Skip term cache if not needed
		];

		// Add timestamp filtering and sorting if field is provided
		if ( $timestamp_field ) {
			$args['orderby']    = 'meta_value_num';
			$args['meta_key']   = $timestamp_field;
			$args['order']      = 'ASC';
			$args['meta_query'] = array_merge( [
				[
					'key'     => $timestamp_field,
					'value'   => current_time( 'timestamp' ),
					'compare' => '>='
				]
			], $meta_query );
		} else {
			// Default sorting by post date
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			if ( ! empty( $meta_query ) ) {
				$args['meta_query'] = $meta_query;
			}
		}

		// Add taxonomy query if provided
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		return new WP_Query( $args );
	}

	public static function setup_hubspot_metadata( $postID ) {

		$hubspotData = get_post_meta( $postID );
		$eventDate   = Helpers::getDateParts( new DateTime( $hubspotData['aanvangsdatum_en_tijd'][0], new DateTimeZone( 'Europe/Amsterdam' ) ), intval( $hubspotData['duur'][0] ) );
		$eventData   = array(
			'data_raw'      => $hubspotData,
			'timestamp'     => $eventDate,
			'timestamp_raw' => $hubspotData['aanvangsdatum_en_tijd'][0],
			'soort'         => get_post_type(),
			'type'          => strtolower( $hubspotData['type'][0] ),
			'locatie'       => $hubspotData['vestiging'][0],
			'duur'          => intval( $hubspotData['duur'][0] ),
			'intro'         => $hubspotData['intro_kort'][0] ?? '',
			'intro_lang'    => $hubspotData['intro_lang'][0] ?? '',
			'onderwerpen'   => $hubspotData['onderwerpen'][0] ?? '',
			'open'          => strtolower( $hubspotData['inschrijvingsstatus'][0] ) === 'open',
			'status'        => strtolower( $hubspotData['inschrijvingsstatus'][0] )
		);

		//inschrijvingsstatus = open, gesloten, vol en geannuleerd
		return $eventData;
	}

	/**
	 * Vestiging name aliases for Hubspot data mapping
	 *
	 * Maps Hubspot vestiging names to WordPress vestiging names when they differ.
	 * Key = Hubspot name (lowercase), Value = WordPress vestiging name (city part of title)
	 * Example: If Hubspot uses "Velp" but WordPress has "NOK Arnhem", add: 'velp' => 'Arnhem'
	 */
	private static array $vestiging_aliases = [
		// Add aliases here if Hubspot uses different names than WordPress vestiging titles
		// 'hubspot-name' => 'WordPress City Name',
	];

	/**
	 * Normalize vestiging name, applying aliases
	 *
	 * Converts Hubspot vestiging names to their canonical WordPress equivalents.
	 *
	 * @param string $city City name from voorlichting
	 * @return string Normalized city name
	 */
	public static function normalize_vestiging_name( string $city ): string {
		$lowercase = strtolower( trim( $city ) );
		return self::$vestiging_aliases[ $lowercase ] ?? $city;
	}

	/**
	 * Get all vestiging names that map to a canonical name
	 *
	 * Returns array of names including aliases that should match a vestiging.
	 * Used for meta queries to match both "Arnhem" and "Velp".
	 *
	 * @param string $city Canonical city name
	 * @return string[] Array of matching names
	 */
	public static function get_vestiging_name_variants( string $city ): array {
		$variants = [ $city ];
		$city_lower = strtolower( trim( $city ) );

		// Add any aliases that map to this city
		foreach ( self::$vestiging_aliases as $alias => $canonical ) {
			if ( strtolower( $canonical ) === $city_lower ) {
				$variants[] = ucfirst( $alias );
			}
		}

		return $variants;
	}

	/**
	 * Get vestiging post by city name from voorlichting
	 *
	 * Matches voorlichting's vestiging field (city name like "Amsterdam", "Den Haag")
	 * to the corresponding vestiging CPT post by sanitizing to slug format.
	 * Handles aliases (e.g., "Velp" -> "Arnhem").
	 *
	 * @param string $city City name from voorlichting vestiging field
	 * @return \WP_Post|null Vestiging post or null if not found
	 *
	 * @example
	 * $vestiging = Helpers::get_vestiging_by_city('Den Haag');
	 * // Returns vestiging post with slug 'den-haag'
	 *
	 * @example
	 * $vestiging = Helpers::get_vestiging_by_city('Velp');
	 * // Returns vestiging post with slug 'arnhem' (via alias)
	 */
	public static function get_vestiging_by_city( string $city ): ?\WP_Post {
		if ( empty( $city ) ) {
			return null;
		}

		// Apply alias mapping and sanitize to slug format
		$normalized = self::normalize_vestiging_name( $city );
		$slug = sanitize_title( $normalized );

		$posts = get_posts( [
			'post_type'      => 'vestiging',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		] );

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Count upcoming voorlichtingen for a vestiging
	 *
	 * Counts voorlichting posts where the vestiging field matches the given city
	 * and the start date/time is in the future.
	 *
	 * @param string $city City name to filter by (e.g., "Amsterdam")
	 * @param bool $open_only Only count voorlichtingen with status "open"
	 * @return int Count of upcoming voorlichtingen
	 *
	 * @example
	 * $count = Helpers::count_upcoming_voorlichtingen('Amsterdam');
	 * // Returns number of upcoming voorlichtingen in Amsterdam
	 */
	public static function count_upcoming_voorlichtingen( string $city, bool $open_only = false ): int {
		if ( empty( $city ) ) {
			return 0;
		}

		// Get all name variants (e.g., "Arnhem" also matches "Velp")
		$variants = self::get_vestiging_name_variants( $city );

		$meta_query = [
			[
				'key'     => 'aanvangsdatum_en_tijd',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
			[
				'key'     => 'vestiging',
				'value'   => $variants,
				'compare' => 'IN',
			],
		];

		if ( $open_only ) {
			$meta_query[] = [
				'key'     => 'inschrijvingsstatus',
				'value'   => 'open',
				'compare' => '=',
			];
		}

		$query = new \WP_Query( [
			'post_type'      => 'voorlichting',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => $meta_query,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		return $query->post_count;
	}

	/**
	 * Get upcoming voorlichtingen for a vestiging
	 *
	 * Retrieves voorlichting posts where the vestiging field matches the given city
	 * and the start date/time is in the future, ordered by date ascending.
	 *
	 * @param string $city City name to filter by (e.g., "Amsterdam")
	 * @param int $limit Maximum number of results
	 * @param bool $open_only Only include voorlichtingen with status "open"
	 * @return \WP_Post[] Array of voorlichting posts
	 *
	 * @example
	 * $voorlichtingen = Helpers::get_voorlichtingen_for_vestiging('Amsterdam', 3);
	 * foreach ($voorlichtingen as $post) {
	 *     $data = Helpers::setup_hubspot_metadata($post->ID);
	 * }
	 */
	public static function get_voorlichtingen_for_vestiging( string $city, int $limit = 6, bool $open_only = false ): array {
		if ( empty( $city ) ) {
			return [];
		}

		// Get all name variants (e.g., "Arnhem" also matches "Velp")
		$variants = self::get_vestiging_name_variants( $city );

		$meta_query = [
			[
				'key'     => 'aanvangsdatum_en_tijd',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
			[
				'key'     => 'vestiging',
				'value'   => $variants,
				'compare' => 'IN',
			],
		];

		if ( $open_only ) {
			$meta_query[] = [
				'key'     => 'inschrijvingsstatus',
				'value'   => 'open',
				'compare' => '=',
			];
		}

		return get_posts( [
			'post_type'      => 'voorlichting',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => $meta_query,
			'meta_key'       => 'aanvangsdatum_en_tijd',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		] );
	}

	/**
	 * Get vestiging address data for voorlichting display
	 *
	 * Attempts to get address from vestiging CPT post, falls back to
	 * Hubspot-synced vestiging_* fields if vestiging post not found.
	 *
	 * @param string $city City name from voorlichting
	 * @param array $hubspot_data Raw Hubspot metadata for fallback
	 * @return array{street: string, housenumber: string, postal_code: string, city: string, phone: string, email: string}
	 */
	public static function get_voorlichting_address( string $city, array $hubspot_data ): array {
		$vestiging = self::get_vestiging_by_city( $city );

		if ( $vestiging ) {
			// Use vestiging CPT data
			$meta = get_post_meta( $vestiging->ID );
			return [
				'street'      => $meta['_street'][0] ?? '',
				'housenumber' => $meta['_housenumber'][0] ?? '',
				'postal_code' => $meta['_postal_code'][0] ?? '',
				'city'        => $meta['_city'][0] ?? $city,
				'phone'       => $meta['_phone'][0] ?? '',
				'email'       => $meta['_email'][0] ?? '',
			];
		}

		// Fallback to Hubspot vestiging_* fields
		return [
			'street'      => $hubspot_data['vestiging_straat'][0] ?? '',
			'housenumber' => '',
			'postal_code' => $hubspot_data['vestiging_postcode'][0] ?? '',
			'city'        => $hubspot_data['vestiging_plaats'][0] ?? $city,
			'phone'       => $hubspot_data['vestiging_telefoonnummer'][0] ?? '',
			'email'       => $hubspot_data['vestiging__e_mail'][0] ?? '',
		];
	}

	public static function has_field( $field ): bool {
		global $page_part_fields;

		return key_exists( $field, $page_part_fields ) && $page_part_fields[ $field ] !== '';
	}

	/**
	 * Strip all quote-like characters for display within quoted text
	 *
	 * Removes straight quotes, curly quotes, HTML entities, grave accents,
	 * and other quote-like characters to prevent visual duplication when
	 * displaying content within HTML quote marks.
	 *
	 * @param string $text Text containing various quote characters
	 *
	 * @return string Text with all quotes removed
	 * @example Quote display
	 * <h2>"<?= esc_html(Helpers::strip_all_quotes($quote['quote'])) ?>"</h2>
	 *
	 */
	public static function strip_all_quotes( mixed $text ): string {
		// Handle non-string inputs
		if ( $text === null || $text === '' ) {
			return '';
		}

		// Cast to string for numeric types
		$text = (string) $text;

		// Convert HTML entities to actual characters
		$text = wp_specialchars_decode( $text, ENT_QUOTES );

		// Remove all quote-like characters
		$quotes = [
			'“',
			'”',
			'"',        // Straight double quote
			"'",        // Straight single quote
			"\u{2018}", // Left single curly quote
			"\u{2019}", // Right single curly quote
			"\u{201C}", // Left double curly quote
			"\u{201D}", // Right double curly quote
			"\u{201F}", // Double high-reversed-9 quotation mark
			"\u{201E}", // Double low-9 quotation mark
			"\u{2039}", // Single left-pointing angle quotation mark
			"\u{203A}", // Single right-pointing angle quotation mark
			"\u{00AB}", // Left-pointing double angle quotation mark
			"\u{00BB}", // Right-pointing double angle quotation mark
			'`',        // Grave accent
			"\u{00B4}", // Acute accent
			"\u{2032}", // Prime
			"\u{2033}", // Double prime
		];

		return str_replace( $quotes, '', trim($text) );
	}

	/**
	 * Format Dutch phone numbers for display
	 *
	 * Formats Dutch phone numbers with area code separation and grouping.
	 * Mirrors the JavaScript formatPhone function from util.format.mjs.
	 *
	 * Handles:
	 * - Mobile numbers (06)
	 * - Geographic area codes (010, 020, etc.)
	 * - International prefixes (+31, 0031)
	 * - Premium/toll-free numbers (0900, 0800)
	 *
	 * Output format:
	 * - National: `020 - 123 45 67`
	 * - International: `+31 20 - 123 45 67`
	 *
	 * @param string $phone Phone number to format
	 * @return string Formatted phone number or original input if invalid
	 *
	 * @example
	 * format_phone('0201234567');
	 * // Returns: '020 - 123 45 67'
	 *
	 * @example
	 * format_phone('+31201234567');
	 * // Returns: '+31 20 - 123 45 67'
	 *
	 * @example
	 * format_phone('0612345678');
	 * // Returns: '06 - 123 45 678'
	 */
	public static function format_phone( string $phone ): string {
		// Dutch area code prefixes ordered by length (longest first for greedy matching)
		$area_prefixes = [
			// 4-digit prefixes
			'0909', '0906', '0900', '0842', '0800', '0676',
			// 3-digit prefixes
			'0111', '0475', '0113', '0478', '0114', '0481', '0115', '0485',
			'0117', '0486', '0118', '0487', '0488', '0492', '0161', '0493',
			'0162', '0495', '0164', '0497', '0165', '0499', '0166', '0511',
			'0167', '0512', '0168', '0513', '0172', '0514', '0174', '0515',
			'0180', '0516', '0181', '0517', '0182', '0518', '0183', '0519',
			'0184', '0521', '0186', '0522', '0187', '0523', '0222', '0524',
			'0223', '0525', '0224', '0527', '0226', '0528', '0227', '0529',
			'0228', '0251', '0543', '0252', '0544', '0255', '0545', '0294',
			'0546', '0297', '0547', '0299', '0548', '0313', '0561', '0314',
			'0562', '0315', '0566', '0316', '0570', '0317', '0571', '0318',
			'0572', '0320', '0573', '0321', '0575', '0341', '0577', '0342',
			'0578', '0343', '0591', '0344', '0592', '0345', '0593', '0346',
			'0594', '0347', '0595', '0348', '0596', '0418', '0597', '0411',
			'0598', '0412', '0599', '0413', '0416',
			// 2-digit prefixes (common area codes)
			'010', '013', '015', '020', '023', '024', '026', '030', '033',
			'035', '036', '038', '040', '043', '045', '046', '050', '053',
			'055', '058', '070', '071', '072', '073', '074', '075', '076',
			'077', '078', '079',
			// Mobile
			'06'
		];

		$result = [];
		$country_prefix = '';

		// Normalize: strip whitespace and non-numeric characters except + at start
		$normalized = preg_replace( '/\s+/', '', $phone );
		$normalized = preg_replace( '/[^\d+]/', '', $normalized );

		// Extract international prefix if present
		if ( str_starts_with( $normalized, '00' ) || str_starts_with( $normalized, '+' ) ) {
			$country_prefix = str_starts_with( $normalized, '+' )
				? substr( $normalized, 0, 3 )   // +31
				: substr( $normalized, 0, 4 );  // 0031
			$normalized = '0' . substr( $normalized, strlen( $country_prefix ) );
			$result[] = $country_prefix;
		}

		// Find longest matching area code (greedy match)
		$area_code = substr( $normalized, 0, 3 ); // Default: first 3 digits
		for ( $length = 5; $length >= 2; $length-- ) {
			$candidate = substr( $normalized, 0, $length );
			if ( in_array( $candidate, $area_prefixes, true ) ) {
				$area_code = $candidate;
				break;
			}
		}

		// Extract subscriber number (digits after area code)
		$subscriber_number = substr( $normalized, strlen( $area_code ) );

		// Format area code (strip leading 0 if international)
		$formatted_area_code = $country_prefix
			? substr( $area_code, 1 )
			: $area_code;
		$result[] = $formatted_area_code . ' - ';

		// Group subscriber number: XX XX XX XX or XX XX XX
		$subscriber_length = strlen( $subscriber_number );
		if ( $subscriber_length > 7 ) {
			// 8+ digits: XXX XX XX XX or XX XX XX XX
			if ( preg_match( '/(\d{2,3})(\d{2})(\d{2})(\d{2})/', $subscriber_number, $matches ) ) {
				array_shift( $matches ); // Remove full match
				$result[] = implode( ' ', $matches );
			} else {
				$result[] = $subscriber_number;
			}
		} elseif ( $subscriber_length >= 6 ) {
			// 6-7 digits: XX XX XX or XXX XX XX
			if ( preg_match( '/(\d{2,3})(\d{2})(\d{2})/', $subscriber_number, $matches ) ) {
				array_shift( $matches ); // Remove full match
				$result[] = implode( ' ', $matches );
			} else {
				$result[] = $subscriber_number;
			}
		} else {
			// Too short, just append as-is
			$result[] = $subscriber_number;
		}

		$formatted = implode( ' ', $result );
		// Clean up multiple spaces
		$formatted = preg_replace( '/\s{2,}/', ' ', $formatted );

		// Return original if result looks invalid
		if ( strlen( $normalized ) < 10 || empty( $subscriber_number ) ) {
			return $phone;
		}

		return trim( $formatted );
	}

	/**
	 * Format opening hours into a single-line summary
	 *
	 * Creates a condensed summary like "ma-vr 8.30 - 17.00 uur" for use in cards and listings.
	 * Groups consecutive days with identical hours and handles non-consecutive days with "&".
	 *
	 * Output examples:
	 * - All weekdays same: "ma-vr 8.30 - 17.00 uur"
	 * - Wednesday closed: "ma-di & do-vr 8.30 - 17.00 uur"
	 * - Single day: "ma 9.00 - 17.00 uur"
	 * - Non-consecutive: "ma-wo & vr 8.30 - 17.00 uur"
	 *
	 * @param string $opening_hours_json JSON string of opening hours
	 * @return string Single-line summary or empty string if no valid hours
	 *
	 * @example format_opening_hours_summary('{"monday":[{"opens":"08:30","closes":"17:00"}],"tuesday":[{"opens":"08:30","closes":"17:00"}]}')
	 * Returns: "ma-di 8.30 - 17.00 uur"
	 */
	public static function format_opening_hours_summary( string $opening_hours_json ): string {
		if ( empty( $opening_hours_json ) || $opening_hours_json === '{}' ) {
			return '';
		}

		$hours = json_decode( $opening_hours_json, true );
		if ( ! is_array( $hours ) ) {
			return '';
		}

		$day_abbrevs = [
			'monday'    => 'ma',
			'tuesday'   => 'di',
			'wednesday' => 'wo',
			'thursday'  => 'do',
			'friday'    => 'vr',
		];
		$weekday_keys = array_keys( $day_abbrevs );

		// Get weekdays template if exists
		$weekdays_template = isset( $hours['weekdays'] ) && ! empty( $hours['weekdays'] ) ? $hours['weekdays'][0] : null;

		// Build array of [day_key => "opens-closes" or null if closed]
		$day_times = [];
		foreach ( $weekday_keys as $day_key ) {
			$day_hours = isset( $hours[ $day_key ] ) && ! empty( $hours[ $day_key ] ) ? $hours[ $day_key ] : [];

			$time_block = null;
			if ( count( $day_hours ) > 0 ) {
				$time_block = $day_hours[0];
				if ( isset( $time_block['closed'] ) && $time_block['closed'] === true ) {
					$day_times[ $day_key ] = null; // explicitly closed
					continue;
				}
			} elseif ( $weekdays_template ) {
				$time_block = $weekdays_template;
			}

			if ( $time_block && isset( $time_block['opens'] ) && isset( $time_block['closes'] ) ) {
				$day_times[ $day_key ] = $time_block['opens'] . '-' . $time_block['closes'];
			} else {
				$day_times[ $day_key ] = null;
			}
		}

		// Group consecutive days with same hours
		$groups = [];
		$current_group = null;
		$current_time = null;

		foreach ( $weekday_keys as $index => $day_key ) {
			$time = $day_times[ $day_key ];

			if ( $time === null ) {
				// Day is closed - end current group if any
				if ( $current_group !== null ) {
					$groups[] = [ 'days' => $current_group, 'time' => $current_time ];
					$current_group = null;
					$current_time = null;
				}
				continue;
			}

			if ( $current_time === $time ) {
				// Same hours, extend group
				$current_group[] = $day_key;
			} else {
				// Different hours - save previous group and start new one
				if ( $current_group !== null ) {
					$groups[] = [ 'days' => $current_group, 'time' => $current_time ];
				}
				$current_group = [ $day_key ];
				$current_time = $time;
			}
		}

		// Don't forget the last group
		if ( $current_group !== null ) {
			$groups[] = [ 'days' => $current_group, 'time' => $current_time ];
		}

		if ( empty( $groups ) ) {
			return '';
		}

		// Merge groups with same hours (for non-consecutive days)
		$merged = [];
		foreach ( $groups as $group ) {
			$found = false;
			foreach ( $merged as &$m ) {
				if ( $m['time'] === $group['time'] ) {
					$m['days'] = array_merge( $m['days'], $group['days'] );
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$merged[] = $group;
			}
		}

		// Format output - find most common time (for simplicity, use first/largest group)
		usort( $merged, fn( $a, $b ) => count( $b['days'] ) - count( $a['days'] ) );
		$primary = $merged[0];

		// Format day ranges from array of day keys
		$format_day_range = function( array $days ) use ( $day_abbrevs, $weekday_keys ): string {
			// Find consecutive sequences
			$sequences = [];
			$current_seq = [];

			foreach ( $weekday_keys as $day_key ) {
				if ( in_array( $day_key, $days, true ) ) {
					$current_seq[] = $day_key;
				} elseif ( ! empty( $current_seq ) ) {
					$sequences[] = $current_seq;
					$current_seq = [];
				}
			}
			if ( ! empty( $current_seq ) ) {
				$sequences[] = $current_seq;
			}

			// Format each sequence
			$parts = [];
			foreach ( $sequences as $seq ) {
				if ( count( $seq ) === 1 ) {
					$parts[] = $day_abbrevs[ $seq[0] ];
				} else {
					$parts[] = $day_abbrevs[ $seq[0] ] . '-' . $day_abbrevs[ end( $seq ) ];
				}
			}

			return implode( ' & ', $parts );
		};

		// Format time (08:30 -> 8.30)
		$format_time = function( string $time ): string {
			$parts = explode( ':', $time );
			$hour = ltrim( $parts[0], '0' ) ?: '0';
			$minute = $parts[1] ?? '00';
			return $hour . '.' . $minute;
		};

		$day_part = $format_day_range( $primary['days'] );
		list( $opens, $closes ) = explode( '-', $primary['time'] );

		return sprintf(
			'%s %s - %s uur',
			$day_part,
			$format_time( $opens ),
			$format_time( $closes )
		);
	}

	/**
	 * Format opening hours JSON into readable HTML
	 *
	 * Converts opening hours JSON structure into formatted HTML output
	 * with Dutch day names. Respects weekdays template with individual day overrides.
	 *
	 * Displays Monday-Friday only (weekend hours are not supported in this implementation).
	 *
	 * Logic:
	 * - For Mon-Fri: Uses individual day hours if set, otherwise falls back to "weekdays" hours
	 * - Shows "Gesloten" if no hours are available or day is explicitly marked as closed
	 *
	 * @param string $opening_hours_json JSON string of opening hours
	 * @return string Formatted HTML output with Monday-Friday hours
	 *
	 * @example format_opening_hours('{"weekdays":[{"opens":"09:00","closes":"17:00"}],"monday":[]}')
	 * Returns: <p>Maandag: 09:00 - 17:00</p> (uses weekdays fallback)
	 *
	 * @example format_opening_hours('{"weekdays":[{"opens":"09:00","closes":"17:00"}],"wednesday":[{"closed":true}]}')
	 * Returns: Monday-Friday with Wednesday showing "Gesloten"
	 */
	public static function format_opening_hours( string $opening_hours_json ): string {
		if ( empty( $opening_hours_json ) || $opening_hours_json === '{}' ) {
			return '';
		}

		$hours = json_decode( $opening_hours_json, true );
		if ( ! is_array( $hours ) ) {
			return '';
		}

		$day_labels = [
			'monday'    => 'Maandag',
			'tuesday'   => 'Dinsdag',
			'wednesday' => 'Woensdag',
			'thursday'  => 'Donderdag',
			'friday'    => 'Vrijdag',
		];

		$weekdays_template = isset( $hours['weekdays'] ) && ! empty( $hours['weekdays'] ) ? $hours['weekdays'][0] : null;
		$weekday_keys = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ];

		$output = '';
		foreach ( $day_labels as $day_key => $day_label ) {
			$day_hours = isset( $hours[ $day_key ] ) && ! empty( $hours[ $day_key ] ) ? $hours[ $day_key ] : [];

			// Determine which hours to use
			$time_block = null;
			$is_explicitly_closed = false;

			if ( count( $day_hours ) > 0 ) {
				// Individual day hours set - check if explicitly closed
				$time_block = $day_hours[0];
				if ( isset( $time_block['closed'] ) && $time_block['closed'] === true ) {
					$is_explicitly_closed = true;
				}
			} elseif ( in_array( $day_key, $weekday_keys, true ) && $weekdays_template ) {
				// Weekday without individual hours - use weekdays template
				$time_block = $weekdays_template;
			}

			// Output the day
			if ( $is_explicitly_closed ) {
				// Explicitly marked as closed
				$output .= sprintf(
					'<p class="nok-layout-flex-row space-between"><span>%s</span> <span style="color: #999;">Gesloten</span></p>',
					esc_html( $day_label )
				);
			} elseif ( $time_block && isset( $time_block['opens'] ) && isset( $time_block['closes'] ) ) {
				// Has opening hours
				$output .= sprintf(
					'<p class="nok-layout-flex-row space-between"><span>%s</span> <span>%s - %s uur</span></p>',
					esc_html( $day_label ),
					esc_html( $time_block['opens'] ),
					esc_html( $time_block['closes'] )
				);
			} else {
				// No hours available
				$output .= sprintf(
					'<p class="nok-layout-flex-row space-between"><span>%s</span> <span style="color: #999;">Gesloten</span></p>',
					esc_html( $day_label )
				);
			}
		}

		return $output;
	}

	/**
	 * Render breadcrumb navigation
	 *
	 * Outputs semantic breadcrumb navigation using Yoast SEO breadcrumbs
	 * with fallback to simple Home link if Yoast is not available.
	 *
	 * Breadcrumb structure:
	 * - Wrapped in <nav class="nok-breadcrumbs"> with aria-label
	 * - Uses Yoast's breadcrumb_output filter for customization
	 * - Includes structured data (via Yoast)
	 *
	 * @param bool $echo Whether to echo output (true) or return (false). Default true.
	 * @param string $wrapper_class Additional CSS classes for nav wrapper. Default ''.
	 * @return string|null HTML output when $echo is false, null when echoing
	 *
	 * @example Basic usage in template
	 * Helpers::render_breadcrumbs();
	 *
	 * @example Return without echoing
	 * $breadcrumbs_html = Helpers::render_breadcrumbs(false);
	 *
	 * @example With custom class
	 * Helpers::render_breadcrumbs(true, 'nok-mb-3');
	 */
	public static function render_breadcrumbs(bool $echo = true, string $wrapper_class = ''): ?string {
		$home_icon = Assets::getIcon('ui_home', 'nok-text-lightblue larger');
		// Check if Yoast SEO breadcrumbs are available
		if (function_exists('yoast_breadcrumb')) {
			/** @noinspection PhpUndefinedFunctionInspection */
			$breadcrumb_html = yoast_breadcrumb('', '', false);

			if (!empty($breadcrumb_html)) {
				// Inject home icon into the first breadcrumb link via DOM manipulation
				$breadcrumb_html = self::inject_home_icon_in_breadcrumb_html($breadcrumb_html, $home_icon);

				$classes = 'nok-breadcrumbs' . ($wrapper_class ? ' ' . esc_attr($wrapper_class) : '');
				$output = sprintf(
					'<nav class="%s" aria-label="%s">%s</nav>',
					$classes,
					esc_attr__('Breadcrumb', THEME_TEXT_DOMAIN),
					$breadcrumb_html
				);

				if ($echo) {
					echo $output;
					return null;
				}
				return $output;
			}
		}

		// Fallback: Simple home link with icon (matches Yoast structure)
		$classes = 'nok-breadcrumbs nok-breadcrumbs--fallback' . ($wrapper_class ? ' ' . esc_attr($wrapper_class) : '');
		$output = sprintf(
			'<nav class="%s" aria-label="%s"><span><span><a href="%s">%s<span class="sr-only">%s</span></a></span></span></nav>',
			$classes,
			esc_attr__('Breadcrumb', THEME_TEXT_DOMAIN),
			esc_url(home_url('/')),
			$home_icon,
			esc_html__('Home', THEME_TEXT_DOMAIN)
		);

		if ($echo) {
			echo $output;
			return null;
		}
		return $output;
	}

	/**
	 * Inject home icon into Yoast breadcrumb HTML
	 *
	 * Uses DOMDocument to safely inject the home icon into the first breadcrumb link
	 * (home link) as the first child element, and wraps the "Home" text in a
	 * visually-hidden span for accessibility.
	 *
	 * @param string $breadcrumb_html Yoast-generated breadcrumb HTML
	 * @param string $home_icon SVG icon HTML
	 * @return string Modified breadcrumb HTML with icon
	 */
	private static function inject_home_icon_in_breadcrumb_html(string $breadcrumb_html, string $home_icon): string {
		// Wrap in a container for parsing with proper UTF-8 encoding
		$html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $breadcrumb_html . '</body></html>';

		$dom = new \DOMDocument();
		$dom->encoding = 'UTF-8';

		// Suppress warnings for malformed HTML and load with UTF-8 encoding
		@$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// Find the first <a> tag (home link)
		$links = $dom->getElementsByTagName('a');
		if ($links->length > 0) {
			$first_link = $links->item(0);

			// Create a temporary container to parse the icon SVG
			$icon_dom = new \DOMDocument();
			$icon_dom->encoding = 'UTF-8';
			@$icon_dom->loadHTML(mb_convert_encoding('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $home_icon . '</body></html>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

			$svg_element = $icon_dom->getElementsByTagName('svg')->item(0);

			if ($svg_element) {
				// Import the icon node into our main DOM
				$icon_node = $dom->importNode($svg_element, true);

				// Insert icon as the first child of the home link
				$first_link->insertBefore($icon_node, $first_link->firstChild);
			}

			// Wrap text nodes in a visually-hidden span for accessibility
			$text_nodes = [];
			foreach ($first_link->childNodes as $child) {
				if ($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue) !== '') {
					$text_nodes[] = $child;
				}
			}

			foreach ($text_nodes as $text_node) {
				$hidden_span = $dom->createElement('span');
				$hidden_span->setAttribute('class', 'sr-only');
				$text_node->parentNode->replaceChild($hidden_span, $text_node);
				$hidden_span->appendChild($text_node);
			}
		}

		// Extract just the breadcrumb content
		$body = $dom->getElementsByTagName('body')->item(0);
		$result = '';
		foreach ($body->childNodes as $node) {
			$result .= $dom->saveHTML($node);
		}

		return $result;
	}

	/**
	 * Get trimmed excerpt with custom word count
	 *
	 * Uses WordPress's excerpt generation with wp_trim_words for
	 * clean text truncation. Falls back to auto-generated excerpt
	 * from post content if manual excerpt is empty.
	 *
	 * @example Default 55 words
	 * $excerpt = Helpers::get_excerpt($post_id);
	 *
	 * @example Custom length
	 * $short = Helpers::get_excerpt($post_id, 20);
	 *
	 * @param int|\WP_Post|null $post Post ID, object, or null for current post
	 * @param int $word_count Number of words to trim to
	 * @return string HTML-escaped excerpt
	 */
	public static function get_excerpt(int|\WP_Post|null $post = null, int $word_count = 55): string {
		$post = get_post($post);

		if (!$post) {
			return '';
		}

		// Get excerpt or generate from content
		$excerpt = $post->post_excerpt ?: $post->post_content;

		// Strip shortcodes and tags, then trim
		$excerpt = strip_shortcodes($excerpt);
		$excerpt = wp_strip_all_tags($excerpt);
		$excerpt = self::strip_all_quotes( $excerpt );

		return wp_trim_words($excerpt, $word_count, '...');
	}

	/**
	 * Extract quote data from experience posts
	 *
	 * Extracts blockquotes from post content. Randomly selects one
	 * blockquote if multiple exist. Falls back to post title if no
	 * blockquotes found.
	 *
	 * Structure matches nok-scrollable-quote-block template requirements:
	 * - quote: Main quote text (stripped of quote characters)
	 * - excerpt: Short summary with ellipsis
	 * - name: Patient name or 'Anonieme patiënt'
	 * - subnaam: Optional subtitle (location, date, etc.)
	 * - link_url: Permalink to full post
	 * - image_url: Featured image URL or fallback
	 *
	 * @example Single post
	 * $quotes = Helpers::get_quotes_from_experience_posts([123, 456]);
	 *
	 * @example With empty array
	 * $quotes = Helpers::get_quotes_from_experience_posts([]);
	 * // Returns []
	 *
	 * @param int[] $post_ids Array of post IDs from 'ervaringen' category
	 * @return array[] Quote data arrays with standardized keys
	 */
	public static function get_quotes_from_experience_posts( array $post_ids ): array {
		$quote_items = [];

		foreach ( $post_ids as $post_id ) {
			$post      = get_post( $post_id );
			$post_meta = get_post_meta( $post_id );

			// Extract blockquotes from content
			$blockquotes = self::extract_blockquotes_from_content( $post->post_content );

			// Select quote: random blockquote or fall back to title
			$quote_text = !empty($blockquotes)
				? $blockquotes[array_rand($blockquotes)]
				: html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');

			// Build excerpt with fallback chain
			$excerpt = self::get_excerpt( $post_id, 30 );
			if ( isset( $post_meta['_highlighted_excerpt'] ) ) {
				$trimmed = rtrim($post_meta['_highlighted_excerpt'][0], '.' );
				if ($trimmed !== '') {
					$excerpt = self::strip_all_quotes( $trimmed ) . '...';
				}
			}

			$quote_item = [
				'quote'     => self::strip_all_quotes( $quote_text ),
				'excerpt'   => $excerpt,
				'name'      => $post_meta['_naam_patient'][0] ?? 'Anonieme patiënt',
				'subnaam'   => $post_meta['_subnaam_patient'][0] ?? '',
				'link_url'  => get_permalink( $post_id ),
				'image_url' => self::get_featured_image_uri( $post, 'thumbnail' ),
			];

			$quote_items[] = $quote_item;
		}

		return $quote_items;
	}

	/**
	 * Extract text content from all blockquotes in HTML
	 *
	 * Parses HTML content and returns array of blockquote text content,
	 * stripping all HTML tags and preserving only the text.
	 *
	 * @param string $content HTML content potentially containing blockquotes
	 * @return string[] Array of blockquote text content
	 */
	private static function extract_blockquotes_from_content( string $content ): array {
		if ( empty( $content ) ) {
			return [];
		}

		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$blockquotes = $dom->getElementsByTagName( 'blockquote' );
		$quotes      = [];

		foreach ( $blockquotes as $blockquote ) {
			$text = trim( $blockquote->textContent );
			if ( !empty( $text ) ) {
				$quotes[] = $text;
			}
		}

		return $quotes;
	}

	/**
	 * Build complete quote collection with optional random padding
	 *
	 * Merges explicit posts, custom quotes, and random padding into
	 * unified collection. Shuffles when custom quotes or random padding
	 * are present to prevent predictable ordering.
	 *
	 * Processing order:
	 * 1. Extract quotes from explicitly selected posts
	 * 2. Merge with custom manual quotes
	 * 3. If enabled and below minimum, pad with random posts
	 * 4. Shuffle if custom content or random padding added
	 *
	 * Random posts exclude explicitly selected posts and any posts
	 * referenced in custom quotes to prevent duplicates.
	 *
	 * @example Basic usage with explicit posts
	 * $quotes = Helpers::build_quote_collection(
	 *     explicit_posts: [123, 456]
	 * );
	 *
	 * @example With custom quotes and padding
	 * $quotes = Helpers::build_quote_collection(
	 *     explicit_posts: [123],
	 *     custom_quotes: [['quote' => 'Great care', 'name' => 'Jan']],
	 *     pad_with_random: true,
	 *     minimum_count: 5
	 * );
	 *
	 * @example All parameters
	 * $quotes = Helpers::build_quote_collection(
	 *     explicit_posts: $context->quote_posts->json(),
	 *     custom_quotes: $context->quote_items->json(),
	 *     pad_with_random: $context->random_quotes->isTrue(),
	 *     minimum_count: 5
	 * );
	 *
	 * @param int[] $explicit_posts Post IDs explicitly selected
	 * @param array[] $custom_quotes Manual quote arrays with quote/name keys
	 * @param bool $pad_with_random Fill to minimum with random posts
	 * @param int $minimum_count Target quote count for random padding
	 * @return array[] Complete quote collection ready for rendering
	 */
	public static function build_quote_collection(
		array $explicit_posts,
		array $custom_quotes = [],
		bool $pad_with_random = false,
		int $minimum_count = 5,
		array $not = [],
	): array {
		// Extract quotes from explicitly selected posts
		$quote_data = self::get_quotes_from_experience_posts( $explicit_posts );

		// Merge with custom quotes
		$quote_data = array_merge( $quote_data, $custom_quotes );

		// Pad with random quotes if enabled and below minimum
		if ( $pad_with_random && count( $quote_data ) < $minimum_count ) {
			$needed = $minimum_count - count( $quote_data );

			// Build exclusion list from explicit posts and custom quotes
			$excluded_ids = array_merge($explicit_posts, $not);
			foreach ( $custom_quotes as $custom ) {
				if ( isset( $custom['post_id'] ) ) {
					$excluded_ids[] = $custom['post_id'];
				}
			}

			$random_post_ids = get_posts( [
				'post_type'      => 'post',
				'category_name'  => 'ervaringen',
				'posts_per_page' => $needed,
				'fields'         => 'ids',
				'post__not_in'   => array_filter( $excluded_ids ),
				'orderby'        => 'rand'
			] );

			if ( ! empty( $random_post_ids ) ) {
				$random_quotes = self::get_quotes_from_experience_posts( $random_post_ids );
				$quote_data    = array_merge( $quote_data, $random_quotes );
			}
		}

		// Shuffle if custom quotes or random padding added
		if ( ! empty( $custom_quotes ) || $pad_with_random ) {
			shuffle( $quote_data );
		}

		return $quote_data;
	}

	/**
	 * Echo first paragraph from post content
	 *
	 * @param string $extra_class Optional CSS class to add to the paragraph
	 */
	public static function the_content_first_paragraph( string $extra_class = '' ): void {
		$html = self::get_content_first_paragraph();

		if ( $extra_class !== '' && $html !== '' ) {
			// Add class to existing class attribute or create one
			if ( preg_match( '/<p\s+class="([^"]*)"/', $html, $matches ) ) {
				$html = preg_replace(
					'/<p\s+class="([^"]*)"/',
					'<p class="$1 ' . esc_attr( $extra_class ) . '"',
					$html,
					1
				);
			} else {
				$html = preg_replace( '/<p(\s|>)/', '<p class="' . esc_attr( $extra_class ) . '"$1', $html, 1 );
			}
		}

		echo $html;
	}

	/**
	 * Echo remaining content after first paragraph
	 */
	public static function the_content_rest(): void {
		echo self::get_content_rest();
	}

	/**
	 * Echo remaining content with organically distributed injected HTML
	 *
	 * Distributes injections evenly throughout the content based on total
	 * block count. With 3 injections in 10 blocks, places at ~25%, 50%, 75%.
	 *
	 * @param string|array<string> $injections HTML to inject (single string or array)
	 *
	 * @example Single injection (placed at ~50%)
	 * Helpers::the_content_rest_with_injections('<aside>Ad</aside>');
	 *
	 * @example Multiple injections (evenly distributed)
	 * Helpers::the_content_rest_with_injections([
	 *     '<aside>Ad 1</aside>',
	 *     '<div>CTA</div>',
	 *     '<aside>Ad 2</aside>'
	 * ]);
	 */
	public static function the_content_rest_with_injections(string|array $injections = []): void {
		echo self::get_content_rest_with_injections($injections);
	}

	/**
	 * Get first paragraph from post content
	 *
	 * Applies all WordPress content filters after splitting at first
	 * paragraph boundary in raw content. Cached per post.
	 *
	 * @return string First paragraph HTML or empty string
	 */
	public static function get_content_first_paragraph(): string {
		return self::split_content_cache(get_the_ID())['first'] ?? '';
	}

	/**
	 * Get remaining content after first paragraph
	 *
	 * @return string Remaining content HTML or empty string
	 */
	public static function get_content_rest(): string {
		return self::split_content_cache(get_the_ID())['rest'] ?? '';
	}

	/**
	 * Split content and populate static cache
	 *
	 * Priority logic:
	 * 1. If highlighted_excerpt custom field is set, use it as 'first'
	 *    and full content as 'rest'
	 * 2. Otherwise, split at first paragraph boundary in raw content
	 *
	 * Applies WordPress filters to each section independently to keep
	 * shortcodes/blocks intact and respect semantic structure.
	 *
	 * @param int $post_id Post ID to process
	 * @return array{first: string, rest: string} Split content parts
	 */
	private static function split_content_cache(int $post_id): array {
		static $cache = [];

		if (isset($cache[$post_id])) {
			return $cache[$post_id];
		}

		// Check for highlighted_excerpt custom field
		$highlighted_excerpt = get_post_meta($post_id, '_highlighted_excerpt', true);

		if (!empty($highlighted_excerpt)) {
			// Use highlighted excerpt as first, full content as rest
			$full_content = apply_filters('the_content', get_post_field('post_content', $post_id));
			$cache[$post_id] = [
				'first' => apply_filters('the_content', $highlighted_excerpt),
				'rest'  => $full_content
			];
			return $cache[$post_id];
		}

		// Get raw content before filters
		$raw = get_post_field('post_content', $post_id);

		// Find first paragraph break (double newline)
		$pattern = '/\n\s*\n/';
		$parts = preg_split($pattern, $raw, 2);

		if (count($parts) === 1) {
			// No paragraph break - everything is "first"
			$cache[$post_id] = [
				'first' => apply_filters('the_content', $parts[0]),
				'rest'  => ''
			];
			return $cache[$post_id];
		}

		// Apply filters to each part independently
		$cache[$post_id] = [
			'first' => apply_filters('the_content', $parts[0]),
			'rest'  => apply_filters('the_content', $parts[1])
		];

		return $cache[$post_id];
	}

	/**
	 * Get remaining content with organically distributed injected HTML
	 *
	 * Automatically calculates optimal injection positions based on content
	 * length. Distributes N injections into N+1 equal segments.
	 *
	 * @param string|array<string> $injections HTML to inject (single string or array)
	 * @return string Content with distributed injections
	 */
	public static function get_content_rest_with_injections(string|array $injections = []): string {
		$content = self::get_content_rest();

		if (empty($content) || empty($injections)) {
			return $content;
		}

		// Normalize to array
		if (is_string($injections)) {
			$injections = [$injections];
		}

		// Count total block elements in content
		$block_count = self::count_block_elements($content);

		if ($block_count === 0) {
			// No blocks found, append all injections at end
			return $content . implode('', $injections);
		}

		// Calculate positions for even distribution
		$positions = self::calculate_distribution_positions(count($injections), $block_count);

		// Build position => HTML map
		$position_map = array_combine($positions, $injections);

		// Sort descending and inject
		krsort($position_map, SORT_NUMERIC);

		foreach ($position_map as $position => $html) {
			$content = self::inject_after_block_element($content, $html, $position);
		}

		return $content;
	}

	/**
	 * Count root-level block elements in HTML content
	 *
	 * Only counts direct children, ignores nested blocks.
	 *
	 * @param string $content HTML content
	 * @return int Number of root block elements
	 */
	private static function count_block_elements(string $content): int {
		$block_tags = 'p|div|figure|blockquote|ul|ol|h[1-6]|pre|table|section|article|aside|header|footer|nav|main';

		// Match all opening and closing tags
		$pattern = '/<(\/)?('. $block_tags . ')(?:\s[^>]*)?\s*>/i';

		preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

		$depth = 0;
		$root_count = 0;

		foreach ($matches as $match) {
			$is_closing = !empty($match[1]); // Check for closing slash

			if ($is_closing) {
				if ($depth === 1) {  // Changed from 0 to 1
					// Root-level closing tag
					$root_count++;
				}
				$depth = max(0, $depth - 1);
			} else {
				// Opening tag
				$depth++;
			}
		}

		return $root_count;
	}


	/**
	 * Calculate evenly distributed positions for injections
	 *
	 * Divides content into N+1 segments and places injections at segment boundaries.
	 * With 3 injections in 10 blocks: places at blocks 2, 5, 7 (~25%, 50%, 75%).
	 *
	 * @param int $injection_count Number of items to inject
	 * @param int $total_blocks Total block elements available
	 * @return int[] Positions for injections (1-based)
	 */
	private static function calculate_distribution_positions(int $injection_count, int $total_blocks): array {
		$positions = [];
		$step = $total_blocks / ($injection_count + 1);

		for ($i = 1; $i <= $injection_count; $i++) {
			$positions[] = (int) round($i * $step);
		}

		return $positions;
	}

	/**
	 * Inject HTML after Nth root-level block element
	 *
	 * Counts only direct children, ignoring nested blocks.
	 * Appends if target position exceeds available root blocks.
	 *
	 * @param string $content HTML content
	 * @param string $injection HTML to inject
	 * @param int $after_position Target root block number (1-based)
	 * @return string Modified content
	 */
	private static function inject_after_block_element(string $content, string $injection, int $after_position): string {
		$block_tags = 'p|div|figure|blockquote|ul|ol|h[1-6]|pre|table|section|article|aside|header|footer|nav|main';

		// Match all opening and closing tags with their positions
		$pattern = '/<(\/)?('. $block_tags . ')(?:\s[^>]*)?\s*>/i';

		preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$depth = 0;
		$root_count = 0;

		foreach ($matches as $match_data) {
			$full_match = $match_data[0][0]; // Full matched string
			$position = $match_data[0][1];   // Position in content
			$is_closing = !empty($match_data[1][0]); // Check for closing slash

			if ($is_closing) {
				if ($depth === 1) {  // Changed from 0 to 1
					// Root-level closing tag
					$root_count++;

					if ($root_count === $after_position) {
						// Insert after this root closing tag
						$insert_pos = (int)($position + strlen($full_match));
						$before = substr($content, 0, $insert_pos);
						$after = substr($content, $insert_pos);
						return $before . $injection . $after;
					}
				}
				$depth = max(0, $depth - 1);
			} else {
				// Opening tag
				$depth++;
			}
		}

		// Not enough root blocks, append at end
		return $content . $injection;
	}
}



/**
 * Join an array of items into a comma‑separated list,
 * using a localized “and” before the last item.
 *
 * @param string[] $items
 *
 * @return string
 */
function oxford_list( array $items ): string {
	$count = count( $items );
	if ( $count === 0 ) {
		return '';
	}
	if ( $count === 1 ) {
		return esc_html( $items[0] );
	}
	$last = array_pop( $items );

	return esc_html( implode( ', ', $items ) )
	       . ' ' . __( 'en', 'mytheme' ) . ' '
	       . esc_html( $last );
}

function get_csp( $nonce ): string {
	require_once( 'libs/hnl.cspGenerator.php' );

	return constructCSP(
		array(
			'default-src' => array(
				'self',
				'data',
				'unsafe-eval',
				'hosts' => array(
					'https:'
				)
			),
			'script-src'  => array(
				'self',
				'unsafe-inline',
				'unsafe-eval',
				'unsafe-hashes',
				//'strict-dynamic'
			),
			'style-src'   => array(
				'self',
				'data',
				'unsafe-inline',
				'unsafe-hashes'
			),
			'img-src'     => array(
				'self',
				'data'
			),
			'font-src'    => array(
				'self',
				'data'
			),
			'connect-src' => array(
				'self'
			),
			'frame-src'   => array(
				'self'
			),
			'base-uri'    => array(
				'self'
			)
		),
		array(
			//'\'nonce-'.$nonce.'\''  =>  array ( 'script-src' ),
			'*.obesitaskliniek.nl'   => array( 'script-src', 'style-src', 'img-src', 'font-src', 'frame-src' ),
			'code.hnldesign.nl'      => array( 'script-src', 'style-src' ),
			'cdn.jsdelivr.net'       => array( 'script-src', 'style-src' ),
			'connect.facebook.net'   => array( 'script-src' ),
			'*.facebook.com'         => array( 'img-src', 'frame-src', 'connect-src' ),
			'*.youtube.com'          => array( 'script-src', 'frame-src', 'connect-src' ),
			'*.googleapis.com'       => array( 'script-src', 'style-src', 'img-src', 'font-src', 'frame-src' ),
			'*.googleoptimize.com'   => array( 'script-src' ),
			'*.google.com'           => array( 'script-src', 'img-src', 'font-src', 'frame-src' ),
			'*.google.nl'            => array( 'img-src' ),
			'*.gstatic.com'          => array( 'script-src', 'img-src', 'font-src', 'frame-src' ),
			'*.google-analytics.com' => array( 'script-src', 'img-src', 'connect-src' ),
			'*.googletagmanager.com' => array( 'script-src', 'img-src', 'frame-src' ),
			'*.googleadservices.com' => array( 'script-src' ),
			'*.ubembed.com'          => array( 'script-src', 'frame-src' ),
			'*.g.doubleclick.net'    => array( 'script-src', 'img-src', 'connect-src' ),
			'*.hotjar.com'           => array( 'script-src', 'connect-src', 'frame-src' ),
			'*.omnivr.nl'            => array( 'frame-src' ),
			'sentry.io'              => array( 'connect-src' ),
			//hubspot
			'*.hs-scripts.com'       => array( 'script-src' ),
			'*.hs-banner.com'        => array( 'script-src' ),
			'*.hs-analytics.net'     => array( 'script-src' ),
			'*.hscollectedforms.net' => array( 'script-src' ),
			'*.hubspot.com'          => array( 'connect-src', 'img-src' ),
			'*.hsforms.com'          => array( 'img-src' ),
		)
	);
}

/**
 * Send additional security headers
 *
 * Includes CSP in Report-Only mode for violation auditing without breaking functionality.
 *
 * ARCHITECTURAL DECISION: CSP Report-Only Mode
 * ============================================
 * The CSP header uses Content-Security-Policy-Report-Only instead of enforcing mode because:
 *
 * 1. WordPress ecosystem incompatibility: Gutenberg, many plugins, and WordPress core
 *    rely on inline scripts and eval(). Enforcing CSP would break core functionality.
 *
 * 2. The current CSP includes 'unsafe-inline' and 'unsafe-eval' which essentially
 *    defeats the purpose of CSP for XSS protection anyway.
 *
 * 3. Report-Only mode allows us to:
 *    - Monitor potential violations in browser console
 *    - Audit third-party script behavior
 *    - Prepare for stricter CSP in future WordPress versions
 *
 * 4. XSS protection is achieved through proper output escaping (FieldContext, esc_*
 *    functions) rather than CSP.
 *
 * To enable violation reporting to a server endpoint, add report-uri or report-to
 * directives to the CSP configuration in get_csp().
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy-Report-Only
 *
 * @param string $nonce Security nonce (currently unused, reserved for future nonce-based CSP)
 * @return void
 */
function do_extra_header_data( $nonce = '' ): void {
	if ( defined( 'is_admin' ) && ! is_admin() ) {
		// Report-Only mode: logs violations without blocking - see docblock for rationale
		header( 'Content-Security-Policy-Report-Only:' . get_csp( $nonce ) );
	}
	header( 'X-Frame-Options: Allow' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-XSS-Protection: 1; mode=block' );
	header( 'Strict-Transport-Security: max-age=631138519; includeSubDomains' );
	header( 'Connection: keep-alive' );
}

//print an element from a multidimensional array
function array_to_element( $type, $attributes, $innerHTML = '', $pre = '' ): string {
	$mapper = function ( $v, $k ) {
		if ( is_bool( $v ) && $v ) {
			return $k;
		} else {
			return $k . '="' . $v . '"';
		}
	};

	return $pre . '<' . $type . ' ' . implode( ' ', array_map( $mapper, $attributes, array_keys( $attributes ) ) ) . '>' . $innerHTML . ( $innerHTML ? "\n" . $pre : '' ) . '</' . $type . '>' . "\n";
}
