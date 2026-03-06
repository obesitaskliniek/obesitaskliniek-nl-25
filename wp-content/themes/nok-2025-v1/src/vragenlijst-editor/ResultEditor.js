/**
 * ResultEditor — form for editing a single result card.
 *
 * Includes condition type toggle (default vs rule-based),
 * the visual RuleBuilder for structured conditions, and
 * result card content fields.
 */
import {useCallback} from '@wordpress/element';
import {
    TextControl,
    TextareaControl,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';

import RuleBuilder from './RuleBuilder';
import {RESULT_STYLES, createBlankGroup} from './utils';

const labelStyle = {
    display: 'block',
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    marginBottom: '4px',
    marginTop: '10px',
    color: '#1e1e1e',
};

const ResultEditor = ({result, questions, allResults, onChange}) => {
    const update = useCallback((partial) => {
        onChange({...result, ...partial});
    }, [result, onChange]);

    const isDefault = result.condition === 'default';
    const otherHasDefault = allResults.some(r => r.id !== result.id && r.condition === 'default');

    return (
        <div style={{paddingTop: '8px'}}>
            {/* ID */}
            <div style={labelStyle}>ID</div>
            <TextControl
                value={result.id}
                onChange={(val) => update({id: val.replace(/[^a-z0-9_]/gi, '_').toLowerCase()})}
                help="Unieke identifier. Gebruikt als branch-doel vanuit vragen."
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />

            {/* Default toggle */}
            <div style={{marginTop: '10px'}}>
                <ToggleControl
                    label="Standaard-resultaat (fallback)"
                    help={isDefault
                        ? 'Dit resultaat wordt getoond als geen andere conditie matcht.'
                        : otherHasDefault
                            ? 'Een ander resultaat is al standaard.'
                            : 'Maak dit het fallback-resultaat.'}
                    checked={isDefault}
                    onChange={(checked) => {
                        if (checked) {
                            // Remove default from other results
                            update({condition: 'default'});
                        } else {
                            update({condition: createBlankGroup()});
                        }
                    }}
                    disabled={false}
                    __nextHasNoMarginBottom
                />
            </div>

            {/* Condition — inline RuleBuilder */}
            {!isDefault && (
                <>
                    <div style={labelStyle}>Conditie</div>
                    <RuleBuilder
                        rule={typeof result.condition === 'object' ? result.condition : createBlankGroup()}
                        questions={questions}
                        onChange={(condition) => update({condition})}
                    />
                </>
            )}

            {/* Title */}
            <div style={labelStyle}>Titel</div>
            <TextControl
                value={result.title || ''}
                onChange={(title) => update({title})}
                placeholder="Bijv. 'U komt mogelijk in aanmerking'"
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />

            {/* Body (HTML) */}
            <div style={labelStyle}>Inhoud (HTML)</div>
            <TextareaControl
                value={result.body || ''}
                onChange={(body) => update({body})}
                rows={4}
                help="Wordt weergegeven als HTML. Gebruik <p>, <em>, <strong>."
            />

            {/* Style */}
            <div style={labelStyle}>Stijl</div>
            <SelectControl
                value={result.style || 'neutral'}
                options={RESULT_STYLES}
                onChange={(style) => update({style})}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />

            {/* CTA */}
            <div style={labelStyle}>CTA knop (optioneel)</div>
            <TextControl
                value={result.cta_text || ''}
                onChange={(cta_text) => update({cta_text: cta_text || undefined})}
                placeholder="Knoptekst, bijv. 'Maak een afspraak'"
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
            <TextControl
                value={result.cta_url || ''}
                onChange={(cta_url) => update({cta_url: cta_url || undefined})}
                placeholder="/pad-op-de-site/"
                help="Relatief pad of URL op obesitaskliniek.nl"
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
        </div>
    );
};

export default ResultEditor;
