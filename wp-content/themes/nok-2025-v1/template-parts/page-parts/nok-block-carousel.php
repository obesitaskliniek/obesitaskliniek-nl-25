<?php
/**
 * Template Name: Block Carousel
 * Slug: nok-block-carousel
 * Custom Fields:
 * - blocks:repeater
 * - read_more:text
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$default_colors = 'nok-bg-darkerblue nok-text-white';
$colors = $context->has('colors') ? $context->get('colors') : $default_colors;
?>

<nok-section class="<?= $colors; ?>">
    <div class="nok-section__inner--stretched">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
				<?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
                <div class="nok-text-content"><?php the_content(); ?></div>

                <!-- Component: drag-scrollable blokkengroep -->
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
									<?= $context->get_esc_html('read_more', 'Lees verder'); ?> <?= Assets::getIcon('ui_arrow-right-longer'); ?>
                                </a>
                            </nok-square-block>
						<?php endwhile; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>