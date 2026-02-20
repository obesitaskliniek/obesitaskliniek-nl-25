<?php
/**
 * Server-side render for NOK Video Section block
 *
 * Uses the block-parts template system for consistent rendering.
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\PageParts\TemplateRenderer;

return function( array $attributes ): string {
	// Return empty if no video URL
	if ( empty( $attributes['videoUrl'] ) ) {
		return '';
	}

	// Map block attributes to field names expected by block-part template
	$fields = [
		'video_url'       => $attributes['videoUrl'] ?? '',
		'video_type'      => $attributes['videoType'] ?? 'youtube',
		'video_hq'        => $attributes['videoHq'] ?? '',
		'video_poster'    => $attributes['videoPoster'] ?? '',
		'video_start'     => $attributes['videoStart'] ?? '',
		'autoplay'        => $attributes['autoplay'] ?? 'visibility',
		'full_section'    => ( $attributes['fullSection'] ?? true ) ? '1' : '0',
		'achtergrondkleur' => $attributes['backgroundColor'] ?? 'nok-bg-darkerblue',
		'tekstkleur'      => $attributes['textColor'] ?? 'nok-text-contrast',
		'narrow_section'  => ( $attributes['narrowSection'] ?? false ) ? '1' : '0',
		'section_title'       => $attributes['sectionTitle'] ?? '',
		'section_description' => $attributes['sectionDescription'] ?? '',
	];

	$renderer = new TemplateRenderer();

	return $renderer->render_block_part( 'video-section', $fields, [
		'attributes' => $attributes,
	] );
};
