<?php
/**
 * Template Name: CTA block
 * Description: Call-to-action block with icon, title, content and optional button in horizontal layout
 * Slug: nok-cta-block
 * Custom Fields:
 * - layout_offset:select(left|balanced)!default(left)
 * - colors:select(Blauw op transparant|Blauw op donkerblauw)!page-editable!default(Blauw op transparant)
 * - icon:icon-selector!page-editable!default(ui_arrow-right-long)
 * - button_text:text
 * - button_url:url
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
    <div class="nok-section__inner">
        <nok-square-block class="horizontal nok-p-xl-4 layout-<?= $c->layout_offset ?> <?= $block_colors ?>" data-shadow="true">
            <div class="nok-square-block__icon">
				<?= Assets::getIcon($c->icon->raw()) ?>
            </div>
			<?php the_title('<h1 class="nok-square-block__heading nok-fs-5">', '</h1>'); ?>
            <div class="nok-square-block__text"><?php the_content(); ?></div>

			<?php if ($c->has('button_url')): ?>
                <a role="button" href="<?= $c->button_url->url() ?>" class="nok-button nok-align-self-end nok-bg-white nok-text-darkblue fill-mobile">
					<?= $c->button_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-lightblue') ?>
                </a>
			<?php endif; ?>
        </nok-square-block>
    </div>
</nok-section>