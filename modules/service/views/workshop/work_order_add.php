<?php
/**
 * View: Adaugă Ordine de Lucru
 * Formular pentru creare ordine nouă în atelier
 */

$vehicles = $vehicles ?? [];
$mechanics = $mechanics ?? [];
$errors = $errors ?? [];
$formData = $formData ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-plus-circle"></i> Ordine de Lucru Nouă</h2>
                <a href="<?= ROUTE_BASE ?>service/workshop" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Înapoi
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

            <form method="POST" id="workOrderForm">
                <div class="row">
                    <!-- Coloana stânga -->
                    <div class="col-md-8">
                        <!-- Informații Vehicul -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-car"></i> Informații Vehicul</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Vehicul <span class="text-danger">*</span></label>
                                        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                                            <option value="">Selectează vehicul...</option>
                                            <?php foreach ($vehicles as $vehicle): ?>
                                                <option value="<?= $vehicle['id'] ?>" 
                                                        data-vin="<?= htmlspecialchars($vehicle['vin']) ?>"
                                                        data-make="<?= htmlspecialchars($vehicle['make']) ?>"
                                                        data-model="<?= htmlspecialchars($vehicle['model']) ?>"
                                                        data-year="<?= $vehicle['year'] ?>"
                                                        data-km="<?= number_format($vehicle['current_km']) ?>"
                                                        <?= ($formData['vehicle_id'] ?? '') == $vehicle['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($vehicle['plate_number']) ?> - 
                                                    <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?> 
                                                    (<?= $vehicle['year'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Kilometraj Intrare <span class="text-danger">*</span></label>
                                        <input type="number" name="entry_km" id="entry_km" class="form-control" 
                                               value="<?= htmlspecialchars($formData['entry_km'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Data/Ora Intrare <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="entry_date" class="form-control" 
                                               value="<?= $formData['entry_date'] ?? date('Y-m-d\TH:i') ?>" required>
                                    </div>
                                </div>
                                <div id="vehicleInfo" class="alert alert-info d-none">
                                    <strong>Info Vehicul:</strong>
                                    <div id="vehicleDetails"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalii Serviciu -->
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Detalii Serviciu</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Descriere Problemă <span class="text-danger">*</span></label>
                                        <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                                        <small class="text-muted">Descrie cât mai detaliat problema raportată</small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Lucrări Solicitate</label>
                                        <textarea name="requested_work" class="form-control" rows="3"><?= htmlspecialchars($formData['requested_work'] ?? '') ?></textarea>
                                        <small class="text-muted">Ex: Înlocuire plăcuțe frână față, verificare suspensie, etc.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prioritate <span class="text-danger">*</span></label>
                                        <select name="priority" class="form-select" required>
                                            <option value="normal" <?= ($formData['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>
                                                <i class="fas fa-minus"></i> Normală
                                            </option>
                                            <option value="high" <?= ($formData['priority'] ?? '') === 'high' ? 'selected' : '' ?>>
                                                <i class="fas fa-arrow-up"></i> Ridicată
                                            </option>
                                            <option value="urgent" <?= ($formData['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>
                                                <i class="fas fa-exclamation-triangle"></i> Urgentă
                                            </option>
                                            <option value="low" <?= ($formData['priority'] ?? '') === 'low' ? 'selected' : '' ?>>
                                                <i class="fas fa-arrow-down"></i> Scăzută
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estimare Ore</label>
                                        <input type="number" name="estimated_hours" class="form-control" 
                                               step="0.5" min="0" 
                                               value="<?= htmlspecialchars($formData['estimated_hours'] ?? '') ?>">
                                        <small class="text-muted">Ore estimate pentru finalizare</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observații -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-comment"></i> Observații Inițiale</h5>
                            </div>
                            <div class="card-body">
                                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                                <small class="text-muted">Note suplimentare, observații din inspecția inițială</small>
                            </div>
                        </div>
                    </div>

                    <!-- Coloana dreaptă -->
                    <div class="col-md-4">
                        <!-- Alocare Mecanic -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-user-cog"></i> Alocare Mecanic</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Mecanic Responsabil</label>
                                    <select name="mechanic_id" id="mechanic_id" class="form-select">
                                        <option value="">Nealocat (Se va aloca mai târziu)</option>
                                        <?php foreach ($mechanics as $mechanic): ?>
                                            <option value="<?= $mechanic['id'] ?>"
                                                    data-workload="<?= $mechanic['active_work_orders'] ?>"
                                                    <?= ($formData['mechanic_id'] ?? '') == $mechanic['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mechanic['name']) ?>
                                                <span class="badge bg-secondary"><?= $mechanic['active_work_orders'] ?> active</span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Mecanic principal pentru această ordine</small>
                                </div>
                                <div id="mechanicInfo" class="alert alert-secondary d-none">
                                    <small><strong>Sarcină curentă:</strong> <span id="mechanicWorkload"></span> ordine active</small>
                                </div>
                            </div>
                        </div>

                        <!-- Estimare Costuri -->
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-calculator"></i> Estimare Costuri</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Manoperă Estimată (RON)</label>
                                    <input type="number" name="estimated_labor_cost" id="estimated_labor_cost" 
                                           class="form-control" step="0.01" min="0"
                                           value="<?= htmlspecialchars($formData['estimated_labor_cost'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Piese Estimate (RON)</label>
                                    <input type="number" name="estimated_parts_cost" id="estimated_parts_cost" 
                                           class="form-control" step="0.01" min="0"
                                           value="<?= htmlspecialchars($formData['estimated_parts_cost'] ?? '') ?>">
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total Estimat:</strong>
                                    <strong id="totalEstimated">0 RON</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Data Livrare -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Livrare Planificată</h5>
                            </div>
                            <div class="card-body">
                                <input type="datetime-local" name="estimated_completion_date" class="form-control"
                                       value="<?= $formData['estimated_completion_date'] ?? '' ?>">
                                <small class="text-muted">Data estimată de finalizare</small>
                            </div>
                        </div>

                        <!-- Butoane -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Creează Ordine de Lucru
                            </button>
                            <a href="<?= ROUTE_BASE ?>service/workshop" class="btn btn-outline-secondary">
                                Anulează
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Actualizare info vehicul
    $('#vehicle_id').on('change', function() {
        const selected = $(this).find(':selected');
        if (selected.val()) {
            const vin = selected.data('vin');
            const make = selected.data('make');
            const model = selected.data('model');
            const year = selected.data('year');
            const km = selected.data('km');
            
            $('#vehicleDetails').html(`
                <div class="row">
                    <div class="col-6"><strong>VIN:</strong> ${vin}</div>
                    <div class="col-6"><strong>An:</strong> ${year}</div>
                    <div class="col-6"><strong>Model:</strong> ${make} ${model}</div>
                    <div class="col-6"><strong>KM Curent:</strong> ${km}</div>
                </div>
            `);
            $('#vehicleInfo').removeClass('d-none');
            
            // Setează kilometrajul de intrare (sugestie)
            if (!$('#entry_km').val()) {
                $('#entry_km').val(selected.data('km').replace(',', ''));
            }
        } else {
            $('#vehicleInfo').addClass('d-none');
        }
    });

    // Actualizare info mecanic
    $('#mechanic_id').on('change', function() {
        const selected = $(this).find(':selected');
        if (selected.val()) {
            const workload = selected.data('workload');
            $('#mechanicWorkload').text(workload);
            $('#mechanicInfo').removeClass('d-none');
        } else {
            $('#mechanicInfo').addClass('d-none');
        }
    });

    // Calcul total estimat
    function updateTotalEstimated() {
        const labor = parseFloat($('#estimated_labor_cost').val()) || 0;
        const parts = parseFloat($('#estimated_parts_cost').val()) || 0;
        const total = labor + parts;
        $('#totalEstimated').text(total.toFixed(0) + ' RON');
    }

    $('#estimated_labor_cost, #estimated_parts_cost').on('input', updateTotalEstimated);
    updateTotalEstimated();

    // Trigger initial pentru vehicul dacă e pre-selectat
    if ($('#vehicle_id').val()) {
        $('#vehicle_id').trigger('change');
    }
});
</script>
