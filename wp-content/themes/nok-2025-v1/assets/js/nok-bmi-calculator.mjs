/**
 * BMI Calculator Core Module
 *
 * Compact calculation engine for BMI operations supporting adults and children (2-18 years).
 * Implements WHO/IOTF standards for BMI classification and treatment eligibility assessment.
 *
 * @version 3.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / hnldesign
 * @since 2024
 *
 * @example
 * // Basic BMI calculation
 * const result = BMICalculator.calculate({
 *   height: 175,
 *   weight: 70
 * });
 * console.log(result.bmi); // 22.86
 * console.log(result.category.label); // "Normal"
 *
 * @example
 * // Calculate target weight for healthy BMI
 * const range = BMICalculator.healthyWeightRange(175, [18.5, 25]);
 * console.log(`Healthy weight: ${range.min.toFixed(1)} - ${range.max.toFixed(1)} kg`);
 */

export const NAME = "NOK-BMI-Calculator";

import {debouncedEvent} from "./domule/util.debounce.mjs";
import {logger} from "./domule/core.log.mjs";

// ============================================================================
// CONSTANTS
// ============================================================================

/**
 * BMI cutoff values for classification.
 * @const
 * @private
 */
const CUTOFFS = {
    adults: [18.5, 25, 30, 35, 40],
    children: {
        girls: {
            2: [14.74, 18, 19.8, 23.4], 3: [14.38, 17.6, 19.4, 23.2], 4: [14.15, 17.3, 19.2, 23.5],
            5: [13.97, 17.2, 19.2, 24.2], 6: [13.92, 17.3, 19.7, 25.5], 7: [14, 17.8, 20.5, 27.4],
            8: [14.16, 18.4, 21.6, 29.8], 9: [14.42, 19.1, 22.8, 32.3], 10: [14.78, 19.9, 24.1, 34.6],
            11: [15.25, 20.7, 25.4, 36.5], 12: [15.83, 21.7, 26.7, 38], 13: [16.43, 22.6, 27.8, 38.9],
            14: [17, 23.3, 28.6, 39.4], 15: [17.52, 23.9, 29.1, 39.7], 16: [17.95, 24.4, 29.4, 39.9],
            17: [18.33, 24.7, 29.7, 39.9], 18: [18.5, 25, 30, 40]
        },
        boys: {
            2: [14.95, 18.4, 20.1, 23.6], 3: [14.54, 17.9, 19.6, 22.2], 4: [14.30, 17.6, 19.3, 21.7],
            5: [14.12, 17.4, 19.3, 21.7], 6: [14, 17.6, 19.8, 22.2], 7: [14, 17.9, 20.6, 23.2],
            8: [14.20, 18.4, 21.6, 24.9], 9: [14.41, 19.1, 22.8, 27], 10: [14.69, 19.8, 24, 29.5],
            11: [15.03, 20.6, 25.1, 32.2], 12: [15.47, 21.2, 26, 34.8], 13: [15.98, 21.9, 26.8, 36.9],
            14: [16.54, 22.6, 27.6, 38.4], 15: [17.13, 23.3, 28.3, 39.1], 16: [17.70, 23.9, 28.9, 39.5],
            17: [18.24, 24.5, 29.4, 39.8], 18: [18.5, 25, 30, 40]
        }
    }
};

/**
 * Clinical input bounds for validation.
 * @const
 * @private
 */
const BOUNDS = {
    height: {min: 80, max: 220},
    weight: {min: 10, max: 250},
    bmi: {min: 15, max: 60}
};

/**
 * Treatment eligibility thresholds.
 * @const
 * @private
 */
const TREATMENT_CRITERIA = {
    nokClinics: 27,
    regular: 35
};

/**
 * BMI category color palette.
 * @const
 * @private
 */
const BMI_COLORS = [
    'var(--color-1, #FFC425)', // Underweight
    'var(--color-2, #4CAF50)', // Normal
    'var(--color-3, #CDDC39)', // Overweight
    'var(--color-4, #FF9800)', // Obese I
    'var(--color-5, #ff6f00)', // Obese II
    'var(--color-6, #D32F2F)'  // Obese III
];

