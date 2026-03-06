/**
 * BranchEditor — "if answer [op] [value], skip to [target]" per question.
 *
 * Only shows forward targets (questions after the current one + all results)
 * to prevent backward branches that would cause infinite loops.
 */
import {useCallback} from '@wordpress/element';
import {SelectControl, TextControl, ToggleControl} from '@wordpress/components';

import {OPERATORS, collectTargets} from './utils';

const sectionStyle = {
    marginTop: '12px',
    paddingTop: '8px',
    borderTop: '1px solid #eee',
};

const labelStyle = {
    display: 'block',
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    marginBottom: '4px',
    color: '#1e1e1e',
};

const BranchEditor = ({question, index, questions, results, onChange}) => {
    const branch = question.branch;
    const hasBranch = !!branch;

    // Only show questions AFTER the current one as targets (forward-only)
    const targets = collectTargets(questions, results, index);
    const targetOptions = [
        {value: '', label: 'Selecteer doel...'},
        ...targets.questions,
        ...targets.results,
    ];

    const toggleBranch = useCallback((enabled) => {
        if (enabled) {
            onChange({op: '==', value: '', action: 'skip_to', target: ''});
        } else {
            onChange(null);
        }
    }, [onChange]);

    const updateBranch = useCallback((partial) => {
        onChange({...branch, ...partial});
    }, [branch, onChange]);

    return (
        <div style={sectionStyle}>
            <ToggleControl
                label="Branch-logica"
                help={hasBranch
                    ? 'Als het antwoord voldoet, spring naar een ander punt.'
                    : 'Voeg conditionele sprong toe na dit antwoord.'}
                checked={hasBranch}
                onChange={toggleBranch}
                __nextHasNoMarginBottom
            />

            {hasBranch && (
                <div style={{
                    marginTop: '8px',
                    padding: '8px',
                    background: '#f5f5f5',
                    borderRadius: '4px',
                    fontSize: '12px',
                }}>
                    <div style={{
                        display: 'flex',
                        gap: '4px',
                        alignItems: 'center',
                        flexWrap: 'wrap',
                        marginBottom: '8px',
                    }}>
                        <span style={{fontWeight: 600, whiteSpace: 'nowrap'}}>Als antwoord</span>
                        <SelectControl
                            value={branch.op || '=='}
                            options={OPERATORS}
                            onChange={(op) => updateBranch({op})}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            style={{minWidth: '100px'}}
                        />
                    </div>

                    <TextControl
                        value={branch.value ?? ''}
                        onChange={(value) => updateBranch({value})}
                        placeholder="vergelijkingswaarde"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    <div style={{...labelStyle, marginTop: '8px'}}>Spring naar</div>
                    <SelectControl
                        value={branch.target || ''}
                        options={targetOptions}
                        onChange={(target) => updateBranch({target})}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />

                    {branch.target && branch.target.startsWith('r_') && (
                        <p style={{
                            fontSize: '11px',
                            color: '#757575',
                            fontStyle: 'italic',
                            marginTop: '4px',
                        }}>
                            Springt direct naar dit resultaat &mdash; overige vragen worden overgeslagen.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
};

export default BranchEditor;
