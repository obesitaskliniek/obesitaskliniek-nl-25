import { registerBlockType, createBlock } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	TextareaControl,
	Button,
	ColorIndicator,
	ColorPicker,
	RangeControl,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Chart from 'chart.js/auto';

const CHART_TYPES = [
	{ label: __( 'Staafdiagram', 'nok-2025-v1' ), value: 'bar' },
	{ label: __( 'Lijndiagram', 'nok-2025-v1' ), value: 'line' },
];

/**
 * Convert hex color to rgba string.
 *
 * @param {string} hex  Hex color (e.g. "#14477c")
 * @param {number} alpha Opacity 0–1
 * @return {string} CSS rgba value
 */
function hexToRgba( hex, alpha ) {
	const r = parseInt( hex.slice( 1, 3 ), 16 );
	const g = parseInt( hex.slice( 3, 5 ), 16 );
	const b = parseInt( hex.slice( 5, 7 ), 16 );
	return `rgba(${ r }, ${ g }, ${ b }, ${ alpha })`;
}

const CHART_COLORS = window.PagePartDesignSettings?.chartColors || [
	{ label: 'Donkerblauw', hex: '#14477c' },
	{ label: 'Lichtblauw', hex: '#00b0e4' },
	{ label: 'Groenblauw', hex: '#35aba5' },
	{ label: 'Geel', hex: '#ffd41f' },
	{ label: 'Groen', hex: '#54b085' },
	{ label: 'Donkerst blauw', hex: '#0b2355' },
	{ label: 'Clinics blauw', hex: '#B6BBD6' },
	{ label: 'Clinics oranje', hex: '#C27655' },
];

/**
 * Parse tabular text (tab-separated or comma-separated) into labels + datasets.
 *
 * Expects:
 * - First row: [empty or label-column-header, label1, label2, ...]
 * - Subsequent rows: [series-name, value1, value2, ...]
 *
 * @param {string} text Raw text (pasted from Excel, CSV, etc.)
 * @return {{ labels: string[], datasets: Array<{label: string, data: number[], color: string, customColor: boolean}> } | null}
 */
function parseTabularData( text ) {
	const lines = text.trim().split( /\r?\n/ );
	if ( lines.length < 2 ) {
		return null;
	}

	// Detect delimiter: tabs (Excel paste) vs commas (CSV)
	const delimiter = lines[ 0 ].includes( '\t' ) ? '\t' : ',';

	const rows = lines.map( ( line ) =>
		line.split( delimiter ).map( ( cell ) => cell.trim() )
	);

	const headerRow = rows[ 0 ];

	// Detect label column: either first header cell is empty, or data rows
	// have non-numeric text in the first cell (e.g. series names like "Bovengrens")
	const headerFirstEmpty = ! headerRow[ 0 ];
	const hasLabelColumn =
		headerFirstEmpty ||
		( rows.length > 1 &&
			rows.slice( 1 ).some( ( row ) => {
				const val = ( row[ 0 ] || '' )
					.replace( /%/g, '' )
					.replace( /,/g, '.' );
				return val !== '' && isNaN( parseFloat( val ) );
			} ) );

	// Only slice the header if its first cell is actually empty (a placeholder).
	// If data rows have text labels but the header has a real value in cell 0,
	// keep it — the header has one fewer column than data rows.
	const labels = headerFirstEmpty ? headerRow.slice( 1 ) : headerRow;

	const datasets = [];
	for ( let i = 1; i < rows.length; i++ ) {
		const row = rows[ i ];
		if ( ! row.length || row.every( ( c ) => ! c ) ) {
			continue;
		}

		const label = hasLabelColumn ? row[ 0 ] || `Serie ${ i }` : `Serie ${ i }`;
		const values = ( hasLabelColumn ? row.slice( 1 ) : row ).map( ( v ) => {
			const cleaned = v.replace( /%/g, '' ).replace( /,/g, '.' );
			return parseFloat( cleaned ) || 0;
		} );

		datasets.push( {
			label,
			data: values,
			color: '',
			customColor: false,
		} );
	}

	return datasets.length ? { labels, datasets } : null;
}

