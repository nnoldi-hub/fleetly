<?php
// modules/notifications/views/preferences_simple.php
// View simplificat pentru utilizatori normali - doar preferințe personale
// FĂRĂ configurări SMTP/SMS (doar superadmin)

$notificationTypes = [
    'insurance_expiry' => [
        'label' => 'Expirare Asigurări',
        'icon' => 'fa-file-shield'
    ],
    'document_expiry' => [
        'label' => 'Expirare Documente',
        'icon' => 'fa-file-contract'
    ],
    'maintenance_due' => [
        'label' => 'Mentenanță Scadentă',
        'icon' => 'fa-wrench'
    ],
    'system_alert' => [
        'label' => 'Alerte Sistem',
        'icon' => 'fa-triangle-exclamation'
    ],
    'fuel_alert' => [
        'label' => 'Cheltuieli Combustibil',
        'icon' => 'fa-gas-pump'
    ],
    'driver_permit_expiry' => [
        'label' => 'Permise Conducere',
        'icon' => 'fa-id-card'
    ],
];

// Extrage preferințe din $data
$prefs = $data['prefs'] ?? [];
$currentUser = $data['currentUser'] ?? null;
$enabledTypes = $prefs['enabled_types'] ?? array_keys($notificationTypes);
$quietHours = $prefs['quiet_hours'] ?? ['start' => '22:00', 'end' => '08:00'];
?>

