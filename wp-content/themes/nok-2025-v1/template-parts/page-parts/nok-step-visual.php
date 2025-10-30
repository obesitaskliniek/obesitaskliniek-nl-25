<?php
/**
 * Template Name: Step Visual
 * Description: A split-content page part for representing a step with a visual
 * Slug: nok-step-visual
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text
 * - button_blauw_text:text!default(Lees meer)
 * - button_blauw_url:url
 * - layout:select(left|right)!page-editable!default(left)
 * - colors:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white)!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;
$left = $c->layout->is('left');
$featuredImage = Helpers::get_featured_image();
?>

<nok-section class="linked <?= $c->colors ?>">
    <div class="nok-section__inner">
		<?php if ($left) : ?>
            <div class="nok-align-self-stretch
                            text-start
                            nok-layout-grid overlap-middle offset--1 nok-columns-6 no-overlap-to-lg nok-column-offset-0
                            nok-align-items-center">
                <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-shadow="true">
                    <div class="nok-square-block__heading">
                        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2">
							<?= $c->tagline ?>
                        </h2>
						<?php the_title('<h1>', '</h1>'); ?>
                    </div>
                    <div class="nok-square-block__text">
						<?php the_content(); ?>
                    </div>
					<?php if ($c->has('button_blauw_url')) : ?>
                        <a role="button" href="<?= $c->button_blauw_url->url() ?>" class="nok-button nok-justify-self-start
                    nok-bg-darkblue nok-text-contrast fill-mobile" tabindex="0">
							<?= $c->button_blauw_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
					<?php endif; ?>
                </nok-square-block>
                <div class="nok-image-cover nok-rounded-border-large nok-invisible-to-lg nok-h-100">
					<?= $featuredImage ?>
                </div>
            </div>
		<?php else : ?>
            <div class="nok-align-self-stretch
                        text-start
                        nok-layout-grid overlap-middle offset--1 nok-columns-6 no-overlap-to-lg nok-column-offset-1
                        nok-align-items-center">
                <div class="nok-image-cover nok-rounded-border-large nok-invisible-to-lg nok-h-100">
					<?= $featuredImage ?>
                </div>
                <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-shadow="true">
                    <div class="nok-square-block__heading">
                        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2">
							<?= $c->tagline ?>
                        </h2>
						<?php the_title('<h1>', '</h1>'); ?>
                    </div>
                    <div class="nok-square-block__text">
						<?php the_content(); ?>
                    </div>
					<?php if ($c->has('button_blauw_url')) : ?>
                        <a role="button" href="<?= $c->button_blauw_url->url() ?>" class="nok-button nok-justify-self-start
                nok-bg-darkblue nok-text-contrast fill-mobile" tabindex="0">
							<?= $c->button_blauw_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
					<?php endif; ?>
                </nok-square-block>
            </div>
		<?php endif; ?>
    </div>
</nok-section>