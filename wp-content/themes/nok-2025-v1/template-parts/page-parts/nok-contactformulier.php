<?php
/**
 * Template Name: Contactformulier
 * Description: Contact form with vestiging selector - routes to selected vestiging's email
 * Slug: nok-contactformulier
 * Custom Fields:
 * - colors:select(Wit op lichtgrijs|Wit op donkerblauw)!page-editable!default(Wit op lichtgrijs)
 * - show_intro:checkbox!default(true)!descr[Toon introductietekst boven het formulier]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - center_text:checkbox!default(false)!descr[Midden uitlijnen]!page-editable
 * - preselect_vestiging:checkbox!page-editable!default(false)!descr[Selecteer automatisch de vestiging van deze pagina (verbergt dropdown)]
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\ContactForm;

$c = $context;

// Color mappings
$section_colors = $c->colors->is('Wit op donkerblauw',
	'nok-bg-darkerblue nok-text-white',
	'nok-bg-body--darker'
);
$block_colors = $c->colors->is('Wit op donkerblauw',
	'nok-bg-darkblue nok-text-white',
	'nok-bg-body nok-text-contrast'
);

$form_id = ContactForm::FORM_ID;

// Check for automatic vestiging preselection
$vestiging_value = '';
$hide_vestiging_dropdown = false;

if ($c->preselect_vestiging->isTrue()) {
	// Get the current page context (the page being viewed, not the page_part)
	$context_post = get_queried_object();

	if ($context_post instanceof WP_Post && $context_post->post_type === 'vestiging') {
		// We're on a vestiging page - use this vestiging
		$vestiging_value = (string) $context_post->ID;
		$hide_vestiging_dropdown = true;
	}
	// If not on a vestiging page, checkbox has no effect - show dropdown as normal
}

// Form field values for dynamic population
// Note: Parameter name prefixed to avoid conflict with 'vestiging' CPT slug
$field_values = $vestiging_value ? 'contactform_vestiging=' . $vestiging_value : '';
?>

<?php if ($hide_vestiging_dropdown): ?>
<style>.populate-vestigingen { display: none !important; }</style>
<?php endif; ?>

<nok-section class="<?= $section_colors ?>">
	<div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?> <?= $c->center_text->isTrue('text-center'); ?>" id="nok-contactformulier">

		<?php if ($c->show_intro->isTrue()): ?>
			<div class="nok-layout-grid nok-mb-2">
				<?php if (!$c->hide_title->isTrue()) : ?>
			<h2 class="nok-fs-6"><?= $c->title() ?></h2>
			<?php endif; ?>
				<?php if ($hide_vestiging_dropdown): ?>
					<?php
					// Get city name from vestiging meta
					$city = get_post_meta($context_post->ID, '_city', true);
					?>
					<p>Heb je een vraag voor onze vestiging in <?= esc_html($city) ?>? Vul dan onderstaand formulier in en we nemen zo snel mogelijk contact met je op.</p>
				<?php else: ?>
					<div><?= $c->content() ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<nok-square-block class="<?= $block_colors ?>" data-shadow="true">
			<?php if (function_exists('gravity_form')): ?>
				<?php gravity_form($form_id, false, false, false, $field_values ?: null, true); ?>
			<?php else: ?>
				<p class="nok-text-muted">
					<?php esc_html_e('Gravity Forms is niet actief. Neem contact op met de beheerder.', THEME_TEXT_DOMAIN); ?>
				</p>
			<?php endif; ?>
		</nok-square-block>
	</div>
</nok-section>
