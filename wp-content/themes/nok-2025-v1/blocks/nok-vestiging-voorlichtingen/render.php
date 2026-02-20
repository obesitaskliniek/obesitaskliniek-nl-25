<?php
/**
 * Server-side render for NOK Vestiging Voorlichtingen block
 *
 * Resolves vestiging context (explicit, auto-detect, or all locations)
 * and delegates rendering to the block-parts template.
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\Helpers;
use NOK2025\V1\PageParts\TemplateRenderer;

return function( array $attributes ): string {
	$vestiging_id  = (int) ( $attributes['vestigingId'] ?? 0 );
	$limit         = (int) ( $attributes['limit'] ?? 6 );
	$title         = $attributes['title'] ?? 'Voorlichtingen';
	$show_all_link = (bool) ( $attributes['showAllLink'] ?? true );
	$city          = null;

	// Context resolution: determine which city to filter by
	if ( $vestiging_id > 0 ) {
		// Explicit vestiging selected — get city from that post's title
		$vestiging_post = get_post( $vestiging_id );
		if ( $vestiging_post ) {
			$city = preg_replace( '/^NOK\s+/i', '', $vestiging_post->post_title );
		}
	} elseif ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && get_post_type() === 'vestiging' ) {
		// Auto-detect from current vestiging page (frontend only)
		$city = preg_replace( '/^NOK\s+/i', '', get_the_title() );
	}
	// Otherwise $city stays null → all locations

	$voorlichtingen = Helpers::get_upcoming_voorlichtingen( $limit, $city );

	if ( empty( $voorlichtingen ) ) {
		return '';
	}

	// Build "Bekijk alle" URL
	$archive_url = get_post_type_archive_link( 'voorlichting' );
	$all_url     = $city
		? add_query_arg( 'locatie', urlencode( $city ), $archive_url )
		: $archive_url;

	$renderer = new TemplateRenderer();

	return $renderer->render_block_part( 'vestiging-voorlichtingen', [
		'title'            => $title,
		'background_color' => $attributes['backgroundColor'] ?? '',
		'text_color'       => $attributes['textColor'] ?? '',
	], [
		'voorlichtingen' => $voorlichtingen,
		'all_url'        => $all_url,
		'show_all_link'  => $show_all_link,
		'city'           => $city,
	] );
};
