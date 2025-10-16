<?php
/**
 * Template Name: Quote Showcase
 * Slug: nok-quote-showcase
 * Custom Fields:
 *  - layout:select(left|right),
 *  - colors:select(Transparant::nok-bg-body|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue|Blauw::nok-bg-darkerblue nok-text-contrast)!page-editable,
 *  - block-colors:select(Wit::nok-bg-white nok-text-darkestblue|Blauw::nok-bg-darkblue nok-text-contrast)!page-editable,
 *  - quote-items:repeater(quote:text,name:text,profession:text),
 *  - accordion-items:repeater(title:text,content:textarea,button_text:text,button_url:url),
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;

$default_colors = '';
$colors         = $context->has( 'colors' ) ? $context->get( 'colors' ) : $default_colors;
$block_colors   = $context->has( 'block-colors' ) ? $context->get( 'block-colors' ) : 'nok-bg-body--darker nok-dark-bg-darkblue nok-text-contrast';
$left           = ! $context->has( 'layout' ) || $context->get( 'layout' ) === 'left';
?>

<nok-section class="<?= $colors; ?> gradient-background">
    <div class="nok-section__inner">
        <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start nok-column-gap-3">
            <div class="nok-layout-flex-column nok-align-items-stretch" style="order:<?= $left ? '1' : '2'; ?>">
				<?php the_title( '<h1>', '</h1>' ); ?>
                <div><?php the_content(); ?></div>

				<?php if ($context->has( 'quote-items' )) :
				$x = 0;
				$quote_group = 'quote-group';

				$quote_data = json_decode( $context->get( 'quote-items', '[]' ), true );

				if ( empty( $quote_data ) ) {
					// Fallback to hardcoded array for demo/development
					$quote_data = [
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
					];
				}

				?>

                <nok-square-block class="nok-p-2 no-gap
                <?= $block_colors; ?>">
                    <div class="nok-scrollable__horizontal" id="quote-showcase-scroller" data-scroll-snapping="true"
                         data-draggable="true"
                         data-autoscroll="true">
                        <?php foreach ($quote_data as $index => $quote) : ?>
                        <div>
                            <blockquote class="nok-square-block__text"><?= esc_html( $quote['quote'] ); ?></blockquote>
                            <div class="nok-layout-flex-column nok-align-items-start no-gap">
                                <strong class="nok-fs-2"><?= esc_html( $quote['name'] ); ?></strong>
	                            <?= $quote['profession'] ? ('<p>' . esc_html( $quote['profession'] . '</p>')) : ''; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </nok-square-block>
                <?php endif; ?>
            </div>

			<?php if ( $context->has( 'accordion-items' ) ) : ?>
                <!-- Component: accordion items -->
                <div class="nok-layout-grid nok-layout-grid__1-column"
                     data-requires="./nok-accordion.mjs" data-require-lazy="true"
                     style="order:<?= $left ? '2' : '1'; ?>">

					<?php $x         = 0;
					$accordion_group = 'accordion-group';

					$accordion_data = json_decode( $context->get( 'accordion-items', '[]' ), true );

					if ( empty( $accordion_data ) ) {
						// Fallback to hardcoded array for demo/development
						$accordion_data = [
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
						];
					}

					foreach ( $accordion_data as $index => $specialist ) : ?>
                        <nok-accordion>
                            <details
                                    class="<?= $block_colors; ?> nok-rounded-border nok-text-contrast"
                                    name="<?= $accordion_group; ?>" <?= $index == 0 ? 'open' : ''; ?>>
                                <summary
                                        class="nok-py-1 nok-px-2 nok-fs-3 nok-fs-to-sm-2 fw-bold"><?= esc_html( $specialist['title'] ); ?></summary>
                                <div class="accordion-content nok-p-2 nok-pt-0">
                                    <p class="<?= isset( $specialist['button_url'] ) ? 'nok-mb-1' : ''; ?>"><?= wp_kses_post( $specialist['content'] ); ?></p>
									<?php if ( isset( $specialist['button_url'] ) ) : ?>
                                        <a href="<?= esc_url( $specialist['button_url'] ); ?>" role="button"
                                           class="nok-button nok-text-contrast nok-bg-darkblue--darker nok-dark-bg-darkestblue nok-visible-xs nok-align-self-stretch fill-mobile"
                                           tabindex="0">
											<?= esc_html( trim( $specialist['button_text'] ) !== '' ? trim( $specialist['button_text'] ) : 'Lees meer' ); ?> <?= Assets::getIcon('ui_arrow-right-long', 'nok-text-yellow'); ?>
                                        </a>
									<?php endif; ?>
                                </div>
                            </details>
                        </nok-accordion>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>

        </article>
    </div>
</nok-section>