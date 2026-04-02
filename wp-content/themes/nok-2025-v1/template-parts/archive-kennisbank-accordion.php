<?php
/**
 * Archive Template: Kennisbank FAQ Accordion View
 *
 * Alternative layout for FAQ category archives that displays all posts as
 * expandable accordions grouped by subcategory, with client-side search.
 *
 * Loaded by archive-kennisbank.php when:
 * - Current taxonomy is one of the whitelisted FAQ category slugs
 * - ?view=accordion URL parameter is present
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

get_header( 'generic' );

// Current taxonomy context (already verified by the gate in archive-kennisbank.php)
$current_term = get_queried_object();

// Query ALL published posts in this category (including children), no pagination
$query_args = [
	'post_type'      => 'kennisbank',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'title',
	'order'          => 'ASC',
	'tax_query'      => [ [
		'taxonomy'         => 'kennisbank_categories',
		'field'            => 'term_id',
		'terms'            => $current_term->term_id,
		'include_children' => true,
	] ],
];

$posts = new WP_Query( $query_args );

// Get child categories for grouping (ordered by post count, most popular first)
$child_categories = get_terms( [
	'taxonomy'   => 'kennisbank_categories',
	'hide_empty' => true,
	'parent'     => $current_term->term_id,
	'orderby'    => 'count',
	'order'      => 'DESC',
] );

// Build grouped array: child_term_id => [ posts ]
// Pinned posts (meta _faq_pinned = "1") are partitioned out for display above groups.
$grouped_posts   = [];
$ungrouped_posts = [];
$pinned_posts    = [];

if ( $posts->have_posts() ) {
	while ( $posts->have_posts() ) {
		$posts->the_post();
		$post_id    = get_the_ID();

		// Check pinned status before grouping
		if ( get_post_meta( $post_id, '_faq_pinned', true ) === '1' ) {
			$pinned_posts[] = $post_id;
			continue;
		}

		$post_terms = get_the_terms( $post_id, 'kennisbank_categories' );

		if ( ! $post_terms || is_wp_error( $post_terms ) ) {
			$ungrouped_posts[] = $post_id;
			continue;
		}

		// Find the deepest child category that is a direct child of current_term
		$child_term_id = null;
		foreach ( $post_terms as $term ) {
			if ( $term->parent === $current_term->term_id ) {
				$child_term_id = $term->term_id;
				break;
			}
		}

		if ( $child_term_id ) {
			$grouped_posts[ $child_term_id ][] = $post_id;
		} else {
			$ungrouped_posts[] = $post_id;
		}
	}
	wp_reset_postdata();
}

$total_count = $posts->found_posts;

// Build child category lookup
$child_cat_map = [];
if ( $child_categories && ! is_wp_error( $child_categories ) ) {
	foreach ( $child_categories as $cat ) {
		$child_cat_map[ $cat->term_id ] = $cat;
	}
}

// Category pill navigation — reuse exact logic from archive-kennisbank.php
$current_term_id           = $current_term->term_id;
$current_term_has_children = (bool) get_term_children( $current_term_id, 'kennisbank_categories' );

if ( $current_term_has_children ) {
	$pill_categories = $child_categories;
	$all_url         = get_term_link( $current_term );
	$all_is_active   = true;
} else {
	$pill_categories = get_terms( [
		'taxonomy'   => 'kennisbank_categories',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'parent'     => $current_term->parent,
	] );
	$parent_term     = get_term( $current_term->parent, 'kennisbank_categories' );
	$all_url         = get_term_link( $parent_term );
	$all_is_active   = false;
}
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-text-white nok-dark-text-white collapse-bottom">
		<header class="nok-section__inner nok-mt-0 nok-mb-2">
			<?php Helpers::render_breadcrumbs(); ?>

			<h1 class="nok-fs-giant"><?= esc_html( $current_term->name ); ?></h1>
			<?php if ( $current_term->description ) : ?>
				<p class="nok-intro-text"><?= wp_kses_post( $current_term->description ); ?></p>
			<?php endif; ?>

			<!-- Search input -->
			<div class="nok-mt-1 w-100">
				<input type="search"
				       class="nok-search-input nok-text-contrast nok-bg-darkestblue--lighter nok-hover-to-darkestblue"
				       id="faq-search"
				       placeholder="Typ uw vraag, bijv. 'vitaminen'"
				       aria-label="Zoek in veelgestelde vragen">
			</div>

			<?php if ( $pill_categories && ! is_wp_error( $pill_categories ) ) :
				$current_slug = $current_term->slug;
				?>
				<nav class="nok-category-pills nok-mt-1" style="justify-content: center;" aria-label="<?php esc_attr_e( 'Filter op categorie', THEME_TEXT_DOMAIN ); ?>">
					<a href="<?= esc_url( $all_url ); ?>"
					   class="nok-pill <?= $all_is_active ? 'nok-pill--active' : ''; ?>">
						Alles
					</a>
					<?php foreach ( $pill_categories as $category ) : ?>
						<a href="<?= esc_url( get_term_link( $category ) ); ?>"
						   class="nok-pill nok-hover-to-darkestblue nok-bg-darkblue nok-text-contrast <?= $current_slug === $category->slug ? 'nok-pill--active' : ''; ?>">
							<?= esc_html( $category->name ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</header>
	</div>
</nok-hero>

<nok-section class="no-aos">
    <div class="nok-section__inner" data-requires="./nok-faq-search.mjs" id="faq-accordion-content">

		<?php // Render pinned posts above all category groups
		if ( ! empty( $pinned_posts ) ) : ?>
			<div class="faq-group nok-mb-2" data-category="pinned">
				<div class="nok-mb-0_5" style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.6rem; border-bottom: 2px solid var(--nok-darkblue);">
					<h2 class="nok-fs-3 fw-bold" style="margin: 0;">Uitgelicht</h2>
					<span class="nok-text-muted nok-fs-1" style="background: var(--nok-body--darker); padding: 0.2rem 0.65rem; border-radius: 100px; white-space: nowrap;">
						<?= sprintf( '%d vragen', count( $pinned_posts ) ) ?>
					</span>
				</div>

				<div data-requires="./nok-accordion.mjs"
				     data-require-lazy="true">

					<?php foreach ( $pinned_posts as $post_id ) :
						$title   = get_the_title( $post_id );
						$excerpt = get_the_excerpt( $post_id );
						?>
                        <nok-accordion class="nok-border-bottom-1">
							<details class="nok-bg-transparent nok-rounded-border"
							         name="faq-pinned"
							         data-search-title="<?= esc_attr( strtolower( $title ) ) ?>"
							         data-search-excerpt="<?= esc_attr( strtolower( wp_strip_all_tags( $excerpt ) ) ) ?>">
								<summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-sm-2 fw-bold">
									<?= esc_html( $title ) ?>
								</summary>
								<div class="accordion-content nok-p-2 nok-pt-0">
									<article>
										<?= wp_kses_post( $excerpt ) ?>
									</article>
									<footer class="nok-mt-1">
										<a role="button"
										   href="<?= esc_url( get_the_permalink( $post_id ) ) ?>"
										   class="nok-button nok-button--small nok-justify-self-start nok-bg-darkblue nok-text-contrast"
										   tabindex="0">
											Lees meer <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
										</a>
									</footer>
								</div>
							</details>
						</nok-accordion>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endif; ?>

		<?php
		// Render each child category group
		foreach ( $child_cat_map as $term_id => $child_cat ) :
			if ( empty( $grouped_posts[ $term_id ] ) ) {
				continue;
			}
			$child_slug  = $child_cat->slug;
			$child_name  = $child_cat->name;
			$child_count = count( $grouped_posts[ $term_id ] );
			$accordion_name = 'faq-' . sanitize_title( $child_slug );
			?>
			<div class="faq-group nok-mb-2" data-category="<?= esc_attr( $child_slug ) ?>">
				<div class="nok-mb-0_5" style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.6rem; border-bottom: 2px solid var(--nok-darkblue);">
					<h2 class="nok-fs-3 fw-bold" style="margin: 0;"><?= esc_html( $child_name ) ?></h2>
					<span class="nok-text-muted nok-fs-1" style="background: var(--nok-body--darker); padding: 0.2rem 0.65rem; border-radius: 100px; white-space: nowrap;">
						<?= sprintf( '%d vragen', $child_count ) ?>
					</span>
				</div>

				<div data-requires="./nok-accordion.mjs"
				     data-require-lazy="true">

					<?php foreach ( $grouped_posts[ $term_id ] as $post_id ) :
						$title   = get_the_title( $post_id );
						$excerpt = get_the_excerpt( $post_id );
						?>
                        <nok-accordion class="nok-border-bottom-1">
							<details class="nok-bg-transparent nok-rounded-border"
							         name="<?= esc_attr( $accordion_name ) ?>"
							         data-search-title="<?= esc_attr( strtolower( $title ) ) ?>"
							         data-search-excerpt="<?= esc_attr( strtolower( wp_strip_all_tags( $excerpt ) ) ) ?>">
								<summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-sm-2 fw-bold">
									<?= esc_html( $title ) ?>
								</summary>
								<div class="accordion-content nok-p-2 nok-pt-0">
									<article>
										<?= wp_kses_post( $excerpt ) ?>
									</article>
									<footer class="nok-mt-1">
										<a role="button"
										   href="<?= esc_url( get_the_permalink( $post_id ) ) ?>"
										   class="nok-button nok-button--small nok-justify-self-start nok-bg-darkblue nok-text-contrast"
										   tabindex="0">
											Lees meer <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
										</a>
									</footer>
								</div>
							</details>
						</nok-accordion>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endforeach; ?>

		<?php
		// Render ungrouped posts (assigned to parent category only)
		if ( ! empty( $ungrouped_posts ) ) :
			$ungrouped_count = count( $ungrouped_posts );
			$accordion_name  = 'faq-overig';
			?>
			<div class="faq-group nok-mb-2" data-category="overig">
				<div class="nok-mb-0_5" style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.6rem; border-bottom: 2px solid var(--nok-darkblue);">
					<h2 class="nok-fs-3 fw-bold" style="margin: 0;">Overig</h2>
					<span class="nok-text-muted nok-fs-1" style="background: var(--nok-body--darker); padding: 0.2rem 0.65rem; border-radius: 100px; white-space: nowrap;">
						<?= sprintf( '%d vragen', $ungrouped_count ) ?>
					</span>
				</div>

				<div data-requires="./nok-accordion.mjs"
				     data-require-lazy="true">

					<?php foreach ( $ungrouped_posts as $post_id ) :
						$title   = get_the_title( $post_id );
						$excerpt = get_the_excerpt( $post_id );
						?>
                        <nok-accordion class="nok-border-bottom-1">
							<details class="nok-bg-transparent nok-rounded-border"
							         name="<?= esc_attr( $accordion_name ) ?>"
							         data-search-title="<?= esc_attr( strtolower( $title ) ) ?>"
							         data-search-excerpt="<?= esc_attr( strtolower( wp_strip_all_tags( $excerpt ) ) ) ?>">
								<summary class="nok-py-1 nok-px-2 nok-fs-2 nok-fs-to-sm-2 fw-bold">
									<?= esc_html( $title ) ?>
								</summary>
								<div class="accordion-content nok-p-2 nok-pt-0">
									<article>
										<?= wp_kses_post( $excerpt ) ?>
									</article>
									<footer class="nok-mt-1">
										<a role="button"
										   href="<?= esc_url( get_the_permalink( $post_id ) ) ?>"
										   class="nok-button nok-button--small nok-justify-self-start nok-bg-darkblue nok-text-contrast"
										   tabindex="0">
											Lees meer <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
										</a>
									</footer>
								</div>
							</details>
						</nok-accordion>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endif; ?>

	</div>
</nok-section>

<!-- "Niet gevonden" CTA -->
<nok-section class="no-aos">
	<div class="nok-section__inner">
		<nok-square-block class="nok-bg-darkerblue nok-bg-alpha-6 nok-text-contrast nok-mt-2">
			<div class="nok-square-block__heading">
				<h3 class="fw-bold">Niet gevonden wat u zocht?</h3>
			</div>
			<a role="button" href="/contact/"
			   class="nok-button nok-justify-self-start nok-bg-darkblue nok-text-contrast" tabindex="0">
				Neem contact op <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ) ?>
			</a>
		</nok-square-block>
	</div>
</nok-section>

<?php
get_footer();
