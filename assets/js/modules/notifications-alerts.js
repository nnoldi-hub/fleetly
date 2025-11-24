// Notification alerts JavaScript functions

// Marchează o notificare ca citită
function markAsRead(notificationId) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(baseUrl + '/modules/notifications/index.php?action=markAsRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId + '&ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('list-group-item-light', 'border-start', 'border-4', 'border-primary');
                const newBadge = notificationElement.querySelector('.badge.bg-primary');
                if (newBadge) newBadge.remove();
                const markButton = notificationElement.querySelector('button[onclick*="markAsRead"]');
                if (markButton) markButton.remove();
            }
            updateNotificationCount();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert('A apărut o eroare la marcarea notificării.');
    });
}

// Marchează toate notificările ca citite
function markAllAsRead(e) {
    console.log('markAllAsRead called', e);
    if (!confirm('Sigur doriți să marcați toate notificările ca citite?')) {
        return;
    }
    
    const btn = e ? e.currentTarget : e.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesare...';
    
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const url = baseUrl + '/modules/notifications/index.php?action=mark-all-read';
    console.log('Fetching URL:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Toate notificările au fost marcate ca citite!');
            location.reload();
        } else {
            alert('Eroare: ' + (data.message || 'Operație eșuată'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert('A apărut o eroare la marcarea notificărilor.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Șterge o notificare
function dismissNotification(notificationId) {
    if (!confirm('Sigur doriți să ștergeți această notificare?')) {
        return;
    }
    
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(baseUrl + '/modules/notifications/index.php?action=dismiss', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId + '&ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.remove();
            }
            updateNotificationCount();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert('A apărut o eroare la ștergerea notificării.');
    });
}

// Actualizează contorul de notificări
function updateNotificationCount() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(baseUrl + '/modules/notifications/index.php?action=unread-count', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badges = document.querySelectorAll('.notification-badge');
            badges.forEach(badge => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            });
        }
    })
    .catch(error => {
        console.error('Eroare la actualizarea contorului:', error);
    });
}

// Actualizează alertele
function refreshAlerts() {
    location.reload();
}

// Generează notificări automate de sistem
function generateSystemNotifications(e) {
    console.log('generateSystemNotifications called', e);
    if (!confirm('Generez notificări pentru asigurări/mentenanță/documente în expirare?')) {
        return;
    }
    
    const btn = e ? e.currentTarget : e.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generare...';
    
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const url = baseUrl + '/modules/notifications/index.php?action=generate-system';
    console.log('Fetching URL:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Succes! Au fost generate ' + (data.created || 0) + ' notificări.');
            location.reload();
        } else {
            alert('Eroare: ' + (data.message || 'Generare eșuată'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Eroare:', error);
        alert('A apărut o eroare la generarea notificărilor.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Auto-refresh la fiecare 5 minute
setInterval(function() {
    updateNotificationCount();
}, 300000); // 5 minute

// Attach event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Notification alerts JavaScript loaded!');
    
    // Generate notifications button
    const btnGenerate = document.getElementById('btn-generate-notifications');
    if (btnGenerate) {
        btnGenerate.addEventListener('click', generateSystemNotifications);
        console.log('Generate button listener attached');
    }
    
    // Mark all as read button
    const btnMarkAll = document.getElementById('btn-mark-all-read');
    if (btnMarkAll) {
        btnMarkAll.addEventListener('click', markAllAsRead);
        console.log('Mark all button listener attached');
    }
    
    // Refresh button
    const btnRefresh = document.getElementById('btn-refresh-alerts');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', refreshAlerts);
        console.log('Refresh button listener attached');
    }
});
