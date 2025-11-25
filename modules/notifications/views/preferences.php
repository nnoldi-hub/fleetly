<?php
// modules/notifications/views/preferences.php
// UI pentru configurarea preferințelor personale de notificări (V2)
// Datele sunt preluate din controller prin extract($data)
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
        <h1 class="h3 mb-0">
            <i class="fas fa-sliders-h text-primary me-2"></i>
            Preferințe Notificări
        </h1>
        <a href="<?= ROUTE_BASE ?>notifications" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Înapoi la Notificări
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
            <!-- Canale de Livrare -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-broadcast-tower me-2"></i>
                            Canale de Livrare
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Selectați modalitățile prin care doriți să primiți notificări.
                        </p>

                        <!-- In-App -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="in_app_enabled" 
                                   name="in_app_enabled" value="1" 
                                   <?= $prefs['in_app_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="in_app_enabled">
                                <i class="fas fa-bell text-info me-2"></i>
                                <strong>Notificări In-App</strong>
                                <small class="d-block text-muted">Afișare în aplicație (implicit activ)</small>
                            </label>
                        </div>

                        <!-- Email -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="email_enabled" 
                                   name="email_enabled" value="1" 
                                   <?= $prefs['email_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_enabled">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <strong>Email</strong>
                                <small class="d-block text-muted">Trimitere prin email</small>
                            </label>
                        </div>

                        <div class="mb-3 ms-4 email-override-section" style="<?= $prefs['email_enabled'] ? '' : 'display: none;' ?>">
                            <label for="email_override" class="form-label">
                                <i class="fas fa-at me-1"></i>Email alternativ (opțional)
                            </label>
                            <input type="email" class="form-control" id="email_override" 
                                   name="email" placeholder="ex: <?= htmlspecialchars($currentUser->email ?? 'user@example.com') ?>"
                                   value="<?= htmlspecialchars($prefs['email'] ?? '') ?>">
                            <small class="text-muted">Lasați gol pentru a folosi email-ul din cont</small>
                        </div>

                        <!-- SMS -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="sms_enabled" 
                                   name="sms_enabled" value="1" 
                                   <?= $prefs['sms_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sms_enabled">
                                <i class="fas fa-sms text-success me-2"></i>
                                <strong>SMS</strong>
                                <small class="d-block text-muted">Trimitere prin SMS (poate implica costuri)</small>
                            </label>
                        </div>

                        <div class="mb-3 ms-4 sms-override-section" style="<?= $prefs['sms_enabled'] ? '' : 'display: none;' ?>">
                            <label for="phone_override" class="form-label">
                                <i class="fas fa-phone me-1"></i>Telefon pentru SMS
                            </label>
                            <input type="tel" class="form-control" id="phone_override" 
                                   name="phone" placeholder="ex: +40712345678"
                                   value="<?= htmlspecialchars($prefs['phone'] ?? '') ?>">
                            <small class="text-muted">Format internațional (ex: +40712345678)</small>
                        </div>

                        <!-- Push Notifications -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="push_enabled" 
                                   name="push_enabled" value="1" 
                                   <?= $prefs['push_enabled'] ? 'checked' : '' ?>
                                   disabled>
                            <label class="form-check-label" for="push_enabled">
                                <i class="fas fa-mobile-alt text-warning me-2"></i>
                                <strong>Push Notifications</strong>
                                <small class="d-block text-muted">În curând - necesită aplicație mobilă</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tipuri de Notificări -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-check me-2"></i>
                            Tipuri de Notificări
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Selectați categoriile de notificări pe care doriți să le primiți.
                        </p>

                        <?php foreach ($notificationTypes as $typeKey => $typeInfo): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="type_<?= $typeKey ?>" 
                                   name="enabled_types[]" 
                                   value="<?= $typeKey ?>"
                                   <?= in_array($typeKey, $enabledTypes) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type_<?= $typeKey ?>">
                                <i class="fas <?= $typeInfo['icon'] ?> me-2"></i>
                                <strong><?= $typeInfo['label'] ?></strong>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <hr class="my-4">

                        <!-- Prioritate Minimă -->
                        <div class="mb-3">
                            <label for="min_priority" class="form-label">
                                <i class="fas fa-flag me-1"></i>Prioritate Minimă
                            </label>
                            <select class="form-select" id="min_priority" name="min_priority">
                                <option value="low" <?= $prefs['min_priority'] === 'low' ? 'selected' : '' ?>>
                                    Scăzută (primesc toate)
                                </option>
                                <option value="medium" <?= $prefs['min_priority'] === 'medium' ? 'selected' : '' ?>>
                                    Medie (fără notificări scăzute)
                                </option>
                                <option value="high" <?= $prefs['min_priority'] === 'high' ? 'selected' : '' ?>>
                                    Înaltă (doar urgente)
                                </option>
                            </select>
                            <small class="text-muted">Filtrează notificările pe bază de prioritate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Frecvență și Timing -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Frecvență și Timing
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Frecvență -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Frecvență Livrare
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequency" 
                                       id="freq_immediate" value="immediate"
                                       <?= $prefs['frequency'] === 'immediate' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="freq_immediate">
                                    <strong>Imediat</strong>
                                    <small class="d-block text-muted">Trimitere instantanee (recomandat)</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequency" 
                                       id="freq_daily" value="daily"
                                       <?= $prefs['frequency'] === 'daily' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="freq_daily">
                                    <strong>Rezumat Zilnic</strong>
                                    <small class="d-block text-muted">Un singur email/SMS pe zi la 06:00</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequency" 
                                       id="freq_weekly" value="weekly"
                                       <?= $prefs['frequency'] === 'weekly' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="freq_weekly">
                                    <strong>Rezumat Săptămânal</strong>
                                    <small class="d-block text-muted">Luni la 09:00</small>
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Ore Liniștite -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-moon me-1"></i>Ore Liniștite (Do Not Disturb)
                            </label>
                            <small class="d-block text-muted mb-2">Nu primiți notificări în acest interval</small>
                            <div class="row">
                                <div class="col-6">
                                    <label for="quiet_start" class="form-label text-muted small">Început</label>
                                    <input type="time" class="form-control" id="quiet_start" 
                                           name="quiet_hours[start]" 
                                           value="<?= htmlspecialchars($quietHours['start']) ?>">
                                </div>
                                <div class="col-6">
                                    <label for="quiet_end" class="form-label text-muted small">Sfârșit</label>
                                    <input type="time" class="form-control" id="quiet_end" 
                                           name="quiet_hours[end]" 
                                           value="<?= htmlspecialchars($quietHours['end']) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Timezone -->
                        <div class="mb-3">
                            <label for="timezone" class="form-label">
                                <i class="fas fa-globe me-1"></i>Fus Orar
                            </label>
                            <select class="form-select" id="timezone" name="timezone">
                                <?php foreach ($timezones as $tz => $label): ?>
                                <option value="<?= $tz ?>" <?= $prefs['timezone'] === $tz ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expirări și Acțiuni -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-times me-2"></i>
                            Configurare Expirări
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Zile Înainte de Expirare -->
                        <div class="mb-4">
                            <label for="days_before_expiry" class="form-label">
                                <i class="fas fa-calendar-day me-1"></i>
                                Zile înainte de expirare pentru avertizare
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="days_before_expiry" 
                                       name="days_before_expiry" min="7" max="90" step="1"
                                       value="<?= $prefs['days_before_expiry'] ?? 30 ?>"
                                       oninput="updateDaysLabel(this.value)">
                                <span class="input-group-text">zile</span>
                            </div>
                            <small class="text-muted">
                                <span id="days_label">Primiți notificări cu <?= $prefs['days_before_expiry'] ?? 30 ?> zile înainte</span>
                                (minim 7, maxim 90)
                            </small>
                            <div class="mt-2">
                                <input type="range" class="form-range" min="7" max="90" step="1"
                                       value="<?= $prefs['days_before_expiry'] ?? 30 ?>"
                                       oninput="document.getElementById('days_before_expiry').value = this.value; updateDaysLabel(this.value)">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Info Box -->
                        <div class="alert alert-light border">
                            <h6 class="alert-heading">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Sfat Util
                            </h6>
                            <p class="mb-0 small">
                                <strong>Frecvență Imediat + Email:</strong> Ideală pentru notificări urgente<br>
                                <strong>Rezumat Zilnic:</strong> Reduc numărul de email-uri primite<br>
                                <strong>Ore Liniștite:</strong> Notificările se amână pentru ziua următoare
                            </p>
                        </div>

                        <!-- Test Notification -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="testNotification()">
                                <i class="fas fa-paper-plane me-2"></i>
                                Trimite Notificare Test
                            </button>
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
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Salvează Preferințe
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg ms-2" onclick="resetToDefaults()">
                            <i class="fas fa-undo me-2"></i>Resetare Implicite
                        </button>
                    </div>
                    <a href="<?= ROUTE_BASE ?>notifications" class="btn btn-outline-secondary">
                        Anulare
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Toggle email override section
document.getElementById('email_enabled').addEventListener('change', function() {
    document.querySelector('.email-override-section').style.display = this.checked ? 'block' : 'none';
});

// Toggle SMS override section
document.getElementById('sms_enabled').addEventListener('change', function() {
    document.querySelector('.sms-override-section').style.display = this.checked ? 'block' : 'none';
});

// Update days label
function updateDaysLabel(value) {
    document.getElementById('days_label').textContent = `Primiți notificări cu ${value} zile înainte`;
}

// Reset to defaults
function resetToDefaults() {
    if (confirm('Sigur doriți să resetați toate preferințele la valorile implicite?')) {
        // Set default values
        document.getElementById('in_app_enabled').checked = true;
        document.getElementById('email_enabled').checked = false;
        document.getElementById('sms_enabled').checked = false;
        document.getElementById('push_enabled').checked = false;
        
        // Enable all types
        document.querySelectorAll('input[name="enabled_types[]"]').forEach(cb => cb.checked = true);
        
        document.getElementById('min_priority').value = 'low';
        document.getElementById('freq_immediate').checked = true;
        document.getElementById('days_before_expiry').value = 30;
        document.getElementById('quiet_start').value = '22:00';
        document.getElementById('quiet_end').value = '08:00';
        document.getElementById('timezone').value = 'Europe/Bucharest';
        
        updateDaysLabel(30);
        
        // Hide override sections
        document.querySelector('.email-override-section').style.display = 'none';
        document.querySelector('.sms-override-section').style.display = 'none';
    }
}

// Test notification
function testNotification() {
    if (confirm('Trimitem o notificare test pe canalele active?')) {
        fetch('<?= ROUTE_BASE ?>notifications/sendTest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Notificare test trimisă! Verificați email-ul/SMS-ul.');
            } else {
                alert('❌ Eroare: ' + (data.error || 'Necunoscut'));
            }
        })
        .catch(err => alert('❌ Eroare de rețea: ' + err.message));
    }
}

// Form validation
document.getElementById('preferencesForm').addEventListener('submit', function(e) {
    const emailEnabled = document.getElementById('email_enabled').checked;
    const smsEnabled = document.getElementById('sms_enabled').checked;
    
    if (emailEnabled) {
        const emailInput = document.getElementById('email_override');
        if (emailInput.value && !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('❌ Adresa de email nu este validă!');
            emailInput.focus();
            return false;
        }
    }
    
    if (smsEnabled) {
        const phoneInput = document.getElementById('phone_override');
        if (phoneInput.value && !phoneInput.value.match(/^\+?[0-9]{10,15}$/)) {
            e.preventDefault();
            alert('❌ Numărul de telefon trebuie să fie în format internațional (ex: +40712345678)!');
            phoneInput.focus();
            return false;
        }
    }
    
    return true;
});
</script>
