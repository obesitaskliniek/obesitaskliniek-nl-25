<?php
/**
 * Template Name: Step Visual
 * Description: A split-content page part for representing a step with a visual
 * Slug: nok-step-visual
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text
 * - button_blauw_text:text!default(Lees meer)
 * - button_blauw_url:url
 * - layout:select(left|right)!page-editable!default(left)
 * - colors:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white)!page-editable
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - linked_section:checkbox!default(true)!descr[Link deze page part met een volgend (identiek) "Step visual" page part dmv een verbindend lijntje]!page-editable
 * - video_lq:url!page-editable!descr[Video LQ (achtergrond, zonder geluid)]
 * - video_hq:url!page-editable!descr[Video HQ (fullscreen, met geluid)]
 * - video_poster:url!page-editable!descr[Video poster afbeelding URL]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;
$left = $c->layout->is('left');
$featuredImage = Helpers::get_featured_image();

// Video support
$has_video = $c->has('video_lq');
$video_lq = $c->has('video_lq') ? $c->video_lq->url() : '';
$video_hq = $c->has('video_hq') ? $c->video_hq->url() : $video_lq;
$video_poster_url = $c->has('video_poster') ? $c->video_poster->url() : '';
$video_title = esc_attr(get_the_title());
?>

<nok-section class="<?= $c->linked_section->isTrue('linked'); ?> <?= $c->colors ?>">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
		<?php if ($left) : ?>
            <div class="nok-align-self-stretch
                            text-start
                            nok-layout-grid overlap-middle offset--1 nok-columns-6 no-overlap-to-lg nok-column-offset-0
                            nok-align-items-center">
                <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0 nok-z-2" data-shadow="true">
                    <div class="nok-square-block__heading">
                        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2">
							<?= $c->tagline ?>
                        </h2>
                        <h2 class="nok-fs-6"><?= $c->title() ?></h2>
                    </div>
                    <div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column">
						<?= $c->content(); ?>
                    </div>
					<?php if ($c->has('button_blauw_url')) : ?>
                        <a role="button" href="<?= $c->button_blauw_url->url() ?>" class="nok-button nok-justify-self-start
                    nok-bg-darkblue nok-text-contrast fill-mobile" tabindex="0">
                            <span><?= $c->button_blauw_text ?></span><?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
					<?php endif; ?>
                </nok-square-block>
				<?php if ($has_video) : ?>
                    <div class="nok-video-background nok-rounded-border-large nok-invisible-to-lg nok-h-100 nok-z-1"
                         data-requires="./nok-video.mjs"
                         data-video-lq="<?= esc_url($video_lq) ?>"
                         data-video-hq="<?= esc_url($video_hq) ?>">
                        <video muted loop playsinline preload="none"
                               aria-label="Achtergrondvideo: <?= $video_title ?>"
                               <?php if ($video_poster_url) : ?>poster="<?= esc_url($video_poster_url) ?>"<?php endif; ?>>
                            <source src="<?= esc_url($video_lq) ?>" type="video/mp4">
                        </video>
                        <button type="button"
                                class="nok-video-background__play-trigger nok-bg-darkestblue"
                                aria-label="Video fullscreen afspelen"
                                data-video-play>
                        </button>
                    </div>
				<?php else : ?>
                    <div class="nok-image-cover nok-rounded-border-large nok-invisible-to-lg nok-h-100 nok-z-1">
						<?= $featuredImage ?>
                    </div>
				<?php endif; ?>
            </div>
		<?php else : ?>
            <div class="nok-align-self-stretch
                        text-starth
                        nok-layout-grid overlap-middle offset--1 nok-columns-6 no-overlap-to-lg nok-column-offset-1
                        nok-align-items-center">
				<?php if ($has_video) : ?>
                    <div class="nok-video-background nok-rounded-border-large nok-invisible-to-lg nok-h-100"
                         data-requires="./nok-video.mjs"
                         data-video-lq="<?= esc_url($video_lq) ?>"
                         data-video-hq="<?= esc_url($video_hq) ?>">
                        <video muted loop playsinline preload="none"
                               aria-label="Achtergrondvideo: <?= $video_title ?>"
                               <?php if ($video_poster_url) : ?>poster="<?= esc_url($video_poster_url) ?>"<?php endif; ?>>
                            <source src="<?= esc_url($video_lq) ?>" type="video/mp4">
                        </video>
                        <button type="button"
                                class="nok-video-background__play-trigger nok-bg-darkestblue"
                                aria-label="Video fullscreen afspelen"
                                data-video-play>
                        </button>
                    </div>
				<?php else : ?>
                    <div class="nok-image-cover nok-rounded-border-large nok-invisible-to-lg nok-h-100">
						<?= $featuredImage ?>
                    </div>
				<?php endif; ?>
                <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-shadow="true">
                    <div class="nok-square-block__heading">
                        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2">
							<?= $c->tagline ?>
                        </h2>
                        <h2 class="nok-fs-6"><?= $c->title() ?></h2>
                    </div>
                    <div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column">
						<?= $c->content(); ?>
                    </div>
					<?php if ($c->has('button_blauw_url')) : ?>
                        <a role="button" href="<?= $c->button_blauw_url->url() ?>" class="nok-button nok-justify-self-start
                nok-bg-darkblue nok-text-contrast fill-mobile" tabindex="0">
                            <span><?= $c->button_blauw_text ?></span><?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
					<?php endif; ?>
                </nok-square-block>
            </div>
		<?php endif; ?>
    </div>
</nok-section>