<div class="container-fluid py-4">
    <?php 
    $breadcrumb = [
        ['title' => 'Notificări', 'url' => ROUTE_BASE . 'notifications'],
        ['title' => 'Preferințe', 'url' => '']
    ];
    include ROOT_PATH . '/includes/breadcrumb.php'; 
    ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-user-cog text-primary me-2"></i>
                Preferințele Mele de Notificări
            </h1>
            <p class="text-muted mb-0">Personalizează modul în care primești notificări</p>
        </div>
        <a href="<?= ROUTE_BASE ?>notifications" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Înapoi
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <form method="POST" action="<?= ROUTE_BASE ?>notifications/savePreferences" id="preferencesForm">
        <div class="row">
            <!-- Tipuri de Notificări -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Ce Notificări Doresc să Primesc
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Bifează categoriile de notificări pe care dorești să le primești
                        </p>

                        <?php foreach ($notificationTypes as $typeKey => $typeInfo): ?>
                        <div class="form-check mb-3 p-3 border rounded <?= in_array($typeKey, $enabledTypes) ? 'bg-light' : '' ?>">
                            <input class="form-check-input" type="checkbox" 
                                   id="type_<?= $typeKey ?>" 
                                   name="enabled_types[]" 
                                   value="<?= $typeKey ?>"
                                   <?= in_array($typeKey, $enabledTypes) ? 'checked' : '' ?>>
                            <label class="form-check-label w-100" for="type_<?= $typeKey ?>">
                                <i class="fas <?= $typeInfo['icon'] ?> me-2 text-primary"></i>
                                <strong><?= $typeInfo['label'] ?></strong>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Contact & Metode Livrare -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            Unde să Primesc Notificările
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-at me-1"></i>
                                <strong>Email pentru notificări</strong>
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" 
                                   name="email" 
                                   placeholder="<?= htmlspecialchars($currentUser->email ?? 'email@exemplu.ro') ?>"
                                   value="<?= htmlspecialchars($prefs['email'] ?? $currentUser->email ?? '') ?>">
                            <small class="text-muted">
                                Lasă gol pentru a folosi email-ul din cont: <strong><?= htmlspecialchars($currentUser->email ?? 'N/A') ?></strong>
                            </small>
                        </div>

                        <hr>

                        <!-- Telefon SMS -->
                        <div class="mb-4">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                <strong>Telefon pentru SMS</strong> <span class="badge bg-warning">Opțional</span>
                            </label>
                            <input type="tel" class="form-control form-control-lg" id="phone" 
                                   name="phone" 
                                   placeholder="+40712345678"
                                   value="<?= htmlspecialchars($prefs['phone'] ?? '') ?>">
                            <small class="text-muted">
                                Format internațional (ex: +40712345678). Lasă gol dacă nu dorești SMS.
                            </small>
                        </div>

                        <hr>

                        <!-- Info -->
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-lightbulb me-2"></i>
                                Notă Importantă
                            </h6>
                            <p class="mb-0 small">
                                ✅ Notificările <strong>în aplicație</strong> sunt MEREU active<br>
                                📧 <strong>Email-urile</strong> se trimit automat dacă ai completat adresa<br>
                                📱 <strong>SMS-urile</strong> se trimit doar dacă ai completat numărul de telefon
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ore Liniștite -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-moon me-2"></i>
                            Nu Deranja (Quiet Hours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Alege intervalul orar în care NU dorești să primești notificări
                        </p>
                        
                        <div class="row">
                            <div class="col-6">
                                <label for="quiet_start" class="form-label">
                                    <i class="fas fa-moon me-1"></i>Început
                                </label>
                                <input type="time" class="form-control form-control-lg" 
                                       id="quiet_start" 
                                       name="quiet_hours[start]" 
                                       value="<?= htmlspecialchars($quietHours['start']) ?>">
                            </div>
                            <div class="col-6">
                                <label for="quiet_end" class="form-label">
                                    <i class="fas fa-sun me-1"></i>Sfârșit
                                </label>
                                <input type="time" class="form-control form-control-lg" 
                                       id="quiet_end" 
                                       name="quiet_hours[end]" 
                                       value="<?= htmlspecialchars($quietHours['end']) ?>">
                            </div>
                        </div>
                        
                        <div class="alert alert-light border mt-3 mb-0">
                            <small class="text-muted">
                                <strong>Exemplu:</strong> Setând 22:00 - 08:00, notificările primite în acest interval vor fi amânate pentru a doua zi.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save me-2"></i>Salvează Preferințele
                        </button>
                        <a href="<?= ROUTE_BASE ?>notifications" class="btn btn-outline-secondary btn-lg ms-2">
                            Anulare
                        </a>
                    </div>
                    <button type="button" class="btn btn-outline-info" onclick="resetToDefaults()">
                        <i class="fas fa-undo me-2"></i>Resetare Implicite
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Reset to defaults
function resetToDefaults() {
    if (confirm('Sigur dorești să resetezi toate preferințele la valorile implicite?')) {
        // Enable all notification types
        document.querySelectorAll('input[name="enabled_types[]"]').forEach(cb => cb.checked = true);
        
        // Clear email and phone
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        
        // Reset quiet hours
        document.getElementById('quiet_start').value = '22:00';
        document.getElementById('quiet_end').value = '08:00';
        
        alert('✅ Preferințe resetate! Apasă "Salvează" pentru a confirma.');
    }
}

// Form validation
document.getElementById('preferencesForm').addEventListener('submit', function(e) {
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    
    // Validare email (dacă este completat)
    if (emailInput.value && !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        e.preventDefault();
        alert('❌ Adresa de email nu este validă!');
        emailInput.focus();
        return false;
    }
    
    // Validare telefon (dacă este completat)
    if (phoneInput.value && !phoneInput.value.match(/^\+?[0-9]{10,15}$/)) {
        e.preventDefault();
        alert('❌ Numărul de telefon trebuie să fie în format internațional (ex: +40712345678)!');
        phoneInput.focus();
        return false;
    }
    
    // Verificare dacă cel puțin un tip de notificare este selectat
    const checkedTypes = document.querySelectorAll('input[name="enabled_types[]"]:checked');
    if (checkedTypes.length === 0) {
        e.preventDefault();
        alert('❌ Trebuie să selectezi cel puțin un tip de notificare!');
        return false;
    }
    
    return true;
});
</script>
