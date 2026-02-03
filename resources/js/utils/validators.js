/**
 * Form Validation Utilities
 * Client-side validation helpers
 */

/**
 * Validation rules
 */
export const rules = {
    required: (value) => {
        if (Array.isArray(value)) return value.length > 0;
        return value !== null && value !== undefined && value !== '';
    },
    
    email: (value) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    },
    
    phone: (value) => {
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        return phoneRegex.test(value) && value.replace(/\D/g, '').length >= 10;
    },
    
    minLength: (value, length) => {
        return value.length >= length;
    },
    
    maxLength: (value, length) => {
        return value.length <= length;
    },
    
    min: (value, min) => {
        return Number(value) >= min;
    },
    
    max: (value, max) => {
        return Number(value) <= max;
    },
    
    numeric: (value) => {
        return !isNaN(value) && !isNaN(parseFloat(value));
    },
    
    alpha: (value) => {
        return /^[a-zA-Z]+$/.test(value);
    },
    
    alphaNumeric: (value) => {
        return /^[a-zA-Z0-9]+$/.test(value);
    },
    
    url: (value) => {
        try {
            new URL(value);
            return true;
        } catch {
            return false;
        }
    },
    
    match: (value, matchValue) => {
        return value === matchValue;
    },
    
    pattern: (value, pattern) => {
        return new RegExp(pattern).test(value);
    }
};

/**
 * Error messages
 */
export const messages = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    phone: 'Please enter a valid phone number',
    minLength: (length) => `Minimum length is ${length} characters`,
    maxLength: (length) => `Maximum length is ${length} characters`,
    min: (min) => `Minimum value is ${min}`,
    max: (max) => `Maximum value is ${max}`,
    numeric: 'Please enter a valid number',
    alpha: 'Only letters are allowed',
    alphaNumeric: 'Only letters and numbers are allowed',
    url: 'Please enter a valid URL',
    match: 'Values do not match',
    pattern: 'Invalid format'
};

/**
 * Validate a single field
 * @param {string} value - Field value
 * @param {Array} validations - Array of validation rules
 * @returns {Object} { valid: boolean, error: string|null }
 */
export function validateField(value, validations = []) {
    for (const validation of validations) {
        const { rule, params = [], message } = validation;
        
        if (!rules[rule]) {
            console.warn(`Unknown validation rule: ${rule}`);
            continue;
        }
        
        const isValid = rules[rule](value, ...params);
        
        if (!isValid) {
            return {
                valid: false,
                error: message || (typeof messages[rule] === 'function' 
                    ? messages[rule](...params) 
                    : messages[rule])
            };
        }
    }
    
    return { valid: true, error: null };
}

/**
 * Validate entire form
 * @param {Object} formData - Form data object
 * @param {Object} validationRules - Validation rules for each field
 * @returns {Object} { valid: boolean, errors: Object }
 */
export function validateForm(formData, validationRules) {
    const errors = {};
    let isValid = true;
    
    for (const [field, rules] of Object.entries(validationRules)) {
        const value = formData[field];
        const result = validateField(value, rules);
        
        if (!result.valid) {
            errors[field] = result.error;
            isValid = false;
        }
    }
    
    return { valid: isValid, errors };
}

/**
 * Show validation error on field
 * @param {HTMLElement} field - Input field element
 * @param {string} error - Error message
 */
export function showFieldError(field, error) {
    // Remove existing error
    clearFieldError(field);
    
    // Add invalid class
    field.classList.add('is-invalid');
    
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = error;
    
    // Insert after field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

/**
 * Clear validation error from field
 * @param {HTMLElement} field - Input field element
 */
export function clearFieldError(field) {
    field.classList.remove('is-invalid');
    
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Show validation success on field
 * @param {HTMLElement} field - Input field element
 */
export function showFieldSuccess(field) {
    clearFieldError(field);
    field.classList.add('is-valid');
}

/**
 * Clear all validation states from field
 * @param {HTMLElement} field - Input field element
 */
export function clearFieldValidation(field) {
    field.classList.remove('is-valid', 'is-invalid');
    clearFieldError(field);
}

/**
 * Password strength checker
 * @param {string} password - Password to check
 * @returns {Object} { strength: string, score: number, feedback: Array }
 */
export function checkPasswordStrength(password) {
    let score = 0;
    const feedback = [];
    
    // Length check
    if (password.length >= 8) score++;
    else feedback.push('Use at least 8 characters');
    
    if (password.length >= 12) score++;
    
    // Complexity checks
    if (/[a-z]/.test(password)) score++;
    else feedback.push('Add lowercase letters');
    
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('Add uppercase letters');
    
    if (/\d/.test(password)) score++;
    else feedback.push('Add numbers');
    
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    else feedback.push('Add special characters');
    
    // Determine strength
    let strength = 'weak';
    if (score >= 5) strength = 'strong';
    else if (score >= 3) strength = 'medium';
    
    return { strength, score, feedback };
}

/**
 * Real-time validation setup for form
 * @param {HTMLFormElement} form - Form element
 * @param {Object} validationRules - Validation rules
 */
export function setupRealtimeValidation(form, validationRules) {
    const fields = form.querySelectorAll('input, textarea, select');
    
    fields.forEach(field => {
        const fieldName = field.name;
        const rules = validationRules[fieldName];
        
        if (!rules) return;
        
        // Validate on blur
        field.addEventListener('blur', () => {
            const result = validateField(field.value, rules);
            
            if (!result.valid) {
                showFieldError(field, result.error);
            } else if (field.value) {
                showFieldSuccess(field);
            }
        });
        
        // Clear error on input
        field.addEventListener('input', () => {
            if (field.classList.contains('is-invalid')) {
                clearFieldError(field);
            }
        });
    });
}
