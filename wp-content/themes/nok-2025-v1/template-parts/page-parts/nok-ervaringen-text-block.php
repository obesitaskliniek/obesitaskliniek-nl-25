<?php
/**
 * Template Name: Ervaringen text block
 * Description: Text block with decorative circle background and testimonial carousel
 * Slug: nok-ervaringen-text-block
 * Custom Fields:
 * - layout:select(left|right)!page-editable!default(left)
 * - achtergrond:select(Blauw::nok-bg-darkerblue|Wit::nok-bg-white nok-dark-bg-darkestblue|Transparant::nok-text-darkerblue)!page-editable!default(nok-text-darkerblue)
 * - quote_items:repeater(quote:text,name:text,subname:text,excerpt:text,link_url:url,image_url:url)!descr[Voeg handmatige quotes toe om te tonen]
 * - quote_posts:post_repeater(post:ervaringen)!descr[Kies specifieke ervaringsverhalen om te tonen]
 * - random_quotes:checkbox!default(true)!descr[Vul aan met willekeurige ervaringen indien minder dan 5 quotes aanwezig zijn]
 * - carousel_buttons:checkbox!default(false)!descr[Toon navigatieknoppen voor de carousel]
 * - colors:select(Transparant::nok-bg-body|Grijs::nok-bg-body--darker gradient-background|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(Transparant)
 * - circle_color:select(Blauw::var(--nok-darkerblue)|Wit::var(--nok-darkerblue)|Automatisch-lichter::oklch(from var(--bg-color) calc(l * 1.2) c h / 1)|Automatisch-donkerder::oklch(from var(--bg-color) calc(l * .8) c h / 1)|Uit::transparent)!page-editable!default(Uit)
 * - quote_block_colors:select(Body::nok-bg-body nok-text-contrast|Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(Wit)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

// Layout direction
$left = $c->layout->is('left');

// Circle color as CSS custom property
$circle_style = $c->circle_color->css_var('circle-background-color');

// Circle offset calculation based on layout
$circle_offset = "--circle-offset:" . $c->layout->is('left', 'calc(50vw - (var(--section-max-width) * 0.35))', 'calc(50vw + (var(--section-max-width) * 0.25))');

// Build quote collection using Helpers
$testimonial_data = Helpers::build_quote_collection(
    $c->quote_posts->json(),
    $c->quote_items->json(),
    $c->random_quotes->isTrue(),
    5
);

//todo: add button option, to link to alle ervaringen

$scroller_id = 'ervaringen-scroller';
?>
<nok-section class="circle <?= $c->colors ?> gradient-background"
             style="<?= $circle_style ?>; <?= $circle_offset ?>;">
    <div class="nok-section__inner <?= $c->narrow_section->isTrue('nok-section-narrow'); ?> triple-margin nok-my-to-lg-4">
        <article class="nok-layout-grid
                            nok-columns-6 nok-columns-to-lg-1
                            nok-align-items-center
                            nok-column-gap-3">
            <div class="nok-align-self-to-lg-stretch nok-column-first-2 nok-layout-flex-column nok-align-items-stretch nok-fs-2">
				<h2 class="nok-fs-6"><?= $c->title() ?></h2>
				<?= $c->content(); ?>
                <?php if ($c->carousel_buttons->isTrue()) : ?>
                <div class="nok-button-group">
                    <button class="nok-button nok-bg-lightgrey nok-dark-bg-darkblue nok-text-contrast fill-group-column"
                            data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="backward">
						<?= Assets::getIcon('ui_arrow-left-longer') ?>
                    </button>
                    <button class="nok-button nok-bg-lightgrey nok-dark-bg-darkblue nok-text-contrast fill-group-column"
                            data-scroll-target="<?= $scroller_id; ?>" data-scroll-action="forward">
						<?= Assets::getIcon('ui_arrow-right-longer') ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>


	        <?php get_template_part( 'template-parts/post-parts/nok-scrollable-quote-block', null,
		        array(
                    'scroller_id' => $scroller_id,
			        'quotes'      => $testimonial_data,
                    'block_color' => $c->quote_block_colors->raw()
		        )
	        ) ?>

        </article>
    </div>
</nok-section>