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

						<?php foreach ( $voorlichtingen as $post ) :
							setup_postdata( $post );
							$hubspotData = Helpers::setup_hubspot_metadata( $post->ID );
							$is_open     = $hubspotData['open'];
							$is_online   = strtolower( $hubspotData['type'] ) === 'online';
							?>

							<nok-square-block class="nok-bg-darkblue nok-dark-bg-darkestblue nok-text-contrast" data-shadow="false">
								<div class="nok-square-block__heading">
									<h3 class="nok-fs-3 nok-fs-to-md-2">
										<?= esc_html( $hubspotData['timestamp']['niceDateFull'] ); ?>
										<small class="nok-text-yellow">(<?= esc_html( $hubspotData['type'] ); ?>)</small>
									</h3>
								</div>
								<div class="nok-square-block__text">
									<p class="nok-layout-flex-row nok-column-gap-0_5">
										<?= Assets::getIcon( 'ui_time' ); ?>
										<span><?= esc_html( $hubspotData['timestamp']['start_time'] ); ?> - <?= esc_html( $hubspotData['timestamp']['end_time'] ); ?> uur</span>
									</p>
									<p class="nok-layout-flex-row nok-column-gap-0_5">
										<?= Assets::getIcon( $is_online ? 'ui_laptop' : 'ui_location' ); ?>
										<span><?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html( ucfirst( $hubspotData['locatie'] ) ); ?></span>
									</p>
									<?php if ( ! $is_open ) : ?>
										<p class="nok-text-yellow">
											<?= esc_html( ucfirst( $hubspotData['status'] ) ); ?>
										</p>
									<?php endif; ?>
								</div>
								<a role="button" href="<?= esc_url( get_permalink( $post->ID ) ); ?>"
								   class="nok-button nok-justify-self-start w-100 nok-bg-yellow nok-text-contrast"
								   tabindex="0">
									<?php $is_open ? esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN ) : esc_html_e( 'Bekijken', THEME_TEXT_DOMAIN ); ?>
									<?= Assets::getIcon( 'ui_arrow-right-long' ); ?>
								</a>
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
