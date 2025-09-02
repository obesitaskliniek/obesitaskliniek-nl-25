<?php
/* Template Name: Event */

get_header();


use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();

$into_lang_default = '<p>Heeft u te maken met ernstig overgewicht en overweegt u een maagverkleining? Dan is het goed om te weten wat u allemaal te wachten staat. Als u in aanmerking komt voor behandeling, verandert er veel in uw leven. Betrouwbare, actuele informatie is erg belangrijk. Daarom organiseren wij maandelijks een informatiebijeenkomst over de behandeling van ernstig overgewicht met behulp van een maagverkleinende operatie.</p>
<p>Tijdens de voorlichting vertellen we u meer over het hele traject en de operatie en beantwoorden we graag al uw vragen. Ook uw partner, vrienden of familie zijn van harte uitgenodigd om een bijeenkomst bij te wonen.</p>';

$hubspotData = get_post_meta( get_the_ID() );
$eventDate   = Helpers::getDateParts( new DateTime( $hubspotData['aanvangsdatum_en_tijd'][0], new DateTimeZone( 'Europe/Amsterdam' ) ), intval ( $hubspotData['duur'][0] ) );
$eventData   = array(
        'soort'       => get_post_type(),
        'type'        => strtolower( $hubspotData['type'][0] ),
        'locatie'     => $hubspotData['vestiging'][0],
        'duur'        => intval($hubspotData['duur'][0]),
        'intro'       => $hubspotData['intro_kort'][0] ?? '',
        'intro_lang'  => $hubspotData['intro_lang'][0] ?? $into_lang_default,
        'onderwerpen' => $hubspotData['onderwerpen'][0] ?? '',
        'open'        => strtolower($hubspotData['inschrijvingsstatus'][0] ) === 'open',
);
$ws          = ' ';

?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner nok-m-0 nok-border-radius-to-sm-0
nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

            <article>
                <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-2 nok-fs-to-md-1">
                    Kruimelpad
                </h2>
                <h1 class="nok-fs-6">
                    <?= ucfirst( $eventData['soort'] . $ws . ucfirst( $eventData['locatie'] ) ); ?> (<?= $eventData['type']; ?>)
                </h1>
                <div class="">
                    <?= ucfirst( $eventData['intro'] ); ?>
                </div>
            </article>

        </div>
    </nok-hero>

    <nok-section>
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3
                        nok-text-darkblue">

                <div class="body-copy nok-order-1 nok-order-lg-0">
                    <?= Helpers::classFirstP( $eventData['intro_lang'], "fw-bold nok-fs-2" ); ?>
                    <?php if (!empty($eventData['onderwerpen'])) : ?>
                    <h2>Onderwerpen</h2>
                    <p>Wat kunt u verwachten van deze voorlichting?</p>
                    <?= $eventData['onderwerpen']; ?>
                    <?php endif; ?>
                    <h2>Kosten</h2>
                    <p>
                        Deze voorlichting is gratis.
                    </p>
                    <h2 id="aanmelden">Aanmelden</h2>
                    <?php
                    if ( function_exists( 'gravity_form' ) ) {
                        gravity_form( 1, false, false );
                    }
                    ?>

                </div>

                <div class="nok-column-last-1 nok-order-0 nok-order-lg-1 nok-gap-1 nok-layout-flex nok-layout-flex-column nok-align-items-stretch">
                    <nok-square-block class="nok-bg-darkerblue nok-text-contrast nok-alpha-10 nok-pull-up-lg-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2 class="nok-text-yellow nok-dark-text-yellow nok-fs-2 nok-fs-to-md-2"><?= ucfirst( $eventData['soort'] ); ?>
                                (<?= $eventData['type']; ?>)</h2>
                            <h2><?= $eventDate['niceDateFull']; ?></h2>
                            <h3 class="fw-normal"><?= $eventDate['start_time'] . ' - ' . $eventDate['end_time']; ?> uur</h3>
                        </div>
                        <div class="nok-square-block__text nok-fs-1">

                            <address>
                                <span class="location" title="Nederlandse Obesitas Kliniek Venlo" id="location-name">Vestiging <?= ucfirst( $eventData['locatie'] ); ?></span>
                                <span class="street" id="street">Noorderpoort 9B</span>
                                <span class="postal-code" id="zipcode">5916 PJ Venlo</span>
                                <span class="phone" id="phone"><a href="tel:077 - 303 06 30" class="nok-hyperlink">077 - 303 06 30</a></span>
                            </address>
                        </div>
                        <a role="button" href="#aanmelden" class="nok-button nok-justify-self-start w-100
                nok-base-font nok-bg-yellow nok-text-contrast" tabindex="0">
                            Aanmelden
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 12 12" width="12" height="12"
                                 stroke="currentColor"
                                 style="stroke-linecap: round; stroke-linejoin: round;">
                                <path d="M 5,0 L 5,10 M 5,10 L 0,4 M 5,10 L 10,4" data-name="Down"></path>
                            </svg>
                        </a>
                        <a role="button" href="" class="nok-button nok-justify-self-start w-100
                nok-base-font nok-bg-lightgrey--lighter nok-text-contrast" tabindex="0">
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
get_footer();
?>