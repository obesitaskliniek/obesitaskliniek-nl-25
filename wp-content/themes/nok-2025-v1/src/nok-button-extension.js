/**
 * Button Block Extension
 *
 * Extends core/button block with NOK-specific features:
 * - Icon selector (ui_ icons only)
 * - Background color via ColorSelector (button-backgrounds palette)
 * - Optional text color override via ColorSelector (text palette)
 * - Icon color via ColorSelector (icon-colors palette)
 * - Icon position (before/after text)
 * - Fill-mobile toggle
 *
 * Backward compatible: legacy nokStyle/nokIconColor (simple color names)
 * still render via PHP fallback. New attributes store full CSS class strings.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import IconSelector from './components/IconSelector';
import ColorSelector from './components/ColorSelector';

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
            // Legacy: simple color name (only populated on pre-migration blocks)
            nokStyle: {
                type: 'string',
                default: '',
            },
            // Full class string from button-backgrounds palette
            nokBgColor: {
                type: 'string',
                default: 'nok-bg-darkblue nok-text-contrast',
            },
            // New: explicit text color override from text palette
            nokTextColor: {
                type: 'string',
                default: '',
            },
            // Icon color — stores full class (new) or simple name (legacy)
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
            nokBgColor = '',
            nokTextColor = '',
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

                        <label className="components-base-control__label" style={{ display: 'block', marginBottom: '4px' }}>
                            {__('Knop kleur', 'nok2025')}
                        </label>
                        <ColorSelector
                            value={nokBgColor}
                            onChange={(value) => setAttributes({ nokBgColor: value })}
                            palette="button-backgrounds"
                        />

                        <label className="components-base-control__label" style={{ display: 'block', marginBottom: '4px' }}>
                            {__('Tekst kleur (optioneel)', 'nok2025')}
                        </label>
                        <ColorSelector
                            value={nokTextColor}
                            onChange={(value) => setAttributes({ nokTextColor: value })}
                            palette="text"
                        />

                        {nokIcon && (
                            <>
                                <label className="components-base-control__label" style={{ display: 'block', marginBottom: '4px' }}>
                                    {__('Icoon kleur', 'nok2025')}
                                </label>
                                <ColorSelector
                                    value={nokIconColor}
                                    onChange={(value) => setAttributes({ nokIconColor: value })}
                                    palette="icon-colors"
                                />
                            </>
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
 *
 * Priority: nokBgColor (new palette) > nokStyle (legacy)
 */
const applyButtonClassesEditor = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (name !== 'core/button') {
            return <BlockListBlock {...props} />;
        }

        const {
            nokBgColor = '',
            nokTextColor = '',
            nokStyle = 'darkblue',
            fillMobile = false
        } = attributes;

        const classes = ['nok-button'];

        if (nokBgColor) {
            // New format: full class string from palette (e.g., "nok-bg-darkblue nok-text-contrast")
            classes.push(nokBgColor);
        } else {
            // Legacy fallback
            classes.push(`nok-bg-${nokStyle}`, 'nok-text-contrast');
        }

        // Explicit text color override (appended last to win specificity)
        if (nokTextColor) {
            classes.push(nokTextColor);
        }

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

        // Determine icon color:
        // - New format (nok-text-yellow): extract color name, use CSS var
        // - Legacy format (yellow): use CSS var directly
        // - Empty: use currentColor
        let iconColorValue = 'currentColor';
        if (nokIconColor) {
            const colorName = nokIconColor.startsWith('nok-text-')
                ? nokIconColor.replace('nok-text-', '')
                : nokIconColor;
            iconColorValue = `var(--nok-${colorName})`;
        }

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
