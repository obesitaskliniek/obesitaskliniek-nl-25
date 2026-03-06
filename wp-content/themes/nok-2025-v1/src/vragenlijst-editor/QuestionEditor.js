/**
 * QuestionEditor — form for editing a single question.
 *
 * Handles type-specific option editors (radio/select options, number
 * validation), branch logic, and ID editing.
 */
import {useCallback} from '@wordpress/element';
import {
    TextControl,
    TextareaControl,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';

import BranchEditor from './BranchEditor';
import {QUESTION_TYPES} from './utils';

const fieldLabelStyle = {
    display: 'block',
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    marginBottom: '4px',
    marginTop: '10px',
    color: '#1e1e1e',
};

const QuestionEditor = ({question, index, questions, results, onChange}) => {
    const update = useCallback((partial) => {
        onChange({...question, ...partial});
    }, [question, onChange]);

    const updateOption = useCallback((optIndex, field, value) => {
        const options = [...(question.options || [])];
        options[optIndex] = {...options[optIndex], [field]: value};
        update({options});
    }, [question, update]);

    const addOption = useCallback(() => {
        const options = [...(question.options || [])];
        options.push({value: '', label: ''});
        update({options});
    }, [question, update]);

    const removeOption = useCallback((optIndex) => {
        const options = (question.options || []).filter((_, i) => i !== optIndex);
        update({options});
    }, [question, update]);

    const needsOptions = question.type === 'radio' || question.type === 'select';
    const needsValidation = question.type === 'number' || question.type === 'bmi';
    const isInfo = question.type === 'info';

    return (
        <div style={{paddingTop: '8px'}}>
            {/* ID */}
            <div style={fieldLabelStyle}>ID</div>
            <TextControl
                value={question.id}
                onChange={(val) => update({id: val.replace(/[^a-z0-9_]/gi, '_').toLowerCase()})}
                help="Unieke identifier (a-z, 0-9, _). Gebruikt in branch-logica."
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />

            {/* Type */}
            <div style={fieldLabelStyle}>Type</div>
            <SelectControl
                value={question.type}
                options={QUESTION_TYPES}
                onChange={(type) => {
                    const patch = {type};
                    // Initialize options when switching to radio/select
                    if ((type === 'radio' || type === 'select') && !question.options?.length) {
                        patch.options = [{value: 'ja', label: 'Ja'}, {value: 'nee', label: 'Nee'}];
                    }
                    // Initialize validation when switching to number or bmi
                    if ((type === 'number' || type === 'bmi') && !question.validation) {
                        patch.validation = {};
                    }
                    update(patch);
                }}
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />

            {/* Label */}
            <div style={fieldLabelStyle}>
                {isInfo ? 'Tekst' : 'Label (vraagtekst)'}
            </div>
            <TextareaControl
                value={question.label || ''}
                onChange={(label) => update({label})}
                rows={2}
            />

            {/* Help text */}
            {!isInfo && (
                <>
                    <div style={fieldLabelStyle}>Hulptekst (optioneel)</div>
                    <TextControl
                        value={question.help || ''}
                        onChange={(help) => update({help: help || undefined})}
                        placeholder="Extra uitleg onder de vraag"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                </>
            )}

            {/* Required */}
            {!isInfo && (
                <div style={{marginTop: '10px'}}>
                    <ToggleControl
                        label="Verplicht"
                        checked={question.required !== false}
                        onChange={(required) => update({required})}
                        __nextHasNoMarginBottom
                    />
                </div>
            )}

            {/* Options (radio/select) */}
            {needsOptions && (
                <>
                    <div style={fieldLabelStyle}>Opties</div>
                    {(question.options || []).map((opt, optIdx) => (
                        <div key={optIdx} style={{
                            display: 'flex',
                            gap: '4px',
                            alignItems: 'center',
                            marginBottom: '4px',
                        }}>
                            <TextControl
                                value={opt.value}
                                onChange={(val) => updateOption(optIdx, 'value', val)}
                                placeholder="waarde"
                                style={{flex: 1}}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                            <TextControl
                                value={opt.label}
                                onChange={(val) => updateOption(optIdx, 'label', val)}
                                placeholder="label"
                                style={{flex: 1}}
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                            <button
                                type="button"
                                onClick={() => removeOption(optIdx)}
                                style={{
                                    border: 'none',
                                    background: 'none',
                                    cursor: 'pointer',
                                    color: '#cc1818',
                                    fontSize: '16px',
                                    padding: '4px',
                                    lineHeight: 1,
                                }}
                                aria-label="Optie verwijderen"
                            >
                                &times;
                            </button>
                        </div>
                    ))}
                    <button
                        type="button"
                        onClick={addOption}
                        style={{
                            border: '1px dashed #ccc',
                            background: 'none',
                            padding: '4px 10px',
                            fontSize: '12px',
                            cursor: 'pointer',
                            borderRadius: '3px',
                            color: '#007cba',
                            width: '100%',
                            marginTop: '2px',
                        }}
                    >
                        + Optie toevoegen
                    </button>
                </>
            )}

            {/* Number validation */}
            {needsValidation && (
                <>
                    <div style={fieldLabelStyle}>Validatie</div>
                    <div style={{display: 'flex', gap: '8px'}}>
                        <TextControl
                            type="number"
                            label="Min"
                            value={question.validation?.min ?? ''}
                            onChange={(val) => update({
                                validation: {
                                    ...question.validation,
                                    min: val === '' ? undefined : Number(val),
                                },
                            })}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            type="number"
                            label="Max"
                            value={question.validation?.max ?? ''}
                            onChange={(val) => update({
                                validation: {
                                    ...question.validation,
                                    max: val === '' ? undefined : Number(val),
                                },
                            })}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </div>
                    <TextControl
                        value={question.validation?.error || ''}
                        onChange={(error) => update({
                            validation: {...question.validation, error: error || undefined},
                        })}
                        placeholder="Foutmelding bij ongeldige invoer"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                </>
            )}

            {/* Branch logic */}
            {!isInfo && (
                <BranchEditor
                    question={question}
                    index={index}
                    questions={questions}
                    results={results}
                    onChange={(branch) => update({branch: branch || undefined})}
                />
            )}
        </div>
    );
};

export default QuestionEditor;
