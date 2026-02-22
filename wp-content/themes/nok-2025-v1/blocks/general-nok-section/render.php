<?php
/**
 * Server-side render callback for General NOK Section block
 *
 * Uses the block-parts template system for consistent rendering.
 * Wraps inner block content in a nok-section element with configurable styling.
 *
 * @param array $attributes Block attributes from block.json
 * @param string $content Inner blocks content
 *
 * @return string Rendered HTML output
 */

use NOK2025\V1\PageParts\TemplateRenderer;

return function ( array $attributes, string $content ): string {
	// Return empty if no content
	if ( empty( trim( strip_tags( $content ) ) ) ) {
		return '';
	}

	// Map block attributes to field names expected by block-part template
	$fields = [
		'background_color' => $attributes['backgroundColor'] ?? '',
		'text_color'       => $attributes['textColor'] ?? 'nok-text-darkerblue',
		'layout_width'     => $attributes['layoutWidth'] ?? '1-column',
		'narrow_section'   => ( $attributes['narrowSection'] ?? false ) ? '1' : '0',
		'text_center'      => ( $attributes['textCenter'] ?? false ) ? '1' : '0',
		'enable_pull_up'   => ( $attributes['enablePullUp'] ?? false ) ? '1' : '0',
		'enable_no_aos'    => ( $attributes['enableNoAos'] ?? false ) ? '1' : '0',
	];

	$renderer = new TemplateRenderer();

	return $renderer->render_block_part( 'general-section', $fields, [
		'content'    => $content,
		'attributes' => $attributes,
	] );
};
