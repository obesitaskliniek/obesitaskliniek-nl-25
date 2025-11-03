<?php
/* Template Name: Event */

get_header( 'voorlichting' );

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();
$hubspotData   = Helpers::setup_hubspot_metadata( get_the_ID() );

$limit       = 4;
$raw_ids     = explode( ';', $hubspotData['data_raw']['related_voorlichtingen'][0] );
$related_ids = array_map( 'intval', array_slice( $raw_ids, 0, $limit ) );

$alternatives = get_posts( [
        'post__in'    => $related_ids,
        'post_type'   => 'voorlichting',
        'orderby'     => 'post__in',
        'numberposts' => count( $related_ids ),
        'post_status' => 'publish'
] );

?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner
        nok-layout-grid nok-layout-grid__3-column fill-one
        nok-m-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10 nok-subtle-shadow">
            <div class="article">
                <h2 class="nok-fs-2 nok-fs-to-md-1">
                    kruimelpad (todo)
                </h2>
	            <?php printf( '<h1 class="nok-fs-6">%s %s (%s)</h1>',
		            ucfirst( $hubspotData['soort'] ),
		            $hubspotData['locatie'],
		            $hubspotData['type'] );
	            ?>
                <div>
		            <?= ucfirst( $hubspotData['intro'] ); ?>
		            <?php
		            if ( ! $hubspotData['open'] ) {
			            echo "<div class='nok-alert nok-bg-greenyellow--lighter nok-p-1 nok-mt-1 nok-rounded-border nok-bg-alpha-10' role='alert'><p>
                                Helaas, deze voorlichting is {$hubspotData['status']}! Aanmelden is daarom niet (meer) mogelijk.";
			            if ( count( $alternatives ) > 0 ) {
				            echo "Kijk bij de <a class='nok-hyperlink' href='#alternatieven'>alternatieven</a>, of bekijk";
			            } else {
				            echo "Bekijk ";
			            }
			            echo "onze <a href='#'>agenda</a> voor meer voorlichtingen.";
			            echo "</p></div>";
		            }
		            ?>
                </div>
            </div>
        </div>
    </nok-hero>

    <nok-section class="no-aos z-ascend">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3">

                <div class="baseline-grid nok-order-1 nok-order-lg-0" data-requires="./domule/modules/hnl.baseline-grid.mjs">
                    <?= Helpers::classFirstP( $hubspotData['intro_lang'], "fw-bold nok-fs-2" ); ?>
                    <p>De <?= $hubspotData['soort']; ?> start om <?= $hubspotData['timestamp']['start_time']; ?> en
                        duurt ongeveer <?= Helpers::minutesToDutchRounded( intval( $hubspotData['duur'] ) ); ?>,
                        tot <?= $hubspotData['timestamp']['end_time']; ?> uur.</p>
                    <?php if ( ! empty( $hubspotData['onderwerpen'] ) ) : ?>
                        <h2>Onderwerpen</h2>
                        <?= $hubspotData['onderwerpen']; ?>
                    <?php endif; ?>
                    <h2>Kosten</h2>
                    <p>
                        Deze voorlichting is gratis.
                    </p>

                </div>

                <div class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-gap-1 nok-layout-flex nok-layout-flex-column nok-align-items-stretch">
                    <nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-alpha-10 nok-pull-up-lg-3"
                                      data-shadow="true">
                        <div class="nok-square-block__heading">
                            <?php
                            printf( '<h2 class="nok-text-yellow nok-dark-text-yellow nok-fs-2 nok-fs-to-md-2">%s (%s)</p>',
                                    ucfirst( $hubspotData['soort'] ),
                                    $hubspotData['type'] );
                            printf( '<h2>%s</h2>',
                                    $hubspotData['timestamp']['niceDateFull'] );
                            printf( '<h3 class="fw-normal">%s</h2>',
                                    ucfirst( $hubspotData['locatie'] ) );
                            printf( '<h3 class="fw-normal">%s - %s uur</h2>',
                                    $hubspotData['timestamp']['start_time'],
                                    $hubspotData['timestamp']['end_time'] );
                            ?>
                        </div>
                        <div class="nok-square-block__text nok-fs-1">

                            <address>
                                <span class="location" title="Nederlandse Obesitas Kliniek Venlo" id="location-name">Vestiging <?= ucfirst( $hubspotData['locatie'] ); ?></span>
                                <span class="street" id="street">Noorderpoort 9B</span>
                                <span class="postal-code" id="zipcode">5916 PJ Venlo</span>
                                <span class="phone" id="phone"><a href="tel:077 - 303 06 30" class="nok-hyperlink">077 - 303 06 30</a></span>
                            </address>
                        </div>
                        <a role="button" href="#aanmelden" class="nok-button nok-button--large nok-justify-self-start w-100
                nok-bg-yellow nok-text-contrast <?= $hubspotData['open'] ? '' : 'disabled'; ?>" tabindex="0">
                            Aanmelden <?= Assets::getIcon( 'ui_arrow-down' ); ?>
                        </a>
                        <a role="button" href="" class="nok-button nok-justify-self-start w-100
                nok-bg-lightgrey--lighter nok-text-contrast <?= $hubspotData['open'] ? '' : 'disabled'; ?>"
                           tabindex="0">
                            <?= Assets::getIcon( 'ui_plus' ); ?> Voeg toe aan agenda
                        </a>
                    </nok-square-block>

                    <?php if ( count( $alternatives ) > 0 ) : ?>
                        <nok-square-block
                                class="nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10"
                                data-shadow="true">
                            <div class="nok-square-block__heading">
                                <h2>Alternatieven</h2>
                            </div>
                            <div class="nok-square-block__text">
                                <div class="nok-layout nok-layout-grid nok-grid-gap-0_25 nok-column-gap-0_25 nok-mb-1"
                                     style="--grid-template-columns: 1fr auto auto auto;">

                                    <?php

                                    foreach ( $alternatives as $post ) {
                                        $hubspotData = Helpers::setup_hubspot_metadata( $post->ID );
                                        printf(
                                                '<span class="nok-justify-self-start"><a class="nok-hyperlink" href="%s">%s</a></span><span class="nok-justify-self-start">%s</span><span class="nok-justify-self-center">-</span><span class="nok-justify-self-end">%s</span>',
                                                get_permalink( $post->ID ),
                                                $hubspotData['timestamp']['niceDateFull'],
                                                $hubspotData['timestamp']['start_time'],
                                                $hubspotData['timestamp']['end_time']
                                        );
                                    }
                                    ?>
                                </div>
                                <a role="button" href="#aanmelden" class="nok-button nok-button nok-justify-self-start w-100
                                nok-bg-body--darker nok-text-contrast <?= $hubspotData['open'] ? '' : 'disabled'; ?>"
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
                <?php
                if ( function_exists( 'gravity_form' ) && $hubspotData['open'] ) {
                    gravity_form( 1, false, false );
                } else {
                    echo "<p>Helaas, deze voorlichting is {$hubspotData['status']}! Aanmelden is daarom niet (meer) mogelijk. Kijk bij de <a class='nok-hyperlink' href='#alternatieven'>alternatieven</a>, 
                        of ga naar onze <a class='nok-hyperlink' href='#'>agenda</a> voor meer voorlichtingen.</p>";
                }
                ?>
            </nok-square-block>
        </div>
    </nok-section>

<?php
//print '<pre style="max-height: 20vh; overflow: scroll; font-size:0.7rem; line-height: 1;">';
//var_dump($hubspotData['data_raw']);
//print '</pre>';

// With this:
use NOK2025\V1\Theme;

$theme = Theme::get_instance();
$theme->embed_post_part_template( 'nok-voorlichtingen-carousel', [
        'colors' => 'nok-bg-darkblue nok-text-white nok-dark-bg-darkerblue',
] );

get_footer();