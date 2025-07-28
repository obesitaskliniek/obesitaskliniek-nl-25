<?php
/**
 * Template Name: Quote Showcase
 * Slug: nok-quote-showcase
 * Custom Fields:
 *  - layout:select(left|right)
 */

$left = ( $page_part_fields['layout'] ?? 'left' ) == 'left';
?>

<nok-section>
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
            <div class="nok-layout-flex-column nok-align-items-stretch" style="order:<?= $left ? '1' : '2'; ?>">
	            <?php the_title('<h1>', '</h1>'); ?>
	            <div><?php the_content(); ?></div>

                <nok-square-block class="nok-p-2 no-gap
                nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast">
                    <div class="nok-scrollable__horizontal" data-scroll-snapping="true" data-draggable="true"
                         data-autoscroll="true">
                        <div>
                            <blockquote class="nok-square-block__text">
                                "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                                perspiciatis quod, quos
                                vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas,
                                quia
                                reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!"
                            </blockquote>
                            <div class="nok-layout-flex-column nok-align-items-start no-gap">
                                <strong class="nok-fs-2">John Doe</strong>
                                <p>Internist</p>
                            </div>
                        </div>
                        <div>
                            <blockquote class="nok-square-block__text">
                                "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                                perspiciatis quod, quos
                                vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas,
                                quia
                                reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!"
                            </blockquote>
                            <div class="nok-layout-flex-column nok-align-items-start no-gap">
                                <strong class="nok-fs-2">Jane Doe</strong>
                                <p>Di&euml;tist</p>
                            </div>
                        </div>
                    </div>
                </nok-square-block>
            </div>

            <!-- Component: accordion items -->
            <div class="nok-layout-grid nok-layout-grid__1-column"
                 data-requires="./nok-accordion.mjs?cache=<?= time(); ?>" style="order:<?= $left ? '2' : '1'; ?>">
                <nok-accordion>
                    <details
                            class="nok-bg-white nok-dark-bg-darkerblue nok-dark-text-white nok-rounded-border nok-text-contrast">
                        <summary class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold">Los blok (niet onderdeel van accordion)</summary>
                        <div class="accordion-content nok-p-2 nok-pt-0">
                            <p class="nok-mb-1">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                                perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo
                                iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum.
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit!
                            </p>
                        </div>
                    </details>
                </nok-accordion>

                <?php
                $x = 0;
                $accordion_group = 'accordion-group';
                $specialisten = array('Arts', 'Internist', 'Diëtist', 'Psycholoog', 'Bewegingsdeskundige', 'Chirurg');
                foreach ($specialisten as $specialist) : ?>
                    <nok-accordion>
                        <details
                                class="nok-bg-white nok-dark-bg-darkerblue nok-dark-text-white nok-rounded-border nok-text-contrast"
                                name="<?= $accordion_group; ?>" <?= $x == 0 ? 'open' : ''; ?>>
                            <summary
                                    class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold"><?= $specialist; ?></summary>
                            <div class="accordion-content nok-p-2 nok-pt-0">
                                <p class="nok-mb-1">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                                    perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo
                                    iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum.
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit!
                                </p>
                                <button class="nok-button nok-base-font nok-text-contrast nok-bg-darkerblue nok-dark-bg-darkblue nok-visible-xs align-self-stretch"
                                        tabindex="0">Over de <?= $specialist; ?>
                                </button>
                            </div>
                        </details>
                    </nok-accordion>
                    <?php $x++; endforeach; ?>
            </div>

        </article>
    </div>
</nok-section>


<?php