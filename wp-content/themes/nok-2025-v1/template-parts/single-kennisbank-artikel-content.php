<?php
/**
 * Content template for Kennisbank "artikel" category posts
 * Included by content-placeholder-nok-template block or directly as fallback
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$featured_image     = Helpers::get_featured_image();
$blur_image         = Helpers::get_featured_image('nok-image-cover-blur-ghost');
$has_featured_image = has_post_thumbnail(get_the_ID()) && $featured_image !== '';

// Get category for display
$categories = get_the_terms(get_the_ID(), 'kennisbank_categories');
$primary_category = $categories && !is_wp_error($categories) ? $categories[0] : null;

if ($has_featured_image) {
    $heading_article_class = 'nok-mb-double-section-padding';
    $article_class = '';
} else {
    $heading_article_class = 'nok-mb-0';
    $article_class = 'nok-mt-0';
}
?>

<nok-hero class="nok-section">
    <div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
        nok-bg-white nok-dark-bg-darkestblue nok-text-darkerblue nok-dark-text-white nok-bg-alpha-6 nok-dark-bg-alpha-10">

        <header class="nok-section__inner nok-section-narrow nok-mt-0 <?= $heading_article_class; ?>">

            <?php Helpers::render_breadcrumbs(); ?>

            <?php the_title('<h1 class="nok-fs-giant">', '</h1>'); ?>

            <div class="nok-mt-2">
                <?php Helpers::the_content_first_paragraph('nok-fs-2 fw-bold'); ?>
            </div>
        </header>
    </div>
</nok-hero>

<nok-section class="z-ascend no-aos">
    <article class="nok-section__inner nok-section-narrow nok-text-darkerblue <?= $article_class; ?>">
        <?php if ($has_featured_image): ?>
            <figure class="nok-pull-up-4 nok-mb-section-padding nok-image-cover nok-rounded-border-large nok-subtle-shadow nok-aos nok-aspect-16x9">
                <?= $featured_image; ?>
            </figure>
        <?php endif; ?>

        <div class="narrow-paragraphs margin-paragraphs">
            <?php Helpers::the_content_rest(); ?>
        </div>
    </article>
</nok-section>
