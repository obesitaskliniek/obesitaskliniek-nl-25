<?php
/**
 * Template Name: Hero (fullscreen)
 * Description: Full-viewport background photo hero with text overlay — for campaign/landing pages
 * Slug: nok-hero-fullscreen
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text!page-editable
 * - button_primary_text:text!page-editable!default(Meld je aan)
 * - button_primary_url:link!page-editable
 * - button_secondary_text:text!page-editable
 * - button_secondary_url:link!page-editable
 * - image_focus:select(left|center|right|70%)!default(center)
 * - overlay_style:select(gradient-bottom|gradient-left|text-shadow|none)!default(gradient-bottom)
 * - text_theme:select(light|dark)!default(light)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

// --- Image ---
$featured_image = has_post_thumbnail()
	? wp_get_attachment_image(
		get_post_thumbnail_id(),
		'full',
		false,
		[
			'class'         => 'nok-hero-fullscreen__image',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'sizes'         => '100vw',
			'alt'           => '', // Decorative — screen readers skip
		]
	)
	: null;

// --- Overlay & theme ---
$overlay_style = $c->overlay_style->raw() ?: 'gradient-bottom';
$text_theme    = $c->text_theme->raw() ?: 'light';
$image_focus   = $c->image_focus->raw() ?: 'center';

// Map image_focus to CSS object-position value
$focus_map = [
	'left'   => 'left center',
	'center' => 'center center',
	'right'  => 'right center',
	'70%'    => '70% center',
];
$object_position = $focus_map[ $image_focus ] ?? 'center center';

// Theme-based color classes
$theme_classes = $text_theme === 'light'
	? 'nok-hero-fullscreen--light'
	: 'nok-hero-fullscreen--dark';
?>

<nok-hero class="nok-hero--fullscreen nok-section <?= $theme_classes ?>">
	<div class="nok-hero-fullscreen__container"
	     style="--hero-image-focus: <?= esc_attr( $object_position ) ?>">

		<?php if ( $featured_image ) : ?>
			<figure class="nok-hero-fullscreen__figure">
				<?= $featured_image ?>
			</figure>
		<?php endif; ?>

		<?php if ( $overlay_style !== 'none' && $overlay_style !== 'text-shadow' ) : ?>
			<div class="nok-hero-fullscreen__overlay nok-hero-fullscreen__overlay--<?= esc_attr( $overlay_style ) ?>"></div>
		<?php endif; ?>

		<article class="nok-hero-fullscreen__content <?= $overlay_style === 'text-shadow' ? 'nok-hero-fullscreen__content--text-shadow' : '' ?>">
			<div class="nok-hero-fullscreen__text">

				<h1 class="nok-hero-fullscreen__title"><?= $c->title() ?></h1>

                <?php if ( $c->has( 'tagline' ) ) : ?>
                    <p class="nok-hero-fullscreen__tagline"><?= $c->tagline ?></p>
                <?php endif; ?>

				<div class="nok-hero-fullscreen__body nok-layout-grid nok-layout-grid__1-column">
					<?= $c->content(); ?>
				</div>

				<?php if ( $c->has( 'button_primary_url' ) || $c->has( 'button_secondary_url' ) ) : ?>
                    <?php if ( $c->has( 'button_primary_url' ) ) : ?>
                        <a role="button"
                           href="<?= $c->button_primary_url->link() ?>"
                           class="nok-button nok-button--large nok-bg-yellow nok-text-contrast"
                           tabindex="0">
                            <span><?= $c->button_primary_text ?></span>
                            <?= Assets::getIcon( 'ui_arrow-right-long' ) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( $c->has( 'button_secondary_url' ) ) : ?>
                        <a role="button"
                           href="<?= $c->button_secondary_url->link() ?>"
                           class="nok-hyperlink nok-justify-self-center fw-bold">
                            <span><?= $c->button_secondary_text ?></span>
                        </a>
                    <?php endif; ?>
				<?php endif; ?>
			</div>
		</article>
	</div>
</nok-hero>
