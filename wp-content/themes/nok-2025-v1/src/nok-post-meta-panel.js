import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/editor';
import {TextControl, TextareaControl, CheckboxControl, SelectControl, Button, TimePicker} from '@wordpress/components';
import {useSelect, useDispatch} from '@wordpress/data';
import {useMemo, useState} from '@wordpress/element';

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

    const {meta, postType, categories, taxonomyTerms, vestigingenPosts} = useSelect((select) => {
        const editor = select('core/editor');
        const coreSelect = select('core');

        // Query vestiging posts for post_select field
        const vestigingen = coreSelect.getEntityRecords('postType', 'vestiging', {
            per_page: -1,
            status: 'publish',
            orderby: 'title',
            order: 'asc'
        }) || [];

        return {
            meta: editor.getEditedPostAttribute('meta') || {},
            postType: editor.getCurrentPostType(),
            categories: editor.getEditedPostAttribute('categories') || [],
            // Get all taxonomy terms for the post
            taxonomyTerms: editor.getEditedPostAttribute('terms') || {},
            vestigingenPosts: vestigingen
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

            case 'post_select':
                // Build options based on post_type specified in field configuration
                const postType = field.post_type || 'vestiging';
                const posts = postType === 'vestiging' ? vestigingenPosts : [];

                const options = [
                    {label: field.placeholder || 'Selecteer...', value: ''},
                    ...posts.map(post => ({
                        label: post.title?.rendered || `Post #${post.id}`,
                        value: post.id.toString()
                    }))
                ];

                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <SelectControl
                            value={value ? value.toString() : ''}
                            options={options}
                            onChange={(newValue) => updateMeta(metaKey, newValue ? parseInt(newValue) : '')}
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

            case 'opening_hours':
                const parseOpeningHours = (val) => {
                    if (!val) return {};
                    if (typeof val === 'object') return val;
                    try {
                        return JSON.parse(val);
                    } catch (e) {
                        return {};
                    }
                };

                const hoursData = parseOpeningHours(value);
                const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                const dayLabels = {
                    weekdays: 'Werkdagen (ma-vr)',
                    monday: 'Maandag',
                    tuesday: 'Dinsdag',
                    wednesday: 'Woensdag',
                    thursday: 'Donderdag',
                    friday: 'Vrijdag',
                    saturday: 'Zaterdag',
                    sunday: 'Zondag'
                };

                const toggleDay = (day) => {
                    const updated = {...hoursData};
                    if (updated[day] && updated[day].length > 0) {
                        updated[day] = [];
                    } else {
                        updated[day] = [{opens: '08:30', closes: '17:00'}];
                    }
                    updateMeta(metaKey, updated);
                };

                const toggleDayClosed = (day) => {
                    const updated = {...hoursData};
                    if (!updated[day] || updated[day].length === 0) {
                        updated[day] = [{closed: true}];
                    } else {
                        const current = updated[day][0];
                        if (current.closed) {
                            // Toggle from closed to open
                            updated[day] = [{opens: '08:30', closes: '17:00'}];
                        } else {
                            // Toggle from open to closed
                            updated[day] = [{closed: true}];
                        }
                    }
                    updateMeta(metaKey, updated);
                };

                const updateDayTime = (day, timeType, newTime) => {
                    const updated = {...hoursData};
                    if (!updated[day] || updated[day].length === 0) {
                        updated[day] = [{opens: '08:30', closes: '17:00'}];
                    }
                    updated[day][0][timeType] = newTime;
                    updateMeta(metaKey, updated);
                };

                const renderDayRow = (day, label, highlightColor = null) => {
                    const dayHours = hoursData[day] || [];
                    const isOpen = dayHours.length > 0;
                    const hours = isOpen ? dayHours[0] : {opens: '08:30', closes: '17:00'};
                    const isClosed = isOpen && hours.closed === true;
                    const isWeekdaysTemplate = day === 'weekdays';

                    return (
                        <div key={day} style={{
                            marginBottom: '12px',
                            padding: '12px',
                            border: highlightColor ? `2px solid ${highlightColor}` : '1px solid #ddd',
                            borderRadius: '4px',
                            background: isOpen ? (highlightColor ? '#f0f7ff' : '#f9f9f9') : '#fff'
                        }}>
                            <div style={{display: 'flex', alignItems: 'center', marginBottom: isOpen ? '8px' : '0'}}>
                                <CheckboxControl
                                    label={label}
                                    checked={isOpen}
                                    onChange={() => toggleDay(day)}
                                    __nextHasNoMarginBottom
                                />
                            </div>
                            {isOpen && (
                                <div style={{paddingLeft: '30px'}}>
                                    {/* Only show "Gesloten" checkbox for individual days, not for weekdays template */}
                                    {!isWeekdaysTemplate && (
                                        <CheckboxControl
                                            label="Gesloten"
                                            checked={isClosed}
                                            onChange={() => toggleDayClosed(day)}
                                            __nextHasNoMarginBottom
                                        />
                                    )}
                                    {!isClosed && (
                                        <div style={{display: 'flex', gap: '8px', marginTop: isWeekdaysTemplate ? '0' : '8px'}}>
                                            <div style={{flex: 1}}>
                                                <div style={{fontSize: '11px', marginBottom: '4px', color: '#757575'}}>Van</div>
                                                <TextControl
                                                    type="time"
                                                    value={hours.opens || '08:30'}
                                                    onChange={(newTime) => updateDayTime(day, 'opens', newTime)}
                                                    __nextHasNoMarginBottom
                                                    __next40pxDefaultSize
                                                />
                                            </div>
                                            <div style={{flex: 1}}>
                                                <div style={{fontSize: '11px', marginBottom: '4px', color: '#757575'}}>Tot</div>
                                                <TextControl
                                                    type="time"
                                                    value={hours.closes || '17:00'}
                                                    onChange={(newTime) => updateDayTime(day, 'closes', newTime)}
                                                    __nextHasNoMarginBottom
                                                    __next40pxDefaultSize
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                };

                return (
                    <FieldGroup key={field.meta_key} label={field.label}>
                        <div style={{marginTop: '8px'}}>
                            {/* Weekdays template */}
                            {renderDayRow('weekdays', dayLabels.weekdays, '#2271b1')}

                            {/* Tip from description field */}
                            {field.description && (
                                <p style={helpStyle}>
                                    {field.description}
                                </p>
                            )}

                            {/* Individual days */}
                            {days.map(day => renderDayRow(day, dayLabels[day]))}
                        </div>
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