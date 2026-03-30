<?php
/**
 * Server-side render callback for NOK Chart block.
 *
 * Outputs a <figure> with:
 * - A <canvas> for Chart.js rendering (hidden from assistive tech)
 * - A <script type="application/json"> with the Chart.js config
 * - An accessible <table> with the raw data (screen-reader-text)
 * - An optional <figcaption>
 *
 * The DOMule loader lazy-loads nok-chart.mjs which reads the JSON config
 * and creates a Chart.js instance on the canvas.
 *
 * @param array    $attributes Block attributes from block.json schema.
 * @param string   $content    Inner block content (unused).
 * @param WP_Block $block      Block instance.
 * @return string Rendered HTML.
 */
return function ( array $attributes, string $content, WP_Block $block ): string {
	$chart_type    = $attributes['chartType'] ?? 'bar';
	$labels        = $attributes['labels'] ?? [];
	$datasets      = $attributes['datasets'] ?? [];
	$chart_title   = $attributes['chartTitle'] ?? '';
	$show_legend   = $attributes['showLegend'] ?? true;
	$show_tooltips = $attributes['showTooltips'] ?? true;
	$x_axis_label  = $attributes['xAxisLabel'] ?? '';
	$y_axis_label  = $attributes['yAxisLabel'] ?? '';
	$tension       = (float) ( $attributes['tension'] ?? 0 );
	$show_points    = $attributes['showPoints'] ?? true;
	$x_axis_divisor = (float) ( $attributes['xAxisDivisor'] ?? 1 );
	$y_axis_padding = (float) ( $attributes['yAxisPadding'] ?? 0 );
	$y_axis_min     = $attributes['yAxisMin'] ?? '';
	$show_data_table = $attributes['showDataTable'] ?? false;
	$value_suffix    = $attributes['valueSuffix'] ?? '';

	if ( empty( $labels ) || empty( $datasets ) ) {
		return '';
	}

	// Build Chart.js dataset config
	$chartjs_datasets = [];
	$dataset_count    = count( $datasets );

	foreach ( $datasets as $di => $ds ) {
		$color = $ds['color'] ?? '#14477c';
		$base  = [
			'label'       => $ds['label'] ?? '',
			'data'        => array_map( 'floatval', $ds['data'] ?? [] ),
			'borderColor' => $color,
			'borderWidth' => $chart_type === 'line' ? 2 : 0,
			'tension'     => $chart_type === 'line' ? $tension : 0,
			'pointRadius' => $chart_type === 'line' && ! $show_points ? 0 : 3,
		];

		if ( ! empty( $ds['fillToNext'] ) && $chart_type === 'line' ) {
			// Fill to next dataset if one exists, otherwise to x-axis
			$base['fill']            = $di < $dataset_count - 1 ? '+1' : 'origin';
			$base['backgroundColor'] = self_hex_to_rgba( $color, 0.3 );
		} else {
			$base['fill']            = false;
			$base['backgroundColor'] = $color;
		}

		$chartjs_datasets[] = $base;
	}

	$config = [
		'type'    => $chart_type,
		'data'    => [
			'labels'   => $labels,
			'datasets' => $chartjs_datasets,
		],
		'options' => [
			'responsive'          => true,
			'maintainAspectRatio' => false,
			'plugins'             => [
				'legend'  => [ 'display' => $show_legend ],
				'tooltip' => [ 'enabled' => $show_tooltips ],
			],
			'xAxisDivisor'        => $x_axis_divisor,
			'valueSuffix'         => $value_suffix,
			'scales'              => [
				'x' => [
					'title' => [
						'display' => ! empty( $x_axis_label ),
						'text'    => $x_axis_label,
					],
				],
				'y' => array_merge(
					[
						'title' => [
							'display' => ! empty( $y_axis_label ),
							'text'    => $y_axis_label,
						],
					],
					$y_axis_padding > 0 ? [ 'grace' => $y_axis_padding ] : [],
					$y_axis_min !== '' ? [ 'min' => (float) $y_axis_min ] : []
				),
			],
		],
	];

	$wrapper_attributes = get_block_wrapper_attributes( [
		'class' => 'nok-chart',
	] );

	ob_start();
	?>
	<figure <?php echo $wrapper_attributes; ?>
	        data-requires="./nok-chart.mjs"
	        data-require-lazy="true">

		<div class="nok-chart__canvas-wrap<?php echo $chart_type === 'bar' ? ' nok-chart__canvas-wrap--bar' : ''; ?>">
			<canvas class="nok-chart__canvas" aria-hidden="true"></canvas>
		</div>

		<script type="application/json" class="nok-chart__config"><?php
			echo wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		?></script>

		<?php if ( $show_data_table ) : ?>
			<div class="nok-chart__table-wrap">
				<table class="nok-chart__data nok-chart__data--visible<?php echo $show_legend ? ' nok-chart__data--has-legend' : ''; ?>">
					<tbody>
						<?php foreach ( $datasets as $ds ) : ?>
							<tr>
								<th scope="row">
									<span class="nok-chart__swatch" style="background-color: <?php echo esc_attr( $ds['color'] ?? '#14477c' ); ?>;"></span>
									<span class="nok-chart__label"><?php echo esc_html( $ds['label'] ?? '' ); ?></span>
								</th>
								<?php foreach ( ( $ds['data'] ?? [] ) as $value ) : ?>
									<td><?php echo esc_html( $value . $value_suffix ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<table class="nok-chart__data screen-reader-text">
				<thead>
					<tr>
						<th scope="col">&nbsp;</th>
						<?php foreach ( $labels as $label ) : ?>
							<th scope="col"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $datasets as $ds ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $ds['label'] ?? '' ); ?></th>
							<?php foreach ( ( $ds['data'] ?? [] ) as $value ) : ?>
								<td><?php echo esc_html( $value . $value_suffix ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $chart_title ) ) : ?>
			<figcaption class="nok-chart__caption"><?php echo esc_html( $chart_title ); ?></figcaption>
		<?php endif; ?>

	</figure>
	<?php
	return ob_get_clean();
};

/**
 * Convert hex color to rgba string.
 *
 * @param string $hex   Hex color (e.g. "#14477c").
 * @param float  $alpha Opacity 0–1.
 * @return string CSS rgba value.
 */
function self_hex_to_rgba( string $hex, float $alpha ): string {
	$hex = ltrim( $hex, '#' );
	$r   = hexdec( substr( $hex, 0, 2 ) );
	$g   = hexdec( substr( $hex, 2, 2 ) );
	$b   = hexdec( substr( $hex, 4, 2 ) );
	return "rgba($r, $g, $b, $alpha)";
}
