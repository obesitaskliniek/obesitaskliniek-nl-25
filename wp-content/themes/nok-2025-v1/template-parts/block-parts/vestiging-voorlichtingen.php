<?php
/**
 * Block Part: Vestiging Agenda
 * Description: Carousel of upcoming agenda items, optionally filtered by vestiging
 * Slug: vestiging-voorlichtingen
 * Icon: calendar-alt
 * Keywords: voorlichting, carousel, vestiging, agenda
 * Custom Fields:
 * - title:text!default(Agenda)
 * - background_color:text!default(nok-bg-darkerblue)
 * - text_color:text!default(nok-text-white)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @var \WP_Post[] $agenda_items   Agenda posts from render.php
 * @var string     $all_url        URL to the complete agenda
 * @var bool       $show_all_link  Whether to show the "Bekijk alle" link
 * @var string|null $city          City name or null for all locations
 */

use NOK2025\V1\Agenda;
use NOK2025\V1\Assets;

$c        = $context;
$title    = $c->has( 'title' ) ? $c->title->raw() : '';
$bg_color = $c->background_color->raw();
$tx_color = $c->text_color->raw();
$colors   = esc_attr( trim( "$bg_color $tx_color" ) );
?>

<nok-section id="voorlichtingen">
	<div class="nok-section__inner--stretched <?= $colors; ?>">
		<div class="nok-section__inner">

			<article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
				<header class="nok-layout-flex nok-layout-flex-row nok-justify-content-space-between nok-align-items-center">
					<?php if ( $title ) : ?>
						<h2 class="nok-fs-4"><?= esc_html( $title ); ?></h2>
					<?php endif; ?>
				</header>

				<div class="nok-mt-2 nok-align-self-stretch">
					<div class="nok-layout-grid nok-layout-grid__3-column
						nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true">

						<?php
						global $post;
						$timezone = new DateTimeZone( 'Europe/Amsterdam' );
						foreach ( $agenda_items as $post ) :
							setup_postdata( $post );
							Agenda::render_card(
								post: $post,
								timezone: $timezone,
								show_info_link: true,
								show_title: true,
								heading_level: 3,
							);
						endforeach;
						?>
						<?php wp_reset_postdata(); ?>

					</div>
                    <?php if ( $show_all_link ) : ?>
                        <a href="<?= esc_url( $all_url ); ?>" class="nok-button nok-bg-darkerblue nok-text-contrast nok-mt-2">
                            <?php esc_html_e( 'Bekijk de volledige agenda', THEME_TEXT_DOMAIN ); ?>
                            <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ); ?>
                        </a>
                    <?php endif; ?>
				</div>

			</article>

		</div>
	</div>
</nok-section>
