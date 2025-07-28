import {useSelect, useDispatch} from '@wordpress/data';
import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/edit-post';
import {SelectControl, TextControl, TextareaControl} from '@wordpress/components';
import {hnlLogger} from '../assets/js/modules/hnl.logger.mjs';
import {Fragment, useRef, useState, useEffect} from '@wordpress/element';

const NAME = 'nok-page-part-design-selector';

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
                const metaValue = meta[field.meta_key] || '';
                initialValues[field.meta_key] = metaValue;
            });
            setLocalFieldValues(initialValues);

            // Mark as initialized after a short delay to avoid initial flood
            if (!isInitialized) {
                setTimeout(() => setIsInitialized(true), 1000);
            }
        }
    }, [currentTemplate, meta?.design_slug]); // Only sync when template changes, not on every meta change

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
                // For now, show as textarea - we'll enhance this later
                return (
                    <TextareaControl
                        key={field.meta_key}
                        label={field.label + ' (JSON)'}
                        value={fieldValue}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
                        help="Enter JSON data for repeater field"
                        rows={4}
                    />
                );

            case 'select':
                const selectOptions = field.options || [];
                return (
                    <SelectControl
                        key={field.meta_key}
                        label={field.label}
                        value={fieldValue}
                        options={[
                            { label: '— Select —', value: '' },
                            ...selectOptions.map(option => ({
                                label: option,
                                value: option
                            }))
                        ]}
                        onChange={(value) => updateMetaField(field.meta_key, value)}
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
            title="Design template"
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
                        Template Fields
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