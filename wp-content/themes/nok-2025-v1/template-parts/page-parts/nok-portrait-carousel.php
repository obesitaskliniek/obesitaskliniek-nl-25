<?php
/**
 * Template Name: Portrait Carousel
 * Slug: nok-portrait-carousel
 * Custom Fields:
 * - team_members:repeater
 */

/** @var \WP_Post $post */
global $post;
$post = $args['post'] ?? null;
$page_part_fields = $args['page_part_fields'] ?? [];
setup_postdata( $post );        // set up all "in-the-loop" globals

?>

<nok-section>
    <div class="nok-section__inner--stretched
    nok-bg-darkblue nok-text-white">
        <div class="nok-section__inner">

            <article class="nok-layout-grid nok-layout-grid__2-column nok-align-items-start">
                <?php the_title('<h1 class="nok-span-all-columns-to-xxl">', '</h1>'); ?>
                <div class="new-row nok-span-all-columns-to-xxl"><?php the_content(); ?></div>

                <!-- Component: drag-scrollable blokkengroep -->
                <div class="nok-mt-2 align-self-stretch">
                    <div class="nok-layout-grid nok-layout-grid__4-column nok-columns-to-lg-2
                nok-scrollable__horizontal columns-to-slides" data-scroll-snapping="true" data-draggable="true" data-autoscroll="false">
                        <?php
                        $specialisten = array('Arts', 'Internist', 'Diëtist', 'Psycholoog', 'Bewegingsdeskundige', 'Chirurg');
                        $people_dir = THEME_ROOT_ABS . '/assets/img/people';
                        $images = array();
                        if (is_dir($people_dir)) :
                            $images = glob($people_dir . '/*.png');
                            shuffle($specialisten);
                            foreach ($specialisten as $specialist) :
                                if (!empty($images)) :
                                    $random_key = array_rand($images);
                                    $image = $images[$random_key];
                                    $filename = basename($image);
                                    $persoon = str_replace('-transparant', '', pathinfo($filename)['filename']);
                                    $afbeelding = THEME_ROOT . '/assets/img/people/' . $filename;
                                    unset($images[$random_key]); ?>
                                    <nok-square-block class="nok-p-0 nok-border-radius-0">
                                        <div class="square-portrait-image nok-rounded-border-large nok-gradient-1">
                                            <img src="<?= $afbeelding; ?>" loading="lazy" style="filter:drop-shadow(30px 20px 30px rgba(var(--nok-darkerblue-rgb), 0.15))">
                                        </div>
                                        <div>
                                            <h3><?= $persoon; ?></h3>
                                            <p class="fw-300"><?= $specialist; ?></p>
                                        </div>
                                    </nok-square-block>
                                <?php endif; endforeach; endif; ?>
                    </div>
                </div>

            </article>

        </div>
    </div>
</nok-section>


<?php
wp_reset_postdata();            // restore global $post & loop state