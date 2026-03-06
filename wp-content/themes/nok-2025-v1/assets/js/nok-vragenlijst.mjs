/**
 * Vragenlijst (Questionnaire) Module
 *
 * Multi-step wizard with conditional branching, structured rule evaluation,
 * and popup integration. Renders questions from a JSON config embedded in the
 * container by the PHP template.
 *
 * @version 1.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / hnldesign
 * @since 2026
 */

export const NAME = 'NOK-Vragenlijst';

import {logger} from './domule/core.log.min.mjs';
import {calculateBMI} from './nok-bmi-calculator.mjs';

// ============================================================================
// RULE EVALUATOR
// ============================================================================

const MAX_DEPTH = 8;
const MAX_RULES_PER_GROUP = 50;

/**
 * Evaluate a structured rule against an answers map.
 *
 * Supports leaf rules (question/op/value) and group rules (match: all/any
 * with nested rules). Recursion depth and group size are capped to prevent
 * stack overflow from malformed configs.
 *
 * @param {Object} rule - Leaf rule {question, op, value} or group {match, rules[]}
 * @param {Object} answers - Map of questionId → answer value
 * @param {number} [depth=0] - Current recursion depth
 * @returns {boolean} Whether the rule matches
 */
function evaluateRule(rule, answers, depth = 0) {
    if (depth > MAX_DEPTH) return false;

    // Leaf rule: compare a question's answer to a value
    if (rule.question) {
        const answer = answers[rule.question];
        if (answer === undefined) return false;

        const a = Number(answer), v = Number(rule.value);
        const useNumeric = !isNaN(a) && !isNaN(v);
        const left = useNumeric ? a : String(answer);
        const right = useNumeric ? v : String(rule.value);

        switch (rule.op) {
            case '==': return left === right;
            case '!=': return left !== right;
            case '>':  return left > right;
            case '>=': return left >= right;
            case '<':  return left < right;
            case '<=': return left <= right;
            default:   return false;
        }
    }

    // Group rule: combine child rules with all/any
    if (rule.match && Array.isArray(rule.rules)) {
        if (rule.rules.length > MAX_RULES_PER_GROUP) return false;
        const fn = rule.match === 'all' ? 'every' : 'some';
        return rule.rules[fn](r => evaluateRule(r, answers, depth + 1));
    }

    return false;
}

// ============================================================================
// ENGINE — State machine for questionnaire flow
// ============================================================================

class VragenlijstEngine {
    /**
     * @param {Object} config - Parsed questionnaire config
     * @param {Array} config.questions - Question definitions
     * @param {Array} config.results - Result definitions with conditions
     * @param {Object} [config.settings] - Display settings
     */
    constructor(config) {
        this.questions = config.questions || [];
        this.results = config.results || [];
        this.settings = config.settings || {};
        this.answers = {};
        this.history = [];
    }

    reset() {
        this.answers = {};
        this.history = [];
    }

    getCurrentQuestion() {
        if (!this.history.length) return null;
        const id = this.history[this.history.length - 1];
        return this.questions.find(q => q.id === id) || null;
    }

    /**
     * Start the questionnaire — reset state and return first question.
     * @returns {Object|null} First question, or null if no questions
     */
    start() {
        this.reset();
        const first = this.questions[0];
        if (!first) return null;
        this.history.push(first.id);
        return first;
    }

    setAnswer(questionId, value) {
        this.answers[questionId] = value;
    }

    /**
     * Check whether the current question has a valid answer to proceed.
     * @returns {boolean}
     */
    canAdvance() {
        const q = this.getCurrentQuestion();
        if (!q) return false;
        if (q.type === 'info') return true;

        const answer = this.answers[q.id];
        if (answer === undefined || answer === '') {
            return q.required === false;
        }

        if ((q.type === 'number' || q.type === 'bmi') && q.validation) {
            const num = Number(answer);
            if (isNaN(num)) return false;
            if (q.validation.min !== undefined && num < q.validation.min) return false;
            if (q.validation.max !== undefined && num > q.validation.max) return false;
        }

        return true;
    }

    /**
     * Get a human-readable validation error for the current question.
     * @returns {string|null}
     */
    getValidationError() {
        const q = this.getCurrentQuestion();
        if (!q) return null;

        const answer = this.answers[q.id];
        if ((answer === undefined || answer === '') && q.required !== false) {
            return 'Dit veld is verplicht';
        }

        if ((q.type === 'number' || q.type === 'bmi') && q.validation && answer !== undefined && answer !== '') {
            const num = Number(answer);
            if (isNaN(num)) return 'Vul een geldig nummer in';
            if (q.validation.error) return q.validation.error;
        }

        return null;
    }

