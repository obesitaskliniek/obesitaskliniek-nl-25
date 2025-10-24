<?php
// inc/Helpers.php
namespace NOK2025\V1;


use DateInterval;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use WP_Query;

class Helpers {
	public static function makeRandomString( $bits = 256 ): string {
		#generates nonce (for Google Tag Manager etc)
		$bytes  = ceil( $bits / 8 );
		$return = '';
		for ( $i = 0; $i < $bytes; $i ++ ) {
			$return .= chr( mt_rand( 0, 255 ) );
		}

		return $return;
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

	public static function get_featured_image($class = null): string {
		if ( has_post_thumbnail() ) {
			// Output <img> with srcset, sizes, width/height, alt, AND loading="lazy"
			$featuredImage = wp_get_attachment_image(
				get_post_thumbnail_id(),  // attachment ID
				'large',                   // size slug: 'thumbnail', 'medium', 'large', 'full', or your custom size
				false,                    // icon? false = normal image
				[
					'loading'  => 'eager', //eager since we are at the top of the page anyway
					'decoding' => 'async', // async decoding for better performance
					// These attributes get added to the <img> tag
					'class'    => trim(($class ?? '') . " featured-image"),      // your CSS hook
					// size hint: “100vw up to 1200px wide, then cap at 1200px”
					'sizes'    => '(max-width: 1200px) 100vw, 1200px',
				]
			);
		} else {
			$featuredImage = '<img '. ($class ? "class='{$class}'" : '') .' src="https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:100x0-25-0-0-center-0.jpg" 
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
	public static function getDateParts($date, int $minutes = 0): array
	{
		$formatter = new IntlDateFormatter('nl_NL', IntlDateFormatter::NONE, IntlDateFormatter::NONE);

		// Calculate end time by adding minutes
		$endDate = clone $date;
		$endDate->add(new DateInterval('PT' . abs($minutes) . 'M'));
		if ($minutes < 0) {
			$endDate = clone $date;
			$endDate->sub(new DateInterval('PT' . abs($minutes) . 'M'));
		}

		return [
			'day_number' => $date->format('J'),
			'day_name' => $formatter->setPattern('EEEE') ? $formatter->format($date) : null,
			'day_short' => $formatter->setPattern('EEE') ? $formatter->format($date) : null,
			'month_number' => $date->format('n'),
			'month_name' => $formatter->setPattern('MMMM') ? $formatter->format($date) : null,
			'month_short' => $formatter->setPattern('MMM') ? $formatter->format($date) : null,
			'year' => $date->format('Y'),
			'hour' => $date->format('G'),
			'minute' => $date->format('i'),
			'niceDateFull' => $formatter->setPattern('EEEE d MMMM') ? $formatter->format($date) : null,
			'start_time' => $date->format('G:i'),
			'end_time' => $endDate->format('G:i')
		];
	}

	public static function minutesToTime(int $minutes): string
	{
		$hours = intval($minutes / 60);
		$mins = $minutes % 60;
		return sprintf('%d:%02d', $hours, $mins);
	}

	public static function minutesToDutchRounded(int $minutes): string
	{
		// Round to nearest 30 minutes
		$roundedMinutes = round($minutes / 30) * 30;
		// Cap at 8 hours (480 minutes)
		$roundedMinutes = min($roundedMinutes, 480);

		$hours = $roundedMinutes / 60;

		return match($hours) {
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

	public static function classFirstP(string $string, string $class): string {
		// Check if string contains any <p> tags
		if (!preg_match('/<p(\s[^>]*)?>/i', $string)) {
			// No paragraph tags found - wrap entire content in <p> with class
			return '<p class="' . htmlspecialchars($class, ENT_QUOTES) . '">' . $string . '</p>';
		}

		// Paragraph tags exist - apply class to first one using original logic
		return preg_replace('/<p(\s[^>]*)?>/i', '<p$1 class="' . htmlspecialchars($class, ENT_QUOTES) . '">', $string, 1);
	}

	/**
	 * Query and loop through the last n posts from a custom post type
	 *
	 * @param string $post_type Custom post type slug
	 * @param int    $count     Number of posts to retrieve
	 * @param array  $meta_query Optional meta query parameters
	 * @param array  $tax_query  Optional taxonomy query parameters
	 * @return WP_Query|false   Query object or false on failure
	 */
	public static function get_latest_custom_posts($post_type, $count, $meta_query = [], $tax_query = [], $timestamp_field = null): WP_Query|bool {
		// Validate post type exists
		if (!post_type_exists($post_type)) {
			return false;
		}

		$args = [
			'post_type'      => $post_type,
			'posts_per_page' => absint($count),
			'post_status'    => 'publish',
			'no_found_rows'  => true,           // Skip pagination count query
			'update_post_meta_cache' => false,  // Skip meta cache if not needed
			'update_post_term_cache' => false,  // Skip term cache if not needed
		];

		// Add timestamp filtering and sorting if field is provided
		if ($timestamp_field) {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = $timestamp_field;
			$args['order'] = 'ASC';
			$args['meta_query'] = array_merge([
				[
					'key'     => $timestamp_field,
					'value'   => current_time('timestamp'),
					'compare' => '>='
				]
			], $meta_query);
		} else {
			// Default sorting by post date
			$args['orderby'] = 'date';
			$args['order'] = 'DESC';
			if (!empty($meta_query)) {
				$args['meta_query'] = $meta_query;
			}
		}

		// Add taxonomy query if provided
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}

		return new WP_Query($args);
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

	public static function has_field($field) : bool {
		global $page_part_fields;
		return key_exists($field, $page_part_fields)  && $page_part_fields[$field] !== '';
	}

	/**
	 * Strip all quote-like characters for display within quoted text
	 *
	 * Removes straight quotes, curly quotes, HTML entities, grave accents,
	 * and other quote-like characters to prevent visual duplication when
	 * displaying content within HTML quote marks.
	 *
	 * @example Quote display
	 * <h2>"<?= esc_html(Helpers::strip_all_quotes($quote['quote'])) ?>"</h2>
	 *
	 * @param string $text Text containing various quote characters
	 * @return string Text with all quotes removed
	 */
	public static function strip_all_quotes(mixed $text): string {
		// Handle non-string inputs
		if ($text === null || $text === '') {
			return '';
		}

		// Cast to string for numeric types
		$text = (string) $text;

		// Convert HTML entities to actual characters
		$text = wp_specialchars_decode($text, ENT_QUOTES);

		// Remove all quote-like characters
		$quotes = [
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

		return str_replace($quotes, '', $text);
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

function do_extra_header_data( $nonce = '' ): void {
	if ( defined( 'is_admin' ) && ! is_admin() ) {
		header( 'Content-Security-Policy:' . get_csp( $nonce ) );
	}
	header( 'X-Frame-Options: Allow' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-XSS-Protection: 1; mode=block' );
	header( 'Strict-Transport-Security: max-age=631138519; includeSubDomains' );
	header( 'Connection: keep-alive' );
}

function makeRandomString( $bits = 256 ): string {
#generate nonce (for Google Tag Manager etc)
	$bytes  = ceil( $bits / 8 );
	$return = '';
	for ( $i = 0; $i < $bytes; $i ++ ) {
		$return .= chr( mt_rand( 0, 255 ) );
	}

	return $return;
}

//format a phonenumber
function format_phone( $phone, $landcode = '31' ): string {
	$phone     = str_replace( ' ', '', $phone );
	$kentallen = array(
		'06',
		'0909',
		'0906',
		'0900',
		'0842',
		'0800',
		'0676',
		'010',
		'046',
		'0111',
		'0475',
		'0113',
		'0478',
		'0114',
		'0481',
		'0115',
		'0485',
		'0117',
		'0486',
		'0118',
		'0487',
		'013',
		'0488',
		'015',
		'0492',
		'0161',
		'0493',
		'0162',
		'0495',
		'0164',
		'0497',
		'0165',
		'0499',
		'0166',
		'050',
		'0167',
		'0511',
		'0168',
		'0512',
		'0172',
		'0513',
		'0174',
		'0514',
		'0180',
		'0515',
		'0181',
		'0516',
		'0182',
		'0517',
		'0183',
		'0518',
		'0184',
		'0519',
		'0186',
		'0521',
		'0187',
		'0522',
		'020',
		'0523',
		'0222',
		'0524',
		'0223',
		'0525',
		'0224',
		'0527',
		'0226',
		'0528',
		'0227',
		'0529',
		'0228',
		'053',
		'0229',
		'0541',
		'023',
		'0543',
		'024',
		'0544',
		'0251',
		'0545',
		'0252',
		'0546',
		'0255',
		'0547',
		'026',
		'0548',
		'0294',
		'055',
		'0297',
		'0561',
		'0299',
		'0562',
		'030',
		'0566',
		'0313',
		'0570',
		'0314',
		'0571',
		'0315',
		'0572',
		'0316',
		'0573',
		'0317',
		'0575',
		'0318',
		'0577',
		'0320',
		'0578',
		'0321',
		'058',
		'033',
		'0591',
		'0341',
		'0592',
		'0342',
		'0593',
		'0343',
		'0594',
		'0344',
		'0595',
		'0345',
		'0596',
		'0346',
		'0597',
		'0347',
		'0598',
		'0348',
		'0599',
		'035',
		'070',
		'036',
		'071',
		'038',
		'072',
		'040',
		'073',
		'0411',
		'074',
		'0412',
		'075',
		'0413',
		'076',
		'0416',
		'077',
		'0418',
		'078',
		'043',
		'079',
		'045'
	);
	if ( substr( $phone, 0, 3 ) == '+31' || substr( $phone, 0, 2 ) == '31' ) {
		$phone = str_replace( '+31', '0', $phone );
	}
	$netnummer = '0'; //def
	for ( $i = 4; $i >= 0; $i -- ) {
		$netnummer = substr( $phone, 0, $i );
		if ( in_array( $netnummer, $kentallen ) ) {
			break;
		} else {
			$netnummer = substr( $phone, 0, 3 ); //def eerste 3 cijfers.
		}
	}
	$search = '/' . preg_quote( $netnummer, '/' ) . '/';
	$nummer = preg_replace( $search, '', $phone, 1 ); //haal netnummer van oorspronkelijke nummer af
	if ( strlen( $nummer ) < 8 ) {
		preg_match( '/(\d{2,3})(\d{2}+)(\d{2}+)/', $nummer, $matches ); //maakt groepjes: XXX XX XX of XX XX XX in het geval van een 4 cijferig netnummer
	} else {
		preg_match( '/(\d{2})(\d{2})(\d{2}+)(\d{2}+)/', $nummer, $matches ); //maakt groepjes: XXX XX XX of XX XX XX in het geval van een 4 cijferig netnummer
	}
	array_shift( $matches ); //remove first item (original string)
	if ( $landcode ) {
		$landcode = ( substr( $landcode, 0, 1 ) == '+' ) ? $landcode : '+' . $landcode;
		$search   = '/' . preg_quote( '0', '/' ) . '/';

		return preg_replace( $search, $landcode . ' ', $netnummer, 1 ) . ' ' . implode( ' ', $matches );
	} else {
		return $netnummer . ' ' . implode( ' ', $matches );
	}
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