/**
 * Category labels and risk levels.
 * @const
 * @private
 */
const CATEGORY_LABELS = [
    'Ondergewicht', 'Normal', 'Overgewicht',
    'Obesitas Klasse I', 'Obesitas Klasse II', 'Obesitas Klasse III'
];

const RISK_LEVELS = [
    'matig verhoogd', 'normaal', 'licht verhoogd',
    'matig verhoogd', 'sterk verhoogd', 'extreem verhoogd'
];

const CLASSIFICATIONS = [
    'ondergewicht', 'een normaal gewicht', 'overgewicht',
    'obesitas (klasse I)', 'ernstige obesitas (klasse II)', 'zeer ernstige obesitas (klasse III)'
];

/**
 * UI timing constants.
 * @const
 * @private
 */
const TIMING = {
    DEBOUNCE_DELAY: 33,
    STATE_TRANSITION_DELAY: 250,
    UPDATE_RETRY_DELAY: 10
};

// ============================================================================
// ELEMENT ASSOCIATION (replaces DOM pollution)
// ============================================================================

/**
 * WeakMap for element-specific data.
 * Automatically garbage collected when elements are removed.
 * @private
 */
const elementData = new WeakMap();

/**
 * Store data for an element.
 * @private
 */
function setElementData(element, key, value) {
    if (!elementData.has(element)) {
        elementData.set(element, {});
    }
    elementData.get(element)[key] = value;
}

/**
 * Retrieve data for an element.
 * @private
 */
function getElementData(element, key) {
    return elementData.get(element)?.[key];
}

// ============================================================================
// VALIDATION
// ============================================================================

/**
 * Validate input parameters against clinical ranges.
 * @private
 * @param {Object} params - Input parameters
 */
function validateInputs(params) {
    const warnings = [];

    if (params.height !== undefined && params.height > BOUNDS.height.max) {
        warnings.push(`Height ${params.height}cm exceeds typical range (max: ${BOUNDS.height.max}cm)`);
    }

    if (params.weight !== undefined && params.weight > BOUNDS.weight.max) {
        warnings.push(`Weight ${params.weight}kg exceeds typical range (max: ${BOUNDS.weight.max}kg)`);
    }

    if (params.bmi !== undefined) {
        if (params.bmi < BOUNDS.bmi.min) {
            warnings.push(`BMI ${params.bmi.toFixed(1)} below clinical range (min: ${BOUNDS.bmi.min})`);
        }
        if (params.bmi > BOUNDS.bmi.max) {
            warnings.push(`BMI ${params.bmi.toFixed(1)} exceeds clinical range (max: ${BOUNDS.bmi.max})`);
        }
    }

    warnings.forEach(msg => logger.warn(NAME, msg));
}

// ============================================================================
// CORE CALCULATIONS
// ============================================================================

/**
 * Calculate BMI from height and weight.
 * @param {number} height - Height in cm
 * @param {number} weight - Weight in kg
 * @returns {number} BMI value
 */
function calculateBMI(height, weight) {
    validateInputs({height, weight});
    return weight / Math.pow(height / 100, 2);
}

/**
 * Calculate weight from BMI and height.
 * @param {number} bmi - Target BMI
 * @param {number} height - Height in cm
 * @returns {number} Weight in kg
 */
function weightFromBMI(bmi, height) {
    validateInputs({bmi, height});
    return bmi * Math.pow(height / 100, 2);
}

/**
 * Calculate height from BMI and weight.
 * @param {number} bmi - Target BMI
 * @param {number} weight - Weight in kg
 * @returns {number} Height in cm
 */
function heightFromBMI(bmi, weight) {
    validateInputs({bmi, weight});
    return Math.sqrt(weight / bmi) * 100;
}

/**
 * Round to one decimal place.
 * @private
 */
function round(value) {
    return Math.round(value * 10) / 10;
}

