<?php
/**
 * Template Name: BMI Calculator
 * Description: A BMI Calculator
 * Slug: nok-bmi-calculator
 * Custom Fields:
 */

?>

<style>
    @property --val {
        syntax: '<integer>';
        inherits: true;
        initial-value: 0;
    }
    @property --e {
        syntax: '<number>';
        inherits: true;
        initial-value: 0;
    }

    .calculator-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    input[type="number"] {
        line-height: 1;
        field-sizing: content;
        min-width: 6ex;
        -webkit-appearance: none;
        appearance: none;
        border: 0 none;
        outline: 0 none;
        margin: 0;
    }
    input[type="range"] {
        --track-color: var(--nok-lightgrey--lighter);
        --active-range-color: var(--nok-lightblue);
        --thumb-border-color: var(--nok-lightblue);
        --thumb-fill-color: transparent;
        --thumb-hover-color: var(--nok-greenblue);
        --track-line-thickness: round(0.5em,1px);
        --thumb-line-thickness: round(0.5em,1px);
        --thumb-gap: round(.5em,1px);
        --thumb-size: round(2.2em,1px);

        width: 100%;
        flex-basis: 100%;
        grid-column: 1 / -1;

        height: var(--thumb-size); /* needed for Firefox*/
        appearance :none;
        background: none;
        cursor: pointer;
        overflow: hidden;
        font-size: inherit;
    }
    input[type="range"]:focus-visible,
    input[type="range"]:hover{
        --p: 25%;
    }
    input[type="range"]:active,
    input[type="range"]:focus-visible{
        --_b: var(--thumb-size);
        --thumb-border-color: var(--thumb-hover-color);
    }
    /* chromium */
    input[type="range"]::-webkit-slider-thumb {
        height: var(--thumb-size);
        aspect-ratio: 1;
        border-radius: 50%;
        background: var(--thumb-fill-color);
        box-shadow: 0 0 0 var(--_b,var(--thumb-line-thickness)) inset var(--thumb-border-color);
        border-image: linear-gradient(90deg, var(--active-range-color) 50%, var(--track-color) 0)
            0 1/calc(50% - var(--track-line-thickness)/2) 100vw/0 calc(100vw + var(--thumb-gap));
        -webkit-appearance: none;
        appearance: none;
        transition: .3s;
    }
    /* Firefox */
    input[type="range"]::-moz-range-thumb {
        height: var(--thumb-size);
        width: var(--thumb-size);
        background: none;
        border-radius: 50%;
        background: var(--thumb-fill-color);
        box-shadow: 0 0 0 var(--_b,var(--thumb-line-thickness)) inset var(--thumb-border-color);
        border-image: linear-gradient(90deg, var(--active-range-color) 50%, var(--track-color) 0)
            0 1/calc(50% - var(--track-line-thickness)/2) 100vw/0 calc(100vw + var(--thumb-gap));
        -moz-appearance: none;
        appearance: none;
        transition: .3s;
    }
    input[type="range"]#BMISlider {
        --thumb-gap: 0px;
        --thumb-fill-color: var(--track-color);
        --thumb-border-color: var(--bmi-classification-color);
        --active-range-color: transparent;
        background-image: var(--bmi-gradient), var(--bmi-gradient);
        background-size: calc(100% - var(--thumb-size)) var(--track-line-thickness), 100% var(--track-line-thickness);
        background-repeat: no-repeat;
        background-position: 50% 50%;
    }
    .bmi-class-color {
        color: var(--bmi-classification-color);
    }

