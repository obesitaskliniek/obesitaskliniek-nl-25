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

        <article class="nok-layout-flex-column nok-align-items-left nok-column-gap-3 text-left">
            <h2 class="nok-fs-6"><?= $c->title() ?></h2>

            <div class="nok-layout-grid nok-layout-grid__1-column
                        pull-down-correction
                        nok-column-first-2 nok-text-wrap-balance nok-order-1
                        <?= $c->lettergrootte ?>">
                <?= $c->content(); ?>
            </div>
        </article>

		<?php Theme::get_instance()->embed_post_part_template('nok-bmi-calculator', [], true); ?>

    </div>
</nok-section>