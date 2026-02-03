/**
 * Toast Notification System
 * Custom toast notifications without external libraries
 */

import { generateId } from '../utils/helpers.js';

/**
 * Toast container
 */
let toastContainer = null;

/**
 * Initialize toast container
 */
function initToastContainer() {
    if (toastContainer) return;
    
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    document.body.appendChild(toastContainer);
}

/**
 * Create toast notification
 * @param {Object} options - Toast options
 */
export function showToast(options = {}) {
    initToastContainer();
    
    const {
        message = '',
        type = 'info',
        duration = 5000,
        dismissible = true,
        icon = null,
        action = null
    } = options;
    
    const toastId = generateId();
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    const colors = {
        success: 'text-bg-success',
        error: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info'
    };
    
    const toastIcon = icon || icons[type] || icons.info;
    const toastColor = colors[type] || colors.info;
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${toastColor} animate-slide-in-right" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${toastColor} border-0">
                <i class="${toastIcon} me-2"></i>
                <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                ${dismissible ? '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>' : ''}
            </div>
            <div class="toast-body">
                ${message}
                ${action ? `<div class="mt-2 pt-2 border-top"><button class="btn btn-sm btn-light toast-action">${action.text}</button></div>` : ''}
            </div>
            ${duration > 0 ? `<div class="toast-progress" style="height: 3px; background: rgba(255,255,255,0.3);"><div class="toast-progress-bar" style="height: 100%; background: rgba(255,255,255,0.7); width: 100%; transition: width ${duration}ms linear;"></div></div>` : ''}
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, {
        autohide: duration > 0,
        delay: duration
    });
    
    // Handle action button
    if (action && action.callback) {
        toastEl.querySelector('.toast-action')?.addEventListener('click', () => {
            action.callback();
            toast.hide();
        });
    }
    
    // Animate progress bar
    if (duration > 0) {
        const progressBar = toastEl.querySelector('.toast-progress-bar');
        if (progressBar) {
            setTimeout(() => {
                progressBar.style.width = '0%';
            }, 10);
        }
    }
    
    // Remove from DOM after hidden
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
    
    toast.show();
    
    return toast;
}

/**
 * Success toast
 * @param {string} message - Toast message
 * @param {Object} options - Additional options
 */
export function success(message, options = {}) {
    return showToast({ ...options, message, type: 'success' });
}

/**
 * Error toast
 * @param {string} message - Toast message
 * @param {Object} options - Additional options
 */
export function error(message, options = {}) {
    return showToast({ ...options, message, type: 'error' });
}

/**
 * Warning toast
 * @param {string} message - Toast message
 * @param {Object} options - Additional options
 */
export function warning(message, options = {}) {
    return showToast({ ...options, message, type: 'warning' });
}

/**
 * Info toast
 * @param {string} message - Toast message
 * @param {Object} options - Additional options
 */
export function info(message, options = {}) {
    return showToast({ ...options, message, type: 'info' });
}

/**
 * Clear all toasts
 */
export function clearAll() {
    if (toastContainer) {
        toastContainer.querySelectorAll('.toast').forEach(toastEl => {
            const toast = bootstrap.Toast.getInstance(toastEl);
            if (toast) {
                toast.hide();
            }
        });
    }
}

// Export as default object
export default {
    show: showToast,
    success,
    error,
    warning,
    info,
    clearAll
};
