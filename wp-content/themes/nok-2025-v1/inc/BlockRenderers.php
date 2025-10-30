<?php
// inc/BlockRenderers.php

namespace NOK2025\V1;

/**
 * BlockRenderers - Custom rendering for native WordPress blocks
 *
 * Transforms native Gutenberg block output to match theme styling patterns.
 * Currently handles:
 * - core/quote: Restructures blockquote with icon and semantic classes
 *
 * @example Initialize in Theme.php
 * $this->block_renderers = new BlockRenderers();
 * $this->block_renderers->register_hooks();
 *
 * @package NOK2025\V1
 */
class BlockRenderers {

	/**
	 * Register WordPress filter hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter('render_block_core/quote', [$this, 'render_quote_block'], 10, 2);
		add_filter('render_block_core/heading', [$this, 'render_heading_block'], 10, 2);
	}

	/**
	 * Transform native blockquote to themed structure
	 *
	 * Converts WordPress core quote block output to:
	 * <blockquote class="nok-quote nok-fs-4 fw-bold nok-my-2">
	 *   <div class="nok-quote__icon">SVG</div>
	 *   <p class="nok-quote__text">Content</p>
	 * </blockquote>
	 *
	 * @param string $block_content Original block HTML
	 * @param array $block Block data including attributes
	 * @return string Transformed HTML
	 */
	public function render_quote_block(string $block_content, array $block): string {
		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding($block_content, 'HTML-ENTITIES', 'UTF-8'),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$blockquote = $dom->getElementsByTagName('blockquote')->item(0);
		if (!$blockquote) {
			return $block_content;
		}

		// Extract all inner content, stripping paragraph wrappers
		$text = '';
		foreach ($blockquote->childNodes as $node) {
			if ($node->nodeName === 'p') {
				// Get innerHTML only (content without the <p> tags)
				foreach ($node->childNodes as $child) {
					$text .= $dom->saveHTML($child);
				}
				// Add space between paragraphs if multiple exist
				$text .= ' ';
			}
		}

		$text = trim($text);

		// Bail if no content extracted
		if (empty($text)) {
			return $block_content;
		}

		// Get quote icon
		$icon = Assets::getIcon('ui_quote');

		// Rebuild with theme structure
		return sprintf(
			'<blockquote class="wp-block-quote nok-quote nok-fs-5 nok-my-2"><div class="nok-quote__icon">%s</div><p class="nok-quote__text">%s</p></blockquote>',
			$icon,
			$text
		);
	}

	public function render_heading_block( string $block_content, array $block) {
		// Use DOMDocument (you're already using it for quotes)
		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding( $block_content, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$heading2 = $dom->getElementsByTagName( 'h2' )->item( 0 );

		if ( $heading2 ) {
			$existing = $heading2->getAttribute( 'class' );
			$heading2->setAttribute( 'class', trim( $existing . ' nok-fs-5 fw-400 nok-my-1' ) );
		}

		return $dom->saveHTML();
	}

}