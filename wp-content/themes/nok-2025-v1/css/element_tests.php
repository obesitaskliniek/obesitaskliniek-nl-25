<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Element tests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../fonts/Realist/Realist-full.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./color_tests.css?cache=<?= time();?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./element_tests.css?cache=<?= time();?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./helpers.css?cache=<?= time();?>" crossorigin="anonymous">
    <link rel="stylesheet" href="./tests.css?cache=<?= time();?>" crossorigin="anonymous">

    <link rel="modulepreload" href="../js/entrypoint.min.mjs?cache=<?= time();?>">
    <script type="module" src="../js/entrypoint.min.mjs?cache=<?= time();?>" defer></script>

    <!-- Load the module with explicit defer
    <link rel="modulepreload" href="../js/nok-element-library.min.mjs?cache=<?= time();?>">
    <script type="module" src="../js/nok-element-library.min.mjs?cache=<?= time();?>" defer></script>

    <style>
        :not(:defined) {
            /*visibility: hidden;*/
        }
    </style>-->
</head>
<body class="nok25-bg-body nok25-text-contrast">

<nav class="nok25-horizontal-section nok25-text-darkerblue nok25-dark-text-white nok25-sticky nok25-nav">
    <div class="nok25-horizontal-section--stretched nok25-bg-body nok25-bg-blur nok25-nav-top-row nok25-z-1">
        <div class="nok25-horizontal-section__inner nok25-nav-top">
            <div>Review</div>
            <div>Werken bij</div>
            <div>Kennisbank</div>
            <div>Mijn NOK</div>
            <div>NOK App</div>
            <div>+31 12345678</div>
            <div>Zoek</div>
            <div>NL</div>
        </div>
    </div>
    <div class="nok25-horizontal-section__inner nok25-nav-menubar-row nok25-z-2">
        <div class="nok25-bg-white nok25-nav-menubar">
            <div>
                <img src="https://assets.obesitaskliniek.nl/files/logos/NOK_Logo_-_Kleur.svg" alt="NOK Logo">
            </div>
            <div>Behandelingen</div>
            <div>Over NOK</div>
            <div>Agenda</div>
            <div>Verwijzers</div>
            <div><button class="nok25-button nok25-base-font nok25-bg-yellow" tabindex="0">Gratis voorlichtingsavond</button></div>
        </div>
    </div>
</nav>

