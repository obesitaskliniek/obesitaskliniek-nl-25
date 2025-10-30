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
		add_filter( 'render_block_core/quote', [ $this, 'render_quote_block' ], 10, 2 );
		add_filter( 'render_block_core/heading', [ $this, 'render_heading_block' ], 10, 2 );
		add_filter( 'render_block_core/image', [ $this, 'handle_ghost_image' ], 10, 2 );
	}

	public function handle_ghost_image( string $block_content, array $block ): string {
		$enable_blur = $block['attrs']['enableBlurBackground'] ?? false;

		if ( ! $enable_blur ) {
			return $block_content;
		}

		$image_id = $block['attrs']['id'] ?? 0;
		if ( ! $image_id ) {
			return $block_content;
		}

		// Get thumbnail/medium size for blur
		$blur_image = wp_get_attachment_image(
			$image_id,
			'medium',
			false,
			[ 'class' => 'cover-image-blur' ]
		);

		if ( ! $blur_image ) {
			return $block_content;
		}

		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding( $block_content, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$figure    = $dom->getElementsByTagName( 'figure' )->item( 0 );
		$first_img = $dom->getElementsByTagName( 'img' )->item( 0 );

		if ( ! $figure || ! $first_img ) {
			return $block_content;
		}

		// Parse blur image HTML
		$blur_dom = new \DOMDocument();
		@$blur_dom->loadHTML(
			mb_convert_encoding( $blur_image, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$blur_img_node = $dom->importNode( $blur_dom->documentElement, true );

		// Insert BEFORE first image
		//$first_img->parentNode->insertBefore($blur_img_node, $first_img);

		// Wrap both images in container div
		$wrapper = $dom->createElement( 'div' );
		$wrapper->setAttribute( 'class', 'nok-image-cover-blur-container' );

		// Insert wrapper before first image
		$first_img->parentNode->insertBefore( $wrapper, $first_img );

		// Move both images into wrapper
		$wrapper->appendChild( $blur_img_node );
		$wrapper->appendChild( $first_img );

		return $dom->saveHTML();
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
	 *
	 * @return string Transformed HTML
	 */
	public function render_quote_block( string $block_content, array $block ): string {
		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding( $block_content, 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		$blockquote = $dom->getElementsByTagName( 'blockquote' )->item( 0 );
		if ( ! $blockquote ) {
			return $block_content;
		}

		// Extract all inner content, stripping paragraph wrappers
		$text = '';
		foreach ( $blockquote->childNodes as $node ) {
			if ( $node->nodeName === 'p' ) {
				// Get innerHTML only (content without the <p> tags)
				foreach ( $node->childNodes as $child ) {
					$text .= $dom->saveHTML( $child );
				}
				// Add space between paragraphs if multiple exist
				$text .= ' ';
			}
		}

		$text = trim( $text );

		// Bail if no content extracted
		if ( empty( $text ) ) {
			return $block_content;
		}

		// Get quote icon
		$icon = Assets::getIcon( 'ui_quote' );

		// Rebuild with theme structure
		return sprintf(
			'<blockquote class="wp-block-quote nok-quote nok-fs-5 nok-my-2"><div class="nok-quote__icon">%s</div><p class="nok-quote__text">%s</p></blockquote>',
			$icon,
			$text
		);
	}

	public function render_heading_block( string $block_content, array $block ) {
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