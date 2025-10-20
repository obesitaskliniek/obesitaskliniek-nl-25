<?php
/**
 * Template Name: Simple picture text block
 * Description: A basic block with a title, image and content. Image 'sticks' to the left or right side of the viewport.
 * Slug: nok-picture-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text
 * - button_text:text!default(Lees meer)
 * - button_url:url
 * - layout:select(left|right)!page-editable
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Donkerder::nok-bg-body--darker|Transparant::)!page-editable
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Zwart::nok-text-black)!page-editable
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::|Uit::transparent)!page-editable
 * - pull_down:checkbox(true)!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

// Layout direction
$left = $c->layout->is('left');

// Circle color as CSS custom property
$circle_style = $c->circle_color->css_var('circle-background-color');

// Featured image with conditional border class
$border_class = $c->pull_down->isTrue(($left ? 'nok-rounded-border-large-right' : 'nok-rounded-border-large-left'));
$featured_image = Helpers::get_featured_image($border_class);

?>
<nok-section class="circle <?= $c->pull_down->isTrue('pull-down', '') ?> <?= $c->achtergrondkleur ?>"
             style="<?= $circle_style ?>;--circle-offset:<?= $left ? 'calc(50vw + (var(--section-max-width) * 0.25))' : 'calc(50vw - (var(--section-max-width) * 0.25))' ?>;">
    <div class="nok-section__inner">
        <article class="nok-align-self-stretch
                        <?= $c->tekstkleur ?>
                        text-start
                        nok-layout-grid
                        nok-columns-8 nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">
            <div class="nok-align-self-to-lg-stretch nok-column-first-5 nok-mb-section-padding">
				<?php if ($c->has('tagline')) : ?>
                    <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-4 nok-mb-0_5">
						<?= $c->tagline ?>
                    </h2>
				<?php endif; ?>
				<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
            </div>
			<?php if ($left) : ?>
                <div class="nok-column-first-lg-4
                    pullee
                    stick-to-left-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-0">
					<?= $featured_image ?>
                </div>
                <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-last-xl-3 nok-column-last-lg-4 nok-text-wrap-balance nok-order-1">
					<?php the_content(); ?>
                    <div>
						<?php if ($c->has('button_url')) : ?>
                            <a role="button" href="<?= $c->button_url->url() ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
								<?= $c->button_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
			<?php else : ?>
                <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-first-xl-3 nok-column-first-lg-4
                        nok-text-wrap-balance nok-order-0">
					<?php the_content(); ?>
                    <div>
						<?php if ($c->has('button_url')) : ?>
                            <a role="button" href="<?= $c->button_url->url() ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
								<?= $c->button_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
                <div class="
                    nok-column-last-lg-4
                    pullee
                    stick-to-right-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-1">
					<?= $featured_image ?>
                </div>
			<?php endif; ?>
        </article>
    </div>
</nok-section>