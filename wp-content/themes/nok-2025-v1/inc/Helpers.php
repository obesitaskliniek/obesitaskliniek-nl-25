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
	 * @param int|WP_Post|null $post Post ID, object, or null for current post
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
			$excerpt = '';
			if ( isset( $post_meta['_highlighted_excerpt'] ) ) {
				$excerpt = self::strip_all_quotes( rtrim( $post_meta['_highlighted_excerpt'][0], '.' ) ) . '...';
			} else {
				$excerpt = self::get_excerpt( $post_id, 30 );
			}

			$quote_item = [
				'quote'     => self::strip_all_quotes( $quote_text ),
				'excerpt'   => $excerpt,
				'name'      => $post_meta['_naam_patient'][0] ?? 'Anonieme patiënt',
				'subnaam'   => $post_meta['_subnaam_patient'][0] ?? '',
				'link_url'  => get_permalink( $post_id ),
				'image_url' => self::get_featured_image_uri( $post )
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
	 */
	public static function the_content_first_paragraph(): void {
		echo self::get_content_first_paragraph();
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
						$insert_pos = $position + strlen($full_match);
						return substr($content, 0, $insert_pos) . $injection . substr($content, $insert_pos);
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
