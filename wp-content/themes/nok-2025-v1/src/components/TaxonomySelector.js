/**
 * Taxonomy Selector Component
 *
 * Fetches terms from a taxonomy REST API endpoint and provides
 * a multi-select or single-select checkbox interface.
 *
 * @package NOK2025_V1
 * @since 1.0.0
 */

import {useState, useEffect} from '@wordpress/element';
import {CheckboxControl, Button} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * TaxonomySelector - Dynamic taxonomy term selector
 *
 * @param {Object} props
 * @param {string} props.taxonomy - Taxonomy slug (e.g., 'kennisbank_categories')
 * @param {string} props.value - Current value (comma-separated term IDs or empty for "all")
 * @param {Function} props.onChange - Callback when selection changes
 * @param {boolean} props.multiple - Allow multiple selections (default: true)
 * @param {string} props.label - Field label
 */
const TaxonomySelector = ({taxonomy, value, onChange, multiple = true, label}) => {
    const [terms, setTerms] = useState([]);
    const [isLoading, setIsLoading] = useState(true);

    // Parse current value to array of IDs
    const selectedIds = value
        ? value.split(',').map(id => parseInt(id.trim(), 10)).filter(id => !isNaN(id))
        : [];

    // Fetch terms on mount
    useEffect(() => {
        setIsLoading(true);
        apiFetch({path: `/wp/v2/${taxonomy}?per_page=100&orderby=name&order=asc`})
            .then(fetchedTerms => {
                setTerms(fetchedTerms);
                setIsLoading(false);
            })
            .catch(error => {
                console.error('Failed to fetch taxonomy terms:', error);
                setIsLoading(false);
            });
    }, [taxonomy]);

    const handleChange = (termId, isChecked) => {
        let newIds;
        if (multiple) {
            if (isChecked) {
                newIds = [...selectedIds, termId];
            } else {
                newIds = selectedIds.filter(id => id !== termId);
            }
        } else {
            newIds = isChecked ? [termId] : [];
        }
        // Return empty string for "all" (no filter), otherwise comma-separated IDs
        onChange(newIds.length > 0 ? newIds.join(',') : '');
    };

    if (isLoading) {
        return <p style={{fontSize: '12px', color: '#666'}}>Categorieën laden...</p>;
    }

    if (terms.length === 0) {
        return <p style={{fontSize: '12px', color: '#666'}}>Geen categorieën gevonden.</p>;
    }

    return (
        <div style={{marginBottom: '12px'}}>
            {label && (
                <label style={{
                    display: 'block',
                    fontSize: '11px',
                    fontWeight: '600',
                    marginBottom: '8px',
                    color: '#1e1e1e'
                }}>
                    {label}
                </label>
            )}
            <p style={{fontSize: '11px', color: '#666', marginBottom: '8px'}}>
                {selectedIds.length === 0
                    ? 'Alle categorieën (geen filter)'
                    : `${selectedIds.length} categorie${selectedIds.length !== 1 ? 'ën' : ''} geselecteerd`}
            </p>
            <div style={{
                maxHeight: '200px',
                overflowY: 'auto',
                border: '1px solid #ddd',
                borderRadius: '4px',
                padding: '8px',
                backgroundColor: '#fff'
            }}>
                {terms.map(term => (
                    <CheckboxControl
                        key={term.id}
                        label={term.name}
                        checked={selectedIds.includes(term.id)}
                        onChange={(checked) => handleChange(term.id, checked)}
                        __nextHasNoMarginBottom
                    />
                ))}
            </div>
            {selectedIds.length > 0 && (
                <Button
                    variant="link"
                    isDestructive
                    onClick={() => onChange('')}
                    style={{marginTop: '8px', fontSize: '12px'}}
                >
                    Selectie wissen (alle tonen)
                </Button>
            )}
        </div>
    );
};

export default TaxonomySelector;
