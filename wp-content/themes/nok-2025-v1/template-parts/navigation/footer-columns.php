<?php
/**
 * Footer Columns Template
 * Renders footer navigation with accordion behavior on mobile
 *
 * Desktop: Column headers are non-interactive labels with links beneath
 * Mobile: Headers become accordion toggles (using <details>) that expand/collapse
 *
 * Note: This template renders individual accordion columns without a wrapper.
 * The parent template should provide the grid container.
 *
 * Available variables:
 * @var array $menu_items Hierarchical menu structure
 * @var string $location Menu location identifier
 */

// Fallback if no menu assigned
if ( empty( $menu_items ) ) {
	if ( current_user_can( 'manage_options' ) ) {
		?>
		<div class="nok-footer-columns--empty">
			<a href="<?= esc_url( admin_url( 'nav-menus.php?action=locations' ) ); ?>" class="nok-nav-menu-item">
				<?= esc_html__( 'Assign a menu to the Footer location', THEME_TEXT_DOMAIN ); ?>
			</a>
		</div>
		<?php
	}

	return;
}

// Filter to only header items with children (columns)
$columns = array_filter( $menu_items, function( $item ) {
	return $item['is_header'] && $item['has_children'];
} );

// If no valid columns found, show all top-level items as simple links
if ( empty( $columns ) ) {
	?>
	<div class="nok-footer-column">
		<ul class="nok-ul-list">
			<?php foreach ( $menu_items as $item ): ?>
				<li>
					<a href="<?= esc_url( $item['url'] ?: '#' ); ?>" class="nok-nav-menu-item">
						<?= esc_html( $item['title'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return;
}

// Render each column as an accordion
foreach ( $columns as $column ): ?>
	<nok-accordion class="nok-border-bottom-to-lg-1 nok-pt-to-lg-1 nok-pb-to-lg-0_5">
		<details data-opened-at="lg" name="footer-accordion-group">
			<summary class="nok-fs-2 nok-fs-to-lg-3 fw-bold nok-mb-0_5">
				<?= esc_html( $column['title'] ); ?>
			</summary>
			<div class="accordion-content">
				<ul class="nok-ul-list">
					<?php foreach ( $column['children'] as $child ):
						$classes = [ 'nok-nav-menu-item' ];

						if ( $child['is_current'] ) {
							$classes[] = 'nok-nav-menu-item--active';
						}

						if ( ! empty( $child['classes'] ) ) {
							$classes = array_merge( $classes, array_filter( $child['classes'] ) );
						}

						$class_string = implode( ' ', array_map( 'esc_attr', $classes ) );

						$attrs = [];
						if ( ! empty( $child['target'] ) ) {
							$attrs[] = 'target="' . esc_attr( $child['target'] ) . '"';
						}
						if ( ! empty( $child['attr_title'] ) ) {
							$attrs[] = 'title="' . esc_attr( $child['attr_title'] ) . '"';
						}
						$attr_string = ! empty( $attrs ) ? ' ' . implode( ' ', $attrs ) : '';
						?>
						<li>
							<a href="<?= esc_url( $child['url'] ); ?>" class="<?= $class_string; ?>"<?= $attr_string; ?>>
								<?= esc_html( $child['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</details>
	</nok-accordion>
<?php endforeach;