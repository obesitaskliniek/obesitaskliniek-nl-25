<?php
/**
 * Template Name: CTA block
 * Description: Call-to-action block with icon, title, content and optional button in horizontal layout
 * Slug: nok-cta-block
 * Custom Fields:
 * - layout_offset:select(left|balanced)!default(left)
 * - colors:select(Blauw op transparant|Blauw op donkerblauw)!page-editable!default(Blauw op transparant)
 * - icon:icon-selector!page-editable!default(ui_arrow-right-long)
 * - button_text:text!default(Lees meer)
 * - button_url:url
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;

$section_colors = $c->colors->is('Blauw op donkerblauw', 'nok-bg-darkerblue', '');
$block_colors = $c->colors->is('Blauw op donkerblauw',
	'nok-bg-darkblue nok-text-white',
	'nok-bg-darkerblue nok-text-white'
);
?>

<nok-section class="<?= $section_colors ?>">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
        <nok-square-block class="horizontal nok-p-xl-4 layout-<?= $c->layout_offset->attr() ?> <?= $block_colors ?>" data-shadow="true">
            <div class="nok-square-block__icon">
				<?= Assets::getIcon($c->icon->raw()) ?>
            </div>
            <h2 class="nok-fs-6 nok-square-block__heading"><?= $c->title() ?></h2>
            <div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column"><?= $c->content(); ?></div>

			<?php if ($c->has('button_url')): ?>
                <a role="button" href="<?= $c->button_url->url() ?>" class="nok-button nok-align-self-end nok-bg-white nok-text-darkblue fill-mobile">
                    <span><?= $c->button_text ?></span><?= Assets::getIcon('ui_arrow-right-long', 'nok-text-lightblue') ?>
                </a>
			<?php endif; ?>
        </nok-square-block>
    </div>
</nok-section>