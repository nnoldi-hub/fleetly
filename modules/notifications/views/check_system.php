<?php
/**
 * Pagină de verificare rapidă sistem notificări
 * Accesibil din: /notifications/check-system
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';

// Check authentication
$auth = Auth::getInstance();
if (!$auth->check()) {
    header('Location: ' . ROUTE_BASE . 'login');
    exit;
}

$db = Database::getInstance();
$checks = [];
$allGood = true;

// Helper function
function checkTable($db, $tableName) {
    try {
        $db->query("SELECT 1 FROM $tableName LIMIT 1");
        return ['exists' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['exists' => false, 'error' => $e->getMessage()];
    }
}

// 1. Check tabele principale
$tables = [
    'notifications' => 'Notificări principale',
    'notification_preferences' => 'Preferințe utilizatori (V2)',
    'notification_queue' => 'Queue procesare (V2)',
    'notification_templates' => 'Template-uri mesaje (V2)',
    'notification_logs' => 'Log-uri evenimente'
];

foreach ($tables as $table => $desc) {
    $result = checkTable($db, $table);
    $checks['tables'][$table] = [
        'name' => $desc,
        'exists' => $result['exists'],
        'error' => $result['error']
    ];
    if (!$result['exists']) $allGood = false;
}

// 2. Count records
try {
    $checks['counts'] = [
        'notifications' => $db->fetch("SELECT COUNT(*) as cnt FROM notifications")['cnt'] ?? 0,
        'templates' => checkTable($db, 'notification_templates')['exists'] 
            ? $db->fetch("SELECT COUNT(*) as cnt FROM notification_templates")['cnt'] ?? 0 
            : 'N/A',
        'preferences' => checkTable($db, 'notification_preferences')['exists']
            ? $db->fetch("SELECT COUNT(*) as cnt FROM notification_preferences")['cnt'] ?? 0
            : 'N/A',
        'queue' => checkTable($db, 'notification_queue')['exists']
            ? $db->fetch("SELECT COUNT(*) as cnt FROM notification_queue")['cnt'] ?? 0
            : 'N/A'
    ];
} catch (Throwable $e) {
    $checks['counts'] = ['error' => $e->getMessage()];
}

// 3. Check models
$models = [
    'Notification' => __DIR__ . '/../models/Notification.php',
    'NotificationPreference' => __DIR__ . '/../models/NotificationPreference.php',
    'NotificationQueue' => __DIR__ . '/../models/NotificationQueue.php',
    'NotificationTemplate' => __DIR__ . '/../models/NotificationTemplate.php'
];

foreach ($models as $name => $path) {
    $checks['models'][$name] = file_exists($path);
}

// 4. Check SMTP/SMS config
try {
    $smtpRow = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'smtp_settings'");
    $checks['smtp_configured'] = !empty($smtpRow['setting_value']);
    
    $smsRow = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
    $checks['sms_configured'] = !empty($smsRow['setting_value']);
} catch (Throwable $e) {
    $checks['smtp_configured'] = false;
    $checks['sms_configured'] = false;
}

// Header
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-heartbeat"></i> Status Sistem Notificări
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= ROUTE_BASE ?>notifications" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi la Notificări
            </a>
        </div>
    </div>

    <!-- Overall Status -->
    <div class="alert <?= $allGood ? 'alert-success' : 'alert-warning' ?> mb-4">
        <h4 class="alert-heading">
            <?php if ($allGood): ?>
                <i class="fas fa-check-circle"></i> Sistem Funcțional
            <?php else: ?>
                <i class="fas fa-exclamation-triangle"></i> Necesită Atenție
            <?php endif; ?>
        </h4>
        <p class="mb-0">
            <?php if ($allGood): ?>
                Toate componentele sistemului de notificări sunt instalate și funcționale.
            <?php else: ?>
                Unele tabele sau componente lipsesc. Verificați detaliile mai jos.
            <?php endif; ?>
        </p>
    </div>

    <!-- Tabele -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-database"></i> Tabele Bază de Date</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tabel</th>
                            <th>Descriere</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks['tables'] as $table => $info): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($table) ?></code></td>
                            <td><?= htmlspecialchars($info['name']) ?></td>
                            <td>
                                <?php if ($info['exists']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> EXISTS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> MISSING</span>
                                    <?php if ($info['error']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($info['error']) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$allGood): ?>
            <div class="alert alert-info mt-3">
                <strong><i class="fas fa-info-circle"></i> Soluție:</strong> 
                Rulați migrarea SQL: <code>sql/migrations/2025_01_12_001_notification_system_v2.sql</code>
                <br>
                <a href="<?= ROUTE_BASE ?>scripts/run_migration.php?file=sql/migrations/2025_01_12_001_notification_system_v2.sql" 
                   class="btn btn-sm btn-primary mt-2">
                    <i class="fas fa-play"></i> Rulează Migrarea Acum
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistici Date</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3><?= $checks['counts']['notifications'] ?></h3>
                            <p class="mb-0">Notificări Totale</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3><?= $checks['counts']['templates'] ?></h3>
                            <p class="mb-0">Template-uri</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3><?= $checks['counts']['preferences'] ?></h3>
                            <p class="mb-0">Preferințe Utilizatori</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3><?= $checks['counts']['queue'] ?></h3>
                            <p class="mb-0">Items în Queue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modele PHP -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-code"></i> Modele PHP</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($checks['models'] as $model => $exists): ?>
                <div class="col-md-6 mb-2">
                    <span class="badge <?= $exists ? 'bg-success' : 'bg-danger' ?>">
                        <i class="fas fa-<?= $exists ? 'check' : 'times' ?>"></i>
                    </span>
                    <code><?= htmlspecialchars($model) ?>.php</code>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Configurații -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-cog"></i> Configurații</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Email (SMTP)</h6>
                    <?php if ($checks['smtp_configured']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Configurat</span>
                    <?php else: ?>
                        <span class="badge bg-warning"><i class="fas fa-exclamation"></i> Neconfigurat</span>
                        <br><small>Accesați <a href="<?= ROUTE_BASE ?>notifications/settings">Setări Admin</a> pentru configurare.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6>SMS</h6>
                    <?php if ($checks['sms_configured']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i> Configurat</span>
                    <?php else: ?>
                        <span class="badge bg-warning"><i class="fas fa-exclamation"></i> Neconfigurat</span>
                        <br><small>Accesați <a href="<?= ROUTE_BASE ?>notifications/settings">Setări Admin</a> pentru configurare.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-rocket"></i> Acțiuni Rapide</h5>
        </div>
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?= ROUTE_BASE ?>notifications/preferences" class="btn btn-primary">
                    <i class="fas fa-user-cog"></i> Configurează Preferințe
                </a>
                <a href="<?= ROUTE_BASE ?>notifications/settings" class="btn btn-secondary">
                    <i class="fas fa-sliders-h"></i> Setări Admin
                </a>
                <button onclick="testNotification()" class="btn btn-info">
                    <i class="fas fa-vial"></i> Trimite Notificare Test
                </button>
                <a href="<?= ROUTE_BASE ?>notifications?action=generate" class="btn btn-success">
                    <i class="fas fa-magic"></i> Generează Notificări
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function testNotification() {
    if (confirm('Doriți să trimiteți o notificare de test?')) {
        fetch('<?= ROUTE_BASE ?>notifications/send-test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Notificare test trimisă cu succes!');
                location.reload();
            } else {
                alert('❌ Eroare: ' + (data.error || data.message));
            }
        })
        .catch(e => alert('❌ Eroare de rețea: ' + e.message));
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
