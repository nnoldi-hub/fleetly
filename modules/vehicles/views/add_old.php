<?php
// modules/vehicles/views/add.php
$pageTitle = 'Adaugă Vehicul Nou';

// Obținem tipurile de vehicule pentru dropdown
$vehicleTypeModel = new VehicleType();
$vehicleTypes = method_exists($vehicleTypeModel, 'findAll') 
    ? $vehicleTypeModel->findAll() 
    : (method_exists($vehicleTypeModel, 'getAllVehicleTypes') 
        ? $vehicleTypeModel->getAllVehicleTypes() 
        : []); // fallback to empty array if neither method exists

// Preluăm datele din sesiune dacă există erori
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
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>vehicles">Vehicule</a></li>
                <li class="breadcrumb-item active">Adaugă Vehicul</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus-circle text-primary me-2"></i>
                Adaugă Vehicul Nou
            </h1>
            <a href="<?= BASE_URL ?>vehicles" class="btn btn-outline-secondary">
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

        <!-- Formular Adăugare Vehicul -->
        <div class="row">
            <div class="col-lg-8">
                <form action="<?= BASE_URL ?>vehicles/add" method="POST" enctype="multipart/form-data" id="addVehicleForm">
                    
                    <!-- Informații Generale -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-car me-2"></i>
                                Informații Generale
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Număr Înmatriculare -->
                                <div class="col-md-6 mb-3">
                                    <label for="registration_number" class="form-label required">
                                        <i class="fas fa-id-card me-1"></i>
                                        Numărul de Înmatriculare
                                    </label>
                                    <input type="text" class="form-control" id="registration_number" name="registration_number" 
                                           value="<?= old('registration_number') ?>" required 
                                           placeholder="ex: B-123-ABC">
                                    <div class="form-text">Numărul de înmatriculare al vehiculului</div>
                                </div>

                                <!-- VIN -->
                                <div class="col-md-6 mb-3">
                                    <label for="vin_number" class="form-label">
                                        <i class="fas fa-barcode me-1"></i>
                                        Numărul VIN
                                    </label>
                                    <input type="text" class="form-control" id="vin_number" name="vin_number" 
                                           value="<?= old('vin_number') ?>" 
                                           placeholder="ex: 1HGBH41JXMN109186" maxlength="17">
                                    <div class="form-text">Numărul de identificare al vehiculului (opțional)</div>
                                </div>

                                <!-- Marca -->
                                <div class="col-md-6 mb-3">
                                    <label for="brand" class="form-label required">
                                        <i class="fas fa-industry me-1"></i>
                                        Marca
                                    </label>
                                    <input type="text" class="form-control" id="brand" name="brand" 
                                           value="<?= old('brand') ?>" required 
                                           placeholder="ex: Volkswagen">
                                    <div class="form-text">Marca vehiculului</div>
                                </div>

                                <!-- Model -->
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label required">
                                        <i class="fas fa-car-side me-1"></i>
                                        Model
                                    </label>
                                    <input type="text" class="form-control" id="model" name="model" 
                                           value="<?= old('model') ?>" required 
                                           placeholder="ex: Passat">
                                    <div class="form-text">Modelul vehiculului</div>
                                </div>

                                <!-- An Fabricație -->
                                <div class="col-md-4 mb-3">
                                    <label for="year" class="form-label required">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Anul Fabricației
                                    </label>
                                    <input type="number" class="form-control" id="year" name="year" 
                                           value="<?= old('year') ?>" required 
                                           min="1900" max="<?= date('Y') + 1 ?>" 
                                           placeholder="<?= date('Y') ?>">
                                    <div class="form-text">Anul fabricației vehiculului</div>
                                </div>

                                <!-- Tip Vehicul -->
                                <div class="col-md-4 mb-3">
                                    <label for="vehicle_type_id" class="form-label required">
                                        <i class="fas fa-tags me-1"></i>
                                        Tip Vehicul
                                    </label>
                                    <select class="form-select" id="vehicle_type_id" name="vehicle_type_id" required>
                                        <option value="">Selectează tipul</option>
                                        <?php foreach ($vehicleTypes as $type): ?>
                                            <option value="<?= $type['id'] ?>" <?= (old('vehicle_type_id') == $type['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($type['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Categoria vehiculului</div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label required">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Status
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?= (old('status') == 'active' || !old('status')) ? 'selected' : '' ?>>Activ</option>
                                        <option value="inactive" <?= (old('status') == 'inactive') ? 'selected' : '' ?>>Inactiv</option>
                                        <option value="maintenance" <?= (old('status') == 'maintenance') ? 'selected' : '' ?>>În service</option>
                                        <option value="sold" <?= (old('status') == 'sold') ? 'selected' : '' ?>>Vândut</option>
                                    </select>
                                    <div class="form-text">Statusul actual al vehiculului</div>
                                </div>

                                <!-- Culoare -->
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">
                                        <i class="fas fa-palette me-1"></i>
                                        Culoare
                                    </label>
                                    <input type="text" class="form-control" id="color" name="color" 
                                           value="<?= old('color') ?>" 
                                           placeholder="ex: Albastru">
                                    <div class="form-text">Culoarea vehiculului (opțional)</div>
                                </div>

                                <!-- Combustibil -->
                                <div class="col-md-6 mb-3">
                                    <label for="fuel_type" class="form-label required">
                                        <i class="fas fa-gas-pump me-1"></i>
                                        Tip Combustibil
                                    </label>
                                    <select class="form-select" id="fuel_type" name="fuel_type" required>
                                        <option value="">Selectează combustibilul</option>
                                        <option value="benzina" <?= (old('fuel_type') == 'benzina') ? 'selected' : '' ?>>Benzină</option>
                                        <option value="motorina" <?= (old('fuel_type') == 'motorina') ? 'selected' : '' ?>>Motorină</option>
                                        <option value="hibrid" <?= (old('fuel_type') == 'hibrid') ? 'selected' : '' ?>>Hibrid</option>
                                        <option value="electric" <?= (old('fuel_type') == 'electric') ? 'selected' : '' ?>>Electric</option>
                                        <option value="gpl" <?= (old('fuel_type') == 'gpl') ? 'selected' : '' ?>>GPL</option>
                                    </select>
                                    <div class="form-text">Tipul de combustibil</div>
                                </div>
                            </div>
                        </div>
                    <!-- Informații Tehnice -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>
                                Informații Tehnice
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Kilometraj -->
                                <div class="col-md-6 mb-3">
                                    <label for="mileage" class="form-label">
                                        <i class="fas fa-tachometer-alt me-1"></i>
                                        Kilometraj
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="mileage" name="mileage" 
                                               value="<?= old('mileage') ?>" min="0" placeholder="0">
                                        <span class="input-group-text">km</span>
                                    </div>
                                    <div class="form-text">Kilometrajul actual al vehiculului</div>
                                </div>

                                <!-- Capacitate Motor -->
                                <div class="col-md-6 mb-3">
                                    <label for="engine_capacity" class="form-label">
                                        <i class="fas fa-tachometer-alt me-1"></i>
                                        Capacitate Motor
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" id="engine_capacity" name="engine_capacity" 
                                               value="<?= old('engine_capacity') ?>" min="0" placeholder="1.6">
                                        <span class="input-group-text">L</span>
                                    </div>
                                    <div class="form-text">Capacitatea motorului în litri</div>
                                </div>

                                <!-- Putere -->
                                <div class="col-md-6 mb-3">
                                    <label for="power" class="form-label">
                                        <i class="fas fa-bolt me-1"></i>
                                        Putere
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="power" name="power" 
                                               value="<?= old('power') ?>" min="0" placeholder="150">
                                        <span class="input-group-text">CP</span>
                                    </div>
                                    <div class="form-text">Puterea motorului în cai putere</div>
                                </div>

                                <!-- Transmisie -->
                                <div class="col-md-6 mb-3">
                                    <label for="transmission" class="form-label">
                                        <i class="fas fa-cog me-1"></i>
                                        Transmisie
                                    </label>
                                    <select class="form-select" id="transmission" name="transmission">
                                        <option value="">Selectează transmisia</option>
                                        <option value="manual" <?= (old('transmission') == 'manual') ? 'selected' : '' ?>>Manuală</option>
                                        <option value="automat" <?= (old('transmission') == 'automat') ? 'selected' : '' ?>>Automată</option>
                                        <option value="semi-automat" <?= (old('transmission') == 'semi-automat') ? 'selected' : '' ?>>Semi-automată</option>
                                    </select>
                                    <div class="form-text">Tipul de transmisie</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informații Financiare -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Informații Financiare
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Preț Achiziție -->
                                <div class="col-md-6 mb-3">
                                    <label for="purchase_price" class="form-label">
                                        <i class="fas fa-shopping-cart me-1"></i>
                                        Preț Achiziție
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                                               value="<?= old('purchase_price') ?>" min="0" placeholder="25000.00">
                                        <span class="input-group-text">RON</span>
                                    </div>
                                    <div class="form-text">Prețul de achiziție al vehiculului</div>
                                </div>

                                <!-- Data Achiziție -->
                                <div class="col-md-6 mb-3">
                                    <label for="purchase_date" class="form-label">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Data Achiziție
                                    </label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                           value="<?= old('purchase_date') ?>">
                                    <div class="form-text">Data achiziționării vehiculului</div>
                                </div>

                                <!-- Valoare Curentă -->
                                <div class="col-md-6 mb-3">
                                    <label for="current_value" class="form-label">
                                        <i class="fas fa-chart-line me-1"></i>
                                        Valoare Curentă
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="current_value" name="current_value" 
                                               value="<?= old('current_value') ?>" min="0" placeholder="20000.00">
                                        <span class="input-group-text">RON</span>
                                    </div>
                                    <div class="form-text">Valoarea estimată curentă</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Butoane -->
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>vehicles" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Anulează
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvează Vehicul
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar cu informații -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Informații Utile
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-1"></i> Sfaturi:</h6>
                            <ul class="mb-0">
                                <li>Completează toate câmpurile obligatorii marcate cu *</li>
                                <li>Verifică numărul de înmatriculare să fie corect</li>
                                <li>Adaugă o fotografie pentru identificare mai ușoară</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-1"></i> Atenție:</h6>
                            <ul class="mb-0">
                                <li>Numărul VIN trebuie să aibă exact 17 caractere</li>
                                <li>Verifică tipul de combustibil să fie corect</li>
                                <li>Statusul influențează disponibilitatea vehiculului</li>
                            </ul>
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
    const form = document.getElementById('addVehicleForm');
    
    // Handler pentru submit
    form.addEventListener('submit', function(e) {
        console.log('Form submitted');
        
        // Verifică câmpurile obligatorii
        const requiredFields = ['registration_number', 'brand', 'model', 'year', 'vehicle_type_id', 'fuel_type'];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field || !field.value.trim()) {
                console.error('Required field missing: ' + fieldName);
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Vă rugăm să completați toate câmpurile obligatorii marcate cu *');
            return false;
        }
        
        console.log('Form validation passed, submitting...');
    });
    
    // Validare VIN (17 caractere)
    const vinInput = document.getElementById('vin_number');
    if (vinInput) {
        vinInput.addEventListener('input', function() {
            const vin = this.value.toUpperCase();
            this.value = vin;
            
            if (vin.length > 0 && vin.length !== 17) {
                this.setCustomValidity('VIN-ul trebuie să aibă exact 17 caractere');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Auto-calculare valoare curentă bazată pe an și preț achiziție
    const yearInput = document.getElementById('year');
    const purchasePriceInput = document.getElementById('purchase_price');
    const currentValueInput = document.getElementById('current_value');
    
    function calculateCurrentValue() {
        if (!yearInput || !purchasePriceInput || !currentValueInput) return;
        
        const year = parseInt(yearInput.value);
        const purchasePrice = parseFloat(purchasePriceInput.value);
        
        if (year && purchasePrice) {
            const currentYear = new Date().getFullYear();
            const age = currentYear - year;
            const depreciation = Math.min(age * 0.1, 0.7); // max 70% depreciation
            const currentValue = purchasePrice * (1 - depreciation);
            
            if (currentValueInput.value === '' || confirm('Calculez automat valoarea curentă?')) {
                currentValueInput.value = currentValue.toFixed(2);
            }
        }
    }
    
    if (yearInput) yearInput.addEventListener('blur', calculateCurrentValue);
    if (purchasePriceInput) purchasePriceInput.addEventListener('blur', calculateCurrentValue);
});</script>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="purchase_date" class="form-label">Data Achiziției</label>
                                            <input type="date" 
                                                   class="form-control <?php echo !empty($errors['purchase_date']) ? 'is-invalid' : ''; ?>" 
                                                   id="purchase_date" 
                                                   name="purchase_date" 
                                                   value="<?php echo htmlspecialchars($formData['purchase_date'] ?? ''); ?>">
                                            <?php if (!empty($errors['purchase_date'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($errors['purchase_date']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="purchase_price" class="form-label">Prețul de Achiziție (RON)</label>
                                            <input type="number" 
                                                   class="form-control <?php echo !empty($errors['purchase_price']) ? 'is-invalid' : ''; ?>" 
                                                   id="purchase_price" 
                                                   name="purchase_price" 
                                                   value="<?php echo htmlspecialchars($formData['purchase_price'] ?? ''); ?>"
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            <?php if (!empty($errors['purchase_price'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($errors['purchase_price']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="current_mileage" class="form-label">Kilometrajul Curent</label>
                                            <input type="number" 
                                                   class="form-control <?php echo !empty($errors['current_mileage']) ? 'is-invalid' : ''; ?>" 
                                                   id="current_mileage" 
                                                   name="current_mileage" 
                                                   value="<?php echo htmlspecialchars($formData['current_mileage'] ?? '0'); ?>"
                                                   min="0"
                                                   placeholder="0">
                                            <?php if (!empty($errors['current_mileage'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo htmlspecialchars($errors['current_mileage']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-text">Kilometrajul în km</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="/modules/vehicles/" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Anulează
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvează Vehicul
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Informații Utile
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb"></i> Sfaturi:</h6>
                                <ul class="mb-0">
                                    <li>Numărul de înmatriculare trebuie să fie unic</li>
                                    <li>VIN-ul este opțional dar recomandat pentru identificarea precisă</li>
                                    <li>Selectați tipul corect de vehicul pentru statistici precise</li>
                                    <li>Introduceți kilometrajul curent pentru urmărirea uzurii</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Atenție:</h6>
                                <p class="mb-0">
                                    După salvarea vehiculului, veți putea adăuga documente, 
                                    programa întrețineri și urmări consumul de combustibil.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags"></i> Tipuri de Vehicule Disponibile
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($vehicleTypes)): ?>
                                <div class="row">
                                    <?php foreach ($vehicleTypes as $type): ?>
                                        <div class="col-12 mb-2">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Nu există tipuri de vehicule definite.</p>
                                <a href="/modules/vehicles/types/add" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus"></i> Adaugă Tip Vehicul
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Validare formular
document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validare număr înmatriculare
    const registrationNumber = document.getElementById('registration_number').value.trim();
    if (registrationNumber.length < 3) {
        isValid = false;
        showFieldError('registration_number', 'Numărul de înmatriculare trebuie să aibă cel puțin 3 caractere');
    }
    
    // Validare an
    const year = parseInt(document.getElementById('year').value);
    const currentYear = new Date().getFullYear();
    if (year < 1900 || year > currentYear + 1) {
        isValid = false;
        showFieldError('year', `Anul trebuie să fie între 1900 și ${currentYear + 1}`);
    }
    
    // Validare tip vehicul
    const vehicleType = document.getElementById('vehicle_type_id').value;
    if (!vehicleType) {
        isValid = false;
        showFieldError('vehicle_type_id', 'Vă rugăm să selectați tipul vehiculului');
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add('is-invalid');
    
    // Elimină feedback-ul existent
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Adaugă noul feedback
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    field.parentNode.appendChild(feedback);
}

// Eliminare erorilor la modificarea câmpurilor
</script>