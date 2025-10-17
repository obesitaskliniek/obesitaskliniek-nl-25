<?php
/**
 * Template Name: Block Carousel
 * Description: Displays title, content, and a horizontally scrollable carousel of blocks
 * Slug: nok-block-carousel
 * Custom Fields:
 * - blocks:repeater
 * - read_more:text!default(Lees verder)
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)!page-editable!default(nok-bg-darkerblue nok-text-white)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
$c = $context;
?>

<nok-section class="<?= $c->colors ?>">
    <div class="nok-section__inner--stretched">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
				<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
                <div class="nok-text-content"><?php the_content(); ?></div>

                <!-- Component: drag-scrollable block carousel -->
                <div class="nok-mt-2 nok-align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__3-column
            nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="true">
						<?php $x = 6; while ($x--) : ?>
                            <nok-square-block class="nok-bg-darkblue nok-text-white">
                                <div class="nok-square-block__icon">
									<?= Assets::getIcon('nok_leefstijl'); ?>
                                </div>
                                <h2 class="nok-square-block__heading">
                                    Een titeltekst met variabele lengte <?= $x; ?>
                                </h2>
                                <p class="nok-square-block__text">
                                    Aenean ac feugiat nibh. Praesent venenatis non nibh vitae pretium. Suspendisse euismod
                                    blandit lorem vel mattis. Pellentesque ultrices velit at nisl placerat faucibus.
                                </p>
                                <a class="nok-square-block__link" href="#">
	                                <?= $c->read_more ?> <?= Assets::getIcon('ui_arrow-right-longer'); ?>
                                </a>
                            </nok-square-block>
						<?php endwhile; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>