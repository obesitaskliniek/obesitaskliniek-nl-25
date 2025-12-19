import { useState, useEffect } from '@wordpress/element';
import { Modal, Button, SelectControl, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Type compatibility map for field imports
 * Each key maps to an array of compatible source types
 */
const TYPE_COMPATIBILITY = {
    'text':          ['text', 'textarea', 'url'],
    'textarea':      ['textarea', 'text'],
    'url':           ['url', 'text'],
    'select':        ['select', 'text'],
    'checkbox':      ['checkbox'],
    'icon-selector': ['icon-selector', 'text'],
    'repeater':      ['repeater']
};

/**
 * Check if source type is compatible with target type
 */
const isTypeCompatible = (targetType, sourceType) => {
    return TYPE_COMPATIBILITY[targetType]?.includes(sourceType) ?? false;
};

/**
 * Find the best matching source field for a target field
 */
const findBestMatch = (targetName, sourceFields, targetType) => {
    const compatible = sourceFields.filter(s => isTypeCompatible(targetType, s.type));

    // Exact name match
    const exact = compatible.find(s => s.name === targetName);
    if (exact) return exact.meta_key;

    // Partial match (target name contained in source or vice versa)
    const partial = compatible.find(s =>
        s.name.includes(targetName) || targetName.includes(s.name)
    );
    if (partial) return partial.meta_key;

    return '';
};

/**
 * Field Import Wizard Modal Component
 *
 * Allows users to map and import field values from a previous template
 * when switching page part templates.
 */
const FieldImportWizard = ({
    isOpen,
    onClose,
    postId,
    currentTemplate,
    targetFields,
    onImport
}) => {
    const [loading, setLoading] = useState(true);
    const [sources, setSources] = useState([]);
    const [selectedSource, setSelectedSource] = useState('');
    const [mappings, setMappings] = useState({});
    const [repeaterMappings, setRepeaterMappings] = useState({});

    // Fetch orphaned fields on open
    useEffect(() => {
        if (!isOpen) return;

        setLoading(true);
        apiFetch({
            path: `/nok/v1/page-part/${postId}/orphaned-fields?current_template=${currentTemplate}`
        }).then(response => {
            setSources(response.sources || []);
            if (response.sources?.length === 1) {
                setSelectedSource(response.sources[0].template_slug);
            }
            setLoading(false);
        }).catch(() => {
            setSources([]);
            setLoading(false);
        });
    }, [isOpen, postId, currentTemplate]);

    // Auto-suggest mappings when source changes
    useEffect(() => {
        if (!selectedSource) {
            setMappings({});
            setRepeaterMappings({});
            return;
        }

        const source = sources.find(s => s.template_slug === selectedSource);
        if (!source) return;

        const newMappings = {};
        const newRepeaterMappings = {};

        targetFields.forEach(target => {
            const suggested = findBestMatch(target.name, source.fields, target.type);
            newMappings[target.meta_key] = suggested;

            // For repeaters, also suggest subfield mappings
            if (target.type === 'repeater' && suggested) {
                const sourceField = source.fields.find(f => f.meta_key === suggested);
                if (sourceField?.schema && target.schema) {
                    const subMappings = {};
                    target.schema.forEach(targetSub => {
                        const sourceSub = sourceField.schema?.find(s =>
                            s.name === targetSub.name ||
                            s.name.includes(targetSub.name) ||
                            targetSub.name.includes(s.name)
                        );
                        subMappings[targetSub.name] = sourceSub?.name || '';
                    });
                    newRepeaterMappings[target.meta_key] = subMappings;
                }
            }
        });

        setMappings(newMappings);
        setRepeaterMappings(newRepeaterMappings);
    }, [selectedSource, sources, targetFields]);

    const getSourceFields = () => {
        return sources.find(s => s.template_slug === selectedSource)?.fields || [];
    };

    const getCompatibleSources = (targetType) => {
        return getSourceFields().filter(f => isTypeCompatible(targetType, f.type));
    };

    const handleImport = () => {
        const sourceFields = getSourceFields();
        const importData = {};

        Object.entries(mappings).forEach(([targetKey, sourceKey]) => {
            if (!sourceKey) return;

            const sourceField = sourceFields.find(f => f.meta_key === sourceKey);
            const targetField = targetFields.find(f => f.meta_key === targetKey);

            if (!sourceField || !targetField) return;

            if (targetField.type === 'repeater' && sourceField.type === 'repeater') {
                // Apply subfield mappings
                const subMappings = repeaterMappings[targetKey] || {};
                try {
                    const sourceItems = JSON.parse(sourceField.value);
                    const mappedItems = sourceItems.map(item => {
                        const newItem = {
                            _id: `item_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
                        };
                        targetField.schema.forEach(targetSub => {
                            const sourceSubName = subMappings[targetSub.name];
                            newItem[targetSub.name] = sourceSubName ? (item[sourceSubName] || '') : '';
                        });
                        return newItem;
                    });
                    importData[targetKey] = JSON.stringify(mappedItems);
                } catch (e) {
                    console.error('Failed to map repeater:', e);
                }
            } else {
                importData[targetKey] = sourceField.value;
            }
        });

        onImport(importData);
        onClose();
    };

    const getSourceField = (metaKey) => getSourceFields().find(f => f.meta_key === metaKey);

    if (!isOpen) return null;

    const selectedSourceData = sources.find(s => s.template_slug === selectedSource);

    return (
        <Modal
            title="Import from previous template"
            onRequestClose={onClose}
            style={{ maxWidth: '600px', width: '100%' }}
        >
            {loading ? (
                <div style={{ textAlign: 'center', padding: '20px' }}>
                    <Spinner />
                </div>
            ) : sources.length === 0 ? (
                <p>No importable fields found from previous templates.</p>
            ) : (
                <div>
                    {sources.length > 1 && (
                        <SelectControl
                            label="Source template"
                            value={selectedSource}
                            options={[
                                { label: '— Select template —', value: '' },
                                ...sources.map(s => ({
                                    label: `${s.template_name} (${s.fields.length} fields)`,
                                    value: s.template_slug
                                }))
                            ]}
                            onChange={setSelectedSource}
                            __nextHasNoMarginBottom
                            style={{ marginBottom: '16px' }}
                        />
                    )}

                    {selectedSource && (
                        <div>
                            <p style={{ color: '#666', fontSize: '12px', marginBottom: '16px' }}>
                                Map fields from <strong>{selectedSourceData?.template_name}</strong> to current template.
                            </p>

                            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr style={{ borderBottom: '2px solid #ddd' }}>
                                        <th style={{ textAlign: 'left', padding: '8px', width: '40%' }}>Target field</th>
                                        <th style={{ textAlign: 'left', padding: '8px' }}>Import from</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {targetFields.map(target => {
                                        const compatible = getCompatibleSources(target.type);
                                        const isRepeater = target.type === 'repeater';
                                        const selectedSourceField = getSourceField(mappings[target.meta_key]);
                                        const showSubfields = isRepeater && selectedSourceField?.schema && mappings[target.meta_key];

                                        return (
                                            <React.Fragment key={target.meta_key}>
                                                <tr style={{ borderBottom: '1px solid #eee' }}>
                                                    <td style={{ padding: '8px', verticalAlign: 'top' }}>
                                                        <strong>{target.label}</strong>
                                                        <br />
                                                        <span style={{ fontSize: '11px', color: '#666' }}>{target.type}</span>
                                                    </td>
                                                    <td style={{ padding: '8px' }}>
                                                        <SelectControl
                                                            value={mappings[target.meta_key] || ''}
                                                            options={[
                                                                { label: "— Don't import —", value: '' },
                                                                ...compatible.map(f => ({
                                                                    label: `${f.label || f.name} (${f.type})`,
                                                                    value: f.meta_key
                                                                }))
                                                            ]}
                                                            onChange={val => setMappings(m => ({ ...m, [target.meta_key]: val }))}
                                                            __nextHasNoMarginBottom
                                                        />
                                                    </td>
                                                </tr>

                                                {/* Repeater subfield mapping */}
                                                {showSubfields && (
                                                    <tr key={`${target.meta_key}-sub`}>
                                                        <td colSpan="2" style={{ padding: '0 8px 8px 24px', background: '#f9f9f9' }}>
                                                            <div style={{ fontSize: '11px', fontWeight: '600', margin: '8px 0', textTransform: 'uppercase', color: '#666' }}>
                                                                Subfield mapping ({JSON.parse(selectedSourceField.value).length} items)
                                                            </div>
                                                            <table style={{ width: '100%' }}>
                                                                <tbody>
                                                                    {target.schema.map(targetSub => (
                                                                        <tr key={targetSub.name}>
                                                                            <td style={{ padding: '4px 8px', width: '40%' }}>
                                                                                {targetSub.label || targetSub.name}
                                                                                <span style={{ fontSize: '10px', color: '#999', marginLeft: '4px' }}>({targetSub.type})</span>
                                                                            </td>
                                                                            <td style={{ padding: '4px 8px' }}>
                                                                                <SelectControl
                                                                                    value={repeaterMappings[target.meta_key]?.[targetSub.name] || ''}
                                                                                    options={[
                                                                                        { label: '— None —', value: '' },
                                                                                        ...selectedSourceField.schema.map(s => ({
                                                                                            label: s.label || s.name,
                                                                                            value: s.name
                                                                                        }))
                                                                                    ]}
                                                                                    onChange={val => setRepeaterMappings(rm => ({
                                                                                        ...rm,
                                                                                        [target.meta_key]: {
                                                                                            ...rm[target.meta_key],
                                                                                            [targetSub.name]: val
                                                                                        }
                                                                                    }))}
                                                                                    __nextHasNoMarginBottom
                                                                                />
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div style={{ marginTop: '24px', display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
                        <Button variant="tertiary" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            onClick={handleImport}
                            disabled={!selectedSource || Object.values(mappings).every(v => !v)}
                        >
                            Import
                        </Button>
                    </div>
                </div>
            )}
        </Modal>
    );
};

export default FieldImportWizard;
