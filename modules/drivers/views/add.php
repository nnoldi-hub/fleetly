<?php
// modules/drivers/views/add.php
$pageTitle = "Adaugă Șofer Nou";

// Obținem lista vehiculelor pentru dropdown
$vehicleModel = new Vehicle();
$vehicles = method_exists($vehicleModel, 'findAll') 
    ? $vehicleModel->findAll() 
    : (method_exists($vehicleModel, 'getAllVehicles') 
        ? $vehicleModel->getAllVehicles() 
        : []); // fallback to empty array if neither method exists

// Categorii de permis
$licenseCategories = [
    'A' => 'Categoria A - Motociclete',
    'A1' => 'Categoria A1 - Motociclete ușoare',
    'A2' => 'Categoria A2 - Motociclete mijlocii',
    'B' => 'Categoria B - Autoturisme',
    'B1' => 'Categoria B1 - Autoturisme ușoare',
    'BE' => 'Categoria BE - Autoturisme cu remorcă',
    'C' => 'Categoria C - Camioane',
    'C1' => 'Categoria C1 - Camioane ușoare',
    'CE' => 'Categoria CE - Camioane cu remorcă',
    'C1E' => 'Categoria C1E - Camioane ușoare cu remorcă',
    'D' => 'Categoria D - Autobuze',
    'D1' => 'Categoria D1 - Microbuze',
    'DE' => 'Categoria DE - Autobuze cu remorcă',
    'D1E' => 'Categoria D1E - Microbuze cu remorcă'
];

// Tipuri de contract
$contractTypes = [
    'full_time' => 'Normă întreagă',
    'part_time' => 'Normă parțială',
    'contract' => 'Contract de prestări servicii',
    'temporary' => 'Contract temporar',
    'intern' => 'Stagiar'
];

