<?php
/**
 * Template Name: Block Carousel
 * Slug: nok-block-carousel
 * Custom Fields:
 * - blocks:repeater
 * - read_more:text
 * - colors:select(Blauw::nok-bg-darkerblue nok-text-white|Wit::nok-bg-white nok-dark-bg-darkestblue nok-text-darkblue)
 */

$default_colors = 'nok-bg-darkerblue nok-text-white';
$colors = ($page_part_fields['colors'] ?? "") !== "" ? $page_part_fields['colors'] : $default_colors;
?>

<nok-section class="<?= $colors;?>">
    <div class="nok-section__inner--stretched">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__2-column fill-fill nok-align-items-start">
	            <?php the_title('<h1>', '</h1>'); ?>
                <div class="nok-text-content"><?php the_content(); ?></div>

                <!-- Component: drag-scrollable blokkengroep -->
                <div class="nok-mt-2 nok-align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__3-column
            nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="true">
                        <?php $x = 6; while ($x--) : ?>
                            <nok-square-block class="nok-bg-darkblue nok-text-white">
                                <div class="nok-square-block__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 13" fill="currentColor">
                                        <path d="M4.578.004c-.398.02-.777.094-1.133.223a3.803 3.803 0 0 0-2.058 1.746A4.127 4.127 0 0 0 .956 3.2a4.602 4.602 0 0 0-.047.976c.042.58.209 1.145.488 1.656.059.106.153.16.27.16.137 0 .25-.09.285-.226a.318.318 0 0 0 0-.149q-.008-.031-.082-.176a3.474 3.474 0 0 1-.367-1.77c.023-.335.082-.62.187-.913.36-1.02 1.2-1.79 2.239-2.051.187-.047.359-.078.562-.094.11-.008.41-.008.52 0a3.361 3.361 0 0 1 2.034.895c.024.023.305.3.626.625.418.422.586.586.601.597.054.034.117.05.18.047.07 0 .117-.011.172-.05.016-.008.226-.22.617-.614.328-.328.617-.617.645-.64.272-.248.583-.448.921-.594.352-.149.7-.234 1.098-.27.078-.004.395-.004.477 0a3.249 3.249 0 0 1 3.011 3.227c.014.864-.3 1.702-.879 2.344-.05.058-.449.46-1.761 1.777-3.102 3.113-4.301 4.316-4.309 4.316 0 0-.07-.066-.152-.148-.086-.086-.3-.3-.48-.477-.829-.828-1.876-1.87-2.899-2.902-.328-.324-.461-.457-.48-.473a.275.275 0 0 0-.098-.043.361.361 0 0 0-.145.004.298.298 0 0 0-.215.22.452.452 0 0 0 0 .144c.012.04.03.079.055.113.04.047 3.996 4.004 4.192 4.191.045.049.105.08.171.09.04.008.09.008.13-.004a.456.456 0 0 0 .109-.05c.035-.032.433-.43 4.203-4.211a212.272 212.272 0 0 0 2.11-2.125 4.02 4.02 0 0 0 1.035-3.055 3.879 3.879 0 0 0-.872-2.16A3.844 3.844 0 0 0 12.144 0c-.886 0-1.753.3-2.453.852-.18.14-.222.183-.758.722l-.484.485-.433-.434a19.35 19.35 0 0 0-.637-.629 4.002 4.002 0 0 0-2.39-.988 7.13 7.13 0 0 0-.411-.004Z"/>
                                        <path d="M6.996 7.805c0 .004.027.058-.55-1.125-.29-.594-.532-1.09-.54-1.098a.31.31 0 0 0-.172-.133.37.37 0 0 0-.16 0 .324.324 0 0 0-.133.078c-.011.012-.226.301-.48.649l-.461.625-2.121.004c-2.012 0-2.13 0-2.149.008a.29.29 0 0 0-.136.074.265.265 0 0 0-.086.136C0 7.051 0 7.063 0 7.105a.297.297 0 0 0 .219.293l.027.008h4.453l.027-.008a.3.3 0 0 0 .137-.082c.012-.011.18-.238.38-.507.194-.27.358-.489.358-.489.004.004.274.555.602 1.227.328.676.602 1.234.61 1.246.044.065.11.11.187.129a.337.337 0 0 0 .125 0 .3.3 0 0 0 .207-.168c.008-.016.23-.742.559-1.824.3-.989.546-1.797.55-1.797l.375 1.047c.124.357.252.712.383 1.066a.32.32 0 0 0 .106.133c.029.019.06.033.093.043.024.008.059.008.903.008.863 0 .879 0 .914-.008a.3.3 0 0 0 .219-.395.31.31 0 0 0-.215-.195c-.02-.004-.075-.004-.782-.004-.71-.004-.754-.004-.757-.008 0-.004-.223-.625-.493-1.379-.27-.753-.496-1.382-.504-1.398a.274.274 0 0 0-.136-.137.254.254 0 0 0-.121-.031c-.032 0-.047 0-.075.008a.3.3 0 0 0-.207.168L6.996 7.805Z"/>
                                    </svg>
                                </div>
                                <h2 class="nok-square-block__heading">
                                    Een titeltekst met variabele lengte <?= $x; ?>
                                </h2>
                                <p class="nok-square-block__text">
                                    Aenean ac feugiat nibh. Praesent venenatis non nibh vitae pretium. Suspendisse euismod
                                    blandit lorem vel mattis. Pellentesque ultrices velit at nisl placerat faucibus.
                                </p>
                                <a class="nok-square-block__link" href="#"><?php echo $page_part_fields['read_more'] ?? 'Lees verder'; ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" width="25" height="25"
                                         fill="currentColor">
                                        <path d="m17.5 5.999-.707.707 5.293 5.293H1v1h21.086l-5.294 5.295.707.707L24 12.499l-6.5-6.5z"
                                              data-name="Right"/>
                                    </svg>
                                </a>
                            </nok-square-block>
                        <?php endwhile; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>


<?php