/**
 * Calculate complete BMI profile.
 * @param {number} height - Height in cm
 * @param {number} weight - Weight in kg
 * @param {number} [bmi] - Pre-calculated BMI (optional)
 * @returns {Object} Complete BMI analysis
 */
function calculate(height, weight, bmi = null) {
    validateInputs({height, weight});
    bmi = bmi ?? calculateBMI(height, weight);

    return {
        height: round(height),
        weight: round(bmi ? weightFromBMI(bmi, height) : weight),
        bmi: round(bmi),
        category: classify(bmi),
        treatmentEligible: checkTreatmentEligibility(bmi),
        healthyWeightRange: healthyWeightRange(height, [18.5, 25], weight)
    };
}

/**
 * Calculate healthy weight range for height.
 * @param {number} height - Height in cm
 * @param {number[]} [cutoffs=[18.5, 25]] - BMI range
 * @param {number} [currentWeight] - Current weight for comparison
 * @returns {Object} Weight range with optional excess calculation
 */
function healthyWeightRange(height, cutoffs = [18.5, 25], currentWeight = null) {
    const heightM2 = Math.pow(height / 100, 2);
    const min = cutoffs[0] * heightM2;
    const max = cutoffs[1] * heightM2;

    const result = {min, max};

    if (currentWeight !== null) {
        result.current = currentWeight;
        result.excess = Math.max(0, currentWeight - max, min - currentWeight);
    }

    return result;
}

/**
 * Classify BMI into WHO categories.
 * @param {number} bmi - BMI value
 * @returns {Object} Classification with label, risk, and boundary info
 */
function classify(bmi) {
    const cutoffs = CUTOFFS.adults;

    let index = cutoffs.findIndex(cutoff => bmi <= cutoff);
    if (index === -1) index = cutoffs.length;

    return {
        index,
        label: CATEGORY_LABELS[index],
        risk: RISK_LEVELS[index],
        classification: CLASSIFICATIONS[index],
        nearBoundary: isNearBoundary(bmi, cutoffs, index)
    };
}

/**
 * Check treatment eligibility based on BMI.
 * @param {number} bmi - BMI value
 * @returns {Object} Eligibility flags
 */
function checkTreatmentEligibility(bmi) {
    return {
        nokClinics: bmi >= TREATMENT_CRITERIA.nokClinics,
        regular: bmi >= TREATMENT_CRITERIA.regular
    };
}

/**
 * Check if BMI is near category boundary.
 * @private
 * @param {number} bmi - BMI value
 * @param {number[]} cutoffs - Cutoff values
 * @param {number} currentIndex - Current category index
 * @returns {Object} Boundary proximity flags
 */
function isNearBoundary(bmi, cutoffs, currentIndex) {
    const lower = currentIndex > 0 &&
        (bmi - cutoffs[currentIndex - 1] <= 1) &&
        (bmi >= cutoffs[currentIndex - 1]);

    const upper = currentIndex < cutoffs.length &&
        (cutoffs[currentIndex] - bmi <= 1) &&
        (bmi <= cutoffs[currentIndex]);

    return {lower, upper};
}

/**
 * Get child-specific BMI cutoffs.
 * @param {number} age - Age in years (2-18)
 * @param {('boys'|'girls')} gender - Child's gender
 * @returns {number[]} Age-appropriate cutoffs
 */
function getChildCutoffs(age, gender) {
    const ageKey = Math.floor(Math.max(2, Math.min(18, age)));
    const childData = CUTOFFS.children[gender];
    return (childData && childData[ageKey]) || CUTOFFS.adults;
}

/**
 * Map BMI value to color index.
 * @private
 */
function mapToBMIColorIndex(bmi) {
    const index = CUTOFFS.adults.findIndex(cutoff => cutoff >= bmi);
    return index === -1 ? CUTOFFS.adults.length : index;
}

// ============================================================================
// GUI CONTROLLER
// ============================================================================