// Mesaje de eroare sau succes
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Funcție helper pentru a obține valori din sesiune (dacă nu există deja)
if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_SESSION['form_data'][$key] ?? $default;
    }
}
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>drivers">Șoferi</a></li>
                <li class="breadcrumb-item active">Adaugă Șofer</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-plus text-primary me-2"></i>
                Adaugă Șofer Nou
            </h1>
            <a href="<?= BASE_URL ?>drivers" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Înapoi la Listă
            </a>
        </div>

        <!-- Mesaje de eroare/succes -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erori găsite:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formular Adăugare Șofer -->
        <div class="row">
            <div class="col-lg-8">
                <form action="<?= BASE_URL ?>drivers/add" method="POST" enctype="multipart/form-data" id="addDriverForm">
                    
                    <!-- Informații Personale -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>
                                Informații Personale
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nume Complet -->
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required">
                                        <i class="fas fa-user me-1"></i>
                                        Nume Complet
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= old('name') ?>" required 
                                           placeholder="ex: Popescu Ion">
                                    <div class="form-text">Numele complet al șoferului</div>
                                </div>

                                <!-- CNP -->
                                <div class="col-md-6 mb-3">
                                    <label for="cnp" class="form-label required">
                                        <i class="fas fa-id-card me-1"></i>
                                        CNP
                                    </label>
                                    <input type="text" class="form-control" id="cnp" name="cnp" 
                                           value="<?= old('cnp') ?>" required maxlength="13"
                                           placeholder="1234567890123">
                                    <div class="form-text">Codul numeric personal (13 cifre)</div>
                                </div>

                                <!-- Data Nașterii -->
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label required">
                                        <i class="fas fa-birthday-cake me-1"></i>
                                        Data Nașterii
                                    </label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="<?= old('birth_date') ?>" required>
                                    <div class="form-text">Data nașterii șoferului</div>
                                </div>

                                <!-- Adresa -->
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label required">
                                        <i class="fas fa-home me-1"></i>
                                        Adresa
                                    </label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?= old('address') ?>" required 
                                           placeholder="Str. Exemplu nr. 123, București">
                                    <div class="form-text">Adresa de domiciliu</div>
                                </div>

                                <!-- Telefon -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label required">
                                        <i class="fas fa-phone me-1"></i>
                                        Telefon
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= old('phone') ?>" required 
                                           placeholder="0723456789">
                                    <div class="form-text">Numărul de telefon pentru contact</div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= old('email') ?>" 
                                           placeholder="sofer@email.com">
                                    <div class="form-text">Adresa de email (opțional)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informații Permis de Conducere -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-id-badge me-2"></i>
                                Permis de Conducere
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Număr Permis -->
                                <div class="col-md-6 mb-3">
                                    <label for="license_number" class="form-label required">
                                        <i class="fas fa-credit-card me-1"></i>
                                        Număr Permis
                                    </label>
                                    <input type="text" class="form-control" id="license_number" name="license_number" 
                                           value="<?= old('license_number') ?>" required 
                                           placeholder="ex: AB123456">
                                    <div class="form-text">Numărul unic al permisului de conducere</div>
                                </div>

                                <!-- Categorie Permis -->
                                <div class="col-md-6 mb-3">
                                    <label for="license_category" class="form-label required">
                                        <i class="fas fa-tags me-1"></i>
                                        Categorie Permis
                                    </label>
                                    <select class="form-select" id="license_category" name="license_category" required>
                                        <option value="">Selectează categoria</option>
                                        <?php foreach ($licenseCategories as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= (old('license_category') == $key) ? 'selected' : '' ?>>
                                                <?= $value ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Categoria permisului de conducere</div>
                                </div>

                                <!-- Data Obținere Permis -->
                                <div class="col-md-6 mb-3">
                                    <label for="license_issue_date" class="form-label required">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Data Obținere
                                    </label>
                                    <input type="date" class="form-control" id="license_issue_date" name="license_issue_date" 
                                           value="<?= old('license_issue_date') ?>" required>
                                    <div class="form-text">Data obținerii permisului</div>
                                </div>

                                <!-- Data Expirare Permis -->
                                <div class="col-md-6 mb-3">
                                    <label for="license_expiry_date" class="form-label required">
                                        <i class="fas fa-calendar-times me-1"></i>
                                        Data Expirare
                                    </label>
                                    <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" 
                                           value="<?= old('license_expiry_date') ?>" required>
                                    <div class="form-text">Data expirării permisului</div>
                                </div>

                                <!-- Autoritatea Emitentă -->
                                <div class="col-md-12 mb-3">
                                    <label for="license_authority" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Autoritatea Emitentă
                                    </label>
                                    <input type="text" class="form-control" id="license_authority" name="license_authority" 
                                           value="<?= old('license_authority') ?>" 
                                           placeholder="ex: Poliția Română - SPCEP București">
                                    <div class="form-text">Instituția care a emis permisul (opțional)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informații Angajare -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-briefcase me-2"></i>
                                Informații Angajare
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Data Angajare -->
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label required">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Data Angajare
                                    </label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                           value="<?= old('hire_date') ?: date('Y-m-d') ?>" required>
                                    <div class="form-text">Data începerii colaborării</div>
                                </div>

                                <!-- Tip Contract -->
                                <div class="col-md-6 mb-3">
                                    <label for="contract_type" class="form-label required">
                                        <i class="fas fa-file-contract me-1"></i>
                                        Tip Contract
                                    </label>
                                    <select class="form-select" id="contract_type" name="contract_type" required>
                                        <option value="">Selectează tipul de contract</option>
                                        <?php foreach ($contractTypes as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= (old('contract_type') == $key) ? 'selected' : '' ?>>
                                                <?= $value ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Tipul contractului de muncă</div>
                                </div>

                                <!-- Salariu -->
                                <div class="col-md-6 mb-3">
                                    <label for="salary" class="form-label">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        Salariu (RON)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="salary" name="salary" 
                                               value="<?= old('salary') ?>" placeholder="0.00">
                                        <span class="input-group-text">RON</span>
                                    </div>
                                    <div class="form-text">Salariul lunar (opțional)</div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label required">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Status
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?= (old('status') == 'active' || !old('status')) ? 'selected' : '' ?>>Activ</option>
                                        <option value="inactive" <?= (old('status') == 'inactive') ? 'selected' : '' ?>>Inactiv</option>
                                        <option value="suspended" <?= (old('status') == 'suspended') ? 'selected' : '' ?>>Suspendat</option>
                                        <option value="terminated" <?= (old('status') == 'terminated') ? 'selected' : '' ?>>Contractul încheiat</option>
                                    </select>
                                    <div class="form-text">Statusul actual al șoferului</div>
                                </div>

                                <!-- Vehicul Asignat -->
                                <div class="col-md-12 mb-3">
                                    <label for="assigned_vehicle_id" class="form-label">
                                        <i class="fas fa-car me-1"></i>
                                        Vehicul Asignat
                                    </label>
                                    <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id">
                                        <option value="">Niciun vehicul asignat</option>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?= $vehicle['id'] ?>" <?= (old('assigned_vehicle_id') == $vehicle['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vehicle['registration_number']) ?> - 
                                                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Vehiculul asignat șoferului (opțional)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documente și Observații -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                Documente și Observații
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Fotografie -->
                                <div class="col-md-6 mb-3">
                                    <label for="photo" class="form-label">
                                        <i class="fas fa-camera me-1"></i>
                                        Fotografie
                                    </label>
                                    <input type="file" class="form-control" id="photo" name="photo" 
                                           accept="image/jpeg,image/jpg,image/png">
                                    <div class="form-text">Fotografia șoferului (max 5MB, format: JPG, PNG)</div>
                                </div>

                                <!-- Copie Permis -->
                                <div class="col-md-6 mb-3">
                                    <label for="license_copy" class="form-label">
                                        <i class="fas fa-file-image me-1"></i>
                                        Copie Permis
                                    </label>
                                    <input type="file" class="form-control" id="license_copy" name="license_copy" 
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text">Copie permis de conducere (max 10MB, format: PDF, JPG, PNG)</div>
                                </div>

                                <!-- Experiență -->
                                <div class="col-md-6 mb-3">
                                    <label for="experience_years" class="form-label">
                                        <i class="fas fa-clock me-1"></i>
                                        Experiență (ani)
                                    </label>
                                    <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                           value="<?= old('experience_years') ?>" min="0" max="50"
                                           placeholder="5">
                                    <div class="form-text">Numărul de ani de experiență în conducere</div>
                                </div>

                                <!-- Contact Urgență -->
                                <div class="col-md-6 mb-3">
                                    <label for="emergency_contact" class="form-label">
                                        <i class="fas fa-phone-alt me-1"></i>
                                        Contact Urgență
                                    </label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                                           value="<?= old('emergency_contact') ?>" 
                                           placeholder="Nume: 0723456789">
                                    <div class="form-text">Persoană de contact în caz de urgență</div>
                                </div>

                                <!-- Observații -->
                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        Observații
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Observații despre șofer, calificări suplimentare, etc..."><?= old('notes') ?></textarea>
                                    <div class="form-text">Informații suplimentare despre șofer (opțional)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Butoane -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>drivers" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Anulează
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvează Șofer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar cu informații -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informații Utile
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-1"></i> Sfaturi:</h6>
                            <ul class="mb-0 small">
                                <li>Completează toate câmpurile obligatorii marcate cu *</li>
                                <li>Verifică CNP-ul pentru corectitudine</li>
                                <li>Adaugă o fotografie pentru identificare</li>
                                <li>Înregistrează contactul de urgență</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-1"></i> Atenție:</h6>
                            <ul class="mb-0 small">
                                <li>Permisele expirate nu permit conducerea</li>
                                <li>Verifică categoria de permis pentru vehicul</li>
                                <li>Păstrează copii ale documentelor</li>
                            </ul>
                        </div>

                        <!-- Categorii de permis frecvente -->
                        <h6><i class="fas fa-id-badge me-1"></i> Categorii Permis:</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-2">
                                <strong>B:</strong> Autoturisme până la 3.5t
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>C:</strong> Camioane peste 3.5t
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>D:</strong> Autobuze cu peste 8 locuri
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>CE:</strong> Camioane cu remorcă
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validare formular
    const form = document.getElementById('addDriverForm');
    const cnpInput = document.getElementById('cnp');
    const birthDateInput = document.getElementById('birth_date');
    const licenseExpiryInput = document.getElementById('license_expiry_date');
    const licenseIssueInput = document.getElementById('license_issue_date');
    
    // Validare CNP
    cnpInput.addEventListener('input', function() {
        const cnp = this.value.replace(/\D/g, ''); // Elimină caracterele non-numerice
        this.value = cnp;
        
        if (cnp.length === 13) {
            if (validateCNP(cnp)) {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                
                // Auto-completează data nașterii din CNP
                const birthDate = extractBirthDateFromCNP(cnp);
                if (birthDate && !birthDateInput.value) {
                    birthDateInput.value = birthDate;
                }
            } else {
                this.setCustomValidity('CNP invalid');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-valid', 'is-invalid');
        }
    });
    
    // Validare date permis
    function validateLicenseDates() {
        const issueDate = new Date(licenseIssueInput.value);
        const expiryDate = new Date(licenseExpiryInput.value);
        const birthDate = new Date(birthDateInput.value);
        
        // Verifică că data obținerii este după naștere + 18 ani
        if (birthDate && issueDate) {
            const minDate = new Date(birthDate);
            minDate.setFullYear(minDate.getFullYear() + 18);
            
            if (issueDate < minDate) {
                licenseIssueInput.setCustomValidity('Data obținerii permisului trebuie să fie după împlinirea vârstei de 18 ani');
            } else {
                licenseIssueInput.setCustomValidity('');
            }
        }
        
        // Verifică că data expirării este după data obținerii
        if (issueDate && expiryDate && expiryDate <= issueDate) {
            licenseExpiryInput.setCustomValidity('Data expirării trebuie să fie după data obținerii');
        } else {
            licenseExpiryInput.setCustomValidity('');
        }
    }
    
    birthDateInput.addEventListener('change', validateLicenseDates);
    licenseIssueInput.addEventListener('change', validateLicenseDates);
    licenseExpiryInput.addEventListener('change', validateLicenseDates);
    
    // Auto-completare data expirării permisului (10 ani pentru categoria B)
    licenseIssueInput.addEventListener('change', function() {
        if (!licenseExpiryInput.value && this.value) {
            const issueDate = new Date(this.value);
            issueDate.setFullYear(issueDate.getFullYear() + 10);
            licenseExpiryInput.value = issueDate.toISOString().split('T')[0];
        }
    });
    
    // Validare fișiere
    document.getElementById('photo').addEventListener('change', function() {
        validateFile(this, 5, ['image/jpeg', 'image/jpg', 'image/png']);
    });
    
    document.getElementById('license_copy').addEventListener('change', function() {
        validateFile(this, 10, ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);
    });
    
    function validateFile(input, maxSizeMB, allowedTypes) {
        const file = input.files[0];
        if (file) {
            const maxSize = maxSizeMB * 1024 * 1024;
            
            if (file.size > maxSize) {
                alert(`Fișierul este prea mare. Dimensiunea maximă permisă este ${maxSizeMB}MB.`);
                input.value = '';
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format de fișier neacceptat. Verifică tipurile permise.');
                input.value = '';
                return;
            }
        }
    }
    
    // Confirmare înainte de submisie
    form.addEventListener('submit', function(e) {
        const confirmMsg = 'Ești sigur că vrei să salvezi acest șofer?';
        if (!confirm(confirmMsg)) {
            e.preventDefault();
        }
    });
});

// Funcție pentru validarea CNP-ului
function validateCNP(cnp) {
    if (cnp.length !== 13) return false;
    
    const checkDigit = parseInt(cnp[12]);
    const weights = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
    let sum = 0;
    
    for (let i = 0; i < 12; i++) {
        sum += parseInt(cnp[i]) * weights[i];
    }
    
    const remainder = sum % 11;
    const expectedCheckDigit = remainder < 10 ? remainder : 1;
    
    return checkDigit === expectedCheckDigit;
}

// Funcție pentru extragerea datei de naștere din CNP
function extractBirthDateFromCNP(cnp) {
    if (cnp.length !== 13) return null;
    
    const sex = parseInt(cnp[0]);
    let year = parseInt(cnp.substr(1, 2));
    const month = parseInt(cnp.substr(3, 2));
    const day = parseInt(cnp.substr(5, 2));
    
    // Determinăm secolul
    if (sex === 1 || sex === 2) {
        year += 1900;
    } else if (sex === 3 || sex === 4) {
        year += 1800;
    } else if (sex === 5 || sex === 6) {
        year += 2000;
    } else {
        return null;
    }
    
    // Validăm data
    if (month < 1 || month > 12 || day < 1 || day > 31) {
        return null;
    }
    
    const date = new Date(year, month - 1, day);
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }
    
    return date.toISOString().split('T')[0];
}
</script>

<?php
require_once ROOT_PATH . '/includes/footer.php';

// Funcție helper pentru old values
function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}
?>
