<?php
/**
 * Template Name: Double text block
 * Description: Two side-by-side square blocks with customizable content, colors, and buttons. Optionally includes media (image/video) glued to each card.
 * Slug: nok-double-text-block
 * Custom Fields:
 * - colors:color-selector(backgrounds)!page-editable!default()
 * - tekstkleur:color-selector(text)!page-editable!default(nok-text-darkerblue)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 * - layout:select(Links eerst::left|Rechts eerst::right)!page-editable!default(left)
 * - shadow:checkbox!default(true)!descr[Schaduw onder blokken?]!page-editable
 * - card_style:select(Alleen tekst::text|Met media::media)!default(text)!descr[Kaart stijl]!page-editable
 * - block_1_title:text!descr[Titel blok 1]
 * - block_1_content:textarea!descr[Inhoud blok 1]
 * - block_1_bg:color-selector(backgrounds-full)!default(nok-bg-darkblue)!descr[Achtergrondkleur blok 1]
 * - block_1_text:color-selector(text)!default(nok-text-white)!descr[Tekstkleur blok 1]
 * - block_1_button_text:text!default(Lees meer)!descr[Knoptekst blok 1]
 * - block_1_button_url:url!descr[Knop URL blok 1]
 * - block_1_button_target:select(Zelfde venster::_self|Nieuw venster::_blank)!default(_self)!descr[Knop target blok 1]
 * - block_1_button_bg:color-selector(button-backgrounds)!default(nok-bg-white nok-text-darkblue)!descr[Knop achtergrondkleur blok 1]
 * - block_1_button_icon:icon-selector!default(ui_arrow-right-long)!descr[Knop icoon blok 1]
 * - block_1_button_icon_color:color-selector(icon-colors)!default(nok-text-lightblue)!descr[Knop icoonkleur blok 1]
 * - block_1_button_2_text:text!default(Lees meer)!descr[Knop 2 tekst blok 1]
 * - block_1_button_2_url:url!descr[Knop 2 URL blok 1]
 * - block_1_button_2_target:select(Zelfde venster::_self|Nieuw venster::_blank)!default(_self)!descr[Knop 2 target blok 1]
 * - block_1_button_2_bg:color-selector(button-backgrounds)!default(nok-bg-white nok-text-darkblue)!descr[Knop 2 achtergrondkleur blok 1]
 * - block_1_button_2_icon:icon-selector!default(ui_arrow-right-long)!descr[Knop 2 icoon blok 1]
 * - block_1_button_2_icon_color:color-selector(icon-colors)!default(nok-text-lightblue)!descr[Knop 2 icoonkleur blok 1]
 * - block_1_image:image!page-editable!descr[Afbeelding blok 1]
 * - block_1_video_lq:url!page-editable!descr[Video LQ URL blok 1]
 * - block_1_video_hq:url!page-editable!descr[Video HQ URL blok 1]
 * - block_1_video_poster:url!page-editable!descr[Video poster URL blok 1]
 * - block_1_media_position:select(Boven::top|Onder::bottom)!default(top)!descr[Media positie blok 1]!page-editable
 * - block_2_title:text!descr[Titel blok 2]
 * - block_2_content:textarea!descr[Inhoud blok 2]
 * - block_2_bg:color-selector(backgrounds-full)!default(nok-bg-darkblue)!descr[Achtergrondkleur blok 2]
 * - block_2_text:color-selector(text)!default(nok-text-white)!descr[Tekstkleur blok 2]
 * - block_2_button_text:text!default(Lees meer)!descr[Knoptekst blok 2]
 * - block_2_button_url:url!descr[Knop URL blok 2]
 * - block_2_button_target:select(Zelfde venster::_self|Nieuw venster::_blank)!default(_self)!descr[Knop target blok 2]
 * - block_2_button_bg:color-selector(button-backgrounds)!default(nok-bg-white nok-text-darkblue)!descr[Knop achtergrondkleur blok 2]
 * - block_2_button_icon:icon-selector!default(ui_arrow-right-long)!descr[Knop icoon blok 2]
 * - block_2_button_icon_color:color-selector(icon-colors)!default(nok-text-lightblue)!descr[Knop icoonkleur blok 2]
 * - block_2_button_2_text:text!default(Lees meer)!descr[Knop 2 tekst blok 2]
 * - block_2_button_2_url:url!descr[Knop 2 URL blok 2]
 * - block_2_button_2_target:select(Zelfde venster::_self|Nieuw venster::_blank)!default(_self)!descr[Knop 2 target blok 2]
 * - block_2_button_2_bg:color-selector(button-backgrounds)!default(nok-bg-white nok-text-darkblue)!descr[Knop 2 achtergrondkleur blok 2]
 * - block_2_button_2_icon:icon-selector!default(ui_arrow-right-long)!descr[Knop 2 icoon blok 2]
 * - block_2_button_2_icon_color:color-selector(icon-colors)!default(nok-text-lightblue)!descr[Knop 2 icoonkleur blok 2]
 * - block_2_image:image!page-editable!descr[Afbeelding blok 2]
 * - block_2_video_lq:url!page-editable!descr[Video LQ URL blok 2]
 * - block_2_video_hq:url!page-editable!descr[Video HQ URL blok 2]
 * - block_2_video_poster:url!page-editable!descr[Video poster URL blok 2]
 * - block_2_media_position:select(Boven::top|Onder::bottom)!default(top)!descr[Media positie blok 2]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;
$left_first = $c->layout->is('left');
$shadow = $c->shadow->isTrue() ? 'true' : 'false';
$is_media_style = $c->card_style->is('media');

// Block 1 data
$block_1 = [
	'title'              => $c->block_1_title,
	'content'            => $c->block_1_content,
	'button_text'        => $c->block_1_button_text,
	'button_url'         => $c->block_1_button_url,
	'button_target'      => $c->block_1_button_target->raw(),
	'button_bg'          => $c->block_1_button_bg,
	'button_icon'        => $c->block_1_button_icon->raw() ?: 'ui_arrow-right-long',
	'button_icon_color'  => $c->block_1_button_icon_color->raw() ?: 'nok-text-lightblue',
	'button_2_text'      => $c->block_1_button_2_text,
	'button_2_url'       => $c->block_1_button_2_url,
	'button_2_target'    => $c->block_1_button_2_target->raw(),
	'button_2_bg'        => $c->block_1_button_2_bg,
	'button_2_icon'      => $c->block_1_button_2_icon->raw() ?: 'ui_arrow-right-long',
	'button_2_icon_color'=> $c->block_1_button_2_icon_color->raw() ?: 'nok-text-lightblue',
	'bg'                 => $c->block_1_bg,
	'text'               => $c->block_1_text,
	'order'              => $left_first ? 1 : 2,
	'delay'              => 0,
	'image'              => $c->block_1_image,
	'video_lq'           => $c->block_1_video_lq,
	'video_hq'           => $c->block_1_video_hq,
	'video_poster'       => $c->block_1_video_poster,
	'media_position'     => $c->block_1_media_position->raw() ?: 'top',
];

// Block 2 data
$block_2 = [
	'title'              => $c->block_2_title,
	'content'            => $c->block_2_content,
	'button_text'        => $c->block_2_button_text,
	'button_url'         => $c->block_2_button_url,
	'button_target'      => $c->block_2_button_target->raw(),
	'button_bg'          => $c->block_2_button_bg,
	'button_icon'        => $c->block_2_button_icon->raw() ?: 'ui_arrow-right-long',
	'button_icon_color'  => $c->block_2_button_icon_color->raw() ?: 'nok-text-lightblue',
	'button_2_text'      => $c->block_2_button_2_text,
	'button_2_url'       => $c->block_2_button_2_url,
	'button_2_target'    => $c->block_2_button_2_target->raw(),
	'button_2_bg'        => $c->block_2_button_2_bg,
	'button_2_icon'      => $c->block_2_button_2_icon->raw() ?: 'ui_arrow-right-long',
	'button_2_icon_color'=> $c->block_2_button_2_icon_color->raw() ?: 'nok-text-lightblue',
	'bg'                 => $c->block_2_bg,
	'text'               => $c->block_2_text,
	'order'              => $left_first ? 2 : 1,
	'delay'              => 250,
	'image'              => $c->block_2_image,
	'video_lq'           => $c->block_2_video_lq,
	'video_hq'           => $c->block_2_video_hq,
	'video_poster'       => $c->block_2_video_poster,
	'media_position'     => $c->block_2_media_position->raw() ?: 'top',
];

$blocks = [$block_1, $block_2];

/**
 * Check if block has media (video takes precedence over image)
 */
