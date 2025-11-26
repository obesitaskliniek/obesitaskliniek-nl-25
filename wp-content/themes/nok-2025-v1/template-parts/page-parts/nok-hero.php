<?php
/**
 * Template Name: Hero
 * Description: Full-width hero section with SVG-masked image, title, content, CTAs and USP footer
 * Slug: nok-hero
 * Featured Image Overridable: true
 * Custom Fields:
 * - tagline:text!page-editable
 * - button_blauw_text:text!page-editable
 * - button_blauw_url:url!page-editable
 * - button_transparant_text:text!page-editable
 * - button_transparant_url:url!page-editable
 * - usp_1_icon:icon-selector!page-editable!default(nok_kosten)
 * - usp_1_text:text!page-editable!default(Vergoed door je zorgverzekering)
 * - usp_2_icon:icon-selector!page-editable!default(nok_30_jaar_ervaring)
 * - usp_2_text:text!page-editable!default(Meer dan 30 jaar ervaring)
 *  - usp_3_icon:icon-selector!page-editable!default(nok_hospital)
 * - usp_3_text:text!page-editable!default(Samenwerking met de beste ziekenhuizen))
 * - button_vestiging_text:text!page-editable!default(Onze vestigingen)
 * - button_vestiging_url:url!page-editable!default(/vestigingen)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c             = $context;
$featuredImage = Helpers::get_featured_image();
?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

            <article class="nok-pl-section-padding nok-px-to-lg-section-padding">
                <?php Helpers::render_breadcrumbs(); ?>
                <?php if ($c->has('tagline')) : ?>
                <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading">
					<?= $c->tagline ?>
                </h2>
                <?php endif; ?>
                <h1 class="nok-fs-giant"><?= $c->title() ?></h1>
                <div class="nok-layout-grid">
					<?= $c->content(); ?>
                </div>
                <div class="nok-button-group nok-justify-items-start">
					<?php if ( $c->has( 'button_blauw_url' ) ): ?>
                        <a role="button" href="<?= $c->button_blauw_url->url() ?>"
                           class="nok-button nok-justify-self-center nok-bg-darkerblue nok-text-contrast fill-group-column"
                           tabindex="0">
                            <span><?= $c->button_blauw_text ?></span>
                        </a>
					<?php endif; ?>
					<?php if ( $c->has( 'button_transparant_url' ) ): ?>
                        <a role="button" href="<?= $c->button_transparant_url->url() ?>"
                           class="nok-hyperlink nok-justify-self-center fw-bold">
                            <span><?= $c->button_transparant_text ?></span>
                        </a>
					<?php endif; ?>
                </div>
            </article>

            <figure>
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                     viewBox="0 0 900 1060">
                    <defs>
                        <linearGradient id="c" x1="899.81" x2="1920.52" y1="1367.93" y2="-128.38"
                                        gradientTransform="rotate(-45 802.663 961.106)" gradientUnits="userSpaceOnUse">
                            <stop offset="0"
                                  stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset=".5"
                                  stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset="1"
                                  stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        </linearGradient>
                        <linearGradient id="b" x1="881.9" x2="1940.53" y1="1391.88" y2="-160.02"
                                        gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                            <stop offset="0"
                                  stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset=".5"
                                  stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset="1"
                                  stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        </linearGradient>
                        <linearGradient id="a" x1="865.07" x2="1963.13" y1="1419.45" y2="-190.25"
                                        gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                            <stop offset="0"
                                  stop-color="oklch(from var(--grad-1-3) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset=".5"
                                  stop-color="oklch(from var(--grad-1-2) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                            <stop offset="1"
                                  stop-color="oklch(from var(--grad-1-1) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"></stop>
                        </linearGradient>
                        <filter id="luminosity-noclip" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse">
                            <feFlood flood-color="#fff" result="bg"></feFlood>
                            <feBlend in="SourceGraphic" in2="bg"></feBlend>
                        </filter>
                        <mask id="image-mask" maskUnits="userSpaceOnUse">
                            <g style="filter: url(#luminosity-noclip)">
                                <path id="mask-path"
                                      d="M418-143.6c2.7-1.6,5.5-3,8.2-4.4-2.8,1.4-5.6,2.8-8.3,4.2l-.5-1-652.3,2.5v1207.8h1685.7v-65.4c-283.3-.3-530.7-.6-529.9-.9-.8.2-1.7.5-2.4.7h0c-1.6.4-3.2.9-4.8,1.3-3.6,1-7.2,2-10.8,2.8-210.2,51.2-429-87.2-581.7-408.1C154.1,245.1,205.1-25.5,418-143.6Z"></path>
                            </g>
                        </mask>
                    </defs>
                    <path id="d"
                          fill="oklch(from var(--base-layer) l c h / var(--global-bg-alpha-value, var(--bg-alpha-value, 1))"
                          d="M137.3,682.5C-22.9,346.2,75,36.7,415.1-141.8,30.2,58.5-79.7,385.6,75.6,711.9c151.5,318,459.6,442.1,846.1,287.2-.8.2-1.7.5-2.4.7-340.4,131.3-626.9,8.6-782.1-317.3h0Z"></path>
                    <path id="c" fill="url(#c)"
                          d="M137.3,682.5c155.2,325.9,441.8,448.6,782.1,317.3-1.6.4-3.2.9-4.8,1.3-293.6,106-557.6-15.9-715.7-347.9C33.9,306.8,120,14.6,415.7-142.1c-.2.1-.4.2-.6.3C75,36.7-22.9,346.2,137.3,682.5Z"></path>
                    <path id="b" fill="url(#b)"
                          d="M260.6,623.8C90.7,267.3,165-7.5,416.1-142.3c-.2,0-.3.2-.5.2C120,14.6,33.9,306.8,198.9,653.2c158.1,332,422.1,454,715.7,347.9-3.6,1-7.2,2-10.8,2.8-245.8,76.4-484.1-46.2-643.3-380.1h0Z"></path>
                    <path id="a" fill="url(#a)"
                          d="M260.6,623.8c159.1,334.1,397.5,456.5,643.3,380.1-210.2,51.2-429-87.2-581.7-408.1C152.9,240.6,207.4-32.3,427.1-148c-3.6,1.9-7.3,3.7-11,5.6C165-7.5,90.7,267.3,260.6,623.8Z"></path>
                    <g mask="url(#image-mask)" class="">
                        <foreignObject height="1060" width="700" x="200" class="nok-image-cover">
							<?= $featuredImage; ?>
                        </foreignObject>
                    </g>
                </svg>

            </figure>

            <footer class="nok-px-section-padding nok-bg-body--lighter nok-dark-bg-darkerblue nok-bg-blur--large nok-bg-alpha-6">
                <div class="nok-fs-buttons nok-usp nok-invisible-to-lg">
                    <?= Assets::getIcon($c->usp_1_icon->raw(), 'nok-text-lightblue') ?>
                    <?= $c->usp_1_text ?>
                </div>
                <div class="nok-fs-buttons nok-usp nok-invisible-to-xl">
                    <?= Assets::getIcon($c->usp_2_icon->raw(), 'nok-text-lightblue') ?>
                    <?= $c->usp_2_text ?>
                </div>
                <div class="nok-fs-buttons nok-usp nok-invisible-to-xxxl">
                    <?= Assets::getIcon($c->usp_3_icon->raw(), 'nok-text-lightblue') ?>
                    <?= $c->usp_3_text ?>
                </div>
                <a role="button" href="<?= $c->button_vestiging_url->url() ?>"
                   class="nok-button nok-bg-white nok-text-darkerblue nok-visible-xs nok-align-self-stretch"
                   tabindex="0"><span><?= $c->button_vestiging_text ?></span>
                </a>
            </footer>
        </div>
    </nok-hero>

<?php