/**
 * Create GUI state controller.
 * @private
 * @param {HTMLElement} container - Calculator container
 * @returns {Object} Controller with state management
 */
function createGUIController(container) {
    const state = new Map();
    let isUpdating = false;
    let lastResults = {};
    let updateQueue = Promise.resolve();

    // Store timeout reference for cleanup
    let transitionTimeout = null;

    /**
     * Update input values from calculation result.
     * @private
     */
    const updateInputs = (result, options = {}) => {
        ['height', 'weight', 'bmi'].forEach(type => {
            const group = state.get(type);
            const newValue = result[type];

            if (lastResults[type] === newValue) return;

            if (group?.inputs) {
                group.inputs.forEach(input => {
                    if (type === 'bmi') {
                        const color = BMI_COLORS[mapToBMIColorIndex(newValue)];
                        container.style.setProperty('--bmi-classification-color-for-slider-handle', color);
                    }
                    if (parseFloat(input.value) !== newValue) {
                        input.value = newValue;
                    }
                });
            }
        });

        if (options.final) {
            clearTimeout(transitionTimeout);

            transitionTimeout = setTimeout(() => {
                updateContainerClasses(container, result);
                updateConclusionElements(container, result);
                container.style.setProperty(
                    '--bmi-classification-color',
                    BMI_COLORS[mapToBMIColorIndex(result.bmi)]
                );
                container.classList.remove('calculating');
                transitionTimeout = null;
            }, TIMING.STATE_TRANSITION_DELAY);
        } else {
            container.classList.add('calculating');
            clearTimeout(transitionTimeout);
        }

        lastResults = {...result};
    };

    /**
     * Perform calculation update.
     * @private
     */
    const updateCalculation = (changedType, options = {final: false}) => {
        updateQueue = updateQueue.then(async () => {
            if (isUpdating) {
                await new Promise(resolve => setTimeout(resolve, TIMING.UPDATE_RETRY_DELAY));
                return updateCalculation(changedType, options);
            }

            isUpdating = true;

            try {
                const values = {
                    height: state.get('height')?.value || 0,
                    weight: state.get('weight')?.value || 0,
                    bmi: state.get('bmi')?.value || 0
                };

                const isValid = (val) => val && !isNaN(val) && isFinite(val) && val > 0;

                const canCalculate = changedType === 'bmi'
                    ? isValid(values.bmi) && (isValid(values.height) || isValid(values.weight))
                    : isValid(values.height) && isValid(values.weight);

                if (canCalculate) {
                    const result = calculate(
                        values.height,
                        values.weight,
                        changedType !== 'bmi' ? null : values.bmi
                    );

                    ['height', 'weight', 'bmi'].forEach(type => {
                        const group = state.get(type);
                        if (group && isValid(result[type])) {
                            group.value = result[type];
                        }
                    });

                    updateInputs(result, options);
                    container.classList.remove('invalid-inputs');
                    return result;
                } else {
                    container.classList.add('invalid-inputs');
                    return null;
                }
            } finally {
                isUpdating = false;
            }
        });

        return updateQueue;
    };

    /**
     * Cleanup controller resources.
     * @private
     */
    const cleanup = () => {
        clearTimeout(transitionTimeout);
        state.clear();
    };

    return {state, updateCalculation, cleanup};
}

// ============================================================================
// GUI UPDATES
// ============================================================================

/**
 * Update container CSS classes based on results.
 * @private
 */
function updateContainerClasses(container, result) {
    const {category, treatmentEligible} = result;

    const categoryClasses = [
        'bmi-ondergewicht', 'bmi-normaal', 'bmi-overgewicht',
        'bmi-obesitas-1', 'bmi-obesitas-2', 'bmi-obesitas-3'
    ];

    // Remove all category classes
    container.classList.remove(...categoryClasses, 'bmi-ongeldig');

    // Add current category
    if (categoryClasses[category.index]) {
        container.classList.add(categoryClasses[category.index]);
    } else {
        container.classList.add('bmi-ongeldig');
    }

    // Treatment eligibility
    container.classList.remove('behandeling-nok-clinics', 'behandeling-nok-regulier');
    if (treatmentEligible?.nokClinics) container.classList.add('behandeling-nok-clinics');
    if (treatmentEligible?.regular) container.classList.add('behandeling-nok-regulier');

    // Boundaries
    container.classList.toggle('near-lower-boundary', category?.nearBoundary.lower);
    container.classList.toggle('near-upper-boundary', category?.nearBoundary.upper);
}

