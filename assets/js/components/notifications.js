/**
 * Notifications Component
 * Provides toast notifications and notification management
 */
class Notifications {
    constructor(options = {}) {
        this.options = {
            container: '.notifications-container',
            duration: 5000,
            position: 'top-right',
            maxNotifications: 5,
            ...options
        };
        
        this.notifications = [];
        this.init();
    }
    
    init() {
        this.createContainer();
        this.bindEvents();
    }
    
    createContainer() {
        let container = document.querySelector(this.options.container);
        if (!container) {
            container = document.createElement('div');
            container.className = `notifications-container position-${this.options.position}`;
            document.body.appendChild(container);
        }
        this.container = container;
    }
    
    bindEvents() {
        // Handle dismiss buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.notification-dismiss')) {
                const notification = e.target.closest('.notification');
                if (notification) {
                    this.dismiss(notification);
                }
            }
        });
    }
    
    show(message, type = 'info', options = {}) {
        const notification = this.create(message, type, options);
        this.add(notification);
        
        // Auto dismiss
        if (options.duration !== false) {
            const duration = options.duration || this.options.duration;
            setTimeout(() => {
                this.dismiss(notification);
            }, duration);
        }
        
        return notification;
    }
    
    create(message, type, options = {}) {
        const notification = document.createElement('div');
        notification.className = `notification alert alert-${type} fade show`;
        notification.setAttribute('role', 'alert');
        
        const icon = this.getIcon(type);
        const dismissible = options.dismissible !== false;
        
        notification.innerHTML = `
            <div class="notification-content">
                ${icon ? `<i class="fas fa-${icon} me-2"></i>` : ''}
                <span class="notification-message">${message}</span>
            </div>
            ${dismissible ? '<button type="button" class="btn-close notification-dismiss"></button>' : ''}
        `;
        
        return notification;
    }
    
    add(notification) {
        // Remove oldest if at max capacity
        if (this.notifications.length >= this.options.maxNotifications) {
            this.dismiss(this.notifications[0]);
        }
        
        this.container.appendChild(notification);
        this.notifications.push(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
    }
    
    dismiss(notification) {
        if (!notification || !notification.parentNode) return;
        
        notification.classList.remove('show');
        notification.classList.add('hiding');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
        }, 300);
    }
    
    clear() {
        this.notifications.forEach(notification => {
            this.dismiss(notification);
        });
    }
    
    getIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || null;
    }
    
    // Convenience methods
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }
    
    error(message, options = {}) {
        return this.show(message, 'danger', { duration: 8000, ...options });
    }
    
    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }
    
    info(message, options = {}) {
        return this.show(message, 'info', options);
    }
    
    // Static instance
    static instance = null;
    
    static getInstance() {
        if (!this.instance) {
            this.instance = new Notifications();
        }
        return this.instance;
    }
    
    static show(message, type, options) {
        return this.getInstance().show(message, type, options);
    }
    
    static success(message, options) {
        return this.getInstance().success(message, options);
    }
    
    static error(message, options) {
        return this.getInstance().error(message, options);
    }
    
    static warning(message, options) {
        return this.getInstance().warning(message, options);
    }
    
    static info(message, options) {
        return this.getInstance().info(message, options);
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notifications system
    window.notifications = Notifications.getInstance();
    
    // Handle server-side flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        const type = message.dataset.type || 'info';
        const text = message.textContent.trim();
        if (text) {
            notifications.show(text, type);
        }
        message.remove();
    });
    
    // Handle AJAX form responses
    document.addEventListener('ajax:success', function(e) {
        if (e.detail.message) {
            notifications.success(e.detail.message);
        }
    });
    
    document.addEventListener('ajax:error', function(e) {
        const message = e.detail.message || 'A apărut o eroare neașteptată';
        notifications.error(message);
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Notifications;
}
