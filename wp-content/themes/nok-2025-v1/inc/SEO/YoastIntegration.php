<?php
/**
 * YoastIntegration - Page Parts SEO Content Analysis
 *
 * Integrates page parts with Yoast SEO by collecting rendered content
 * from iframe previews and providing aggregated text to Yoast's analysis engine.
 *
 * Architecture notes:
 * - Page parts render in iframes (not directly in editor DOM)
 * - Each iframe extracts its semantic content via meta tag
 * - JavaScript waits for all iframes to load before registering with Yoast
 * - Visual editor mode only (code editor cannot access iframe content)
 *
 * Yoast title/meta templates (configured in Yoast admin, documented here for traceability):
 * - Sitename (%%sitename%%) set to "NOK" across all content types (2026-04-15),
 *   replacing the previous "Nederlandse Obesitas Kliniek" suffix to keep
 *   <title> under 60 chars / 580px. See plan-seo-technical.md §1.1.
 * - Same %%sitename%% -> "NOK" change applied in meta description templates
 *   across all content types (2026-04-15). See plan-seo-technical.md §1.2.
 *   Note: vestiging meta descriptions may still exceed 155 chars due to
 *   boilerplate length — verify via re-crawl.
 *
 * @example Basic initialization in Theme class
 * $yoast = new YoastIntegration();
 * $yoast->register_hooks();
 *
 * @package NOK2025\V1\SEO
 */

namespace NOK2025\V1\SEO;

class YoastIntegration {

	/**
	 * Register WordPress hooks
	 *
	 * Hooks into admin_enqueue_scripts to load integration JavaScript
	 * only on appropriate edit screens with Yoast SEO active.
	 */
	public function register_hooks(): void {
		add_action('admin_enqueue_scripts', [$this, 'enqueue_integration_script'], 20);
		// Exclude page_part from Yoast indexables and sitemaps
		add_filter('wpseo_indexable_excluded_post_types', [$this, 'exclude_page_parts_from_indexables']);
		add_filter('wpseo_sitemap_exclude_post_type', [$this, 'exclude_page_parts_from_sitemap'], 10, 2);
		// Fix breadcrumb archive URLs for custom post types
		add_filter('wpseo_breadcrumb_links', [$this, 'fix_breadcrumb_archive_urls']);
		// Enhance Yoast schema graph with MedicalOrganization data
		add_filter('wpseo_schema_graph', [$this, 'enhance_schema_graph'], 10, 2);
		// Fall back to post excerpt when no explicit meta description is set
		add_filter('wpseo_metadesc', [$this, 'fallback_to_excerpt']);
		// Noindex showcase page (works with or without Yoast)
		add_filter('wp_robots', [$this, 'noindex_showcase_page']);
		// Exclude showcase page from Yoast sitemap and WordPress internal search
		add_filter('wpseo_sitemap_entry', [$this, 'exclude_showcase_from_sitemap'], 10, 3);
		add_action('pre_get_posts', [$this, 'exclude_showcase_from_search']);
	}

	public function exclude_page_parts_from_indexables(array $excluded): array {
		$excluded[] = 'page_part';
		return $excluded;
	}

	public function exclude_page_parts_from_sitemap(bool $excluded, string $post_type): bool {
		return $post_type === 'page_part' ? true : $excluded;
	}

