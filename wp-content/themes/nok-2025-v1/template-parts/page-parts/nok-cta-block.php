<?php
/**
 * Template Name: CTA block
 * Description: A basic CTA (call-to-action) text block with title, content and optional button.
 * Slug: nok-cta-block
 * Custom Fields:
 * - layout_offset:select(left|balanced),
 * - colors:select(Blauw op transparant|Blauw op donkerblauw),
 * - button_text:text,
 * - button_url:url,
 */

use NOK2025\V1\Assets;

$layout = ( $page_part_fields['layout_offset'] ?? 'left' ) === "" ? 'left' : $page_part_fields['layout_offset'];

//default colors
$section_colors = '';
$block_colors = 'nok-bg-darkerblue nok-text-white';

if ( $page_part_fields['colors'] === "Blauw op donkerblauw" ) {

    //dark blue colors
    $section_colors = 'nok-bg-darkerblue';
    $block_colors = 'nok-bg-darkblue nok-text-white';
}

?>

    <nok-section class=" <?= $section_colors ? : ''?>">
        <div class="nok-section__inner double-margin">
            <nok-square-block class="horizontal double-padding layout-<?= $layout;?> <?= $block_colors; ?>" data-shadow="true">
                <div class="nok-square-block__icon">
                    <?= Assets::getIcon('arrow-right-long'); ?>
                </div>
	            <?php the_title('<h1 class="nok-square-block__heading nok-fs-5">', '</h2>'); ?>
                <div class="nok-square-block__text"><?php the_content(); ?></div>

                <?php if (!empty($page_part_fields['button_url'])) : ?>
                <a role="button" href="<?= $page_part_fields['button_url']; ?>" class="nok-button nok-align-self-end nok-bg-white nok-text-darkblue"><?= $page_part_fields['button_text']; ?></a>
                <?php endif; ?>
            </nok-square-block>
        </div>
    </nok-section>

<?php