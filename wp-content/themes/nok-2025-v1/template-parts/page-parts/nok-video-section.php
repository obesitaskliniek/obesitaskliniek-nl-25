<?php
/**
 * Template Name: Video Section
 * Description: Video embed with play button overlay, title and description
 * Slug: nok-video-section
 * Featured Image Overridable: false
 * Custom Fields:
 * - video_url:url!default(https://www.youtube.com/watch?v=dQw4w9WgXcQ)
 * - video_type:select(YouTube::youtube|Vimeo::vimeo|Self-hosted::self)!default(youtube)
 * - full_section:checkbox!default(true)!descr[Bedek de hele sectie tot max 90% de hoogte van het browserscherm]!page-editable
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Donkerder::nok-bg-body--darker|Transparant::)!page-editable
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Blauw::nok-text-darkerblue)!page-editable
*  - narrow_section:checkbox!default(false)!descr[Smalle sectie - heeft geen invloed als full section aan staat]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @see TODO.md LOW-001, LOW-002 for field parser enhancements
 */

use NOK2025\V1\Helpers;

$c = $context;

// Get video embed HTML
$video_html = '';
if ( $c->has( 'video_url' ) ) {
	$video_url = $c->video_url->raw();

	if ( $c->video_type->is( 'self' ) ) {
		// Self-hosted video
		$video_html = sprintf(
			'<video controls src="%s" preload="metadata"></video>',
			esc_url( $video_url )
		);
	} else {
		// YouTube or Vimeo via oEmbed
		$video_html = wp_oembed_get( $video_url, [ 'width' => 1280 ] );

		// Fallback if oEmbed fails
		if ( ! $video_html ) {
			$video_html = sprintf(
				'<iframe src="%s" frameborder="0" allowfullscreen></iframe>',
				esc_url( $video_url )
			);
		}
	}
}
?>

<nok-section class="<?= $c->achtergrondkleur ?>">
    <?php if ($c->full_section->isTrue()) : ?>

        <?php if ( $video_html ): ?>
            <div class="nok-video-background w-100">
                <?= $video_html ?>
            </div>
        <?php else: ?>
            <div class="nok-video-section__video-wrapper nok-video-section__empty">
                <p>Geen video URL opgegeven</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">
        <article class="nok-video-section__content <?= $c->tekstkleur ?>">

            <div class="nok-video-section__text">
                <h2 class="nok-fs-6"><?= $c->title() ?></h2>
                <div class="nok-fs-body">
                    <?= $c->content(); ?>
                </div>
            </div>

            <?php if ( $video_html ): ?>
                <div class="nok-video-section__video-wrapper">
                    <?= $video_html ?>
                </div>
            <?php else: ?>
                <div class="nok-video-section__video-wrapper nok-video-section__empty">
                    <p>Geen video URL opgegeven</p>
                </div>
            <?php endif; ?>

        </article>
    </div>
    <?php endif; ?>
</nok-section>