    /**
     * Advance to the next question or result.
     *
     * Evaluates the current question's branch (if any), then either
     * skips to a target or proceeds sequentially.
     *
     * @returns {{type: string, question?: Object, result?: Object}}
     */
    next() {
        const current = this.getCurrentQuestion();
        if (!current) return {type: 'error'};

        // Evaluate branch on current question
        if (current.branch) {
            const branch = current.branch;
            const rule = {
                question: current.id,
                op: branch.op,
                value: branch.value
            };

            if (evaluateRule(rule, this.answers)) {
                const target = branch.target;

                // Target is a result ID (r_ prefix) — short-circuit
                if (typeof target === 'string' && target.startsWith('r_')) {
                    const result = this.results.find(r => r.id === target);
                    return {type: 'result', result: result || this.getResult()};
                }

                // Target is a question ID — skip to it
                const targetQ = this.questions.find(q => q.id === target);
                if (targetQ) {
                    this.history.push(target);
                    return {type: 'question', question: targetQ};
                }
            }
        }

        // Sequential: next question in array
        const currentIndex = this.questions.findIndex(q => q.id === current.id);
        const nextIndex = currentIndex + 1;

        if (nextIndex < this.questions.length) {
            const nextQ = this.questions[nextIndex];
            this.history.push(nextQ.id);
            return {type: 'question', question: nextQ};
        }

        // End of questions — evaluate results
        return {type: 'result', result: this.getResult()};
    }

    /**
     * Go back to the previous question.
     *
     * Pops the history stack and clears all answers for questions
     * after the new current position. This prevents stale answers
     * from influencing results after the user changes an earlier answer.
     *
     * @returns {Object|null} The previous question, or null if at start
     */
    prev() {
        if (this.history.length <= 1) return null;

        this.history.pop();

        // Clear answers after current position to prevent stale data
        const currentId = this.history[this.history.length - 1];
        const currentIndex = this.questions.findIndex(q => q.id === currentId);
        for (let i = currentIndex + 1; i < this.questions.length; i++) {
            delete this.answers[this.questions[i].id];
        }

        return this.getCurrentQuestion();
    }

    canGoBack() {
        return this.history.length > 1 && this.settings.allow_back !== false;
    }

    /**
     * Evaluate result conditions and return the first match.
     * Falls back to the result with condition: "default".
     *
     * @returns {Object|null} Matching result definition
     */
    getResult() {
        for (const result of this.results) {
            if (result.condition === 'default') continue;
            if (typeof result.condition === 'object' && evaluateRule(result.condition, this.answers)) {
                return result;
            }
        }
        return this.results.find(r => r.condition === 'default') || null;
    }

    /**
     * Calculate progress as percentage.
     * Uses visited count vs total questions. Percentage-based to avoid
     * confusing jumps when branching changes the visible question count.
     *
     * @returns {number} 0-100
     */
    getProgress() {
        if (!this.history.length) return 0;
        const total = this.questions.length;
        if (total <= 1) return 100;
        return Math.min(100, Math.round((this.history.length / total) * 100));
    }
}

// ============================================================================
// RENDERER — DOM construction and view management
// ============================================================================

class VragenlijstRenderer {
    /**
     * @param {HTMLElement} container - The .nok-vragenlijst wrapper
     * @param {VragenlijstEngine} engine - The questionnaire engine
     */
    constructor(container, engine) {
        this.container = container;
        this.engine = engine;
        this.intro = container.querySelector('.nok-vragenlijst__intro');
        this.onRestart = null;

        this.createWizardDOM();
    }

