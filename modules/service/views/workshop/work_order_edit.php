<?php
/**
 * View: Editare Ordine de Lucru
 */

$workOrder = $workOrder ?? [];
$mechanics = $mechanics ?? [];
$errors = $errors ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-edit"></i> Editare Ordine #<?= htmlspecialchars($workOrder['work_order_number']) ?></h2>
                <a href="<?= ROUTE_BASE ?>service/workshop/view/<?= $workOrder['id'] ?>" class="btn btn-secondary">
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

            <form method="POST">
                <div class="row">
                    <!-- Coloana stânga -->
                    <div class="col-md-8">
                        <!-- Informații Vehicul (Read-only) -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-car"></i> Vehicul</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Vehicul:</strong> <?= htmlspecialchars($workOrder['plate_number']) ?> - <?= htmlspecialchars($workOrder['make'] . ' ' . $workOrder['model']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>VIN:</strong> <?= htmlspecialchars($workOrder['vin']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalii Serviciu -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Detalii Lucrare</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Descriere Lucrare</label>
                                        <textarea name="work_description" class="form-control" rows="4"><?= htmlspecialchars($workOrder['work_description'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Diagnostic / Constatări</label>
                                        <textarea name="diagnosis" class="form-control" rows="4"><?= htmlspecialchars($workOrder['diagnosis'] ?? '') ?></textarea>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prioritate</label>
                                        <select name="priority" class="form-select">
                                            <option value="normal" <?= ($workOrder['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>Normală</option>
                                            <option value="high" <?= ($workOrder['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Ridicată</option>
                                            <option value="urgent" <?= ($workOrder['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgentă</option>
                                            <option value="low" <?= ($workOrder['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Scăzută</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?= ($workOrder['status'] ?? '') === 'pending' ? 'selected' : '' ?>>În așteptare</option>
                                            <option value="in_progress" <?= ($workOrder['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>În lucru</option>
                                            <option value="completed" <?= ($workOrder['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Finalizat</option>
                                            <option value="cancelled" <?= ($workOrder['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Anulat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Note Interne -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Note Interne</h5>
                            </div>
                            <div class="card-body">
                                <textarea name="internal_notes" class="form-control" rows="3"><?= htmlspecialchars($workOrder['internal_notes'] ?? '') ?></textarea>
                                <small class="text-muted">Vizibile doar pentru personalul service-ului</small>
                            </div>
                        </div>
                    </div>

                    <!-- Coloana dreaptă -->
                    <div class="col-md-4">
                        <!-- Alocare Mecanic -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-user-cog"></i> Mecanic Alocat</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Mecanic</label>
                                    <select name="assigned_mechanic_id" class="form-select">
                                        <option value="">-- Nealocat --</option>
                                        <?php foreach ($mechanics as $mechanic): ?>
                                            <option value="<?= $mechanic['id'] ?>" <?= ($workOrder['assigned_mechanic_id'] ?? '') == $mechanic['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mechanic['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Estimări -->
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-clock"></i> Estimări</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Ore Estimate</label>
                                    <input type="number" name="estimated_hours" class="form-control" step="0.5" value="<?= htmlspecialchars($workOrder['estimated_hours'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data Finalizare Estimată</label>
                                    <input type="datetime-local" name="estimated_completion" class="form-control" value="<?= $workOrder['estimated_completion'] ? date('Y-m-d\TH:i', strtotime($workOrder['estimated_completion'])) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Butoane -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Salvează Modificările
                            </button>
                            <a href="<?= ROUTE_BASE ?>service/workshop/view/<?= $workOrder['id'] ?>" class="btn btn-outline-secondary">
                                Anulează
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
