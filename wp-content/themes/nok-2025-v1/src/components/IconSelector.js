import { useState, useRef } from '@wordpress/element';
import { Popover } from '@wordpress/components';

const IconSelector = ({ value, onChange, icons }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const triggerRef = useRef(null);

    const iconStyles = `
        svg.nok-icon {
            width: 100%;
            height: 100%;
            display: block;
        }
    `;

    // Flatten icons from {ui: {...}, nok: {...}} structure
    const allIcons = Object.entries(icons).flatMap(entry =>
        Object.entries(entry[1]).map(iconEntry => ({
            name: iconEntry[0],
            svg: iconEntry[1],
            category: entry[0]
        }))
    );

    const filteredIcons = allIcons.filter(icon =>
        icon.name.toLowerCase().includes(search.toLowerCase())
    );

    const selectedIcon = allIcons.find(icon => icon.name === value);

    return (
        <>
        <style>{iconStyles}</style>
        <div style={{ marginBottom: '12px' }}>
            <label style={{
                display: 'block',
                fontSize: '12px',
                fontWeight: '600',
                marginBottom: '8px'
            }}>
                Icon
            </label>

            {/* Selected icon preview */}
            <div style={{ position: 'relative' }}>
                <div
                    ref={triggerRef}
                    onClick={() => setIsOpen(!isOpen)}
                    style={{
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        padding: '8px',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        background: '#fff',
                        minHeight: '40px'
                    }}
                >
                    {selectedIcon ? (
                        <>
                            <div
                                style={{ width: '24px', height: '24px', flexShrink: 0 }}
                                dangerouslySetInnerHTML={{ __html: selectedIcon.svg }}
                            />
                            <span style={{ fontSize: '13px' }}>{selectedIcon.name}</span>
                        </>
                    ) : (
                        <span style={{ fontSize: '13px', color: '#757575' }}>Select an icon...</span>
                    )}
                </div>

                {selectedIcon && (
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            onChange('');
                        }}
                        style={{
                            position: 'absolute',
                            right: '8px',
                            top: '50%',
                            transform: 'translateY(-50%)',
                            background: '#d63638',
                            color: '#fff',
                            border: 'none',
                            borderRadius: '3px',
                            padding: '4px 8px',
                            fontSize: '11px',
                            cursor: 'pointer',
                            fontWeight: '500'
                        }}
                        title="Clear icon"
                    >
                        ×
                    </button>
                )}
            </div>

            {/* Dropdown using WordPress Popover */}
            {isOpen && (
                <Popover
                    anchor={triggerRef.current}
                    onClose={() => {
                        setIsOpen(false);
                        setSearch('');
                    }}
                    placement="bottom-start"
                    shift={true}
                    resize={true}
                >
                    <div style={{
                        background: '#fff',
                        maxHeight: '400px',
                        width: '280px',
                        overflow: 'hidden'
                    }}>
                        {/* Search */}
                        <input
                            type="text"
                            placeholder="Search icons..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            style={{
                                width: '100%',
                                padding: '8px',
                                border: 'none',
                                borderBottom: '1px solid #ddd',
                                fontSize: '13px',
                                outline: 'none',
                                boxSizing: 'border-box'
                            }}
                            autoFocus
                        />

                        {/* Icon grid */}
                        <div style={{
                            overflowY: 'auto',
                            maxHeight: '350px',
                            padding: '8px'
                        }}>
                            {filteredIcons.length === 0 ? (
                                <div style={{ padding: '16px', textAlign: 'center', color: '#757575', fontSize: '13px' }}>
                                    No icons found
                                </div>
                            ) : (
                                <div style={{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(4, 1fr)',
                                    gap: '8px'
                                }}>
                                    {filteredIcons.map(icon => (
                                        <div
                                            key={icon.name}
                                            onClick={() => {
                                                onChange(icon.name);
                                                setIsOpen(false);
                                                setSearch('');
                                            }}
                                            title={icon.name}
                                            style={{
                                                padding: '2px',
                                                border: '1px solid #ddd',
                                                borderRadius: '4px',
                                                cursor: 'pointer',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                background: value === icon.name ? '#e0f0ff' : '#fff',
                                                transition: 'all 0.15s',
                                                aspectRatio: '1'
                                            }}
                                            onMouseEnter={(e) => {
                                                if (value !== icon.name) {
                                                    e.currentTarget.style.background = '#f5f5f5';
                                                }
                                            }}
                                            onMouseLeave={(e) => {
                                                if (value !== icon.name) {
                                                    e.currentTarget.style.background = '#fff';
                                                }
                                            }}
                                        >
                                            <div
                                                style={{ width: '3em', height: '3em' }}
                                                dangerouslySetInnerHTML={{ __html: icon.svg }}
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </Popover>
            )}
        </div>
        </>
    );
};

export default IconSelector;
