import { useState } from '@wordpress/element';

const IconSelector = ({ value, onChange, icons }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');

    const iconStyles = `
        svg.nok-icon {
            width: 100%;
            height: 100%;
            display: block;
        }
    `;

    // Flatten icons from {ui: {...}, nok: {...}} structure
    const allIcons = Object.entries(icons).flatMap(([category, iconList]) =>
        Object.entries(iconList).map(([name, svg]) => ({ name, svg, category }))
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
            <div
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

            {/* Dropdown */}
            {isOpen && (
                <div style={{
                    position: 'absolute',
                    zIndex: 9999,
                    background: '#fff',
                    border: '1px solid #ddd',
                    borderRadius: '4px',
                    marginTop: '4px',
                    maxHeight: '400px',
                    width: '300px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
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
                            outline: 'none'
                        }}
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
            )}
        </div>
        </>
    );
};

export default IconSelector;