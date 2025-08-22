<?php
/**
 * Template Name: Ervaringen text block
 * Description: A block with a title, content, and ervaringen block carousel.
 * Slug: nok-ervaringen-text-block
 *  Custom Fields:
 * - layout:select(left|right)
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Transparant::nok-text-darkerblue nok-dark-text-white)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::var(--nok-body--lighter)|Uit::transparent)
 */

use NOK2025\V1\Helpers;
$featuredImage = Helpers::get_featured_image();

$page_part_fields['layout'] = empty($page_part_fields['layout']) ? 'left' : $page_part_fields['layout'];

$left = $page_part_fields['layout'] === 'left';

$default_circle_color = 'var(--nok-body--lighter)';
$circle_color         = ( $page_part_fields['circle_color'] ?? "") !== "" ? $page_part_fields['circle_color'] : $default_circle_color;

$default_colors = 'nok-text-darkerblue';
$colors = ($page_part_fields['colors'] ?? "") !== "" ? $page_part_fields['colors'] : $default_colors;
?>
    <nok-section class="circle circle-<?=$page_part_fields['layout'];?>"
    style="--circle-background-color:<?=$circle_color;?>;">
        <div class="nok-section__inner triple-margin <?= $colors; ?>">
            <article class="nok-layout-grid nok-columns-6 nok-align-items-center nok-column-gap-3">
                <div class="nok-column-first-2 nok-layout-flex-column nok-align-items-stretch nok-fs-2">
	                <?php the_title(str_contains($page_part_fields['circle_color'], 'dark') ? '<h1 class="nok-text-white">' : '<h1>', '</h1>'); ?>
	                <?php the_content(); ?>
                    <div class="nok-layout-flex-row nok-column-gap-1">
                        <button class="nok-bg-body--darker nok-text-contrast" data-scroll-target="ervaringen-scroller" data-scroll-action="backward">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 35 12" width="35" height="12" stroke="currentColor"
                            style="stroke-linecap: round; stroke-linejoin: round;">
                            <path d="M 0,5 L 33,5 M 0,5 L 6,10 M 0,5 L 6,0" data-name="Left"></path>
                            </svg>
                        </button>
                        <button class="nok-bg-body--darker nok-text-contrast" data-scroll-target="ervaringen-scroller" data-scroll-action="forward">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 35 12" width="35" height="12" stroke="currentColor"
                                 style="stroke-linecap: round; stroke-linejoin: round;">
                                <path d="M 33,5 L 0,5 M 33,5 L 27,10 M 33,5 L 27,0" data-name="Right"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="nok-column-last-3">
                    <div class="nok-scrollable__horizontal nok-subtle-shadow-compensation" data-scroll-snapping="true" data-draggable="true" id="ervaringen-scroller"
                         data-autoscroll="false">

                        <nok-square-block class="nok-bg-white nok-alpha-10 nok-p-3" data-shadow="true">
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
                                   class="nok-button nok-justify-self-start nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
                                    Lees het verhaal
                                    </svg>
                                </a>
                                <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                            </div>
                        </nok-square-block>
                        <nok-square-block class="nok-bg-white nok-alpha-10 nok-p-3" data-shadow="true">
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
                                   class="nok-button nok-justify-self-start nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
                                    Lees het verhaal
                                    </svg>
                                </a>
                                <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                            </div>
                        </nok-square-block>
                        <nok-square-block class="nok-bg-white nok-alpha-10 nok-p-3" data-shadow="true">
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
                                   class="nok-button nok-justify-self-start nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
                                    Lees het verhaal
                                    </svg>
                                </a>
                                <img class="nok-square-block__thumbnail" src="https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg">
                            </div>
                        </nok-square-block>
                    </div>
                </div>

                <?php if ( !$left ) : ?>
                <?php else : ?>
                <?php endif; ?>
            </article>
        </div>
    </nok-section>

<?php