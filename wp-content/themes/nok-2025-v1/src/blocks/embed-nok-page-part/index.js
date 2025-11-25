import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, BlockControls, useBlockProps, MediaUpload, MediaUploadCheck} from '@wordpress/block-editor';
import {SelectControl, PanelBody, Button, Popover, TextControl, CheckboxControl, Icon} from '@wordpress/components';
import {pencil} from '@wordpress/icons';
import {useSelect, useDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {useRef, useState, useEffect} from '@wordpress/element';
import IconSelector from '../../components/IconSelector';
import ColorTool from "../../../assets/js/domule/util.color.mjs";

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-page-part';

const CustomPagePartSelector = ({value, options, onChange, onOpen}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [hoveredOption, setHoveredOption] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const buttonRef = useRef();
    const searchInputRef = useRef();
    const selectedOption = options.find(opt => opt.value === value);

    const handleOpen = () => {
        const newOpenState = !isOpen;
        setIsOpen(newOpenState);
        setSearchQuery(''); // Reset search when opening
        if (newOpenState && onOpen) {
            onOpen();
        }
    };

    // Focus search input when dropdown opens
    useEffect(() => {
        if (isOpen && searchInputRef.current) {
            setTimeout(() => searchInputRef.current?.focus(), 50);
        }
    }, [isOpen]);

    // Fuzzy search function
    const fuzzyMatch = (text, query) => {
        if (!query) return { matches: true, score: 0, indices: [] };

        const lowerText = text.toLowerCase();
        const lowerQuery = query.toLowerCase();

        let queryIndex = 0;
        let textIndex = 0;
        const indices = [];
        let score = 0;

        while (queryIndex < lowerQuery.length && textIndex < lowerText.length) {
            if (lowerQuery[queryIndex] === lowerText[textIndex]) {
                indices.push(textIndex);
                score += (100 - textIndex); // Favor matches earlier in string
                queryIndex++;
            }
            textIndex++;
        }

        const matches = queryIndex === lowerQuery.length;
        return { matches, score, indices };
    };

    // Filter and sort options based on search query
    const filteredOptions = searchQuery
        ? options
            .map(option => {
                // Search in both label and template name
                const labelMatch = fuzzyMatch(option.label.replace(/<[^>]*>/g, ''), searchQuery);
                const templateMatch = fuzzyMatch(option.template || '', searchQuery);
                const bestMatch = labelMatch.score > templateMatch.score ? labelMatch : templateMatch;

                return {
                    ...option,
                    matchData: bestMatch,
                    searchScore: bestMatch.score
                };
            })
            .filter(option => option.matchData.matches)
            .sort((a, b) => b.searchScore - a.searchScore)
        : options.filter(opt => opt.value !== 0); // Exclude placeholder option when not searching

    // Highlight matching characters in text
    const highlightMatch = (text, indices) => {
        if (!indices || indices.length === 0) return text;

        const stripped = text.replace(/<[^>]*>/g, '');
        let result = '';
        let lastIndex = 0;

        indices.forEach(index => {
            result += stripped.slice(lastIndex, index);
            result += `<mark style="background: #ffd700; padding: 0; font-weight: bold;">${stripped[index]}</mark>`;
            lastIndex = index + 1;
        });
        result += stripped.slice(lastIndex);

        return result;
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
                                  backgroundColor: ColorTool.new(selectedOption.template).string,
                                  borderRadius: '6px',
                                  fontSize: '1em',
                                  color: ColorTool.new(selectedOption.template).contra,
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
                        maxHeight: '400px',
                        backgroundColor: 'white',
                        border: '1px solid #ccc',
                        borderRadius: '2px',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
                        zIndex: 100000,
                        display: 'flex',
                        flexDirection: 'column',
                    }}>
                        {/* Search input */}
                        <div style={{
                            padding: '8px',
                            borderBottom: '1px solid #ddd',
                            position: 'sticky',
                            top: 0,
                            backgroundColor: 'white',
                            zIndex: 1,
                        }}>
                            <input
                                ref={searchInputRef}
                                type="text"
                                placeholder="Zoek page part..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Escape') {
                                        setIsOpen(false);
                                    } else if (e.key === 'Enter' && filteredOptions.length > 0) {
                                        onChange(filteredOptions[0].value);
                                        setIsOpen(false);
                                    }
                                }}
                                style={{
                                    width: '100%',
                                    padding: '6px 8px',
                                    border: '1px solid #ccc',
                                    borderRadius: '3px',
                                    fontSize: '13px',
                                    outline: 'none',
                                }}
                            />
                        </div>

                        {/* Options list */}
                        <div style={{
                            overflowY: 'auto',
                            maxHeight: '300px',
                        }}>
                            {filteredOptions.length === 0 ? (
                                <div style={{
                                    padding: '16px',
                                    textAlign: 'center',
                                    color: '#666',
                                    fontSize: '13px',
                                }}>
                                    Geen resultaten gevonden
                                </div>
                            ) : (
                                filteredOptions.map(option => {
                                    const color = ColorTool.new(option.template);
                                    const labelMatch = fuzzyMatch(option.label.replace(/<[^>]*>/g, ''), searchQuery);
                                    const displayLabel = searchQuery ? highlightMatch(option.label, labelMatch.indices) : option.label;

                                    return (
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
                                            <span dangerouslySetInnerHTML={{__html: displayLabel}}/>
                                            {option.value ?
                                                <span dangerouslySetInnerHTML={{__html: `${option.template}`}}
                                                      style={{
                                                          padding: '2px 6px',
                                                          backgroundColor: color.adjust({sat: 0.7}).string,
                                                          borderRadius: '6px',
                                                          fontSize: '1em',
                                                          color: color.contra
                                                      }}/> : null}
                                        </Button>
                                    )
                                })
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

registerBlockType(blockName, {
    attributes: {
        postId: {
            type: 'number',
            default: 0
        },
        overrides: {
            type: 'object',
            default: {}
        },
        excludeFromSeo: {
            type: 'boolean',
            default: false
        }
    },
    edit: ({attributes, setAttributes}) => {
        const {postId, overrides, excludeFromSeo} = attributes;
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
        const partTitle = selectedPart?.title?.rendered || '';
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

        const onLoad = () => {
            updateHeight();

            // Extract semantic content from iframe for Yoast
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                const meta = doc.querySelector('meta[name="yoast-content"]');
                if (meta && postId) {
                    const content = meta.getAttribute('content');

                    // Store in global for Yoast integration
                    window.nokPagePartData = window.nokPagePartData || {};
                    window.nokPagePartData[postId] = content;

                    if (window.nokYoastIntegration?.debug) {
                        console.log(`[Yoast] Stored content for part ${postId}:`, content.length, 'chars');
                    }
                }
            } catch (e) {
                // Cross-origin or not ready - ignore
            }

            // Watch for any DOM changes inside the iframe
            if (iframe.contentDocument && iframe.contentDocument.body) {
                mo = new MutationObserver(updateHeight);
                mo.observe(iframe.contentDocument.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                });
            }
        };

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

                // Extract semantic content from iframe for Yoast
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const meta = doc.querySelector('meta[name="yoast-content"]');
                    if (meta && postId) {
                        const content = meta.getAttribute('content');

                        // Store in global for Yoast integration
                        window.nokPagePartData = window.nokPagePartData || {};
                        window.nokPagePartData[postId] = content;

                        if (window.nokYoastIntegration?.debug) {
                            console.log(`[Yoast] Stored content for part ${postId}:`, content.length, 'chars');
                        }
                    }
                } catch (e) {
                    // Cross-origin or not ready - ignore
                }

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
                <div {...useBlockProps()} style={{
                    position: 'relative',
                    width: '100%',
                    marginBottom: '15px',
                    maxWidth: '100%',
                    padding: '15px 3vw',
                    boxSizing: 'border-box',
                }}>

                    {/* SEO Exclusion Badge */}
                    {excludeFromSeo && (
                        <div style={{
                            position: 'absolute',
                            top: '10px',
                            right: '10px',
                            background: '#f0f0f1',
                            padding: '4px 8px',
                            borderRadius: '3px',
                            fontSize: '11px',
                            color: '#666',
                            zIndex: 10,
                            border: '1px solid #ddd'
                        }}>
                            🚫 SEO uitgesloten
                        </div>
                    )}

                    <PanelBody title={__('NOK Page Part Blok', textDomain) + (partTitle ? ` - ${partTitle}` : '')}
                               initialOpen>
                        <CustomPagePartSelector
                            label={__('Selecteer een Page Part uit de lijst', textDomain)}
                            value={postId}
                            options={dropdownOptions}
                            onChange={val => setAttributes({postId: parseInt(val, 10)})}
                            onOpen={refreshPageParts}
                        />

                        {postId !== 0 && (
                            <PanelBody
                                title={__('SEO Instellingen', textDomain)}
                                initialOpen={false}
                            >
                                <CheckboxControl
                                    label={__('Meenemen in SEO analyse', textDomain)}
                                    help={__('Schakel uit om deze page part uit te sluiten van Yoast SEO analyse', textDomain)}
                                    checked={!excludeFromSeo}
                                    onChange={(value) => {
                                        setAttributes({excludeFromSeo: !value});

                                        // Notify Yoast integration of change
                                        if (window.nokYoastIntegration?.debug) {
                                            console.log(`[Yoast] Part ${postId} SEO exclusion changed to:`, !value);
                                        }
                                    }}
                                />
                            </PanelBody>
                        )}

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
                                        case 'icon-selector':
                                            const availableIcons = window.PagePartDesignSettings?.icons || {};
                                            return (
                                                <div key={field.meta_key} style={{marginBottom: '12px'}}>
                                                    <label style={{
                                                        display: 'block',
                                                        fontSize: '11px',
                                                        fontWeight: '600',
                                                        marginBottom: '4px',
                                                        color: '#666'
                                                    }}>
                                                        Override {field.label}
                                                    </label>
                                                    {currentValue && (
                                                        <button
                                                            onClick={() => updateOverride('')}
                                                            style={{
                                                                fontSize: '11px',
                                                                color: '#d63638',
                                                                background: 'none',
                                                                border: 'none',
                                                                padding: '0',
                                                                marginBottom: '8px',
                                                                cursor: 'pointer',
                                                                textDecoration: 'underline'
                                                            }}
                                                        >
                                                            Clear override (gebruik de ingestelde waarde)
                                                        </button>
                                                    )}
                                                    <IconSelector
                                                        value={currentValue}
                                                        icons={availableIcons}
                                                        onChange={updateOverride}
                                                    />
                                                </div>
                                            );

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
                                        //because an override for a boolean will result in no override when false
                                        case 'checkbox':
                                            return (
                                                <SelectControl
                                                    key={field.meta_key}
                                                    label={`Override ${field.label}`}
                                                    value={currentValue}
                                                    options={[
                                                        {label: '— Gebruik de ingestelde waarde —', value: ''},
                                                        {label: 'Ja', value: '1'},
                                                        {label: 'Nee', value: '0'}
                                                    ]}
                                                    onChange={updateOverride}
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
                            <div style={{
                                marginTop: '15px',
                                border: '1px solid rgba(0,0,0,0.6)',
                                borderRadius: '10px',
                                padding: '0',
                                boxSizing: 'border-box',
                                overflow: 'hidden',
                                display: 'flex',
                                flexDirection: 'column'
                            }}>
                                <div style={{
                                    background: 'rgba(0,0,0,0.1)',
                                    borderBottom: '1px solid rgba(0,0,0,0.6)',
                                    padding: '8px 12px',
                                    fontSize: '12px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '12px'
                                }}>
                                    <strong>Preview:</strong>
                                    <span>{selectedPart?.title.rendered || `ID ${postId}`}</span>
                                    <a
                                        href={`/wp-admin/post.php?post=${postId}&action=edit`}
                                        target="_blank"
                                        title={" Bewerk deze Page Part (in nieuw tabblad)"}
                                        rel="noopener noreferrer"
                                        style={{
                                            color: '#8cf',
                                            textDecoration: 'none',
                                            marginLeft: 'auto'
                                        }}
                                    >
                                        <Icon icon={pencil}/>
                                    </a>
                                </div>
                                <iframe
                                    ref={iframeRef}
                                    src={src}
                                    style={{
                                        width: '100%',
                                        height: `${height}px`,
                                        flexShrink: 0,
                                    }}
                                />
                            </div>
                        )}
                    </PanelBody>
                </div>
            </>
        )
            ;
    },

    save: () => null
});