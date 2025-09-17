/**
 * BMI Calculator Core Module
 *
 * Compact calculation engine for BMI operations supporting adults and children (2-18 years).
 * Implements WHO/IOTF standards for BMI classification and treatment eligibility assessment.
 *
 * @version 2.0.0
 * @author Nederlandse Obesitas Kliniek B.V. / hnldesign
 * @since 2024
 *
 * @example
 * // Basic BMI calculation
 * const result = BMICalculator.calculate({
 *   height: 175,
 *   weight: 70,
 *   changed: 'weight'
 * });
 * console.log(result.bmi); // 22.86
 * console.log(result.category.label); // "Normal"
 *
 * @example
 * // Calculate target weight for healthy BMI
 * const range = BMICalculator.healthyWeightRange(175, [18.5, 25]);
 * console.log(`Healthy weight: ${range.min.toFixed(1)} - ${range.max.toFixed(1)} kg`);
 *
 * @example
 * // Child BMI classification
 * const childCutoffs = BMICalculator.getChildCutoffs(8, 'girls');
 * const childResult = BMICalculator.calculate({
 *   height: 125,
 *   weight: 30,
 *   changed: 'weight'
 * });
 */

import {debounceThis, debouncedEvent} from "./modules/hnl.debounce.mjs";

/**
 * BMI cutoff values for classification.
 * Adults use WHO standard thresholds, children use IOTF age-specific percentiles.
 *
 * @readonly
 * @type {Object}
 * @property {number[]} adults - Adult BMI thresholds [underweight, normal, overweight, obese]
 * @property {Object} children - Age and gender specific BMI percentiles
 * @property {Object} children.girls - BMI cutoffs for girls aged 2-18
 * @property {Object} children.boys - BMI cutoffs for boys aged 2-18
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

const BOUNDS = {
    height: {
        min: 80,
        max: 220
    },
    weight: {
        min: 10,
        max: 300
    },
    bmi: {
        min: 15,
        max: 60
    },
}

/**
 * Treatment eligibility criteria based on BMI thresholds.
 *
 * @readonly
 * @type {Object}
 * @property {number[]} nokClinics - NOK clinic eligibility (BMI ≥ 27)
 * @property {number[]} regular - Regular treatment eligibility (BMI ≥ 35)
 */
const TREATMENT_CRITERIA = {
    nokClinics: [27],
    regular: [35]
};

/**
 * Validate input parameters against clinical ranges and emit console warnings.
 * @param {Object} params - Input parameters to validate
 * @param {number} [params.height] - Height in cm (max: 250)
 * @param {number} [params.weight] - Weight in kg (max: 500)
 * @param {number} [params.bmi] - BMI value (range: 10-80)
 */
function validateInputs(params) {
    const warnings = [];

    if (params.height !== undefined) {
        if (params.height > BOUNDS.height.max) {
            warnings.push(`Height ${params.height}cm exceeds typical range (max: ${BOUNDS.height.max}cm)`);
        }
    }

    if (params.weight !== undefined) {
        if (params.weight > BOUNDS.weight.max) {
            warnings.push(`Weight ${params.weight}kg exceeds typical range (max: ${BOUNDS.weight.max}kg)`);
        }
    }

    if (params.bmi !== undefined) {
        if (params.bmi < BOUNDS.bmi.min) {
            warnings.push(`BMI ${params.bmi.toFixed(1)} below clinical range (min: ${BOUNDS.bmi.min})`);
        }
        if (params.bmi > BOUNDS.bmi.max) {
            warnings.push(`BMI ${params.bmi.toFixed(1)} exceeds clinical range (max: ${BOUNDS.bmi.max})`);
        }
    }

    warnings.forEach(msg => console.warn('[BMI Calculator]', msg));
}

