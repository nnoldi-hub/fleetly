/**
 * Modal Component
 * Provides modal dialog functionality
 */
class Modal {
    constructor(selector, options = {}) {
        this.modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
        this.options = {
            backdrop: true,
            keyboard: true,
            focus: true,
            ...options
        };
        
        if (this.modal) {
            this.init();
        }
    }
    
    init() {
        this.bindEvents();
        this.createBackdrop();
    }
    
    bindEvents() {
        // Close button
        const closeBtn = this.modal.querySelector('.modal-close, .btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        
        // Backdrop click
        if (this.options.backdrop) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.hide();
                }
            });
        }
        
        // Keyboard events
        if (this.options.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible()) {
                    this.hide();
                }
            });
        }
    }
    
    createBackdrop() {
        if (!this.modal.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            this.modal.appendChild(backdrop);
        }
    }
    
    show() {
        this.modal.style.display = 'block';
        this.modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        if (this.options.focus) {
            this.modal.focus();
        }
        
        // Trigger custom event
        this.modal.dispatchEvent(new CustomEvent('modal:show'));
    }
    
    hide() {
        this.modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        setTimeout(() => {
            this.modal.style.display = 'none';
        }, 300);
        
        // Trigger custom event
        this.modal.dispatchEvent(new CustomEvent('modal:hide'));
    }
    
    toggle() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    isVisible() {
        return this.modal.classList.contains('show');
    }
    
    // Static methods
    static getInstance(element) {
        return element._modalInstance || new Modal(element);
    }
    
    static confirm(title, message, callback) {
        const confirmModal = Modal.createConfirmModal(title, message, callback);
        document.body.appendChild(confirmModal);
        const modalInstance = new Modal(confirmModal);
        modalInstance.show();
        return modalInstance;
    }
    
    static createConfirmModal(title, message, callback) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close modal-close"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-close">Anulează</button>
                        <button type="button" class="btn btn-danger confirm-btn">Confirmă</button>
                    </div>
                </div>
            </div>
        `;
        
        // Bind confirm button
        modal.querySelector('.confirm-btn').addEventListener('click', () => {
            if (callback) callback();
            modal.remove();
        });
        
        // Bind close buttons
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.remove());
        });
        
        return modal;
    }
}

// Auto-initialize modals on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal._modalInstance = new Modal(modal);
    });
    
    // Handle modal triggers
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-modal-target]');
        if (trigger) {
            e.preventDefault();
            const targetSelector = trigger.getAttribute('data-modal-target');
            const modal = document.querySelector(targetSelector);
            if (modal && modal._modalInstance) {
                modal._modalInstance.show();
            }
        }
        
        // Handle confirm dialogs
        const confirmTrigger = e.target.closest('[data-confirm]');
        if (confirmTrigger) {
            e.preventDefault();
            const message = confirmTrigger.getAttribute('data-confirm');
            const title = confirmTrigger.getAttribute('data-confirm-title') || 'Confirmare';
            
            Modal.confirm(title, message, () => {
                // If it's a form, submit it
                if (confirmTrigger.type === 'submit') {
                    confirmTrigger.form.submit();
                }
                // If it's a link, navigate to it
                else if (confirmTrigger.href) {
                    window.location.href = confirmTrigger.href;
                }
                // If it has onclick, execute it
                else if (confirmTrigger.onclick) {
                    confirmTrigger.onclick();
                }
            });
        }
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Modal;
}
