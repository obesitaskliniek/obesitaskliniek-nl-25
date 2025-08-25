<?php
/* Template Name: Event */

get_header();


use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();


?>

    <nok-hero class="nok-section">
        <div class="nok-section__inner nok-hero__inner nok-m-0 nok-border-radius-to-sm-0
nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white
nok-bg-alpha-10 nok-dark-bg-alpha-10 nok-subtle-shadow">

            <article>
                <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-2 nok-fs-to-md-1">
					<?php echo $page_part_fields['tagline'] ?? ''; ?>
                </h2>
				<?php the_title( '<h1 class="nok-fs-6">', '</h1>' ); ?>
                <div class="">
					<?php the_content(); ?>
                </div>
            </article>

        </div>
    </nok-hero>

    <nok-section>
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__3-column fill-one nok-column-gap-3
                        nok-text-darkblue">

                <div class="body-copy">
                    <p class="fw-bold nok-fs-2">
                        Heeft u te maken met ernstig overgewicht en overweegt u een maagverkleining? Dan is het goed om
                        te weten wat u allemaal te wachten staat. Als u in
                        aanmerking komt voor behandeling, verandert er veel in uw leven. Betrouwbare, actuele informatie
                        is erg belangrijk. Daarom organiseren wij
                        maandelijks een informatiebijeenkomst over de behandeling van ernstig overgewicht met behulp van
                        een maagverkleinende operatie.
                    </p>
                    <p>
                        Tijdens de voorlichting vertellen we u meer over het hele traject en de operatie en beantwoorden
                        we graag al uw vragen. Ook uw partner, vrienden of
                        familie zijn van harte uitgenodigd om een bijeenkomst bij te wonen.
                    </p>
                    <h2>Onderwerpen</h2>
                    <p>
                        Wat kunt u verwachten van deze voorlichting?
                    </p>
                    <ul>
                        <li>Criteria om voor behandeling in aanmerking te komen
                        </li>
                        <li>Multidisciplinaire behandeling; wat betekent dit?
                        </li>
                        <li>Traject voor de operatie, operatie, traject na de operatie
                        </li>
                        <li>Operatietechnieken
                        </li>
                        <li>Vergoeding &amp; kosten
                        </li>
                    </ul>
                    <h2>Kosten</h2>
                    <p>
                        Deze voorlichting is gratis.
                    </p>
                    <h2>Aanmelden</h2>


                    <form class="nok-form">
                        <div class="nok-form-element">
                            <input type="text" name="input_field" id="input_field" placeholder="" />
                            <label for="input_field">Naam</label>
                        </div>
                        <div class="nok-form-element">
                            <input type="text" name="input_field" id="input_field" placeholder="Geen label" />
                        </div>
                        <div class="nok-form-element">
                            <select id="input-aantal" name="input-aantal">
                                <option disabled selected>Selecteer aantal personen</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="nok-form-element">
                            <input type="checkbox" value="" name="check18" id="check18">
                            <label for="check18">
                                Ik ben 18 jaar of ouder
                            </label>
                        </div>
                    </form>

                </div>

                <div class="nok-column-last-1">
                    <nok-square-block class="nok-bg-white nok-alpha-10 pull-up-3" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2 class="nok-text-lightblue nok-dark-text-yellow nok-fs-2 nok-fs-to-md-2">Voorlichting
                                (online)</h2>
                            <h2>Dinsdag 14 januari</h2>
                        </div>
                        <div class="nok-square-block__text nok-fs-1">

                            <address>
                                <span class="location" title="Nederlandse Obesitas Kliniek Venlo" id="location-name">Vestiging Venlo</span>
                                <span class="street" id="street">Noorderpoort 9B</span>
                                <span class="postal-code" id="zipcode">5916 PJ Venlo</span>
                                <span class="phone" id="phone"><a href="tel:077 - 303 06 30" class="nok-hyperlink">077 - 303 06 30</a></span>
                            </address>
                        </div>
                        <a role="button" href="" class="nok-button nok-justify-self-start w-100
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
                </div>
            </article>
        </div>
    </nok-section>

<?php
get_footer();
?>