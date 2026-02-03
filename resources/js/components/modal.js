/**
 * Modal Component Enhancement
 * Enhanced functionality for Bootstrap modals
 */

import { generateId } from '../utils/helpers.js';

/**
 * Initialize modal enhancements
 */
export function initModals() {
    // Add animation classes when modals show/hide
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('show.bs.modal', function() {
            const dialog = this.querySelector('.modal-dialog');
            if (dialog) {
                dialog.classList.add('animate-scale-in');
            }
        });
        
        modalEl.addEventListener('hidden.bs.modal', function() {
            const dialog = this.querySelector('.modal-dialog');
            if (dialog) {
                dialog.classList.remove('animate-scale-in');
            }
        });
    });
}

/**
 * Show modal programmatically
 * @param {string} modalId - Modal element ID
 */
export function showModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Hide modal programmatically
 * @param {string} modalId - Modal element ID
 */
export function hideModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
}

/**
 * Create confirm dialog
 * @param {Object} options - Dialog options
 * @returns {Promise<boolean>}
 */
export function confirmDialog(options = {}) {
    const {
        title = 'Confirm',
        message = 'Are you sure?',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        confirmVariant = 'primary',
        cancelVariant = 'secondary'
    } = options;
    
    return new Promise((resolve) => {
        const modalId = generateId();
        
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered animate-scale-in">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-${cancelVariant}" data-bs-dismiss="modal">${cancelText}</button>
                            <button type="button" class="btn btn-${confirmVariant} confirm-btn">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modalEl = document.getElementById(modalId);
        const modal = new bootstrap.Modal(modalEl);
        
        modalEl.querySelector('.confirm-btn').addEventListener('click', () => {
            modal.hide();
            resolve(true);
        });
        
        modalEl.addEventListener('hidden.bs.modal', () => {
            modalEl.remove();
            resolve(false);
        });
        
        modal.show();
    });
}

/**
 * Create alert dialog
 * @param {Object} options - Dialog options
 */
export function alertDialog(options = {}) {
    const {
        title = 'Alert',
        message = '',
        buttonText = 'OK',
        variant = 'primary'
    } = options;
    
    return new Promise((resolve) => {
        const modalId = generateId();
        
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered animate-scale-in">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-${variant}" data-bs-dismiss="modal">${buttonText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modalEl = document.getElementById(modalId);
        const modal = new bootstrap.Modal(modalEl);
        
        modalEl.addEventListener('hidden.bs.modal', () => {
            modalEl.remove();
            resolve();
        });
        
        modal.show();
    });
}

/**
 * Focus trap for modals (accessibility)
 * @param {HTMLElement} modalEl - Modal element
 */
export function setupFocusTrap(modalEl) {
    const focusableElements = modalEl.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    modalEl.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            }
        } else {
            if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
    });
    
    // Focus first element when modal opens
    modalEl.addEventListener('shown.bs.modal', () => {
        firstElement.focus();
    });
}

// Initialize on DOM ready
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initModals();
        
        // Setup focus trap for all modals
        document.querySelectorAll('.modal').forEach(setupFocusTrap);
    });
}
