/**
 * NOK Chart — DOMule module
 *
 * Lazy-loaded via data-requires="./nok-chart.mjs" data-require-lazy="true".
 * Injects the Chart.js UMD script, reads JSON config from the DOM, and
 * creates Chart instances on <canvas> elements.
 */

export const NAME = 'chart';

/** @type {Promise<typeof Chart>|null} Singleton Chart.js load promise */
let chartPromise = null;

/**
 * Load Chart.js UMD if not already loaded.
 * Resolves the vendor path relative to this module's location.
 *
 * @return {Promise<typeof Chart>}
 */
function loadChartJS() {
	if ( window.Chart ) {
		return Promise.resolve( window.Chart );
	}

	if ( ! chartPromise ) {
		chartPromise = new Promise( ( resolve, reject ) => {
			const script = document.createElement( 'script' );
			script.src = new URL( 'lib/chart.umd.js', import.meta.url ).href;
			script.onload = () => resolve( window.Chart );
			script.onerror = () => reject( new Error( `[${ NAME }] Failed to load Chart.js` ) );
			document.head.appendChild( script );
		} );
	}

	return chartPromise;
}

/** @type {Map<HTMLElement, Chart>} Active chart instances for cleanup */
const instances = new Map();

/**
 * Initialise chart instances for all matched elements.
 *
 * @param {HTMLElement[]} elements Container elements with data-requires
 * @param {object|null}   context  DOMule context ({isLazy, triggeringElement} or null)
 * @return {Promise<string>} Status message
 */
export async function init( elements, context ) {
	const ChartJS = await loadChartJS();
	let count = 0;

	elements.forEach( ( figure ) => {
		const canvas = figure.querySelector( '.nok-chart__canvas' );
		const configEl = figure.querySelector( '.nok-chart__config' );

		if ( ! canvas || ! configEl ) {
			return;
		}

		try {
			const config = JSON.parse( configEl.textContent );

			// Apply runtime options that can't be serialised as JSON (callbacks)
			const divisor = config.options?.xAxisDivisor;
			const suffix = config.options?.valueSuffix || '';
			delete config.options.xAxisDivisor;
			delete config.options.valueSuffix;

			// X-axis divisor: show only whole-number divided ticks
			if ( divisor && divisor > 1 ) {
				const xScale = config.options.scales?.x;
				if ( xScale ) {
					xScale.ticks = {
						...( xScale.ticks || {} ),
						autoSkip: false,
						callback( value ) {
							const label = parseFloat( this.getLabelForValue( value ) );
							if ( isNaN( label ) ) return this.getLabelForValue( value );
							const divided = label / divisor;
							return Number.isInteger( divided ) ? divided : null;
						},
					};
				}
			}

			// Value suffix: append to y-axis ticks and tooltip labels
			if ( suffix ) {
				const yScale = config.options.scales?.y;
				if ( yScale ) {
					yScale.ticks = {
						...( yScale.ticks || {} ),
						callback( value ) {
							return `${ value }${ suffix }`;
						},
					};
				}
				const tooltip = config.options.plugins?.tooltip;
				if ( tooltip ) {
					tooltip.callbacks = {
						...( tooltip.callbacks || {} ),
						label( ctx ) {
							return `${ ctx.dataset.label }: ${ ctx.parsed.y }${ suffix }`;
						},
					};
				}
			}

			const chart = new ChartJS( canvas, config );
			instances.set( figure, chart );
			count++;
		} catch ( e ) {
			console.error( `[${ NAME }]`, 'Failed to initialise chart:', e );
		}
	} );

	return `Initialised ${ count } chart(s)`;
}

/**
 * Clean up specific chart instances (called by DOMule on element removal).
 *
 * @param {HTMLElement[]} elements
 */
export function cleanup( elements ) {
	if ( ! elements ) {
		return;
	}
	elements.forEach( ( el ) => {
		const chart = instances.get( el );
		if ( chart ) {
			chart.destroy();
			instances.delete( el );
		}
	} );
}

/**
 * Destroy all chart instances (full SPA cleanup).
 */
export function destroy() {
	instances.forEach( ( chart ) => chart.destroy() );
	instances.clear();
}
