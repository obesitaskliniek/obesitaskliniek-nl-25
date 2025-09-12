<?php
/* Template Name: Event */

get_header();

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();
$hubspotData   = Helpers::setup_hubspot_metadata( get_the_ID() );

?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner nok-m-0 nok-border-radius-to-sm-0
        nok-layout-grid nok-layout-grid__3-column one-fill nok-column-gap-3
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

            <article>
                <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-2 nok-fs-to-md-1">
                    kruimelpad (todo)
                </h2>
                <?php printf( '<h1 class="nok-fs-6">%s %s (%s)</h1>',
                        ucfirst( $hubspotData['soort'] ),
                        $hubspotData['locatie'],
                        $hubspotData['type'] );
                ?>
                <div>
                    <?= ucfirst( $hubspotData['intro'] ); ?>
                </div>
                <?php
                if (!$hubspotData['open']) {
                    echo "<div class='nok-alert nok-bg-greenyellow--lighter nok-p-1 nok-mb-1 nok-rounded-border nok-bg-alpha-10' role='alert'>
                                <p>Helaas, deze voorlichting is {$hubspotData['status']}! Aanmelden is daarom niet (meer) mogelijk. Kijk bij de <a class='nok-hyperlink' href='#alternatieven'>alternatieven</a>, 
                                of ga naar onze <a class='nok-hyperlink' href='#'>agenda</a> <a href='#'>agenda</a> <a href='#'>agenda</a> voor meer voorlichtingen.</p>
                              </div>";
                }
                ?>
            </article>

        </div>
    </nok-hero>

    <nok-section>
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3">

                <div class="body-copy baseline-grid nok-order-1 nok-order-lg-0" data-requires="./modules/hnl.baseline-grid.mjs?cache=<?= time(); ?>">
                    <?= Helpers::classFirstP( $hubspotData['intro_lang'], "fw-bold nok-fs-2" ); ?>
                    <p>De <?= $hubspotData['soort'];?> start om <?= $hubspotData['timestamp']['start_time']; ?> en duurt ongeveer <?= Helpers::minutesToDutchRounded(intval ( $hubspotData['duur'] )); ?>, tot <?= $hubspotData['timestamp']['end_time']; ?> uur.</p>
                    <?php if (!empty($hubspotData['onderwerpen'])) : ?>
                    <h2>Onderwerpen</h2>
                    <p>Wat kunt u verwachten van deze voorlichting?</p>
                    <?= $hubspotData['onderwerpen']; ?>
                    <?php endif; ?>
                    <h2>Kosten</h2>
                    <p>
                        Deze voorlichting is gratis.
                    </p>
                    <h2 id="aanmelden">Aanmelden</h2>
                    <?php
                    if ( function_exists( 'gravity_form' ) && $hubspotData['open'] ) {
                        gravity_form( 1, false, false );
                    } else {
                        echo "<p>Helaas, deze voorlichting is {$hubspotData['status']}! Aanmelden is daarom niet (meer) mogelijk. Kijk bij de <a class='nok-hyperlink' href='#alternatieven'>alternatieven</a>, 
                        of ga naar onze <a class='nok-hyperlink' href='#'>agenda</a> voor meer voorlichtingen.</p>";
                    }
                    ?>

                </div>

                <div class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-gap-1 nok-layout-flex nok-layout-flex-column nok-align-items-stretch">
                    <nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-alpha-10 nok-pull-up-lg-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <?php
                            printf('<h2 class="nok-text-yellow nok-dark-text-yellow nok-fs-2 nok-fs-to-md-2">%s (%s)</p>',
                                    ucfirst( $hubspotData['soort'] ),
                                    $hubspotData['type']);
                            printf('<h2>%s %s</h2>',
                                    Assets::getIcon('calendar'),
                                    $hubspotData['timestamp']['niceDateFull']);
                            printf('<h3 class="fw-normal">%s %s</h2>',
                                    Assets::getIcon('location'),
                                    ucfirst( $hubspotData['locatie'] ));
                            printf('<h3 class="fw-normal">%s %s - %s uur</h2>',
                                    Assets::getIcon('time'),
                                    $hubspotData['timestamp']['start_time'],
                                    $hubspotData['timestamp']['end_time']);
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
                        <a role="button" href="#aanmelden" class="nok-button nok-justify-self-start w-100
                nok-base-font nok-bg-yellow nok-text-contrast <?= $hubspotData['open'] ? '' : 'disabled'; ?>" tabindex="0">
                            Aanmelden
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 12 12" width="12" height="12"
                                 stroke="currentColor"
                                 style="stroke-linecap: round; stroke-linejoin: round;">
                                <path d="M 5,0 L 5,10 M 5,10 L 0,4 M 5,10 L 10,4" data-name="Down"></path>
                            </svg>
                        </a>
                        <a role="button" href="" class="nok-button nok-justify-self-start w-100
                nok-base-font nok-bg-lightgrey--lighter nok-text-contrast <?= $hubspotData['open'] ? '' : 'disabled'; ?>" tabindex="0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 12 12" width="12" height="12"
                                 stroke="currentColor"
                                 style="stroke-linecap: round; stroke-linejoin: round;">
                                <path d="M 5,0 L 5,10 M 0,5 L 10,5" data-name="Plus"></path>
                            </svg>
                            Voeg toe aan agenda
                        </a>
                    </nok-square-block>

                    <nok-square-block class="nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2>Alternatieven</h2>
                        </div>
                    </nok-square-block>
                </div>
            </article>
        </div>
    </nok-section>

<?php
print '<pre style="max-height: 20vh; overflow: scroll; font-size:0.7rem; line-height: 1;">';
var_dump($hubspotData['data_raw']);
print '</pre>';
?>

<?php

get_template_part( 'template-parts/post-parts/nok-voorlichtingen-carousel', null, $args = array(
        'colors' => 'nok-bg-darkblue nok-text-white nok-dark-bg-darkerblue',
) );

get_footer();

?>