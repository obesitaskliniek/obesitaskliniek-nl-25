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

	// In the editor, title/description are handled by RichText fields above
	// the ServerSideRender output — skip them here to avoid double-rendering.
	$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;

	$fields = [
		'title'           => $is_editor ? '' : ( $attributes['title'] ?? 'Downloads' ),
		'description'     => $is_editor ? '' : ( $attributes['description'] ?? '' ),
		'narrow_section'  => ! empty( $attributes['narrow_section'] ) ? '1' : '0',
	];

	$renderer = new TemplateRenderer();

	return $renderer->render_block_part( 'attachment-downloads', $fields, [
		'attachments' => $attachments,
		'attributes'  => $attributes,
	] );
};