	/**
	 * Add noindex and nofollow to the showcase page template
	 *
	 * Uses the WordPress core `wp_robots` filter, which works regardless of
	 * whether Yoast SEO is active.
	 *
	 * @param array $robots Robot directives
	 * @return array Modified directives with noindex/nofollow when on showcase page
	 */
	public function noindex_showcase_page(array $robots): array {
		if (is_page_template('page-showcase.php')) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	/**
	 * Exclude showcase page from Yoast XML sitemap
	 *
	 * Filters individual sitemap entries. Returns false to omit the entry
	 * when the post uses the page-showcase.php template.
	 *
	 * @param array  $url       Sitemap entry array with 'loc', 'mod', etc.
	 * @param string $url_type  URL type (e.g., 'post')
	 * @param object $post      Post object
	 * @return array|false Entry array or false to exclude
	 */
	public function exclude_showcase_from_sitemap($url, $url_type, $post) {
		if (!is_object($post) || empty($post->ID)) {
			return $url;
		}

		$template = get_page_template_slug($post->ID);
		if ($template === 'page-showcase.php') {
			return false;
		}

		return $url;
	}

	/**
	 * Exclude showcase pages from WordPress search results
	 *
	 * Catches all search contexts: main query, REST API, and AJAX autocomplete.
	 * Skips admin screens so editors can still find the page in wp-admin.
	 *
	 * @param \WP_Query $query The query object
	 */
	public function exclude_showcase_from_search($query): void {
		if (is_admin() || empty($query->get('s'))) {
			return;
		}

		$showcase_ids = get_posts([
			'post_type'  => 'page',
			'meta_key'   => '_wp_page_template',
			'meta_value' => 'page-showcase.php',
			'fields'     => 'ids',
			'numberposts' => -1,
		]);

		if (!empty($showcase_ids)) {
			$existing = $query->get('post__not_in') ?: [];
			$query->set('post__not_in', array_merge($existing, $showcase_ids));
		}
	}

	/**
	 * Fall back to post excerpt when no explicit Yoast meta description is set
	 *
	 * Fallback chain (singulars):
	 * 1. Manual excerpt on the post itself
	 * 2. Auto-generated excerpt from post_content (first 55 words)
	 * 3. Voorlichting: intro_lang from HubSpot metadata
	 * 4. Content from the first embedded page part that has text
	 * 5. Direct extraction from post_content (bypasses get_the_excerpt() filter
	 *    chain, which can return empty when global $post isn't set — this
	 *    fixed FAQ singles under /kennisbank/veelgestelde-vragen/*)
	 *
	 * Archive coverage (kennisbank):
	 * - Post type archive (and paged variants): templated description
	 * - Taxonomy archive kennisbank_categories (and paged variants): templated
	 *   description including the term name
	 *
	 * @param string $metadesc The meta description from Yoast (empty if not set)
	 * @return string The meta description, or auto-generated excerpt as fallback
	 */
	public function fallback_to_excerpt(string $metadesc): string {
		if ($metadesc !== '') {
			return $metadesc;
		}

		// Kennisbank archive pages (post type archive + paged variants)
		if (is_post_type_archive('kennisbank')) {
			$page = max(1, (int) get_query_var('paged'));
			return $page > 1
				? sprintf('NOK Kennisbank — pagina %d. Antwoorden over obesitas, behandeling en leefstijl.', $page)
				: 'NOK Kennisbank: antwoorden op veelgestelde vragen over obesitas, behandeling, operaties en leefstijl.';
		}

		// Kennisbank taxonomy archives (category + paged variants)
		if (is_tax('kennisbank_categories')) {
			$term = get_queried_object();
			$term_name = ($term && isset($term->name)) ? $term->name : '';
			$page = max(1, (int) get_query_var('paged'));
			if ($page > 1) {
				return sprintf('NOK Kennisbank — %s, pagina %d. Antwoorden over obesitas en behandeling.', $term_name, $page);
			}
			return sprintf('NOK Kennisbank categorie %s. Antwoorden op veelgestelde vragen over obesitas en behandeling.', $term_name);
		}

		if (!is_singular()) {
			return $metadesc;
		}

		$post = get_post();
		if (!$post) {
			return $metadesc;
		}

		// Try the post's own excerpt (manual or auto-generated from content)
		$excerpt = get_the_excerpt($post);
		if ($excerpt) {
			return $excerpt;
		}

		// Voorlichting: use HubSpot intro text (these posts have no post_content)
		if ($post->post_type === 'voorlichting') {
			$hubspot = \NOK2025\V1\Helpers::setup_hubspot_metadata($post->ID);
			$intro = $hubspot['intro_lang'] ?? $hubspot['intro'] ?? '';
			if ($intro) {
				return wp_trim_words(wp_strip_all_tags($intro), 55);
			}
		}

		// Try first embedded page part with content
		$page_part_excerpt = $this->get_first_page_part_excerpt($post);
		if ($page_part_excerpt) {
			return $page_part_excerpt;
		}

		// Final fallback: extract text directly from post_content
		return $this->generate_excerpt_from_content($post) ?: $metadesc;
	}

	/**
	 * Generate an excerpt directly from a post's raw post_content
	 *
	 * Bypasses get_the_excerpt() / wp_trim_excerpt() which can return empty
	 * on some posts (observed on kennisbank FAQ singles where the global
	 * $post isn't set at filter time). Applies the same block-stripping,
	 * shortcode-stripping, and word-trimming logic as core.
	 *
	 * @param \WP_Post $post The post to extract text from
	 * @return string Trimmed excerpt text, or empty string if content yields nothing
	 */
	private function generate_excerpt_from_content(\WP_Post $post): string {
		if (empty($post->post_content)) {
			return '';
		}

		$text = $post->post_content;
		$text = excerpt_remove_blocks($text);
		$text = strip_shortcodes($text);
		$text = wp_strip_all_tags($text);
		$text = trim(preg_replace('/\s+/u', ' ', $text));

		if ($text === '') {
			return '';
		}

		return wp_trim_words($text, 55, '…');
	}

	/**
	 * Get an excerpt from the first embedded page part that has text content
	 *
	 * Parses the post's block content for page part embeds, then checks each
	 * page part's content in order until one produces a usable excerpt.
	 *
	 * @param \WP_Post $post The parent post containing page part embeds
	 * @return string Excerpt text, or empty string if none found
	 */
	private function get_first_page_part_excerpt(\WP_Post $post): string {
		if (empty($post->post_content)) {
			return '';
		}

		$part_ids = $this->extract_page_part_ids($post->post_content);
		if (empty($part_ids)) {
			return '';
		}

		foreach ($part_ids as $part_id) {
			$part = get_post($part_id);
			if (!$part || $part->post_status !== 'publish') {
				continue;
			}

			$excerpt = get_the_excerpt($part);
			if ($excerpt) {
				return $excerpt;
			}
		}

		return '';
	}

	/**
	 * Fix breadcrumb archive URLs and add taxonomy breadcrumbs for custom post types
	 *
	 * Yoast's breadcrumb system has architectural limitations:
	 * 1. It doesn't respect WordPress's has_archive custom slugs
	 * 2. It doesn't automatically insert taxonomy terms for CPTs with taxonomy in URL
	 *
	 * This method:
	 * - Corrects archive URLs using WordPress core's get_post_type_archive_link()
	 * - Inserts category breadcrumb for kennisbank posts (between archive and post)
	 *
	 * @param array $links Array of breadcrumb items from Yoast
	 * @return array Modified breadcrumb array with corrected URLs and added taxonomy
	 */
	public function fix_breadcrumb_archive_urls(array $links): array {
		// Handle vestiging archive URLs
		if (is_singular('vestiging') || is_post_type_archive('vestiging')) {
			foreach ($links as $key => $link) {
				if (isset($link['ptarchive']) && $link['ptarchive'] === 'vestiging') {
					$links[$key]['url'] = get_post_type_archive_link('vestiging');
				}
			}
		}

		// Handle kennisbank: fix archive URL and insert category breadcrumb
		if (is_singular('kennisbank')) {
			$links = $this->add_kennisbank_category_breadcrumb($links);
		}

		// Handle kennisbank taxonomy archive: add Kennisbank parent before category
		if (is_tax('kennisbank_categories')) {
			$links = $this->add_kennisbank_archive_breadcrumb($links);
		}

		// Handle voorlichting: format date in Dutch and fix archive URL
		if (is_singular('voorlichting')) {
			$links = $this->format_voorlichting_breadcrumb($links);
		}

		return $links;
	}

	/**
	 * Add category breadcrumb for kennisbank posts
	 *
	 * Inserts the primary category between the archive and post title breadcrumbs.
	 * Structure: Home / Kennisbank / {Category} / {Post Title}
	 *
	 * @param array $links Breadcrumb links array
	 * @return array Modified links with category inserted
	 */
	private function add_kennisbank_category_breadcrumb(array $links): array {
		$post_id = get_the_ID();
		$terms = get_the_terms($post_id, 'kennisbank_categories');

		if (!$terms || is_wp_error($terms)) {
			return $links;
		}

		$primary_term = $terms[0];
		$category_breadcrumb = [
			'url'  => get_term_link($primary_term),
			'text' => $primary_term->name,
		];

		// Find the position to insert (after the archive, before the post)
		$insert_position = null;
		foreach ($links as $key => $link) {
			// Find the kennisbank archive breadcrumb
			if (isset($link['ptarchive']) && $link['ptarchive'] === 'kennisbank') {
				$insert_position = $key + 1;
				// Also fix the archive URL while we're here
				$links[$key]['url'] = get_post_type_archive_link('kennisbank');
				break;
			}
		}

		// Insert the category breadcrumb
		if ($insert_position !== null) {
			array_splice($links, $insert_position, 0, [$category_breadcrumb]);
		}

		return $links;
	}

	/**
	 * Add Kennisbank archive breadcrumb for taxonomy archives
	 *
	 * Inserts the Kennisbank archive link before the category name.
	 * Structure: Home / Kennisbank / {Category}
	 *
	 * @param array $links Breadcrumb links array
	 * @return array Modified links with archive inserted
	 */
	private function add_kennisbank_archive_breadcrumb(array $links): array {
		$archive_breadcrumb = [
			'url'  => get_post_type_archive_link('kennisbank'),
			'text' => get_post_type_object('kennisbank')->labels->name ?? 'Kennisbank',
		];

		// Find position to insert (after home, before the taxonomy term)
		// Yoast typically puts: Home / {Term} for taxonomy archives
		// We want: Home / Kennisbank / {Term}
		$insert_position = 1; // After home by default

		array_splice($links, $insert_position, 0, [$archive_breadcrumb]);

		return $links;
	}

	/**
	 * Format voorlichting breadcrumb with Dutch date
	 *
	 * Replaces the post title breadcrumb with a properly formatted version
	 * that includes the event type, location, and Dutch date format.
	 * Structure: Home / Agenda / Voorlichting {Location} - {Dutch Date}
	 *
	 * @param array $links Breadcrumb links array
	 * @return array Modified links with formatted voorlichting title
	 */
	private function format_voorlichting_breadcrumb(array $links): array {
		$post_id = get_the_ID();
		if (!$post_id) {
			return $links;
		}

		// Get HubSpot metadata for Dutch date formatting
		$hubspotData = \NOK2025\V1\Helpers::setup_hubspot_metadata($post_id);
		if (empty($hubspotData['timestamp']['niceDateFull'])) {
			return $links;
		}

		// Build formatted breadcrumb text: "Voorlichting {Location} - {Dutch Date}"
		$soort = ucfirst($hubspotData['soort']);
		$locatie = ucfirst($hubspotData['locatie']);
		$date = $hubspotData['timestamp']['niceDateFull'];
		$formatted_text = "$soort $locatie - $date";

		// Find and update the voorlichting breadcrumbs
		foreach ($links as $key => $link) {
			// Fix archive URL
			if (isset($link['ptarchive']) && $link['ptarchive'] === 'voorlichting') {
				$links[$key]['url'] = get_post_type_archive_link('voorlichting');
			}
			// Update post title breadcrumb (last item, has 'id' key)
			if (isset($link['id']) && $link['id'] === $post_id) {
				$links[$key]['text'] = $formatted_text;
			}
		}

		return $links;
	}

	/**
	 * Enhance Yoast's schema graph with MedicalOrganization data
	 *
	 * Modifies the Organization piece to MedicalOrganization with bariatric surgery
	 * specialty and adds all vestigingen as department entries. On singular vestiging
	 * pages, also adds a detailed MedicalClinic piece with address, phone, and
	 * opening hours.
	 *
	 * @param array                              $graph   Schema graph pieces
	 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context Yoast meta tags context
	 * @return array Modified schema graph
	 */
	public function enhance_schema_graph(array $graph, $context): array {
		foreach ($graph as &$piece) {
			if (!isset($piece['@type'])) {
				continue;
			}

			// Upgrade Organization to MedicalOrganization
			$type = $piece['@type'];
			$is_org = ($type === 'Organization')
				|| (is_array($type) && in_array('Organization', $type, true));

			if ($is_org) {
				$piece['@type'] = ['Organization', 'MedicalOrganization'];
				$piece['medicalSpecialty'] = 'Bariatric Surgery';
				$piece['department'] = $this->build_vestiging_departments();
			}
		}
		unset($piece);

		// On singular vestiging pages, add a detailed MedicalClinic piece
		if (is_singular('vestiging')) {
			$clinic_piece = $this->build_vestiging_clinic_schema(get_the_ID());
			if ($clinic_piece) {
				$graph[] = $clinic_piece;
			}
		}

		return $graph;
	}

	/**
	 * Build department array with all vestigingen as MedicalClinic entries
	 *
	 * Queries all published vestigingen and builds a compact MedicalClinic entry
	 * for each, suitable for the Organization's `department` property.
	 *
	 * @return array Array of MedicalClinic schema entries
	 */
	private function build_vestiging_departments(): array {
		$departments = [];
		$vestigingen = get_posts([
			'post_type'      => 'vestiging',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		]);

		foreach ($vestigingen as $vestiging) {
			$id          = $vestiging->ID;
			$street      = get_post_meta($id, '_street', true);
			$housenumber = get_post_meta($id, '_housenumber', true);
			$postal_code = get_post_meta($id, '_postal_code', true);
			$city        = get_post_meta($id, '_city', true);
			$phone       = get_post_meta($id, '_phone', true);

			$department = [
				'@type' => 'MedicalClinic',
				'name'  => $vestiging->post_title,
				'url'   => get_permalink($id),
			];

			if ($street || $city) {
				$department['address'] = [
					'@type'           => 'PostalAddress',
					'streetAddress'   => trim("$street $housenumber"),
					'postalCode'      => $postal_code,
					'addressLocality' => $city,
					'addressCountry'  => 'NL',
				];
			}

			if ($phone) {
				$department['telephone'] = $phone;
			}

			$departments[] = $department;
		}

		return $departments;
	}

	/**
	 * Build detailed MedicalClinic schema piece for a single vestiging
	 *
	 * Includes full address, contact info, opening hours specifications,
	 * and a parentOrganization reference back to the main Organization piece.
	 *
	 * @param int $post_id Vestiging post ID
	 * @return array|null MedicalClinic schema piece, or null if insufficient data
	 */
	private function build_vestiging_clinic_schema(int $post_id): ?array {
		$title       = get_the_title($post_id);
		$street      = get_post_meta($post_id, '_street', true);
		$housenumber = get_post_meta($post_id, '_housenumber', true);
		$postal_code = get_post_meta($post_id, '_postal_code', true);
		$city        = get_post_meta($post_id, '_city', true);
		$phone       = get_post_meta($post_id, '_phone', true);
		$email       = get_post_meta($post_id, '_email', true);
		$hours_json  = get_post_meta($post_id, '_opening_hours', true);

		if (!$title) {
			return null;
		}

		$piece = [
			'@type'              => 'MedicalClinic',
			'@id'                => get_permalink($post_id) . '#medical-clinic',
			'name'               => $title,
			'url'                => get_permalink($post_id),
			'medicalSpecialty'   => 'Bariatric Surgery',
			'parentOrganization' => [
				'@id' => home_url('/#organization'),
			],
		];

		if ($street || $city) {
			$piece['address'] = [
				'@type'           => 'PostalAddress',
				'streetAddress'   => trim("$street $housenumber"),
				'postalCode'      => $postal_code,
				'addressLocality' => $city,
				'addressCountry'  => 'NL',
			];
		}

		if ($phone) {
			$piece['telephone'] = $phone;
		}

		if ($email) {
			$piece['email'] = $email;
		}

		if ($hours_json) {
			$opening_hours = $this->parse_opening_hours_to_schema($hours_json);
			if ($opening_hours) {
				$piece['openingHoursSpecification'] = $opening_hours;
			}
		}

		return $piece;
	}

	/**
	 * Convert opening hours JSON meta to Schema.org OpeningHoursSpecification array
	 *
	 * Parses the vestiging `_opening_hours` JSON format, which supports a "weekdays"
	 * template with per-day overrides, into an array of OpeningHoursSpecification
	 * objects for each open day.
	 *
	 * @param string $opening_hours_json JSON string from `_opening_hours` meta field
	 * @return array Array of OpeningHoursSpecification entries
	 */
	private function parse_opening_hours_to_schema(string $opening_hours_json): array {
		if (empty($opening_hours_json) || $opening_hours_json === '{}') {
			return [];
		}

		$hours = json_decode($opening_hours_json, true);
		if (!is_array($hours)) {
			return [];
		}

		$day_map = [
			'monday'    => 'Monday',
			'tuesday'   => 'Tuesday',
			'wednesday' => 'Wednesday',
			'thursday'  => 'Thursday',
			'friday'    => 'Friday',
		];

		$weekdays_template = isset($hours['weekdays']) && !empty($hours['weekdays'])
			? $hours['weekdays'][0]
			: null;

		$specs = [];
		foreach ($day_map as $day_key => $schema_day) {
			$day_hours = isset($hours[$day_key]) && !empty($hours[$day_key])
				? $hours[$day_key]
				: [];

			$time_block = null;
			if (count($day_hours) > 0) {
				$time_block = $day_hours[0];
				// Skip explicitly closed days
				if (isset($time_block['closed']) && $time_block['closed'] === true) {
					continue;
				}
			} elseif ($weekdays_template) {
				$time_block = $weekdays_template;
			}

			if ($time_block && isset($time_block['opens'], $time_block['closes'])) {
				$specs[] = [
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => $schema_day,
					'opens'     => $time_block['opens'],
					'closes'    => $time_block['closes'],
				];
			}
		}

		return $specs;
	}

	/**
	 * Get social media profiles configured in Yoast SEO
	 *
	 * Reads the `wpseo_social` option and maps configured URLs to platform
	 * metadata (icon name, label). Only platforms with a non-empty URL and
	 * a known icon are returned. TikTok and other extras live in Yoast's
	 * `other_social_urls` array field.
	 *
	 * @example Render social links in a template
	 * $profiles = YoastIntegration::get_social_profiles();
	 * foreach ($profiles as $p) {
	 *     echo '<a href="' . esc_url($p['url']) . '">' . Assets::getIcon($p['icon']) . '</a>';
	 * }
	 *
	 * @return array<int, array{platform: string, url: string, icon: string, label: string}>
	 */
	public static function get_social_profiles(): array {
		if (!defined('WPSEO_VERSION')) {
			return [];
		}

		$social = get_option('wpseo_social', []);
		if (!is_array($social)) {
			return [];
		}

		// Collect URLs from Yoast's known fields
		$urls = [];

		if (!empty($social['facebook_site'])) {
			$urls[] = $social['facebook_site'];
		}
		if (!empty($social['twitter_site'])) {
			$urls[] = 'https://x.com/' . ltrim($social['twitter_site'], '@');
		}
		if (!empty($social['instagram_url'])) {
			$urls[] = $social['instagram_url'];
		}
		if (!empty($social['linkedin_url'])) {
			$urls[] = $social['linkedin_url'];
		}
		if (!empty($social['youtube_url'])) {
			$urls[] = $social['youtube_url'];
		}
		if (!empty($social['pinterest_url'])) {
			$urls[] = $social['pinterest_url'];
		}

		// Merge "other" URLs (TikTok etc.)
		if (!empty($social['other_social_urls']) && is_array($social['other_social_urls'])) {
			foreach ($social['other_social_urls'] as $url) {
				if (!empty($url)) {
					$urls[] = $url;
				}
			}
		}

		// Map each URL to a platform via domain detection
		$platform_map = [
			'facebook.com'  => ['icon' => 'social_facebook',  'label' => 'Facebook'],
			'instagram.com' => ['icon' => 'social_instagram', 'label' => 'Instagram'],
			'linkedin.com'  => ['icon' => 'social_linkedin',  'label' => 'LinkedIn'],
			'youtube.com'   => ['icon' => 'social_youtube',   'label' => 'YouTube'],
			'tiktok.com'    => ['icon' => 'social_tiktok',    'label' => 'TikTok'],
			'x.com'         => ['icon' => 'social_twitter-x', 'label' => 'X'],
			'twitter.com'   => ['icon' => 'social_twitter-x', 'label' => 'X'],
			'pinterest.com' => ['icon' => 'social_pinterest', 'label' => 'Pinterest'],
		];

		$profiles = [];
		foreach ($urls as $url) {
			$host = wp_parse_url($url, PHP_URL_HOST);
			if (!$host) {
				continue;
			}

			foreach ($platform_map as $domain => $meta) {
				if (str_contains($host, $domain)) {
					$profiles[] = [
						'platform' => $meta['label'],
						'url'      => $url,
						'icon'     => $meta['icon'],
						'label'    => $meta['label'],
					];
					break;
				}
			}
		}

		return $profiles;
	}

	/**
	 * Check if Yoast SEO is active
	 *
	 * @return bool True if WPSEO_VERSION constant is defined
	 */
	private function is_yoast_active(): bool {
		return defined('WPSEO_VERSION');
	}

	/**
	 * Enqueue the Yoast integration script
	 *
	 * Loads JavaScript integration only when:
	 * - On post/page edit screens
	 * - Yoast SEO is active
	 * - Block editor is enabled
	 * - Post type supports page parts (page/post)
	 *
	 * Passes expected page part IDs to JavaScript for loading detection.
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_integration_script(string $hook): void {
		// Only load on post edit screens
		if (!in_array($hook, ['post.php', 'post-new.php'])) {
			return;
		}

		if (!$this->is_yoast_active()) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen) {
			return;
		}

		// Only for post types that can contain page parts
		$allowed_post_types = ['page', 'post'];
		if (!in_array($screen->post_type, $allowed_post_types)) {
			return;
		}

		// Only in block editor
		if (!$screen->is_block_editor()) {
			return;
		}

		// Extract expected page part IDs from saved post content
		$post_id = get_the_ID();
		$expected_parts = [];

		if ($post_id) {
			$post = get_post($post_id);
			if ($post && !empty($post->post_content)) {
				$expected_parts = $this->extract_page_part_ids($post->post_content);
			}
		}

		$asset_file = get_theme_file_path('/assets/js/yoast-page-parts-integration.asset.php');

		if (!file_exists($asset_file)) {
			error_log('[Yoast Integration] Asset file not found. Run npm build.');
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'nok-yoast-page-parts-integration',
			get_stylesheet_directory_uri() . '/assets/js/yoast-page-parts-integration.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// JavaScript will use expectedParts to know when all iframes have loaded
		wp_localize_script(
			'nok-yoast-page-parts-integration',
			'nokYoastIntegration',
			[
				'expectedParts' => $expected_parts,
				'postId' => $post_id,
				'debug' => false
			]
		);
	}

	/**
	 * Extract page part IDs from post content
	 *
	 * Parses Gutenberg block structure to find all nok2025/embed-nok-page-part
	 * blocks and extracts their postId attributes.
	 *
	 * @param string $content Raw post_content with block comments
	 * @return array Array of unique page part IDs
	 */
	private function extract_page_part_ids(string $content): array {
		$blocks = parse_blocks($content);
		return $this->find_page_part_ids_recursive($blocks);
	}

	/**
	 * Recursively find page part IDs in block structure
	 *
	 * Handles nested blocks (page parts inside columns, groups, etc.)
	 *
	 * @param array $blocks Array of parsed block arrays from parse_blocks()
	 * @return array Array of unique page part IDs
	 */
	private function find_page_part_ids_recursive(array $blocks): array {
		$part_ids = [];

		foreach ($blocks as $block) {
			if ($block['blockName'] === 'nok2025/embed-nok-page-part') {
				$post_id = $block['attrs']['postId'] ?? 0;
				if ($post_id > 0) {
					$part_ids[] = $post_id;
				}
			}

			// Check inner blocks recursively
			if (!empty($block['innerBlocks'])) {
				$part_ids = array_merge(
					$part_ids,
					$this->find_page_part_ids_recursive($block['innerBlocks'])
				);
			}
		}

		return array_unique($part_ids);
	}
}