<?php
/**
 * Template Name: Portrait Carousel
 * Description: Scrollable carousel displaying team member portraits from filesystem
 * Slug: nok-portrait-carousel
 * Custom Fields:
 * - button_text:text!default(Bekijk alle specialisten)
 * - button_url:url,
 * - team_members:repeater
 * - colors:select(Blauw::nok-bg-darkblue nok-text-white|Wit::nok-bg-white nok-text-darkblue nok-dark-bg-body--darker nok-dark-text-contrast)!page-editable
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * todo: work out the repeater
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;
?>

<nok-section class="<?= $c->colors ?>">
    <div class="nok-section__inner--stretched">
        <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?>">

            <article class="nok-layout-grid nok-layout-grid__3-column nok-align-items-start">
				<?php the_title('<h1 class="nok-column-first-2 nok-span-all-columns-to-xxl">', '</h1>'); ?>
                <div class="new-row nok-column-first-2 nok-span-all-columns-to-xxl"><?php the_content(); ?></div>

				<?php if ($c->has('button_url')) : ?>
                    <a role="button" href="<?= $c->button_url->url() ?>"
                       class="nok-button nok-column-last-1 nok-bg-darkestblue nok-text-contrast fill-mobile">
						<?= $c->button_text ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                    </a>
				<?php endif; ?>
                <!-- Component: drag-scrollable blokkengroep -->
                <div class="nok-mt-2 nok-align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__4-column nok-columns-to-lg-2
                nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="false">
						<?php
						$specialisten = array('Arts', 'Internist', 'Diëtist', 'Psycholoog', 'Bewegingsdeskundige', 'Chirurg');
						$people_dir = THEME_ROOT_ABS . '/assets/img/people';
						$images = array();
						if (is_dir($people_dir)) :
							$images = glob($people_dir . '/*.png');
							shuffle($specialisten);
							foreach ($specialisten as $specialist) :
								if (!empty($images)) :
									$random_key = array_rand($images);
									$image = $images[$random_key];
									$filename = basename($image);
									$persoon = str_replace('-transparant', '', pathinfo($filename)['filename']);
									$afbeelding = THEME_ROOT . '/assets/img/people/' . $filename;
									unset($images[$random_key]); ?>
                                    <nok-square-block class="nok-p-0 nok-border-radius-0">
                                        <div class="nok-image-square-portrait nok-rounded-border-large nok-gradient-1">
                                            <img src="<?= esc_url($afbeelding) ?>" loading="lazy" style="filter:drop-shadow(30px 20px 30px rgba(var(--nok-darkerblue-rgb), 0.15))">
                                        </div>
                                        <div>
                                            <h3><?= esc_html($persoon) ?></h3>
                                            <p class="fw-300 nok-mt-0"><?= esc_html($specialist) ?></p>
                                        </div>
                                    </nok-square-block>
								<?php endif; endforeach; endif; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>