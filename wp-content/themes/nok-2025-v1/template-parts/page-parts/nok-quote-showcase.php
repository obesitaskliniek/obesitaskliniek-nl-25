<?php
/**
 * Template Name: Quote Showcase
 * Slug: nok-quote-showcase
 * Custom Fields:
 *  - layout:select(left|right)
 *  - colors:select(Transparant::nok-bg-body|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)
 *  - accordion-items:repeater(title:text,content:textarea,button_text:text,button_url:url)
 */

$default_colors = '';
$colors = ($page_part_fields['colors'] ?? "") !== "" ? $page_part_fields['colors'] : $default_colors;
$left = empty($page_part_fields['layout']) || $page_part_fields['layout'] === 'left';
?>

    <nok-section class="<?= $colors;?> gradient-background">
        <div class="nok-section__inner">
            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
                <div class="nok-layout-flex-column nok-align-items-stretch" style="order:<?= $left ? '1' : '2'; ?>">
                    <?php the_title('<h1>', '</h1>'); ?>
                    <div><?php the_content(); ?></div>

                    <nok-square-block class="nok-p-2 no-gap
                nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast">
                        <div class="nok-scrollable__horizontal" id="quote-showcase-scroller" data-scroll-snapping="true" data-draggable="true"
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
                            <div>
                                <blockquote class="nok-square-block__text">
                                    "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure
                                    perspiciatis quod, quos
                                    vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas,
                                    quia
                                    reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!"
                                </blockquote>
                                <div class="nok-layout-flex-column nok-align-items-start no-gap">
                                    <strong class="nok-fs-2">Foo Bar</strong>
                                    <p>Bewegingsdeskundige</p>
                                </div>
                            </div>
                        </div>
                    </nok-square-block>
                </div>

                <!-- Component: accordion items -->
                <div class="nok-layout-grid nok-layout-grid__1-column"
                     data-requires="./nok-accordion.mjs" data-require-lazy="true" style="order:<?= $left ? '2' : '1'; ?>">

                    <?php
                    $x = 0;
                    $accordion_group = 'accordion-group';

                    $accordion_data = json_decode($page_part_fields['accordion-items'] ?? '[]', true);

                    if (empty($accordion_data)) {
                        // Fallback to hardcoded array for demo/development
                        $accordion_data = [
                                ['title' => 'Titel 1', 'content' => 'Tekst 1', 'button_text' => 'Button tekst', 'button_url' => '#'],
                                ['title' => 'Titel 2', 'content' => 'Tekst 2', 'button_text' => 'Button tekst', 'button_url' => '#']
                        ];
                    }

                    foreach ($accordion_data as $index => $specialist) : ?>
                        <nok-accordion>
                            <details class="nok-bg-white nok-dark-bg-darkerblue nok-dark-text-white nok-rounded-border nok-text-contrast"
                                     name="<?= $accordion_group; ?>" <?= $index == 0 ? 'open' : ''; ?>>
                                <summary class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold"><?= esc_html($specialist['title']); ?></summary>
                                <div class="accordion-content nok-p-2 nok-pt-0">
                                    <p class="<?= isset($specialist['button_url']) ? 'nok-mb-1' : '';?>"><?= wp_kses_post($specialist['content']); ?></p>
                                    <?php if (isset($specialist['button_url'])) : ?>
                                    <a href="<?= $specialist['button_url']; ?>" role="button" class="nok-button nok-text-contrast nok-bg-darkerblue nok-dark-bg-darkblue nok-visible-xs nok-align-self-stretch fill-mobile" tabindex="0">
                                        <?= esc_html(trim($specialist['button_text']) !== '' ? trim($specialist['button_text']) : 'Lees meer'); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </nok-accordion>
                    <?php endforeach; ?>
                </div>

            </article>
        </div>
    </nok-section>


<?php