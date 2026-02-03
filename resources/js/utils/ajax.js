/**
 * AJAX Wrapper Utilities
 * Simplified AJAX calls with jQuery
 */

/**
 * Get CSRF token from meta tag
 * @returns {string}
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Default AJAX settings
 */
const defaultSettings = {
    headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
    },
    dataType: 'json'
};

/**
 * Handle AJAX errors
 * @param {Object} xhr - XMLHttpRequest object
 * @param {string} status - Error status
 * @param {string} error - Error message
 */
function handleError(xhr, status, error) {
    console.error('AJAX Error:', { xhr, status, error });
    
    let message = 'An error occurred. Please try again.';
    
    if (xhr.status === 401) {
        message = 'Unauthorized. Please log in again.';
    } else if (xhr.status === 403) {
        message = 'Access denied.';
    } else if (xhr.status === 404) {
        message = 'Resource not found.';
    } else if (xhr.status === 422) {
        message = 'Validation error.';
    } else if (xhr.status >= 500) {
        message = 'Server error. Please try again later.';
    } else if (xhr.responseJSON?.message) {
        message = xhr.responseJSON.message;
    }
    
    return { success: false, message, errors: xhr.responseJSON?.errors || {} };
}

/**
 * GET request
 * @param {string} url - Request URL
 * @param {Object} data - Query parameters
 * @param {Object} options - Additional options
 * @returns {Promise}
 */
export function get(url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'GET',
            data,
            ...defaultSettings,
            ...options,
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * POST request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Additional options
 * @returns {Promise}
 */
export function post(url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'POST',
            data,
            ...defaultSettings,
            ...options,
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * PUT request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Additional options
 * @returns {Promise}
 */
export function put(url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'PUT',
            data,
            ...defaultSettings,
            ...options,
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * PATCH request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Additional options
 * @returns {Promise}
 */
export function patch(url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'PATCH',
            data,
            ...defaultSettings,
            ...options,
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * DELETE request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Additional options
 * @returns {Promise}
 */
export function del(url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'DELETE',
            data,
            ...defaultSettings,
            ...options,
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * Upload file with progress tracking
 * @param {string} url - Upload URL
 * @param {FormData} formData - Form data with file
 * @param {Function} onProgress - Progress callback
 * @returns {Promise}
 */
export function upload(url, formData, onProgress = null) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                if (onProgress) {
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            onProgress(percentComplete);
                        }
                    }, false);
                }
                return xhr;
            },
            success: (response) => resolve(response),
            error: (xhr, status, error) => reject(handleError(xhr, status, error))
        });
    });
}

/**
 * Batch requests (execute multiple requests in parallel)
 * @param {Array} requests - Array of request promises
 * @returns {Promise}
 */
export function batch(requests) {
    return Promise.all(requests);
}

/**
 * Sequential requests (execute requests one after another)
 * @param {Array} requests - Array of request functions
 * @returns {Promise}
 */
export async function sequential(requests) {
    const results = [];
    for (const request of requests) {
        const result = await request();
        results.push(result);
    }
    return results;
}

/**
 * Retry failed request
 * @param {Function} requestFn - Request function to retry
 * @param {number} maxRetries - Maximum retry attempts
 * @param {number} delay - Delay between retries (ms)
 * @returns {Promise}
 */
export async function retry(requestFn, maxRetries = 3, delay = 1000) {
    let lastError;
    
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await requestFn();
        } catch (error) {
            lastError = error;
            if (i < maxRetries - 1) {
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    }
    
    throw lastError;
}

/**
 * Cancel token for aborting requests
 */
export class CancelToken {
    constructor() {
        this.cancelled = false;
        this.reason = null;
    }
    
    cancel(reason = 'Request cancelled') {
        this.cancelled = true;
        this.reason = reason;
    }
    
    throwIfCancelled() {
        if (this.cancelled) {
            throw new Error(this.reason);
        }
    }
}

/**
 * Create cancellable request
 * @param {Function} requestFn - Request function
 * @param {CancelToken} cancelToken - Cancel token
 * @returns {Promise}
 */
export async function cancellable(requestFn, cancelToken) {
    cancelToken.throwIfCancelled();
    
    try {
        const result = await requestFn();
        cancelToken.throwIfCancelled();
        return result;
    } catch (error) {
        cancelToken.throwIfCancelled();
        throw error;
    }
}
