<?php
// Verificăm dacă utilizatorul este autentificat
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

// Dacă nu avem datele din controller, redirectăm
if (!isset($vehicles)) {
    header('Location: /modules/insurance/?action=add');
    exit;
}

$pageTitle = 'Adaugă Poliță de Asigurare';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php 
            $breadcrumbs = [
                'Acasă' => '/',
                'Asigurări' => '/modules/insurance/',
                'Adaugă Poliță' => ''
            ];
            include __DIR__ . '/../../../includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Adaugă Poliță de Asigurare</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/modules/insurance/" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Înapoi la Listă
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erori găsite:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; unset($_SESSION['errors']); ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informații Poliță</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="insurance-form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="vehicle_id" class="form-label">Vehicul <span class="text-danger">*</span></label>
                                        <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                            <option value="">Selectați vehiculul</option>
                                            <?php foreach ($vehicles as $vehicle): ?>
                                                <option value="<?php echo $vehicle['id']; ?>" 
                                                        <?php echo (old('vehicle_id') == $vehicle['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="insurance_type" class="form-label">Tip Asigurare <span class="text-danger">*</span></label>
                                        <select class="form-select" id="insurance_type" name="insurance_type" required>
                                            <option value="">Selectați tipul</option>
                                            <option value="rca" <?php echo (old('insurance_type') == 'rca') ? 'selected' : ''; ?>>RCA</option>
                                            <option value="casco" <?php echo (old('insurance_type') == 'casco') ? 'selected' : ''; ?>>CASCO</option>
                                            <option value="carte_verde" <?php echo (old('insurance_type') == 'carte_verde') ? 'selected' : ''; ?>>Carte Verde</option>
                                            <option value="cargo" <?php echo (old('insurance_type') == 'cargo') ? 'selected' : ''; ?>>Cargo</option>
                                            <option value="pasageri" <?php echo (old('insurance_type') == 'pasageri') ? 'selected' : ''; ?>>Asigurare Pasageri</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company" class="form-label">Compania de Asigurare <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company" name="company" 
                                               value="<?php echo htmlspecialchars(old('company')); ?>" 
                                               placeholder="Ex: Allianz Țiriac, City Insurance" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="policy_number" class="form-label">Numărul Poliței <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="policy_number" name="policy_number" 
                                               value="<?php echo htmlspecialchars(old('policy_number')); ?>" 
                                               placeholder="Ex: RCA123456789" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Data Început <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo old('start_date'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">Data Sfârșit <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo old('end_date'); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="annual_premium" class="form-label">Prima Anuală (RON) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="annual_premium" name="annual_premium" 
                                                   step="0.01" min="0" value="<?php echo old('annual_premium'); ?>" 
                                                   placeholder="0.00" required>
                                            <span class="input-group-text">RON</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="coverage_amount" class="form-label">Suma Asigurată (RON)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="coverage_amount" name="coverage_amount" 
                                                   step="0.01" min="0" value="<?php echo old('coverage_amount'); ?>" 
                                                   placeholder="0.00">
                                            <span class="input-group-text">RON</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="deductible" class="form-label">Franșiza (RON)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="deductible" name="deductible" 
                                                   step="0.01" min="0" value="<?php echo old('deductible'); ?>" 
                                                   placeholder="0.00">
                                            <span class="input-group-text">RON</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="policy_file" class="form-label">Fișier Poliță</label>
                                        <input type="file" class="form-control" id="policy_file" name="policy_file" 
                                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                        <div class="form-text">Formate acceptate: PDF, JPG, PNG, DOC, DOCX (max 5MB)</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Observații</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Observații despre această poliță..."><?php echo htmlspecialchars(old('notes')); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="/modules/insurance/" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Anulează
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Salvează Polița
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informații Utile</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Sfaturi:</h6>
                                <ul class="mb-0">
                                    <li>Verificați că toate datele sunt corecte</li>
                                    <li>Uploaddați o copie a poliței pentru referință</li>
                                    <li>Notați data de expirare pentru reînnoire</li>
                                    <li>Păstrați documentele originale în siguranță</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Atenție:</h6>
                                <p class="mb-0">Asigurați-vă că polița este validă și plătită la timp pentru a evita amenzi sau probleme legale.</p>
                            </div>

                            <h6>Tipuri de Asigurare:</h6>
                            <ul class="list-unstyled">
                                <li><strong>RCA:</strong> Obligatorie pentru toate vehiculele</li>
                                <li><strong>CASCO:</strong> Acoperă daunele la propriul vehicul</li>
                                <li><strong>Carte Verde:</strong> Pentru călătorii în străinătate</li>
                                <li><strong>Cargo:</strong> Pentru transportul de mărfuri</li>
                                <li><strong>Pasageri:</strong> Pentru transportul de persoane</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Validare formular
document.getElementById('insurance-form').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    
    if (endDate <= startDate) {
        e.preventDefault();
        alert('Data de sfârșit trebuie să fie după data de început!');
        return false;
    }
    
    const fileInput = document.getElementById('policy_file');
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (file.size > maxSize) {
            e.preventDefault();
            alert('Fișierul este prea mare! Dimensiunea maximă este 5MB.');
            return false;
        }
    }
});

// Auto-calculare data sfârșit pentru RCA (1 an)
document.getElementById('insurance_type').addEventListener('change', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (this.value === 'rca' && startDateInput.value) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        endDate.setDate(endDate.getDate() - 1); // cu o zi înainte
        
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
});

document.getElementById('start_date').addEventListener('change', function() {
    const insuranceType = document.getElementById('insurance_type').value;
    const endDateInput = document.getElementById('end_date');
    
    if (insuranceType === 'rca' && this.value) {
        const startDate = new Date(this.value);
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        endDate.setDate(endDate.getDate() - 1);
        
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
});
</script>

<?php 
// Funcție helper pentru old values
function old($key, $default = '') {
    return $_SESSION['old_input'][$key] ?? $default;
}

include __DIR__ . '/../../../includes/footer.php'; 
?>
