<?php
/**
 * Server-side render for NOK Attachment Downloads block
 *
 * Uses the block-parts template system for consistent rendering.
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\Helpers;
use NOK2025\V1\PageParts\TemplateRenderer;

return function( array $attributes ): string {
	$attachments = Helpers::get_non_image_attachments();

	if ( empty( $attachments ) ) {
		return '';
	}

	$fields = [
		'title'       => $attributes['title'] ?? 'Downloads',
		'description' => $attributes['description'] ?? '',
	];

	$renderer = new TemplateRenderer();

	return $renderer->render_block_part( 'attachment-downloads', $fields, [
		'attachments' => $attachments,
		'attributes'  => $attributes,
	] );
};