/**
 * Calculate complete BMI profile from height and weight.
 * Computes BMI value and provides comprehensive health assessment including
 * category classification, treatment eligibility, and healthy weight ranges.
 *
 * @param {number} height - Height in centimeters
 * @param {number} weight - Weight in kilograms
 * @param {number} [bmi] - BMI
 * @returns {Object} Complete BMI analysis
 * @returns {number} result.height - Height in cm
 * @returns {number} result.weight - Weight in kg
 * @returns {number} result.bmi - Calculated BMI value
 * @returns {Object} result.category - BMI classification with risk assessment
 * @returns {Object} result.treatmentEligible - Treatment program eligibility flags
 * @returns {Object} result.healthyWeightRange - Healthy weight boundaries for this height
 *
 * @example
 * // Standard BMI calculation with full health profile
 * const result = calculate(175, 70);
 * console.log(`BMI: ${result.bmi.toFixed(1)}`); // "BMI: 22.9"
 * console.log(`Category: ${result.category.label}`); // "Category: Normal"
 * console.log(`Risk: ${result.category.risk}`); // "Risk: normal"
 *
 * @example
 * // Overweight assessment with treatment eligibility
 * const overweight = calculate(165, 85);
 * console.log(overweight.treatmentEligible.nokClinics); // true (BMI ≥ 27)
 * console.log(overweight.treatmentEligible.regular); // false (BMI < 35)
 * console.log(`Excess weight: ${overweight.healthyWeightRange.excess.toFixed(1)}kg`);
 *
 * @example
 * // Underweight analysis
 * const underweight = calculate(180, 55);
 * console.log(underweight.category.label); // "Underweight"
 * console.log(`Min healthy weight: ${underweight.healthyWeightRange.min.toFixed(1)}kg`);
 */
