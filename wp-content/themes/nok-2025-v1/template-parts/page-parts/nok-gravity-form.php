<?php
/**
 * Template Name: Gravity Form
 * Description: Willekeurig Gravity Form insluiten met kleur- en layoutopties
 * Slug: nok-gravity-form
 * Custom Fields:
 * - form_id:select(Inschrijfformulier WOD::5|Contactformulier::4|Inschrijving voorlichting::1)!page-editable!descr[Kies een Gravity Forms formulier]
 * - colors:select(Wit op lichtgrijs|Wit op donkerblauw)!page-editable!default(Wit op lichtgrijs)
 * - show_intro:checkbox!default(true)!descr[Toon introductietekst boven het formulier]
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - center_text:checkbox!default(false)!descr[Midden uitlijnen]!page-editable
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

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

$form_id = (int) (string) $c->form_id;

?>

<nok-section class="<?= $section_colors ?>">
	<div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?> <?= $c->center_text->isTrue('text-center'); ?>">

		<?php if ($c->show_intro->isTrue()): ?>
			<div class="nok-layout-grid nok-mb-2">
				<?php if (!$c->hide_title->isTrue()) : ?>
				<h2 class="nok-fs-6"><?= $c->title() ?></h2>
				<?php endif; ?>
				<div><?= $c->content() ?></div>
			</div>
		<?php endif; ?>

		<nok-square-block class="<?= $block_colors ?>" data-shadow="true">
			<?php if ($form_id > 0 && function_exists('gravity_form')): ?>
				<?php gravity_form($form_id, false, false, false, null, true); ?>
			<?php elseif (!function_exists('gravity_form')): ?>
				<p class="nok-text-muted">
					<?php esc_html_e('Gravity Forms is niet actief. Neem contact op met de beheerder.', THEME_TEXT_DOMAIN); ?>
				</p>
			<?php else: ?>
				<p class="nok-text-muted">
					<?php esc_html_e('Geen formulier geselecteerd.', THEME_TEXT_DOMAIN); ?>
				</p>
			<?php endif; ?>
		</nok-square-block>
	</div>
</nok-section>
