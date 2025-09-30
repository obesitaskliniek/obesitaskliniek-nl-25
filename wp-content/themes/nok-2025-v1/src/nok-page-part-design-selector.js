import {useSelect, useDispatch} from '@wordpress/data';
import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/edit-post';
import {SelectControl, TextControl, TextareaControl, CheckboxControl} from '@wordpress/components';
import {hnlLogger} from '../assets/js/modules/hnl.logger.mjs';
import {Fragment, useRef, useState, useEffect} from '@wordpress/element';

const NAME = 'nok-page-part-design-selector';

const RepeaterField = ({ field, schema, value, onChange }) => {
    const [items, setItems] = useState(() => {
        try {
            return JSON.parse(value || '[]');
        } catch {
            return [];
        }
    });

    // Create empty item structure from schema
    const createEmptyItem = () => {
        const emptyItem = {};
        schema.forEach(schemaField => {
            emptyItem[schemaField.name] = '';
        });
        return emptyItem;
    };

    const updateItems = (newItems) => {
        setItems(newItems);
        onChange(JSON.stringify(newItems));
    };

    const addItem = () => {
        const newItems = [...items, createEmptyItem()];
        updateItems(newItems);
    };

    const removeItem = (index) => {
        const newItems = items.filter((_, i) => i !== index);
        updateItems(newItems);
    };

    const updateItem = (index, key, itemValue) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [key]: itemValue };
        updateItems(newItems);
    };

    // Render field based on schema type
    const renderSchemaField = (schemaField, item, index) => {
        const fieldKey = schemaField.name;
        const fieldValue = item[fieldKey] || '';
        const label = schemaField.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        const fieldStyle = {
            width: '100%',
            padding: '6px 8px',
            border: '1px solid #ddd',
            borderRadius: '3px',
            fontSize: '13px'
        };

        switch (schemaField.type) {
            case 'textarea':
                return (
                    <div key={fieldKey} style={{ marginBottom: '8px' }}>
                        <label style={{ display: 'block', fontSize: '11px', fontWeight: '600', marginBottom: '4px' }}>
                            {label}:
                        </label>
                        <textarea
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            rows="3"
                            style={{ ...fieldStyle, resize: 'vertical' }}
                        />
                    </div>
                );

            case 'url':
                return (
                    <div key={fieldKey} style={{ marginBottom: '8px' }}>
                        <label style={{ display: 'block', fontSize: '11px', fontWeight: '600', marginBottom: '4px' }}>
                            {label}:
                        </label>
                        <input
                            type="url"
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            placeholder="https://..."
                            style={fieldStyle}
                        />
                    </div>
                );

            case 'text':
            default:
                return (
                    <div key={fieldKey} style={{ marginBottom: '8px' }}>
                        <label style={{ display: 'block', fontSize: '11px', fontWeight: '600', marginBottom: '4px' }}>
                            {label}:
                        </label>
                        <input
                            type="text"
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            style={fieldStyle}
                        />
                    </div>
                );
        }
    };

    return (
        <div style={{ border: '1px solid #ddd', borderRadius: '4px', padding: '12px', marginTop: '8px' }}>
            <div style={{ marginBottom: '12px', fontSize: '13px', fontWeight: '600' }}>
                {field.label}
            </div>

            {items.map((item, index) => (
                <div key={index} style={{
                    border: '1px solid #e0e0e0',
                    borderRadius: '4px',
                    marginBottom: '10px',
                    overflow: 'hidden'
                }}>
                    <div style={{
                        background: '#f8f9fa',
                        padding: '8px 12px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        borderBottom: '1px solid #e0e0e0'
                    }}>
                        <span style={{ fontSize: '12px', fontWeight: '600' }}>
                            Item {index + 1}
                        </span>
                        <button
                            type="button"
                            onClick={() => removeItem(index)}
                            style={{
                                background: 'none',
                                border: 'none',
                                color: '#d63638',
                                cursor: 'pointer',
                                fontSize: '12px'
                            }}
                        >
                            Remove
                        </button>
                    </div>

                    <div style={{ padding: '12px' }}>
                        {schema.map(schemaField => renderSchemaField(schemaField, item, index))}
                    </div>
                </div>
            ))}

            <button
                type="button"
                onClick={addItem}
                style={{
                    background: '#0073aa',
                    color: 'white',
                    border: 'none',
                    padding: '6px 12px',
                    borderRadius: '3px',
                    cursor: 'pointer',
                    fontSize: '12px'
                }}
            >
                Add Item
            </button>
        </div>
    );
};

