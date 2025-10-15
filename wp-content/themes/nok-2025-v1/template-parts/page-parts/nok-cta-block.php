<?php
/**
 * Template Name: CTA block
 * Description: A basic CTA (call-to-action) text block with title, content and optional button.
 * Slug: nok-cta-block
 * Custom Fields:
 * - layout_offset:select(left|balanced),
 * - colors:select(Blauw op transparant|Blauw op donkerblauw)!page-editable,
 * - button_text:text,
 * - button_url:url,
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$layout = $context->has('layout_offset') ? $context->get('layout_offset') : 'left';

//default colors
$section_colors = '';
$block_colors = 'nok-bg-darkerblue nok-text-white';

if ($context->get('colors') === "Blauw op donkerblauw") {
	//dark blue colors
	$section_colors = 'nok-bg-darkerblue';
	$block_colors = 'nok-bg-darkblue nok-text-white';
}
?>

<nok-section class="<?= $section_colors; ?>">
    <div class="nok-section__inner">
        <nok-square-block class="horizontal nok-p-xl-4 layout-<?= $layout; ?> <?= $block_colors; ?>" data-shadow="true">
            <div class="nok-square-block__icon">
				<?= Assets::getIcon('arrow-right-long'); ?>
            </div>
			<?php the_title('<h1 class="nok-square-block__heading nok-fs-5">', '</h2>'); ?>
            <div class="nok-square-block__text"><?php the_content(); ?></div>

			<?php if ($context->has('button_url')) : ?>
                <a role="button" href="<?= $context->get_esc_url('button_url'); ?>" class="nok-button nok-align-self-end nok-bg-white nok-text-darkblue fill-mobile"><?= $context->get_esc_html('button_text'); ?> <?= Assets::getIcon('arrow-right-long', 'nok-text-lightblue'); ?></a>
			<?php endif; ?>
        </nok-square-block>
    </div>
</nok-section>