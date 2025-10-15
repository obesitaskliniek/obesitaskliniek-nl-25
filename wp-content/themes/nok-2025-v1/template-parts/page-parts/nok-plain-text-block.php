<?php
/**
 * Template Name: Plain text block
 * Description: A very basic text block with title and content.
 * Slug: nok-plain-text-block
 * Custom Fields:
 * - layout:select(left|center|right)
 * - collapse_bottom:checkbox
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

$layout = $context->has('layout') ? $context->get('layout') : 'center';

?>

<nok-section <?= ($context->get('collapse_bottom') === "1") ? 'class="collapse-bottom"' : ''; ?>>
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__1-column nok-justify-items-<?= $layout; ?> nok-column-gap-3 text-<?= $layout; ?>
                        nok-text-darkerblue nok-dark-text-white">
			<?php the_title('<h1>', '</h1>'); ?>
            <div class="nok-fs-2 nok-text-wrap-balance"><?php the_content(); ?></div>
        </article>
    </div>
</nok-section>