<?php
/**
 * Template Name: Plain text block
 * Description: A very basic text block with title and content.
 * Slug: nok-plain-text-block
 * Custom Fields:
 * - layout:select(left|center|right)!page-editable!default(center)
 * - collapse_bottom:checkbox!page-editable
 * - lettergrootte:select(Vergroot::nok-fs-2|Normaal::)!page-editable!default(Vergroot)
 * - colors:select(Transparant::nok-text-contrast|Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(Transparant)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

$c = $context;
?>

<nok-section class="<?= $c->colors ?> <?= $c->collapse_bottom->isTrue('collapse-bottom', '') ?>">
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__1-column nok-justify-items-<?= $c->layout->attr() ?> nok-column-gap-3 text-<?= $c->layout->attr() ?>">
			<?php the_title('<h1>', '</h1>'); ?>
            <div class="<?= $c->lettergrootte ?> nok-text-wrap-balance"><?php the_content(); ?></div>
        </article>
    </div>
</nok-section>