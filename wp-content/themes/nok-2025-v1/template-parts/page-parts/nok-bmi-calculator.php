<?php
/**
 * Template Name: BMI Calculator
 * Description: Page wrapper for BMI calculator post part - displays calculator with title
 * Slug: nok-bmi-calculator
 * Custom Fields:
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Theme;

$c = $context;
?>

<nok-section>
    <div class="nok-section__inner nok-layout-grid nok-layout-grid__1-column nok-align-items-start">

        <h1 class="nok-fs-giant nok-span-all-columns nok-mb-2"><?= $c->title() ?></h1>

		<?php Theme::get_instance()->embed_post_part_template('nok-bmi-calculator', [], true); ?>

    </div>
</nok-section>