    /** Build the wizard and result container elements */
    createWizardDOM() {
        // Wizard wrapper
        this.wizard = document.createElement('div');
        this.wizard.className = 'nok-vragenlijst__wizard';
        this.wizard.hidden = true;

        // Progress bar
        this.progressBar = document.createElement('div');
        this.progressBar.className = 'nok-vragenlijst__progress';
        this.progressBar.setAttribute('role', 'progressbar');
        this.progressBar.setAttribute('aria-valuenow', '0');
        this.progressBar.setAttribute('aria-valuemin', '0');
        this.progressBar.setAttribute('aria-valuemax', '100');

        this.progressFill = document.createElement('div');
        this.progressFill.className = 'nok-vragenlijst__progress-fill';
        this.progressBar.appendChild(this.progressFill);
        this.wizard.appendChild(this.progressBar);

        // Question area (aria-live for screen readers)
        this.questionArea = document.createElement('div');
        this.questionArea.className = 'nok-vragenlijst__question-area';
        this.questionArea.setAttribute('aria-live', 'polite');
        this.wizard.appendChild(this.questionArea);

        // Navigation buttons
        this.nav = document.createElement('div');
        this.nav.className = 'nok-vragenlijst__nav';

        this.prevButton = document.createElement('button');
        this.prevButton.type = 'button';
        this.prevButton.className = 'nok-button nok-vragenlijst__prev';
        this.prevButton.innerHTML = '<span>Vorige</span>';
        this.prevButton.disabled = true;

        this.nextButton = document.createElement('button');
        this.nextButton.type = 'button';
        this.nextButton.className = 'nok-button nok-bg-darkerblue nok-text-contrast nok-vragenlijst__next';
        this.nextButton.innerHTML = '<span>Volgende</span>';
        this.nextButton.disabled = true;

        this.nav.appendChild(this.prevButton);
        this.nav.appendChild(this.nextButton);
        this.wizard.appendChild(this.nav);

        // Result container
        this.resultContainer = document.createElement('div');
        this.resultContainer.className = 'nok-vragenlijst__result-container';
        this.resultContainer.hidden = true;

        // Insert into DOM after intro (or as first children)
        if (this.intro) {
            this.intro.after(this.wizard, this.resultContainer);
        } else {
            this.container.appendChild(this.wizard);
            this.container.appendChild(this.resultContainer);
        }
    }

    showIntro() {
        if (this.intro) this.intro.hidden = false;
        this.wizard.hidden = true;
        this.resultContainer.hidden = true;
    }

    showWizard() {
        if (this.intro) this.intro.hidden = true;
        this.wizard.hidden = false;
        this.resultContainer.hidden = true;
    }

    showResult(result) {
        if (this.intro) this.intro.hidden = true;
        this.wizard.hidden = true;
        this.resultContainer.hidden = false;
        this.resultContainer.innerHTML = this.buildResultHTML(result);

        const restartBtn = this.resultContainer.querySelector('.nok-vragenlijst__restart');
        if (restartBtn) {
            restartBtn.addEventListener('click', () => this.onRestart?.());
        }
    }

    /**
     * Render a question into the question area.
     * Updates progress bar and navigation button states.
     *
     * @param {Object} question - Question definition from config
     */
    renderQuestion(question) {
        this.questionArea.innerHTML = this.buildQuestionHTML(question);
        this.updateProgress();
        this.updateNav();

        // Wire up BMI compound input if present
        if (question.type === 'bmi') {
            this.initBMIInput();
        }

        // Focus first interactive element for accessibility
        requestAnimationFrame(() => {
            const input = this.questionArea.querySelector(
                'input:not([type="hidden"]), select, textarea'
            );
            if (input) input.focus();
        });
    }

    /** Wire up the BMI compound input: height+weight→calc, toggle, direct entry. */
    initBMIInput() {
        const group = this.questionArea.querySelector('.nok-vragenlijst__bmi-group');
        if (!group) return;

        const hwSection = group.querySelector('.nok-vragenlijst__bmi-hw');
        const directSection = group.querySelector('.nok-vragenlijst__bmi-direct');
        const heightInput = group.querySelector('.nok-vragenlijst__bmi-height');
        const weightInput = group.querySelector('.nok-vragenlijst__bmi-weight');
        const bmiResult = group.querySelector('.nok-vragenlijst__bmi-result');
        const bmiValue = group.querySelector('.nok-vragenlijst__bmi-value');
        const directInput = group.querySelector('.nok-vragenlijst__bmi-direct-input');
        const hiddenInput = group.querySelector('.nok-vragenlijst__bmi-answer');
        const toggleBtn = group.querySelector('.nok-vragenlijst__bmi-toggle');

        let isDirect = false;

        const recalc = () => {
            const h = parseFloat(heightInput.value);
            const w = parseFloat(weightInput.value);
            if (h > 0 && w > 0) {
                const bmi = Math.round(calculateBMI(h, w) * 10) / 10;
                bmiValue.textContent = bmi.toFixed(1);
                bmiResult.hidden = false;
                hiddenInput.value = bmi;
                hiddenInput.dispatchEvent(new Event('input', {bubbles: true}));
            } else {
                bmiValue.textContent = '—';
                bmiResult.hidden = true;
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('input', {bubbles: true}));
            }
        };

