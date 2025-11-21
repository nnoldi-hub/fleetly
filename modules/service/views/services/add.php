<?php
/**
 * View: Adaugă Service
 * Formular pentru adăugare service extern sau intern
 */

$errors = $errors ?? [];
$formData = $formData ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-plus-circle"></i> Service Nou</h2>
                <a href="<?= ROUTE_BASE ?>/service/services" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Înapoi
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erori de validare:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="serviceForm">
                <!-- Tip Service -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-layer-group"></i> Tip Service</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tip <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="service_type" id="typeExternal" 
                                           value="external" <?= ($formData['service_type'] ?? 'external') === 'external' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary" for="typeExternal">
                                        <i class="fas fa-handshake"></i> Service Extern (Partener)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="service_type" id="typeInternal" 
                                           value="internal" <?= ($formData['service_type'] ?? '') === 'internal' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="typeInternal">
                                        <i class="fas fa-tools"></i> Service Intern (Atelier Propriu)
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Service extern = partener cu care lucrați | Service intern = atelierul companiei dvs.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informații Generale -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informații Generale</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nume Service <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Web</label>
                                <input type="url" name="website" class="form-control" 
                                       placeholder="https://..." 
                                       value="<?= htmlspecialchars($formData['website'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adresă -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Adresă</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Adresă Completă</label>
                                <input type="text" name="address" class="form-control" 
                                       value="<?= htmlspecialchars($formData['address'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Oraș</label>
                                <input type="text" name="city" class="form-control" 
                                       value="<?= htmlspecialchars($formData['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Județ</label>
                                <input type="text" name="state" class="form-control" 
                                       value="<?= htmlspecialchars($formData['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cod Poștal</label>
                                <input type="text" name="postal_code" class="form-control" 
                                       value="<?= htmlspecialchars($formData['postal_code'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalii Service (Doar pentru externe) -->
                <div class="card mb-3" id="externalDetails">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-briefcase"></i> Detalii Service</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descriere Servicii</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                                <small class="text-muted">Descrieți tipurile de servicii oferite (revizii, reparații, diagnosticare, etc.)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialități</label>
                                <input type="text" name="specialties" class="form-control" 
                                       placeholder="Ex: Audi, BMW, Mercedes"
                                       value="<?= htmlspecialchars($formData['specialties'] ?? '') ?>">
                                <small class="text-muted">Mărci specializate, separate prin virgulă</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rating</label>
                                <select name="rating" class="form-select">
                                    <option value="0">Fără rating</option>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($formData['rating'] ?? 0) == $i ? 'selected' : '' ?>>
                                            <?= str_repeat('⭐', $i) ?> (<?= $i ?>)
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Certificate și Autorizații</label>
                                <textarea name="certifications" class="form-control" rows="2"><?= htmlspecialchars($formData['certifications'] ?? '') ?></textarea>
                                <small class="text-muted">Certificate RAR, ARR, ISO, etc.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configurare Atelier Intern (Doar pentru interne) -->
                <div class="card mb-3 d-none" id="internalDetails">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Configurare Atelier</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacitate Posturi <span class="text-danger">*</span></label>
                                <input type="number" name="capacity" class="form-control" 
                                       min="1" value="<?= htmlspecialchars($formData['capacity'] ?? '4') ?>">
                                <small class="text-muted">Număr de vehicule simultan</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tarif Orar Manoperă (RON)</label>
                                <input type="number" name="hourly_labor_rate" class="form-control" 
                                       step="0.01" value="<?= htmlspecialchars($formData['hourly_labor_rate'] ?? '150') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Program Lucru</label>
                                <input type="text" name="working_hours" class="form-control" 
                                       placeholder="Ex: L-V 08:00-17:00"
                                       value="<?= htmlspecialchars($formData['working_hours'] ?? '') ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Echipamente Disponibile</label>
                                <textarea name="equipment" class="form-control" rows="3"><?= htmlspecialchars($formData['equipment'] ?? '') ?></textarea>
                                <small class="text-muted">Lista echipamentelor din atelier (elevator, geometrie, aparat diagnză, etc.)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Persoană -->
                <div class="card mb-3" id="contactDetails">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Persoană de Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nume Contact</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?= htmlspecialchars($formData['contact_person'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon Contact</label>
                                <input type="tel" name="contact_phone" class="form-control" 
                                       value="<?= htmlspecialchars($formData['contact_phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observații -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Observații</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                        <small class="text-muted">Note interne, termeni colaborare, etc.</small>
                    </div>
                </div>

                <!-- Butoane -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" 
                                           id="isActive" value="1" 
                                           <?= ($formData['is_active'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">
                                        Service activ (poate primi lucrări)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Salvează Service
                                </button>
                                <a href="<?= ROUTE_BASE ?>/service/services" class="btn btn-outline-secondary btn-lg">
                                    Anulează
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle între external și internal
    $('input[name="service_type"]').on('change', function() {
        const type = $(this).val();
        
        if (type === 'external') {
            $('#externalDetails').removeClass('d-none');
            $('#internalDetails').addClass('d-none');
            // Câmpurile externe devin required
            $('#externalDetails').find('input, textarea, select').prop('required', false);
        } else {
            $('#externalDetails').addClass('d-none');
            $('#internalDetails').removeClass('d-none');
            // Câmpurile interne devin required
            $('#internalDetails input[name="capacity"]').prop('required', true);
        }
    });
    
    // Trigger inițial
    $('input[name="service_type"]:checked').trigger('change');
});
</script>

<style>
.btn-check:checked + .btn-outline-primary {
    background-color: #0d6efd;
    color: white;
}

.btn-check:checked + .btn-outline-success {
    background-color: #198754;
    color: white;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
</style>
