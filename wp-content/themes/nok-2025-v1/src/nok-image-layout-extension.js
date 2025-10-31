/**
 * Image Block Layout Extension
 *
 * Extends core/image block with custom layout options via:
 * - Custom attribute for layout choice
 * - Toolbar buttons for layout selection
 * - Class application for editor and frontend
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { ToolbarGroup, DropdownMenu, MenuGroup, MenuItem, PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Get variants from localized data
const LAYOUT_VARIANTS = window.nokImageLayouts?.variants || [
    { name: 'default', label: 'Default', icon: 'format-image' }
];

/**
 * Add customLayout attribute to core/image block
 */
function addLayoutAttribute(settings, name) {
    if (name !== 'core/image') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            customLayout: {
                type: 'string',
                default: 'default',
            },
			enableBlurBackground: {
				type: 'boolean',
				default: false,
			},
        },
    };
}

addFilter(
    'blocks.registerBlockType',
    'nok2025/image-layout-attribute',
    addLayoutAttribute
);

/**
 * Add layout dropdown control to image block
 */
const withLayoutControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name, attributes, setAttributes } = props;

        if (name !== 'core/image') {
            return <BlockEdit {...props} />;
        }

		const { customLayout = 'default', enableBlurBackground = false } = attributes;
		const currentLayout = LAYOUT_VARIANTS.find(v => v.name === customLayout) || LAYOUT_VARIANTS[0];

        return (
            <Fragment>
                <BlockControls group="block">
                    <ToolbarGroup>
                        <DropdownMenu
                            icon={currentLayout.icon}
                            label={__('Image Layout', 'nok2025')}
                        >
                            {({ onClose }) => (
                                <MenuGroup>
                                    {LAYOUT_VARIANTS.map((variant) => (
                                        <MenuItem
                                            key={variant.name}
                                            icon={variant.icon}
                                            isSelected={customLayout === variant.name}
                                            onClick={() => {
                                                setAttributes({ customLayout: variant.name });
                                                onClose();
                                            }}
                                        >
                                            {variant.label}
                                        </MenuItem>
                                    ))}
                                </MenuGroup>
                            )}
                        </DropdownMenu>
                    </ToolbarGroup>
                </BlockControls>
				<InspectorControls group="settings">
					<PanelBody title={__('Afbeelding instellingen', 'nok2025')}>
						<ToggleControl
							label={__('Centreren en uitvullen met vervaagde afbeelding', 'nok2025')}
							checked={enableBlurBackground}
							onChange={(value) => setAttributes({ enableBlurBackground: value })}
							help={__('Voegt een vervaagde achtergrond toe zodat de afbeelding uitvult zonder bij te snijden. Heeft effect op verticale (portret) afbeeldingen die in brede kaders moeten.', 'nok2025')}
						/>
					</PanelBody>
				</InspectorControls>
                <BlockEdit {...props} />
            </Fragment>
        );
    };
}, 'withLayoutControls');

addFilter(
    'editor.BlockEdit',
    'nok2025/image-layout-controls',
    withLayoutControls
);

/**
 * Apply layout class to block wrapper in editor
 */
const applyLayoutClassEditor = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (name !== 'core/image') {
            return <BlockListBlock {...props} />;
        }

        const { customLayout = 'default', enableBlurBackground = false } = attributes;
        let classes = [];

        if (customLayout !== 'default') {
            classes.push(`nok-image-${customLayout}`);
        }

        if (enableBlurBackground) {
            classes.push('nok-image-cover-blur');
        }

        const layoutClass = classes.join(' ');

        return (
            <BlockListBlock
                {...props}
                className={layoutClass ? `${props.className || ''} ${layoutClass}`.trim() : props.className}
            />
        );
    };
}, 'applyLayoutClassEditor');

addFilter(
    'editor.BlockListBlock',
    'nok2025/image-layout-class-editor',
    applyLayoutClassEditor
);

/**
 * Apply blur background style in editor
 */
const withEditorBlurStyle = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { name, attributes } = props;

        if (name !== 'core/image' || !attributes.enableBlurBackground) {
            return <BlockListBlock {...props} />;
        }

        const imageUrl = attributes.url;

        return (
            <div style={{ '--blur-bg-image': imageUrl ? `url(${imageUrl})` : 'none' }}>
                <BlockListBlock {...props} />
            </div>
        );
    };
}, 'withEditorBlurStyle');

addFilter(
    'editor.BlockListBlock',
    'nok2025/image-blur-editor-style',
    withEditorBlurStyle,
    5
);

/**
 * Apply layout class to block wrapper on frontend
 */
function applyLayoutClassFrontend(extraProps, blockType, attributes) {
    if (blockType.name !== 'core/image') {
        return extraProps;
    }

	const { customLayout = 'default', enableBlurBackground = false } = attributes;
	let classes = [];

    if (customLayout !== 'default') {
		classes.push(`nok-image-${customLayout}`);
	}

	if (enableBlurBackground) {
		classes.push('nok-image-cover-blur');
	}

	if (classes.length > 0) {
		const layoutClass = classes.join(' ');
        extraProps.className = extraProps.className
            ? `${extraProps.className} ${layoutClass}`
            : layoutClass;
    }

    return extraProps;
}

addFilter(
    'blocks.getSaveContent.extraProps',
    'nok2025/image-layout-class-frontend',
    applyLayoutClassFrontend
);