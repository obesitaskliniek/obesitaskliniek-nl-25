<?php
/* Template Name: Page Parts Showcase */

use NOK2025\V1\Helpers;
use NOK2025\V1\Theme;

add_filter('body_class', function($classes) {
    $classes[] = 'no-aos';
    return $classes;
});

get_header();

$theme = Theme::get_instance();
$registry = $theme->get_page_part_registry();

// Query all published page parts
$parts_query = new WP_Query([
	'post_type'      => 'page_part',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'title',
	'order'          => 'ASC',
]);

// Group posts by design_slug
$groups = [];
if ($parts_query->have_posts()) {
	while ($parts_query->have_posts()) {
		$parts_query->the_post();
		$part_id     = get_the_ID();
		$design_slug = get_post_meta($part_id, 'design_slug', true);

		if (empty($design_slug)) {
			$design_slug = '_no-template';
		}

		$groups[$design_slug][] = get_post();
	}
	wp_reset_postdata();
}

// Sort groups alphabetically by template name from registry
uksort($groups, function ($a, $b) use ($registry) {
	$name_a = $registry[$a]['name'] ?? $a;
	$name_b = $registry[$b]['name'] ?? $b;
	return strcasecmp($name_a, $name_b);
});
?>

<style>
    .nok-section {
        overflow: hidden;
    }
</style>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

	<nok-section class="nok-bg-body nok-text-contrast">
		<div class="nok-section__inner">
			<div class="nok-layout-grid nok-layout-grid__1-column nok-align-items-start">
				<?php Helpers::render_breadcrumbs(); ?>

				<?php the_title('<h1 class="nok-fs-6">', '</h1>'); ?>

				<?php if (trim(get_the_content())) : ?>
					<div class="nok-layout-grid nok-layout-grid__1-column">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php if (!empty($groups)) : ?>
					<nav aria-label="Inhoudsopgave">
						<h2 class="nok-fs-4 nok-mb-1">Inhoudsopgave</h2>
						<ul style="columns: 2; column-gap: 2rem; list-style: none; padding: 0;">
							<?php foreach ($groups as $slug => $posts) :
								$template_name = $registry[$slug]['name'] ?? $slug;
								$count = count($posts);
							?>
								<li style="break-inside: avoid; margin-bottom: 0.5em;">
									<a href="#<?= esc_attr($slug) ?>" style="color: inherit; text-decoration: underline;">
										<?= esc_html($template_name) ?>
									</a>
									<small>(<?= $count ?>)</small>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</nok-section>

<?php endwhile; endif; ?>

<?php if (!empty($groups)) : ?>
	<?php foreach ($groups as $design_slug => $posts) :
		$template_name = $registry[$design_slug]['name'] ?? $design_slug;
		$count = count($posts);
	?>
		<nok-section class="nok-bg-white" id="<?= esc_attr($design_slug) ?>" style="
        position: sticky;
        height: 8rem;
        max-height: 8rem;
        top: 0;
        z-index: 98;">
			<div class="nok-section__inner condensed">
                <h2 class="nok-fs-5">
                    <?= esc_html($template_name) ?>
                    <small style="font-weight: normal; opacity: 0.6;"><?= esc_html($design_slug) ?> (<?= $count ?>)</small>
                </h2>
			</div>
		</nok-section>

		<?php foreach ($posts as $part_post) :
			$part_id = $part_post->ID;
			$edit_url = admin_url("post.php?post={$part_id}&action=edit");
		?>
			<div style="background: #f0f0f1;
			padding: .5rem 3vw;
			position: sticky;
			top: 8rem;
            font-size: 0.85rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 97;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;">
				<strong><?= esc_html($part_post->post_title) ?></strong>
				<span style="opacity: 0.6;">ID <?= $part_id ?></span>
				<?php if (current_user_can('edit_posts')) : ?>
					<a href="<?= esc_url($edit_url) ?>" target="_blank" rel="noopener" style="margin-left: auto; color: #2271b1; text-decoration: none;">
						Bewerken &rarr;
					</a>
				<?php endif; ?>
			</div>

			<?php
			$page_part_fields = $theme->get_page_part_fields($part_id, $design_slug, false);
			$theme->include_page_part_template($design_slug, [
				'post'             => $part_post,
				'page_part_fields' => $page_part_fields,
			]);
			?>

		<?php endforeach; ?>

	<?php endforeach; ?>
<?php else : ?>
	<nok-section class="nok-bg-white">
		<div class="nok-section__inner">
			<p>Er zijn geen gepubliceerde page parts gevonden.</p>
		</div>
	</nok-section>
<?php endif; ?>

<?php get_footer(); ?>