        heightInput.addEventListener('input', recalc);
        weightInput.addEventListener('input', recalc);

        directInput.addEventListener('input', () => {
            hiddenInput.value = directInput.value;
            hiddenInput.dispatchEvent(new Event('input', {bubbles: true}));
        });

        toggleBtn.addEventListener('click', () => {
            isDirect = !isDirect;
            hwSection.hidden = isDirect;
            directSection.hidden = !isDirect;
            toggleBtn.textContent = isDirect
                ? 'Liever lengte & gewicht invoeren?'
                : 'BMI al bekend? Direct invoeren';

            // Clear answer when switching modes
            hiddenInput.value = '';
            hiddenInput.dispatchEvent(new Event('input', {bubbles: true}));
            if (isDirect) {
                directInput.value = '';
                directInput.focus();
            } else {
                heightInput.value = '';
                weightInput.value = '';
                bmiResult.hidden = true;
                bmiValue.textContent = '—';
                heightInput.focus();
            }
        });
    }

    // --- HTML builders ---

    buildQuestionHTML(question) {
        const existingAnswer = this.engine.answers[question.id];
        let html = '<div class="nok-vragenlijst__question">';

        if (question.type !== 'info') {
            html += `<label class="nok-vragenlijst__label">${escapeHtml(question.label)}</label>`;
        }

        if (question.help) {
            html += `<p class="nok-vragenlijst__help">${escapeHtml(question.help)}</p>`;
        }

        html += '<div class="nok-vragenlijst__field">';
        html += this.buildInputHTML(question, existingAnswer);
        html += '</div>';

        html += '<div class="nok-vragenlijst__error" hidden></div>';
        html += '</div>';
        return html;
    }

    buildInputHTML(question, existingAnswer) {
        const qid = escapeAttr(question.id);

        switch (question.type) {
            case 'text':
                return `<input type="text" class="nok-vragenlijst__input"
                    data-qid="${qid}"
                    value="${escapeAttr(existingAnswer || '')}"
                    ${question.required !== false ? 'required' : ''}>`;

            case 'number': {
                const min = question.validation?.min ?? '';
                const max = question.validation?.max ?? '';
                return `<input type="number" inputmode="decimal" class="nok-vragenlijst__input"
                    data-qid="${qid}"
                    value="${escapeAttr(existingAnswer ?? '')}"
                    ${min !== '' ? `min="${min}"` : ''}
                    ${max !== '' ? `max="${max}"` : ''}
                    step="any"
                    ${question.required !== false ? 'required' : ''}>`;
            }

            case 'bmi': {
                // Compound input: height + weight → auto-calculated BMI, with direct-entry toggle
                const bmiVal = existingAnswer ?? '';
                return `<div class="nok-vragenlijst__bmi-group">
                    <div class="nok-vragenlijst__bmi-hw">
                        <label class="nok-vragenlijst__bmi-field">
                            <span>Lengte (cm)</span>
                            <input type="number" inputmode="decimal" class="nok-vragenlijst__input nok-vragenlijst__bmi-height"
                                min="80" max="220" step="1" placeholder="bijv. 175">
                        </label>
                        <label class="nok-vragenlijst__bmi-field">
                            <span>Gewicht (kg)</span>
                            <input type="number" inputmode="decimal" class="nok-vragenlijst__input nok-vragenlijst__bmi-weight"
                                min="10" max="250" step="0.1" placeholder="bijv. 92">
                        </label>
                        <div class="nok-vragenlijst__bmi-result" hidden>
                            Uw BMI: <strong class="nok-vragenlijst__bmi-value">—</strong>
                        </div>
                    </div>
                    <div class="nok-vragenlijst__bmi-direct" hidden>
                        <input type="number" inputmode="decimal" class="nok-vragenlijst__input nok-vragenlijst__bmi-direct-input"
                            min="${question.validation?.min ?? 10}" max="${question.validation?.max ?? 80}"
                            step="any" value="${escapeAttr(bmiVal)}"
                            placeholder="bijv. 30.0" ${question.required !== false ? 'required' : ''}>
                    </div>
                    <button type="button" class="nok-vragenlijst__bmi-toggle">
                        BMI al bekend? Direct invoeren
                    </button>
                    <input type="hidden" class="nok-vragenlijst__bmi-answer" data-qid="${qid}" value="${escapeAttr(bmiVal)}">
                </div>`;
            }

            case 'radio':
                return (question.options || []).map(opt => {
                    const checked = existingAnswer === opt.value;
                    const inputId = `${qid}_${escapeAttr(opt.value)}`;
                    return `<label class="nok-vragenlijst__radio-option${checked ? ' is-selected' : ''}" for="${inputId}">
                        <input type="radio" id="${inputId}" name="${qid}"
                            value="${escapeAttr(opt.value)}" data-qid="${qid}"
                            ${checked ? 'checked' : ''}>
                        <span>${escapeHtml(opt.label)}</span>
                    </label>`;
                }).join('');

            case 'select': {
                const options = (question.options || []).map(opt => {
                    const selected = existingAnswer === opt.value ? ' selected' : '';
                    return `<option value="${escapeAttr(opt.value)}"${selected}>${escapeHtml(opt.label)}</option>`;
                }).join('');
                return `<select class="nok-vragenlijst__input" data-qid="${qid}"
                    ${question.required !== false ? 'required' : ''}>
                    <option value="">Maak een keuze...</option>
                    ${options}
                </select>`;
            }

            case 'checkbox': {
                const checked = existingAnswer ? ' checked' : '';
                return `<label class="nok-vragenlijst__checkbox-option" for="${qid}_cb">
                    <input type="checkbox" id="${qid}_cb" data-qid="${qid}"${checked}>
                    <span>${escapeHtml(question.label)}</span>
                </label>`;
            }

            case 'info':
                return `<div class="nok-vragenlijst__info-text">${escapeHtml(question.label || '')}</div>`;

            default:
                return '';
        }
    }

    buildResultHTML(result) {
        if (!result) return `<p>${escapeHtml('Geen resultaat beschikbaar.')}</p>`;

        const styleClass = result.style ? ` nok-vragenlijst__result--${escapeAttr(result.style)}` : '';
        let html = `<div class="nok-vragenlijst__result${styleClass}">`;
        html += `<h3 class="nok-vragenlijst__result-title">${escapeHtml(result.title)}</h3>`;

        if (result.body) {
            // Body HTML is pre-sanitized with wp_kses_post on save
            html += `<div class="nok-vragenlijst__result-body">${result.body}</div>`;
        }

        if (result.cta_url && result.cta_text) {
            html += `<a href="${escapeAttr(result.cta_url)}"
                class="nok-button nok-bg-darkerblue nok-text-contrast nok-vragenlijst__result-cta">
                <span>${escapeHtml(result.cta_text)}</span>
            </a>`;
        }

        html += `<button type="button" class="nok-button nok-vragenlijst__restart">
            <span>Opnieuw</span>
        </button>`;
        html += '</div>';
        return html;
    }

    // --- State updates ---

    updateProgress() {
        const percent = this.engine.getProgress();
        this.progressFill.style.width = `${percent}%`;
        this.progressBar.setAttribute('aria-valuenow', String(percent));
    }

    updateNav() {
        const engine = this.engine;
        this.prevButton.disabled = !engine.canGoBack();
        this.nextButton.disabled = !engine.canAdvance();

        // "Bekijk resultaat" on last question, "Volgende" otherwise
        const current = engine.getCurrentQuestion();
        const currentIndex = engine.questions.findIndex(q => q.id === current?.id);
        const isLast = currentIndex === engine.questions.length - 1;

        // Also check if current branch would skip to result
        let branchToResult = false;
        if (current?.branch && current.branch.target?.startsWith('r_')) {
            const rule = {question: current.id, op: current.branch.op, value: current.branch.value};
            branchToResult = evaluateRule(rule, engine.answers);
        }

        const submitText = engine.settings.submit_button_text || 'Bekijk resultaat';
        this.nextButton.querySelector('span').textContent =
            (isLast || branchToResult) ? submitText : 'Volgende';
    }

    /**
     * Read the current answer from the rendered input element.
     * @returns {*} Answer value, or undefined for info-type questions
     */
    getCurrentAnswer() {
        const q = this.engine.getCurrentQuestion();
        if (!q || q.type === 'info') return undefined;

        if (q.type === 'radio') {
            const checked = this.questionArea.querySelector(`input[name="${q.id}"]:checked`);
            return checked ? checked.value : undefined;
        }

        if (q.type === 'checkbox') {
            const cb = this.questionArea.querySelector(`input[data-qid="${q.id}"]`);
            return cb ? cb.checked : false;
        }

        const input = this.questionArea.querySelector(`[data-qid="${q.id}"]`);
        return input ? (input.value || undefined) : undefined;
    }

    showError(message) {
        const el = this.questionArea.querySelector('.nok-vragenlijst__error');
        if (el) {
            el.textContent = message;
            el.hidden = false;
        }
    }

    hideError() {
        const el = this.questionArea.querySelector('.nok-vragenlijst__error');
        if (el) el.hidden = true;
    }
}

