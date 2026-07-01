<?php
/**
 * Archive Template: Voorlichtingen (Agenda)
 *
 * @package NOK2025_V1
 */

use NOK2025\V1\Agenda;

Agenda::render_archive( [
	'post_types'        => [ 'voorlichting' ],
	'archive_link'      => get_post_type_archive_link( 'voorlichting' ),
	'intro_post_type'   => 'voorlichting',
	'heading'           => __( 'Agenda', THEME_TEXT_DOMAIN ),
	'period_label'      => __( 'Voorlichtingen in', THEME_TEXT_DOMAIN ),
	'empty_message'     => __( 'Geen voorlichtingen gevonden in deze maand.', THEME_TEXT_DOMAIN ),
	'protect_logged_in' => false,
] );
