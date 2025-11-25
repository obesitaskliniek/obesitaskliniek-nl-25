import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/general-nok-section';

const ALLOWED_BLOCKS = [
    'core/paragraph',
    'core/heading',
    'core/list',
    'core/image',
    'core/gallery',
    'core/quote',
    'core/table',
    'core/columns',
    'core/group',
    'core/spacer',
    'core/separator',
    'core/buttons',
    'core/button',
    'core/html',
    'core/shortcode',
];

// Color options matching theme colors
const COLOR_OPTIONS = [
    { label: __('Wit', textDomain), value: 'white' },
    { label: __('Lichtblauw', textDomain), value: 'lightblue' },
    { label: __('Blauw', textDomain), value: 'blue' },
    { label: __('Donkerblauw', textDomain), value: 'darkblue' },
    { label: __('Donkerder blauw', textDomain), value: 'darkerblue' },
    { label: __('Donkerste blauw', textDomain), value: 'darkestblue' },
    { label: __('Transparant', textDomain), value: 'transparent' },
];

// Text color options
const TEXT_COLOR_OPTIONS = [
    { label: __('Donkerder blauw', textDomain), value: 'darkerblue' },
    { label: __('Wit', textDomain), value: 'white' },
    { label: __('Contrast', textDomain), value: 'contrast' },
    { label: __('Lichtblauw', textDomain), value: 'lightblue' },
];

// Layout width options
const LAYOUT_OPTIONS = [
    { label: __('1 kolom (standaard)', textDomain), value: '1-column' },
    { label: __('2 kolommen', textDomain), value: '2-column' },
    { label: __('3 kolommen', textDomain), value: '3-column' },
];

registerBlockType(blockName, {
    edit: ({ attributes, setAttributes }) => {
        const { backgroundColor, textColor, layoutWidth, enablePullUp, enableNoAos } = attributes;

        const blockProps = useBlockProps({
            className: 'nok-general-section-editor',
        });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Sectie instellingen', textDomain)} initialOpen={true}>
                        <SelectControl
                            label={__('Achtergrondkleur', textDomain)}
                            value={backgroundColor}
                            options={COLOR_OPTIONS}
                            onChange={(value) => setAttributes({ backgroundColor: value })}
                        />

                        <SelectControl
                            label={__('Tekstkleur', textDomain)}
                            value={textColor}
                            options={TEXT_COLOR_OPTIONS}
                            onChange={(value) => setAttributes({ textColor: value })}
                        />

                        <SelectControl
                            label={__('Layout breedte', textDomain)}
                            value={layoutWidth}
                            options={LAYOUT_OPTIONS}
                            onChange={(value) => setAttributes({ layoutWidth: value })}
                            help={__('Bepaalt het aantal kolommen in de layout grid', textDomain)}
                        />
                    </PanelBody>

                    <PanelBody title={__('Geavanceerde opties', textDomain)} initialOpen={false}>
                        <ToggleControl
                            label={__('Pull-up effect', textDomain)}
                            help={__('Trekt de sectie omhoog over de vorige sectie', textDomain)}
                            checked={enablePullUp}
                            onChange={(value) => setAttributes({ enablePullUp: value })}
                        />

                        <ToggleControl
                            label={__('Disable animaties (no-aos)', textDomain)}
                            help={__('Schakelt scroll-animaties uit voor deze sectie', textDomain)}
                            checked={enableNoAos}
                            onChange={(value) => setAttributes({ enableNoAos: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="nok-general-section-editor__label" style={{
                        padding: '20px',
                        background: '#f0f0f1',
                        border: '1px solid #c3c4c7',
                        borderRadius: '4px',
                        textAlign: 'center'
                    }}>
                        <strong>{__('NOK Generieke sectie', textDomain)}</strong>
                        <div style={{ fontSize: '12px', marginTop: '8px', color: '#666' }}>
                            {__('Achtergrond:', textDomain)} {COLOR_OPTIONS.find(opt => opt.value === backgroundColor)?.label || backgroundColor} |&nbsp;
                            {__('Tekst:', textDomain)} {TEXT_COLOR_OPTIONS.find(opt => opt.value === textColor)?.label || textColor} |&nbsp;
                            {__('Layout:', textDomain)} {LAYOUT_OPTIONS.find(opt => opt.value === layoutWidth)?.label || layoutWidth}
                        </div>
                    </div>
                    <InnerBlocks
                        allowedBlocks={ALLOWED_BLOCKS}
                        templateLock={false}
                    />
                </div>
            </>
        );
    },

    save: () => {
        return <InnerBlocks.Content />;
    },
});
