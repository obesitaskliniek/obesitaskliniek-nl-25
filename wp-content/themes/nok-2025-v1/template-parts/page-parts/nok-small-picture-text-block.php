<?php
/**
 * Template Name: Small simple picture text block
 * Description: A basic block with a title, image and content.
 * Slug: nok-small-picture-text-block
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text
 * - button_text:text!default(Lees meer)
 * - button_url:link
 * - layout:select(left|right)!page-editable!default(left)
 * - perspective:checkbox!default(false)!descr[Afbeelding 3D draaien]!page-editable
 * - achtergrondkleur:color-selector(backgrounds-simple)!page-editable
 * - tekstkleur:color-selector(text-extended)!page-editable!default(nok-text-darkerblue nok-dark-text-contrast)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - video:url!page-editable!descr[Video URL (vervangt afbeelding)]
 * - video_poster:url!page-editable!descr[Video poster afbeelding URL]
 * - video_start:text!page-editable!descr[Video starttijd in seconden (bijv. 2.5)]
 * - autoplay:select(Automatisch::visibility|Klik om af te spelen::click|Klik om fullscreen af te spelen::off)!default(visibility)!descr[Autoplay gedrag voor achtergrondvideo]!page-editable
 * - hide_title:checkbox!page-editable!descr[Verberg de sectietitel]
 * - lightbox:checkbox!page-editable!descr[Afbeelding vergroten bij klikken]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;
$left = $c->layout->is('left');
$order = $left ? 1 : 2;
$featuredImage = Helpers::get_featured_image('nok-rounded-border-large');

// Lightbox support — get full-size URL when enabled and image exists
$lightbox_src = '';
if ( $c->lightbox->isTrue() && has_post_thumbnail() ) {
	$lightbox_src = wp_get_attachment_image_url( get_post_thumbnail_id(), 'full' );
}

// Video support
$has_video = $c->has('video');
$video_url = $has_video ? $c->video->url() : '';
$video_poster = $c->has('video_poster') ? $c->video_poster->url() : '';
$video_start = $c->has('video_start') ? floatval($c->video_start->raw()) : 0;
$autoplay = $c->has('autoplay') ? $c->autoplay->raw() : 'visibility';
?>
<nok-section class="<?= $c->achtergrondkleur ?>">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">

        <article class="nok-align-self-stretch
                        <?= $c->tekstkleur ?> nok-dark-text-contrast
                        text-start
                        nok-layout-grid
                        fill-fill nok-columns-to-lg-1 nok-column-gap-3
                        nok-align-items-start">

            <div class="nok-order-<?= $order ?> nok-layout-flex-column nok-align-items-start">
				<?php if ($c->has('tagline')) : ?>
                    <h2 class="nok-fs-4 nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-mb-0">
						<?= $c->tagline ?>
                    </h2>
				<?php endif; ?>
                <?php if (!$c->hide_title->isTrue()) : ?>
                <h2 class="nok-fs-6 nok-mb-1"><?= $c->title() ?></h2>
                <?php endif; ?>
				<?= $c->content(); ?>
				<?php if ($c->has('button_url')) : ?>
                    <a role="button" href="<?= $c->button_url->link() ?>"
                       class="nok-button nok-align-self-to-sm-stretch nok-bg-darkblue nok-text-contrast fill-mobile nok-mt-1">
						<span><?= $c->button_text ?></span><?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                    </a>
				<?php endif; ?>
            </div>
            <?php if ($has_video) : ?>
                <div class="nok-video-background nok-rounded-border-large nok-order-<?= ($order % 2) + 1 ?>"
                     data-requires="./nok-video.mjs"
                     data-video-autoplay="<?= esc_attr($autoplay) ?>"
                     <?php if ($video_start > 0) : ?>data-video-start="<?= esc_attr($video_start) ?>"<?php endif; ?>>
                    <video muted loop playsinline preload="none"
                           aria-label="Achtergrondvideo: <?= esc_attr(get_the_title()) ?>"
                           <?php if ($video_poster) : ?>poster="<?= esc_url($video_poster) ?>"<?php endif; ?>>
                        <source src="<?= esc_url($video_url) ?>" type="video/mp4">
                    </video>
                </div>
            <?php else : ?>
                <div class="nok-image-cover <?= $c->perspective->isTrue('nok-image-perspective'); ?> nok-order-<?= ($order % 2) + 1 ?>"
                    <?php if ( $lightbox_src ) : ?>
                         data-requires="./nok-lightbox.mjs"
                         data-lightbox-src="<?= esc_url( $lightbox_src ) ?>"
                    <?php endif; ?>>
                    <?= $featuredImage ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
</nok-section>