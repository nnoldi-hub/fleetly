<?php
// Expect: $vehicles, $documentTypes, $selectedVehicleId, $formData provided by controller
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-file-plus text-primary me-2"></i>
            Adaugă Document Nou
        </h1>
        <a href="<?= BASE_URL ?>documents" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Înapoi la Liste
        </a>
    </div>

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

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Informații Document
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>documents/add" method="POST" enctype="multipart/form-data" id="addDocumentForm">
                            <div class="row">
                                <!-- Tip Document -->
                                <div class="col-md-6 mb-3">
                                    <label for="document_type" class="form-label required">
                                        <i class="fas fa-tags me-1"></i>
                                        Tip Document
                                    </label>
                                    <select class="form-select" id="document_type" name="document_type" required>
                                        <option value="">Selectează tipul documentului</option>
                                    <?php foreach ($documentTypes as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= (($formData['document_type'] ?? '') == $key) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($value) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Selectează tipul documentului din lista predefinită</div>
                                </div>

                                <!-- Vehicul -->
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_id" class="form-label required">
                                        <i class="fas fa-car me-1"></i>
                                        Vehicul
                                    </label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Selectează vehiculul</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>"
                                                <?= ((($formData['vehicle_id'] ?? $selectedVehicleId) == $vehicle['id']) ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($vehicle['registration_number']) ?> - <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Selectează vehiculul asociat cu acest document</div>
                                </div>

                                <!-- Număr Document -->
                                <div class="col-md-6 mb-3">
                                    <label for="document_number" class="form-label required">
                                        <i class="fas fa-hashtag me-1"></i>
                                        Număr Document
                                    </label>
                                    <input type="text" class="form-control" id="document_number" name="document_number" 
                         value="<?= htmlspecialchars($formData['document_number'] ?? '') ?>" required 
                                           placeholder="ex: RCA123456789">
                                    <div class="form-text">Numărul unic al documentului</div>
                                </div>

                                <!-- Furnizor/Emitent -->
                                <div class="col-md-6 mb-3">
                                    <label for="provider" class="form-label required">
                                        <i class="fas fa-building me-1"></i>
                                        Furnizor/Emitent
                                    </label>
                                    <input type="text" class="form-control" id="provider" name="provider" 
                         value="<?= htmlspecialchars($formData['provider'] ?? '') ?>" required 
                                           placeholder="ex: Allianz Țiriac, RAR">
                                    <div class="form-text">Compania sau instituția care a emis documentul</div>
                                </div>

                                <!-- Data Emitere -->
                                <div class="col-md-6 mb-3">
                                    <label for="issue_date" class="form-label required">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Data Emitere
                                    </label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" 
                         value="<?= htmlspecialchars($formData['issue_date'] ?? '') ?>" required>
                                    <div class="form-text">Data când a fost emis documentul</div>
                                </div>

                                <!-- Data Expirare -->
                                <div class="col-md-6 mb-3">
                                    <label for="expiry_date" class="form-label required">
                                        <i class="fas fa-calendar-times me-1"></i>
                                        Data Expirare
                                    </label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                         value="<?= htmlspecialchars($formData['expiry_date'] ?? '') ?>" required>
                                    <div class="form-text">Data când expiră documentul</div>
                                </div>

                                <!-- Cost -->
                                <div class="col-md-6 mb-3">
                                    <label for="cost" class="form-label">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        Cost (RON)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                                               value="<?= htmlspecialchars($formData['cost'] ?? '') ?>" placeholder="0.00">
                                        <span class="input-group-text">RON</span>
                                    </div>
                                    <div class="form-text">Costul documentului (opțional)</div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label required">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Status
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?= (($formData['status'] ?? 'active') == 'active') ? 'selected' : '' ?>>Activ</option>
                                        <option value="expired" <?= (($formData['status'] ?? '') == 'expired') ? 'selected' : '' ?>>Expirat</option>
                                        <option value="suspended" <?= (($formData['status'] ?? '') == 'suspended') ? 'selected' : '' ?>>Suspendat</option>
                                        <option value="cancelled" <?= (($formData['status'] ?? '') == 'cancelled') ? 'selected' : '' ?>>Anulat</option>
                                    </select>
                                    <div class="form-text">Statusul actual al documentului</div>
                                </div>

                                <!-- Notificări -->
                                <div class="col-md-6 mb-3">
                                    <label for="notification_days" class="form-label">
                                        <i class="fas fa-bell me-1"></i>
                                        Notificare cu (zile înainte)
                                    </label>
                     <input type="number" class="form-control" id="reminder_days" name="reminder_days" 
                         value="<?= htmlspecialchars($formData['reminder_days'] ?? '30') ?>" min="1" max="365">
                                    <div class="form-text">Cu câte zile înainte să primești notificare de expirare</div>
                                </div>

                                <!-- Fișier Document -->
                                <div class="col-12 mb-3">
                                    <label for="document_file" class="form-label">
                                        <i class="fas fa-file-upload me-1"></i>
                                        Încarcă Document (PDF, JPG, PNG)
                                    </label>
                                    <input type="file" class="form-control" id="document_file" name="document_file" 
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text">
                                        Opțional: Încarcă o copie a documentului (max 10MB, format: PDF, JPG, PNG)
                                    </div>
                                </div>

                                <!-- Observații -->
                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        Observații
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Observații suplimentare despre document..."><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                                    <div class="form-text">Informații suplimentare despre document (opțional)</div>
                                </div>
                            </div>

                            <!-- Butoane -->
                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>documents" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Anulează
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Salvează Document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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
                                <li>Verifică datele de expirare pentru notificări corecte</li>
                                <li>Încarcă o copie digitală pentru backup</li>
                                <li>Notificările se trimit automat cu numărul de zile specificat</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-1"></i> Atenție:</h6>
                            <ul class="mb-0 small">
                                <li>Documentele expirate pot genera amenzi</li>
                                <li>Verifică periodic statusul documentelor</li>
                                <li>Păstrează copii fizice pentru controale</li>
                            </ul>
                        </div>

                        <!-- Tipuri documente frecvente -->
                        <h6><i class="fas fa-star me-1"></i> Documente Frecvente:</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-2">
                                <strong>ITP:</strong> Valabil 1-2 ani
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>RCA:</strong> Valabil 1 an
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>Vinieta:</strong> Valabilă 1 an
                            </div>
                            <div class="list-group-item px-0 py-2">
                                <strong>Permis:</strong> Valabil 10-15 ani
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
    const form = document.getElementById('addDocumentForm');
    const expiryDate = document.getElementById('expiry_date');
    const issueDate = document.getElementById('issue_date');
    
    // Validare date
    function validateDates() {
        const issue = new Date(issueDate.value);
        const expiry = new Date(expiryDate.value);
        
        if (issue && expiry && expiry <= issue) {
            expiryDate.setCustomValidity('Data expirării trebuie să fie după data emiterii');
        } else {
            expiryDate.setCustomValidity('');
        }
    }
    
    issueDate.addEventListener('change', validateDates);
    expiryDate.addEventListener('change', validateDates);
    
    // Auto-completare bazată pe tipul documentului
    document.getElementById('document_type').addEventListener('change', function() {
        const type = this.value;
        const today = new Date();
        const issueInput = document.getElementById('issue_date');
        const expiryInput = document.getElementById('expiry_date');
        
        // Setează data emiterii la azi dacă nu e setată
        if (!issueInput.value) {
            issueInput.value = today.toISOString().split('T')[0];
        }
        
        // Sugestii pentru data expirării bazate pe tip
        if (!expiryInput.value) {
            const expiry = new Date(today);
            switch(type) {
                case 'RCA':
                case 'VINIETA':
                    expiry.setFullYear(expiry.getFullYear() + 1);
                    break;
                case 'ITP':
                    expiry.setFullYear(expiry.getFullYear() + 1);
                    break;
                case 'PERMIS_CONDUCERE':
                    expiry.setFullYear(expiry.getFullYear() + 10);
                    break;
                case 'CASCO':
                    expiry.setFullYear(expiry.getFullYear() + 1);
                    break;
                default:
                    expiry.setFullYear(expiry.getFullYear() + 1);
            }
            expiryInput.value = expiry.toISOString().split('T')[0];
        }
    });
    
    // Validare fișier
    document.getElementById('document_file').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            
            if (file.size > maxSize) {
                alert('Fișierul este prea mare. Dimensiunea maximă permisă este 10MB.');
                this.value = '';
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format de fișier neacceptat. Folosește PDF, JPG sau PNG.');
                this.value = '';
                return;
            }
        }
    });
    
    // Confirmare înainte de submisie
    form.addEventListener('submit', function(e) {
        const confirmMsg = 'Ești sigur că vrei să salvezi acest document?';
        if (!confirm(confirmMsg)) {
            e.preventDefault();
        }
    });
});
</script>
<!-- View template only: header/footer are included by Controller::render() -->
