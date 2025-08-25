<?php
/**
 * Template Name: Hero
 * Description: A hero unit for the top of the main (first level) pages.
 * Slug: nok-hero
 * Custom Fields:
 * - tagline:text,
 * - button_blauw_text:text,
 * - button_blauw_url:url,
 * - button_transparant_text:text,
 * - button_transparant_url:url,
 * - usp_1_text:text,
 * - usp_2_text:text,
 * - usp_3_text:text,
 * - link_url:url,
 *  - button_vestiging_text:text,
 *  - button_vestiging_url:url,
 */

use NOK2025\V1\Helpers;
$featuredImage = Helpers::get_featured_image();


?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

            <article class="nok-pl-section-padding nok-px-to-lg-section-padding">
                <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-2 nok-fs-to-md-1">
	                <?php echo $page_part_fields['tagline'] ?? ''; ?>
                </h2>
				<?php the_title( '<h1 class="nok-fs-6">', '</h1>' ); ?>
                <div>
					<?php the_content(); ?>
                </div>
                <div class="nok-button-group nok-justify-items-start">
                    <a role="button" href="<?= $page_part_fields['button_blauw_url']; ?>"
                       class="nok-button nok-justify-self-center fill-group-column nok-bg-darkerblue nok-text-contrast" tabindex="0">
	                    <?= $page_part_fields['button_blauw_text']; ?>
                    </a>
                    <a role="button" href="<?= $page_part_fields['button_transparant_url']; ?>"
                       class="nok-hyperlink nok-justify-self-center fw-bold">
                        <?= $page_part_fields['button_transparant_text']; ?>
                    </a>
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
                        <foreignObject height="1060" width="700" x="200" class="cover-image">
							<?= $featuredImage; ?>
                        </foreignObject>
                    </g>
                </svg>

            </figure>

            <footer class="nok-px-section-padding nok-bg-body--lighter nok-dark-bg-darkerblue nok-bg-blur--large nok-bg-alpha-6">
                <div class="nok-fs-buttons nok-usp nok-invisible-to-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="nok-text-lightblue" viewBox="0 0 16 16">
                        <path d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936q-.002-.165.008-.329h4.078v-.927H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.6 6.6 0 0 0 10.341 2c-2.928 0-4.82 1.569-5.244 4.3H4v.928h1.01v1.265H4v.928z"></path>
                    </svg>
	                <?= $page_part_fields['usp_1_text']; ?>
                </div>
                <div class="nok-fs-buttons nok-usp nok-invisible-to-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                         class="nok-text-lightblue">
                        <path d="M3.5 5.1c.7-.3 1.1-.9 1.1-1.7s-.8-1.9-2-1.9-1.9.5-2.2.9l.6 1c.4-.4.8-.6 1.3-.6s.9.3.9.7c0 .8-.8.9-1.4.9h-.2v1.2h.3c1 0 1.6.3 1.6 1s-.4.9-1.1.9-1.1-.4-1.3-.6L.5 8c.2.2.8.8 2.1.8s1.4-.2 1.8-.6c.4-.4.7-.9.7-1.5 0-.9-.4-1.6-1.3-1.8Zm6.4-2.7c-.6-.7-1.4-.9-2-.9s-1.3.1-2 .9C5.3 3.1 5 4 5 5.3s.3 2.2.9 2.9c.6.7 1.4.9 2 .9s1.3-.1 2-.9c.6-.7.9-1.6.9-2.9s-.3-2.2-.9-2.9Zm-.6 2.8c0 1.5-.5 2.4-1.4 2.4s-1.4-.8-1.4-2.4.5-2.4 1.4-2.4 1.4.8 1.4 2.4Zm6.6-.2h-1.7V3.3h-1.1V5h-1.7v1.1h1.7v1.8h1.1V6.1h1.7V5Z"></path>
                        <rect width=".7" height=".6" x="5.1" y="11" rx="0" ry="0"></rect>
                        <path d="M5.7 11.8h-.6V14c0 .3 0 .3-.2.3h-.1v.5h.3c.5 0 .8-.3.8-.8v-2.2Zm1.2-.1c-.4 0-.7.1-.9.4l.2.4c0-.1.3-.2.5-.2s.4.1.4.4h-.3c-.6 0-.9.3-.9.7s.2.7.7.7.5-.1.6-.2v.2h.5v-1.2c0-.6-.3-1-.9-1Zm.3 1.4v.2s-.1.1-.4.1-.2 0-.2-.2.1-.2.4-.2h.2ZM9 11.7c-.4 0-.7.1-.9.4l.2.4c0-.1.3-.2.5-.2s.4.1.4.4h-.3c-.6 0-.9.3-.9.7s.2.7.7.7.5-.1.6-.2v.2h.5v-1.2c0-.6-.3-1-.9-1Zm.3 1.4v.2s-.1.1-.4.1-.2 0-.2-.2.1-.2.4-.2h.2Zm2.2-1.4c-.4 0-.5.1-.6.3v-.2h-.5v2.1h.6v-1.4c0-.2.3-.3.5-.3v-.5Z"></path>
                    </svg>
	                <?= $page_part_fields['usp_2_text']; ?>
                </div>
                <div class="nok-fs-buttons nok-usp nok-invisible-to-xxxl">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         viewBox="0 0 16 16" class="nok-text-lightblue">
                        <path d="M8.5 5.034v1.1l.953-.55.5.867L9 7l.953.55-.5.866-.953-.55v1.1h-1v-1.1l-.953.55-.5-.866L7 7l-.953-.55.5-.866.953.55v-1.1zM13.25 9a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM13 11.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25zm.25 1.75a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zm-11-4a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5A.25.25 0 0 0 3 9.75v-.5A.25.25 0 0 0 2.75 9zm0 2a.25.25 0 0 0-.25.25v.5c0 .138.112.25.25.25h.5a.25.25 0 0 0 .25-.25v-.5a.25.25 0 0 0-.25-.25zM2 13.25a.25.25 0 0 1 .25-.25h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5a.25.25 0 0 1-.25-.25z"></path>
                        <path d="M5 1a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1a1 1 0 0 1 1 1v4h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h3V3a1 1 0 0 1 1-1zm2 14h2v-3H7zm3 0h1V3H5v12h1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1zm0-14H6v1h4zm2 7v7h3V8zm-8 7V8H1v7z"></path>
                    </svg>
	                <?= $page_part_fields['usp_3_text']; ?>
                </div>
                <a role="button" href="<?= $page_part_fields['button_vestiging_url']; ?>" class="nok-button nok-base-font nok-bg-white nok-text-darkerblue nok-visible-xs nok-align-self-stretch"
                        tabindex="0"><?= $page_part_fields['button_vestiging_text']; ?>
                </a>
            </footer>
        </div>
    </nok-hero>

<?php