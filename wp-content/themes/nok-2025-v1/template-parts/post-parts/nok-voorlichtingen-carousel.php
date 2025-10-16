<?php

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$colors = 'nok-bg-darkerblue nok-text-white';

if ( ! empty( $args ) ) {
    if ( $args['colors'] ) {
        $colors = $args['colors'];
    }
}
?>

<nok-section>
    <div class="nok-section__inner--stretched <?= $colors;?>">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
                <h1>Meer voorlichtingen</h1>

                <!-- Component: drag-scrollable blokkengroep -->
                <div class="nok-mt-2 nok-align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__3-column
            nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="true">

                        <!-- alle komende voorlichtingen, niet specifiek die van de huidige vestiging -->
                        <?php
                        $evenementen_query = Helpers::get_latest_custom_posts( 'voorlichting', 6, [], [], 'aanvangsdatum_en_tijd' );
                        if ( $evenementen_query && $evenementen_query->have_posts() ) :
                            while ( $evenementen_query->have_posts() ) :
                                $evenementen_query->the_post();
                                // Your loop content
                                $hubspotData = Helpers::setup_hubspot_metadata( get_the_ID() ); ?>

                                <nok-square-block class="nok-bg-darkblue nok-dark-bg-darkestblue nok-text-contrast" data-shadow="false">
                                    <div class="nok-square-block__heading">
                                        <h2 class="nok-fs-3 nok-fs-to-md-2">
                                            <?= ucfirst(sprintf('%s %s (%s)', $hubspotData['soort'], ucfirst($hubspotData['locatie']), $hubspotData['type'])) ?>
                                        </h2>
                                    </div>
                                    <div class="nok-square-block__text">
                                        <?php
                                        printf('<p>%s %s</p>',
                                                Assets::getIcon('ui_calendar'),
                                                $hubspotData['timestamp']['niceDateFull']);
                                        printf('<p>%s %s</p>',
                                                Assets::getIcon('ui_location'),
                                                ucfirst( $hubspotData['locatie'] ));
                                        printf('<p>%s %s - %s uur</p>',
                                                Assets::getIcon('ui_time'),
                                                $hubspotData['timestamp']['start_time'],
                                                $hubspotData['timestamp']['end_time']);
                                        ?>
                                    </div>
                                    <a role="button" href="<?php the_permalink(); ?>" class="nok-button nok-justify-self-start w-100 nok-bg-yellow nok-text-contrast" tabindex="0">
                                        Bekijken
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 22 18" width="25" height="25" stroke="currentColor"
                                             style="stroke-width: .1rem; stroke-linecap: round; stroke-linejoin: round;">
                                            <path d="M 20,8 L 0,8 M 20,8 L 14,13 M 20,8 L 14,3" data-name="Right"></path>
                                        </svg>
                                    </a>
                                </nok-square-block>

                            <?php endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>

                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>


<?php