function block_has_media(array $block): bool {
	return $block['video_lq']->raw() || $block['image']->raw();
}

/**
 * Render the content block (nok-square-block) HTML
 */
function render_content_block(array $block, string $shadow): string {
	ob_start();
	?>
	<nok-square-block
		class="link-bottom <?= esc_attr($block['bg']) ?> <?= esc_attr($block['text']) ?>"
		data-shadow="<?= esc_attr($shadow) ?>">

		<?php if ($block['title']->raw()) : ?>
			<h3 class="nok-square-block__heading nok-fs-5">
				<?= $block['title'] ?>
			</h3>
		<?php else : ?>
			<div></div>
		<?php endif; ?>

		<?php if ($block['content']->raw()) : ?>
			<div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column">
				<?= wp_kses_post(wpautop($block['content']->raw())) ?>
			</div>
		<?php else : ?>
			<div></div>
		<?php endif; ?>

		<div></div><?php // Spacer for link-bottom grid alignment ?>

		<?php if ($block['button_url']->raw() || $block['button_2_url']->raw()) : ?>
			<div class="nok-layout-grid fill-fill nok-column-gap-0_5">
				<?php if ($block['button_url']->raw()) : ?>
					<a role="button"
					   href="<?= $block['button_url']->url() ?>"
						<?php if ($block['button_target'] === '_blank') : ?>target="_blank" rel="noopener"<?php endif; ?>
					   class="nok-button <?= esc_attr($block['button_bg']) ?>">
						<span><?= $block['button_text'] ?></span><?= Assets::getIcon($block['button_icon'], $block['button_icon_color']) ?>
					</a>
				<?php endif; ?>
				<?php if ($block['button_2_url']->raw()) : ?>
					<a role="button"
					   href="<?= $block['button_2_url']->url() ?>"
						<?php if ($block['button_2_target'] === '_blank') : ?>target="_blank" rel="noopener"<?php endif; ?>
					   class="nok-button <?= esc_attr($block['button_2_bg']) ?>">
						<span><?= $block['button_2_text'] ?></span><?= Assets::getIcon($block['button_2_icon'], $block['button_2_icon_color']) ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</nok-square-block>
	<?php
	return ob_get_clean();
}

