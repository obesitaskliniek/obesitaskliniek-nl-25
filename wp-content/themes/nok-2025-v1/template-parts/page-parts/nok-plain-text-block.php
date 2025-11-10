<?php
/**
 * Template Name: Plain text block
 * Description: A very basic text block with title and content.
 * Slug: nok-plain-text-block
 * Custom Fields:
 * - layout:select(left|center|right)!page-editable!default(center)
 * - collapse_bottom:checkbox!page-editable
 * - lettergrootte:select(Vergroot::nok-fs-2|Normaal::)!page-editable!default(Vergroot)
 * - colors:select(Transparant::nok-text-darkerblue nok-dark-text-contrast|Body::nok-bg-body nok-text-darkerblue nok-dark-text-contrast|Wit::nok-bg-white nok-text-darkerblue|Blauw::nok-bg-darkblue nok-text-contrast|Donkerblauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(Transparant)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

$c = $context;
?>

<nok-section class="<?= $c->colors ?> <?= $c->collapse_bottom->isTrue('collapse-bottom', '') ?>">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
        <article class="nok-layout-flex-column nok-align-items-<?= $c->layout->attr() ?> nok-column-gap-3 text-<?= $c->layout->attr() ?>">
			<?php the_title('<h1>', '</h1>'); ?>

            <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-last-xl-3 nok-column-last-lg-4 nok-text-wrap-balance nok-order-1
                        <?= $c->lettergrootte ?>">
                <?php the_content(); ?>
            </div>
        </article>
    </div>
</nok-section>