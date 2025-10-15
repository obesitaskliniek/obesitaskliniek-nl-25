import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, BlockControls, useBlockProps} from '@wordpress/block-editor';
import {SelectControl, PanelBody, Button, Popover} from '@wordpress/components';
import {useSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {useRef, useState, useEffect} from '@wordpress/element';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-page-part';

const CustomPagePartSelector = ({value, options, onChange}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [hoveredOption, setHoveredOption] = useState(null);
    const buttonRef = useRef();
    const selectedOption = options.find(opt => opt.value === value);

    return (
        <div style={{position: 'relative'}}>
            <Button
                ref={buttonRef}
                variant="secondary"
                onClick={() => setIsOpen(!isOpen)}
                style={{
                    width: '100%',
                    textAlign: 'left',
                    padding: '8px 12px',
                    borderBottom: '1px solid #f0f0f1',
                    borderRadius: '0',
                    justifyContent: 'end',
                    color: 'inherit'
                }}
            >
                <span dangerouslySetInnerHTML={{__html: selectedOption?.label || 'Select Page Part...'}}
                      style={{
                          flexBasis: '100%'
                      }}/>
                {
                    selectedOption ?
                        <span dangerouslySetInnerHTML={{__html: `${selectedOption.template}`}}
                              style={{
                                  padding: '2px 6px',
                                  backgroundColor: '#f0f0f1',
                                  borderRadius: '6px',
                                  fontSize: '1em',
                                  color: '#555'
                              }}/> : null
                }
                <span>▼</span>
            </Button>

            {isOpen && (
                <div
                    style={{
                        position: 'absolute',
                        top: '100%',
                        left: '0',
                        right: '0',
                        zIndex: 999999,
                        backgroundColor: 'white',
                        border: '1px solid #ccd0d4',
                        borderRadius: '4px',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.05)',
                        maxHeight: '300px',
                        overflow: 'hidden'
                    }}
                >
                    <div style={{
                        minWidth: '100%',
                        maxHeight: '300px',
                        backgroundColor: 'white',
                        border: '1px solid #ccd0d4',
                        borderRadius: '4px',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.05)',
                        overflow: 'hidden auto'
                    }}>
                        {options.map(option => (
                            <Button
                                key={option.value}
                                variant="tertiary"
                                onMouseEnter={() => setHoveredOption(option.value)}
                                onMouseLeave={() => setHoveredOption(null)}
                                onClick={() => {
                                    onChange(option.value);
                                    setIsOpen(false);
                                }}
                                style={{
                                    width: '100%',
                                    textAlign: 'left',
                                    padding: '8px 12px',
                                    borderBottom: '1px solid #f0f0f1',
                                    borderRadius: '0',
                                    justifyContent: 'space-between',
                                    backgroundColor: option.value === value ? 'var(--wp-admin-theme-color)' : (hoveredOption === option.value
                                        ? '#e6f3ff'
                                        : 'transparent'),
                                    color: option.value === value ? 'white' : 'inherit',
                                }}
                            >
                                <span dangerouslySetInnerHTML={{__html: option.label}}/>
                                {option.value ?
                                    <span dangerouslySetInnerHTML={{__html: `${option.template}`}}
                                          style={{
                                              padding: '2px 6px',
                                              backgroundColor: '#f0f0f1',
                                              borderRadius: '6px',
                                              fontSize: '1em',
                                              color: '#555'
                                          }}/> : null}
                            </Button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

registerBlockType(blockName, {
    edit: ({attributes, setAttributes}) => {
        const {postId, overrides} = attributes;
        const parts = useSelect(
            select => select('core').getEntityRecords('postType', 'page_part', {
                per_page: -1,
                _embed: true,
                orderby: 'modified',
                order: 'desc'
            }),
            []
        ) || [];

        // Get template registry from localized data
        const registry = (typeof window !== 'undefined' && window.PagePartDesignSettings)
            ? window.PagePartDesignSettings.registry || {}
            : {};

        // Get selected page part and its template data
        const selectedPart = parts.find(p => p.id === postId);
        const designSlug = selectedPart?.meta?.design_slug || '';
        const templateData = registry[designSlug] || {};
        const pageEditableFields = (templateData.custom_fields || []).filter(f => f.page_editable);

        // Build dropdown options with template names
        const dropdownOptions = [
            {label: __(' - Selecteer Page Part blok… - ', textDomain), value: 0},
            ...parts.map(part => {
                const designSlug = (part.meta && part.meta.design_slug) || '';
                const templateName = (registry[designSlug] && registry[designSlug].name)
                    ? registry[designSlug].name
                    : (designSlug || 'Unknown');

                const formattedLabel = `${part.title.rendered}`;

                return {
                    label: formattedLabel,
                    template: templateName,
                    value: part.id
                };
            })
        ];

        // Render override control based on field type
        const renderOverrideControl = (field) => {
            const overrideValue = overrides[field.meta_key] || '';

            const updateOverride = (value) => {
                const newOverrides = {...overrides};
                if (value === '' || value === null) {
                    delete newOverrides[field.meta_key];
                } else {
                    newOverrides[field.meta_key] = value;
                }
                setAttributes({overrides: newOverrides});
            };

            switch (field.type) {
                case 'select':
                    const selectOptions = field.options || [];
                    const selectLabels = field.option_labels || selectOptions;

                    return (
                        <SelectControl
                            key={field.meta_key}
                            label={`Override ${field.label}`}
                            value={overrideValue}
                            options={[
                                {label: '— Gebruik de ingestelde waarde —', value: ''},
                                ...selectOptions.map((option, index) => ({
                                    label: selectLabels[index] || option,
                                    value: option
                                }))
                            ]}
                            onChange={updateOverride}
                        />
                    );

                case 'checkbox':
                    return (
                        <CheckboxControl
                            key={field.meta_key}
                            label={`Override ${field.label}`}
                            checked={overrideValue === '1'}
                            onChange={(checked) => updateOverride(checked ? '1' : '0')}
                        />
                    );

                case 'text':
                default:
                    return (
                        <TextControl
                            key={field.meta_key}
                            label={`Override ${field.label}`}
                            value={overrideValue}
                            onChange={updateOverride}
                            placeholder="Gebruik de ingestelde waarde"
                        />
                    );
            }
        };


        // Build the iframe src
        //const src = postId ? `/wp-json/nok-2025-v1/v1/embed-page-part/${postId}` : '';
        const src = postId
            ? `/wp-json/nok-2025-v1/v1/embed-page-part/${postId}?${new URLSearchParams(
                Object.entries(overrides).map(([key, value]) => [key, value])
            ).toString()}`
            : '';

        // Refs & state for dynamic height
        const iframeRef = useRef(null);
        const [height, setHeight] = useState(400);

        useEffect(() => {
            const iframe = iframeRef.current;
            if (!iframe) {
                return;
            }

            let mo; // mutation observer

            const updateHeight = () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const newHeight = Math.max(
                        doc.documentElement.scrollHeight,
                        doc.body.scrollHeight
                    );
                    setHeight(newHeight);
                } catch (e) {
                    // ignore if not ready or cross-origin
                }
            };

            const onLoad = () => {
                updateHeight();
                // watch for any DOM changes inside the iframe
                if (iframe.contentDocument && iframe.contentDocument.body) {
                    mo = new MutationObserver(updateHeight);
                    mo.observe(iframe.contentDocument.body, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                    });
                }
            };

            iframe.addEventListener('load', onLoad);

            // cleanup
            return () => {
                iframe.removeEventListener('load', onLoad);
                if (mo) {
                    mo.disconnect();
                }
            };
        }, [postId]);

        return (
            <>
                <BlockControls></BlockControls>
                <div {...useBlockProps()} style={{position: 'relative', width: '100%', margin: '0', maxWidth: '100%'}}>
                    <PanelBody title={__('NOK Page Part Blok', textDomain)} initialOpen>
                        <CustomPagePartSelector
                            label={__('Selecteer een Page Part uit de lijst', textDomain)}
                            value={postId}
                            options={dropdownOptions}
                            onChange={val => setAttributes({postId: parseInt(val, 10)})}
                        />

                        {pageEditableFields.length > 0 && postId !== 0 && (
                            <PanelBody title={__('Pagina-afhankelijke overrides', textDomain)} initialOpen={false}>
                                <p style={{fontSize: '12px', color: '#666', marginBottom: '12px'}}>
                                    Deze page part biedt de mogelijkheid om enkele instellingen specifek voor deze
                                    pagina te overschrijven/herdefinieren:
                                </p>
                                {pageEditableFields.map(renderOverrideControl)}
                            </PanelBody>
                        )}
                    </PanelBody>

                    {postId ? (
                        <div style={{position: 'relative', width: '100%'}}>
                            <iframe
                                key={`${postId}-${JSON.stringify(overrides)}`} // force reload when overrides change
                                ref={iframeRef}
                                title={__('Embedded NOK Page Part', textDomain)}
                                src={src}
                                style={{
                                    width: '100%',
                                    height: `${height}px`,
                                    border: 0,
                                }}
                                sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                            />
                        </div>
                    ) : (
                        <p>{__('Selecteer een blok om te bekijken…', textDomain)}</p>
                    )}
                </div>
            </>
        );
    },
    save: () => null, // fully dynamic
});