<section class="nok25-horizontal-section nok25-text-darkerblue nok25-dark-text-white">
    <div class="nok25-horizontal-section__inner nok25-bg-white nok25-dark-bg-darkerblue nok25-bg-alpha-6 nok25-hero">

        <article>
            <h2 class="nok25-text-lightblue nok25-hero__pre-heading">
                #1 Obesitas Kliniek van Nederland
            </h2>
            <h1 class="nok25-horizontal-section__heading nok25-hero__heading">
                Serieuze aanpak, gezond afvallen
            </h1>
            <p class="nok25-horizontal-section__heading__description nok25-block-group__description">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero.
            </p>
            <div class="nok25-horizontal-button-group">
                <button class="nok25-button nok25-base-font nok25-bg-darkerblue nok25-text-contrast" tabindex="0">De behandeling</button>
                <a class="nok25-hyperlink fw-bold" href="#">Kom ik in aanmerking?</a>
            </div>
        </article>

        <figure>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 1065">
                <defs>
                    <linearGradient id="a" x1="865.07" x2="1963.13" y1="1419.45" y2="-190.25" gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="hsla(var(--grad-1-2) / var(--local-color-alpha, 1))"></stop>
                        <stop offset=".75" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                        <stop offset="1" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                    </linearGradient>
                    <linearGradient id="b" x1="881.9" x2="1940.53" y1="1391.88" y2="-160.02" gradientTransform="rotate(-45 804.633 957.17)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="hsla(var(--grad-1-2) / var(--local-color-alpha, 1))"></stop>
                        <stop offset=".75" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                        <stop offset="1" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                    </linearGradient>
                    <linearGradient id="c" x1="899.81" x2="1920.52" y1="1367.93" y2="-128.38" gradientTransform="rotate(-45 802.663 961.106)" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="hsla(var(--grad-1-2) / var(--local-color-alpha, 1))"></stop>
                        <stop offset=".75" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                        <stop offset="1" stop-color="hsla(var(--grad-1-1) / var(--local-color-alpha, 1))"></stop>
                    </linearGradient>
                    <mask id="mask-path" maskUnits="userSpaceOnUse">
                        <!-- White background to reveal everything by default -->
                        <rect x="-155" y="-699.19" width="2155.65" height="2014.27" fill="white"/>

                        <!-- Black shape to hide the image -->
                        <path d="M441.6-27c2.6-1.5,5.3-2.9,7.9-4.3-2.7,1.4-5.4,2.7-8,4.1l-.5-1L-20-20v1118.2h945.4v-20.1c.7-.3,1.4-.6,2.2-.9-.8.2-1.6.5-2.3.7,0,0,0,0,0,0-1.5.4-3.1.9-4.6,1.3-3.5,1-7,1.9-10.4,2.7-203.1,49.5-414.5-84.3-562.1-394.3C186.6,348.6,235.9,87.1,441.6-27Z" fill="black"/>
                    </mask>
                </defs>
                <foreignObject width="100%" height="100%" mask="url(#mask-path)">
                    <?php $testimg = 'https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%20' . str_pad(rand(1,59),2,0,STR_PAD_LEFT); ?>
                    <img src="<?= $testimg;?>:400x0-25-0-0-center-0.jpg" width="1920" height="1281" srcset="<?= $testimg;?>:1920x0-65-0-0-center-0.jpg 1920w,
                                 <?= $testimg;?>:768x0-65-0-0-center-0.jpg 768w,
                                 <?= $testimg;?>:320x0-65-0-0-center-0.jpg 320w,
                                 <?= $testimg;?>:150x0-65-0-0-center-0.jpg 150w" sizes="(max-width: 575px) 100vw,
                                     (min-width: 575px) 75vw,
                                     (min-width: 768px) 84vw,
                                     (min-width: 996px) 84vw,
                                     (min-width: 1200px) 84vw" loading="lazy">
                </foreignObject>
                <g class="st2">
                    <g id="a847a6d1-9c45-4359-9797-5a80bb44c069">
                        <path id="d" fill="hsla(var(--base-layer) / var(--local-color-alpha, 1))" class="st1" d="M169.5,771.2C14.7,446.3,109.3,147.2,437.9-25.3,66,168.3-40.2,484.3,109.9,799.6c146.4,307.3,444.1,427.2,817.6,277.5-.8.2-1.6.5-2.3.7-328.9,126.9-605.8,8.3-755.7-306.6Z"/>
                        <path id="c" fill="url(#c)" class="st2" d="M169.5,771.2c150,314.9,426.9,433.5,755.7,306.6-1.5.4-3.1.9-4.6,1.3-283.7,102.4-538.8-15.4-691.6-336.2C69.6,408.2,152.8,125.8,438.5-25.6c-.2.1-.4.2-.6.3C109.3,147.2,14.7,446.3,169.5,771.2Z"/>
                        <path id="b" fill="url(#b)" class="st3" d="M288.6,714.5C124.5,370,196.3,104.5,438.9-25.8c-.2,0-.3.2-.5.2C152.8,125.8,69.6,408.2,229,742.9c152.8,320.8,407.9,438.7,691.6,336.2-3.5,1-7,1.9-10.4,2.7-237.5,73.8-467.8-44.6-621.6-367.3Z"/>
                        <path id="a" fill="url(#a)" class="st0" d="M288.6,714.5c153.7,322.8,384.1,441.1,621.6,367.3-203.1,49.5-414.5-84.3-562.1-394.3C184.6,344.2,237.2,80.5,449.5-31.3c-3.5,1.8-7.1,3.6-10.6,5.4C196.3,104.5,124.5,370,288.6,714.5Z"/>
                    </g>
                </g>
            </svg>

        </figure>

        <section class="nok25-bg-body--lighter nok25-bg-blur">
            <div>Vergoed door zorgverzekeringen</div>
            <div>Meer dan 30 jaar ervaring</div>
            <div>Samenwerking met de beste ziekenhuizen</div>
            <div><button class="nok25-button nok25-base-font nok25-bg-white nok25-text-darkerblue" tabindex="0">Vind een vestiging</button></div>
        </section>
    </div>