/**
 * Assign default NOK palette colors to datasets that have no color set.
 *
 * @param {Array} datasets
 * @return {Array} Datasets with colors assigned
 */
function assignDefaultColors( datasets ) {
	return datasets.map( ( ds, i ) => ( {
		...ds,
		color: ds.color || CHART_COLORS[ i % CHART_COLORS.length ]?.hex || '#14477c',
	} ) );
}

/**
 * Strip HTML tags from a string.
 *
 * @param {string} html
 * @return {string}
 */
function stripTags( html ) {
	return html.replace( /<[^>]*>/g, '' ).trim();
}

/**
 * Parse core/table block attributes into chart data.
 *
 * @param {{ head: Array, body: Array }} attrs core/table attributes
 * @return {{ labels: string[], datasets: Array } | null}
 */
function parseTableBlockAttributes( attrs ) {
	const head = attrs.head || [];
	const body = attrs.body || [];

	if ( ! body.length ) {
		return null;
	}

	let headerCells;
	let dataRows;

	if ( head.length && head[ 0 ].cells ) {
		// Table has <thead> — use it for labels
		headerCells = head[ 0 ].cells.map( ( c ) => stripTags( c.content || '' ) );
		dataRows = body;
	} else {
		// No <thead> — first body row is headers
		headerCells = body[ 0 ].cells.map( ( c ) => stripTags( c.content || '' ) );
		dataRows = body.slice( 1 );
	}

	if ( ! dataRows.length ) {
		return null;
	}

	// Detect label column: either first header cell is empty, or data rows
	// have non-numeric text in the first cell
	const headerFirstEmpty = ! headerCells[ 0 ];
	const hasLabelColumn =
		headerFirstEmpty ||
		dataRows.some( ( row ) => {
			const val = stripTags( row.cells[ 0 ]?.content || '' )
				.replace( /%/g, '' )
				.replace( /,/g, '.' );
			return val !== '' && isNaN( parseFloat( val ) );
		} );
	const labels = headerFirstEmpty ? headerCells.slice( 1 ) : headerCells;

	const datasets = dataRows.map( ( row, i ) => {
		const cells = row.cells.map( ( c ) => stripTags( c.content || '' ) );
		const label = hasLabelColumn
			? cells[ 0 ] || `Serie ${ i + 1 }`
			: `Serie ${ i + 1 }`;
		const values = ( hasLabelColumn ? cells.slice( 1 ) : cells ).map( ( v ) => {
			const cleaned = v.replace( /%/g, '' ).replace( /,/g, '.' );
			return parseFloat( cleaned ) || 0;
		} );

		return { label, data: values, color: '', customColor: false };
	} );

	return datasets.length ? { labels, datasets } : null;
}

// ─── Block registration ──────────────────────────────────────────

