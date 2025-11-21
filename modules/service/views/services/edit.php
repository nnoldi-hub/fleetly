<?php
/**
 * View: Editează Service
 * Formular pentru editare service existent
 */

$service = $service ?? [];
$errors = $errors ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-edit"></i> Editează Service: 
                    <span class="text-primary"><?= htmlspecialchars($service['name']) ?></span>
                </h2>
                <div>
                    <a href="<?= ROUTE_BASE ?>/service/services/view/<?= $service['id'] ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> Vizualizare
                    </a>
                    <a href="<?= ROUTE_BASE ?>/service/services" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Înapoi
                    </a>
                </div>
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

            <form method="POST" id="serviceForm">
                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                
                <!-- Tip Service (doar afișare, nu se poate schimba) -->
                <div class="card mb-3">
                    <div class="card-header bg-<?= $service['service_type'] === 'internal' ? 'success' : 'primary' ?> text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-<?= $service['service_type'] === 'internal' ? 'tools' : 'handshake' ?>"></i> 
                            Tip Service
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?= $service['service_type'] === 'internal' ? 'success' : 'primary' ?>">
                            <strong>
                                <?php if ($service['service_type'] === 'internal'): ?>
                                    <i class="fas fa-tools"></i> Service Intern (Atelier Propriu)
                                <?php else: ?>
                                    <i class="fas fa-handshake"></i> Service Extern (Partener)
                                <?php endif; ?>
                            </strong>
                            <br><small>Tipul service-ului nu poate fi modificat după creare</small>
                        </div>
                        <input type="hidden" name="service_type" value="<?= $service['service_type'] ?>">
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
                                       value="<?= htmlspecialchars($service['name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($service['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($service['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Web</label>
                                <input type="url" name="website" class="form-control" 
                                       value="<?= htmlspecialchars($service['website'] ?? '') ?>">
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
                                       value="<?= htmlspecialchars($service['address'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Oraș</label>
                                <input type="text" name="city" class="form-control" 
                                       value="<?= htmlspecialchars($service['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Județ</label>
                                <input type="text" name="state" class="form-control" 
                                       value="<?= htmlspecialchars($service['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cod Poștal</label>
                                <input type="text" name="postal_code" class="form-control" 
                                       value="<?= htmlspecialchars($service['postal_code'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalii Specifice -->
                <?php if ($service['service_type'] === 'external'): ?>
                    <!-- Detalii Service Extern -->
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-briefcase"></i> Detalii Service</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Descriere Servicii</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialități</label>
                                    <input type="text" name="specialties" class="form-control" 
                                           value="<?= htmlspecialchars($service['specialties'] ?? '') ?>">
                                    <small class="text-muted">Mărci specializate, separate prin virgulă</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rating</label>
                                    <select name="rating" class="form-select">
                                        <option value="0">Fără rating</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($service['rating'] ?? 0) == $i ? 'selected' : '' ?>>
                                                <?= str_repeat('⭐', $i) ?> (<?= $i ?>)
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Certificate și Autorizații</label>
                                    <textarea name="certifications" class="form-control" rows="2"><?= htmlspecialchars($service['certifications'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Configurare Atelier Intern -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs"></i> Configurare Atelier</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Capacitate Posturi <span class="text-danger">*</span></label>
                                    <input type="number" name="capacity" class="form-control" 
                                           min="1" value="<?= htmlspecialchars($service['capacity'] ?? '4') ?>" required>
                                    <small class="text-muted">Număr de vehicule simultan</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tarif Orar Manoperă (RON)</label>
                                    <input type="number" name="hourly_labor_rate" class="form-control" 
                                           step="0.01" value="<?= htmlspecialchars($service['hourly_labor_rate'] ?? '150') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Program Lucru</label>
                                    <input type="text" name="working_hours" class="form-control" 
                                           value="<?= htmlspecialchars($service['working_hours'] ?? '') ?>">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Echipamente Disponibile</label>
                                    <textarea name="equipment" class="form-control" rows="3"><?= htmlspecialchars($service['equipment'] ?? '') ?></textarea>
                                    <small class="text-muted">Lista echipamentelor din atelier</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Persoană -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Persoană de Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nume Contact</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?= htmlspecialchars($service['contact_person'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefon Contact</label>
                                <input type="tel" name="contact_phone" class="form-control" 
                                       value="<?= htmlspecialchars($service['contact_phone'] ?? '') ?>">
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
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($service['notes'] ?? '') ?></textarea>
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
                                           <?= $service['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">
                                        Service activ (poate primi lucrări)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Salvează Modificările
                                </button>
                                <a href="<?= ROUTE_BASE ?>/service/services/view/<?= $service['id'] ?>" 
                                   class="btn btn-outline-secondary btn-lg">
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

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
</style>