/**
 * Get nested object value using dot notation.
 * @private
 */
function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj);
}

/**
 * Format value for display.
 * @private
 */
function formatDisplayValue(value, property, suffix = '') {
    if (value === null || value === undefined) return '';

    if (typeof value === 'number') {
        if (property.endsWith('.excess') && value === 0) return '';

        if (property.includes('weight') || property.includes('bmi') ||
            property.includes('min') || property.includes('max')) {
            value = value.toFixed(1);
        } else {
            value = Number.isInteger(value) ? value.toString() : value.toFixed(1);
        }
    }

    const stringValue = String(value);
    return stringValue !== '' && suffix ? `${stringValue}${suffix}` : stringValue;
}

/**
 * Update conclusion display elements.
 * @private
 */
function updateConclusionElements(container, results) {
    const elements = container.querySelectorAll('[data-output-for]');

    elements.forEach(element => {
        const property = element.dataset.outputFor;
        const suffix = element.dataset.valueSuffix || '';
        const value = getNestedValue(results, property);

        if (value !== undefined) {
            const displayValue = formatDisplayValue(value, property, suffix);
            element.textContent = displayValue;
            handleSpecialCases(element, property, value, displayValue);
        }
    });
}

/**
 * Handle special display cases.
 * @private
 */
function handleSpecialCases(element, property, rawValue, displayValue) {
    if (property.endsWith('.excess')) {
        const hasExcess = displayValue !== '' && parseFloat(displayValue) > 0;
        element.parentElement?.classList.toggle('has-excess', hasExcess);
    }

    if (property.startsWith('treatmentEligible.')) {
        element.classList.toggle('eligible', rawValue === true);
        element.classList.toggle('not-eligible', rawValue === false);
    }

    if (property.startsWith('category.nearBoundary.')) {
        element.classList.toggle('near-boundary', rawValue === true);
    }
}

/**
 * Generate BMI gradient for slider.
 * @private
 */
function generateBMIGradient(container, cutoffs = CUTOFFS.adults, minBMI = 15, maxBMI = 45) {
    const range = maxBMI - minBMI;
    const gradientStops = [];

    gradientStops.push(`${BMI_COLORS[0]} 0%`);

    cutoffs.forEach((cutoff, index) => {
        const percentage = ((cutoff - minBMI) / range * 100).toFixed(2);
        gradientStops.push(`${BMI_COLORS[index]} ${percentage}%`);
        if (index + 1 < BMI_COLORS.length) {
            gradientStops.push(`${BMI_COLORS[index + 1]} ${percentage}%`);
        }
    });

    gradientStops.push(`${BMI_COLORS[BMI_COLORS.length - 1]} 100%`);

    container.style.setProperty('--bmi-gradient', `linear-gradient(to right, ${gradientStops.join(', ')})`);
}

/**
 * Apply input bounds.
 * @private
 */
function applyBounds(input, type) {
    if (BOUNDS[type]) {
        input.min = BOUNDS[type].min;
        input.max = BOUNDS[type].max;
        const output = input.parentElement.querySelector('output');
        if (output) {
            output.style.setProperty('--min', input.min);
            output.style.setProperty('--max', input.max);
        }
    }
}

/**
 * Register GUI input with controller.
 * @private
 */