registerBlockType( 'nok2025/nok-chart', {
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/table' ],
				transform: ( attributes ) => {
					const parsed = parseTableBlockAttributes( attributes );
					if ( ! parsed ) {
						return createBlock( 'nok2025/nok-chart', {} );
					}
					return createBlock( 'nok2025/nok-chart', {
						labels: parsed.labels,
						datasets: assignDefaultColors( parsed.datasets ),
					} );
				},
			},
		],
	},

	edit: function ChartBlockEdit( { attributes, setAttributes } ) {
		const {
			chartType, labels, datasets, chartTitle,
			showLegend, showTooltips, xAxisLabel, yAxisLabel, tension, showPoints,
			xAxisDivisor, yAxisPadding, yAxisMin, showDataTable, valueSuffix,
		} = attributes;

		const [ pasteText, setPasteText ] = useState( '' );
		const [ colorPickerDataset, setColorPickerDataset ] = useState( null );
		const canvasRef = useRef( null );
		const chartRef = useRef( null );

		const blockProps = useBlockProps( {
			className: 'nok-chart-editor',
			style: {
				width: '100%',
				maxWidth: '100%',
				padding: '15px 3vw',
				boxSizing: 'border-box',
			},
		} );

		// ─── Chart preview ───────────────────────────────────────

		useEffect( () => {
			if ( ! canvasRef.current || ! labels.length || ! datasets.length ) {
				if ( chartRef.current ) {
					chartRef.current.destroy();
					chartRef.current = null;
				}
				return;
			}

			if ( chartRef.current ) {
				chartRef.current.destroy();
			}

			chartRef.current = new Chart( canvasRef.current, {
				type: chartType,
				data: {
					labels,
					datasets: datasets.map( ( ds, di ) => {
						const color = ds.color || '#14477c';
						const base = {
							label: ds.label,
							data: ds.data,
							borderColor: color,
							borderWidth: chartType === 'line' ? 2 : 0,
							tension: chartType === 'line' ? tension : 0,
							pointRadius: chartType === 'line' && ! showPoints ? 0 : undefined,
						};
						if ( ds.fillToNext && chartType === 'line' ) {
							// Fill to next dataset if one exists, otherwise to x-axis
							base.fill = di < datasets.length - 1 ? '+1' : 'origin';
							base.backgroundColor = hexToRgba( color, 0.3 );
						} else {
							base.fill = false;
							base.backgroundColor = color;
						}
						return base;
					} ),
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					animation: false,
					plugins: {
						legend: { display: showLegend },
						tooltip: {
							enabled: showTooltips,
							callbacks: valueSuffix
								? {
										label( ctx ) {
											return `${ ctx.dataset.label }: ${ ctx.parsed.y }${ valueSuffix }`;
										},
									}
								: {},
						},
					},
					scales: {
						x: {
							title: {
								display: !! xAxisLabel,
								text: xAxisLabel,
							},
							ticks: xAxisDivisor > 1
								? {
										autoSkip: false,
										callback( value ) {
											const label = parseFloat(
												this.getLabelForValue( value )
											);
											if ( isNaN( label ) ) {
												return this.getLabelForValue(
													value
												);
											}
											const divided =
												label / xAxisDivisor;
											return Number.isInteger( divided )
												? divided
												: null;
										},
									}
								: {},
						},
						y: {
							title: {
								display: !! yAxisLabel,
								text: yAxisLabel,
							},
							grace: yAxisPadding > 0
								? yAxisPadding
								: undefined,
							min: yAxisMin !== ''
								? parseFloat( yAxisMin )
								: undefined,
							ticks: valueSuffix
								? {
										callback( value ) {
											return `${ value }${ valueSuffix }`;
										},
									}
								: {},
						},
					},
				},
			} );

			return () => {
				if ( chartRef.current ) {
					chartRef.current.destroy();
					chartRef.current = null;
				}
			};
		}, [ chartType, labels, datasets, showLegend, showTooltips, xAxisLabel, yAxisLabel, tension, showPoints, xAxisDivisor, yAxisPadding, yAxisMin, valueSuffix ] );

		// ─── Data mutation helpers ───────────────────────────────

		const updateLabel = useCallback(
			( index, value ) => {
				const next = [ ...labels ];
				next[ index ] = value;
				setAttributes( { labels: next } );
			},
			[ labels, setAttributes ]
		);

		const addColumn = useCallback( () => {
			const next = [ ...labels, `Kolom ${ labels.length + 1 }` ];
			const nextDatasets = datasets.map( ( ds ) => ( {
				...ds,
				data: [ ...ds.data, 0 ],
			} ) );
			setAttributes( { labels: next, datasets: nextDatasets } );
		}, [ labels, datasets, setAttributes ] );

		const removeColumn = useCallback(
			( index ) => {
				const next = labels.filter( ( _, i ) => i !== index );
				const nextDatasets = datasets.map( ( ds ) => ( {
					...ds,
					data: ds.data.filter( ( _, i ) => i !== index ),
				} ) );
				setAttributes( { labels: next, datasets: nextDatasets } );
			},
			[ labels, datasets, setAttributes ]
		);

		const updateDatasetLabel = useCallback(
			( dsIndex, value ) => {
				const next = [ ...datasets ];
				next[ dsIndex ] = { ...next[ dsIndex ], label: value };
				setAttributes( { datasets: next } );
			},
			[ datasets, setAttributes ]
		);

		const updateDatasetValue = useCallback(
			( dsIndex, valIndex, value ) => {
				const next = [ ...datasets ];
				// Pad data array if shorter than labels
				const data = [ ...next[ dsIndex ].data ];
				while ( data.length < labels.length ) {
					data.push( 0 );
				}
				data[ valIndex ] = parseFloat( value ) || 0;
				next[ dsIndex ] = { ...next[ dsIndex ], data };
				setAttributes( { datasets: next } );
			},
			[ datasets, labels, setAttributes ]
		);

		const updateDatasetColor = useCallback(
			( dsIndex, color ) => {
				const next = [ ...datasets ];
				next[ dsIndex ] = { ...next[ dsIndex ], color };
				setAttributes( { datasets: next } );
			},
			[ datasets, setAttributes ]
		);

		const toggleDatasetFill = useCallback(
			( dsIndex, value ) => {
				const next = [ ...datasets ];
				next[ dsIndex ] = { ...next[ dsIndex ], fillToNext: value };
				setAttributes( { datasets: next } );
			},
			[ datasets, setAttributes ]
		);

		const addRow = useCallback( () => {
			const newDs = {
				label: `Serie ${ datasets.length + 1 }`,
				data: labels.map( () => 0 ),
				color: CHART_COLORS[ datasets.length % CHART_COLORS.length ]?.hex || '#14477c',
				customColor: false,
			};
			setAttributes( { datasets: [ ...datasets, newDs ] } );
		}, [ labels, datasets, setAttributes ] );

		const removeRow = useCallback(
			( index ) => {
				setAttributes( {
					datasets: datasets.filter( ( _, i ) => i !== index ),
				} );
			},
			[ datasets, setAttributes ]
		);

		const handlePaste = useCallback( () => {
			const parsed = parseTabularData( pasteText );
			if ( ! parsed ) {
				return;
			}
			setAttributes( {
				labels: parsed.labels,
				datasets: assignDefaultColors( parsed.datasets ),
			} );
			setPasteText( '' );
		}, [ pasteText, setAttributes ] );

		// ─── Empty state ─────────────────────────────────────────

		const hasData = labels.length > 0 && datasets.length > 0;

		// ─── Render ──────────────────────────────────────────────

		return (
			<>
				<InspectorControls>
					<PanelBody
						title={ __( 'Grafiekinstellingen', 'nok-2025-v1' ) }
					>
						<SelectControl
							label={ __( 'Type', 'nok-2025-v1' ) }
							value={ chartType }
							options={ CHART_TYPES }
							onChange={ ( v ) =>
								setAttributes( { chartType: v } )
							}
						/>
						<TextControl
							label={ __( 'Titel', 'nok-2025-v1' ) }
							value={ chartTitle }
							onChange={ ( v ) =>
								setAttributes( { chartTitle: v } )
							}
						/>
						<ToggleControl
							label={ __( 'Legenda tonen', 'nok-2025-v1' ) }
							checked={ showLegend }
							onChange={ ( v ) =>
								setAttributes( { showLegend: v } )
							}
						/>
						<ToggleControl
							label={ __( 'Tooltips tonen', 'nok-2025-v1' ) }
							checked={ showTooltips }
							onChange={ ( v ) =>
								setAttributes( { showTooltips: v } )
							}
						/>
						<ToggleControl
							label={ __( 'Datatabel tonen', 'nok-2025-v1' ) }
							checked={ showDataTable }
							onChange={ ( v ) =>
								setAttributes( { showDataTable: v } )
							}
						/>
						{ chartType === 'line' && (
							<>
								<RangeControl
									label={ __( 'Lijninterpolatie', 'nok-2025-v1' ) }
									value={ tension }
									onChange={ ( v ) =>
										setAttributes( { tension: v } )
									}
									min={ 0 }
									max={ 1 }
									step={ 0.05 }
									marks={ [
										{ value: 0, label: __( 'Recht', 'nok-2025-v1' ) },
										{ value: 0.5, label: __( 'Vloeiend', 'nok-2025-v1' ) },
									] }
								/>
								<ToggleControl
									label={ __( 'Datapunten tonen', 'nok-2025-v1' ) }
									checked={ showPoints }
									onChange={ ( v ) =>
										setAttributes( { showPoints: v } )
									}
								/>
							</>
						) }
						<TextControl
							label={ __( 'X-as label', 'nok-2025-v1' ) }
							value={ xAxisLabel }
							onChange={ ( v ) =>
								setAttributes( { xAxisLabel: v } )
							}
							placeholder={ __( 'Bijv. Aantal jaar na operatie', 'nok-2025-v1' ) }
						/>
						<TextControl
							label={ __( 'Y-as label', 'nok-2025-v1' ) }
							value={ yAxisLabel }
							onChange={ ( v ) =>
								setAttributes( { yAxisLabel: v } )
							}
							placeholder={ __( 'Bijv. Gewichtsverlies (kg)', 'nok-2025-v1' ) }
						/>
						<TextControl
							label={ __( 'Waarde-eenheid', 'nok-2025-v1' ) }
							help={ __(
								'Achtervoegsel bij waarden, bijv. %, kg, km.',
								'nok-2025-v1'
							) }
							value={ valueSuffix }
							onChange={ ( v ) =>
								setAttributes( { valueSuffix: v } )
							}
							placeholder={ __( 'Bijv. %', 'nok-2025-v1' ) }
						/>
						<TextControl
							label={ __( 'Y-as marge', 'nok-2025-v1' ) }
							help={ __(
								'Extra ruimte boven de hoogste waarde. Bijv. 5 of 10.',
								'nok-2025-v1'
							) }
							type="number"
							value={ yAxisPadding }
							onChange={ ( v ) =>
								setAttributes( {
									yAxisPadding:
										parseFloat( v ) || 0,
								} )
							}
							min={ 0 }
						/>
						<TextControl
							label={ __( 'Y-as minimum', 'nok-2025-v1' ) }
							help={ __(
								'Vaste ondergrens voor de Y-as. Leeg = automatisch.',
								'nok-2025-v1'
							) }
							type="number"
							value={ yAxisMin }
							onChange={ ( v ) =>
								setAttributes( { yAxisMin: v } )
							}
							placeholder={ __( 'Auto', 'nok-2025-v1' ) }
						/>
						<TextControl
							label={ __( 'X-as eenheid', 'nok-2025-v1' ) }
							help={ __(
								'Vul 12 in om van maanden naar jaren te gaan, 1000 om van meter naar km te gaan, etc.',
								'nok-2025-v1'
							) }
							type="number"
							value={ xAxisDivisor }
							onChange={ ( v ) =>
								setAttributes( {
									xAxisDivisor:
										parseFloat( v ) || 1,
								} )
							}
							min={ 1 }
						/>
					</PanelBody>

					{ datasets.length > 0 && (
						<PanelBody
							title={ __( 'Dataset kleuren', 'nok-2025-v1' ) }
							initialOpen={ false }
						>
							{ datasets.map( ( ds, di ) => (
								<div
									key={ di }
									style={ {
										marginBottom: '12px',
										borderBottom: '1px solid #ddd',
										paddingBottom: '12px',
									} }
								>
									<Flex align="center" gap={ 2 }>
										<FlexItem>
											<ColorIndicator
												colorValue={ ds.color }
											/>
										</FlexItem>
										<FlexItem isBlock>
											<strong>{ ds.label }</strong>
										</FlexItem>
									</Flex>

									<div
										style={ {
											display: 'flex',
											flexWrap: 'wrap',
											gap: '4px',
											margin: '8px 0',
										} }
									>
										{ CHART_COLORS.map( ( c ) => (
											<button
												key={ c.hex }
												type="button"
												title={ c.label }
												onClick={ () => {
													updateDatasetColor(
														di,
														c.hex
													);
													setColorPickerDataset(
														null
													);
												} }
												style={ {
													width: '24px',
													height: '24px',
													borderRadius: '50%',
													backgroundColor: c.hex,
													border:
														ds.color === c.hex
															? '2px solid #000'
															: '1px solid #ccc',
													cursor: 'pointer',
													padding: 0,
												} }
											/>
										) ) }
									</div>

									<Button
										variant="link"
										onClick={ () =>
											setColorPickerDataset(
												colorPickerDataset === di
													? null
													: di
											)
										}
									>
										{ __(
											'Aangepaste kleur',
											'nok-2025-v1'
										) }
									</Button>

									{ colorPickerDataset === di && (
										<ColorPicker
											color={ ds.color }
											onChangeComplete={ ( c ) =>
												updateDatasetColor(
													di,
													c.hex
												)
											}
											disableAlpha
										/>
									) }

									{ chartType === 'line' && (
										<ToggleControl
											label={ __(
												'Vlak vullen tot volgende dataset',
												'nok-2025-v1'
											) }
											checked={
												!! ds.fillToNext
											}
											onChange={ ( v ) =>
												toggleDatasetFill(
													di,
													v
												)
											}
										/>
									) }
								</div>
							) ) }
						</PanelBody>
					) }

					<PanelBody
						title={ __( 'Importeer data', 'nok-2025-v1' ) }
						initialOpen={ false }
					>
						<TextareaControl
							label={ __(
								'Plak uit Excel of CSV',
								'nok-2025-v1'
							) }
							help={ __(
								'Eerste rij = kolomlabels, volgende rijen = datasets. Eerste kolom mag serienamen bevatten.',
								'nok-2025-v1'
							) }
							value={ pasteText }
							onChange={ setPasteText }
							rows={ 6 }
						/>
						<Button
							variant="secondary"
							onClick={ handlePaste }
							disabled={ ! pasteText.trim() }
						>
							{ __( 'Importeren', 'nok-2025-v1' ) }
						</Button>
					</PanelBody>
				</InspectorControls>

				<div { ...blockProps }>
					{ ! hasData ? (
						<div
							style={ {
								textAlign: 'center',
								padding: '40px 20px',
								border: '1px dashed #ccc',
								borderRadius: '4px',
							} }
						>
							<p>
								{ __(
									'Voeg data toe om een grafiek te maken.',
									'nok-2025-v1'
								) }
							</p>
							<Button
								variant="primary"
								onClick={ () => {
									setAttributes( {
										labels: [
											'Kolom 1',
											'Kolom 2',
											'Kolom 3',
										],
										datasets: assignDefaultColors( [
											{
												label: 'Serie 1',
												data: [ 0, 0, 0 ],
												color: '',
												customColor: false,
											},
										] ),
									} );
								} }
							>
								{ __( 'Data toevoegen', 'nok-2025-v1' ) }
							</Button>
						</div>
					) : (
						<>
							{ /* Data grid */ }
							<table
								className="nok-chart-editor__grid"
								style={ {
									width: '100%',
									borderCollapse: 'collapse',
									marginBottom: '16px',
									fontSize: '13px',
								} }
							>
								<thead>
									<tr>
										<th
											style={ {
												width: '120px',
												padding: '4px',
											} }
										/>
										{ labels.map( ( label, li ) => (
											<th
												key={ li }
												style={ { padding: '4px' } }
											>
												<Flex
													align="center"
													gap={ 1 }
												>
													<FlexItem isBlock>
														<input
															type="text"
															value={ label }
															onChange={ (
																e
															) =>
																updateLabel(
																	li,
																	e.target
																		.value
																)
															}
															style={ {
																width: '100%',
																padding:
																	'4px 6px',
																border: '1px solid #ddd',
																borderRadius:
																	'2px',
																fontSize:
																	'13px',
																fontWeight:
																	'600',
															} }
														/>
													</FlexItem>
													<FlexItem>
														<button
															type="button"
															onClick={ () =>
																removeColumn(
																	li
																)
															}
															title={ __(
																'Kolom verwijderen',
																'nok-2025-v1'
															) }
															style={ {
																background:
																	'none',
																border: 'none',
																cursor: 'pointer',
																color: '#cc1818',
																fontSize:
																	'14px',
																lineHeight: 1,
																padding:
																	'2px',
															} }
														>
															&times;
														</button>
													</FlexItem>
												</Flex>
											</th>
										) ) }
										<th style={ { width: '40px' } }>
											<Button
												variant="secondary"
												isSmall
												onClick={ addColumn }
												title={ __(
													'Kolom toevoegen',
													'nok-2025-v1'
												) }
											>
												+
											</Button>
										</th>
									</tr>
								</thead>
								<tbody>
									{ datasets.map( ( ds, di ) => (
										<tr key={ di }>
											<td
												style={ {
													padding: '4px',
												} }
											>
												<Flex
													align="center"
													gap={ 1 }
												>
													<FlexItem>
														<ColorIndicator
															colorValue={
																ds.color
															}
														/>
													</FlexItem>
													<FlexItem isBlock>
														<input
															type="text"
															value={
																ds.label
															}
															onChange={ (
																e
															) =>
																updateDatasetLabel(
																	di,
																	e.target
																		.value
																)
															}
															style={ {
																width: '100%',
																padding:
																	'4px 6px',
																border: '1px solid #ddd',
																borderRadius:
																	'2px',
																fontSize:
																	'13px',
															} }
														/>
													</FlexItem>
													<FlexItem>
														<button
															type="button"
															onClick={ () =>
																removeRow(
																	di
																)
															}
															title={ __(
																'Dataset verwijderen',
																'nok-2025-v1'
															) }
															style={ {
																background:
																	'none',
																border: 'none',
																cursor: 'pointer',
																color: '#cc1818',
																fontSize:
																	'14px',
																lineHeight: 1,
																padding:
																	'2px',
															} }
														>
															&times;
														</button>
													</FlexItem>
												</Flex>
											</td>
											{ labels.map( ( _, vi ) => (
												<td
													key={ vi }
													style={ {
														padding: '4px',
													} }
												>
													<input
														type="number"
														value={ ds.data[ vi ] ?? 0 }
														onChange={ ( e ) =>
															updateDatasetValue(
																di,
																vi,
																e.target
																	.value
															)
														}
														style={ {
															width: '100%',
															padding:
																'4px 6px',
															border: '1px solid #ddd',
															borderRadius:
																'2px',
															fontSize:
																'13px',
															textAlign:
																'right',
														} }
													/>
												</td>
											) ) }
											<td />
										</tr>
									) ) }
									<tr>
										<td
											colSpan={
												labels.length + 2
											}
											style={ { padding: '4px' } }
										>
											<Button
												variant="secondary"
												isSmall
												onClick={ addRow }
											>
												{ __(
													'+ Dataset',
													'nok-2025-v1'
												) }
											</Button>
										</td>
									</tr>
								</tbody>
							</table>

							{ /* Chart preview */ }
							<div
								style={ {
									position: 'relative',
									height: chartType === 'bar'
										? 'clamp(330px, 50vw, 500px)'
										: 'clamp(280px, 50vw, 500px)',
								} }
							>
								<canvas ref={ canvasRef } />
							</div>

							{ showDataTable && (
								<table
									className="nok-chart__data nok-chart__data--visible"
									style={ {
										width: '100%',
										borderCollapse: 'collapse',
										marginTop: '8px',
										fontSize: '13px',
									} }
								>
									<tbody>
										{ datasets.map( ( ds, di ) => (
											<tr key={ di }>
												<th
													scope="row"
													style={ {
														textAlign: 'left',
														whiteSpace: 'nowrap',
														fontWeight: 600,
														padding: '4px 8px',
														border: '1px solid #ddd',
													} }
												>
													<span
														style={ {
															display:
																'inline-block',
															width: '0.75em',
															height: '0.75em',
															marginRight:
																'0.375em',
															verticalAlign:
																'middle',
															backgroundColor:
																ds.color ||
																'#14477c',
														} }
													/>
													{ ds.label }
												</th>
												{ labels.map(
													( _, vi ) => (
														<td
															key={ vi }
															style={ {
																textAlign:
																	'center',
																padding:
																	'4px 8px',
																border: '1px solid #ddd',
															} }
														>
															{ ds.data[ vi ] != null
																? `${ ds.data[ vi ] }${ valueSuffix }`
																: '' }
														</td>
													)
												) }
											</tr>
										) ) }
									</tbody>
								</table>
							) }
						</>
					) }
				</div>
			</>
		);
	},

	save: () => null,
} );
