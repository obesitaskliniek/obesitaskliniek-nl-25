import {useSelect, useDispatch} from '@wordpress/data';
import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/editor';
import {SelectControl, TextControl, TextareaControl, CheckboxControl, Button, Draggable} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import {logger} from '../assets/js/domule/core.log.mjs';
import {Fragment, useRef, useState, useEffect, useMemo} from '@wordpress/element';
import IconSelector from './components/IconSelector';
import TaxonomySelector from './components/TaxonomySelector';
import ColorSelector from './components/ColorSelector';
import ImageSelector from './components/ImageSelector';
import FieldImportWizard from './components/FieldImportWizard';
import LinkField from './components/LinkField';

const NAME = 'nok-page-part-design-selector';

const fieldStyle = {
    width: '100%',
    borderBottom: '1px solid #ddd',
    borderRadius: '3px',
    fontSize: '13px',
    margin: '0',
    padding: '16px 0',
};

const helpStyle = {
    margin: '8px 0 8px 0',
    fontSize: '12px',
    color: '#757575',
    fontStyle: 'italic',
    lineHeight: '1.4'
};

// Wrapper for consistent field spacing and labeling
const FieldGroup = ({label, children}) => (
    <div style={{marginBottom: '16px'}}>
        {label && (
            <div style={{
                marginBottom: '8px',
                fontSize: '11px',
                fontWeight: '600',
                textTransform: 'uppercase',
                color: '#1e1e1e'
            }}>
                {label}
            </div>
        )}
        {children}
    </div>
);