</style>

    <nok-section>
        <div class="nok-section__inner">

            <nok-bmi-calculator class="nok-square-block nok-layout-grid nok-layout-grid__3-column one-fill nok-align-items-start
            nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-requires="./nok-bmi-calculator.mjs?cache=<?= time(); ?>">
                <div class='calculator-inputs nok-layout-grid nok-layout-grid__2-column auto-auto-1fr half-grid-gap nok-align-items-center'>
                <?php $calculatorInputs = array(
                        'Lengte (cm)' => array('name' => 'height', 'default' => 195),
                        'Gewicht (kg)' => array('name' => 'weight', 'default' => 80),
                        'BMI' => array('name' => 'BMI', 'default' => '')
                );
                foreach ( $calculatorInputs as $niceName => $id ) :
                    print "<label class='nok-fs-2' for='{$id['name']}Number' >{$niceName}</label>
                    <input class='nok-fs-3 fw-bold' type='number' inputmode='decimal' pattern='^\d+(?:\.\d{1,2})?$' id='{$id['name']}Number' data-input-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>
                    <label for='{$id['name']}Slider' class='visually-hidden'>{$niceName}</label>
                    <input type='range' id='{$id['name']}Slider' data-input-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>";
                 endforeach; ?>
                </div>
                <div class="calculator-conclusion baseline-grid">
                    <h1 class="bmi-class-color">U heeft <span data-input-for="category.classification"></span></h1>

                    <p class="show-bmi-normaal">Met <span data-input-for="healthyWeightRange.current" data-value-suffix=" kg"></span> zit uw gewicht binnen de marge
                        (<span data-input-for="healthyWeightRange.min" data-value-suffix=""></span> - <span data-input-for="healthyWeightRange.max" data-value-suffix=" kg"></span>)
                        die als gezond wordt gezien voor uw lengte.
                    </p>

                    <p class="show-not-bmi-normaal">
                        Met <span data-input-for="healthyWeightRange.current" data-value-suffix=" kg"></span> vormt uw gewicht een <span class="bmi-class-color fw-bold" data-input-for="category.risk"></span> gezondheidsrisico.
                        Een gezond gewicht voor uw lengte ligt tussen <span class="fw-bold" data-input-for="healthyWeightRange.min" data-value-suffix=""></span>
                        en <span class="fw-bold" data-input-for="healthyWeightRange.max" data-value-suffix=" kg"></span>. U bent dus momenteel ongeveer
                        <span class="fw-bold" data-input-for="healthyWeightRange.excess" data-value-suffix=" kg"></span> te <span class="show-not-bmi-ondergewicht">zwaar</span><span class="show-bmi-ondergewicht">licht</span>.
                    </p>

                    <p class="show-bmi-ondergewicht">
                        Probeer wat aan te komen, of in ieder geval niet verder af te vallen. Neem contact op met uw huisarts, om te kijken of er een onderliggende ziekte aan uw lage gewicht ten grondslag ligt.
                    </p>
                    <p class="show-near-lower-boundary-for-bmi-normaal">
                        U zit met uw gewicht echter wel aan de ondergrens voor een gezond gewicht. Ons advies is om in ieder geval verder gewichtsverlies te voorkomen.
                    </p>
                    <p class="show-near-upper-boundary-for-bmi-normaal">
                        U zit met uw gewicht echter wel aan de bovengrens voor een gezond gewicht. Ons advies is om in ieder geval niet verder aan te komen.
                    </p>
                    <p class="show-bmi-overgewicht">
                        <span class="show-near-lower-boundary-for-bmi-overgewicht">U zit met uw gewicht aan de ondergrens voor overgewicht.</span>
                        <span class="show-not-near-upper-boundary-for-bmi-overgewicht">Probeer wat af te vallen en in ieder geval niet verder aan te komen.</span>
                        <span class="show-near-upper-boundary-for-bmi-overgewicht">U zit met uw gewicht aan de bovengrens van overgewicht, u doet er verstandig aan om snel actie te ondernemen om uw gewicht niet verder te laten toenemen.</span>
                    </p>

                    <p class="small nok-text-lightgrey--darker">De berekende BMI is met deze rekenmethode niet geldig voor baby’s, zwangere vrouwen of vrouwen die borstvoeding geven, ernstig zieke personen, atleten of volwassenen van 74 jaar of ouder.</p>
                    <p class="small nok-text-lightgrey--darker">Meetgegevens op basis van de <a href="https://www.partnerschapovergewicht.nl/richtlijn-overgewicht-en-obesitas/" target="_blank">Zorgstandaard Obesitas 10-07-2023</a></p>
                </div>
            </nok-bmi-calculator>

        </div>
    </nok-section>

<?php