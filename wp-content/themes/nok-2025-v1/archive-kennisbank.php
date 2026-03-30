<?php
/**
 * Archive Template: Kennisbank
 *
 * Displays kennisbank posts in a grid layout with article cards.
 * Used for:
 * - Post type archive (/kennisbank/)
 * - Category taxonomy archives (/kennisbank/{category}/)
 *
 * @package NOK2025_V1
 * @since   1.0.0
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

// Flat view: standalone HTML for Word export (?flat=true)
if ( isset( $_GET['flat'] ) && $_GET['flat'] === 'true' && is_user_logged_in() ) {
    nok_render_kennisbank_flat_view();
    exit;
}

// Accordion view: search-first FAQ layout (?view=accordion)
// Scoped to specific FAQ categories only — other kennisbank archives are unaffected.
// When making this permanent, remove the $_GET check and keep only the slug check.
$accordion_category_slugs = [ 'veelgestelde-vragen', 'veelgestelde-vragen-voor-verwijzers' ];
$_current_term            = is_tax( 'kennisbank_categories' ) ? get_queried_object() : null;
$is_faq_category          = $_current_term && in_array( $_current_term->slug, $accordion_category_slugs, true );

if ( $is_faq_category && isset( $_GET['view'] ) && $_GET['view'] === 'accordion' ) {
    require get_template_directory() . '/template-parts/archive-kennisbank-accordion.php';
    return;
}

get_header('generic');

// Determine archive type
$is_taxonomy_archive = is_tax('kennisbank_categories');
$current_term = $is_taxonomy_archive ? get_queried_object() : null;

// Handle category exclusion via ?exclude= parameter (supports comma-separated slugs)
$exclude_param = isset($_GET['exclude']) ? sanitize_text_field($_GET['exclude']) : '';
$exclude_slugs = array_filter(array_map('trim', explode(',', $exclude_param)));

$query_args = array_merge($wp_query->query, ['posts_per_page' => 12]);

if (!empty($exclude_slugs)) {
	$query_args['tax_query'] = $query_args['tax_query'] ?? [];
	$query_args['tax_query'][] = [
		'taxonomy' => 'kennisbank_categories',
		'field'    => 'slug',
		'terms'    => $exclude_slugs,
		'operator' => 'NOT IN',
	];
}

query_posts($query_args);
?>

<nok-hero class="nok-section">
	<div class="nok-section__inner nok-columns-1 nok-hero__inner nok-mt-0 nok-px-0 nok-border-radius-to-sm-0
		nok-bg-darkerblue nok-dark-bg-darkestblue nok-text-white nok-dark-text-white nok-bg-alpha-10 nok-dark-bg-alpha-10">
		<header class="nok-section__inner nok-mt-0 nok-mb-section-padding">
			<?php Helpers::render_breadcrumbs(); ?>

			<?php if ($is_taxonomy_archive && $current_term): ?>
				<!-- Category Taxonomy Archive -->
				<h1 class="nok-fs-giant"><?= esc_html($current_term->name); ?></h1>
				<?php if ($current_term->description): ?>
					<p class="nok-intro-text"><?= wp_kses_post($current_term->description); ?></p>
				<?php endif; ?>
			<?php else: ?>
				<!-- Main Kennisbank Archive -->
				<h1 class="nok-fs-giant"><?php post_type_archive_title(); ?></h1>
				<?php
				$intro = Theme::get_archive_intro('kennisbank', '');
				if ($intro):
					echo '<p class="nok-intro-text">' . wp_kses_post($intro) . '</p>';
				endif;
				?>
			<?php endif; ?>

            <?php
            // Category pill navigation
            // Logic: When viewing a parent category, show only its children
            // When viewing main archive or child category, show top-level categories
            $current_term_id = $current_term?->term_id ?? 0;
            $current_term_parent = $current_term?->parent ?? 0;
            $current_term_has_children = $current_term ? (bool) get_term_children($current_term_id, 'kennisbank_categories') : false;

            if ($is_taxonomy_archive && $current_term_has_children) {
                // Viewing a parent category: show its children only
                $pill_categories = get_terms([
                    'taxonomy'   => 'kennisbank_categories',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'parent'     => $current_term_id,
                ]);
                $all_url = get_term_link($current_term);
                $all_is_active = true; // "Alles" is current page
            } elseif ($is_taxonomy_archive && $current_term_parent > 0) {
                // Viewing a child category: show siblings (same parent)
                $pill_categories = get_terms([
                    'taxonomy'   => 'kennisbank_categories',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'parent'     => $current_term_parent,
                ]);
                $parent_term = get_term($current_term_parent, 'kennisbank_categories');
                $all_url = get_term_link($parent_term);
                $all_is_active = false;
            } else {
                // Main archive: show top-level categories only
                $pill_categories = get_terms([
                    'taxonomy'   => 'kennisbank_categories',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'parent'     => 0,
                ]);
                $all_url = get_post_type_archive_link('kennisbank');
                $all_is_active = !$is_taxonomy_archive;
            }

            if ($pill_categories && !is_wp_error($pill_categories)):
                $current_slug = $current_term?->slug;
                ?>
                <nav class="nok-category-pills nok-mb-2" aria-label="<?php esc_attr_e('Filter op categorie', THEME_TEXT_DOMAIN); ?>">
                    <a href="<?= esc_url($all_url); ?>"
                       class="nok-pill <?= $all_is_active ? 'nok-pill--active' : ''; ?>">
                        Alles
                    </a>
                    <?php foreach ($pill_categories as $category):
                        $is_excluded = in_array($category->slug, $exclude_slugs, true);
                        ?>
                        <a href="<?= esc_url(get_term_link($category)); ?>"
                           class="nok-pill nok-bg-white nok-text-contrast <?= $current_slug === $category->slug ? 'nok-pill--active' : ''; ?>"
                           <?= $is_excluded ? 'style="--bg-alpha-value: 0.3"' : ''; ?>>
                            <?= esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
		</header>
	</div>
</nok-hero>

<nok-section class="no-aos z-ascend">
	<div class="nok-section__inner nok-pull-up-2">

		<?php if (have_posts()): ?>
			<div class="nok-layout-grid nok-layout-grid__3-column nok-grid-gap-2">
				<?php while (have_posts()): the_post();
					$post_id = get_the_ID();
					$permalink = get_the_permalink();
					$title = get_the_title();
					$excerpt = get_the_excerpt();
					$date = get_the_date('d-m-y');

					// Get featured image
					$image_url = Helpers::get_featured_image_uri($post_id, 'medium_large');
					$has_image = has_post_thumbnail($post_id);

					// Get primary category
					$categories = get_the_terms($post_id, 'kennisbank_categories');
					$primary_category = $categories && !is_wp_error($categories) ? $categories[0] : null;
					?>

					<article class="nok-square-block link-bottom nok-bg-white nok-text-darkblue" data-shadow="true">
						<!-- Featured image with badge overlays -->
						<figure class="nok-square-block__image">
							<?php if ($has_image): ?>
								<img src="<?= esc_url($image_url); ?>"
								     alt="<?= esc_attr($title); ?>"
								     loading="lazy"
								     decoding="async">
							<?php elseif ($primary_category->name == 'Artikelen') : ?>
								<div class="nok-square-block__image--placeholder">
									<div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
										<?= Assets::getIcon('ui_info'); ?>
									</div>
								</div>
							<?php else: ?>
								<div class="nok-square-block__image--placeholder">
									<div style="transform: translateY(1em) rotateZ(45deg) scale(10);">
										<?= Assets::getIcon('ui_question'); ?>
									</div>
								</div>
							<?php endif; ?>

                            <?php if ($primary_category): ?>
							<div class="nok-square-block__badges">
                                <span class="nok-badge nok-bg-darkerblue nok-text-white">
                                    <?= esc_html($primary_category->name); ?>
                                </span>
							</div>
                            <?php endif; ?>
						</figure>

						<!-- Card content -->
						<h2 class="nok-square-block__heading nok-fs-3">
							<?= esc_html($title); ?>
						</h2>

						<p class="nok-square-block__text nok-fs-1">
							<?= esc_html(wp_trim_words($excerpt, 20, '...')); ?>
						</p>

						<a href="<?= esc_url($permalink); ?>"
						   class="nok-square-block__link"
						   title="<?= esc_attr($title); ?>">
							LEES MEER
							<?= Assets::getIcon('ui_arrow-right-long'); ?>
						</a>
					</article>

				<?php endwhile; ?>
			</div>

			<?php
			// Pagination
			the_posts_pagination([
				'class'              => 'nok-navigation-pagination',
				'mid_size'           => 2,
				'prev_text'          => Assets::getIcon('ui_arrow-left') . ' ' . __('<span class="nok-invisible-to-sm">Vorige</span>', THEME_TEXT_DOMAIN),
				'next_text'          => __('<span class="nok-invisible-to-sm">Volgende</span>', THEME_TEXT_DOMAIN) . ' ' . Assets::getIcon('ui_arrow-right'),
				'screen_reader_text' => __('Paginanavigatie', THEME_TEXT_DOMAIN),
			]);
			?>

		<?php else: ?>
			<p><?php esc_html_e('Geen artikelen gevonden.', THEME_TEXT_DOMAIN); ?></p>
		<?php endif; ?>
	</div>
</nok-section>

<?php
wp_reset_query();
get_footer();

/**
 * Render kennisbank posts as standalone HTML for Word export.
 *
 * Outputs a minimal HTML page (no theme chrome) with all posts in the current
 * category context, sorted alphabetically. Designed for copy-pasting to Word.
 *
 * Triggered by ?flat=true query parameter.
 *
 * @since 1.0.0
 */
