import { useState, useRef, useMemo } from '@wordpress/element';
import { Popover } from '@wordpress/components';

/**
 * Split a CSS class string into light and dark mode parts.
 *
 * @param {string} classString Combined CSS class string
 * @return {{ light: string, dark: string }}
 */
const splitLightDark = (classString) => {
    const classes = (classString || '').split(/\s+/).filter(Boolean);
    return {
        light: classes.filter(c => !c.startsWith('nok-dark-')).join(' '),
        dark: classes.filter(c => c.startsWith('nok-dark-')).join(' '),
    };
};

/**
 * Merge light and dark class strings into a single value.
 *
 * @param {string} light Light mode classes
 * @param {string} dark  Dark mode classes
 * @return {string}
 */
const mergeLightDark = (light, dark) =>
    [light, dark].filter(Boolean).join(' ').trim();

/**
 * Convert palette value classes to their dark-mode equivalents.
 * nok-bg-X → nok-dark-bg-X, nok-text-X → nok-dark-text-X.
 * Skips non-color classes (e.g. 'gradient-background') and nok-bg-transparent.
 *
 * @param {string} value Palette entry value
 * @return {string} Dark-prefixed classes
 */
const toDarkClasses = (value) =>
    (value || '').split(/\s+/).filter(Boolean)
        .filter(c => (c.startsWith('nok-bg-') || c.startsWith('nok-text-')) && c !== 'nok-bg-transparent')
        .map(c => c.replace(/^nok-/, 'nok-dark-'))
        .join(' ');

/**
 * Convert dark classes back to their light equivalents for palette matching.
 *
 * @param {string} darkClasses Dark-prefixed class string
 * @return {string} Light equivalents
 */
const fromDarkClasses = (darkClasses) =>
    (darkClasses || '').split(/\s+/).filter(Boolean)
        .map(c => c.replace(/^nok-dark-/, 'nok-'))
        .join(' ');

/**
 * Extract only nok-bg-* and nok-text-* classes from a string, sorted.
 * Used as a fingerprint for matching palette entries against stored dark classes.
 *
 * @param {string} classString CSS class string
 * @return {string} Sorted color classes
 */
const colorFingerprint = (classString) =>
    (classString || '').split(/\s+/).filter(Boolean)
        .filter(c => (c.startsWith('nok-bg-') || c.startsWith('nok-text-')) && c !== 'nok-bg-transparent')
        .sort()
        .join(' ');

/**
 * ColorSelector - Visual color swatch picker with dark mode support
 *
 * Displays a dropdown with color swatches for selecting from predefined palettes.
 * When the palette has dark-capable entries, a second picker row appears showing
 * the SAME palette — editors pick a dark mode color the same way they pick a light one.
 *
 * BACKWARD COMPATIBILITY:
 * - Stored values with baked-in dark classes (e.g. 'nok-bg-white nok-dark-bg-darkestblue')
 *   are split on read for display, but onChange is NEVER called on mount or prop change.
 * - Only user interaction triggers onChange, preserving byte-identical stored values.
 *
 * @param {Object} props Component props
 * @param {string} props.value Current selected value (CSS class string, may contain dark classes)
 * @param {Function} props.onChange Callback when selection changes
 * @param {string} props.palette Palette name (e.g., 'backgrounds', 'text')
 */
