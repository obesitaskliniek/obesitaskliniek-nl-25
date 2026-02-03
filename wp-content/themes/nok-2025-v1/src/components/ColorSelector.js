import { useState, useRef } from '@wordpress/element';
import { Popover } from '@wordpress/components';

/**
 * ColorSelector - Visual color swatch picker component
 *
 * Displays a dropdown with color swatches for selecting from predefined palettes.
 * Uses WordPress Popover for proper positioning within the editor sidebar.
 *
 * @param {Object} props Component props
 * @param {string} props.value Current selected value (CSS class string)
 * @param {Function} props.onChange Callback when selection changes
 * @param {string} props.palette Palette name (e.g., 'backgrounds', 'text')
 */
const ColorSelector = ({ value, onChange, palette }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const triggerRef = useRef(null);

    // Get palette from global PagePartDesignSettings
    const palettes = window.PagePartDesignSettings?.colorPalettes || {};
    const paletteColors = palettes[palette] || [];

    // Filter colors by search term
    const filteredColors = paletteColors.filter(color =>
        color.label.toLowerCase().includes(search.toLowerCase())
    );

    // Find selected color info
    const selectedColor = paletteColors.find(color => color.value === value);

    // Render a color swatch
    const renderSwatch = (color, size = '24px') => {
        const isTransparent = !color || color === 'transparent' || color === 'inherit';

        return (
            <div
                style={{
                    width: size,
                    height: size,
                    borderRadius: '4px',
                    border: '1px solid #ccc',
                    flexShrink: 0,
                    background: isTransparent
                        ? 'repeating-linear-gradient(45deg, #fff, #fff 4px, #eee 4px, #eee 8px)'
                        : color,
                    boxShadow: 'inset 0 0 0 1px rgba(0,0,0,0.1)',
                }}
            />
        );
    };

    return (
        <div style={{ marginBottom: '12px' }}>
            {/* Selected color preview */}
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
                    {selectedColor ? (
                        <>
                            {renderSwatch(selectedColor.color)}
                            <span style={{ fontSize: '13px' }}>{selectedColor.label}</span>
                        </>
                    ) : value ? (
                        <>
                            {renderSwatch(null)}
                            <span style={{ fontSize: '13px', color: '#757575' }}>{value}</span>
                        </>
                    ) : (
                        <span style={{ fontSize: '13px', color: '#757575' }}>Selecteer kleur...</span>
                    )}
                </div>

                {selectedColor && (
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
                        title="Wis selectie"
                    >
                        &times;
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
                            placeholder="Zoek kleur..."
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

                        {/* Color grid */}
                        <div style={{
                            overflowY: 'auto',
                            maxHeight: '350px',
                            padding: '8px'
                        }}>
                            {filteredColors.length === 0 ? (
                                <div style={{ padding: '16px', textAlign: 'center', color: '#757575', fontSize: '13px' }}>
                                    Geen kleuren gevonden
                                </div>
                            ) : (
                                <div style={{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(4, 1fr)',
                                    gap: '8px'
                                }}>
                                    {filteredColors.map(color => (
                                        <div
                                            key={color.value || 'empty'}
                                            onClick={() => {
                                                onChange(color.value);
                                                setIsOpen(false);
                                                setSearch('');
                                            }}
                                            title={color.label}
                                            style={{
                                                padding: '4px',
                                                border: value === color.value ? '2px solid #007cba' : '1px solid #ddd',
                                                borderRadius: '4px',
                                                cursor: 'pointer',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                gap: '4px',
                                                background: value === color.value ? '#e0f0ff' : '#fff',
                                                transition: 'all 0.15s',
                                                minHeight: '50px'
                                            }}
                                            onMouseEnter={(e) => {
                                                if (value !== color.value) {
                                                    e.currentTarget.style.background = '#f5f5f5';
                                                }
                                            }}
                                            onMouseLeave={(e) => {
                                                if (value !== color.value) {
                                                    e.currentTarget.style.background = '#fff';
                                                }
                                            }}
                                        >
                                            {renderSwatch(color.color, '32px')}
                                            <span style={{
                                                fontSize: '10px',
                                                textAlign: 'center',
                                                lineHeight: '1.2',
                                                color: '#333',
                                                maxWidth: '100%',
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                whiteSpace: 'nowrap'
                                            }}>
                                                {color.label}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </Popover>
            )}
        </div>
    );
};

export default ColorSelector;
