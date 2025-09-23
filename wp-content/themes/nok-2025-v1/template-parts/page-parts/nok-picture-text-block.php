<?php
/**
 * Template Name: Simple picture text block
 * Description: A basic block with a title, image and content. Image 'sticks' to the left or right side of the viewport.
 * Slug: nok-picture-text-block
 *  Custom Fields:
 * - tagline:text,
 * - button_text:text,
 * - button_url:url,
 * - layout:select(left|right)
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Transparant::nok-text-darkerblue)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::var(--bg-color--hover)|Uit::transparent)
 * - pull_down:checkbox(true)
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
$featuredImage = Helpers::get_featured_image();

$page_part_fields['layout'] = empty($page_part_fields['layout']) ? 'left' : $page_part_fields['layout'];

$left = $page_part_fields['layout'] === 'left';
$collapse             = !empty($page_part_fields['pull_down']) || $page_part_fields['pull_down'] === '1';

$default_circle_color = 'var(--text-color--contrast)';
$circle_color         = ( $page_part_fields['circle_color'] ?? "") !== "" ? $page_part_fields['circle_color'] : $default_circle_color;

$default_colors = 'nok-text-darkerblue';
$colors = ($page_part_fields['colors'] ?? "") !== "" ? $page_part_fields['colors'] : $default_colors;
?>
    <nok-section class="circle circle-<?=$left ? 'right' : 'left';?> <?= $collapse ? 'collapse-bottom pull-down' : ''; ?> <?= $colors; ?>"
    style="--circle-background-color:<?=$circle_color;?>;">
        <div class="nok-section__inner">
            <article class=" <?= $collapse ? 'nok-mt-2' : 'nok-my-2'; ?> nok-align-self-stretch
                        text-start
                        nok-layout-grid
                        nok-columns-8 nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">
                <div class="nok-align-self-to-lg-stretch nok-column-first-5 nok-mb-section-padding">
                    <?php if (!empty($page_part_fields['tagline'])) : ?>
                        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-4 nok-mb-0_5">
		                    <?= $page_part_fields['tagline']; ?>
                        </h2>
                    <?php endif; ?>
	                <?php the_title(str_contains($page_part_fields['circle_color'], 'dark') ? '<h1 class="nok-fs-giant nok-text-white">' : '<h1 class="nok-fs-giant">', '</h1>'); ?>
                </div>
                <?php if ( $left ) : ?>
                    <div class="nok-column-first-lg-4
                    stick-to-left-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-0">
                        <?= $featuredImage; ?>
                    </div>
                    <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        <?= str_contains($page_part_fields['circle_color'], 'dark') ? 'nok-text-white' : '';?>
                        nok-column-last-lg-3 nok-text-wrap-balance nok-order-1"><?php the_content(); ?>
                        <div>
	                        <?php if (!empty($page_part_fields['button_url'])) : ?>
                            <a role="button" href="<?= $page_part_fields['button_url']; ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-yellow nok-text-contrast">
		                        <?= $page_part_fields['button_text']; ?> <?= Assets::getIcon('arrow-right-long'); ?>
                            </a>
	                        <?php endif; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        <?= str_contains($page_part_fields['circle_color'], 'dark') ? 'nok-text-white' : '';?>
                        nok-column-first-lg-3
                        nok-text-wrap-balance nok-order-0"><?php the_content(); ?>
                        <div>
			                <?php if (!empty($page_part_fields['button_url'])) : ?>
                                <a role="button" href="<?= $page_part_fields['button_url']; ?>"
                                   class="nok-button nok-align-self-to-sm-stretch nok-bg-yellow nok-text-contrast">
					                <?= $page_part_fields['button_text']; ?> <?= Assets::getIcon('arrow-right-long'); ?>
                                </a>
			                <?php endif; ?>
                        </div>
                    </div>
                    <div class="
                    nok-column-last-lg-4
                    stick-to-right-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-1">
		                <?= $featuredImage; ?>
                    </div>
                <?php endif; ?>
            </article>
        </div>
    </nok-section>

<?php