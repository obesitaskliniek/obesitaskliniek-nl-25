<?php
/**
 * Single Template: Voorlichting (Information Session)
 *
 * Displays a single voorlichting event with registration form and alternatives.
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

get_header( 'voorlichting' );

// Get event data
$hubspotData  = Helpers::setup_hubspot_metadata( get_the_ID() );
$archive_url  = get_post_type_archive_link( 'voorlichting' );

// Pre-compute commonly used values
$is_online    = strtolower( $hubspotData['type'] ) === 'online';
$is_open      = $hubspotData['open'];
$soort        = ucfirst( $hubspotData['soort'] );
$locatie      = ucfirst( $hubspotData['locatie'] );
$type         = $hubspotData['type'];
$timestamp    = $hubspotData['timestamp'];

// Get related voorlichtingen
$limit       = 4;
$raw_ids     = explode( ';', $hubspotData['data_raw']['related_voorlichtingen'][0] ?? '' );
$related_ids = array_filter( array_map( 'intval', array_slice( $raw_ids, 0, $limit ) ) );

$alternatives = $related_ids ? get_posts( [
	'post__in'    => $related_ids,
	'post_type'   => 'voorlichting',
	'orderby'     => 'post__in',
	'numberposts' => count( $related_ids ),
	'post_status' => 'publish',
] ) : [];

$has_alternatives = count( $alternatives ) > 0;

// Badge classes based on event type
$badge_class = $is_online ? 'nok-bg-lightblue--lighter' : 'nok-bg-green--lighter';
$badge_text  = $is_online ? __( 'Online', THEME_TEXT_DOMAIN ) : __( 'Op locatie', THEME_TEXT_DOMAIN );
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-hero__inner
		nok-layout-grid nok-layout-grid__3-column fill-one
		nok-m-0 nok-border-radius-to-sm-0
		nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10 nok-subtle-shadow">
		<div class="article">
			<?php Helpers::render_breadcrumbs(); ?>
			<h1 class="nok-fs-6"><?= esc_html( "$soort $locatie ($type)" ); ?></h1>
			<div>
				<?= ucfirst( $hubspotData['intro'] ); ?>
				<?php if ( ! $is_open ) : ?>
					<div class="nok-alert nok-bg-greenyellow--lighter nok-p-1 nok-mt-1 nok-rounded-border nok-bg-alpha-10" role="alert">
						<p>
							Helaas, deze voorlichting is <?= esc_html( $hubspotData['status'] ); ?>! Aanmelden is daarom niet (meer) mogelijk.
							<?php if ( $has_alternatives ) : ?>
								Kijk bij de <a class="nok-hyperlink" href="#alternatieven">alternatieven</a>, of bekijk
							<?php else : ?>
								Bekijk
							<?php endif; ?>
							onze <a class="nok-hyperlink" href="<?= esc_url( $archive_url ); ?>">agenda</a> voor meer voorlichtingen.
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</nok-hero>

<nok-section class="no-aos z-ascend">
	<div class="nok-section__inner">
		<article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3">

			<!-- Main content -->
			<div class="baseline-grid nok-order-1 nok-order-lg-0" data-requires="./domule/modules/hnl.baseline-grid.mjs">
				<?= Helpers::classFirstP( $hubspotData['intro_lang'], 'fw-bold nok-fs-2' ); ?>
				<p>
					De <?= esc_html( $hubspotData['soort'] ); ?> start om <?= esc_html( $timestamp['start_time'] ); ?> en
					duurt ongeveer <?= esc_html( Helpers::minutesToDutchRounded( (int) $hubspotData['duur'] ) ); ?>,
					tot <?= esc_html( $timestamp['end_time'] ); ?> uur.
				</p>
				<?php if ( ! empty( $hubspotData['onderwerpen'] ) ) : ?>
					<h2>Onderwerpen</h2>
					<?= $hubspotData['onderwerpen']; ?>
				<?php endif; ?>
				<h2>Kosten</h2>
				<p>Deze voorlichting is gratis.</p>
			</div>

			<!-- Sidebar -->
			<div class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-gap-1 nok-layout-flex nok-layout-flex-column nok-align-items-stretch">

				<!-- Event details card -->
				<nok-square-block class="nok-bg-white nok-dark-bg-darkestblue nok-grid-gap-0_5 nok-pull-up-lg-3" data-shadow="true">
					<span class="nok-square-block__banner nok-badge <?= $badge_class; ?> nok-text-darkerblue">
						<?= esc_html( $badge_text ); ?>
					</span>

					<h2 class="nok-square-block__heading">
						<?= esc_html( "$soort $locatie" ); ?>
					</h2>

					<table class="nok-square-block__text nok-icon-table nok-fs-1">
						<tr>
							<td><?= Assets::getIcon( 'ui_calendar' ); ?></td>
							<td class="fw-bold"><?= esc_html( $timestamp['niceDateFull'] ); ?></td>
						</tr>
						<tr>
							<td><?= Assets::getIcon( 'ui_time' ); ?></td>
							<td><?= esc_html( "{$timestamp['start_time']} - {$timestamp['end_time']} uur" ); ?></td>
						</tr>
						<tr>
							<td><?= Assets::getIcon( 'ui_location' ); ?></td>
							<td><?= $is_online ? esc_html__( 'Online', THEME_TEXT_DOMAIN ) : esc_html( $locatie ); ?></td>
						</tr>
						<?php if ( ! $is_online ) :
							$address = Helpers::get_voorlichting_address( $hubspotData['locatie'], $hubspotData['data_raw'] );
							if ( $address['street'] ) : ?>
								<tr>
									<td></td>
									<td>
										<?= esc_html( $address['street'] ); ?><?= $address['housenumber'] ? ' ' . esc_html( $address['housenumber'] ) : ''; ?><br>
										<?= esc_html( trim( "{$address['postal_code']} {$address['city']}" ) ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( $address['phone'] ) : ?>
								<tr>
									<td><?= Assets::getIcon( 'ui_phone' ); ?></td>
									<td>
										<a href="tel:<?= esc_attr( preg_replace( '/[^0-9+]/', '', $address['phone'] ) ); ?>" class="nok-hyperlink">
											<?= esc_html( Helpers::format_phone( $address['phone'] ) ); ?>
										</a>
									</td>
								</tr>
							<?php endif; ?>
						<?php endif; ?>
					</table>

					<div class="nok-layout-flex nok-layout-flex-row nok-column-gap-0_5">
						<a role="button" href="#aanmelden"
						   class="nok-button nok-bg-yellow nok-text-darkerblue w-100 <?= $is_open ? '' : 'disabled'; ?>">
							<?php esc_html_e( 'Aanmelden', THEME_TEXT_DOMAIN ); ?>
						</a>
						<a role="button" href=""
						   class="nok-button nok-bg-lightgrey--lighter nok-text-darkerblue w-100 <?= $is_open ? '' : 'disabled'; ?>">
							<?= Assets::getIcon( 'ui_plus' ); ?> <?php esc_html_e( 'Agenda', THEME_TEXT_DOMAIN ); ?>
						</a>
					</div>
				</nok-square-block>

				<!-- Alternatives card -->
				<?php if ( $has_alternatives ) : ?>
					<nok-square-block
						class="nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10"
						data-shadow="true">
						<div class="nok-square-block__heading" id="alternatieven">
							<h2>Alternatieven</h2>
						</div>
						<div class="nok-square-block__text">
							<div class="nok-layout nok-layout-grid nok-grid-gap-0_25 nok-column-gap-0_25 nok-mb-1"
							     style="--grid-template-columns: 1fr auto auto auto;">
								<?php foreach ( $alternatives as $alt_post ) :
									$alt_data = Helpers::setup_hubspot_metadata( $alt_post->ID );
									?>
									<span><a class="nok-hyperlink" href="<?= esc_url( get_permalink( $alt_post->ID ) ); ?>"><?= esc_html( $alt_data['timestamp']['niceDateFull'] ); ?></a></span>
									<span class="nok-justify-self-start"><?= esc_html( $alt_data['timestamp']['start_time'] ); ?></span>
									<span class="nok-justify-self-center">-</span>
									<span class="nok-justify-self-end"><?= esc_html( $alt_data['timestamp']['end_time'] ); ?></span>
								<?php endforeach; ?>
							</div>
							<a role="button" href="<?= esc_url( $archive_url ); ?>"
							   class="nok-button nok-justify-self-start w-100 nok-bg-darkerblue nok-text-contrast"
							   tabindex="0">
								Bekijk volledige agenda <?= Assets::getIcon( 'ui_calendar-full' ); ?>
							</a>
						</div>
					</nok-square-block>
				<?php endif; ?>

			</div>
		</article>
	</div>
</nok-section>

<nok-section class="nok-bg-body--darker">
	<div class="nok-section__inner">
		<h2 id="aanmelden" class="nok-mb-1">Aanmelden</h2>
		<nok-square-block class="nok-bg-body nok-text-contrast" data-shadow="true">
			<?php if ( function_exists( 'gravity_form' ) && $is_open ) :
				gravity_form( 1, false, false );
			else : ?>
				<p>
					Helaas, deze voorlichting is <?= esc_html( $hubspotData['status'] ); ?>! Aanmelden is daarom niet (meer) mogelijk.
					<?php if ( $has_alternatives ) : ?>
						Kijk bij de <a class="nok-hyperlink" href="#alternatieven">alternatieven</a>, of ga naar
					<?php else : ?>
						Ga naar
					<?php endif; ?>
					onze <a class="nok-hyperlink" href="<?= esc_url( $archive_url ); ?>">agenda</a> voor meer voorlichtingen.
				</p>
			<?php endif; ?>
		</nok-square-block>
	</div>
</nok-section>

<?php
Theme::get_instance()->embed_post_part_template( 'nok-voorlichtingen-carousel', [
	'colors' => 'nok-bg-darkblue nok-text-white nok-dark-bg-darkerblue',
] );

get_footer();