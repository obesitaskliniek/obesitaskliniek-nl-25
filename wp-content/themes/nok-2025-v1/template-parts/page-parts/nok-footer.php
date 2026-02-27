<?php
/**
 * Template Name: Footer
 * Description: Sitefooter met contact-CTA, navigatie-accordions en juridische links
 * Slug: nok-footer
 * Custom Fields:
 * - colors:color-selector(footer-colors)!default(nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue nok-dark-text-white)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\SEO\YoastIntegration;
use NOK2025\V1\Theme;

$c = $context;

$theme = Theme::get_instance();
$menu_manager = $theme->get_menu_manager();
$block_colors = $c->colors->contains('nok-bg-darkestblue',
	'nok-bg-darkblue nok-bg-alpha-6 nok-alpha-10',
	'nok-bg-body nok-dark-bg-body--darker nok-alpha-10 nok-alpha-10'
);
?>

<nok-page-footer class="nok-section" role="contentinfo">
    <div class="nok-section__inner--stretched nok-my-0 nok-px-0 nok-border-radius-0 <?= $c->colors ?>">
        <div class="nok-section__inner nok-page-footer__inner">
            <div class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
                <nok-square-block class="<?= $block_colors ?>">
                    <div class="nok-square-block__heading">
                        <h3 class="fw-bold">Vragen?</h3>
                        <h3 class="fw-400">We helpen je graag</h3>
                    </div>
                    <div class="nok-layout-flex nok-column-gap-0_5 flex-wrap nok-equal-button-width">
                        <a href="/contact/" role="button" class="nok-button nok-justify-self-start fill-mobile nok-bg-darkblue nok-text-contrast" tabindex="0">
                            Neem contact op <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
                        <a href="/kennisbank/veelgestelde-vragen" role="button" class="nok-button nok-justify-self-start fill-mobile nok-bg-body--darker nok-dark-bg-darkerblue nok-text-contrast" tabindex="0">
                            Bekijk veelgestelde vragen <?= Assets::getIcon('ui_question', 'nok-text-darkblue nok-dark-text-yellow') ?>
                        </a>
                    </div>
                </nok-square-block>
                <div class="nok-layout-grid nok-layout-grid__3-column" data-requires="./nok-accordion.mjs" data-require-lazy="true">
					<?php $menu_manager->render_footer_columns( 'footer' ); ?>
                </div>
            </div>
            <div class="nok-layout-grid__span-all nok-page-footer__bottom-bar">
                <div class="nok-page-footer__legal-links">
                    <a href="/klachten-privacy/" class="nok-hyperlink">Klachten & Privacy</a>
                    <a href="/zorgmail/" class="nok-hyperlink">ZorgMail</a>
                    <a href="/cookie-verklaring/" class="nok-hyperlink">Cookie Verklaring</a>
                </div>
                <?php $social_profiles = YoastIntegration::get_social_profiles(); ?>
                <?php if ($social_profiles): ?>
                    <nav class="nok-page-footer__social-links" aria-label="Social media">
                        <?php foreach ($social_profiles as $profile): ?>
                            <a href="<?= esc_url($profile['url']) ?>"
                               class="nok-page-footer__social-link"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="<?= esc_attr($profile['label']) ?>">
                                <?= Assets::getIcon($profile['icon']) ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nok-page-footer>