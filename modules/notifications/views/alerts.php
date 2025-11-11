<?php
// View curățat: layout (header/footer, sesiune, config) este gestionat de Controller::render.
// Presupunem că $notifications, $unreadCount, $stats, $filters sunt furnizate în $data.
?>
        <div class="container-fluid px-0">
            <?php 
            $breadcrumbs = [
                'Acasă' => '/',
                'Notificări' => '/modules/notifications/',
                'Alerte' => ''
            ];
            include 'includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-bell"></i> Alerte și Notificări
                    <?php if (!empty($unreadCount)): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="generateSystemNotifications()">
                            <i class="fas fa-magic"></i> Generează Notificări
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Marchează toate ca citite
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshAlerts()">
                            <i class="fas fa-sync-alt"></i> Actualizează
                        </button>
                        <a href="<?= ROUTE_BASE ?>notifications/settings" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-sliders-h"></i> Setări
                        </a>
                    </div>
                </div>
            </div>

            <?php // Mesajele flash sunt deja afișate de header.php; nu duplicăm aici. ?>

            <!-- Filtre -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="action" value="alerts">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Tip Notificare</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Toate tipurile</option>
                                <option value="insurance_expiry" <?php echo (isset($_GET['type']) && $_GET['type'] == 'insurance_expiry') ? 'selected' : ''; ?>>
                                    Expirare Asigurare
                                </option>
                                <option value="maintenance_due" <?php echo (isset($_GET['type']) && $_GET['type'] == 'maintenance_due') ? 'selected' : ''; ?>>
                                    Mentenanță Scadentă
                                </option>
                                <option value="document_expiry" <?php echo (isset($_GET['type']) && $_GET['type'] == 'document_expiry') ? 'selected' : ''; ?>>
                                    Expirare Document
                                </option>
                                <option value="fuel_alert" <?php echo (isset($_GET['type']) && $_GET['type'] == 'fuel_alert') ? 'selected' : ''; ?>>
                                    Alertă Combustibil
                                </option>
                                <option value="vehicle_issue" <?php echo (isset($_GET['type']) && $_GET['type'] == 'vehicle_issue') ? 'selected' : ''; ?>>
                                    Problemă Vehicul
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Prioritate</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="">Toate prioritățile</option>
                                <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'high') ? 'selected' : ''; ?>>
                                    Ridicată
                                </option>
                                <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'medium') ? 'selected' : ''; ?>>
                                    Medie
                                </option>
                                <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'low') ? 'selected' : ''; ?>>
                                    Scăzută
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Toate</option>
                                <option value="unread" <?php echo (isset($_GET['status']) && $_GET['status'] == 'unread') ? 'selected' : ''; ?>>
                                    Necitite
                                </option>
                                <option value="read" <?php echo (isset($_GET['status']) && $_GET['status'] == 'read') ? 'selected' : ''; ?>>
                                    Citite
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="per_page" class="form-label">Pe pagină</label>
                            <select class="form-select" id="per_page" name="per_page">
                                <option value="25" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '25') ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '50') ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '100') ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrează
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistici rapide -->
            <?php if (!empty($stats)): ?>
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $stats['high_priority_count'] ?? ($stats['high_priority'] ?? 0); ?></div>
                                    <div>Prioritate Ridicată</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $stats['insurance_expiring']; ?></div>
                                    <div>Asigurări în Expirare</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $stats['maintenance_due']; ?></div>
                                    <div>Mentenanță Scadentă</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-wrench fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $stats['total_unread']; ?></div>
                                    <div>Total Necitite</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-bell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista notificărilor -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notificări Active</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($notifications)): ?>
                        <div class="list-group list-group-flush" id="notificationListFull">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-light border-start border-4 border-primary'; ?>" 
                                     data-notification-id="<?php echo $notification['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <?php
                                                    $iconClass = 'fas fa-bell';
                                                    $badgeClass = 'bg-secondary';
                                                    
                                                    switch ($notification['type']) {
                                                        case 'insurance_expiry':
                                                            $iconClass = 'fas fa-shield-alt';
                                                            $badgeClass = $notification['priority'] == 'high' ? 'bg-danger' : 'bg-warning';
                                                            break;
                                                        case 'maintenance_due':
                                                            $iconClass = 'fas fa-wrench';
                                                            $badgeClass = 'bg-info';
                                                            break;
                                                        case 'document_expiry':
                                                            $iconClass = 'fas fa-file-alt';
                                                            $badgeClass = 'bg-warning';
                                                            break;
                                                        case 'fuel_alert':
                                                            $iconClass = 'fas fa-gas-pump';
                                                            $badgeClass = 'bg-primary';
                                                            break;
                                                        case 'vehicle_issue':
                                                            $iconClass = 'fas fa-car-crash';
                                                            $badgeClass = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?> p-2">
                                                        <i class="<?php echo $iconClass; ?>"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?php echo htmlspecialchars($notification['title']); ?>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge bg-primary ms-2">Nou</span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-<?php echo $notification['priority'] == 'high' ? 'danger' : ($notification['priority'] == 'medium' ? 'warning' : 'secondary'); ?> ms-1">
                                                            <?php echo ucfirst($notification['priority']); ?>
                                                        </span>
                                                    </h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <?php if ($notification['related_vehicle']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-car"></i> 
                                                            Vehicul: <?php echo htmlspecialchars($notification['related_vehicle']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?php echo timeAgo($notification['created_at']); ?>
                                            </small>
                                            <div class="btn-group btn-group-sm mt-2">
                                                <?php if (!$notification['is_read']): ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="markAsRead(<?php echo $notification['id']; ?>)" 
                                                            title="Marchează ca citit">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($notification['action_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                                       class="btn btn-outline-info btn-sm" title="Vezi detalii">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="dismissNotification(<?php echo $notification['id']; ?>)" 
                                                        title="Șterge notificarea">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginare -->
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <nav aria-label="Paginare notificări" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo ($currentPage == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?action=alerts&page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET)); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-bell-slash fa-3x mb-3 d-block"></i>
                                <h5>Nu aveți notificări</h5>
                                <p>Toate notificările vor apărea aici când sunt generate.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<script>
// Marchează o notificare ca citită
function markAsRead(notificationId) {
    fetch('<?= ROUTE_BASE ?>notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId + '&ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizăm interfața
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('list-group-item-light', 'border-start', 'border-4', 'border-primary');
                const newBadge = notificationElement.querySelector('.badge.bg-primary');
                if (newBadge) {
                    newBadge.remove();
                }
                const markButton = notificationElement.querySelector('button[onclick*="markAsRead"]');
                if (markButton) {
                    markButton.remove();
                }
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
function markAllAsRead() {
    if (confirm('Sigur doriți să marcați toate notificările ca citite?')) {
    fetch('<?= ROUTE_BASE ?>notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            alert('A apărut o eroare la marcarea notificărilor.');
        });
    }
}

// Șterge o notificare
function dismissNotification(notificationId) {
    if (confirm('Sigur doriți să ștergeți această notificare?')) {
    fetch('<?= ROUTE_BASE ?>notifications/dismiss', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + notificationId + '&ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminăm elementul din interfață
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
}

// Actualizează contorul de notificări
function updateNotificationCount() {
    fetch('<?= ROUTE_BASE ?>notifications/unread-count', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizăm badge-ul din header și alte locuri
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

// Auto-refresh la fiecare 5 minute
setInterval(function() {
    updateNotificationCount();
}, 300000); // 5 minute

// Generează notificări automate de sistem
function generateSystemNotifications() {
    if (!confirm('Generez notificări pentru asigurări/mentenanță/documente în expirare?')) {
        return;
    }
    
    const primaryUrl = '<?= ROUTE_BASE ?>notifications/generate?ajax=1';
    const fallbackUrl = '<?= BASE_URL ?>modules/notifications/?action=generateSystemNotifications&ajax=1';

    // Încercare 1: rută prin router (index.php)
    fetch(primaryUrl)
        .then(async r => {
            if (r.ok) return r.json();
            // extragem textul de eroare (poate fi HTML), apoi aruncăm pentru fallback
            const t = await r.text();
            throw { type: 'primary', status: r.status, body: t };
        })
        .then(data => handleGenerateResponse(data))
        .catch(err => {
            // Dacă a picat ruta principală, încercăm fallback direct pe modul și expunem mesajul JSON chiar dacă status=500
            console.warn('Generator via router a eșuat, încerc fallback direct:', err);
            return fetch(fallbackUrl)
                .then(async r2 => {
                    const raw = await r2.text();
                    try {
                        const data2 = JSON.parse(raw);
                        // Dacă vine JSON dar success=false, propagăm mesajul clar
                        if (!data2.success) {
                            throw new Error(data2.message || ('HTTP ' + r2.status));
                        }
                        return data2;
                    } catch (e) {
                        // Nu e JSON valid; includem primele caractere ca indiciu
                        throw new Error('HTTP ' + r2.status + ' ' + raw.slice(0, 200));
                    }
                })
                .then(data2 => handleGenerateResponse(data2))
                .catch(err2 => {
                    console.error('Generator fallback a eșuat:', err2);
                    alert('Eroare la generarea notificărilor: ' + (err2.message || 'necunoscută'));
                });
        });
}

function handleGenerateResponse(data) {
    if (data && data.success) {
        var created = (data && typeof data.created !== 'undefined' && data.created !== null) ? data.created : '?';
        alert('Succes! Au fost generate notificări pentru ' + created + ' evenimente.');
        location.reload();
    } else {
        alert('Eroare: ' + (data && data.message ? data.message : 'Generare eșuată'));
    }
}
</script>

<?php
// Helper local (dacă nu există global). Ideal mutat într-un utilitar comun.
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        if ($time < 60) return 'acum ' . $time . ' secunde';
        if ($time < 3600) return 'acum ' . floor($time/60) . ' minute';
        if ($time < 86400) return 'acum ' . floor($time/3600) . ' ore';
        if ($time < 2592000) return 'acum ' . floor($time/86400) . ' zile';
        return date('d.m.Y H:i', strtotime($datetime));
    }
}
?>
