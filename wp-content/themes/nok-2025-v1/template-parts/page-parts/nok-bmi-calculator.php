<?php
/**
 * Template Name: BMI Calculator
 * Description: A BMI Calculator
 * Slug: nok-bmi-calculator
 * Custom Fields:
 */

use NOK2025\V1\Theme;

?>

    <nok-section>
        <div class="nok-section__inner nok-layout-grid nok-layout-grid__1-column nok-align-items-start">

            <?php the_title( '<h1 class="nok-fs-giant nok-span-all-columns nok-mb-2">', '</h1>' ); ?>

            <?php ( Theme::get_instance() )->embed_post_part_template( 'nok-bmi-calculator', array(), true ); ?>

        </div>
    </nok-section>

<?php