const ColorSelector = ({ value, onChange, palette }) => {
    const [lightOpen, setLightOpen] = useState(false);
    const [darkOpen, setDarkOpen] = useState(false);
    const [lightSearch, setLightSearch] = useState('');
    const [darkSearch, setDarkSearch] = useState('');
    const lightRef = useRef(null);
    const darkRef = useRef(null);

    // Get palette from global PagePartDesignSettings
    const palettes = window.PagePartDesignSettings?.colorPalettes || {};
    const paletteColors = palettes[palette] || [];

    // Does this palette have any dark mode options?
    const hasDarkOptions = useMemo(
        () => paletteColors.some(c => c.darkValue),
        [paletteColors]
    );

    // Palette entries that produce meaningful dark classes
    const darkCapableEntries = useMemo(
        () => paletteColors.filter(c => toDarkClasses(c.value)),
        [paletteColors]
    );

    // Split current stored value into light/dark portions
    const { light: currentLight, dark: currentDark } = splitLightDark(value);

    // Match light portion to palette entry
    const selectedLight = paletteColors.find(c => c.value === currentLight);

    // Match dark portion: convert stored dark classes back to light equivalents,
    // fingerprint them, find the palette entry with matching color classes
    const darkFingerprint = colorFingerprint(fromDarkClasses(currentDark));
    const matchedDark = darkFingerprint
        ? paletteColors.find(c => colorFingerprint(c.value) === darkFingerprint)
        : null;

    // Show dark row when palette supports it AND (entry has curated default OR stored value has dark classes)
    const showDarkRow = hasDarkOptions && !!(selectedLight?.darkValue || currentDark);

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

    // Render color grid inside a popover
    const renderGrid = (entries, searchValue, isDarkPicker, selectedFingerprint) => {
        const filtered = entries.filter(c =>
            c.label.toLowerCase().includes(searchValue.toLowerCase())
        );

        if (filtered.length === 0) {
            return (
                <div style={{ padding: '16px', textAlign: 'center', color: '#757575', fontSize: '13px' }}>
                    Geen kleuren gevonden
                </div>
            );
        }

        return (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '8px' }}>
                {isDarkPicker && (
                    <div
                        key="dark-none"
                        onClick={() => {
                            onChange(currentLight);
                            setDarkOpen(false);
                            setDarkSearch('');
                        }}
                        title="Geen donkere modus"
                        style={{
                            padding: '4px',
                            border: !currentDark ? '2px solid #007cba' : '1px solid #ddd',
                            borderRadius: '4px',
                            cursor: 'pointer',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: '4px',
                            background: !currentDark ? '#e0f0ff' : '#fff',
                            transition: 'all 0.15s',
                            minHeight: '50px'
                        }}
                        onMouseEnter={(e) => { if (currentDark) e.currentTarget.style.background = '#f5f5f5'; }}
                        onMouseLeave={(e) => { if (currentDark) e.currentTarget.style.background = '#fff'; }}
                    >
                        {renderSwatch(null, '32px')}
                        <span style={{ fontSize: '10px', color: '#757575' }}>Geen</span>
                    </div>
                )}
                {filtered.map(entry => {
                    const fp = colorFingerprint(entry.value);
                    const isSelected = isDarkPicker
                        ? (fp && fp === selectedFingerprint)
                        : (entry.value === currentLight);

                    return (
                        <div
                            key={entry.value || 'empty'}
                            onClick={() => {
                                if (isDarkPicker) {
                                    const darkClasses = toDarkClasses(entry.value);
                                    onChange(mergeLightDark(currentLight, darkClasses));
                                    setDarkOpen(false);
                                    setDarkSearch('');
                                } else {
                                    // Auto-fill curated dark default when picking light
                                    onChange(mergeLightDark(entry.value, entry.darkValue || ''));
                                    setLightOpen(false);
                                    setLightSearch('');
                                }
                            }}
                            title={entry.label}
                            style={{
                                padding: '4px',
                                border: isSelected ? '2px solid #007cba' : '1px solid #ddd',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '4px',
                                background: isSelected ? '#e0f0ff' : '#fff',
                                transition: 'all 0.15s',
                                minHeight: '50px'
                            }}
                            onMouseEnter={(e) => { if (!isSelected) e.currentTarget.style.background = '#f5f5f5'; }}
                            onMouseLeave={(e) => { if (!isSelected) e.currentTarget.style.background = '#fff'; }}
                        >
                            {renderSwatch(entry.color, '32px')}
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
                                {entry.label}
                            </span>
                        </div>
                    );
                })}
            </div>
        );
    };

    // Render a popover picker
    const renderPopover = (isOpen, setIsOpen, anchorRef, searchVal, setSearchVal, entries, isDark, selectedFp) => {
        if (!isOpen) return null;
        return (
            <Popover
                anchor={anchorRef.current}
                onClose={() => { setIsOpen(false); setSearchVal(''); }}
                placement="bottom-start"
                shift={true}
                resize={true}
            >
                <div style={{ background: '#fff', maxHeight: '400px', width: '280px', overflow: 'hidden' }}>
                    <input
                        type="text"
                        placeholder="Zoek kleur..."
                        value={searchVal}
                        onChange={(e) => setSearchVal(e.target.value)}
                        style={{
                            width: '100%', padding: '8px', border: 'none',
                            borderBottom: '1px solid #ddd', fontSize: '13px',
                            outline: 'none', boxSizing: 'border-box'
                        }}
                        autoFocus
                    />
                    <div style={{ overflowY: 'auto', maxHeight: '350px', padding: '8px' }}>
                        {renderGrid(entries, searchVal, isDark, selectedFp)}
                    </div>
                </div>
            </Popover>
        );
    };

    // Determine dark row display label and color
    const darkDisplayLabel = matchedDark?.label || selectedLight?.darkLabel || currentDark || null;
    const darkDisplayColor = matchedDark?.color || selectedLight?.darkColor || null;

    return (
        <div style={{ marginBottom: '12px' }}>
            {/* Light picker trigger */}
            <div style={{ position: 'relative' }}>
                <div
                    ref={lightRef}
                    onClick={() => setLightOpen(!lightOpen)}
                    style={{
                        border: '1px solid #ddd',
                        borderRadius: showDarkRow ? '4px 4px 0 0' : '4px',
                        padding: '8px',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        background: '#fff',
                        minHeight: '40px'
                    }}
                >
                    {selectedLight ? (
                        <>
                            {renderSwatch(selectedLight.color)}
                            <span style={{ fontSize: '13px' }}>{selectedLight.label}</span>
                        </>
                    ) : currentLight ? (
                        <>
                            {renderSwatch(null)}
                            <span style={{ fontSize: '13px', color: '#757575' }}>{currentLight}</span>
                        </>
                    ) : (
                        <span style={{ fontSize: '13px', color: '#757575' }}>Selecteer kleur...</span>
                    )}
                </div>

                {selectedLight && (
                    <button
                        onClick={(e) => { e.stopPropagation(); onChange(''); }}
                        style={{
                            position: 'absolute', right: '8px', top: '50%',
                            transform: 'translateY(-50%)',
                            background: '#d63638', color: '#fff', border: 'none',
                            borderRadius: '3px', padding: '4px 8px',
                            fontSize: '11px', cursor: 'pointer', fontWeight: '500'
                        }}
                        title="Wis selectie"
                    >
                        &times;
                    </button>
                )}
            </div>

            {/* Light picker popover */}
            {renderPopover(
                lightOpen, setLightOpen, lightRef,
                lightSearch, setLightSearch,
                paletteColors, false, null
            )}

            {/* Dark mode helper — explain when DARK row appears */}
            {hasDarkOptions && !showDarkRow && selectedLight && (
                <p style={{ margin: '4px 0 0', fontSize: '11px', color: '#757575', fontStyle: 'italic' }}>
                    Deze kleur heeft geen dark-mode variant.
                </p>
            )}

            {/* Dark mode picker row — same palette, picks dark-prefixed classes */}
            {showDarkRow && (
                <div style={{ position: 'relative' }}>
                    <div
                        ref={darkRef}
                        onClick={() => setDarkOpen(!darkOpen)}
                        style={{
                            border: '1px solid #ddd',
                            borderTop: 'none',
                            borderRadius: '0 0 4px 4px',
                            padding: '6px 8px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            background: '#f9f9f9',
                            minHeight: '32px'
                        }}
                    >
                        <span style={{
                            fontSize: '10px', fontWeight: '600',
                            textTransform: 'uppercase', letterSpacing: '0.5px',
                            color: '#757575', flexShrink: 0,
                        }}>
                            Dark
                        </span>

                        {currentDark ? (
                            <>
                                {renderSwatch(darkDisplayColor, '18px')}
                                <span style={{ fontSize: '12px', color: '#555' }}>{darkDisplayLabel}</span>
                            </>
                        ) : (
                            <span style={{ fontSize: '12px', color: '#aaa' }}>Geen</span>
                        )}

                        {currentDark && (
                            <button
                                onClick={(e) => { e.stopPropagation(); onChange(currentLight); }}
                                style={{
                                    marginLeft: 'auto', background: 'none', color: '#999',
                                    border: 'none', padding: '0 4px', fontSize: '14px',
                                    cursor: 'pointer', lineHeight: 1, flexShrink: 0,
                                }}
                                title="Donkere modus wissen"
                            >
                                &times;
                            </button>
                        )}
                    </div>

                    {/* Dark picker popover — same palette entries */}
                    {renderPopover(
                        darkOpen, setDarkOpen, darkRef,
                        darkSearch, setDarkSearch,
                        darkCapableEntries, true, darkFingerprint
                    )}
                </div>
            )}
        </div>
    );
};

export default ColorSelector;
