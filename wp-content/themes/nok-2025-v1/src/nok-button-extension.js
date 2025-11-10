/**
 * Button Block Extension
 *
 * Extends core/button block with NOK-specific features:
 * - Icon selector (ui_ icons only)
 * - Style presets (5 color schemes)
 * - Optional icon color override
 * - Icon position (before/after text)
 * - Fill-mobile toggle
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import IconSelector from './components/IconSelector';

// Style presets
const STYLE_PRESETS = [
    { label: 'Donkerblauw (standaard)', value: 'darkblue' },
    { label: 'Donkerste blauw', value: 'darkestblue' },
    { label: 'Wit', value: 'white' },
    { label: 'Lichtblauw', value: 'lightblue' },
    { label: 'Geel', value: 'yellow' },
];

// Icon color options (same as style presets)
const ICON_COLOR_OPTIONS = [
    { label: 'Overnemen van knop', value: '' },
    { label: 'Standaard (geel)', value: 'yellow' },
    ...STYLE_PRESETS.filter(preset => preset.value !== 'yellow')
];

// Color values for editor preview - using CSS custom properties from color_tests-v2.scss
const COLOR_VALUES = {
    darkblue: 'var(--nok-darkblue)',
    darkestblue: 'var(--nok-darkestblue)',
    white: 'var(--nok-white)',
    lightblue: 'var(--nok-lightblue)',
    yellow: 'var(--nok-yellow)',
};

/**
 * Add custom attributes to core/button block
 */
function addButtonAttributes(settings, name) {
    if (name !== 'core/button') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            nokIcon: {
                type: 'string',
                default: '',
            },
            nokStyle: {
                type: 'string',
                default: 'darkblue',
            },
            nokIconColor: {
                type: 'string',
                default: '',
            },
            nokIconPosition: {
                type: 'string',
                default: 'after',
            },
            fillMobile: {
                type: 'boolean',
                default: false,
            },
        },
    };
}

addFilter(
    'blocks.registerBlockType',
    'nok2025/button-attributes',
    addButtonAttributes
);

/**
 * Add custom controls to button block inspector
 */
const withButtonControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name, attributes, setAttributes } = props;

        if (name !== 'core/button') {
            return <BlockEdit {...props} />;
        }

        const {
            nokIcon = '',
            nokStyle = 'darkblue',
            nokIconColor = '',
            nokIconPosition = 'after',
            fillMobile = false
        } = attributes;

        // Get icons from localized data
        const availableIcons = (window.nokButtonIcons && window.nokButtonIcons.ui) || {};

        return (
            <Fragment>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody title={__('NOK Button Instellingen', 'nok2025')} initialOpen={true}>
                        <IconSelector
                            value={nokIcon}
                            onChange={(value) => setAttributes({ nokIcon: value })}
                            icons={{ ui: availableIcons }}
                        />

                        {nokIcon && (
                            <SelectControl
                                label={__('Icoon positie', 'nok2025')}
                                value={nokIconPosition}
                                options={[
                                    { label: 'Voor tekst', value: 'before' },
                                    { label: 'Na tekst', value: 'after' },
                                ]}
                                onChange={(value) => setAttributes({ nokIconPosition: value })}
                            />
                        )}

                        <SelectControl
                            label={__('Knop stijl', 'nok2025')}
                            value={nokStyle}
                            options={STYLE_PRESETS}
                            onChange={(value) => setAttributes({ nokStyle: value })}
                        />

                        {nokIcon && (
                            <SelectControl
                                label={__('Icoon kleur', 'nok2025')}
                                value={nokIconColor}
                                options={ICON_COLOR_OPTIONS}
                                onChange={(value) => setAttributes({ nokIconColor: value })}
                                help={__('Laat leeg om de tekstkleur van de knop over te nemen', 'nok2025')}
                            />
                        )}

                        <ToggleControl
                            label={__('Volledige breedte op mobiel', 'nok2025')}
                            checked={fillMobile}
                            onChange={(value) => setAttributes({ fillMobile: value })}
                            help={__('Knop vult de volledige breedte op mobiele apparaten', 'nok2025')}
                        />
                    </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'withButtonControls');

addFilter(
    'editor.BlockEdit',
    'nok2025/button-controls',
    withButtonControls
);

/**
 * Apply NOK button classes to wrapper for editor styling ONLY
 * (PHP handles frontend rendering)
 */
const applyButtonClassesEditor = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (name !== 'core/button') {
            return <BlockListBlock {...props} />;
        }

        const {
            nokStyle = 'darkblue',
            fillMobile = false
        } = attributes;

        const classes = ['nok-button', 'nok-text-contrast', `nok-bg-${nokStyle}`];

        if (fillMobile) {
            classes.push('fill-mobile');
        }

        const combinedClasses = classes.join(' ');

        return (
            <BlockListBlock
                {...props}
                className={combinedClasses ?
                    `${props.className || ''} ${combinedClasses}`.trim() :
                    props.className
                }
            />
        );
    };
}, 'applyButtonClassesEditor');

addFilter(
    'editor.BlockListBlock',
    'nok2025/button-classes-editor',
    applyButtonClassesEditor
);

/**
 * Inject icon into button in editor preview
 */
const withButtonIconPreview = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (name !== 'core/button') {
            return <BlockListBlock {...props} />;
        }

        const {
            nokIcon = '',
            nokIconColor = '',
            nokIconPosition = 'after'
        } = attributes;

        // Render block normally first
        const block = <BlockListBlock {...props} />;

        // No icon selected - return normal block
        if (!nokIcon) {
            return block;
        }

        // Get icon SVG from localized data
        const availableIcons = (window.nokButtonIcons && window.nokButtonIcons.ui) || {};
        const iconSvg = availableIcons[nokIcon];

        if (!iconSvg) {
            return block;
        }

        // Determine icon color
        const iconColorValue = nokIconColor ? COLOR_VALUES[nokIconColor] : 'currentColor';

        return (
            <>
                <style>{`
                    [data-block="${props.clientId}"] .wp-block-button__link::${nokIconPosition === 'before' ? 'before' : 'after'} {
                        content: '';
                        display: inline-block;
                        width: 1em;
                        height: 1em;
                        margin: ${nokIconPosition === 'before' ? '0 0.5em 0 0' : '0 0 0 0.5em'};
                        background: ${iconColorValue};
                        mask-image: url('data:image/svg+xml;utf8,${encodeURIComponent(iconSvg)}');
                        mask-size: contain;
                        mask-repeat: no-repeat;
                        mask-position: center;
                        -webkit-mask-image: url('data:image/svg+xml;utf8,${encodeURIComponent(iconSvg)}');
                        -webkit-mask-size: contain;
                        -webkit-mask-repeat: no-repeat;
                        -webkit-mask-position: center;
                    }
                `}</style>
                {block}
            </>
        );
    };
}, 'withButtonIconPreview');

addFilter(
    'editor.BlockListBlock',
    'nok2025/button-icon-preview',
    withButtonIconPreview,
    20
);