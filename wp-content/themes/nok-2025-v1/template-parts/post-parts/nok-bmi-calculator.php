<?php
$rand_id = rand( 1000, 9999 );
?>
    <nok-bmi-calculator class="nok-layout-grid nok-grid-gap-3 nok-align-items-start calculating loading"
                        data-requires="./nok-bmi-calculator.mjs?cache=<?= $rand_id ?>" data-require-lazy="true">
        <form id="<?= $rand_id; ?>" style="display: contents;">
        <nok-square-block class="calculator-inputs nok-layout-grid nok-layout-grid__2-column half-grid-gap nok-align-items-center
                nok-bg-white nok-dark-bg-darkestblue nok-text-contrast" data-shadow="true">
			<?php $calculatorInputs = array(
				'Lengte (cm)'  => array( 'name' => 'height', 'default' => 195 ),
				'Gewicht (kg)' => array( 'name' => 'weight', 'default' => 80 ),
				'BMI'          => array( 'name' => 'BMI', 'default' => '' )
			);
			foreach ( $calculatorInputs as $niceName => $id ) :
				print "<label class='nok-fs-2' for='{$id['name']}Number' >{$niceName}</label>
                    <input class='nok-fs-4 fw-bold' type='number' inputmode='decimal' pattern='^\d+(?:\.\d{1,2})?$' id='{$id['name']}Number' data-output-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>
                    <label for='{$id['name']}Slider' class='visually-hidden'>{$niceName}</label>
                    <input type='range' id='{$id['name']}Slider' data-output-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>";
			endforeach; ?>
        </nok-square-block>
        </form>
        <nok-square-block class="calculator-conclusion nok-layout nok-layout-grid half-grid-gap
                nok-bg-white nok-dark-bg-darkestblue nok-text-contrast nok-alpha-10" data-shadow="true">
            <h1 class="bmi-class-color">U heeft <span data-output-for="category.classification"></span></h1>
            <div class="conclusion-text">
                <p>
                        <span class="show-bmi-normaal">Met <span data-output-for="healthyWeightRange.current"
                                                                 data-value-suffix=" kg"></span> zit uw gewicht binnen de marge
                            (<span data-output-for="healthyWeightRange.min" data-value-suffix=""></span> - <span
                                    data-output-for="healthyWeightRange.max" data-value-suffix=" kg"></span>)
                            die voor uw lengte als gezond wordt beschouwd.
                        </span>

                    <span class="show-not-bmi-normaal">
                            Met <span data-output-for="healthyWeightRange.current" data-value-suffix=" kg"></span> vormt uw gewicht een <span
                                class="bmi-class-color fw-bold" data-output-for="category.risk"></span> gezondheidsrisico.
                            Een gezond gewicht voor uw lengte ligt tussen <span class="fw-bold"
                                                                                data-output-for="healthyWeightRange.min"
                                                                                data-value-suffix=""></span>
                            en <span class="fw-bold" data-output-for="healthyWeightRange.max"
                                     data-value-suffix=" kg"></span>. U bent momenteel dus ongeveer
                            <span class="fw-bold" data-output-for="healthyWeightRange.excess"
                                  data-value-suffix=" kg"></span> te <span
                                class="show-not-bmi-ondergewicht">zwaar</span><span
                                class="show-bmi-ondergewicht">licht</span>.
                        </span>

                    <span class="show-bmi-ondergewicht">
                            Probeer wat aan te komen, of in ieder geval niet verder af te vallen. Uw gewicht hoeft niet meteen een probleem te zijn, maar het kan wel invloed hebben op hoe u zich voelt.
                            Bespreek het daarom eens met uw huisarts, die kan samen met u bekijken wat er speelt - en wat u er zelf, of met hulp van een professional, aan kunt doen.
                        </span>

                    <span class="show-near-lower-boundary-for-bmi-normaal">
                            U zit echter wel aan de <em>ondergrens</em> voor een gezond gewicht. Ons advies is om in ieder geval verder gewichtsverlies te voorkomen.
                        </span>
                    <span class="show-near-upper-boundary-for-bmi-normaal">
                            U zit echter wel aan de <em>bovengrens</em> voor een gezond gewicht. Ons advies is om in ieder geval niet verder aan te komen.
                        </span>

                    <span class="show-bmi-overgewicht">
                            <span class="show-near-lower-boundary-for-bmi-overgewicht">U zit met uw gewicht aan de ondergrens voor overgewicht.</span>
                            <span class="show-not-near-upper-boundary-for-bmi-overgewicht">Dit hoeft niet meteen een probleem te zijn, maar probeer wat af te vallen en in ieder geval niet verder aan te komen.</span>
                            <span class="show-near-upper-boundary-for-bmi-overgewicht">U zit met uw gewicht aan de bovengrens van overgewicht, u doet er verstandig aan om snel actie te ondernemen om uw gewicht niet verder te laten toenemen.</span>
                        </span>

                    <span class="show-bmi-obesitas-1">
                            Hoewel uw gewicht nog geen ernstig risico vormt, adviseren wij wel om in actie te komen en iets aan uw gewicht te doen. U kunt ook contact opnemen met uw huisarts.
                            Die kan samen met u kijken naar uw gewicht, en wat u hier zelf - of met professionele hulp - aan kunt doen.
                        </span>

                    <span class="show-bmi-obesitas-2">
                            U doet er verstandig aan snel in actie te komen en contact op te nemen met uw huisarts. Die kan samen met u kijken naar de behandelmogelijkheden.
                        </span>

                    <span class="show-bmi-obesitas-3">
                            Dit is een ernstige situatie, en we adviseren u met klem snel in actie te komen en contact op te nemen met uw huisarts. Die kan samen met u kijken naar de behandelmogelijkheden.
                        </span>

                    <span class="show-behandeling-nok-regulier">
                            Op basis van deze BMI komt u <span class="show-bmi-obesitas-2">mogelijk</span> in aanmerking voor onze behandeling.
                            Wilt u meer weten? Meld u <a
                                href="https://www.obesitaskliniek.nl/aanmelden-gratis-voorlichtingsavond/"
                                target="_blank">hier</a> aan voor een gratis voorlichting.
                        </span>
                </p>

                <p class="show-behandeling-nok-clinics nok-mt-1">
                    U kunt voor behandeling <span class="show-behandeling-nok-regulier">óók</span> terecht bij NOK
                    Clinics; plan <a
                            href="https://nokclinics.nl/intake-plannen?onderwerp_aanvraag=Ik+wil+graag+een+intake+plannen"
                            target="_blank">hier</a> een intake op de website.
                </p>
            </div>

        </nok-square-block>
        <div class="conclusion-footer small nok-text-universal-contrast nok-span-all-columns">
            <p>Let op: de berekende BMI is met deze rekenmethode niet geldig voor baby’s, zwangere vrouwen
                of vrouwen die borstvoeding geven, ernstig zieke personen, atleten of volwassenen van 74
                jaar of ouder.</p>
            <p>Meetgegevens op basis van de <a
                        href="https://www.partnerschapovergewicht.nl/richtlijn-overgewicht-en-obesitas/"
                        target="_blank">Zorgstandaard Obesitas 10-07-2023</a></p>
        </div>
    </nok-bmi-calculator>

<?php