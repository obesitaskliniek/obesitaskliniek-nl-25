<?php
/**
 * Template Name: Top Level Header
 * Slug: header-top-level
 * CSS: header-top-level
 */

/** @var \WP_Post|null $post */
$post = $args['post'] ?? null;

if ( ! ( $post instanceof \WP_Post ) ) {
    echo '<p><em>Ongeldig Page Part</em></p>';
    return;
}

setup_postdata($args['post']);

print get_the_title($post->ID);

echo wpautop( $post->post_content );
?>
<div class="page-part page-part--header-top-level">


    <nok-section>
        <div class="nok-section__inner--stretched
        nok-bg-darkblue nok-text-white">
            <div class="nok-section__inner">

                <article class="nok-layout-grid nok-layout-grid__2-column nok-align-items-start">
                    <h1 class="nok-span-all-columns-to-xxl">
                        Het multidisciplinaire team
                    </h1>
                    <p class="new-row nok-span-all-columns-to-xxl">
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet doloribus iure perspiciatis quod, quos
                        vero. Architecto, blanditiis est exercitationem illo iusto magni nihil nulla, quam quas, quia
                        reprehenderit vel voluptatum. Lorem ipsum dolor sit amet, consectetur adipisicing elit!
                    </p>

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
    </div>
<?php
wp_reset_postdata();