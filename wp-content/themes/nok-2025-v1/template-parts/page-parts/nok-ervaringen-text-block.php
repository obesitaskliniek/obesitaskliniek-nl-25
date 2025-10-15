<?php
/**
 * Template Name: Ervaringen text block
 * Description: A block with a title, content, and ervaringen block carousel.
 * Slug: nok-ervaringen-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - layout:select(left|right)
 * - achtergrond:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-white|Transparant::nok-text-darkerblue nok-dark-text-white)
 * - tekst:select(Blauw::nok-text-darkerblue nok-text-dark-contrast|Wit::nok-text-white nok-text-dark-contrast)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::var(--nok-body--lighter)|Uit::transparent)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
$featuredImage = Helpers::get_featured_image();

$layout = $context->has('layout') ? $context->get('layout') : 'left';
$left = $layout === 'left';

$default_circle_color = '';
$circle_color = $context->has('circle_color') ? $context->get('circle_color') : $default_circle_color;
$circle_color = $circle_color !== '' ? '--circle-background-color:'.$circle_color : '';

$default_text_color = 'nok-text-darkerblue';
$default_bg_color = '';
$text_color = $context->has('tekst') ? $context->get('tekst') : $default_text_color;
$bg_colors = $context->has('achtergrond') ? $context->get('achtergrond') : $default_bg_color;
?>
<nok-section class="circle <?= $bg_colors; ?> <?=$text_color;?>"
             style="<?=$circle_color;?>;--circle-offset:<?= $left ? 'calc(50vw - (var(--section-max-width) * 0.35))' : 'calc(50vw + (var(--section-max-width) * 0.25))'; ?>;">
    <div class="nok-section__inner triple-margin nok-my-to-lg-4">
        <article class="nok-layout-grid
                            nok-columns-6 nok-columns-to-lg-1
                            nok-align-items-center
                            nok-column-gap-3">
            <div class="nok-align-self-to-lg-stretch nok-column-first-2 nok-layout-flex-column nok-align-items-stretch nok-fs-2">
				<?php the_title('<h1>', '</h1>'); ?>
				<?php the_content(); ?>
                <div class="nok-button-group">
                    <button class="nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast fill-group-column" data-scroll-target="ervaringen-scroller" data-scroll-action="backward">
						<?= Assets::getIcon('arrow-left-longer'); ?>
                    </button>
                    <button class="nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast fill-group-column" data-scroll-target="ervaringen-scroller" data-scroll-action="forward">
						<?= Assets::getIcon('arrow-right-longer'); ?>
                    </button>
                </div>
            </div>

            <div class="nok-align-self-to-lg-stretch nok-column-last-3">
                <div class="nok-scrollable__horizontal nok-subtle-shadow-compensation" data-scroll-snapping="true" data-draggable="true" id="ervaringen-scroller"
                     data-autoscroll="false">

                    <nok-square-block class="nok-bg-white nok-text-darkerblue nok-dark-bg-darkblue nok-dark-text-contrast nok-alpha-10 nok-p-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2>"Wat een fijn traject ben ik aangegaan"</h2>
                        </div>
                        <div class="nok-square-block__text nok-fs-2">
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                            perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo
                            iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem
                            ipsum dolor sit amet, consectetur adipisicing elit!
                        </div>
                        <div class="nok-layout-flex-row space-between">
                            <a role="button" href="#"
                               class="nok-button nok-justify-self-start nok-bg-darkblue nok-text-contrast nok-dark-bg-darkerblue fill-mobile" tabindex="0">
                                Lees het verhaal
                            </a>
                            <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                        </div>
                    </nok-square-block>
                    <nok-square-block class="nok-bg-white nok-text-darkerblue nok-dark-bg-darkblue nok-dark-text-contrast nok-alpha-10 nok-p-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2>"Wat een fijn traject ben ik aangegaan"</h2>
                        </div>
                        <div class="nok-square-block__text nok-fs-2">
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                            perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo
                            iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem
                            ipsum dolor sit amet, consectetur adipisicing elit!
                        </div>
                        <div class="nok-layout-flex-row space-between">
                            <a role="button" href="#"
                               class="nok-button nok-justify-self-start nok-bg-darkblue nok-text-contrast nok-dark-bg-darkerblue fill-mobile" tabindex="0">
                                Lees het verhaal
                            </a>
                            <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                        </div>
                    </nok-square-block>
                    <nok-square-block class="nok-bg-white nok-text-darkerblue nok-dark-bg-darkblue nok-dark-text-contrast nok-alpha-10 nok-p-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2>"Wat een fijn traject ben ik aangegaan"</h2>
                        </div>
                        <div class="nok-square-block__text nok-fs-2">
                            Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                            perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo
                            iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem
                            ipsum dolor sit amet, consectetur adipisicing elit!
                        </div>
                        <div class="nok-layout-flex-row space-between">
                            <a role="button" href="#"
                               class="nok-button nok-justify-self-start nok-bg-darkblue nok-text-contrast nok-dark-bg-darkerblue fill-mobile" tabindex="0">
                                Lees het verhaal
                            </a>
                            <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                        </div>
                    </nok-square-block>
                </div>
            </div>
        </article>
    </div>
</nok-section>