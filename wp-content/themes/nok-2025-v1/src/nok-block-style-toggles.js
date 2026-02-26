/**
 * Block Style Toggles Extension
 *
 * Adds toggle controls to common blocks for applying NOK utility classes:
 * - Rounded borders (nok-rounded-border)
 * - Subtle shadow (nok-subtle-shadow)
 *
 * Follows the same three-filter pattern as nok-image-layout-extension.js.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Blocks that support style toggles.
 *
 * @type {Set<string>}
 */
const SUPPORTED_BLOCKS = new Set([
	'core/paragraph',
	'core/heading',
	'core/group',
	'core/columns',
	'core/column',
	'core/list',
	'core/image',
	'core/cover',
	'core/media-text',
	'core/table',
]);

/**
 * Toggle definitions — each entry becomes a ToggleControl.
 * Add new toggles here.
 *
 * @type {Array<{attribute: string, className: string, label: string, help: string}>}
 */
const TOGGLES = [
	{
		attribute:  'nokRoundedBorder',
		className:  'nok-rounded-border',
		label:      __( 'Rounded borders', 'nok2025' ),
		help:       __( 'Voegt afgeronde hoeken toe.', 'nok2025' ),
	},
	{
		attribute:  'nokSubtleShadow',
		className:  'nok-subtle-shadow',
		label:      __( 'Shadow', 'nok2025' ),
		help:       __( 'Voegt een subtiele schaduw toe.', 'nok2025' ),
	},
];

/**
 * 1. Register boolean attributes on supported blocks.
 */
function addStyleToggleAttributes( settings, name ) {
	if ( ! SUPPORTED_BLOCKS.has( name ) ) {
		return settings;
	}

	const extraAttributes = {};
	for ( const toggle of TOGGLES ) {
		extraAttributes[ toggle.attribute ] = {
			type: 'boolean',
			default: false,
		};
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			...extraAttributes,
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'nok2025/block-style-toggle-attributes',
	addStyleToggleAttributes
);

/**
 * 2. Add InspectorControls panel with toggles.
 */
const withStyleToggleControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		if ( ! SUPPORTED_BLOCKS.has( name ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'NOK Stijl', 'nok2025' ) } initialOpen={ false }>
						{ TOGGLES.map( ( toggle ) => (
							<ToggleControl
								key={ toggle.attribute }
								label={ toggle.label }
								help={ toggle.help }
								checked={ !! attributes[ toggle.attribute ] }
								onChange={ ( value ) => setAttributes( { [ toggle.attribute ]: value } ) }
							/>
						) ) }
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withStyleToggleControls' );

addFilter(
	'editor.BlockEdit',
	'nok2025/block-style-toggle-controls',
	withStyleToggleControls
);

/**
 * 3. Apply utility classes in the editor canvas.
 */
const applyStyleToggleClassesEditor = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const { name, attributes } = props;

		if ( ! SUPPORTED_BLOCKS.has( name ) ) {
			return <BlockListBlock { ...props } />;
		}

		const classes = [];
		for ( const toggle of TOGGLES ) {
			if ( attributes[ toggle.attribute ] ) {
				classes.push( toggle.className );
			}
		}

		if ( classes.length === 0 ) {
			return <BlockListBlock { ...props } />;
		}

		const extraClass = classes.join( ' ' );

		return (
			<BlockListBlock
				{ ...props }
				className={ `${ props.className || '' } ${ extraClass }`.trim() }
			/>
		);
	};
}, 'applyStyleToggleClassesEditor' );

addFilter(
	'editor.BlockListBlock',
	'nok2025/block-style-toggle-classes-editor',
	applyStyleToggleClassesEditor
);

/**
 * 4. Apply utility classes to saved block HTML (frontend).
 */
function applyStyleToggleClassesFrontend( extraProps, blockType, attributes ) {
	if ( ! SUPPORTED_BLOCKS.has( blockType.name ) ) {
		return extraProps;
	}

	const classes = [];
	for ( const toggle of TOGGLES ) {
		if ( attributes[ toggle.attribute ] ) {
			classes.push( toggle.className );
		}
	}

	if ( classes.length > 0 ) {
		const toggleClass = classes.join( ' ' );
		extraProps.className = extraProps.className
			? `${ extraProps.className } ${ toggleClass }`
			: toggleClass;
	}

	return extraProps;
}

addFilter(
	'blocks.getSaveContent.extraProps',
	'nok2025/block-style-toggle-classes-frontend',
	applyStyleToggleClassesFrontend
);
