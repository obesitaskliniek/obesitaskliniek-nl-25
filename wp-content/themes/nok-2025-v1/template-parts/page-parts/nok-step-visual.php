<?php
/**
 * Template Name: Step Visual
 * Description: A split-content page part for representing a step with a visual
 * Slug: nok-step-visual
 * Custom Fields:
 * - tagline:text,
 * - button_blauw_text:text,
 * - button_blauw_url:url,
 * - layout:select(left|right)
 */

use NOK2025\V1\Helpers;
$featuredImage = Helpers::get_featured_image();

$left = empty($page_part_fields['layout']) || $page_part_fields['layout'] === 'left';

?>

    <nok-section>
        <div class="nok-section__inner">
			<?php if ( $left ) : ?>
                <div class="nok-my-2 align-self-stretch
                            text-start
                        nok-layout-grid overlap-middle offset--1 nok-columns-7 no-overlap-to-lg nok-column-offset-0 nok-column-offset-xl--1
                            nok-align-items-center">
                    <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2"><?php echo $page_part_fields['tagline'] ?? ''; ?></h2>
							<?php the_title( '<h1>', '</h1>' ); ?>
                        </div>
                        <div class="nok-square-block__text nok-fs-2">
							<?php the_content(); ?>
                        </div>
                        <a role="button" href="<?php echo $page_part_fields['button_blauw_url'] ?? '#'; ?>" class="nok-button nok-justify-self-start
                nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
		                    <?php echo $page_part_fields['button_blauw_text'] ?? ''; ?>
                            </svg>
                        </a>
                    </nok-square-block>
                    <div class="cover-image nok-rounded-border-large nok-invisible-to-lg align-self-stretch">
	                    <?= $featuredImage; ?>
                    </div>
                </div>
			<?php else : ?>
                <div class="nok-my-2 align-self-stretch
                        text-start
                        nok-layout-grid overlap-middle offset--1 nok-columns-7 no-overlap-to-lg nok-column-offset-0 nok-column-offset-xl-1
                        nok-align-items-center">
                    <div class="cover-image nok-rounded-border-large nok-invisible-to-lg align-self-stretch">
	                    <?= $featuredImage; ?>
                    </div>
                    <nok-square-block class="nok-bg-white nok-alpha-10 nok-my-2 nok-my-to-lg-0" data-shadow="true">
                        <div class="nok-square-block__heading">
                            <h2 class="nok-text-lightblue nok-dark-text-yellow nok-hero__pre-heading nok-fs-3 nok-fs-to-md-2"><?php echo $page_part_fields['tagline'] ?? ''; ?></h2>
	                        <?php the_title( '<h1>', '</h1>' ); ?>
                        </div>
                        <div class="nok-square-block__text nok-fs-2">
	                        <?php the_content(); ?>
                        </div>
                        <a role="button" href="<?php echo $page_part_fields['button_blauw_url'] ?? '#'; ?>" class="nok-button nok-justify-self-start
                nok-base-font nok-bg-darkblue nok-text-contrast" tabindex="0">
	                        <?php echo $page_part_fields['button_blauw_text'] ?? ''; ?>
                            </svg>
                        </a>
                    </nok-square-block>
                </div>
			<?php endif; ?>
        </div>
    </nok-section>

<?php