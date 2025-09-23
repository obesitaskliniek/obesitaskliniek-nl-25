<?php
/**
 * Template Name: Footer
 * Description: A footer unit for the bottom of all pages.
 * Slug: nok-footer
 * Custom Fields:
 * - tagline:text,
 * - button_blauw_text:text,
 * - button_blauw_url:url,
 * - button_transparant_text:text,
 * - button_transparant_url:url,
 * - link_text:text,
 * - link_url:url,
 *  - colors:select(Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue nok-dark-text-white|Donkerblauw::nok-bg-darkestblue nok-text-white--darker)
 */

use NOK2025\V1\Assets;

$default_colors = 'nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue nok-dark-text-white';
$colors = ($page_part_fields['colors'] ?? "") !== "" ? $page_part_fields['colors'] : $default_colors;
$left = empty($page_part_fields['layout']) || $page_part_fields['layout'] === 'left';
?>

<nok-page-footer class="nok-section <?= $colors; ?>">
    <div class="nok-section__inner--stretched nok-my-0 nok-px-0 nok-border-radius-0 ">
        <div class="nok-section__inner nok-page-footer__inner">
            <div class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
                <?php $block_colors = str_contains($colors, 'nok-bg-darkestblue') ? 'nok-bg-darkblue nok-bg-alpha-6 nok-alpha-10' : 'nok-bg-body nok-dark-bg-body--darker nok-alpha-10 nok-alpha-10'; ?>
                <nok-square-block class="<?= $block_colors; ?>">
                    <div class="nok-square-block__heading">
                        <h3 class="fw-bold">Vragen?</h3>
                        <h3 class="fw-400">We helpen je graag</h3>
                    </div>
                    <p class="nok-square-block__text nok-fs-2">
                        Wil je meer weten over de behandeling? Bel of mail ons dan gerust.
                    </p>
                    <button class="nok-button nok-justify-self-start
                nok-bg-darkblue nok-text-contrast" tabindex="0">
                        Neem contact op <?= Assets::getIcon('arrow-right-long', 'nok-text-yellow'); ?>
                    </button>
                </nok-square-block>
                <div class="nok-layout-grid nok-layout-grid__3-column" data-requires="./nok-accordion.mjs?cache=<?= time(); ?>">
                    <nok-accordion class="nok-border-bottom-to-lg-1 nok-pt-to-lg-1 nok-pb-to-lg-0_5">
                        <details data-opened-at="lg" name="footer-accordion-group">
                            <summary class="nok-fs-2 nok-fs-to-lg-3 fw-bold nok-mb-0_5">Behandeling</summary>
                            <div class="accordion-content">
                                <ul class="nok-ul-list">
                                    <li>Advies over obesitas</li>
                                    <li>Behandeling met operatie</li>
                                    <li>Ons behandelprogramma</li>
                                    <li>Kosten van de behandeling</li>
                                    <li>Onze specialisten</li>
                                    <li>Verwijzers</li>
                                </ul>
                            </div>
                        </details>
                    </nok-accordion>
                    <nok-accordion class="nok-border-bottom-to-lg-1 ok-pt-to-lg-1 nok-pb-to-lg-0_5">
                        <details data-opened-at="lg" name="footer-accordion-group">
                            <summary class="nok-fs-2 nok-fs-to-lg-3 fw-bold nok-mb-0_5">Over de NOK</summary>
                            <div class="accordion-content">
                                <ul class="nok-ul-list">
                                    <li>Over ons</li>
                                    <li>Ervaringen</li>
                                    <li>Kennisbank</li>
                                    <li>Werken bij</li>
                                    <li>Vestigingen</li>
                                    <li>Contact</li>
                                </ul>
                            </div>
                        </details>
                    </nok-accordion>
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

<?php