<?php
/**
 * Template Name: Small simple picture text block
 * Description: A basic block with a title, image and content.
 * Slug: nok-small-picture-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text
 * - button_text:text!default(Lees meer)
 * - button_url:url
 * - layout:select(left|right)!page-editable!default(left)
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Transparant::)!page-editable
 * - tekstkleur:select(Standaard::nok-text-darkerblue|Contrast::nok-text-contrast|Wit::nok-text-white|Zwart::nok-text-black)!page-editable!default(nok-text-darkerblue)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;
$left = $c->layout->is('left');
$order = $left ? 1 : 2;
$featuredImage = Helpers::get_featured_image();
?>
<nok-section class="<?= $c->achtergrondkleur ?>">
    <div class="nok-section__inner">

        <article class="nok-align-self-stretch
                        <?= $c->tekstkleur ?>
                        text-start
                        nok-layout-grid
                        fill-fill nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">

            <div class="nok-order-<?= $order ?>">
				<?php if ($c->has('tagline')) : ?>
                    <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-4 nok-mb-0_5">
						<?= $c->tagline ?>
                    </h2>
				<?php endif; ?>
				<?php the_title('<h1 class="nok-fs-giant nok-mb-1">', '</h1>'); ?>
				<?php the_content(); ?>
				<?php if ($c->has('button_url')) : ?>
                    <a role="button" href="<?= $c->button_url->url() ?>"
                       class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile nok-mt-1">
						<?= $c->button_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                    </a>
				<?php endif; ?>
            </div>
            <div class="cover-image nok-rounded-border-large nok-order-<?= ($order % 2) + 1 ?>">
				<?= $featuredImage ?>
            </div>
        </article>
    </div>
</nok-section>