function registerGUIInput(controller, type, element) {
    let group = controller.state.get(type);
    if (!group) {
        group = {type, value: parseFloat(element.value) || 0, inputs: new Set()};
        controller.state.set(type, group);
    }
    group.inputs.add(element);

    if (element.type === 'number') {
        element.addEventListener('focusin', () => {
            setElementData(element, 'originalValue', element.value);
            element.select();
        });

        element.addEventListener('blur', () => {
            if (!element.value) {
                element.value = getElementData(element, 'originalValue');
            }
        });
    }

    const handleChange = async (e) => {
        const newValue = parseFloat(e.originalEvent.target.value) || 0;
        if (group.value !== newValue) {
            group.value = newValue;
        }

        controller.updateCalculation(type, {final: e.debounceStateFinal})
            .then(result => {
                if (e.debounceStateFinal) {
                    element.dispatchEvent(new CustomEvent('bmi-input-completed', {
                        detail: {type, value: newValue, calculationResult: result}
                    }));
                }
            });
    };

    const events = element.type === 'range' ? 'input' : 'input, change, keydown, blur';
    const cleanup = debouncedEvent(element, events, handleChange, {delay: TIMING.DEBOUNCE_DELAY, after: true, during: true});

    setElementData(element, 'cleanup', cleanup);
}

// ============================================================================
// PUBLIC API
// ============================================================================

/**
 * Initialize GUI calculator.
 * @param {NodeList|Array} elements - Container elements
 */
function initGUI(elements) {
    elements.forEach(container => {
        const controller = createGUIController(container);

        // Store controller for cleanup
        setElementData(container, 'controller', controller);

        container.querySelectorAll('[data-output-for]').forEach(input => {
            const cleanup = getElementData(input, 'cleanup');
            if (cleanup) cleanup();

            const inputType = input.dataset.outputFor.toLowerCase();

            if (['height', 'weight', 'bmi'].includes(inputType)) {
                applyBounds(input, inputType);

                if (inputType === 'bmi') {
                    generateBMIGradient(container, CUTOFFS.adults, input.min ?? 10, input.max ?? 80);
                }

                if (input.dataset.default) {
                    input.value = input.dataset.default;
                }

                registerGUIInput(controller, inputType, input);
            }
        });

        container.classList.remove('loading');
        controller.updateCalculation('height', {final: true});
    });
}

/**
 * Cleanup calculator resources.
 * @param {HTMLElement} container - Calculator container
 */
function destroyGUI(container) {
    const controller = getElementData(container, 'controller');
    if (controller) {
        controller.cleanup();
    }

    container.querySelectorAll('[data-output-for]').forEach(input => {
        const cleanup = getElementData(input, 'cleanup');
        if (cleanup) cleanup();
    });
}

/**
 * Module API for inter-module coordination.
 * @param {string} action - Action name
 * @param {...*} args - Action arguments
 * @returns {*} Action result
 */
export function api(action, ...args) {
    switch (action) {
        case 'calculate':
            return calculate(...args[0]);
        default:
            logger.warn(NAME, `Unknown action: ${action}`);
            return null;
    }
}

/**
 * Module initialization (DOMule standard).
 * @param {NodeList|Array} elements - Elements requiring this module
 * @returns {string} Initialization status
 */
export function init(elements, context) {
    initGUI(elements);
    return `Initialized ${elements.length} calculator(s)`;
}

/**
 * Module cleanup (DOMule standard).
 */
export function destroy() {
    // Cleanup will happen automatically via WeakMap garbage collection
    // But explicitly clean up any remaining resources
    logger.info(NAME, 'Module destroyed');
}

// ============================================================================
// EXPORTS
// ============================================================================

export const BMICalculator = {
    CUTOFFS,
    TREATMENT_CRITERIA,
    calculate,
    calculateWeight: weightFromBMI,
    calculateHeight: heightFromBMI,
    healthyWeightRange,
    classify,
    checkTreatmentEligibility,
    isNearBoundary,
    getChildCutoffs
};

export {
    calculate,
    healthyWeightRange,
    classify,
    checkTreatmentEligibility,
    isNearBoundary,
    getChildCutoffs,
    calculateBMI,
    weightFromBMI,
    heightFromBMI,
    CUTOFFS,
    TREATMENT_CRITERIA
};