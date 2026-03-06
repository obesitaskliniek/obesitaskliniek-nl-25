/**
 * ResultsPanel — list of result cards with expand-to-edit.
 */
import {useState, useCallback} from '@wordpress/element';
import {Button} from '@wordpress/components';

import ResultEditor from './ResultEditor';
import {generateId, createBlankResult, RESULT_STYLES, countReferences} from './utils';

const styleBadgeColors = {
    positive: {bg: '#d4edda', color: '#155724'},
    neutral: {bg: '#e0e0e0', color: '#555'},
    negative: {bg: '#f8d7da', color: '#721c24'},
};

const resultItemStyle = {
    border: '1px solid #ddd',
    borderRadius: '4px',
    marginBottom: '6px',
    background: '#fff',
};

const resultHeaderStyle = {
    display: 'flex',
    alignItems: 'center',
    gap: '4px',
    padding: '8px',
    cursor: 'pointer',
    fontSize: '12px',
    lineHeight: '1.4',
};

const ResultsPanel = ({results, questions, onChange}) => {
    const [expandedId, setExpandedId] = useState(null);

    const existingIds = results.map(r => r.id);
    const hasDefault = results.some(r => r.condition === 'default');

    const addResult = useCallback(() => {
        const id = generateId('r', existingIds);
        // First result without a default gets default condition
        const isDefault = !hasDefault;
        const newR = createBlankResult(id, isDefault);
        onChange([...results, newR]);
        setExpandedId(id);
    }, [results, existingIds, hasDefault, onChange]);

    const updateResult = useCallback((index, updated) => {
        const next = [...results];
        next[index] = updated;

        // If this result was set to default, clear default from all others
        if (updated.condition === 'default') {
            for (let i = 0; i < next.length; i++) {
                if (i !== index && next[i].condition === 'default') {
                    next[i] = {...next[i], condition: {match: 'all', rules: []}};
                }
            }
        }

        onChange(next);
    }, [results, onChange]);

    const removeResult = useCallback((index) => {
        const next = results.filter((_, i) => i !== index);
        onChange(next);
        setExpandedId(null);
    }, [results, onChange]);

    const moveResult = useCallback((index, direction) => {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= results.length) return;
        const next = [...results];
        [next[index], next[newIndex]] = [next[newIndex], next[index]];
        onChange(next);
    }, [results, onChange]);

    const getStyleLabel = (style) => {
        const found = RESULT_STYLES.find(s => s.value === style);
        return found ? found.label.split(' ')[0] : style || 'Geen';
    };

    const getConditionSummary = (result) => {
        if (result.condition === 'default') return 'Standaard (fallback)';
        if (typeof result.condition === 'object') {
            const ruleCount = result.condition.rules?.length || 0;
            const match = result.condition.match === 'any' ? 'OF' : 'EN';
            return `${ruleCount} regel${ruleCount !== 1 ? 's' : ''} (${match})`;
        }
        return '—';
    };

    return (
        <div>
            {!hasDefault && results.length > 0 && (
                <div style={{
                    padding: '6px 8px',
                    background: '#fcf0f0',
                    borderRadius: '4px',
                    fontSize: '11px',
                    color: '#721c24',
                    marginBottom: '8px',
                }}>
                    Geen standaard-resultaat ingesteld. Voeg een resultaat toe met
                    &ldquo;Standaard (fallback)&rdquo; als conditie.
                </div>
            )}

            {results.map((result, index) => {
                const isExpanded = expandedId === result.id;
                const isDefault = result.condition === 'default';
                const colors = styleBadgeColors[result.style] || styleBadgeColors.neutral;

                return (
                    <div key={result.id} style={{
                        ...resultItemStyle,
                        borderLeft: `3px solid ${colors.bg}`,
                    }}>
                        <div
                            style={resultHeaderStyle}
                            onClick={() => setExpandedId(isExpanded ? null : result.id)}
                            role="button"
                            tabIndex={0}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    setExpandedId(isExpanded ? null : result.id);
                                }
                            }}
                        >
                            <span style={{
                                display: 'inline-block',
                                fontSize: '10px',
                                fontWeight: 600,
                                textTransform: 'uppercase',
                                padding: '1px 6px',
                                borderRadius: '3px',
                                background: colors.bg,
                                color: colors.color,
                                flexShrink: 0,
                            }}>
                                {getStyleLabel(result.style)}
                            </span>
                            <span style={{
                                flex: 1,
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                                color: result.title ? '#1e1e1e' : '#999',
                            }}>
                                {result.title || '(geen titel)'}
                            </span>
                            <span style={{
                                fontSize: '10px',
                                color: isDefault ? '#155724' : '#999',
                                flexShrink: 0,
                                fontStyle: isDefault ? 'italic' : 'normal',
                            }}>
                                {getConditionSummary(result)}
                            </span>
                            <span style={{flexShrink: 0, fontSize: '16px', color: '#999'}}>
                                {isExpanded ? '▾' : '▸'}
                            </span>
                        </div>

                        {isExpanded && (
                            <div style={{padding: '0 8px 8px', borderTop: '1px solid #eee'}}>
                                <ResultEditor
                                    result={result}
                                    questions={questions}
                                    allResults={results}
                                    onChange={(updated) => updateResult(index, updated)}
                                />
                                <div style={{
                                    display: 'flex',
                                    gap: '4px',
                                    marginTop: '12px',
                                    paddingTop: '8px',
                                    borderTop: '1px solid #eee',
                                }}>
                                    <Button
                                        size="small"
                                        icon="arrow-up-alt2"
                                        label="Omhoog"
                                        disabled={index === 0}
                                        onClick={() => moveResult(index, -1)}
                                    />
                                    <Button
                                        size="small"
                                        icon="arrow-down-alt2"
                                        label="Omlaag"
                                        disabled={index === results.length - 1}
                                        onClick={() => moveResult(index, 1)}
                                    />
                                    <span style={{flex: 1}} />
                                    <Button
                                        size="small"
                                        isDestructive
                                        icon="trash"
                                        label="Verwijderen"
                                        onClick={() => {
                                            const refs = countReferences(result.id, questions, results);
                                            let msg = `Resultaat "${result.title || result.id}" verwijderen?`;
                                            if (refs > 0) {
                                                msg += `\n\nLet op: dit ID wordt ${refs}x gerefereerd als branch-doel.`;
                                            }
                                            if (window.confirm(msg)) {
                                                removeResult(index);
                                            }
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                );
            })}

            <Button
                variant="secondary"
                icon="plus-alt2"
                onClick={addResult}
                style={{width: '100%', justifyContent: 'center', marginTop: '4px'}}
            >
                Resultaat toevoegen
            </Button>
        </div>
    );
};

export default ResultsPanel;
