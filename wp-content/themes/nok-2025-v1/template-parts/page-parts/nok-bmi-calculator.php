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

    label {
        --c: #547980; /* slider color */
        --g: round(.3em,1px);  /* the gap */
        --l: round(.2em,1px);  /* line thickness*/
        --s: round(1.3em,1px); /* thumb size*/
        --t: round(.8em,1px);  /* tooltip tail size */
        --r: round(.8em,1px);  /* tooltip radius */

        timeline-scope: --thumb-view;
        position: relative; /* No, It's not useless so don't remove it (or remove it and see what happens) */
        font-size: 24px;
    }

    input[type="range"] {
        width: 100%;
        flex-basis: 100%;
        height: var(--s); /* needed for Firefox*/
        --_c: color-mix(in srgb, var(--c), #000 var(--p,0%));
        appearance :none;
        background: none;
        cursor: pointer;
        overflow: hidden;
        font-size: inherit;
        animation: range linear both;
        animation-timeline: --thumb-view;
        animation-range: entry 100% exit 0%;
    }
    input[type="range"]:focus-visible,
    input[type="range"]:hover{
        --p: 25%;
    }
    input[type="range"]:active,
    input[type="range"]:focus-visible{
        --_b: var(--s)
    }
    /* chromium */
    input[type="range" i]::-webkit-slider-thumb {
        height: var(--s);
        aspect-ratio: 1;
        border-radius: 50%;
        box-shadow: 0 0 0 var(--_b,var(--l)) inset var(--_c);
        border-image: linear-gradient(90deg,var(--_c) 50%,#ababab 0) 0 1/calc(50% - var(--l)/2) 100vw/0 calc(100vw + var(--g));
        -webkit-appearance: none;
        appearance: none;
        transition: .3s;
        anchor-name: --thumb;
        view-timeline: --thumb-view inline;
    }
    /* Firefox */
    input[type="range"]::-moz-range-thumb {
        height: var(--s);
        width: var(--s);
        background: none;
        border-radius: 50%;
        box-shadow: 0 0 0 var(--_b,var(--l)) inset var(--_c);
        border-image: linear-gradient(90deg,var(--_c) 50%,#ababab 0) 0 1/calc(50% - var(--l)/2) 100vw/0 calc(100vw + var(--g));
        -moz-appearance: none;
        appearance: none;
        transition: .3s;
        anchor-name: --thumb;
        view-timeline: --thumb-view inline;
    }

    @keyframes range {
        0%   {--c: #8A9B0F;}
        100% {--c: #CC333F;}
    }


</style>

    <nok-section>
        <div class="nok-section__inner">

            <nok-bmi-calculator data-requires="./nok-bmi-calculator.mjs?cache=<?= time(); ?>">
                
                <?php $calculatorInputs = array(
                        'Lengte (cm)' => array('name' => 'height', 'default' => 195),
                        'Gewicht (kg)' => array('name' => 'weight', 'default' => 80),
                        'BMI' => array('name' => 'BMI', 'default' => '')
                );
                foreach ( $calculatorInputs as $niceName => $id ) :
                    print "<div class='calculator-input'>
                    <label for='{$id['name']}Number'>{$niceName}
                        <input type='number' id='{$id['name']}Number' data-input-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>
                        <label for='{$id['name']}Slider' class='visually-hidden'>{$niceName}</label>
                        <input type='range' id='{$id['name']}Slider' data-input-for='{$id['name']}' min='50' max='250' step='0.1' value='{$id['default']}' data-default='{$id['default']}'>
                    </label>
                </div>";
                 endforeach; ?>
            </nok-bmi-calculator>

        </div>
    </nok-section>

<?php