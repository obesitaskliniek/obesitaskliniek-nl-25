<?php
/**
 * Template Name: Simple picture text block
 * Description: A basic block with a title, image and content. Image 'sticks' to the left or right side of the viewport.
 * Slug: nok-picture-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text,
 * - button_text:text,
 * - button_url:url,
 * - layout:select(left|right)
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Transparant::)
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Zwart::nok-text-black)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::|Uit::transparent)
 * - pull_down:checkbox(true)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$layout = $context->has('layout') ? $context->get('layout') : 'left';
$left = $layout === 'left';
$pull_down = $context->get('pull_down') === '1';

$default_circle_color = '';
$circle_color = $context->has('circle_color') ? $context->get('circle_color') : $default_circle_color;
$circle_color = $circle_color !== '' ? '--circle-background-color:'.$circle_color : '';

$default_colors = '';
$colors = $context->has('achtergrondkleur') ? $context->get('achtergrondkleur') : $default_colors;

$featuredImage = Helpers::get_featured_image($pull_down ? ($left ? 'nok-rounded-border-large-right' : 'nok-rounded-border-large-left') : null);
?>
<nok-section class="circle <?= $pull_down ? 'pull-down' : ''; ?> <?= $colors; ?>"
             style="<?=$circle_color;?>;--circle-offset:<?= $left ? 'calc(50vw + (var(--section-max-width) * 0.25))' : 'calc(50vw - (var(--section-max-width) * 0.25))'; ?>;">
    <div class="nok-section__inner">
        <article class="nok-align-self-stretch
                        <?= $context->get('tekstkleur'); ?>
                        text-start
                        nok-layout-grid
                        nok-columns-8 nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">
            <div class="nok-align-self-to-lg-stretch nok-column-first-5 nok-mb-section-padding">
				<?php if ($context->has('tagline')) : ?>
                    <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-4 nok-mb-0_5">
						<?= $context->get_esc_html('tagline'); ?>
                    </h2>
				<?php endif; ?>
				<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
            </div>
			<?php if ($left) : ?>
                <div class="nok-column-first-lg-4
                    pullee
                    stick-to-left-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-0">
					<?= $featuredImage; ?>
                </div>
                <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-last-xl-3 nok-column-last-lg-4 nok-text-wrap-balance nok-order-1"><?php the_content(); ?>
                    <div>
						<?php if ($context->has('button_url')) : ?>
                            <a role="button" href="<?= $context->get_esc_url('button_url'); ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
								<?= $context->get_esc_html('button_text'); ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
			<?php else : ?>
                <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-first-xl-3 nok-column-first-lg-4
                        nok-text-wrap-balance nok-order-0"><?php the_content(); ?>
                    <div>
						<?php if ($context->has('button_url')) : ?>
                            <a role="button" href="<?= $context->get_esc_url('button_url'); ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
								<?= $context->get_esc_html('button_text'); ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
                <div class="
                    nok-column-last-lg-4
                    pullee
                    stick-to-right-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-1">
					<?= $featuredImage; ?>
                </div>
			<?php endif; ?>
        </article>
    </div>
</nok-section>