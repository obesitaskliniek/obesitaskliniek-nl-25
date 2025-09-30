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
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Transparant::)
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Zwart::nok-text-black)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::|Uit::transparent)
 * - pull_down:checkbox(true)
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$page_part_fields['layout'] = empty($page_part_fields['layout']) ? 'left' : $page_part_fields['layout'];

$left = $page_part_fields['layout'] === 'left';
$pull_down             = !empty($page_part_fields['pull_down']) || $page_part_fields['pull_down'] === '1';

$default_circle_color = '';
$circle_color         = ( $page_part_fields['circle_color'] ?? "") !== "" ? $page_part_fields['circle_color'] : $default_circle_color;
$circle_color =         $circle_color !== '' ? '--circle-background-color:'.$circle_color : '';

$default_colors = '';
$colors = ($page_part_fields['achtergrondkleur'] ?? "") !== "" ? $page_part_fields['achtergrondkleur'] : $default_colors;

$featuredImage = Helpers::get_featured_image($pull_down ? ($left ? 'nok-rounded-border-large-right' : 'nok-rounded-border-large-left') : null);
?>
    <nok-section class="circle <?= $pull_down ? 'pull-down' : ''; ?> <?= $colors; ?>"
    style="<?=$circle_color;?>;--circle-offset:<?= $left ? 'calc(50vw + (var(--section-max-width) * 0.25))' : 'calc(50vw - (var(--section-max-width) * 0.25))'; ?>;">
        <div class="nok-section__inner">
            <article class="nok-align-self-stretch
                        <?= $page_part_fields['tekstkleur'] ;?>
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
	                <?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>
                </div>
                <?php if ( $left ) : ?>
                    <div class="nok-column-first-lg-4
                    pullee
                    stick-to-left-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-0">
                        <?= $featuredImage; ?>
                    </div>
                    <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-last-xl-3 nok-column-last-lg-4 nok-text-wrap-balance nok-order-1"><?php the_content(); ?>
                        <div>
	                        <?php if (!empty($page_part_fields['button_url'])) : ?>
                            <a role="button" href="<?= $page_part_fields['button_url']; ?>"
                               class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
		                        <?= $page_part_fields['button_text']; ?> <?= Assets::getIcon('arrow-right-long', 'nok-text-yellow'); ?>
                            </a>
	                        <?php endif; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-first-xl-3 nok-column-first-lg-4
                        nok-text-wrap-balance nok-order-0"><?php the_content(); ?>
                        <div>
			                <?php if (!empty($page_part_fields['button_url'])) : ?>
                                <a role="button" href="<?= $page_part_fields['button_url']; ?>"
                                   class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile">
					                <?= $page_part_fields['button_text']; ?> <?= Assets::getIcon('arrow-right-long', 'nok-text-yellow'); ?>
                                </a>
			                <?php endif; ?>
                        </div>
                    </div>
                    <div class="
                    nok-column-last-lg-4
                    pullee
                    stick-to-right-viewport-side nok-h-100
                    cover-image nok-rounded-border-large nok-order-1">
		                <?= $featuredImage; ?>
                    </div>
                <?php endif; ?>
            </article>
        </div>
    </nok-section>

<?php