<?php
/**
 * Template Name: Ervaringen text block
 * Description: Text block with decorative circle background and testimonial carousel
 * Slug: nok-ervaringen-text-block
 * Custom Fields:
 * - layout:select(left|right)!page-editable!default(left)
 * - achtergrond:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-white|Transparant::nok-text-darkerblue nok-dark-text-white)!page-editable!default(nok-text-darkerblue nok-dark-text-white)
 * - tekst:select(Blauw::nok-text-darkerblue nok-dark-text-contrast|Wit::nok-text-white nok-dark-text-contrast)!page-editable!default(nok-text-darkerblue)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Automatisch::var(--nok-body--lighter)|Uit::transparent)!page-editable!default(var(--nok-body--lighter))
 * - testimonials:repeater(quote:text,excerpt:text,link_url:url,image_url:url)
 * - carousel_buttons:checkbox(false)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;

// Layout direction
$left = $c->layout->is('left');

// Circle color as CSS custom property
$circle_style = $c->circle_color->css_var('circle-background-color');

// Circle offset calculation based on layout
$circle_offset = "--circle-offset:" . $c->layout->is('left', 'calc(50vw - (var(--section-max-width) * 0.35))', 'calc(50vw + (var(--section-max-width) * 0.25))');

$testimonial_data = $c->testimonials->json([
	[
		'quote' => 'Wat een fijn traject ben ik aangegaan',
		'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero.',
		'link_url' => '#',
		'image_url' => 'https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg'
	],
	[
		'quote' => 'Wat een fijn traject ben ik aangegaan',
		'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero.',
		'link_url' => '#',
		'image_url' => 'https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg'
	],
	[
		'quote' => 'Wat een fijn traject ben ik aangegaan',
		'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero.',
		'link_url' => '#',
		'image_url' => 'https://www.obesitaskliniek.nl/wp-content/uploads/2025/06/1000108534-scaled:350x247-45-0-0-center-0-0.jpg'
	]
]);
?>
<nok-section class="circle <?= $c->achtergrond ?> <?= $c->tekst ?>"
             style="<?= $circle_style ?>; <?= $circle_offset ?>;">
    <div class="nok-section__inner triple-margin nok-my-to-lg-4">
        <article class="nok-layout-grid
                            nok-columns-6 nok-columns-to-lg-1
                            nok-align-items-center
                            nok-column-gap-3">
            <div class="nok-align-self-to-lg-stretch nok-column-first-2 nok-layout-flex-column nok-align-items-stretch nok-fs-2">
				<?php the_title('<h1>', '</h1>'); ?>
				<?php the_content(); ?>
                <?php if ($c->carousel_buttons->isTrue()) : ?>
                <div class="nok-button-group">
                    <button class="nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast fill-group-column"
                            data-scroll-target="ervaringen-scroller" data-scroll-action="backward">
						<?= Assets::getIcon('ui_arrow-left-longer') ?>
                    </button>
                    <button class="nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast fill-group-column"
                            data-scroll-target="ervaringen-scroller" data-scroll-action="forward">
						<?= Assets::getIcon('ui_arrow-right-longer') ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="nok-align-self-to-lg-stretch nok-column-last-3">
                <div class="nok-scrollable__horizontal nok-subtle-shadow-compensation"
                     data-scroll-snapping="true" data-draggable="true"
                     id="ervaringen-scroller" data-autoscroll="false">

					<?php foreach ($testimonial_data as $testimonial): ?>
                        <nok-square-block class="nok-bg-white nok-text-darkerblue nok-dark-bg-darkblue nok-dark-text-contrast nok-alpha-10 nok-p-3" data-shadow="true">
                            <div class="nok-square-block__heading">
                                <h2>"<?= esc_html($testimonial['quote']) ?>"</h2>
                            </div>
                            <div class="nok-square-block__text nok-fs-2">
								<?= esc_html($testimonial['excerpt']) ?>
                            </div>
							<?php if (!empty($testimonial['link_url'])): ?>
                                <div class="nok-layout-flex-row space-between">
                                    <a role="button" href="<?= esc_url($testimonial['link_url']) ?>"
                                       class="nok-button nok-justify-self-start nok-bg-darkblue nok-text-contrast nok-dark-bg-darkerblue fill-mobile" tabindex="0">
                                        Lees het verhaal <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                                    </a>
									<?php if (!empty($testimonial['image_url'])): ?>
                                        <img class="nok-square-block__thumbnail" src="<?= esc_url($testimonial['image_url']) ?>" loading="lazy">
									<?php endif; ?>
                                </div>
							<?php endif; ?>
                        </nok-square-block>
					<?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>
</nok-section>