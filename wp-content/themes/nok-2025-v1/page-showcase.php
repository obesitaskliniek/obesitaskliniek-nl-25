<?php
/* Template Name: Page Parts Showcase */

use NOK2025\V1\Colors;
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

<script>
    function showcaseScrollTo(slug) {
        const anchor = document.getElementById(slug);
        if (!anchor) return;
        const top = anchor.getBoundingClientRect().top + window.scrollY;
        requestAnimationFrame(function () {
            window.scrollTo({top: top, behavior: 'auto'});
        });
    }

    // Handle initial hash after full page load (images, etc.)
    window.addEventListener('load', function () {
        const hash = location.hash.slice(1);
        if (hash) {
            // Delay to let sticky layout settle after all content is loaded
            requestAnimationFrame(function () {
                showcaseScrollTo(hash);

            });
        }
    });
</script>

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
									<a href="#<?= esc_attr($slug) ?>" onclick="event.preventDefault(); history.pushState(null, '', this.href); showcaseScrollTo('<?= esc_attr($slug) ?>');" style="color: inherit; text-decoration: underline;">
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
		<div id="<?= esc_attr($design_slug) ?>" style="scroll-margin-top:0;position: relative;"></div>
		<nok-section class="nok-bg-white" style="
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

		<?php
		$template_fields = $registry[$design_slug]['custom_fields'] ?? [];
		$template_desc   = $registry[$design_slug]['description'] ?? '';
		$has_featured    = !empty($registry[$design_slug]['featured_image_overridable']);
		?>

		<?php if (!empty($template_fields) || $template_desc || $has_featured) : ?>
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
                        border-bottom: 1px solid #000000;">Eigenschappen</div>
			<nok-section class="nok-bg-body--darker" style="border-bottom: 1px solid #c3c4c7;">
				<div class="nok-section__inner condensed nok-bg-body nok-rounded-border-large small nok-p-3" style="padding-top: 1rem; padding-bottom: 1rem;">

					<?php if ($template_desc) : ?>
						<p style="margin: 0 0 0.75rem; opacity: 0.7; font-style: italic;">
							<?= esc_html($template_desc) ?>
						</p>
					<?php endif; ?>

					<?php if ($has_featured) : ?>
						<p style="margin: 0 0 0.75rem;">
							<span style="display: inline-block; background: #dba617; color: #fff; font-size: 0.75rem; padding: 0.15em 0.5em; border-radius: 3px; font-weight: 600;">Featured Image Overridable</span>
						</p>
					<?php endif; ?>

					<?php if (!empty($template_fields)) : ?>
						<table style="width: 100%; border-collapse: collapse;">
							<thead>
								<tr style="text-align: left; border-bottom: 2px solid #c3c4c7;">
									<th style="padding: 0.35em 0.75em 0.35em 0;">Veld</th>
									<th style="padding: 0.35em 0.75em;">Type</th>
									<th style="padding: 0.35em 0.75em;">Standaardwaarde</th>
									<th style="padding: 0.35em 0.75em;">Beschrijving/opties</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($template_fields as $field) :
									// Build details column
									$details = [];
									if (!empty($field['description'])) {
										$details[] = esc_html($field['description']);
									}
									if (!empty($field['options'])) {
										if ($field['type'] === 'color-selector' && !empty($field['palette'])) {
											// Render color swatches instead of text
											$palette = Colors::getPalette($field['palette']);
											$palette_by_value = [];
											foreach ($palette as $entry) {
												$palette_by_value[$entry['value']] = $entry['color'];
											}
											$swatch_html = '<span style="display: inline-flex; flex-wrap: wrap; gap: 4px; align-items: center;">';
											$labels = $field['option_labels'] ?? $field['options'];
											foreach ($field['options'] as $i => $opt) {
												$hex = $palette_by_value[$opt] ?? Colors::resolveColor($opt);
												$label = $labels[$i] ?? $opt;
												$is_transparent = ($hex === 'transparent' || $hex === 'inherit' || $opt === '');
												$is_light = in_array($hex, ['#ffffff', '#f3f4f9', '#e5e7ef', '#cccccc', 'transparent', 'inherit'], true);
												$border = ($is_light || $is_transparent)
													? '1px solid #c3c4c7'
													: '1px solid transparent';
												if ($is_transparent) {
													$bg_style = 'background: linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%), linear-gradient(45deg, #ccc 25%, transparent 25%, transparent 75%, #ccc 75%); background-size: 6px 6px; background-position: 0 0, 3px 3px';
												} else {
													$bg_style = 'background: ' . esc_attr($hex);
												}
												$swatch_html .= sprintf(
													'<span title="%s" style="display: inline-block; width: 18px; height: 18px; border-radius: 50%%; border: %s; %s; flex-shrink: 0;"></span>',
													esc_attr($label . ' (' . $opt . ')'),
													$border,
													$bg_style
												);
											}
											$swatch_html .= '</span>';
											$details[] = $swatch_html;
										} else {
											$labels = $field['option_labels'] ?? $field['options'];
											$option_parts = [];
											foreach ($field['options'] as $i => $opt) {
												$label = $labels[$i] ?? $opt;
												if ($label !== $opt) {
													$option_parts[] = esc_html($label) . '::' . esc_html($opt);
												} else {
													$option_parts[] = esc_html($opt);
												}
											}
											$details[] = implode(' | ', $option_parts);
										}
									}
									if (!empty($field['schema'])) {
										$sub_fields = array_map(function($s) {
											return esc_html($s['name']) . ':' . esc_html($s['type']);
										}, $field['schema']);
										$details[] = '(' . implode(', ', $sub_fields) . ')';
									}
									if (!empty($field['post_types'])) {
										$details[] = 'posts: ' . esc_html(implode('|', $field['post_types']));
										if (!empty($field['categories'])) {
											$details[count($details) - 1] .= ':' . esc_html(implode(',', $field['categories']));
										}
									}
									if (!empty($field['taxonomy'])) {
										$tax_detail = esc_html($field['taxonomy']);
										if (!empty($field['multiple'])) {
											$tax_detail .= ' (multi)';
										} else {
											$tax_detail .= ' (single)';
										}
										$details[] = $tax_detail;
									}
									if (!empty($field['palette'])) {
										$details[] = 'palette: ' . esc_html($field['palette']);
									}
								?>
									<tr style="border-bottom: 1px solid #dcdcde;">
										<td style="padding: 0.35em 0.75em 0.35em 0; font-weight: 600; white-space: nowrap;">
											<?= esc_html($field['name']) ?><?php if (!empty($field['page_editable'])) : ?><span style="color: #b32d2e;" title="page-editable">*</span><?php endif; ?>
										</td>
										<td style="padding: 0.35em 0.75em;">
											<code style="background: #f0f0f1; padding: 0.1em 0.4em; border-radius: 3px; font-size: 0.9em;">
												<?= esc_html($field['type']) ?>
											</code>
										</td>
										<td style="padding: 0.35em 0.75em; white-space: nowrap;">
											<?php if ($field['default'] !== null && $field['default'] !== '') : ?>
												<code style="background: #f0f0f1; padding: 0.1em 0.4em; border-radius: 3px; font-size: 0.9em;">
													<?= esc_html($field['default']) ?>
												</code>
											<?php endif; ?>
										</td>
										<td style="padding: 0.35em 0.75em; color: #50575e; word-break: break-word;">
											<?= implode('<br>', $details) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php
						$has_page_editable = array_filter($template_fields, fn($f) => !empty($f['page_editable']));
						if (!empty($has_page_editable)) : ?>
							<p style="margin: 0.5rem 0 0; opacity: 0.6; font-size: 0.8em;">
								<span style="color: #b32d2e;">*</span> page-editable — overschrijfbaar per pagina
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</nok-section>
		<?php endif; ?>

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