</section>

<section class="nok25-horizontal-section nok25-bg-darkerblue nok25-text-contrast">
    <div class="nok25-horizontal-section__inner nok25-block-group">
        <div class="nok25-block-group__inner">
            <h1 class="nok25-horizontal-section__heading">
                A title for this section
            </h1>
            <p class="nok25-horizontal-section__heading__description nok25-block-group__description">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas, quia reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ab cumque est illo laboriosam libero officia praesentium quasi similique vitae voluptates? Adipisci aspernatur autem, corporis dolorem esse facere ipsam laboriosam rem!
            </p>
            <div class="nok25-block-group__scroller nok25-draggable" data-requires="./modules/hnl.draggable" data-snap-items="nok25-draggable-slider-item">
                <div class="nok25-block-group__blocks">
                    <?php $x = 6; while ($x--) : ?>
                        <nok25-square-block class="nok25-draggable-slider-item">
                            <div class="nok25-square-block__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 13" fill="currentColor">
                                    <path d="M4.578.004c-.398.02-.777.094-1.133.223a3.803 3.803 0 0 0-2.058 1.746A4.127 4.127 0 0 0 .956 3.2a4.602 4.602 0 0 0-.047.976c.042.58.209 1.145.488 1.656.059.106.153.16.27.16.137 0 .25-.09.285-.226a.318.318 0 0 0 0-.149q-.008-.031-.082-.176a3.474 3.474 0 0 1-.367-1.77c.023-.335.082-.62.187-.913.36-1.02 1.2-1.79 2.239-2.051.187-.047.359-.078.562-.094.11-.008.41-.008.52 0a3.361 3.361 0 0 1 2.034.895c.024.023.305.3.626.625.418.422.586.586.601.597.054.034.117.05.18.047.07 0 .117-.011.172-.05.016-.008.226-.22.617-.614.328-.328.617-.617.645-.64.272-.248.583-.448.921-.594.352-.149.7-.234 1.098-.27.078-.004.395-.004.477 0a3.249 3.249 0 0 1 3.011 3.227c.014.864-.3 1.702-.879 2.344-.05.058-.449.46-1.761 1.777-3.102 3.113-4.301 4.316-4.309 4.316 0 0-.07-.066-.152-.148-.086-.086-.3-.3-.48-.477-.829-.828-1.876-1.87-2.899-2.902-.328-.324-.461-.457-.48-.473a.275.275 0 0 0-.098-.043.361.361 0 0 0-.145.004.298.298 0 0 0-.215.22.452.452 0 0 0 0 .144c.012.04.03.079.055.113.04.047 3.996 4.004 4.192 4.191.045.049.105.08.171.09.04.008.09.008.13-.004a.456.456 0 0 0 .109-.05c.035-.032.433-.43 4.203-4.211a212.272 212.272 0 0 0 2.11-2.125 4.02 4.02 0 0 0 1.035-3.055 3.879 3.879 0 0 0-.872-2.16A3.844 3.844 0 0 0 12.144 0c-.886 0-1.753.3-2.453.852-.18.14-.222.183-.758.722l-.484.485-.433-.434a19.35 19.35 0 0 0-.637-.629 4.002 4.002 0 0 0-2.39-.988 7.13 7.13 0 0 0-.411-.004Z"/>
                                    <path d="M6.996 7.805c0 .004.027.058-.55-1.125-.29-.594-.532-1.09-.54-1.098a.31.31 0 0 0-.172-.133.37.37 0 0 0-.16 0 .324.324 0 0 0-.133.078c-.011.012-.226.301-.48.649l-.461.625-2.121.004c-2.012 0-2.13 0-2.149.008a.29.29 0 0 0-.136.074.265.265 0 0 0-.086.136C0 7.051 0 7.063 0 7.105a.297.297 0 0 0 .219.293l.027.008h4.453l.027-.008a.3.3 0 0 0 .137-.082c.012-.011.18-.238.38-.507.194-.27.358-.489.358-.489.004.004.274.555.602 1.227.328.676.602 1.234.61 1.246.044.065.11.11.187.129a.337.337 0 0 0 .125 0 .3.3 0 0 0 .207-.168c.008-.016.23-.742.559-1.824.3-.989.546-1.797.55-1.797l.375 1.047c.124.357.252.712.383 1.066a.32.32 0 0 0 .106.133c.029.019.06.033.093.043.024.008.059.008.903.008.863 0 .879 0 .914-.008a.3.3 0 0 0 .219-.395.31.31 0 0 0-.215-.195c-.02-.004-.075-.004-.782-.004-.71-.004-.754-.004-.757-.008 0-.004-.223-.625-.493-1.379-.27-.753-.496-1.382-.504-1.398a.274.274 0 0 0-.136-.137.254.254 0 0 0-.121-.031c-.032 0-.047 0-.075.008a.3.3 0 0 0-.207.168L6.996 7.805Z"/>
                                </svg>
                            </div>
                            <h2 class="nok25-square-block__heading">
                                Some large heading text of varying length
                            </h2>
                            <p class="nok25-square-block__text">
                                Aenean ac feugiat nibh. Praesent venenatis non nibh vitae pretium. Suspendisse euismod blandit lorem vel mattis. Pellentesque ultrices velit at nisl placerat faucibus.
                            </p>
                            <a class="nok25-square-block__link" href="#">Read more
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" width="25" height="25" fill="currentColor"><path d="m17.5 5.999-.707.707 5.293 5.293H1v1h21.086l-5.294 5.295.707.707L24 12.499l-6.5-6.5z" data-name="Right"/></svg>
                            </a>
                        </nok25-square-block>
                    <?php endwhile;?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="tests">
    <div data-stylegroup=".nok25-button" class="nok25-button-tests">
        <button class="nok25-button nok25-base-font nok25-bg-white nok25-text-darkerblue" tabindex="0">.nok25-button</button>
        <button class="nok25-button nok25-base-font nok25-bg-darkblue" tabindex="0">.nok25-button</button>
        <button class="nok25-button nok25-base-font nok25-bg-lightblue" tabindex="0">.nok25-button</button>
        <button class="nok25-button nok25-base-font nok25-bg-yellow" tabindex="0">.nok25-button</button>
        <button class="nok25-button nok25-base-font nok25-bg-yellow disabled" tabindex="0">.nok25-button</button>
        <button class="nok25-button nok25-base-font nok25-bg-green--lighter" tabindex="0">.nok25-button</button>
        <a role="button" href="#" class="nok25-button nok25-base-font nok25-bg-green--lighter" tabindex="0">.nok25-button</a>
    </div>
    <div data-stylegroup=".nok25-hyperlink" class="nok25-hyperlink-tests">
        <div class="testcard nok25-text-darkblue nok25-base-font">
            <p>Paragraph text with <a class="nok25-hyperlink" href="#">.nok25-hyperlink</a> inside.</p>
        </div>
        <div class="testcard nok25-text-greenblue--lighter nok25-base-font">
            <p>Paragraph text with <a class="nok25-hyperlink" href="#">.nok25-hyperlink</a> inside.</p>
        </div>
        <div class="testcard">
            <button class="nok25-button nok25-base-font nok25-bg-darkblue" tabindex="0">.nok25-button</button>
        </div>
        <div class="testcard nok25-text-darkblue nok25-base-font">
            <a class="nok25-hyperlink" href="#">.nok25-hyperlink</a>
        </div>
        <div class="testcard nok25-text-greenblue--lighter nok25-base-font">
            <a class="nok25-hyperlink" href="#">.nok25-hyperlink</a>
        </div>
    </div>
</div>

<script>



</script>
</body>
</html>