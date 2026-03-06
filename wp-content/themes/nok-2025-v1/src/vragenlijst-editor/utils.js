/**
 * Shared utilities for the vragenlijst editor
 */

export const QUESTION_TYPES = [
    {value: 'text', label: 'Tekst'},
    {value: 'number', label: 'Getal'},
    {value: 'bmi', label: 'BMI (lengte+gewicht)'},
    {value: 'radio', label: 'Keuzerondjes'},
    {value: 'select', label: 'Dropdown'},
    {value: 'checkbox', label: 'Vinkje'},
    {value: 'info', label: 'Informatietekst'},
];

export const OPERATORS = [
    {value: '==', label: 'is gelijk aan'},
    {value: '!=', label: 'is niet gelijk aan'},
    {value: '>', label: 'groter dan'},
    {value: '>=', label: 'groter dan of gelijk'},
    {value: '<', label: 'kleiner dan'},
    {value: '<=', label: 'kleiner dan of gelijk'},
];

/** Operators valid for checkbox questions (equality only, no numeric). */
export const EQUALITY_OPERATORS = OPERATORS.filter(op => op.value === '==' || op.value === '!=');

export const RESULT_STYLES = [
    {value: 'positive', label: 'Positief (groen)'},
    {value: 'neutral', label: 'Neutraal (grijs)'},
    {value: 'negative', label: 'Negatief (rood)'},
];

/**
 * Generate a unique ID with the given prefix.
 *
 * @param {string} prefix - 'q' for questions, 'r' for results
 * @param {string[]} existingIds - IDs already in use
 * @returns {string}
 */
export function generateId(prefix, existingIds) {
    const used = new Set(existingIds);
    let counter = 1;
    while (used.has(`${prefix}_${counter}`)) {
        counter++;
    }
    return `${prefix}_${counter}`;
}

/**
 * Create a blank question with default values.
 *
 * @param {string} id - Question ID
 * @returns {Object}
 */
export function createBlankQuestion(id) {
    return {
        id,
        type: 'radio',
        label: '',
        required: true,
    };
}

/**
 * Create a blank result with default values.
 *
 * @param {string} id - Result ID
 * @param {boolean} isDefault - Whether this is the default/fallback result
 * @returns {Object}
 */
export function createBlankResult(id, isDefault = false) {
    return {
        id,
        condition: isDefault ? 'default' : {match: 'all', rules: []},
        title: '',
        body: '',
        style: 'neutral',
    };
}

/**
 * Create a blank leaf rule.
 *
 * @returns {Object}
 */
export function createBlankRule() {
    return {question: '', op: '==', value: ''};
}

/**
 * Create a blank rule group.
 *
 * @param {string} match - 'all' or 'any'
 * @returns {Object}
 */
export function createBlankGroup(match = 'all') {
    return {match, rules: [createBlankRule()]};
}

/**
 * Collect question and result IDs for branch target dropdowns.
 * Only includes questions that appear AFTER afterIndex in the array
 * (to prevent backward branches that cause infinite loops), plus all results.
 *
 * @param {Array} questions - All questions
 * @param {Array} results - All results
 * @param {number} afterIndex - Index of the current question
 * @returns {{questions: Array, results: Array}}
 */
export function collectTargets(questions, results, afterIndex = -1) {
    const forwardQuestions = (questions || [])
        .filter((_, i) => i > afterIndex)
        .map(q => ({value: q.id, label: `Vraag: ${q.label || q.id}`}));
    return {
        questions: forwardQuestions,
        results: (results || []).map(r => ({value: r.id, label: `Resultaat: ${r.title || r.id}`})),
    };
}

/**
 * Get the possible answer values for a question (for rule builder value dropdowns).
 *
 * @param {Object} question
 * @returns {Array|null} Options array or null if free-text input
 */
export function getQuestionValueOptions(question) {
    if (!question) return null;
    if (question.type === 'radio' || question.type === 'select') {
        return (question.options || []).map(opt => ({
            value: opt.value,
            label: opt.label || opt.value,
        }));
    }
    if (question.type === 'checkbox') {
        return [
            {value: 'true', label: 'Aangevinkt'},
            {value: 'false', label: 'Niet aangevinkt'},
        ];
    }
    return null;
}

/**
 * Get the allowed operators for a question type.
 *
 * @param {Object|null} question
 * @returns {Array} Operator options for SelectControl
 */
export function getOperatorsForQuestion(question) {
    if (question?.type === 'checkbox') return EQUALITY_OPERATORS;
    return OPERATORS;
}

/**
 * Count how many times an ID is referenced in branch targets and result conditions.
 *
 * @param {string} id - Question or result ID
 * @param {Array} questions - All questions
 * @param {Array} results - All results
 * @returns {number} Reference count
 */
export function countReferences(id, questions, results) {
    let count = 0;

    for (const q of (questions || [])) {
        if (q.branch?.target === id) count++;
    }

    for (const r of (results || [])) {
        if (typeof r.condition === 'object') {
            count += countRuleReferences(id, r.condition);
        }
    }

    return count;
}

/**
 * Recursively count references to an ID within a rule tree.
 */
function countRuleReferences(id, rule) {
    if (!rule) return 0;
    if (rule.question === id) return 1;
    if (rule.match && Array.isArray(rule.rules)) {
        return rule.rules.reduce((sum, r) => sum + countRuleReferences(id, r), 0);
    }
    return 0;
}

/**
 * Rename an ID across all branch targets and result condition rules.
 *
 * @param {string} oldId - Previous ID
 * @param {string} newId - New ID
 * @param {Array} questions - Questions array (mutated in place)
 * @param {Array} results - Results array (mutated in place)
 */
export function cascadeRename(oldId, newId, questions, results) {
    for (const q of (questions || [])) {
        if (q.branch?.target === oldId) {
            q.branch.target = newId;
        }
    }

    for (const r of (results || [])) {
        if (typeof r.condition === 'object') {
            renameInRule(oldId, newId, r.condition);
        }
    }
}

/**
 * Recursively rename question references in a rule tree.
 */
function renameInRule(oldId, newId, rule) {
    if (!rule) return;
    if (rule.question === oldId) {
        rule.question = newId;
    }
    if (rule.match && Array.isArray(rule.rules)) {
        rule.rules.forEach(r => renameInRule(oldId, newId, r));
    }
}
