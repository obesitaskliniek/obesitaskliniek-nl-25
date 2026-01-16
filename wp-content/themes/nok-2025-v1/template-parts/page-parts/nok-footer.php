<?php
/**
 * Template Name: Footer
 * Description: Site footer with contact CTA, navigation accordions, and legal links
 * Slug: nok-footer
 * Custom Fields:
 * - colors:select(Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue nok-dark-text-white|Donkerblauw::nok-bg-darkestblue nok-text-white--darker)!default(nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue nok-dark-text-white)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Theme;

$c = $context;

$theme = Theme::get_instance();
$menu_manager = $theme->get_menu_manager();
$block_colors = $c->colors->contains('nok-bg-darkestblue',
	'nok-bg-darkblue nok-bg-alpha-6 nok-alpha-10',
	'nok-bg-body nok-dark-bg-body--darker nok-alpha-10 nok-alpha-10'
);
?>

<nok-page-footer class="nok-section">
    <div class="nok-section__inner--stretched nok-my-0 nok-px-0 nok-border-radius-0 <?= $c->colors ?>">
        <div class="nok-section__inner nok-page-footer__inner">
            <div class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
                <nok-square-block class="<?= $block_colors ?>">
                    <div class="nok-square-block__heading">
                        <h3 class="fw-bold">Vragen?</h3>
                        <h3 class="fw-400">We helpen je graag</h3>
                    </div>
                    <p class="nok-square-block__text nok-fs-2">
                        Wil je meer weten over de behandeling? Bel of mail ons dan gerust.
                    </p>
                    <div class="nok-layout-flex nok-column-gap-0_5">
                        <a href="#" role="button" class="nok-button nok-justify-self-start fill-mobile nok-bg-darkblue nok-text-contrast" tabindex="0">
                            Neem contact op <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
                        </a>
                        <a href="#" role="button" class="nok-button nok-justify-self-start fill-mobile nok-bg-body--darker nok-text-contrast" tabindex="0">
                            Bekijk veelgestelde vragen <?= Assets::getIcon('ui_question', 'nok-text-darkblue') ?>
                        </a>
                    </div>
                </nok-square-block>
                <div class="nok-layout-grid nok-layout-grid__3-column" data-requires="./nok-accordion.mjs" data-require-lazy="true">
					<?php $menu_manager->render_footer_columns( 'footer' ); ?>
                    <div>
                        <h4 class="nok-fs-2 fw-bold">Neem contact op</h4>
                        <ul class="nok-ul-list">
                            <li>info@obesitaskliniek.nl</li>
                            <li>088 - 88 32 444</li>
                        </ul>
                    </div>
                </div>
                <div class="nok-layout-grid__span-all nok-layout-flex-row">
                    <a href="#" class="nok-hyperlink">Privacy Policy</a>
                    <a href="#" class="nok-hyperlink">Algemene voorwaarden</a>
                </div>
            </div>
        </div>
    </div>
</nok-page-footer>