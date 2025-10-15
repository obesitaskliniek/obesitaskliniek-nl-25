import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, BlockControls, useBlockProps, MediaUpload, MediaUploadCheck} from '@wordpress/block-editor';
import {SelectControl, PanelBody, Button, Popover, TextControl, CheckboxControl} from '@wordpress/components';
import {useSelect, useDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {useRef, useState, useEffect} from '@wordpress/element';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-page-part';

const CustomPagePartSelector = ({value, options, onChange, onOpen}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [hoveredOption, setHoveredOption] = useState(null);
    const buttonRef = useRef();
    const selectedOption = options.find(opt => opt.value === value);

    const handleOpen = () => {
        const newOpenState = !isOpen;
        setIsOpen(newOpenState);
        if (newOpenState && onOpen) {
            onOpen();
        }
    };

    return (
        <div style={{position: 'relative'}}>
            <Button
                ref={buttonRef}
                variant="secondary"
                onClick={handleOpen}
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
                                  color: '#555',
                                  marginLeft: 'auto'
                              }}/> : null
                }
            </Button>
            {isOpen && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    zIndex: 99999
                }}>
                    <div
                        style={{
                            position: 'fixed',
                            top: 0,
                            left: 0,
                            right: 0,
                            bottom: 0,
                            background: 'transparent'
                        }}
                        onClick={() => setIsOpen(false)}
                    />
                    <div style={{
                        position: 'absolute',
                        top: buttonRef.current?.getBoundingClientRect().bottom + window.scrollY,
                        left: buttonRef.current?.getBoundingClientRect().left + window.scrollX,
                        width: buttonRef.current?.offsetWidth || 'auto',
                        maxHeight: '300px',
                        overflowY: 'auto',
                        backgroundColor: 'white',
                        border: '1px solid #ccc',
                        borderRadius: '2px',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
                        zIndex: 100000,
                    }}>
                        {options.map(option => (
                            <Button
                                key={option.value}
                                onClick={() => {
                                    onChange(option.value);
                                    setIsOpen(false);
                                }}
                                onMouseEnter={() => setHoveredOption(option.value)}
                                onMouseLeave={() => setHoveredOption(null)}
                                style={{
                                    width: '100%',
                                    textAlign: 'left',
                                    padding: '8px 12px',
                                    border: 'none',
                                    borderBottom: '1px solid #f0f0f1',
                                    borderRadius: '0',
                                    cursor: 'pointer',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    justifyContent: 'space-between',
                                    backgroundColor: option.value === value
                                        ? 'var(--wp-admin-theme-color)' : (hoveredOption === option.value
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
        const {invalidateResolution} = useDispatch('core');

        const parts = useSelect(
            select => select('core').getEntityRecords('postType', 'page_part', {
                per_page: -1,
                _embed: true,
                orderby: 'modified',
                order: 'desc'
            }),
            []
        ) || [];

        const refreshPageParts = () => {
            invalidateResolution('getEntityRecords', ['postType', 'page_part', {
                per_page: -1,
                _embed: true,
                orderby: 'modified',
                order: 'desc'
            }]);
        };

        // Get template registry from localized data
        const registry = (typeof window !== 'undefined' && window.PagePartDesignSettings)
            ? window.PagePartDesignSettings.registry || {}
            : {};

        // Get selected page part and its template data
        const selectedPart = parts.find(p => p.id === postId);
        const designSlug = selectedPart?.meta?.design_slug || '';
        const templateData = registry[designSlug] || {};
        const pageEditableFields = (templateData.custom_fields || []).filter(f => f.page_editable);
        const overrideThumbnail = useSelect(
            (select) => {
                const thumbnailId = overrides._override_thumbnail_id;
                if (!thumbnailId) return null;
                return select('core').getMedia(thumbnailId);
            },
            [overrides._override_thumbnail_id]
        );

        // Build dropdown options with template names
        const dropdownOptions = [
            {label: __(' - Selecteer Page Part blok… - ', textDomain), value: 0},
            ...parts.map(part => {
                const designSlug = (part.meta && part.meta.design_slug) || '';
                const templateName = (registry[designSlug] && registry[designSlug].name)
                    ? registry[designSlug].name
                    : designSlug || 'Unknown';
                return {
                    label: `${part.title.rendered}`,
                    template: templateName,
                    value: part.id
                };
            })
        ];

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
                            onOpen={refreshPageParts}
                        />

                        {pageEditableFields.length > 0 && postId !== 0 && (
                            <PanelBody title={__('Pagina-afhankelijke overrides', textDomain)} initialOpen={false}>
                                <p style={{fontSize: '12px', color: '#666', marginBottom: '12px'}}>
                                    Deze page part biedt de mogelijkheid om enkele instellingen specifek voor deze
                                    pagina te overschrijven/herdefinieren:
                                </p>
                                {pageEditableFields.map(field => {
                                    const currentValue = overrides[field.meta_key] || '';
                                    const updateOverride = (newValue) => {
                                        const newOverrides = {...overrides};
                                        if (newValue === '' || newValue === null) {
                                            delete newOverrides[field.meta_key];
                                        } else {
                                            newOverrides[field.meta_key] = newValue;
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
                                                    value={currentValue}
                                                    options={[
                                                        {label: '— Gebruik de ingestelde waarde —', value: ''},
                                                        ...selectOptions.map((opt, idx) => ({
                                                            label: selectLabels[idx] || opt,
                                                            value: opt
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
                                                    checked={currentValue === '1'}
                                                    onChange={checked => updateOverride(checked ? '1' : '')}
                                                />
                                            );

                                        case 'text':
                                        case 'url':
                                            return (
                                                <TextControl
                                                    key={field.meta_key}
                                                    label={`Override ${field.label}`}
                                                    value={currentValue}
                                                    onChange={updateOverride}
                                                    placeholder="— Gebruik de ingestelde waarde —"
                                                />
                                            );

                                        default:
                                            return null;
                                    }
                                })}
                            </PanelBody>
                        )}

                        {/* Featured Image Override - separate section, only when template allows it */}
                        {postId !== 0 && templateData.featured_image_overridable && (
                            <PanelBody title={__('Uitgelichte afbeelding', textDomain)} initialOpen={false}>
                                <p style={{fontSize: '12px', color: '#666', marginBottom: '12px'}}>
                                    Overschrijf de uitgelichte afbeelding van deze page part specifiek voor deze pagina.
                                </p>
                                <MediaUploadCheck>
                                    <MediaUpload
                                        onSelect={(media) => {
                                            const newOverrides = {...overrides};
                                            newOverrides._override_thumbnail_id = media.id;
                                            setAttributes({overrides: newOverrides});
                                        }}
                                        allowedTypes={['image']}
                                        value={overrides._override_thumbnail_id || ''}
                                        render={({open}) => (
                                            <div>
                                                {overrideThumbnail && (
                                                    <div style={{
                                                        marginBottom: '12px',
                                                        border: '1px solid #ddd',
                                                        borderRadius: '4px',
                                                        overflow: 'hidden',
                                                        maxWidth: '200px'
                                                    }}>
                                                        <img
                                                            src={overrideThumbnail.media_details?.sizes?.medium?.source_url
                                                                || overrideThumbnail.media_details?.sizes?.thumbnail?.source_url
                                                                || overrideThumbnail.source_url}
                                                            style={{width: '100%', height: 'auto', display: 'block'}}
                                                            alt=""
                                                        />
                                                    </div>
                                                )}
                                                <div style={{display: 'flex', gap: '8px', flexWrap: 'wrap'}}>
                                                    <Button variant="secondary" onClick={open}>
                                                        {overrideThumbnail
                                                            ? __('Wijzig afbeelding', textDomain)
                                                            : __('Selecteer afbeelding', textDomain)}
                                                    </Button>
                                                    {overrideThumbnail && (
                                                        <Button
                                                            variant="tertiary"
                                                            isDestructive
                                                            onClick={() => {
                                                                const newOverrides = {...overrides};
                                                                delete newOverrides._override_thumbnail_id;
                                                                setAttributes({overrides: newOverrides});
                                                            }}
                                                        >
                                                            {__('Verwijder override', textDomain)}
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    />
                                </MediaUploadCheck>
                            </PanelBody>
                        )}
                    </PanelBody>

                    {postId === 0 ? (
                        <div style={{
                            padding: '40px 20px',
                            textAlign: 'center',
                            backgroundColor: '#f0f0f1',
                            border: '1px dashed #ccc'
                        }}>
                            <p style={{margin: 0, color: '#666'}}>
                                {__('Selecteer een Page Part om de preview te zien', textDomain)}
                            </p>
                        </div>
                    ) : (
                        <iframe
                            ref={iframeRef}
                            src={src}
                            style={{width: '100%', height: `${height}px`, border: 'none'}}
                        />
                    )}
                </div>
            </>
        );
    },

    save: () => null
});