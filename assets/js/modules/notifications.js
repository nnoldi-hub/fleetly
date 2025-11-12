/**
 * Notifications Module JavaScript
 * Handles all notification-related AJAX calls and UI interactions
 */

// Generate system notifications (expiring documents, insurance, maintenance)
function generateSystemNotifications() {
    if (!confirm('Doriți să generați notificări pentru documentele/asigurările/mentenanțele care expiră în curând?')) {
        return;
    }

    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generare...';

    fetch('/notifications/generateSystemNotifications', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Notificări generate cu succes!');
            // Refresh the page after 1 second to show new notifications
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Eroare la generarea notificărilor');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Eroare de conexiune');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Mark all notifications as read
function markAllAsRead() {
    if (!confirm('Marcați toate notificările ca citite?')) {
        return;
    }

    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesare...';

    fetch('/notifications/markAllAsRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Toate notificările au fost marcate ca citite');
            // Update UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
            });
            // Update badge
            const badge = document.querySelector('.badge.bg-danger');
            if (badge) {
                badge.remove();
            }
            // Update stats
            const unreadCard = document.querySelector('.card-body:contains("Necitite")');
            if (unreadCard) {
                setTimeout(() => window.location.reload(), 500);
            }
        } else {
            showToast('error', data.message || 'Eroare la marcarea notificărilor');
        }
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Eroare de conexiune');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Refresh alerts/notifications list
function refreshAlerts() {
    window.location.reload();
}

// Mark single notification as read
function markAsRead(notificationId) {
    fetch('/notifications/markAsRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
            }
            // Update unread count
            updateUnreadCount();
        } else {
            showToast('error', data.message || 'Eroare la marcarea notificării');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Eroare de conexiune');
    });
}

// Dismiss notification
function dismissNotification(notificationId) {
    if (!confirm('Doriți să respingeți această notificare?')) {
        return;
    }

    fetch('/notifications/dismiss', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.style.transition = 'opacity 0.3s';
                notificationElement.style.opacity = '0';
                setTimeout(() => {
                    notificationElement.remove();
                }, 300);
            }
            showToast('success', 'Notificare respinsă');
            updateUnreadCount();
        } else {
            showToast('error', data.message || 'Eroare la respingerea notificării');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Eroare de conexiune');
    });
}

// Update unread count badge
function updateUnreadCount() {
    fetch('/notifications/getUnreadCount', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.badge.bg-danger');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const h1 = document.querySelector('h1.h2');
                    if (h1) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger ms-2';
                        newBadge.textContent = data.count;
                        h1.appendChild(newBadge);
                    }
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error updating unread count:', error);
    });
}

// Toast notification helper
function showToast(type, message) {
    // Check if Bootstrap toast exists
    const toastContainer = document.querySelector('.toast-container');
    if (toastContainer) {
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Remove toast after it's hidden
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    } else {
        // Fallback to alert
        alert(message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-update unread count every 60 seconds
    setInterval(updateUnreadCount, 60000);
    
    // Add click handlers for notification items
    document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            if (notificationId) {
                markAsRead(notificationId);
            }
        });
    });
});
