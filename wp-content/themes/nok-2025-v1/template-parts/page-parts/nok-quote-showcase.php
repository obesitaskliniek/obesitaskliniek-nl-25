<?php
/**
 * Template Name: Quote Showcase
 * Description: Two-column layout with quote carousel and accordion items
 * Slug: nok-quote-showcase
 * Custom Fields:
 * - layout:select(left|right)!page-editable!default(left)
 * - colors:select(Transparant::nok-bg-body|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable!default(nok-bg-body)
 * - block_colors:select(Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable!default(nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast)
 * - quote_items:repeater(quote:text,name:text,profession:text)
 * - accordion_items:repeater(title:text,content:textarea,button_text:text,button_url:url)
 * - accordion_button_text:text!default(Lees meer)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$c = $context;

$left = $c->layout->is('left');
?>

<nok-section class="<?= $c->colors ?> gradient-background">
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
            <div class="nok-layout-flex-column nok-align-items-stretch" style="order:<?= $left ? '1' : '2' ?>">
				<?php the_title( '<h1>', '</h1>' ); ?>
                <div><?php the_content(); ?></div>

	            <?php if ($c->has('quote_items')):
		            $quote_data = $c->quote_items->json([
			            [
				            'quote'      => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!',
				            'name'       => 'Henk de Vries',
				            'profession' => 'Voorbeeldkundige',
			            ],
			            [
				            'quote'      => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!',
				            'name'       => 'Tekst 1',
				            'profession' => 'Voorbeeldkundige',
			            ]
		            ]);

				?>

                <nok-square-block class="nok-p-2 no-gap <?= $c->block_colors ?>">
                    <div class="nok-scrollable__horizontal" id="quote-showcase-scroller" data-scroll-snapping="true"
                         data-draggable="true"
                         data-autoscroll="true">
                        <?php foreach ($quote_data as $index => $quote) : ?>
                        <div>
                                <blockquote class="nok-square-block__text"><?= esc_html($quote['quote']) ?></blockquote>
                            <div class="nok-layout-flex-column nok-align-items-start no-gap">
                                    <strong class="nok-fs-2"><?= esc_html($quote['name']) ?></strong>
									<?php if (!empty($quote['profession'])) : ?>
                                        <p><?= esc_html($quote['profession']) ?></p>
									<?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </nok-square-block>
                <?php endif; ?>
            </div>

            <!-- Component: accordion items -->
            <div class="nok-layout-grid nok-layout-grid__1-column"
                 data-requires="./nok-accordion.mjs" data-require-lazy="true"
                 style="order:<?= $left ? '2' : '1' ?>">

                <?php
                $accordion_group = 'accordion-group';

                $accordion_data = $c->accordion_items->json([
                    [ 'title'       => 'Titel 1',
                      'content'     => 'Tekst 1',
                      'button_text' => 'Button tekst',
                      'button_url'  => '#'
                    ],
                    [ 'title'       => 'Titel 2',
                      'content'     => 'Tekst 2',
                      'button_text' => 'Button tekst',
                      'button_url'  => '#'
                    ]
                ]);

				foreach ($accordion_data as $index => $item) : ?>
                    <nok-accordion>
                        <details class="<?= $c->block_colors ?> nok-rounded-border nok-text-contrast"
                                 name="<?= esc_attr($accordion_group) ?>" <?= $index == 0 ? 'open' : '' ?>>
                            <summary class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold">
								<?= esc_html($item['title']) ?>
                            </summary>
                            <div class="accordion-content nok-p-2 nok-pt-0">
                                <p class="<?= !empty($item['button_url']) ? 'nok-mb-1' : '' ?>">
									<?= wp_kses_post($item['content']) ?>
                                </p>
								<?php if (!empty($item['button_url'])) : ?>
                                    <a href="<?= esc_url($item['button_url']) ?>" role="button"
                                       class="nok-button nok-text-contrast nok-bg-darkblue--darker nok-dark-bg-darkestblue nok-visible-xs nok-align-self-stretch fill-mobile"
                                       tabindex="0">
										<?= !empty(trim($item['button_text'])) ? esc_html($item['button_text']) : $c->accordion_button_text ?>
										<?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow') ?>
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