// ============================================================================
// ESCAPING UTILITIES
// ============================================================================

const escapeDiv = document.createElement('div');

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    escapeDiv.textContent = str;
    return escapeDiv.innerHTML;
}

function escapeAttr(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ============================================================================
// CONTROLLER — Wires engine + renderer + event handling
// ============================================================================

function initQuestionnaire(container) {
    // Parse embedded JSON config
    const scriptEl = container.querySelector('script[type="application/json"]');
    if (!scriptEl) {
        logger.warn(NAME, 'No config script found');
        return;
    }

    let config;
    try {
        config = JSON.parse(scriptEl.textContent);
    } catch (e) {
        logger.error(NAME, 'Invalid config JSON', e);
        return;
    }

    if (!config.questions?.length) {
        logger.warn(NAME, 'No questions in config');
        return;
    }

    const engine = new VragenlijstEngine(config);
    const renderer = new VragenlijstRenderer(container, engine);

    // --- Start button ---
    const startBtn = container.querySelector('.nok-vragenlijst__start');
    if (startBtn) {
        startBtn.addEventListener('click', () => {
            const first = engine.start();
            if (!first) return;
            renderer.showWizard();
            renderer.renderQuestion(first);
        });
    }

    // --- Answer input handling ---
    function onAnswerChange() {
        const answer = renderer.getCurrentAnswer();
        const q = engine.getCurrentQuestion();
        if (q && answer !== undefined) {
            engine.setAnswer(q.id, answer);
        }
        renderer.hideError();
        renderer.updateNav();

        // Update radio visual state
        if (q?.type === 'radio') {
            renderer.questionArea.querySelectorAll('.nok-vragenlijst__radio-option').forEach(opt => {
                opt.classList.toggle('is-selected', !!opt.querySelector('input')?.checked);
            });
        }
    }

    renderer.questionArea.addEventListener('input', onAnswerChange);
    renderer.questionArea.addEventListener('change', onAnswerChange);

    // --- Navigation ---
    function advance() {
        // Capture current answer
        const answer = renderer.getCurrentAnswer();
        const q = engine.getCurrentQuestion();
        if (q && q.type !== 'info' && answer !== undefined) {
            engine.setAnswer(q.id, answer);
        }

        if (!engine.canAdvance()) {
            const error = engine.getValidationError();
            if (error) renderer.showError(error);
            return;
        }

        const result = engine.next();
        if (result.type === 'question') {
            renderer.renderQuestion(result.question);
        } else if (result.type === 'result') {
            renderer.showResult(result.result);
        }
    }

    renderer.nextButton.addEventListener('click', advance);

    renderer.prevButton.addEventListener('click', () => {
        const prev = engine.prev();
        if (prev) renderer.renderQuestion(prev);
    });

    // --- Restart ---
    renderer.onRestart = () => {
        engine.reset();
        renderer.showIntro();
    };

    // --- Keyboard: Enter advances ---
    container.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || e.shiftKey) return;
        if (e.target.tagName === 'TEXTAREA') return;
        if (e.target.closest('a, button') && !e.target.closest('.nok-vragenlijst__next')) return;

        if (engine.canAdvance()) {
            e.preventDefault();
            advance();
        }
    });

    // --- Popup close handler ---
    const popup = container.closest('nok-popup');
    if (popup) {
        popup.addEventListener('nok-popup:close', () => {
            engine.reset();
            renderer.showIntro();
        });
    }

    logger.info(NAME, `Loaded: ${config.questions.length} questions, ${config.results.length} results`);
}

// ============================================================================
// DOMULE MODULE INTERFACE
// ============================================================================

/**
 * Module initialization (DOMule standard).
 * @param {NodeList|Array} elements - Elements with data-requires pointing here
 * @returns {string} Status message
 */
export function init(elements) {
    elements.forEach(container => {
        try {
            initQuestionnaire(container);
        } catch (e) {
            logger.error(NAME, 'Init failed', e);
        }
    });
    return `Initialized ${elements.length} questionnaire(s)`;
}

// Export for testing
export {evaluateRule, VragenlijstEngine};
