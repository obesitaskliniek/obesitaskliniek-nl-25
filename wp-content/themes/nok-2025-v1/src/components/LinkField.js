/**
 * LinkField - URL field with autocomplete for internal pages/posts/categories/archives
 *
 * Storage formats:
 * - "post:123" for posts, pages, and CPTs
 * - "term:123" for categories and tags
 * - "archive:post_type" for post type archive pages
 * - Raw URLs for external links
 */

import {useState, useEffect, useRef} from '@wordpress/element';
import {TextControl, Spinner, Button} from '@wordpress/components';
import {link, linkOff} from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Debounce helper
 */
const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};

/**
 * Available archive pages (post type archives)
 */
const ARCHIVE_PAGES = [
    {slug: 'kennisbank', title: 'Kennisbank (archief)', type: 'archive'},
    {slug: 'voorlichting', title: 'Voorlichtingen (archief)', type: 'archive'},
    {slug: 'vestiging', title: 'Vestigingen (archief)', type: 'archive'},
];

/**
 * Parse stored value to determine type and display value
 */
const parseValue = (value) => {
    if (!value) return {type: 'none', id: null, slug: null, url: ''};

    if (value.startsWith('post:')) {
        const id = parseInt(value.replace('post:', ''), 10);
        return {type: 'post', id, slug: null, url: ''};
    }

    if (value.startsWith('term:')) {
        const id = parseInt(value.replace('term:', ''), 10);
        return {type: 'term', id, slug: null, url: ''};
    }

    if (value.startsWith('archive:')) {
        const slug = value.replace('archive:', '');
        return {type: 'archive', id: null, slug, url: ''};
    }

    return {type: 'url', id: null, slug: null, url: value};
};

/**
 * LinkField Component
 */
