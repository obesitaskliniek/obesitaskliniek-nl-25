<?php
/* Template Name: Event */

get_header();


use NOK2025\V1\Helpers;

$featuredImage = Helpers::get_featured_image();


?>

    <nok-hero class="nok-section">
        <article class="nok-section__inner nok-hero__inner nok-m-0 nok-border-radius-to-sm-0
nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white
nok-bg-alpha-10 nok-dark-bg-alpha-10 nok-subtle-shadow">

            <?php Helpers::render_breadcrumbs(); ?>

            <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-2 nok-fs-to-md-1">
                <?php echo $page_part_fields['tagline'] ?? ''; ?>
            </h2>
            <?php the_title( '<h1 class="nok-fs-6">', '</h1>' ); ?>
        </article>
    </nok-hero>

    <nok-section>
        <div class="nok-section__inner">

            <article>
                <?php
                the_content();
                ?>
            </article>
        </div>
    </nok-section>

<?php
get_footer();
?>