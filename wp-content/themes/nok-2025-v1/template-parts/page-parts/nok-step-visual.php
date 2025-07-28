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

$featuredImage = '<img src="https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:100x0-25-0-0-center-0.jpg" 
srcset="https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:1920x0-65-0-0-center-0.jpg 1920w,
                             https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:768x0-65-0-0-center-0.jpg 768w,
                             https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:320x0-65-0-0-center-0.jpg 320w,
                             https://assets.obesitaskliniek.nl/files/2025_fotos/NOK%20Stockfotos%202025%20-%2005-12-2024%20-%2045:150x0-65-0-0-center-0.jpg 150w" sizes="(max-width: 575px) 100vw,
                                 (min-width: 575px) 75vw,
                                 (min-width: 768px) 84vw,
                                 (min-width: 996px) 84vw,
                                 (min-width: 1200px) 84vw" loading="eager" decoding="async">';

if ( has_post_thumbnail() ) {
	// Output <img> with srcset, sizes, width/height, alt, AND loading="lazy"
	$featuredImage = wp_get_attachment_image(
		get_post_thumbnail_id(),  // attachment ID
		'large',                   // size slug: 'thumbnail', 'medium', 'large', 'full', or your custom size
		false,                    // icon? false = normal image
		[
			'loading'  => 'eager', //eager since we are at the top of the page anyway
			'decoding' => 'async', // async decoding for better performance
			// These attributes get added to the <img> tag
			'class'    => 'featured-image',       // your CSS hook
			// size hint: “100vw up to 1200px wide, then cap at 1200px”
			'sizes'    => '(max-width: 1200px) 100vw, 1200px',
		]
	);
}

$left = ( $page_part_fields['layout'] ?? 'left' ) == 'left';

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
                        <p class="nok-square-block__text nok-fs-2">
							<?php the_content(); ?>
                        </p>
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
                        <p class="nok-square-block__text nok-fs-2">
	                        <?php the_content(); ?>
                        </p>
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