<?php
/**
 * Block Part: Vestiging Voorlichtingen
 * Description: Carousel of upcoming voorlichtingen, optionally filtered by vestiging
 * Slug: vestiging-voorlichtingen
 * Icon: calendar-alt
 * Keywords: voorlichting, carousel, vestiging, agenda
 * Custom Fields:
 * - title:text!default(Voorlichtingen)
 * - background_color:text!default(nok-bg-darkerblue)
 * - text_color:text!default(nok-text-white)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @var \WP_Post[] $voorlichtingen Voorlichting posts from render.php
 * @var string     $all_url        URL to full voorlichtingen overview
 * @var bool       $show_all_link  Whether to show the "Bekijk alle" link
 * @var string|null $city          City name or null for all locations
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

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
						foreach ( $voorlichtingen as $post ) :
							setup_postdata( $post );
							$hubspotData = Helpers::setup_hubspot_metadata( $post->ID );
							$is_open     = $hubspotData['open'];
							$is_online   = strtolower( $hubspotData['type'] ) === 'online';
							?>

							<nok-square-block class="nok-bg-white nok-dark-bg-darkestblue nok-grid-gap-0_5" data-shadow="true">

								<span class="nok-square-block__banner nok-badge <?= $is_online ? 'nok-bg-lightblue--lighter' : 'nok-bg-green--lighter'; ?> nok-text-darkerblue">
									<?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html__( 'Op locatie', THEME_TEXT_DOMAIN ); ?>
								</span>

								<h3 class="nok-square-block__heading">
									<a href="<?= esc_url( get_permalink( $post->ID ) ); ?>" class="nok-text-darkerblue nok-dark-text-white">
										<?= esc_html( ucfirst( $hubspotData['soort'] ) ); ?> <?= esc_html( ucfirst( $hubspotData['locatie'] ) ); ?>
									</a>
								</h3>

								<table class="nok-square-block__text nok-icon-table">
									<tr>
										<td><?= Assets::getIcon( 'ui_calendar' ); ?></td>
										<td class="fw-bold"><?= esc_html( ucfirst( $hubspotData['timestamp']['niceDateFull'] ) ); ?></td>
									</tr>
									<tr>
										<td><?= Assets::getIcon( 'ui_time' ); ?></td>
										<td><?= esc_html( $hubspotData['timestamp']['start_time'] ); ?> - <?= esc_html( $hubspotData['timestamp']['end_time'] ); ?> uur</td>
									</tr>
									<tr>
										<td><?= Assets::getIcon( 'ui_location' ); ?></td>
										<td><?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html( ucfirst( $hubspotData['locatie'] ) ); ?></td>
									</tr>
								</table>

								<div class="nok-layout-flex nok-layout-flex-row nok-column-gap-0_5">
									<a href="<?= esc_url( get_permalink( $post->ID ) ); ?>#aanmelden"
									   class="nok-button nok-bg-yellow nok-text-darkerblue w-100 <?= ! $is_open ? 'disabled' : ''; ?>">
										<?php esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN ); ?>
									</a>
									<a href="<?= esc_url( get_permalink( $post->ID ) ); ?>"
									   class="nok-button nok-bg-lightgrey--lighter nok-text-darkerblue w-100 nok-dark-text-white">
										<?php esc_html_e( 'Informatie', THEME_TEXT_DOMAIN ); ?>
									</a>
								</div>
							</nok-square-block>

						<?php endforeach; ?>
						<?php wp_reset_postdata(); ?>

					</div>
                    <?php if ( $show_all_link ) : ?>
                        <a href="<?= esc_url( $all_url ); ?>" class="nok-button nok-bg-darkerblue nok-text-inherit nok-mt-2">
                            <?php esc_html_e( 'Bekijk alle voorlichtingen', THEME_TEXT_DOMAIN ); ?>
                            <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ); ?>
                        </a>
                    <?php endif; ?>
				</div>

			</article>

		</div>
	</div>
</nok-section>
