<?php
/* Template Name: FAQ */

get_header();
?>

<?php get_template_part('design/swoosh', 'one');

function createSlider($name, $id, $val, $min = 0, $max = 100, $decimals = 0) {
    $step = ($decimals > 0) ? '0.' : '1';
    $perc = round((($val - $min) / ($max - $min)) * 100, 2);
    while($decimals--) { $step .= '1'; }
    return '<label for="input-' . $id . '" class="form-label col-12">' . $name . '</label>
        <input type="number" class="form-control nok-range-input nok-lesslightblue bg-transparent border-0 text-start"
               name="' . $name . '" min="' . $min . '" max="' . $max . '" value="' . $val . '" step="any" required
               placeholder="' . $name . '" id="input-' . $id . '">
        <label for="slider-' . $id . '" class="visually-hidden">' . $name . '</label>
        <input type="range" class="form-control border-0 nok-range-slider ' . $id . '" name="' . $id . '"
               id="slider-' . $id . '" min="' . $min . '" max="' . $max . '" value="' . $val . '" step="'. $step . '"
               style="--slider-value:' . $perc . '%;"
               data-target="input-' . $id . '" data-target-control="true">';
}
?>

    <div class="container-md">
        <div class="row">
            <div class="col">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent sollicitudin pharetra ante at
                    malesuada. Phasellus posuere lorem vitae augue molestie auctor sit amet a lacus. Nunc vitae neque
                    maximus, cursus sem vel, maximus eros. Integer sed augue ac magna euismod hendrerit. Nullam
                    vulputate rhoncus quam ut cursus. Etiam faucibus vel odio ut tristique. In rutrum lorem id velit
                    hendrerit, id tempus elit aliquet. Mauris commodo neque sed dignissim fringilla. Donec semper libero
                    eget risus venenatis tristique. Phasellus ac ultrices nibh. Maecenas elementum pretium pellentesque.
                    Etiam egestas dignissim lobortis. Donec efficitur orci id ante aliquet posuere. Aliquam erat
                    volutpat. Fusce in ultrices velit, nec pharetra tellus.</p>

                <div class="nok-bmi-calculator" data-requires="./modules/nok.bmicalc" data-require-lazy="true" data-nosnippet>
                    <div class="row">
                        <div class="col-12">
                            <h2><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calculator" viewBox="0 0 16 16">
                                    <path d="M12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h8zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4z"/>
                                    <path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-4z"/>
                                </svg> BMI Calculator</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="row">
                                <div class="col-6">
                                    <?= createSlider('Lengte (cm)', 'length', 190, 110, 210);?>
                                </div>
                                <div class="col-6">
                                    <?= createSlider('Gewicht (kg)', 'weight', 80, 40, 330);?>
                                </div>
                                <div class="col-12">
                                    <?= createSlider('BMI', 'bmi', 22.2, 10, 90, 1);?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card border-1 shadow-sm mt-4 mt-lg-0">
                                <div class="card-body">

                                    <div class="conclusion conclusion-0">
                                        <h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                            </svg> U heeft ondergewicht</h3>
                                            Een gezond gewicht voor uw lengte ligt tussen de <span class="healthy-weight-low">67</span> en <span class="healthy-weight-high">90</span> kg.
                                        <span>U bent dus ongeveer <span class="healthy-weight-difference">0</span> kg te licht.</span>

                                        <div class="mt-3">
                                            <strong>Ons advies</strong><br/>
                                            Probeer wat aan te komen of in ieder geval niet af te vallen.
                                            Neem eventueel ook contact op met uw huisarts, om te kijken of er geen onderliggende ziekte aan uw gewicht ten grondslag ligt.
                                        </div>
                                    </div>
                                    <div class="conclusion conclusion-1">
                                        <h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                                            </svg> U heeft een normaal gewicht</h3>
                                        Uw gewicht zit binnen de marge die als gezond wordt gezien voor uw lengte.
                                        Een gezond gewicht voor uw lengte ligt tussen de <span class="healthy-weight-low">67</span> en <span class="healthy-weight-high">90</span> kg.
                                        <div class="mt-3 visible-upper">
                                            <strong>Ons advies</strong><br/>
                                            U zit met <span class="current-weight"></span> kg aan de bovengrens voor een gezond gewicht. Probeer in ieder geval niet verder aan te komen.
                                        </div>
                                        <div class="mt-3 visible-lower">
                                            <strong>Ons advies</strong><br/>
                                            U zit met <span class="current-weight"></span> kg aan de ondergrens van een gezond gewicht, probeer in ieder geval verder gewichtsverlies te voorkomen.
                                        </div>
                                    </div>
                                    <div class="conclusion conclusion-2">
                                        <h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                                                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                                                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
                                            </svg> U heeft overgewicht</h3>
                                        Een gezond gewicht voor uw lengte ligt tussen de <span class="healthy-weight-low">67</span> en <span class="healthy-weight-high">90</span> kg.
                                        U bent dus ongeveer <span class="healthy-weight-difference">0</span> kg te zwaar.
                                        <div class="mt-3">
                                            <strong>Ons advies</strong><br/>
                                            <span class="visible-lower">U zit met <span class="current-weight"></span> kg aan de ondergrens van overgewicht, u kunt relatief eenvoudig (weer) een gezond gewicht bereiken.</span>
                                            <span class="visible-upper">U zit met <span class="current-weight"></span> kg aan de bovengrens van overgewicht, u doet er verstandig aan om snel actie te ondernemen om uw gewicht niet verder te laten toenemen.</span>
                                            <span class="invisible-upper">Probeer wat af te vallen en in ieder geval niet verder aan te komen.</span>
                                            <span class="visible-pz">Op basis van deze BMI kunt u voor behandeling terecht bij NOK Clinics; lees meer op <a href="https://www.nokclinics.nl/behandelingen" target="_blank" rel="">de website</a>.
                                        </div>
                                    </div>
                                    <div class="conclusion conclusion-3">
                                        <h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                            </svg> U heeft obesitas</h3>
                                            Dit betekent dat u last kunt hebben/krijgen van gezondheidsklachten door uw overgewicht.
                                            Een gezond gewicht voor uw lengte ligt tussen de <span class="healthy-weight-low">67</span> en <span class="healthy-weight-high">90</span> kg.
                                            U bent dus ongeveer <span class="healthy-weight-difference">0</span> kg te zwaar.

                                        <div class="mt-3 visible-pz visible-regulier">
                                            <strong>Ons advies</strong><br/>
                                            <span class="visible-regulier">Op basis van deze BMI komt u mogelijk in aanmerking voor onze behandeling. Lees hier meer over hoe u zich kunt aanmelden.</span>
                                            <span class="invisible-regulier">Op basis van dit BMI komt u nog niet in aanmerking voor onze behandeling, maar u kunt wél voor behandeling terecht bij NOK Clinics; lees meer op <a href="https://www.nokclinics.nl/behandelingen" target="_blank" rel="">de website</a>.</span>
                                            <span class="visible-regulier">U kunt voor behandeling óók terecht bij NOK Clinics; lees meer op <a href="https://www.nokclinics.nl/behandelingen" target="_blank" rel="">de website</a>.
                                        </div>
                                    </div>
                                    <div class="conclusion conclusion-4">
                                        <h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                            </svg> U heeft ernstige obesitas</h3>
                                        Men noemt dit ook wel ziekelijk overgewicht. U heeft zeer waarschijnlijk last van gezondheidsproblemen door uw overgewicht.
                                            Een gezond gewicht voor uw lengte ligt tussen de <span class="healthy-weight-low">67</span> en <span class="healthy-weight-high">90</span> kg.
                                        <span>U bent dus ongeveer <span class="healthy-weight-difference">0</span> kg te zwaar.</span>

                                        <div class="mt-3 visible-pz visible-regulier">
                                            <strong>Ons advies</strong><br/>
                                            Op basis van deze BMI komt u in aanmerking voor onze behandeling. Lees hier meer over hoe u zich kunt aanmelden.
                                            U kunt voor behandeling óók terecht bij NOK Clinics; lees meer op <a href="https://www.nokclinics.nl/behandelingen" target="_blank" rel="">de website</a>.
                                        </div>
                                    </div>

                                </div>
                                <div class="card-footer">
                                    <p class="text-muted small">
                                        De berekende BMI is met deze rekenmethode niet geldig voor baby’s,
                                        zwangere vrouwen of vrouwen die borstvoeding geven, ernstig zieke personen,
                                        atleten of volwassenen van 74 jaar of ouder.
                                    </p>
                                    <p class="text-muted small">Meetgegevens op basis van de
                                        <a href="https://www.partnerschapovergewicht.nl/richtlijnen/" target="_blank">Zorgstandaard Obesitas 25-11-2010</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
get_footer();
?>