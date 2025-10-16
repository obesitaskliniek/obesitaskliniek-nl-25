<?php
/**
 * Template Name: Small simple picture text block
 * Description: A basic block with a title, image and content.
 * Slug: nok-small-picture-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text,
 * - button_text:text,
 * - button_url:url,
 * - layout:select(left|right)
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Transparant::)
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Zwart::nok-text-black)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$layout = $context->has('layout') ? $context->get('layout') : 'left';
$left = $layout === 'left';

$default_colors = '';
$colors = $context->has('achtergrondkleur') ? $context->get('achtergrondkleur') : $default_colors;

$featuredImage = Helpers::get_featured_image();

$order = $layout === 'left' ? 1 : 2;
?>
<nok-section class="<?= $colors; ?>">
    <div class="nok-section__inner">

        <article class="nok-align-self-stretch
                        <?= $context->get('tekstkleur'); ?>
                        text-start
                        nok-layout-grid
                        fill-fill nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">

            <div class="nok-order-<?= ($order);?>">
				<?php if ($context->has('tagline')) : ?>
                    <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-4 nok-mb-0_5">
						<?= $context->get_esc_html('tagline'); ?>
                    </h2>
				<?php endif; ?>
				<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
	            <?php the_content(); ?>
	            <?php if ($context->has('button_url')) : ?>
                    <a role="button" href="<?= $context->get_esc_url('button_url'); ?>"
                       class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile nok-mt-1">
			            <?= $context->get_esc_html('button_text'); ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                    </a>
	            <?php endif; ?>
            </div>
            <div class="cover-image nok-rounded-border-large nok-order-<?= (($order % 2) + 1);?>">
                <?= $featuredImage; ?>
            </div>
        </article>
    </div>
</nok-section>