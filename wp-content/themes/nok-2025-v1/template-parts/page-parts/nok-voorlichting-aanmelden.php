<?php
/**
 * Template Name: Voorlichting Aanmelden
 * Description: General voorlichting registration form with AJAX-populated dropdowns for location and date/time selection
 * Slug: nok-voorlichting-aanmelden
 * Custom Fields:
 * - colors:select(Wit op lichtgrijs|Wit op donkerblauw)!page-editable!default(Wit op lichtgrijs)
 * - show_intro:checkbox!default(true)!descr[Toon introductietekst boven het formulier]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - center_text:checkbox!default(false)!descr[Midden uitlijnen]!page-editable
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\VoorlichtingForm;

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

// Check if there are any upcoming voorlichtingen
$has_voorlichtingen = false;
$vestigingen = get_posts([
	'post_type'      => 'vestiging',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
]);
foreach ($vestigingen as $vestiging) {
	$city = preg_replace('/^NOK\s+/i', '', $vestiging->post_title);
	if (\NOK2025\V1\Helpers::count_upcoming_voorlichtingen($city, true) > 0) {
		$has_voorlichtingen = true;
		break;
	}
}

// Form configuration
$form_id = VoorlichtingForm::FORM_ID;
$voorlichting_id_field = 'input_' . $form_id . '_' . VoorlichtingForm::FIELD_VOORLICHTING_ID;
?>

<nok-section class="<?= $section_colors ?>" data-requires="./nok-voorlichting-form.mjs">
	<div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>" id="nok-voorlichting-form">

		<?php if ($c->show_intro->isTrue()): ?>
			<div class="nok-layout-grid nok-mb-2 <?= $c->center_text->isTrue('text-center'); ?>">
				<?php if (!$c->hide_title->isTrue()) : ?>
				<h2 class="nok-fs-6"><?= $c->title() ?></h2>
				<?php endif; ?>
				<div><?= $c->content() ?></div>
			</div>
		<?php endif; ?>

		<nok-square-block class="<?= $block_colors ?>" data-shadow="true">
			<?php if ($has_voorlichtingen): ?>
				<?php if (function_exists('gravity_form')): ?>
					<!-- Voorlichting selector (OUTSIDE form) -->
					<div class="nok-voorlichting-selector nok-mb-1_5"
					     data-voorlichting-selector
					     data-api-url="<?= esc_url(rest_url('nok-2025-v1/v1/voorlichtingen/options')) ?>"
					     data-target-form="#gform_<?= $form_id ?>"
					     data-voorlichting-id-field="<?= esc_attr($voorlichting_id_field) ?>">

						<div class="nok-form-row nok-layout-grid" style="--cols: 2; --gap: var(--spacing-1);">
							<div class="nok-form-field">
								<label for="voorlichting-location" class="gfield_label gform-field-label">
									<?php esc_html_e('Vestiging', THEME_TEXT_DOMAIN); ?>
									<span class="gfield_required gfield_required_asterisk">*</span>
								</label>
								<select id="voorlichting-location"
								        class="gfield_select"
								        required>
									<option value=""><?php esc_html_e('Selecteer een vestiging', THEME_TEXT_DOMAIN); ?></option>
								</select>
							</div>
							<div class="nok-form-field">
								<label for="voorlichting-datetime" class="gfield_label gform-field-label">
									<?php esc_html_e('Datum en tijd', THEME_TEXT_DOMAIN); ?>
									<span class="gfield_required gfield_required_asterisk">*</span>
								</label>
								<select id="voorlichting-datetime"
								        class="gfield_select"
								        disabled
								        required>
									<option value=""><?php esc_html_e('Selecteer eerst een vestiging', THEME_TEXT_DOMAIN); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- Gravity Form 1 (same as single-voorlichting), disabled until voorlichting selected -->
					<fieldset data-voorlichting-form-fieldset disabled>
						<?php gravity_form($form_id, false, false); ?>
					</fieldset>
				<?php else: ?>
					<p class="nok-text-muted">
						<?php esc_html_e('Gravity Forms is niet actief. Neem contact op met de beheerder.', THEME_TEXT_DOMAIN); ?>
					</p>
				<?php endif; ?>
			<?php else: ?>
				<div class="nok-alert nok-bg-greenyellow--lighter nok-p-1 nok-rounded-border nok-bg-alpha-10">
					<p>
						<?= Assets::getIcon('ui_calendar', 'nok-mr-0_5') ?>
						<?php esc_html_e('Er zijn momenteel geen voorlichtingen beschikbaar voor aanmelding.', THEME_TEXT_DOMAIN); ?>
					</p>
					<p class="nok-mt-1">
						<a href="<?= esc_url(get_post_type_archive_link('voorlichting')) ?>" class="nok-hyperlink">
							<?php esc_html_e('Bekijk de volledige agenda', THEME_TEXT_DOMAIN); ?>
						</a>
						<?php esc_html_e('voor toekomstige voorlichtingen.', THEME_TEXT_DOMAIN); ?>
					</p>
				</div>
			<?php endif; ?>
		</nok-square-block>
	</div>
</nok-section>