function calculate(height, weight, bmi = null) {
    validateInputs({height: height, weight: weight});
    bmi = bmi ?? weight / Math.pow(height / 100, 2);

    const round = (int) => {
        return Math.round(int * 10) / 10;
    }
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
 * Calculate healthy weight range for a given height using BMI cutoffs.
 * Optionally compares against current weight to determine excess.
 *
 * @param {number} height - Height in centimeters
 * @param {number[]} [cutoffs] - BMI range for healthy weight calculation
 * @param {number} [currentWeight] - Current weight for comparison (optional)
 * @returns {Object} Weight range calculation
 * @returns {number} result.min - Minimum healthy weight in kg
 * @returns {number} result.max - Maximum healthy weight in kg
 * @returns {number} [result.current] - Current weight (if provided)
 * @returns {number} [result.excess] - Weight excess beyond healthy range (if current weight provided)
 *
 * @example
 * // Basic healthy weight range
 * const range = healthyWeightRange(175);
 * console.log(`Healthy range: ${range.min.toFixed(1)} - ${range.max.toFixed(1)} kg`);
 * // Output: "Healthy range: 56.7 - 76.6 kg"
 *
 * @example
 * // Compare current weight to healthy range
 * const comparison = healthyWeightRange(165, [18.5, 25], 85);
 * console.log(`Current: ${comparison.current}kg, Excess: ${comparison.excess.toFixed(1)}kg`);
 * // Output: "Current: 85kg, Excess: 16.6kg"
 *
 * @example
 * // Custom BMI range for athletic build
 * const athleticRange = healthyWeightRange(180, [20, 27]);
 * console.log(`Athletic range: ${athleticRange.min} - ${athleticRange.max} kg`);
 */
function healthyWeightRange(height, cutoffs, currentWeight) {
    cutoffs = cutoffs || [18.5, 25];
    currentWeight = currentWeight || null;

    const heightM2 = Math.pow(height / 100, 2);
    const min = cutoffs[0] * heightM2;
    const max = cutoffs[1] * heightM2;

    const result = {min: min, max: max};

    if (currentWeight !== null) {
        result.current = currentWeight;
        result.excess = Math.max(0, currentWeight - max, min - currentWeight);
    }

    return result;
}

/**
 * Classify BMI value into standard WHO categories with risk assessment.
 * Includes boundary detection for BMI values within 1 point of category thresholds.
 *
 * @param {number} bmi - BMI value to classify
 * @returns {Object} BMI classification result
 * @returns {number} result.index - Category index (0=underweight, 1=normal, 2=overweight, etc.)
 * @returns {string} result.label - Human-readable category label
 * @returns {string} result.risk - Risk level: 'low', 'normal', 'increased', 'high', 'very-high'
 * @returns {Object} result.nearBoundary - Boundary proximity flags
 * @returns {boolean} result.nearBoundary.lower - Within 1 point above lower threshold
 * @returns {boolean} result.nearBoundary.upper - Within 1 point below upper threshold
 *
 * @example
 * // Normal BMI classification
 * const normal = classify(22.5);
 * console.log(normal.label); // "Normal"
 * console.log(normal.risk);  // "normal"
 *
 * @example
 * // Boundary detection for overweight
 * const boundary = classify(24.2);
 * console.log(boundary.label); // "Normal"
 * console.log(boundary.nearBoundary.upper); // true (close to overweight threshold)
 *
 * @example
 * // Obese classification
 * const obese = classify(32);
 * console.log(`${obese.label} (Risk: ${obese.risk})`); // "Obese Class I (Risk: high)"
 */
function classify(bmi) {
    const cutoffs = CUTOFFS.adults;
    const labels = ['Ondergewicht', 'Normal', 'Overgewicht', 'Obesitas Klasse I', 'Obesitas Klasse II', 'Obesitas Klasse III'];
    const risks = ['matig verhoogd', 'normaal', 'licht verhoogd', 'matig verhoogd', 'sterk verhoogd', 'extreem verhoogd'];
    const classifications = ['ondergewicht', 'een normaal gewicht', 'overgewicht', 'obesitas (klasse I)', 'ernstige obesitas (klasse II)', 'zeer ernstige obesitas (klasse III)'];

    let index = -1;
    for (let i = 0; i < cutoffs.length; i++) {
        if (bmi <= cutoffs[i]) {
            index = i;
            break;
        }
    }
    if (index === -1) index = cutoffs.length;

    return {
        index: index,
        label: labels[index],
        risk: risks[index],
        classification: classifications[index],
        nearBoundary: isNearBoundary(bmi, cutoffs, index)
    };
}

/**
 * Evaluate treatment eligibility based on BMI thresholds.
 * Determines qualification for different treatment programs.
 *
 * @param {number} bmi - BMI value to evaluate
 * @returns {Object} Treatment eligibility assessment
 * @returns {boolean} result.nokClinics - Eligible for NOK clinic treatment (BMI ≥ 27)
 * @returns {boolean} result.regular - Eligible for regular treatment programs (BMI ≥ 35)
 *
 * @example
 * // Check eligibility for moderate overweight
 * const moderate = checkTreatmentEligibility(28);
 * console.log(moderate.nokClinics); // true
 * console.log(moderate.regular);    // false
 *
 * @example
 * // Check eligibility for severe obesity
 * const severe = checkTreatmentEligibility(38);
 * console.log(severe.nokClinics); // true
 * console.log(severe.regular);    // true
 *
 * @example
 * // Normal weight eligibility
 * const normal = checkTreatmentEligibility(23);
 * console.log(normal.nokClinics); // false
 * console.log(normal.regular);    // false
 */
function checkTreatmentEligibility(bmi) {
    return {
        nokClinics: bmi >= TREATMENT_CRITERIA.nokClinics[0],
        regular: bmi >= TREATMENT_CRITERIA.regular[0]
    };
}

/**
 * Determine if BMI is within 1 point of category boundary thresholds.
 * Used for UI styling and warnings about category transitions.
 *
 * @param {number} bmi - BMI value to check
 * @param {number[]} cutoffs - BMI cutoff values array
 * @param {number} currentIndex - Current BMI category index
 * @returns {Object} Boundary proximity flags
 * @returns {boolean} result.lower - Within 1 point above the lower category threshold
 * @returns {boolean} result.upper - Within 1 point below the upper category threshold
 *
 * @example
 * // Check boundary proximity for normal weight near overweight
 * const cutoffs = [18.5, 25, 30, 40];
 * const boundary = isNearBoundary(24.2, cutoffs, 1);
 * console.log(boundary.upper); // true (24.2 is within 1 point of 25.0)
 * console.log(boundary.lower); // false
 *
 * @example
 * // Overweight near obesity boundary
 * const obeseBoundary = isNearBoundary(29.1, cutoffs, 2);
 * console.log(obeseBoundary.upper); // true (within 1 point of 30.0)
 */
function isNearBoundary(bmi, cutoffs, currentIndex) {
    const lower = currentIndex > 0 &&
        (bmi - cutoffs[currentIndex - 1] <= 1) &&
        (bmi >= cutoffs[currentIndex - 1]);

    const upper = currentIndex < cutoffs.length &&
        (cutoffs[currentIndex] - bmi <= 1) &&
        (bmi <= cutoffs[currentIndex]);

    return {lower: lower, upper: upper};
}

/**
 * Get age and gender appropriate BMI cutoffs for children (2-18 years).
 * Uses IOTF (International Obesity Task Force) percentile data.
 * Automatically falls back to adult cutoffs for ages outside range.
 *
 * @param {number} age - Age in years (will be clamped to 2-18 range)
 * @param {('boys'|'girls')} gender - Child's gender
 * @returns {number[]} Age-appropriate BMI cutoff array [underweight, normal, overweight, obese]
 *
 * @example
 * // Get cutoffs for 8-year-old girl
 * const girlCutoffs = getChildCutoffs(8, 'girls');
 * console.log(girlCutoffs); // [14.16, 18.4, 21.6, 29.8]
 *
 * @example
 * // Get cutoffs for 12-year-old boy
 * const boyCutoffs = getChildCutoffs(12, 'boys');
 * console.log(boyCutoffs); // [15.47, 21.2, 26, 34.8]
 *
 * @example
 * // Automatic fallback to adult values for age > 18
 * const adultCutoffs = getChildCutoffs(20, 'boys');
 * console.log(adultCutoffs); // [18.5, 25, 30, 40] (adult cutoffs)
 *
 * @example
 * // Use with child BMI calculation
 * const childAge = 10;
 * const childGender = 'girls';
 * const cutoffs = getChildCutoffs(childAge, childGender);
 * const childBMI = calculateBMI(140, 35); // height: 140cm, weight: 35kg
 * const classification = classify(childBMI);
 * // Note: classify() uses adult cutoffs by default, use cutoffs parameter for children
 */
function getChildCutoffs(age, gender) {
    const ageKey = Math.floor(Math.max(2, Math.min(18, age)));
    const childData = CUTOFFS.children[gender];
    return (childData && childData[ageKey]) || CUTOFFS.adults;
}

/**
 * Convenience function for direct BMI calculation from height and weight.
 *
 * @param {number} height - Height in centimeters
 * @param {number} weight - Weight in kilograms
 * @returns {number} Calculated BMI value
 *
 * @example
 * const bmi = calculateBMI(175, 70);
 * console.log(bmi.toFixed(1)); // "22.9"
 */
function calculateBMI(height, weight) {
    validateInputs({height: height, weight: weight});
    return weight / Math.pow(height / 100, 2);
}

/**
 * Convenience function to calculate required weight for target BMI.
 *
 * @param {number} bmi - Target BMI value
 * @param {number} height - Height in centimeters
 * @returns {number} Required weight in kilograms
 *
 * @example
 * const targetWeight = weightFromBMI(22, 180);
 * console.log(targetWeight.toFixed(1)); // "71.3"
 */
function weightFromBMI(bmi, height) {
    validateInputs({bmi: bmi, height: height});
    return bmi * Math.pow(height / 100, 2);
}

/**
 * Convenience function to calculate required height for target BMI.
 *
 * @param {number} bmi - Target BMI value
 * @param {number} weight - Weight in kilograms
 * @returns {number} Required height in centimeters
 *
 * @example
 * const targetHeight = heightFromBMI(25, 80);
 * console.log(targetHeight.toFixed(1)); // "178.9"
 */
function heightFromBMI(bmi, weight) {
    validateInputs({bmi: bmi, weight: weight});
    return Math.sqrt(weight / bmi) * 100;
}


const BMIColors = [
    'var(--color-1, #FFC425)', // Underweight (< 18.5)
    'var(--color-2, #4CAF50)', // Normal (18.5 - 25)
    'var(--color-3, #CDDC39)', // Overweight (25 - 30)
    'var(--color-4, #FF9800)', // Obese Class I (30 - 40)
    'var(--color-5, #ff6f00)', // Obese Class II (35 - 40)
    'var(--color-6, #D32F2F)'  // Obese Class III (> 40)
];

function maptoBMIRange(arr, value) {
    const index = arr.findIndex(element => element >= value);
    return index === -1 ? arr.length : index;
}

/**
 * Generate CSS linear gradient based on BMI cutoffs and color palette
 * @param {number[]} cutoffs - BMI cutoff values
 * @returns {string} CSS linear gradient string
 */
function generateBMIGradient(container, cutoffs = CUTOFFS.adults, minBMI = 15, maxBMI = 45) {

    //maxBMI = parseInt(maxBMI) - 2; //slight compensation for thumb alignment
    const range = maxBMI - minBMI;
    const gradientStops = [];

    // Start with first color from beginning
    gradientStops.push(`${BMIColors[0]} 0%`);

    // Create sharp transitions at each cutoff
    cutoffs.forEach((cutoff, index) => {
        const percentage = ((cutoff - minBMI) / range * 100).toFixed(2);

        // End current color just before cutoff
        gradientStops.push(`${BMIColors[index]} ${percentage}%`);

        // Start next color immediately at cutoff
        if (index + 1 < BMIColors.length) {
            gradientStops.push(`${BMIColors[index + 1]} ${percentage}%`);
        }
    });

    // End with final color
    gradientStops.push(`${BMIColors[BMIColors.length - 1]} 100%`);

    container.style.setProperty('--bmi-gradient', `linear-gradient(to right, ${gradientStops.join(', ')})`);
    return true;
}

/**
 * Create a GUI controller for managing BMI calculator state and updates
 * @param {HTMLElement} container - Container element for the calculator
 * @returns {Object} Controller with state management methods
 */
function createGUIController(container) {
    const state = new Map();
    const conclusionContainer = container.querySelector('.calculator-conclusion');
    let isUpdating = false;
    let lastResults = {}; // Cache for comparison
    let classRemovalTimeout = null; // Add timeout tracker

    let updateQueue = Promise.resolve();

    const updateInputs = (result, finalUpdate) => {
        ['height', 'weight', 'bmi'].forEach(type => {
            const group = state.get(type);
            const newValue = result[type];

            // Skip update if value hasn't changed
            if (lastResults[type] === newValue) return;

            if (group && group.inputs) {
                group.inputs.forEach(input => {
                    if (type === 'bmi') {
                        container.style.setProperty('--bmi-classification-color', BMIColors[maptoBMIRange(CUTOFFS.adults, newValue)])
                    }
                    if (parseFloat(input.value) !== newValue) {
                        input.value = newValue;
                    }
                });
            }
        });

        if (finalUpdate) {
            // Update container classes based on BMI category and treatment eligibility
            updateContainerClasses(container, result);

            // Clear any existing timeout to extend the delay
            if (classRemovalTimeout) {
                clearTimeout(classRemovalTimeout);
            }

            // Set new timeout for class removal
            classRemovalTimeout = setTimeout(() => {
                //conclusionContainer.style.height = '';
                container.classList.remove('calculating');
                classRemovalTimeout = null; // Clean up reference
            }, 250);

            // Update health range display elements
            updateConclusionEntries(container, result);
        } else {
            container.classList.add('calculating');
            //conclusionContainer.style.height = `${Math.ceil(conclusionContainer.offsetHeight)}px`;
            // Cancel pending class removal if we're calculating again
            if (classRemovalTimeout) {
                clearTimeout(classRemovalTimeout);
                classRemovalTimeout = null;
            }
        }

        lastResults = {...result}; // Cache for next comparison
    };

    const updateCalculation = (changedType, finalUpdate = false) => {
        // Queue this update to prevent race conditions
        updateQueue = updateQueue.then(async () => {
            if (isUpdating) {
                // Wait briefly and retry instead of dropping
                await new Promise(resolve => setTimeout(resolve, 10));
                return updateCalculation(changedType, finalUpdate);
            }

            isUpdating = true;

            try {
                const values = {
                    height: state.get('height')?.value || 0,
                    weight: state.get('weight')?.value || 0,
                    bmi: state.get('bmi')?.value || 0
                };

                const validValue = (val) => val && !isNaN(val) && isFinite(val) && val > 0;

                const heightValid = validValue(values.height);
                const weightValid = validValue(values.weight);
                const bmiValid = validValue(values.bmi);

                const canCalculate = changedType === 'bmi'
                    ? bmiValid && (heightValid || weightValid)
                    : heightValid && weightValid;

                if (canCalculate) {
                    const result = calculate(
                        values.height,
                        values.weight,
                        changedType !== 'bmi' ? null : values.bmi
                    );

                    // Update state
                    ['height', 'weight', 'bmi'].forEach(type => {
                        const group = state.get(type);
                        if (group && validValue(result[type])) {
                            group.value = result[type];
                        }
                    });

                    // Update GUI, with optional flag for final update
                    updateInputs(result, finalUpdate);

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

    return { state, updateCalculation, isUpdating: () => isUpdating };
}

/**
 * Update container CSS classes based on calculation results
 * @param {HTMLElement} container - Container element
 * @param {Object} result - Calculation result object
 */
function updateContainerClasses(container, result) {
    const { category, treatmentEligible } = result;

    // Remove existing category classes
    container.classList.remove(
        'bmi-ondergewicht', 'bmi-normaal', 'bmi-overgewicht',
        'bmi-obesitas-1', 'bmi-obesitas-2', 'bmi-obesitas-3', 'bmi-ongeldig'
    );

    // Remove existing treatment classes
    container.classList.remove('behandeling-nok-clinics', 'behandeling-nok-regulier');

    // Add current category class
    const categoryClasses = [
        'bmi-ondergewicht',  // index 0
        'bmi-normaal',       // index 1
        'bmi-overgewicht',   // index 2
        'bmi-obesitas-1',      // index 3
        'bmi-obesitas-2',      // index 4
        'bmi-obesitas-3'       // index 5
    ];

    if (category && categoryClasses[category.index]) {
        container.classList.add(categoryClasses[category.index]);
    } else {
        container.classList.add('bmi-ongeldig');
    }

    // Add treatment eligibility classes
    if (treatmentEligible?.nokClinics) {
        container.classList.add('behandeling-nok-clinics');
    }
    if (treatmentEligible?.regular) {
        container.classList.add('behandeling-nok-regulier');
    }
    //container.classList.toggle(`near-lower-boundary-for-${categoryClasses[category.index]}`, category?.nearBoundary.lower);
    //container.classList.toggle(`near-upper-boundary-for-${categoryClasses[category.index]}`, category?.nearBoundary.upper);
    container.classList.toggle(`near-lower-boundary`, category?.nearBoundary.lower);
    container.classList.toggle(`near-upper-boundary`, category?.nearBoundary.upper);
}

/**
 * Get nested object value using dot notation path
 * @param {Object} obj - Source object
 * @param {string} path - Dot notation path (e.g., "category.label")
 * @returns {*} Value at path or undefined if not found
 */
function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => {
        return current?.[key];
    }, obj);
}

/**
 * Format value for display based on type and context
 * @param {*} value - Raw value to format
 * @param {string} property - Property name for context-specific formatting
 * @param {string} suffix - Optional suffix to append
 * @returns {string} Formatted display value
 */
function formatDisplayValue(value, property, suffix = '') {
    if (value === null || value === undefined) {
        return '';
    }

    // Handle numbers with appropriate precision
    if (typeof value === 'number') {
        // Special case for excess weight - hide zero values
        if (property.endsWith('.excess') && value === 0) {
            return '';
        }

        // Format decimals to 1 place for weight/BMI values
        if (property.includes('weight') || property.includes('bmi') || property.includes('min') || property.includes('max')) {
            value = value.toFixed(1);
        } else {
            // Round other numbers appropriately
            value = Number.isInteger(value) ? value.toString() : value.toFixed(1);
        }
    }

    // Convert to string and apply suffix
    const stringValue = String(value);
    return stringValue !== '' && suffix ? `${stringValue}${suffix}` : stringValue;
}

/**
 * Update all conclusion/display elements based on calculation results
 * Handles nested object properties using dot notation in data-input-for attributes
 * @param {HTMLElement} container - Container element
 * @param {Object} results - Complete calculation results object
 */
function updateConclusionEntries(container, results) {
    // Find all elements with data-input-for attributes
    const conclusionElements = container.querySelectorAll('[data-input-for]');

    conclusionElements.forEach(element => {
        const dataFor = element.dataset.inputFor;
        const suffix = element.dataset.valueSuffix || '';

        // Get nested value using dot notation
        const value = getNestedValue(results, dataFor);

        if (value !== undefined) {
            // Format the value for display
            const displayValue = formatDisplayValue(value, dataFor, suffix);

            // Update element content
            element.textContent = displayValue;

            // Handle special cases for conditional visibility/styling
            handleSpecialCases(element, dataFor, value, displayValue);
        }
    });
}

/**
 * Handle special styling and visibility cases for specific data types
 * @param {HTMLElement} element - Target element
 * @param {string} property - Property path
 * @param {*} rawValue - Unformatted value
 * @param {string} displayValue - Formatted display value
 */
function handleSpecialCases(element, property, rawValue, displayValue) {
    // Handle excess weight visibility
    if (property.endsWith('.excess')) {
        const hasExcess = displayValue !== '' && parseFloat(displayValue) > 0;
        element.parentElement?.classList.toggle('has-excess', hasExcess);
    }

    // Handle BMI category-based styling
    if (property === 'category.index' && element.classList.contains('category-indicator')) {
        // Remove existing category classes
        element.classList.remove('underweight', 'normal', 'overweight', 'obese-1', 'obese-2');

        // Add current category class
        const categoryClassMap = ['underweight', 'normal', 'overweight', 'obese-1', 'obese-2'];
        if (categoryClassMap[rawValue]) {
            element.classList.add(categoryClassMap[rawValue]);
        }
    }

    // Handle risk level styling
    if (property === 'category.risk' && element.classList.contains('risk-indicator')) {
        // Remove existing risk classes
        element.classList.remove('risk-low', 'risk-normal', 'risk-increased', 'risk-high', 'risk-very-high');

        // Add current risk class
        if (rawValue) {
            element.classList.add(`risk-${rawValue}`);
        }
    }

    // Handle treatment eligibility indicators
    if (property.startsWith('treatmentEligible.')) {
        element.classList.toggle('eligible', rawValue === true);
        element.classList.toggle('not-eligible', rawValue === false);
    }

    // Handle boundary proximity indicators
    if (property.startsWith('category.nearBoundary.')) {
        element.classList.toggle('near-boundary', rawValue === true);
    }
}

/**
 * Apply bounds constraints to input element based on type
 * @param {HTMLInputElement} input - Input element to constrain
 * @param {string} type - Input type (height, weight, bmi)
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
 * Create input group and register event handlers with precise completion detection
 * @param {Object} controller - GUI controller instance
 * @param {string} type - Input type (height, weight, bmi)
 * @param {HTMLInputElement} element - Input element
 */
function registerGUIInput(controller, type, element) {
    let group = controller.state.get(type);
    if (!group) {
        group = {type, value: parseFloat(element.value) || 0, inputs: new Set()};
        controller.state.set(type, group);
    }
    group.inputs.add(element);

    const handleChange = async (e) => {
        const newValue = parseFloat(e.originalEvent.target.value) || 0;
        if (group.value !== newValue) {
            group.value = newValue;
        }

        // Chain the calculation and handle completion
        // Only update GUI on final debounce state
        controller.updateCalculation(type, e.debounceStateFinal).then(result => {
            if (e.debounceStateFinal) {
                element.dispatchEvent(new CustomEvent('bmi-input-completed', {
                    detail: { type, value: newValue, calculationResult: result }
                }));
            }
        });
    };

    const events = element.type === 'range'
        ? 'input'
        : 'input, change, keydown, blur';

    // Store cleanup function to prevent memory leaks
    const cleanup = debouncedEvent(element, events, handleChange, 33, true, true);
    element._bmiCleanup = cleanup; // Store for later cleanup
}

/**
 * Initialize GUI calculator for given elements
 * @param {NodeList|Array} elements - Container elements with calculator inputs
 */
function initGUI(elements) {
    elements.forEach(container => {
        const controller = createGUIController(container);

        // Process all calculator inputs
        container.querySelectorAll('[data-input-for]').forEach(uxContainer => {
            // Clean up existing listeners
            if (uxContainer._bmiCleanup) uxContainer._bmiCleanup();
            const inputType = uxContainer.dataset.inputFor.toLowerCase();

            if (['height', 'weight', 'bmi'].includes(inputType)) {
                // Apply bounds and default values
                applyBounds(uxContainer, inputType);

                if (inputType === 'bmi') {
                    // Generate and apply BMI gradient
                    generateBMIGradient(container, CUTOFFS.adults, uxContainer.min ?? 10, uxContainer.max ?? 80);
                }

                if (uxContainer.dataset.default) {
                    uxContainer.value = uxContainer.dataset.default;
                }

                // Register the input
                registerGUIInput(controller, inputType, uxContainer);
            }
        });

        // Perform initial calculation with GUI update
        controller.updateCalculation('height', true);
    });
}

// Public API object for better compatibility
const BMICalculator = {
    CUTOFFS: CUTOFFS,
    TREATMENT_CRITERIA: TREATMENT_CRITERIA,
    calculate,
    calculateWeight: weightFromBMI,
    calculateHeight: heightFromBMI,
    healthyWeightRange: healthyWeightRange,
    classify: classify,
    checkTreatmentEligibility: checkTreatmentEligibility,
    isNearBoundary: isNearBoundary,
    getChildCutoffs: getChildCutoffs,
};

// Export both individual functions and the main object
export {
    BMICalculator,
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
    TREATMENT_CRITERIA,
    initGUI as init
};