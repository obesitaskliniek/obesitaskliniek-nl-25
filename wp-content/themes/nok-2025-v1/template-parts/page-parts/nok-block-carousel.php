<?php
/**
 * Template Name: Block Carousel
 * Description: Displays title, content, and a horizontally scrollable carousel of blocks
 * Slug: nok-block-carousel
 * Custom Fields:
 * - colors:color-selector(section-colors)!page-editable!default(nok-bg-darkerblue nok-text-contrast)
 * - block_colors:color-selector(block-colors)!page-editable!default(nok-bg-darkerblue nok-text-white)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - alternatieve_layout:checkbox!default(false)!page-editable!descr[Gebruik alternatieve layout]
 * - aantal_blocks:select(2|3|4)!default(3)!descr[Aantal blokken dat in beeld is]
 * - shuffle_blocks:checkbox!default(false)!descr[Willekeurige volgorde?]
 * - read_more:text!default(Lees verder)
 * - blocks:repeater(icon:icon-selector,title:text,content:textarea,link_url:link)
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\PageParts\FieldValue;

$c = $context;

$blocks = $c->blocks->json(array_fill(0, 6, [
	'icon' => 'nok_leefstijl',
	'title' => 'Een titeltekst met variabele lengte',
	'content' => 'Aenean ac feugiat nibh. Praesent venenatis non nibh vitae pretium. Suspendisse euismod blandit lorem vel mattis. Pellentesque ultrices velit at nisl placerat faucibus.',
	'link_url' => '#'
]));
?>

<nok-section class="<?= $c->colors ?>">
    <div class="nok-section__inner--stretched">
        <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">

            <article class="nok-layout-grid nok-columns-10 nok-align-items-start nok-column-gap-3">
                <?php if (!$c->hide_title->isTrue()) : ?>
                <h2 class="nok-fs-6 nok-align-self-stretch nok-mb-2"><?= $c->title() ?></h2>
                <?php endif; ?>

                <div class="nok-layout-grid nok-layout-grid__1-column nok-text-content <?= $c->alternatieve_layout->is(true,'nok-align-self-stretch nok-column-first-xl-4', 'nok-align-self-stretch'); ?>"><?= $c->content(); ?></div>

                <!-- Component: drag-scrollable block carousel -->
                <div class="<?= $c->alternatieve_layout->is(true,'nok-align-self-stretch nok-column-last-xl-6', 'nok-mt-2 nok-align-self-stretch'); ?>">
                    <div class="nok-layout-grid nok-layout-grid__<?= $c->alternatieve_layout->is(true, '2', $c->aantal_blocks->raw()); ?>-column
            nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="true" <?= $c->shuffle_blocks->isTrue() ? 'data-nok-shuffle' : '' ?>>
						<?php foreach ($blocks as $block) : ?>
                            <nok-square-block class="<?= $c->block_colors ?>">
								<?php if (!empty($block['icon'])) : ?>
                                    <div class="nok-square-block__icon">
										<?= Assets::getIcon(esc_attr($block['icon'])); ?>
                                    </div>
								<?php endif; ?>
								<?php if (!empty($block['title'])) : ?>
                                    <h2 class="nok-square-block__heading">
										<?= esc_html($block['title']); ?>
                                    </h2>
								<?php endif; ?>
								<?php if (!empty($block['content'])) : ?>
                                    <p class="nok-square-block__text">
										<?= esc_html($block['content']); ?>
                                    </p>
								<?php endif; ?>
								<?php $block_link_href = FieldValue::resolve_link( $block['link_url'] ); ?>
							<?php if ( $block_link_href ) : ?>
                                    <a class="nok-square-block__link" href="<?= $block_link_href; ?>">
										<?= $c->read_more ?> <?= Assets::getIcon('ui_arrow-right-longer'); ?>
                                    </a>
								<?php endif; ?>
                            </nok-square-block>
						<?php endforeach; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>