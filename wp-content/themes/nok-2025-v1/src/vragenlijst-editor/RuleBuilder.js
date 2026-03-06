/**
 * RuleBuilder — visual structured rule builder for nested all/any groups.
 *
 * Renders a recursive tree of rule groups and leaf rules. Each group has
 * an ALL/ANY toggle. Each leaf rule has question/operator/value dropdowns.
 * Supports adding/removing rules and nesting groups up to 4 levels deep.
 *
 * This component mirrors the evaluateRule() function in nok-vragenlijst.mjs —
 * the same JSON structure is both rendered here and evaluated at runtime.
 */
import {useCallback} from '@wordpress/element';
import {SelectControl, TextControl} from '@wordpress/components';

import {OPERATORS, createBlankRule, createBlankGroup, getQuestionValueOptions, getOperatorsForQuestion} from './utils';

const MAX_NESTING = 4;

const groupStyle = (depth) => ({
    borderLeft: `2px solid ${depth === 0 ? '#007cba' : depth === 1 ? '#8c8f94' : '#ddd'}`,
    paddingLeft: '8px',
    marginTop: depth > 0 ? '6px' : 0,
});

const matchToggleStyle = {
    display: 'inline-flex',
    borderRadius: '3px',
    overflow: 'hidden',
    border: '1px solid #ccc',
    fontSize: '11px',
    fontWeight: 600,
    marginBottom: '6px',
};

const matchButtonStyle = (active) => ({
    padding: '2px 8px',
    border: 'none',
    background: active ? '#007cba' : '#fff',
    color: active ? '#fff' : '#555',
    cursor: 'pointer',
    fontSize: '11px',
    fontWeight: 600,
});

const ruleRowStyle = {
    display: 'flex',
    gap: '4px',
    alignItems: 'flex-start',
    marginBottom: '6px',
};

const RuleBuilder = ({rule, questions, onChange, depth = 0}) => {
    // Ensure we have a valid group structure
    const group = (rule && rule.match && Array.isArray(rule.rules))
        ? rule
        : {match: 'all', rules: []};

    const updateGroup = useCallback((partial) => {
        onChange({...group, ...partial});
    }, [group, onChange]);

    const updateRule = useCallback((index, updatedRule) => {
        const rules = [...group.rules];
        rules[index] = updatedRule;
        updateGroup({rules});
    }, [group, updateGroup]);

    const removeRule = useCallback((index) => {
        const rules = group.rules.filter((_, i) => i !== index);
        updateGroup({rules});
    }, [group, updateGroup]);

    const addLeafRule = useCallback(() => {
        updateGroup({rules: [...group.rules, createBlankRule()]});
    }, [group, updateGroup]);

    const addNestedGroup = useCallback(() => {
        const nestedMatch = group.match === 'all' ? 'any' : 'all';
        updateGroup({rules: [...group.rules, createBlankGroup(nestedMatch)]});
    }, [group, updateGroup]);

    const questionOptions = [
        {value: '', label: 'Vraag...'},
        ...(questions || [])
            .filter(q => q.type !== 'info')
            .map(q => ({
                value: q.id,
                label: q.label ? `${q.id}: ${q.label.substring(0, 50)}` : q.id,
            })),
    ];

    return (
        <div style={groupStyle(depth)}>
            {/* ALL/ANY toggle */}
            <div style={matchToggleStyle}>
                <button
                    type="button"
                    style={matchButtonStyle(group.match === 'all')}
                    onClick={() => updateGroup({match: 'all'})}
                >
                    Alle (EN)
                </button>
                <button
                    type="button"
                    style={matchButtonStyle(group.match === 'any')}
                    onClick={() => updateGroup({match: 'any'})}
                >
                    Eén van (OF)
                </button>
            </div>

            {/* Rules */}
            {group.rules.map((childRule, index) => {
                // Nested group
                if (childRule.match && Array.isArray(childRule.rules)) {
                    return (
                        <div key={index} style={{position: 'relative'}}>
                            <RuleBuilder
                                rule={childRule}
                                questions={questions}
                                onChange={(updated) => updateRule(index, updated)}
                                depth={depth + 1}
                            />
                            <button
                                type="button"
                                onClick={() => removeRule(index)}
                                style={{
                                    position: 'absolute',
                                    top: 0,
                                    right: 0,
                                    border: 'none',
                                    background: 'none',
                                    cursor: 'pointer',
                                    color: '#cc1818',
                                    fontSize: '14px',
                                    padding: '2px',
                                    lineHeight: 1,
                                }}
                                aria-label="Groep verwijderen"
                            >
                                &times;
                            </button>
                        </div>
                    );
                }

                // Leaf rule
                const selectedQuestion = (questions || []).find(q => q.id === childRule.question);
                const valueOptions = getQuestionValueOptions(selectedQuestion);
                const allowedOperators = getOperatorsForQuestion(selectedQuestion);

                return (
                    <div key={index} style={ruleRowStyle}>
                        <SelectControl
                            value={childRule.question || ''}
                            options={questionOptions}
                            onChange={(question) => {
                                const newQ = (questions || []).find(q => q.id === question);
                                const ops = getOperatorsForQuestion(newQ);
                                // Reset operator if current one is not valid for the new question type
                                const opValid = ops.some(o => o.value === childRule.op);
                                updateRule(index, {
                                    ...childRule,
                                    question,
                                    op: opValid ? childRule.op : '==',
                                });
                            }}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            style={{flex: 2, minWidth: 0}}
                        />
                        <SelectControl
                            value={childRule.op || '=='}
                            options={allowedOperators}
                            onChange={(op) => updateRule(index, {...childRule, op})}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            style={{flex: 1, minWidth: 0}}
                        />
                        {valueOptions ? (
                            <SelectControl
                                value={String(childRule.value ?? '')}
                                options={[
                                    {value: '', label: '...'},
                                    ...valueOptions,
                                ]}
                                onChange={(value) => updateRule(index, {...childRule, value})}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                                style={{flex: 1, minWidth: 0}}
                            />
                        ) : (
                            <TextControl
                                value={childRule.value ?? ''}
                                onChange={(value) => updateRule(index, {...childRule, value})}
                                placeholder="waarde"
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                                style={{flex: 1, minWidth: 0}}
                            />
                        )}
                        <button
                            type="button"
                            onClick={() => removeRule(index)}
                            style={{
                                border: 'none',
                                background: 'none',
                                cursor: 'pointer',
                                color: '#cc1818',
                                fontSize: '14px',
                                padding: '2px',
                                lineHeight: 1,
                                flexShrink: 0,
                            }}
                            aria-label="Regel verwijderen"
                        >
                            &times;
                        </button>
                    </div>
                );
            })}

            {/* Add buttons */}
            <div style={{display: 'flex', gap: '4px', marginTop: '4px'}}>
                <button
                    type="button"
                    onClick={addLeafRule}
                    style={{
                        border: '1px dashed #ccc',
                        background: 'none',
                        padding: '2px 8px',
                        fontSize: '11px',
                        cursor: 'pointer',
                        borderRadius: '3px',
                        color: '#007cba',
                    }}
                >
                    + Regel
                </button>
                {depth < MAX_NESTING && (
                    <button
                        type="button"
                        onClick={addNestedGroup}
                        style={{
                            border: '1px dashed #ccc',
                            background: 'none',
                            padding: '2px 8px',
                            fontSize: '11px',
                            cursor: 'pointer',
                            borderRadius: '3px',
                            color: '#007cba',
                        }}
                    >
                        + Groep
                    </button>
                )}
            </div>
        </div>
    );
};

export default RuleBuilder;
