import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/editor';
import {TextControl, TextareaControl, CheckboxControl} from '@wordpress/components';
import {useSelect, useDispatch} from '@wordpress/data';
import {useMemo} from '@wordpress/element';

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

const PostMetaPanel = () => {
    const {editPost} = useDispatch('core/editor');

    const {meta, postType, categories, taxonomyTerms} = useSelect((select) => {
        const editor = select('core/editor');
        return {
            meta: editor.getEditedPostAttribute('meta') || {},
            postType: editor.getCurrentPostType(),
            categories: editor.getEditedPostAttribute('categories') || [],
            // Get all taxonomy terms for the post
            taxonomyTerms: editor.getEditedPostAttribute('terms') || {}
        };
    });

    // Get field configuration from localized script
    const allFields = window.nokPostMetaFields?.fields || {};

    // Filter fields based on category/taxonomy constraints
    const visibleFields = useMemo(() => {
        const filtered = {};

        Object.entries(allFields).forEach(([metaKey, field]) => {
            let shouldShow = true;

            // Category filtering
            if (field.categories && field.categories.length > 0) {
                // Show only if post has at least one matching category
                const hasMatchingCategory = field.categories.some(
                    categoryId => categories.includes(categoryId)
                );
                shouldShow = shouldShow && hasMatchingCategory;
            }

            // Custom taxonomy filtering
            if (field.taxonomies && Object.keys(field.taxonomies).length > 0) {
                Object.entries(field.taxonomies).forEach(([taxonomy, requiredTerms]) => {
                    if (requiredTerms.length > 0) {
                        const postTerms = taxonomyTerms[taxonomy] || [];
                        const hasMatchingTerm = requiredTerms.some(
                            termId => postTerms.includes(termId)
                        );
                        shouldShow = shouldShow && hasMatchingTerm;
                    }
                });
            }

            if (shouldShow) {
                filtered[metaKey] = field;
            }
        });

        return filtered;
    }, [allFields, categories, taxonomyTerms]);

    const fieldCount = Object.keys(visibleFields).length;

    if (fieldCount === 0) {
        return null; // Hide panel completely if no fields match
    }

    const updateMeta = (key, value) => {
        editPost({
            meta: {
                ...meta,
                [key]: value
            }
        });
    };

    const renderField = (metaKey, field) => {
        const value = meta[metaKey] ?? field.default ?? '';
        const commonProps = {
            value,
            onChange: (newValue) => updateMeta(metaKey, newValue),
        };

        const labelStyle = {
            display: 'block',
            fontSize: '11px',
            fontWeight: '600',
            textTransform: 'uppercase',
            marginBottom: '8px',
            color: '#1e1e1e'
        };

        switch (field.type) {
            case 'textarea':
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <TextareaControl
                            {...commonProps}
                            placeholder={field.placeholder}
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
                            {...commonProps}
                            type="url"
                            placeholder={field.placeholder || 'https://...'}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
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
                            label={field.label}
                            help={field.description || ''}
                            checked={value === '1' || value === 1 || value === true}
                            onChange={(checked) => updateMeta(metaKey, checked ? '1' : '0')}
                        />
                    </FieldGroup>
                );

            case 'text':
            default:
                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <TextControl
                            {...commonProps}
                            placeholder={field.placeholder}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
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

    return (
        <PluginDocumentSettingPanel
            name="nok-post-meta"
            title="NOK Extra instellingen"
            icon="admin-generic"
        >
            {Object.entries(visibleFields).map(([metaKey, field]) =>
                renderField(metaKey, field)
            )}
        </PluginDocumentSettingPanel>
    );
};

registerPlugin('nok-post-meta-panel', {
    render: PostMetaPanel
});