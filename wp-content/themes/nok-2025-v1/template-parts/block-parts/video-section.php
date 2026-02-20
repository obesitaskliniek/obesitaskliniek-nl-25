<?php
/**
 * Block Part: Video Section
 * Description: Video embed with YouTube, Vimeo or self-hosted video
 * Slug: video-section
 * Icon: video-alt3
 * Keywords: video, youtube, vimeo, embed
 * Custom Fields:
 * - video_url:url
 * - video_type:select(YouTube::youtube|Vimeo::vimeo|Self-hosted::self)!default(youtube)
 * - video_hq:url!descr[Video HQ URL voor fullscreen (optioneel)]
 * - video_poster:url!descr[Poster afbeelding URL (optioneel)]
 * - video_start:text!descr[Starttijd in seconden bijv. 2.5 (optioneel)]
 * - autoplay:select(Automatisch::visibility|Klik om af te spelen::click|Klik om fullscreen af te spelen::off)!default(visibility)!descr[Autoplay gedrag voor achtergrondvideo]
 * - full_section:checkbox!default(true)!descr[Bedek de hele sectie tot max 90% de hoogte van het browserscherm]
 * - achtergrondkleur:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Donkerder::nok-bg-body--darker|Transparant::)
 * - tekstkleur:select(Standaard::nok-text-contrast|Wit::nok-text-white|Blauw::nok-text-darkerblue)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie - heeft geen invloed als full section aan staat]
 * - section_title:text
 * - section_description:text
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @var array $attributes Block attributes (includes anchor, align)
 */

$c = $context;

// Video configuration
$video_url      = $c->has( 'video_url' ) ? $c->video_url->url() : '';
$video_hq       = $c->has( 'video_hq' ) ? $c->video_hq->url() : $video_url;
$video_poster   = $c->has( 'video_poster' ) ? $c->video_poster->url() : '';
$video_start    = $c->has( 'video_start' ) ? floatval( $c->video_start->raw() ) : 0;
$autoplay       = $c->has( 'autoplay' ) ? $c->autoplay->raw() : 'visibility';
$is_self_hosted = $c->video_type->is( 'self' );
$is_full_section = $c->full_section->isTrue();

// Get video embed HTML for YouTube/Vimeo (oEmbed)
$video_embed_html = '';
if ( $video_url && ! $is_self_hosted ) {
	$video_embed_html = wp_oembed_get( $video_url, [ 'width' => 1280 ] );

	// Fallback if oEmbed fails
	if ( ! $video_embed_html ) {
		$video_embed_html = sprintf(
			'<iframe src="%s" frameborder="0" allowfullscreen></iframe>',
			esc_url( $video_url )
		);
	}
}

// Build section classes
$section_classes = [ $c->achtergrondkleur->raw() ];
if ( ! empty( $attributes['align'] ) ) {
	$section_classes[] = 'align' . $attributes['align'];
}

$anchor_attr = ! empty( $attributes['anchor'] )
	? ' id="' . esc_attr( $attributes['anchor'] ) . '"'
	: '';
?>

<nok-section class="<?= esc_attr( implode( ' ', array_filter( $section_classes ) ) ) ?>"<?= $anchor_attr ?>>
	<?php if ( $is_full_section ) : ?>

		<?php if ( $video_url && $is_self_hosted ) : ?>
			<div class="nok-video-background w-100"
			     data-requires="./nok-video.mjs"
			     data-video-lq="<?= esc_url( $video_url ) ?>"
			     data-video-hq="<?= esc_url( $video_hq ) ?>"
			     data-video-autoplay="<?= esc_attr( $autoplay ) ?>"
				<?php if ( $video_start > 0 ) : ?>data-video-start="<?= esc_attr( $video_start ) ?>"<?php endif; ?>>
				<video muted loop playsinline preload="none"
				       aria-label="Achtergrondvideo"
					<?php if ( $video_poster ) : ?>poster="<?= esc_url( $video_poster ) ?>"<?php endif; ?>>
					<source src="<?= esc_url( $video_url ) ?>" type="video/mp4">
				</video>
				<button type="button"
				        title="Bekijk de video op volledig scherm"
				        class="nok-video-background__play-trigger nok-bg-darkestblue"
				        aria-label="Video fullscreen afspelen"
				        data-video-play>
				</button>
			</div>
		<?php elseif ( $video_embed_html ) : ?>
			<!-- YouTube/Vimeo: autoplay not supported, showing embed -->
			<div class="nok-video-section__video-wrapper w-100">
				<?= $video_embed_html ?>
			</div>
		<?php else : ?>
			<div class="nok-video-section__video-wrapper nok-video-section__empty">
				<p>Geen video URL opgegeven</p>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="nok-section__inner <?= $c->narrow_section->isTrue( 'nok-section-narrow' ); ?>">
			<article class="nok-video-section__content <?= $c->tekstkleur ?> nok-layout-grid nok-layout-grid__1-column">

				<?php if ( $c->has( 'section_title' ) && $c->section_title->raw() !== '' ) : ?>
					<h2 class="nok-fs-6"><?= $c->section_title ?></h2>
				<?php endif; ?>
				<?php if ( $c->has( 'section_description' ) && $c->section_description->raw() !== '' ) : ?>
					<p class="nok-fs-body"><?= nl2br( $c->section_description ) ?></p>
				<?php endif; ?>

				<?php if ( $video_url && $is_self_hosted ) : ?>
					<div class="nok-video-background nok-rounded-border-large"
					     data-requires="./nok-video.mjs"
					     data-video-lq="<?= esc_url( $video_url ) ?>"
					     data-video-hq="<?= esc_url( $video_hq ) ?>"
					     data-video-autoplay="<?= esc_attr( $autoplay ) ?>"
						<?php if ( $video_start > 0 ) : ?>data-video-start="<?= esc_attr( $video_start ) ?>"<?php endif; ?>>
						<video muted loop playsinline preload="none"
						       aria-label="Achtergrondvideo"
							<?php if ( $video_poster ) : ?>poster="<?= esc_url( $video_poster ) ?>"<?php endif; ?>>
							<source src="<?= esc_url( $video_url ) ?>" type="video/mp4">
						</video>
						<button type="button"
						        title="Bekijk de video op volledig scherm"
						        class="nok-video-background__play-trigger nok-bg-darkestblue"
						        aria-label="Video fullscreen afspelen"
						        data-video-play>
						</button>
					</div>
				<?php elseif ( $video_embed_html ) : ?>
					<div class="nok-video-section__video-wrapper">
						<?= $video_embed_html ?>
					</div>
				<?php else : ?>
					<div class="nok-video-section__video-wrapper nok-video-section__empty">
						<p>Geen video URL opgegeven</p>
					</div>
				<?php endif; ?>

			</article>
		</div>
	<?php endif; ?>
</nok-section>