const PostSelector = ({value, onChange, postTypes = ['post'], categories = []}) => {
    const [availablePosts, setAvailablePosts] = useState([]);
    const [selectedPosts, setSelectedPosts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    const selectedIds = useMemo(() => {
        try {
            return JSON.parse(value || '[]');
        } catch {
            return [];
        }
    }, [value]);

    // Fetch selected post details on mount
    useEffect(() => {
        const fetchSelectedPosts = async () => {
            if (selectedIds.length === 0) {
                setSelectedPosts([]);
                return;
            }

            try {
                const params = new URLSearchParams({
                    include: selectedIds.join(',')
                });
                const response = await fetch(`/wp-json/nok-2025-v1/v1/posts/query?${params}`);
                const posts = await response.json();
                setSelectedPosts(posts);
            } catch (error) {
                console.error('Failed to fetch selected posts:', error);
            }
        };

        fetchSelectedPosts();
    }, [selectedIds.join(',')]); // Proper dependencies

    // Fetch available posts
    useEffect(() => {
        const fetchPosts = async () => {
            setLoading(true);
            try {
                const params = new URLSearchParams({
                    post_type: postTypes.join(','),
                    exclude: selectedIds.join(','),
                    search: searchTerm
                });

                if (categories.length > 0) {
                    params.append('categories', categories.join(','));
                }
                const response = await fetch(`/wp-json/nok-2025-v1/v1/posts/query?${params}`);
                const posts = await response.json();
                setAvailablePosts(posts);
            } catch (error) {
                console.error('Failed to fetch posts:', error);
            }
            setLoading(false);
        };

        fetchPosts();
    }, [selectedIds.join(','), searchTerm, postTypes.join(','), categories.join(',')]);

    const addPost = (post) => {
        const newIds = [...selectedIds, post.id];
        setSelectedPosts([...selectedPosts, post]);
        onChange(JSON.stringify(newIds));
    };

    const removePost = (postId) => {
        const newIds = selectedIds.filter(id => id !== postId);
        setSelectedPosts(selectedPosts.filter(p => p.id !== postId));
        onChange(JSON.stringify(newIds));
    };

    return (
        <div>
            <div style={{marginBottom: '8px'}}>
                <input
                    type="text"
                    placeholder="Search posts..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    style={{
                        width: '100%',
                        padding: '6px 8px',
                        border: '1px solid #ddd',
                        borderRadius: '3px'
                    }}
                />
            </div>

            {selectedPosts.length > 0 && (
                <div style={{marginBottom: '8px'}}>
                    <strong>Selected:</strong>
                    {selectedPosts.map(post => (
                        <div key={post.id} style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            padding: '4px 8px',
                            background: '#f0f0f0',
                            marginTop: '4px',
                            borderRadius: '3px'
                        }}>
                            <span>{post.title}</span>
                            <button
                                type="button"
                                onClick={() => removePost(post.id)}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    color: '#cc0000',
                                    cursor: 'pointer',
                                    padding: '0 4px'
                                }}
                            >
                                ×
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <div style={{maxHeight: '200px', overflowY: 'auto', border: '1px solid #ddd', borderRadius: '3px'}}>
                {loading ? (
                    <div style={{padding: '8px', textAlign: 'center'}}>Loading...</div>
                ) : availablePosts.length === 0 ? (
                    <div style={{padding: '8px', textAlign: 'center', color: '#666'}}>
                        {searchTerm ? 'No posts found' : 'All posts selected'}
                    </div>
                ) : (
                    availablePosts.map(post => (
                        <div
                            key={post.id}
                            onClick={() => addPost(post)}
                            style={{
                                padding: '8px',
                                cursor: 'pointer',
                                borderBottom: '1px solid #eee'
                            }}
                            onMouseEnter={(e) => e.target.style.background = '#f9f9f9'}
                            onMouseLeave={(e) => e.target.style.background = 'transparent'}
                        >
                            <div style={{fontWeight: '500'}}>{post.title}</div>
                            <div style={{fontSize: '11px', color: '#666'}}>
                                {post.type} • {post.date}
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};

const RepeaterField = ({field, schema, value, onChange}) => {
    const [items, setItems] = useState(() => {
        try {
            return JSON.parse(value || '[]');
        } catch {
            return [];
        }
    });
    const [draggedIndex, setDraggedIndex] = useState(null);

    // Sync items when value prop changes externally (e.g., from import wizard)
    useEffect(() => {
        try {
            const parsedValue = JSON.parse(value || '[]');
            // Compare serialized versions to detect actual external changes
            if (JSON.stringify(parsedValue) !== JSON.stringify(items)) {
                setItems(parsedValue);
            }
        } catch {
            // Invalid JSON, ignore
        }
    }, [value]);

    const createEmptyItem = () => {
        const emptyItem = {
            _id: `item_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
        };
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
        newItems[index] = {...newItems[index], [key]: itemValue};
        updateItems(newItems);
    };

    const moveItem = (fromIndex, toIndex) => {
        const newItems = [...items];
        const [movedItem] = newItems.splice(fromIndex, 1);
        newItems.splice(toIndex, 0, movedItem);
        updateItems(newItems);
    };

    const handleDragStart = (e, index) => {
        setDraggedIndex(index);
        e.dataTransfer.effectAllowed = 'move';
        e.currentTarget.style.opacity = '0.5';
    };

    const handleDragEnd = (e) => {
        e.currentTarget.style.opacity = '1';
        setDraggedIndex(null);
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (e, dropIndex) => {
        e.preventDefault();
        if (draggedIndex !== null && draggedIndex !== dropIndex) {
            moveItem(draggedIndex, dropIndex);
        }
    };

    const renderSchemaField = (schemaField, item, index) => {
        const fieldKey = schemaField.name;
        const fieldValue = item[fieldKey] || '';
        const label = schemaField.name.charAt(0).toUpperCase() + schemaField.name.slice(1).replace(/_/g, ' ');

        switch (schemaField.type) {
            case 'icon-selector':
                const availableIcons = window.PagePartDesignSettings?.icons || {};
                return (
                    <FieldGroup key={fieldKey} label={schemaField.label || label}>
                        <IconSelector
                            value={fieldValue}
                            icons={availableIcons}
                            onChange={(value) => updateItem(index, fieldKey, value)}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );

            case 'textarea':
                return (
                    <FieldGroup key={fieldKey} label={schemaField.label || label}>
                        <textarea
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            rows={3}
                            style={{
                                width: '100%',
                                padding: '6px 8px',
                                border: '1px solid #ddd',
                                borderRadius: '3px',
                                fontSize: '13px'
                            }}
                        />
                    </FieldGroup>
                );

            case 'url':
                return (
                    <FieldGroup key={fieldKey} label={schemaField.label || label}>
                        <input
                            type="url"
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            placeholder="https://..."
                            style={{
                                width: '100%',
                                padding: '6px 8px',
                                border: '1px solid #ddd',
                                borderRadius: '3px',
                                fontSize: '13px'
                            }}
                        />
                    </FieldGroup>
                );

            default:
                return (
                    <FieldGroup key={fieldKey} label={schemaField.label || label}>
                        <input
                            type="text"
                            value={fieldValue}
                            onChange={(e) => updateItem(index, fieldKey, e.target.value)}
                            style={{
                                width: '100%',
                                padding: '6px 8px',
                                border: '1px solid #ddd',
                                borderRadius: '3px',
                                fontSize: '13px'
                            }}
                        />
                    </FieldGroup>
                );
        }
    };

    return (
        <>
            {items.map((item, index) => (
                <div
                    key={item._id || index}
                    draggable
                    onDragStart={(e) => handleDragStart(e, index)}
                    onDragEnd={handleDragEnd}
                    onDragOver={handleDragOver}
                    onDrop={(e) => handleDrop(e, index)}
                    style={{
                        background: draggedIndex === index ? '#f0f0f0' : '#f8f9fa',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        padding: '12px',
                        marginBottom: '8px',
                        position: 'relative',
                        cursor: 'move',
                        transition: 'background 0.2s'
                    }}
                >
                    <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginBottom: '8px'
                    }}>
                            <span style={{
                                fontSize: '11px',
                                fontWeight: '600',
                                color: '#555',
                                userSelect: 'none'
                            }}>
                                ⋮⋮ Item {index + 1}
                            </span>
                        <button
                            type="button"
                            onClick={() => removeItem(index)}
                            style={{
                                background: '#dc3232',
                                color: 'white',
                                border: 'none',
                                padding: '4px 8px',
                                borderRadius: '3px',
                                cursor: 'pointer',
                                fontSize: '11px'
                            }}
                        >
                            Verwijder
                        </button>
                    </div>

                    {schema.map(schemaField => renderSchemaField(schemaField, item, index))}
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
        </>
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

    // 6) Local state for immediate UI updates + debounced backend updates
    const [localFieldValues, setLocalFieldValues] = useState({});
    const [isInitialized, setIsInitialized] = useState(false);
    const debounceRef = useRef({});

    // 6b) State for field import wizard
    const [showImportWizard, setShowImportWizard] = useState(false);
    const [hasOrphanedFields, setHasOrphanedFields] = useState(false);

    // Initialize local state from meta when meta changes (but not from our own updates)
    useEffect(() => {
        if (meta && customFields.length > 0) {
            const initialValues = {};
            customFields.forEach(field => {
                const metaValue = meta[field.meta_key];
                // Use field default if meta value is empty/undefined
                const effectiveValue = (metaValue !== undefined)
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

    // Check for orphaned fields when template changes
    useEffect(() => {
        if (!postId || !currentTemplate) {
            setHasOrphanedFields(false);
            return;
        }

        apiFetch({
            path: `/nok/v1/page-part/${postId}/orphaned-fields?current_template=${currentTemplate}`
        }).then(response => {
            setHasOrphanedFields(response.sources?.length > 0);
        }).catch(() => setHasOrphanedFields(false));
    }, [postId, currentTemplate]);

    // Handle import from wizard
    const handleImport = (importData) => {
        const newMeta = {...meta, ...importData};
        editPost({meta: newMeta});

        // Update local state for immediate UI feedback
        setLocalFieldValues(prev => ({...prev, ...importData}));

        logger.log(NAME, `Imported ${Object.keys(importData).length} fields from previous template`);
    };

    const updateMetaField = (fieldName, value) => {
        // Don't trigger updates during initialization
        if (!isInitialized) {
            logger.log(NAME, `Skipping update during initialization for ${fieldName}`);
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
            logger.log(NAME, `Debounced update for ${fieldName}: ${value}`);

            // Update the editor meta
            const newMeta = {...meta, [fieldName]: value};
            editPost({meta: newMeta});

            // Clean up timeout reference
            delete debounceRef.current[fieldName];
        }, 500);
    };

    // 7) Render field based on type
    const renderField = (field) => {
        // Use local state value for immediate UI updates, fallback to meta value
        const fieldValue = localFieldValues[field.meta_key] ?? meta?.[field.meta_key] ?? '';

        switch (field.type) {
            case 'textarea':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <TextareaControl
                            value={fieldValue}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                            rows={3}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );

            case 'url':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <TextControl
                            type="url"
                            value={fieldValue}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize={true}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                            placeholder="https://..."
                        />
                    </FieldGroup>
                );

            case 'link':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        {field.description && (
                            <p style={helpStyle}>{field.description}</p>
                        )}
                        <LinkField
                            value={fieldValue}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                            placeholder="Search pages or enter URL..."
                        />
                    </FieldGroup>
                );

            case 'repeater':
                if (field.repeater_subtype === 'post') {
                    return (
                        <FieldGroup key={field.meta_key} label={field.label}>{field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                            <PostSelector
                                value={fieldValue}
                                onChange={(value) => updateMetaField(field.meta_key, value)}
                                postTypes={field.post_types || ['post']}
                                categories={field.categories || []}
                            />
                        </FieldGroup>
                    );
                }

                if (!field.schema || field.schema.length === 0) {
                    return (
                        <div key={field.meta_key} style={{
                            padding: '8px',
                            background: '#fff3cd',
                            border: '1px solid #ffc107',
                            borderRadius: '4px',
                            marginBottom: '16px'
                        }}>
                            <strong>Warning:</strong> Repeater field "{field.label}" has no schema defined.
                        </div>
                    );
                }

                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                        <RepeaterField
                            field={field}
                            schema={field.schema}
                            value={fieldValue}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                    </FieldGroup>
                );
            case 'icon-selector':
                const availableIcons = window.PagePartDesignSettings?.icons || {};
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <IconSelector
                            value={fieldValue}
                            icons={availableIcons}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                    </FieldGroup>
                );
            case 'color-selector':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <ColorSelector
                            value={fieldValue}
                            palette={field.palette}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );
            case 'image':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <ImageSelector
                            value={fieldValue}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );
            case 'select':
                const selectOptions = field.options || [];
                const selectLabels = field.option_labels || selectOptions; // Fallback to options if no labels
                const hasDefault = field.default && field.default !== '';

                // Only show "— Select —" if no default is defined
                const options = hasDefault
                    ? selectOptions.map((option, index) => ({
                        label: selectLabels[index] || option,
                        value: option
                    }))
                    : [
                        {label: '— Select —', value: ''},
                        ...selectOptions.map((option, index) => ({
                            label: selectLabels[index] || option,
                            value: option
                        }))
                    ];

                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <SelectControl
                            key={field.meta_key}
                            value={fieldValue}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize={true}
                            options={options}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );

            case 'checkbox':
                return (
                    <FieldGroup key={field.meta_key}>
                        <CheckboxControl
                            key={field.meta_key}
                            label={field.label}
                            help={field.description || ''}
                            checked={fieldValue === '1' || fieldValue === true}
                            onChange={(checked) => updateMetaField(field.meta_key, checked ? '1' : '0')}
                        />
                    </FieldGroup>
                );

            case 'taxonomy':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                        <TaxonomySelector
                            taxonomy={field.taxonomy}
                            value={fieldValue}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                            multiple={field.multiple !== false}
                        />
                    </FieldGroup>
                );

            case 'text':
            default:
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <TextControl
                            key={field.meta_key}
                            value={fieldValue}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize={true}
                            onChange={(value) => updateMetaField(field.meta_key, value)}
                        />
                        {field.description && (
                            <p style={helpStyle}>
                                {field.description}
                            </p>
                        )}
                    </FieldGroup>
                );
        }
    };

    function cleanCustomFields(currentTemplate, retainCurrent = false) {
        const fieldsToDelete = [];

        for (const key in meta) {
            if (/^[a-z0-9-]+_[a-z0-9_]+$/.test(key) && key !== 'design_slug') {
                if (!(retainCurrent && key.startsWith(currentTemplate + '_'))) {
                    fieldsToDelete.push(key);
                    logger.warn(NAME, `Will remove ${key}`);
                } else {
                    logger.log(NAME, `Will not remove ${key}`);
                }
            }
        }

        if (fieldsToDelete.length === 0) {
            return;
        }

        wp.apiFetch({
            path: `/nok/v1/page-part/${postId}/prune-fields`,
            method: 'POST',
            data: {retain_current: retainCurrent}
        }).then(() => {
            logger.log(NAME, `Deleted ${fieldsToDelete.length} field(s)`);

            // Update editor state to reflect deletion
            const newMeta = {...meta};
            fieldsToDelete.forEach(key => delete newMeta[key]);
            editPost({meta: newMeta});
        });
    }

    return (
        <PluginDocumentSettingPanel
            name="page-part-design"
            title="NOK Design template"
            icon="admin-generic"
        >
            <SelectControl
                label="Template"
                value={currentTemplate}
                options={options}
                __nextHasNoMarginBottom
                __next40pxDefaultSize={true}
                onChange={(newSlug) => {
                    logger.log(NAME, `→ setting design_slug to "${newSlug}"`);
                    // Update the editor meta
                    const newMeta = {...meta, design_slug: newSlug};
                    editPost({meta: newMeta});
                }}
            />

            {/* Import from previous template button */}
            {hasOrphanedFields && (
                <Button
                    variant="secondary"
                    onClick={() => setShowImportWizard(true)}
                    style={{width: '100%', justifyContent: 'center', marginTop: '12px'}}
                >
                    Importeer...
                </Button>
            )}

            {/* Field Import Wizard Modal */}
            <FieldImportWizard
                isOpen={showImportWizard}
                onClose={() => setShowImportWizard(false)}
                postId={postId}
                currentTemplate={currentTemplate}
                targetFields={customFields}
                onImport={handleImport}
            />

            {/* Render custom fields for selected template */}
            {customFields.length > 0 && (
                <Fragment>
                    <hr style={{margin: '16px 0'}}/>
                    <h2 style={fieldStyle}>
                        Template options
                    </h2>
                    {customFields.map(renderField)}
                </Fragment>
            )}

            {customFields.length > 0 && (
                <Fragment>
                    <hr style={{margin: '16px 0'}}/>

                    <Button
                        isDestructive
                        variant="secondary"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize={true}
                        onClick={() => {
                            if (confirm('Wil je alle velden die niet langer door de huidige template worden gebruikt verwijderen?')) {
                                logger.log(NAME, 'Pruning unused template fields');
                                cleanCustomFields(true);
                            }
                        }}
                        style={{width: '100%', justifyContent: 'center', marginBottom: '16px'}}
                    >
                        Reset ongebruikt
                    </Button>

                    <Button
                        isDestructive
                        variant="secondary"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize={true}
                        onClick={() => {
                            if (confirm('Wil je de velden voor ALLE templates voor deze page part terugzetten naar de standaardwaarden?')) {
                                logger.log(NAME, 'Resetting template fields to defaults');
                                cleanCustomFields();
                            }
                        }}
                        style={{width: '100%', justifyContent: 'center', marginBottom: '16px'}}
                    >
                        Reset opties
                    </Button>
                </Fragment>
            )}
        </PluginDocumentSettingPanel>
    );
}

registerPlugin('page-part-design', {
    render: DesignSlugPanel,
});