/**
 * Render media element (video or image)
 * Video takes precedence over image
 */
function render_media_element(array $block): string {
	$has_video = $block['video_lq']->raw();
	$has_image = $block['image']->raw();

	if (!$has_video && !$has_image) {
		return '';
	}

	ob_start();

	if ($has_video) {
		// Video background with nok-video.mjs
		?>
		<div class="nok-video-background"
		     data-requires="./nok-video.mjs"
		     data-video-lq="<?= $block['video_lq']->url() ?>"
		     <?php if ($block['video_hq']->raw()) : ?>data-video-hq="<?= $block['video_hq']->url() ?>"<?php endif; ?>
		     <?php if ($block['video_poster']->raw()) : ?>data-video-poster="<?= $block['video_poster']->url() ?>"<?php endif; ?>
		     data-video-autoplay="visibility">
			<video muted loop playsinline preload="none"
			       <?php if ($block['video_poster']->raw()) : ?>poster="<?= $block['video_poster']->url() ?>"<?php endif; ?>>
				<source src="<?= $block['video_lq']->url() ?>" type="video/mp4">
			</video>
		</div>
		<?php
	} else {
		// Image
		?>
		<figure class="nok-square-block__image">
			<?= $block['image']->image('large') ?>
		</figure>
		<?php
	}

	return ob_get_clean();
}
?>
<nok-section class="<?= $c->colors ?>">
	<div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">

		<article class="<?= $c->tekstkleur ?>
					text-start
					nok-layout-grid nok-layout-grid__1-column
					nok-align-items-start">

			<?php if ($c->title()) : ?>
				<h2 class="nok-fs-6 nok-mb-1"><?= $c->title() ?></h2>
			<?php endif; ?>

			<?php if ($c->content()) : ?>
				<div class="nok-layout-grid nok-layout-grid__1-column nok-text-content">
					<?= $c->content(); ?>
				</div>
			<?php endif; ?>

			<div class="nok-layout-grid fill-fill nok-columns-to-lg-1 nok-column-gap-3 nok-align-items-stretch nok-mt-2">
				<?php foreach ($blocks as $block) :
					$has_media = $is_media_style && block_has_media($block);
					$media_at_bottom = $block['media_position'] === 'bottom';

					// Determine wrapper classes for media cards
					// When media_position is 'bottom', we use CSS order to visually place media at bottom
					// but DOM order has content first (for accessibility/SEO)
					$wrapper_classes = 'nok-order-' . $block['order'];
					if ($has_media) {
						$wrapper_classes .= ' nok-card-with-media nok-glue-together';
						if ($media_at_bottom) {
							$wrapper_classes .= ' nok-glue-together--reversed';
						}
					}

					$delay_style = $block['delay'] > 0 ? 'animation-fill-mode: both; animation-delay: ' . $block['delay'] . 'ms' : '';
				?>
					<?php if ($has_media) : ?>
						<div class="<?= esc_attr($wrapper_classes) ?>"
						     <?php if ($delay_style) : ?>style="<?= esc_attr($delay_style) ?>"<?php endif; ?>>
							<?php
							// DOM order: content first, media second
							// CSS order handles visual positioning
							echo render_content_block($block, $shadow);
							echo render_media_element($block);
							?>
						</div>
					<?php else : ?>
						<nok-square-block
								class="link-bottom <?= esc_attr($wrapper_classes) ?> <?= $block['bg'] ?> <?= $block['text'] ?>"
								data-shadow="<?= $shadow ?>"
								<?php if ($delay_style) : ?>style="<?= esc_attr($delay_style) ?>"<?php endif; ?>>

							<?php if ($block['title']->raw()) : ?>
								<h3 class="nok-square-block__heading nok-fs-5">
									<?= $block['title'] ?>
								</h3>
							<?php else : ?>
								<div></div>
							<?php endif; ?>

							<?php if ($block['content']->raw()) : ?>
								<div class="nok-square-block__text nok-layout-grid nok-layout-grid__1-column">
									<?= wp_kses_post(wpautop($block['content']->raw())) ?>
								</div>
							<?php else : ?>
								<div></div>
							<?php endif; ?>

							<div></div><?php // Spacer for link-bottom grid alignment ?>

							<?php if ($block['button_url']->raw() || $block['button_2_url']->raw()) : ?>
								<div class="nok-layout-grid fill-fill nok-column-gap-0_5">
									<?php if ($block['button_url']->raw()) : ?>
										<a role="button"
										   href="<?= $block['button_url']->url() ?>"
											<?php if ($block['button_target'] === '_blank') : ?>target="_blank" rel="noopener"<?php endif; ?>
										   class="nok-button <?= $block['button_bg'] ?>">
											<span><?= $block['button_text'] ?></span><?= Assets::getIcon($block['button_icon'], $block['button_icon_color']) ?>
										</a>
									<?php endif; ?>
									<?php if ($block['button_2_url']->raw()) : ?>
										<a role="button"
										   href="<?= $block['button_2_url']->url() ?>"
											<?php if ($block['button_2_target'] === '_blank') : ?>target="_blank" rel="noopener"<?php endif; ?>
										   class="nok-button <?= $block['button_2_bg'] ?>">
											<span><?= $block['button_2_text'] ?></span><?= Assets::getIcon($block['button_2_icon'], $block['button_2_icon_color']) ?>
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>

						</nok-square-block>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

		</article>
	</div>
</nok-section>
