<?php
/**
 * Vragenlijst (Questionnaire) post-part template
 *
 * Renders the questionnaire container with embedded JSON config.
 * JS module handles all interaction (wizard flow, branching, results).
 *
 * Used in popup context (nok-header-main.php) and inline (page-part wrapper).
 *
 * Expected $context fields:
 * - vragenlijst_slug: Slug of the vragenlijst CPT post to load
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

$slug = $context->has( 'vragenlijst_slug' ) ? $context->vragenlijst_slug->raw() : '';

if ( empty( $slug ) ) {
	return;
}

$posts = get_posts( [
	'post_type'      => 'vragenlijst',
	'name'           => sanitize_title( $slug ),
	'posts_per_page' => 1,
	'post_status'    => 'publish',
] );

if ( empty( $posts ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<p class="nok-admin-notice">Vragenlijst "' . esc_html( $slug ) . '" niet gevonden.</p>';
	}

	return;
}

$questionnaire = $posts[0];
$config_json   = get_post_meta( $questionnaire->ID, '_vl_config', true );

if ( empty( $config_json ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<p class="nok-admin-notice">Vragenlijst "' . esc_html( $slug ) . '" heeft geen configuratie.</p>';
	}

	return;
}

// Verify JSON is valid before outputting
$config = json_decode( $config_json, true );
if ( json_last_error() !== JSON_ERROR_NONE || empty( $config['questions'] ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<p class="nok-admin-notice">Vragenlijst configuratie is ongeldig.</p>';
	}

	return;
}

// Build intro from post_content (WordPress content with blocks/shortcodes)
$intro_html = apply_filters( 'the_content', $questionnaire->post_content );
$has_intro  = ! empty( trim( wp_strip_all_tags( $questionnaire->post_content ) ) );

// Start button text from config settings
$settings   = $config['settings'] ?? [];
$start_text = esc_html( $settings['start_button_text'] ?? 'Start de vragenlijst' );
?>
<div class="nok-vragenlijst" data-requires="./nok-vragenlijst.mjs" data-require-lazy="true">
	<script type="application/json"><?= $config_json ?></script>

	<div class="nok-vragenlijst__intro">
		<?php if ( $has_intro ) : ?>
			<div class="nok-vragenlijst__intro-content">
				<?= $intro_html ?>
			</div>
		<?php endif; ?>
		<button type="button" class="nok-button nok-bg-darkerblue nok-text-contrast nok-vragenlijst__start">
			<span><?= $start_text ?></span>
		</button>
	</div>

	<noscript>
		<div class="nok-vragenlijst__noscript">
			<p>Deze vragenlijst vereist JavaScript. Neem contact op met de
				Nederlandse Obesitas Kliniek via <a href="tel:+31888832444">088 883 2444</a>
				of <a href="/contact/">het contactformulier</a> om te bespreken
				of u in aanmerking komt voor behandeling.</p>
		</div>
	</noscript>
</div>
