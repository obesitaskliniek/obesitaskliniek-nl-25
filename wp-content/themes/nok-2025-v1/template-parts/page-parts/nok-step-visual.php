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
 * - colors:color-selector(step-visual-colors)!page-editable
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - linked_section:checkbox!default(true)!descr[Link deze page part met een volgend (identiek) "Step visual" page part dmv een verbindend lijntje]!page-editable
 * - video_lq:url!page-editable!descr[Video LQ (achtergrond, zonder geluid)]
 * - video_hq:url!page-editable!descr[Video HQ (fullscreen, met geluid)]
 * - video_poster:url!page-editable!descr[Video poster afbeelding URL]
 * - video_start:text!page-editable!descr[Video starttijd in seconden (bijv. 2.5). Laat leeg voor begin.]!page-editable
 * - autoplay:select(Automatisch::visibility|Klik om af te spelen::click|Klik om fullscreen af te spelen::off)!default(visibility)!descr[Autoplay gedrag voor desktop achtergrondvideo]!page-editable
 * - mobile_layout:select(Verbergen::collapse|Stapelen::stack)!page-editable!default(collapse)!descr[Hoe visual (afbeelding/video) tonen op mobiel? Verbergen: visual niet zichtbaar op mobiel (video toont compacte link). Stapelen: visual onder content.]
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
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
$video_start = $c->has('video_start') ? floatval($c->video_start->raw()) : 0;
$autoplay = $c->has('autoplay') ? $c->autoplay->raw() : 'visibility';
$video_title = esc_attr(get_the_title());

// Mobile layout: stack shows visual below content, collapse hides visual (with inline trigger for video)
$mobile_stack = $c->mobile_layout->is('stack');

// Layout-dependent classes
// Z-index classes only needed for left layout (content overlaps visual)
$container_offset = $left ? 'nok-column-offset-0' : 'nok-column-offset-1';
$content_z = $left ? 'nok-z-2' : '';
$visual_z = $left ? 'nok-z-1' : '';

// Capture content block
ob_start();
?>
<nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0 <?= $content_z ?>" data-shadow="true">
    <div class="nok-square-block__heading">
        <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2">
            <?= $c->tagline ?>
        </h2>
        <?php if (!$c->hide_title->isTrue()) : ?>
        <h2 class="nok-fs-6"><?= $c->title() ?></h2>
        <?php endif; ?>
    </div>
    <div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column">
        <?= $c->content(); ?>
    </div>
    <?php if ($has_video && !$mobile_stack) : ?>
        <button type="button"
                class="nok-video-inline-trigger nok-invisible-lg"
                aria-label="Bekijk de video"
                data-requires="./nok-video.mjs"
                data-video-hq="<?= esc_url($video_hq) ?>">
            <?php if ($video_poster_url) : ?>
                <img src="<?= esc_url($video_poster_url) ?>"
                     alt=""
                     class="nok-video-inline-trigger__poster">
            <?php endif; ?>
            <span class="nok-video-inline-trigger__label">
                <?= Assets::getIcon('ui_play', 'nok-video-inline-trigger__icon') ?>
                <span>Bekijk de video</span>
            </span>
        </button>
    <?php endif; ?>
    <?php if ($c->has('button_blauw_url')) : ?>
        <a role="button" href="<?= $c->button_blauw_url->url() ?>" class="nok-button nok-justify-self-start
            nok-bg-darkblue nok-text-contrast fill-mobile" tabindex="0">
            <span><?= $c->button_blauw_text ?></span><?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
        </a>
    <?php endif; ?>
</nok-square-block>
<?php
$content_block = ob_get_clean();

// Capture visual block
// Stack mode: show on mobile (no nok-invisible-to-lg)
// Collapse mode: hide on mobile (add nok-invisible-to-lg)
$visual_mobile_hide = $mobile_stack ? '' : 'nok-invisible-to-lg';
// When stacking with right layout, push visual to bottom on mobile via CSS order
$visual_order_mobile = ($mobile_stack && !$left) ? 'nok-order-to-lg-last' : '';
ob_start();
if ($has_video) : ?>
    <div class="nok-video-background nok-rounded-border-large <?= $visual_mobile_hide ?> <?= $visual_order_mobile ?> nok-h-100 <?= $visual_z ?>"
         data-requires="./nok-video.mjs"
         data-video-lq="<?= esc_url($video_lq) ?>"
         data-video-hq="<?= esc_url($video_hq) ?>"
         data-video-autoplay="<?= esc_attr($autoplay) ?>"
         <?php if ($video_start > 0) : ?>data-video-start="<?= esc_attr($video_start) ?>"<?php endif; ?>>
        <video muted loop playsinline preload="none"
               aria-label="Achtergrondvideo: <?= $video_title ?>"
               <?php if ($video_poster_url) : ?>poster="<?= esc_url($video_poster_url) ?>"<?php endif; ?>>
            <source src="<?= esc_url($video_lq) ?>" type="video/mp4">
        </video>
        <button type="button"
                title="Bekijk de video op volledig scherm"
                class="nok-video-background__play-trigger nok-bg-darkestblue"
                aria-label="Video fullscreen afspelen"
                data-video-play>
        </button>
    </div>
<?php else : ?>
    <div class="nok-image-cover nok-rounded-border-large <?= $visual_mobile_hide ?> <?= $visual_order_mobile ?> nok-h-100 <?= $visual_z ?>">
        <?= $featuredImage ?>
    </div>
<?php endif;
$visual_block = ob_get_clean();
?>

<nok-section class="<?= $c->linked_section->isTrue('linked'); ?> <?= $c->colors ?>">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
        <div class="nok-align-self-stretch
                    text-start
                    nok-layout-grid overlap-middle offset--1 nok-columns-6 no-overlap-to-lg <?= $container_offset ?>
                    nok-align-items-center <?= $mobile_stack ? ('nok-glue-together-to-lg' . ($left ? '' : ' nok-glue-together-to-lg--reversed')) : '' ?>">
<?php if ($left) : ?>
            <?= $content_block ?>
            <?= $visual_block ?>
<?php else : ?>
            <?= $visual_block ?>
            <?= $content_block ?>
<?php endif; ?>
        </div>
    </div>
</nok-section>