function DesignSlugPanel() {
    // 1) Grab the current post type
    const postType = useSelect((select) =>
        select('core/editor').getCurrentPostType(), []
    );
    // 2) If it isn't our CPT, render nothing
    if (postType !== 'page_part') {
        return null;
    }

    // 3) Now safely grab ID + meta
    const postId = useSelect((select) =>
        select('core/editor').getCurrentPostId(), []
    );
    const meta = useSelect((select) =>
        select('core/editor').getEditedPostAttribute('meta'), []
    );
    const {editPost} = useDispatch('core/editor');

    // 4) Build your dropdown options
    const registry = window.PagePartDesignSettings?.registry || {};
    const options = [
        {label: '— Select —', value: ''},
        ...Object.entries(registry).map(([slug, data]) => ({
            label: data.name,
            value: slug,
        })),
    ];

    // 5) Get current template and its custom fields
    const currentTemplate = meta?.design_slug || '';
    const currentTemplateData = registry[currentTemplate] || {};
    const customFields = currentTemplateData.custom_fields || [];

    // 6) Enhanced function to trigger preview update with all meta
    const triggerPreviewUpdate = (updatedMeta = null) => {
        const metaToStore = updatedMeta || meta;
        const currentSlug = metaToStore?.design_slug || '';

        hnlLogger.log(NAME, 'Storing all meta:');
        hnlLogger.log(NAME, metaToStore);

        // Prepare form data
        const formData = new URLSearchParams({
            action: 'store_preview_state',
            post_id: postId,
            design_slug: currentSlug
        });

        // Add all meta fields as JSON
        formData.append('all_meta', JSON.stringify(metaToStore));

        // Store the meta value via AJAX
        fetch(window.PagePartDesignSettings.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                hnlLogger.log(NAME, 'Meta stored via React:');
                hnlLogger.log(NAME, data);

                // Trigger autosave and iframe refresh
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(() => {
                hnlLogger.log(NAME, 'Autosave completed via React');

                // Update preview if function exists - mark as user-initiated
                if (typeof window.nokUpdatePreview === 'function') {
                    window.nokUpdatePreview(true); // true = user initiated
                }
            })
            .catch(error => {
                hnlLogger.error(NAME, `Preview update failed: ${error}`);
            });
    };

    // 7) Local state for immediate UI updates + debounced backend updates
    const [localFieldValues, setLocalFieldValues] = useState({});
    const [isInitialized, setIsInitialized] = useState(false);
    const debounceRef = useRef({});

    // Initialize local state from meta when meta changes (but not from our own updates)
    useEffect(() => {
        if (meta && customFields.length > 0) {
            const initialValues = {};
            customFields.forEach(field => {
                const metaValue = meta[field.meta_key];
                // Use field default if meta value is empty/undefined
                const effectiveValue = (metaValue !== undefined && metaValue !== '')
                    ? metaValue
                    : (field.default || '');
                initialValues[field.meta_key] = effectiveValue;
            });
            setLocalFieldValues(initialValues);

            if (!isInitialized) {
                setTimeout(() => setIsInitialized(true), 1000);
            }
        }
    }, [currentTemplate, meta?.design_slug]);

    const updateMetaField = (fieldName, value) => {
        // Don't trigger updates during initialization
        if (!isInitialized) {
            hnlLogger.log(NAME, `Skipping update during initialization for ${fieldName}`);
            return;
        }

        // Update local state immediately for responsive UI
                setLocalFieldValues(prev => ({
                    ...prev,
            [fieldName]: value
                }));

        // Clear any existing timeout for this field
        if (debounceRef.current[fieldName]) {
            clearTimeout(debounceRef.current[fieldName]);
        }

        // Set new timeout for debounced backend update
        debounceRef.current[fieldName] = setTimeout(() => {
            hnlLogger.log(NAME, `Debounced update for ${fieldName}: ${value}`);

            // Update the editor meta
            const newMeta = {...meta, [fieldName]: value};
            editPost({meta: newMeta});

            // Trigger preview update
            triggerPreviewUpdate(newMeta);

            // Clean up timeout reference
            delete debounceRef.current[fieldName];
        }, 500);
    };

    // 8) Render field based on type
    const renderField = (field) => {
        // Use local state value for immediate UI updates, fallback to meta value
        const fieldValue = localFieldValues[field.meta_key] ?? meta?.[field.meta_key] ?? '';

        switch (field.type) {
            case 'textarea':
                return (
                    <TextareaControl
                        key={field.meta_key}
                        label={field.label}
                        value={fieldValue}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                        rows={3}
                    />
                );

            case 'url':
                return (
                    <TextControl
                        key={field.meta_key}
                        label={field.label}
                        type="url"
                        value={fieldValue}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                        placeholder="https://..."
                    />
                );
            case 'repeater':
                // Add safety check for schema
                if (!field.schema || field.schema.length === 0) {
                    return (
                        <div key={field.meta_key} style={{ padding: '12px', background: '#fff3cd', border: '1px solid #ffc107', borderRadius: '4px' }}>
                            <strong>Warning:</strong> Repeater field "{field.label}" has no schema defined.
                        </div>
                    );
                }
                return (
                    <RepeaterField
                        key={field.meta_key}
                        field={field}
                        schema={field.schema}
                        value={fieldValue}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                    />
                );
            case 'select':
                const selectOptions = field.options || [];
                const selectLabels = field.option_labels || selectOptions; // Fallback to options if no labels

                return (
                    <SelectControl
                        key={field.meta_key}
                        label={field.label}
                        value={fieldValue}
                        options={[
                            { label: '— Select —', value: '' },
                            ...selectOptions.map((option, index) => ({
                                label: selectLabels[index] || option,
                                value: option
                            }))
                        ]}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                    />
                );

            case 'checkbox':
                return (
                    <CheckboxControl
                        key={field.meta_key}
                        label={field.label}
                        checked={fieldValue === '1' || fieldValue === true}
                        onChange={(checked) => updateMetaField(field.meta_key, checked ? '1' : '0')}
                    />
                );

            case 'text':
            default:
                return (
                    <TextControl
                        key={field.meta_key}
                        label={field.label}
                        value={fieldValue}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                    />
                );
        }
    };

    return (
        <PluginDocumentSettingPanel
            name="page-part-design"
            title="NOK Design template"
        >
            <SelectControl
                label="Template"
                value={currentTemplate}
                options={options}
                onChange={(newSlug) => {
                    hnlLogger.log(NAME, `→ setting design_slug to "${newSlug}"`);
                    // Update the editor meta
                    const newMeta = {...meta, design_slug: newSlug};
                    editPost({meta: newMeta});
                    // Trigger preview update with the updated meta
                    triggerPreviewUpdate(newMeta);
                }}
            />

            {/* Render custom fields for selected template */}
            {customFields.length > 0 && (
                <Fragment>
                    <hr style={{margin: '16px 0'}}/>
                    <h4 style={{margin: '0 0 12px 0', fontSize: '13px', fontWeight: '600'}}>
                        Template options
                    </h4>
                    {customFields.map(renderField)}
                </Fragment>
            )}
        </PluginDocumentSettingPanel>
    );
}

registerPlugin('page-part-design', {
    render: DesignSlugPanel,
});