<?php
/**
 * SEO Content Aggregator
 *
 * Aggregates content from page and all embedded page parts for SEO analysis.
 * Extracts semantic HTML elements (headings, paragraphs, images, videos, lists)
 * and returns clean text suitable for Yoast SEO analysis.
 *
 * @package NOK2025\V1\SEO
 */

namespace NOK2025\V1\SEO;

use NOK2025\V1\PageParts\TemplateRenderer;
use NOK2025\V1\PageParts\MetaManager;

class ContentAggregator {
	private TemplateRenderer $renderer;
	private MetaManager $meta_manager;
	private const CACHE_TTL = 300; // 5 minutes
	private const CACHE_PREFIX = 'nok_seo_content_';

	public function __construct(TemplateRenderer $renderer, MetaManager $meta_manager) {
		$this->renderer = $renderer;
		$this->meta_manager = $meta_manager;
	}

	/**
	 * Get aggregated SEO content for a post
	 *
	 * Combines the post's own content with content from all embedded page parts.
	 * Returns plain text with semantic structure preserved.
	 *
	 * @param int  $post_id   Post ID to aggregate content for
	 * @param bool $use_cache Whether to use cached content
	 * @return array {content: string, part_count: int, parts: array}
	 */
	public function get_aggregated_content(int $post_id, bool $use_cache = true): array {
		$cache_key = self::CACHE_PREFIX . $post_id;

		if ($use_cache) {
			$cached = get_transient($cache_key);
			if ($cached !== false) {
				return $cached;
			}
		}

		$post = get_post($post_id);
		if (!$post) {
			return [
				'content' => '',
				'part_count' => 0,
				'parts' => []
			];
		}

		// Start with page's own content
		$aggregated = $this->extract_semantic_content($post->post_content);
		$parts_info = [];

		// Parse and process page part blocks
		$blocks = parse_blocks($post->post_content);
		$page_part_blocks = $this->find_page_part_blocks($blocks);

		foreach ($page_part_blocks as $block) {
			$part_id = $block['attrs']['postId'] ?? 0;
			if (!$part_id) {
				continue;
			}

			$overrides = $block['attrs']['overrides'] ?? [];

			// Render the page part
			$rendered_html = $this->render_page_part($part_id, $overrides);

			// Extract semantic content
			$part_content = $this->extract_semantic_content($rendered_html);

			// Append to aggregated content
			if (!empty($part_content)) {
				$aggregated .= "\n\n" . $part_content;
			}

			$parts_info[] = [
				'id' => $part_id,
				'title' => get_the_title($part_id),
				'content_length' => strlen($part_content)
			];
		}

		$result = [
			'content' => trim($aggregated),
			'part_count' => count($parts_info),
			'parts' => $parts_info
		];

		// Cache the result
		set_transient($cache_key, $result, self::CACHE_TTL);

		return $result;
	}

	/**
	 * Invalidate cached content for a post
	 *
	 * @param int $post_id Post ID
	 */
	public function invalidate_cache(int $post_id): void {
		delete_transient(self::CACHE_PREFIX . $post_id);
	}

	/**
	 * Recursively find all page part blocks
	 *
	 * @param array $blocks Parsed blocks array
	 * @return array Array of page part blocks
	 */
	private function find_page_part_blocks(array $blocks): array {
		$page_part_blocks = [];

		foreach ($blocks as $block) {
			if ($block['blockName'] === 'nok2025/embed-nok-page-part') {
				$page_part_blocks[] = $block;
			}

			// Recursively check inner blocks
			if (!empty($block['innerBlocks'])) {
				$page_part_blocks = array_merge(
					$page_part_blocks,
					$this->find_page_part_blocks($block['innerBlocks'])
				);
			}
		}

		return $page_part_blocks;
	}

	/**
	 * Render a page part with overrides
	 *
	 * @param int   $part_id   Page part post ID
	 * @param array $overrides Block attribute overrides
	 * @return string Rendered HTML
	 */
	private function render_page_part(int $part_id, array $overrides = []): string {
		return $this->renderer->render_page_part_with_context($part_id, $overrides, $this->meta_manager);
	}