const LinkField = ({value, onChange, placeholder = 'Search or enter URL...'}) => {
    const [inputValue, setInputValue] = useState('');
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [linkedPost, setLinkedPost] = useState(null);
    const wrapperRef = useRef(null);

    const parsed = parseValue(value);

    // Fetch linked post/term/archive details on mount/value change
    useEffect(() => {
        if (parsed.type === 'post' && parsed.id) {
            // Try endpoints in sequence: posts, pages, then CPTs
            const tryEndpoints = async () => {
                const endpoints = [
                    {path: `/wp/v2/posts/${parsed.id}`, type: 'post'},
                    {path: `/wp/v2/pages/${parsed.id}`, type: 'page'},
                    {path: `/wp/v2/kennisbank/${parsed.id}`, type: 'kennisbank'},
                    {path: `/wp/v2/voorlichting/${parsed.id}`, type: 'voorlichting'},
                    {path: `/wp/v2/vestigingen/${parsed.id}`, type: 'vestiging'},
                ];

                for (const endpoint of endpoints) {
                    try {
                        const item = await apiFetch({path: endpoint.path});
                        setLinkedPost({id: item.id, title: item.title.rendered, url: item.link, itemType: endpoint.type});
                        return;
                    } catch (e) {
                        // Continue to next endpoint
                    }
                }
                setLinkedPost(null);
            };
            tryEndpoints();
        } else if (parsed.type === 'term' && parsed.id) {
            // Try categories first, then tags
            apiFetch({path: `/wp/v2/categories/${parsed.id}`})
                .then(term => setLinkedPost({id: term.id, title: term.name, url: term.link, itemType: 'category'}))
                .catch(() => {
                    apiFetch({path: `/wp/v2/tags/${parsed.id}`})
                        .then(term => setLinkedPost({id: term.id, title: term.name, url: term.link, itemType: 'tag'}))
                        .catch(() => setLinkedPost(null));
                });
        } else if (parsed.type === 'archive' && parsed.slug) {
            // Find archive in static list
            const archive = ARCHIVE_PAGES.find(a => a.slug === parsed.slug);
            if (archive) {
                setLinkedPost({id: null, title: archive.title, url: null, itemType: 'archive', slug: parsed.slug});
            } else {
                setLinkedPost(null);
            }
        } else {
            setLinkedPost(null);
            if (parsed.type === 'url') {
                setInputValue(parsed.url);
            }
        }
    }, [value]);

    // Search for posts/pages/CPTs/categories/archives
    const searchContent = debounce(async (search) => {
        if (!search || search.length < 2) {
            setSuggestions([]);
            return;
        }

        setLoading(true);

        try {
            // Search all content types in parallel
            const [posts, pages, kennisbank, voorlichting, vestigingen, categories] = await Promise.all([
                apiFetch({path: `/wp/v2/posts?search=${encodeURIComponent(search)}&per_page=5`}),
                apiFetch({path: `/wp/v2/pages?search=${encodeURIComponent(search)}&per_page=5`}),
                apiFetch({path: `/wp/v2/kennisbank?search=${encodeURIComponent(search)}&per_page=5`}).catch(() => []),
                apiFetch({path: `/wp/v2/voorlichting?search=${encodeURIComponent(search)}&per_page=5`}).catch(() => []),
                apiFetch({path: `/wp/v2/vestigingen?search=${encodeURIComponent(search)}&per_page=5`}).catch(() => []),
                apiFetch({path: `/wp/v2/categories?search=${encodeURIComponent(search)}&per_page=5`})
            ]);

            // Filter archive pages by search term
            const searchLower = search.toLowerCase();
            const matchingArchives = ARCHIVE_PAGES.filter(a =>
                a.title.toLowerCase().includes(searchLower) ||
                a.slug.toLowerCase().includes(searchLower)
            );

            const results = [
                ...posts.map(p => ({id: p.id, title: p.title.rendered, type: 'post', url: p.link, storeAs: 'post'})),
                ...pages.map(p => ({id: p.id, title: p.title.rendered, type: 'page', url: p.link, storeAs: 'post'})),
                ...kennisbank.map(p => ({id: p.id, title: p.title.rendered, type: 'kennisbank', url: p.link, storeAs: 'post'})),
                ...voorlichting.map(p => ({id: p.id, title: p.title.rendered, type: 'voorlichting', url: p.link, storeAs: 'post'})),
                ...vestigingen.map(p => ({id: p.id, title: p.title.rendered, type: 'vestiging', url: p.link, storeAs: 'post'})),
                ...categories.map(c => ({id: c.id, title: c.name, type: 'category', url: c.link, storeAs: 'term'})),
                ...matchingArchives.map(a => ({id: a.slug, title: a.title, type: 'archive', url: null, storeAs: 'archive'}))
            ];

            setSuggestions(results);
        } catch (error) {
            console.error('Link search failed:', error);
            setSuggestions([]);
        }

        setLoading(false);
    }, 300);

    // Handle input change
    const handleInputChange = (newValue) => {
        setInputValue(newValue);
        setShowSuggestions(true);

        // Search for content
        searchContent(newValue);
    };

    // Handle selecting a post/page/category/archive
    const handleSelect = (item) => {
        const prefix = item.storeAs || 'post';
        onChange(`${prefix}:${item.id}`);
        setInputValue('');
        setSuggestions([]);
        setShowSuggestions(false);
    };

    // Handle manual URL submission
    const handleBlur = () => {
        // Delay to allow click on suggestions
        setTimeout(() => {
            setShowSuggestions(false);

            // If input looks like a URL and nothing is selected, save as URL
            if (inputValue && !linkedPost) {
                const trimmed = inputValue.trim();
                if (trimmed.startsWith('/') || trimmed.startsWith('http') || trimmed.startsWith('#')) {
                    onChange(trimmed);
                }
            }
        }, 200);
    };

    // Handle clearing the link
    const handleClear = () => {
        onChange('');
        setInputValue('');
        setLinkedPost(null);
    };

    // Click outside to close suggestions
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Render linked post/archive display
    if (linkedPost) {
        // Get path to display - archives don't have URL until PHP resolves them
        let pathDisplay = '';
        if (linkedPost.url) {
            try {
                pathDisplay = new URL(linkedPost.url).pathname;
            } catch (e) {
                pathDisplay = linkedPost.url;
            }
        } else if (linkedPost.itemType === 'archive') {
            pathDisplay = `/${linkedPost.slug}/`;
        }

        return (
            <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                padding: '8px 12px',
                background: linkedPost.itemType === 'archive' ? '#e8f4e8' : '#f0f0f0',
                borderRadius: '4px',
                border: `1px solid ${linkedPost.itemType === 'archive' ? '#a3d9a3' : '#ddd'}`
            }}>
                <span style={{flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'}}>
                    <strong dangerouslySetInnerHTML={{__html: linkedPost.title}} />
                    {pathDisplay && (
                        <span style={{color: '#666', marginLeft: '8px', fontSize: '11px'}}>
                            {pathDisplay}
                        </span>
                    )}
                </span>
                <Button
                    icon={linkOff}
                    label="Remove link"
                    isSmall
                    onClick={handleClear}
                />
            </div>
        );
    }

    // Render URL display (for external links)
    if (parsed.type === 'url' && parsed.url) {
        return (
            <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                padding: '8px 12px',
                background: '#fff8e5',
                borderRadius: '4px',
                border: '1px solid #f0c36d'
            }}>
                <span style={{flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'}}>
                    {parsed.url}
                </span>
                <Button
                    icon={linkOff}
                    label="Remove link"
                    isSmall
                    onClick={handleClear}
                />
            </div>
        );
    }

    // Render search input
    return (
        <div ref={wrapperRef} style={{position: 'relative'}}>
            <div style={{display: 'flex', alignItems: 'center', gap: '4px'}}>
                <TextControl
                    value={inputValue}
                    onChange={handleInputChange}
                    onFocus={() => setShowSuggestions(true)}
                    onBlur={handleBlur}
                    placeholder={placeholder}
                    style={{flex: 1, marginBottom: 0}}
                    __nextHasNoMarginBottom
                />
                {loading && <Spinner />}
            </div>

            {showSuggestions && suggestions.length > 0 && (
                <div style={{
                    position: 'absolute',
                    top: '100%',
                    left: 0,
                    right: 0,
                    background: '#fff',
                    border: '1px solid #ddd',
                    borderRadius: '4px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                    zIndex: 1000,
                    maxHeight: '200px',
                    overflow: 'auto'
                }}>
                    {suggestions.map(item => (
                        <div
                            key={`${item.type}-${item.id}`}
                            onClick={() => handleSelect(item)}
                            style={{
                                padding: '8px 12px',
                                cursor: 'pointer',
                                borderBottom: '1px solid #eee',
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center'
                            }}
                            onMouseEnter={(e) => e.currentTarget.style.background = '#f5f5f5'}
                            onMouseLeave={(e) => e.currentTarget.style.background = '#fff'}
                        >
                            <span dangerouslySetInnerHTML={{__html: item.title}} />
                            <span style={{
                                fontSize: '10px',
                                color: '#666',
                                background: '#eee',
                                padding: '2px 6px',
                                borderRadius: '3px',
                                textTransform: 'uppercase'
                            }}>
                                {item.type}
                            </span>
                        </div>
                    ))}
                </div>
            )}

            {showSuggestions && inputValue && suggestions.length === 0 && !loading && (
                <div style={{
                    fontSize: '12px',
                    color: '#666',
                    marginTop: '4px'
                }}>
                    No results. Press Tab or click away to save as URL.
                </div>
            )}
        </div>
    );
};

export default LinkField;