function nok_render_kennisbank_flat_view(): void {
    $is_taxonomy_archive = is_tax( 'kennisbank_categories' );
    $current_term        = $is_taxonomy_archive ? get_queried_object() : null;

    // Build query
    $query_args = [
        'post_type'      => 'kennisbank',
        'posts_per_page' => - 1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    // Filter by category if viewing a taxonomy archive
    if ( $current_term && ! is_wp_error( $current_term ) ) {
        $query_args['tax_query'] = [
            [
                'taxonomy'         => 'kennisbank_categories',
                'field'            => 'term_id',
                'terms'            => $current_term->term_id,
                'include_children' => true,
            ],
        ];
    }

    // Handle ?exclude= parameter (comma-separated category slugs)
    $exclude_param = isset( $_GET['exclude'] ) ? sanitize_text_field( $_GET['exclude'] ) : '';
    $exclude_slugs = array_filter( array_map( 'trim', explode( ',', $exclude_param ) ) );
    if ( ! empty( $exclude_slugs ) ) {
        $query_args['tax_query']             = $query_args['tax_query'] ?? [];
        $query_args['tax_query']['relation'] = 'AND';
        $query_args['tax_query'][]           = [
            'taxonomy' => 'kennisbank_categories',
            'field'    => 'slug',
            'terms'    => $exclude_slugs,
            'operator' => 'NOT IN',
        ];
    }

    $posts = new WP_Query( $query_args );

    $title = $current_term ? esc_html( $current_term->name ) : 'Kennisbank';
    $count = $posts->found_posts;
    $date  = wp_date( 'j F Y, H:i' );
    ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $title; ?> — NOK Kennisbank (platte weergave)</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, 'Segoe UI', sans-serif; max-width: 960px; margin: 0 auto; padding: 2rem 1rem; line-height: 1.6; color: #1a1a2e; }
        h1 { text-align: center; margin-bottom: 0.25rem; font-size: 1.75rem; }
        .meta { text-align: center; color: #666; margin-bottom: 2rem; font-size: 0.875rem; }
        article { margin-bottom: 3rem; }
        article h2 { font-size: 1.25rem; padding-bottom: 0.5rem; margin-bottom: 1rem; border-bottom: 2px solid #e0e0e0; position: sticky; top: 0; background: white; z-index: 1; }
        article .content { font-size: 0.9375rem; }
        article .content p { margin-bottom: 0.75rem; }
        article .content ul, article .content ol { margin: 0.75rem 0; padding-left: 1.5rem; }
        article .content li { margin-bottom: 0.25rem; }
        article .content img { max-width: 100%; height: auto; }
        article .content h3, article .content h4 { margin: 1rem 0 0.5rem; }
        @media print { article h2 { position: static; } }
    </style>
</head>
<body>
    <h1><?= $title; ?></h1>
    <p class="meta"><?= esc_html( $count ); ?> artikelen — gegenereerd op <?= esc_html( $date ); ?></p>
    <?php if ( $posts->have_posts() ): while ( $posts->have_posts() ): $posts->the_post(); ?>
        <article>
            <h2><?= esc_html( get_the_title() ); ?></h2>
            <div class="content"><?= apply_filters( 'the_content', get_the_content() ); ?></div>
        </article>
    <?php endwhile; endif; ?>
    <?php wp_reset_postdata(); ?>
</body>
</html>
    <?php
}