	/**
	 * Extract semantic content from HTML
	 *
	 * Extracts headings, paragraphs, images, videos, and lists.
	 * Returns plain text suitable for SEO analysis.
	 *
	 * @param string $html HTML content
	 * @return string Extracted semantic content
	 */
	private function extract_semantic_content(string $html): string {if (empty($html)) {
		return '';
	}

		// Remove completely if raw blocks
		if (strpos($html, '<!-- wp:') !== false) {
			return '';  // ✓ Return empty instead of trying to extract
		}

		libxml_use_internal_errors(true);
		$doc = new \DOMDocument();
		$doc->loadHTML(
			mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		$content_parts = [];

		// Extract headings
		for ($i = 1; $i <= 6; $i++) {
			$headings = $doc->getElementsByTagName("h{$i}");
			foreach ($headings as $heading) {
				$text = trim($heading->textContent);
				if (!empty($text)) {
					$content_parts[] = $text;
				}
			}
		}

		// Extract paragraphs
		$paragraphs = $doc->getElementsByTagName('p');
		foreach ($paragraphs as $p) {
			$text = trim($p->textContent);
			if (!empty($text)) {
				$content_parts[] = $text;
			}
		}

		// Extract list items
		$lists = $doc->getElementsByTagName('li');
		foreach ($lists as $li) {
			$text = trim($li->textContent);
			if (!empty($text)) {
				$content_parts[] = $text;
			}
		}

		// ✓ NEW: Extract divs/spans with direct text (not in p/h tags)
		$xpath = new \DOMXPath($doc);
		$textNodes = $xpath->query('//div/text()[normalize-space()] | //span/text()[normalize-space()]');
		foreach ($textNodes as $textNode) {
			$text = trim($textNode->textContent);
			if (!empty($text) && strlen($text) > 20) {  // Only substantial text
				$content_parts[] = $text;
			}
		}

		// Extract image alt text
		$images = $doc->getElementsByTagName('img');
		foreach ($images as $img) {
			$alt = $img->getAttribute('alt');
			if (!empty($alt)) {
				$content_parts[] = "[image: {$alt}]";
			}
		}

		// Extract video information
		$videos = $doc->getElementsByTagName('video');
		foreach ($videos as $video) {
			$video_info = $this->extract_video_info($video);
			if (!empty($video_info)) {
				$content_parts[] = $video_info;
			}
		}

		// Also check for iframe embeds (YouTube, Vimeo, etc.)
		$iframes = $doc->getElementsByTagName('iframe');
		foreach ($iframes as $iframe) {
			$src = $iframe->getAttribute('src');
			$title = $iframe->getAttribute('title');

			if (!empty($src) && $this->is_video_embed($src)) {
				$video_text = "[video";
				if (!empty($title)) {
					$video_text .= ": {$title}";
				}
				$video_text .= " - {$src}]";
				$content_parts[] = $video_text;
			}
		}

		return implode("\n\n", array_unique($content_parts));
	}

	/**
	 * Extract video element information
	 *
	 * @param \DOMElement $video Video DOM element
	 * @return string Formatted video information
	 */
	private function extract_video_info(\DOMElement $video): string {
		$info_parts = ["[video"];

		// Try to get title or aria-label
		$title = $video->getAttribute('title') ?: $video->getAttribute('aria-label');
		if (!empty($title)) {
			$info_parts[] = $title;
		}

		// Get video source
		$src = $video->getAttribute('src');
		if (empty($src)) {
			// Check for source child elements
			$sources = $video->getElementsByTagName('source');
			if ($sources->length > 0) {
				$src = $sources->item(0)->getAttribute('src');
			}
		}

		if (!empty($src)) {
			$info_parts[] = $src;
		}

		// Get poster image if available
		$poster = $video->getAttribute('poster');
		if (!empty($poster)) {
			$info_parts[] = "poster: {$poster}";
		}

		return implode(' - ', $info_parts) . "]";
	}

	/**
	 * Check if URL is a video embed
	 *
	 * @param string $url URL to check
	 * @return bool True if video embed
	 */
	private function is_video_embed(string $url): bool {
		$video_domains = [
			'youtube.com',
			'youtu.be',
			'vimeo.com',
			'dailymotion.com',
			'wistia.com',
			'videopress.com'
		];

		foreach ($video_domains as $domain) {
			if (strpos($url, $domain) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get aggregated content using explicit part IDs (from editor state)
	 *
	 * @param int   $post_id  Post ID
	 * @param array $part_ids Array of page part IDs
	 * @return array
	 */
	public function get_aggregated_content_from_parts(int $post_id, array $part_ids): array {
		$post = get_post($post_id);
		if (!$post) {
			return ['content' => '', 'part_count' => 0, 'parts' => []];
		}

		// Don't extract from page content - only from rendered parts
		$aggregated = '';  // ✓ Changed
		$parts_info = [];

		foreach ($part_ids as $part_id) {
			if (!$part_id) continue;

			$rendered_html = $this->render_page_part($part_id, []);
			$part_content = $this->extract_semantic_content($rendered_html);

			if (!empty($part_content)) {
				$aggregated .= "\n\n" . $part_content;
			}

			$parts_info[] = [
				'id' => $part_id,
				'title' => get_the_title($part_id),
				'content_length' => strlen($part_content)
			];
		}

		return [
			'content' => trim($aggregated),
			'part_count' => count($parts_info),
			'parts' => $parts_info
		];
	}
}