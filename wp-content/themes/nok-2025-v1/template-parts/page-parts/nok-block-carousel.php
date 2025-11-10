<?php
/**
 * Template Name: Block Carousel
 * Description: Displays title, content, and a horizontally scrollable carousel of blocks
 * Slug: nok-block-carousel
 * Custom Fields:
 * - blocks:repeater(icon:icon-selector,title:text,content:textarea,link_url:url)
 * - aantal_blocks:select(2|3|4)!default(3)!descr[Aantal blokken dat in beeld is]
 * - read_more:text!default(Lees verder)
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)!page-editable!default(nok-bg-darkerblue nok-text-white)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

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

            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
				<?php the_title('<h2 class="nok-fs-5">', '</h1>'); ?>
                <div class="nok-text-content"><?php the_content(); ?></div>

                <!-- Component: drag-scrollable block carousel -->
                <div class="nok-mt-2 nok-align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__<?= $c->aantal_blocks->raw(); ?>-column
            nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="true">
						<?php foreach ($blocks as $block) : ?>
                            <nok-square-block class="nok-bg-darkblue nok-text-white">
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
								<?php if (!empty($block['link_url'])) : ?>
                                    <a class="nok-square-block__link" href="<?= esc_url($block['link_url']); ?>">
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