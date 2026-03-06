/**
 * Questions panel — sortable list of questions with expand-to-edit.
 */
import {useState, useCallback} from '@wordpress/element';
import {Button} from '@wordpress/components';

import QuestionEditor from './QuestionEditor';
import {
    generateId,
    createBlankQuestion,
    QUESTION_TYPES,
    countReferences,
    cascadeRename,
} from './utils';

const typeBadgeStyle = {
    display: 'inline-block',
    fontSize: '10px',
    fontWeight: 600,
    textTransform: 'uppercase',
    padding: '1px 6px',
    borderRadius: '3px',
    background: '#e0e0e0',
    color: '#555',
    marginRight: '6px',
    flexShrink: 0,
};

const questionItemStyle = {
    border: '1px solid #ddd',
    borderRadius: '4px',
    marginBottom: '6px',
    background: '#fff',
};

const questionHeaderStyle = {
    display: 'flex',
    alignItems: 'center',
    gap: '4px',
    padding: '8px',
    cursor: 'pointer',
    fontSize: '12px',
    lineHeight: '1.4',
};

/**
 * @param {Object} props
 * @param {Array} props.questions
 * @param {Array} props.results
 * @param {Function} props.onChange - Called with updated questions array
 * @param {Function} props.onBatchChange - Called with {questions, results} for atomic updates (ID renames)
 */
const QuestionsPanel = ({questions, results, onChange, onBatchChange}) => {
    const [expandedId, setExpandedId] = useState(null);

    const addQuestion = useCallback(() => {
        const existingIds = questions.map(q => q.id);
        const id = generateId('q', existingIds);
        const newQ = createBlankQuestion(id);
        onChange([...questions, newQ]);
        setExpandedId(id);
    }, [questions, onChange]);

    const updateQuestion = useCallback((index, updatedQuestion) => {
        const oldId = questions[index].id;
        const newId = updatedQuestion.id;
        const next = [...questions];
        next[index] = updatedQuestion;

        // Cascade ID rename through branch targets and result conditions
        if (oldId !== newId && oldId && newId) {
            const updatedResults = results.map(r => ({
                ...r,
                condition: typeof r.condition === 'object'
                    ? JSON.parse(JSON.stringify(r.condition))
                    : r.condition,
            }));
            cascadeRename(oldId, newId, next, updatedResults);
            // Atomic update: both questions and results in one call
            if (onBatchChange) {
                onBatchChange({questions: next, results: updatedResults});
            } else {
                onChange(next);
            }
        } else {
            onChange(next);
        }
    }, [questions, results, onChange, onBatchChange]);

    const removeQuestion = useCallback((index) => {
        const next = questions.filter((_, i) => i !== index);
        onChange(next);
        setExpandedId(null);
    }, [questions, onChange]);

    const moveQuestion = useCallback((index, direction) => {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= questions.length) return;
        const next = [...questions];
        [next[index], next[newIndex]] = [next[newIndex], next[index]];
        onChange(next);
    }, [questions, onChange]);

    const duplicateQuestion = useCallback((index) => {
        const source = questions[index];
        const existingIds = questions.map(q => q.id);
        const id = generateId('q', existingIds);
        const clone = {...source, id, label: `${source.label} (kopie)`};
        if (clone.options) clone.options = clone.options.map(o => ({...o}));
        if (clone.branch) clone.branch = {...clone.branch};
        if (clone.validation) clone.validation = {...clone.validation};
        const next = [...questions];
        next.splice(index + 1, 0, clone);
        onChange(next);
        setExpandedId(id);
    }, [questions, onChange]);

    const getTypeLabel = (type) => {
        const found = QUESTION_TYPES.find(t => t.value === type);
        return found ? found.label : type;
    };

    const buildDeleteMessage = (question) => {
        const refs = countReferences(question.id, questions, results);
        let msg = `Vraag "${question.label || question.id}" verwijderen?`;
        if (refs > 0) {
            msg += `\n\nLet op: dit ID wordt ${refs}x gerefereerd in branch-logica of resultaat-condities. Deze verwijzingen worden ongeldig.`;
        }
        return msg;
    };

    return (
        <div>
            {questions.map((question, index) => {
                const isExpanded = expandedId === question.id;
                return (
                    <div key={question.id} style={questionItemStyle}>
                        <div
                            style={questionHeaderStyle}
                            onClick={() => setExpandedId(isExpanded ? null : question.id)}
                            role="button"
                            tabIndex={0}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    setExpandedId(isExpanded ? null : question.id);
                                }
                            }}
                        >
                            <span style={{
                                ...typeBadgeStyle,
                                background: question.type === 'info' ? '#d4edda' : '#e0e0e0',
                            }}>
                                {getTypeLabel(question.type)}
                            </span>
                            <span style={{
                                flex: 1,
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                                color: question.label ? '#1e1e1e' : '#999',
                            }}>
                                {question.label || '(geen label)'}
                            </span>
                            <span style={{
                                fontSize: '10px',
                                color: '#999',
                                flexShrink: 0,
                            }}>
                                {question.id}
                            </span>
                            <span style={{flexShrink: 0, fontSize: '16px', color: '#999'}}>
                                {isExpanded ? '▾' : '▸'}
                            </span>
                        </div>

                        {isExpanded && (
                            <div style={{padding: '0 8px 8px', borderTop: '1px solid #eee'}}>
                                <QuestionEditor
                                    question={question}
                                    index={index}
                                    questions={questions}
                                    results={results}
                                    onChange={(updated) => updateQuestion(index, updated)}
                                />
                                <div style={{
                                    display: 'flex',
                                    gap: '4px',
                                    flexWrap: 'wrap',
                                    marginTop: '12px',
                                    paddingTop: '8px',
                                    borderTop: '1px solid #eee',
                                }}>
                                    <Button
                                        size="small"
                                        icon="arrow-up-alt2"
                                        label="Omhoog"
                                        disabled={index === 0}
                                        onClick={() => moveQuestion(index, -1)}
                                    />
                                    <Button
                                        size="small"
                                        icon="arrow-down-alt2"
                                        label="Omlaag"
                                        disabled={index === questions.length - 1}
                                        onClick={() => moveQuestion(index, 1)}
                                    />
                                    <Button
                                        size="small"
                                        icon="admin-page"
                                        label="Dupliceren"
                                        onClick={() => duplicateQuestion(index)}
                                    />
                                    <span style={{flex: 1}} />
                                    <Button
                                        size="small"
                                        isDestructive
                                        icon="trash"
                                        label="Verwijderen"
                                        onClick={() => {
                                            if (window.confirm(buildDeleteMessage(question))) {
                                                removeQuestion(index);
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
                onClick={addQuestion}
                style={{width: '100%', justifyContent: 'center', marginTop: '4px'}}
            >
                Vraag toevoegen
            </Button>
        </div>
    );
};

export default QuestionsPanel;
