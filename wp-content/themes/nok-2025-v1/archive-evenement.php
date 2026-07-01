<?php
/**
 * Archive Template: Evenementen
 *
 * @package NOK2025_V1
 */

use NOK2025\V1\Agenda;

Agenda::render_archive( [
	'post_types'      => [ 'evenement' ],
	'archive_link'    => get_post_type_archive_link( 'evenement' ),
	'intro_post_type' => 'evenement',
	'heading'         => __( 'Evenementen', THEME_TEXT_DOMAIN ),
	'period_label'    => __( 'Evenementen in', THEME_TEXT_DOMAIN ),
	'empty_message'   => __( 'Geen evenementen gevonden in deze maand.', THEME_TEXT_DOMAIN ),
] );