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

$settings   = $config['settings'] ?? [];
$skip_intro = ! empty( $settings['skip_intro'] );

// Build intro from post_content (only needed when we actually render the intro)
$intro_html = '';
$has_intro  = false;
if ( ! $skip_intro ) {
	$intro_html = apply_filters( 'the_content', $questionnaire->post_content );
	$has_intro  = ! empty( trim( wp_strip_all_tags( $questionnaire->post_content ) ) );
}

$start_text = esc_html( $settings['start_button_text'] ?? 'Start de vragenlijst' );

// Pre-render any Gravity Forms referenced by results with end_action='form'.
// We render them once into a hidden pool so the JS renderer can move the
// fully-enqueued form DOM into the result container without losing GF's
// bound event listeners or needing a shortcode re-render at runtime.
$form_pool = [];
if ( function_exists( 'gravity_form' ) ) {
	foreach ( ( $config['results'] ?? [] ) as $result ) {
		if ( empty( $result['id'] ) ) {
			continue;
		}
		$end_action = $result['end_action'] ?? '';
		$form_id    = isset( $result['gravity_form_id'] ) ? (int) $result['gravity_form_id'] : 0;
		if ( $end_action !== 'form' || $form_id <= 0 ) {
			continue;
		}
		ob_start();
		gravity_form( $form_id, false, false, false, null, true );
		$form_pool[ $result['id'] ] = (string) ob_get_clean();
	}
}
?>
<?php
// Lazy loading relies on the container having a non-zero intersection rect
// (DOMule's IntersectionObserver checks ratio > 0). With skip_intro on, the
// container has no visible children server-side, so the lazy watcher would
// never fire inside a closed popup. Load eagerly in that case.
$lazy_attr = $skip_intro ? '' : ' data-require-lazy="true"';
?>
<div class="nok-vragenlijst" data-requires="./nok-vragenlijst.mjs"<?= $lazy_attr ?>>
	<script type="application/json"><?= $config_json ?></script>

	<?php if ( ! $skip_intro ) : ?>
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
	<?php endif; ?>

	<?php /*
		Pool containers position the form off-screen with the full parent
		width so Gravity Forms' init scripts compute real field dimensions.
		Using the `hidden` attribute (display:none) would render the form at
		zero size — it would then only appear after a window resize triggered
		a re-layout once moved. `aria-hidden` + `inert`/`tabindex=-1` keep
		the off-screen copy out of assistive tech and focus order.
	*/ ?>
	<?php foreach ( $form_pool as $result_id => $form_html ) : ?>
		<div class="nok-vragenlijst__form-pool" data-result-id="<?= esc_attr( $result_id ) ?>"
		     aria-hidden="true" inert tabindex="-1"
		     style="position:absolute;left:-10000px;top:0;width:100%;max-width:100%;overflow:hidden;pointer-events:none;">
			<?= $form_html ?>
		</div>
	<?php endforeach; ?>

	<noscript>
		<div class="nok-vragenlijst__noscript">
			<p>Deze vragenlijst vereist JavaScript. Neem contact op met de
				Nederlandse Obesitas Kliniek via <a href="tel:+31888832444">088 883 2444</a>
				of <a href="/contact/">het contactformulier</a> om te bespreken
				of u in aanmerking komt voor behandeling.</p>
		</div>
	</noscript>
</div>
