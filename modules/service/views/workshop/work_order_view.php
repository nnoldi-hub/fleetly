<?php
/**
 * View: Detalii Ordine de Lucru
 * View complet pentru gestionare ordine în atelier
 */

$wo = $wo ?? [];
$parts = $parts ?? [];
$labor = $labor ?? [];
$checklist = $checklist ?? [];
$mechanics = $mechanics ?? [];
$canEdit = $canEdit ?? false;
?>

<div class="container-fluid mt-4">
    <!-- Header cu acțiuni -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-tools"></i> 
                Ordine <?= htmlspecialchars($wo['work_order_number']) ?>
                <?php
                $statusBadges = [
                    'pending' => 'secondary',
                    'in_progress' => 'primary',
                    'waiting_parts' => 'warning',
                    'completed' => 'success',
                    'delivered' => 'info'
                ];
                $badgeClass = $statusBadges[$wo['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $wo['status'])) ?></span>
            </h2>
            <p class="text-muted">
                <i class="fas fa-car"></i> <?= htmlspecialchars($wo['plate_number']) ?> - 
                <?= htmlspecialchars($wo['make'] . ' ' . $wo['model']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>service/workshop" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                    <i class="fas fa-edit"></i> Schimbă Status
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Coloana principală -->
        <div class="col-md-8">
            <!-- Informații Generale -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informații Generale</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Data Intrare:</strong> <?= date('d.m.Y H:i', strtotime($wo['entry_date'])) ?></p>
                            <p><strong>Kilometraj Intrare:</strong> <?= number_format($wo['entry_km']) ?> km</p>
                            <p><strong>Prioritate:</strong> 
                                <?php
                                $priorityBadges = [
                                    'urgent' => ['danger', 'exclamation-triangle'],
                                    'high' => ['warning', 'arrow-up'],
                                    'normal' => ['info', 'minus'],
                                    'low' => ['secondary', 'arrow-down']
                                ];
                                $pInfo = $priorityBadges[$wo['priority']] ?? ['secondary', 'minus'];
                                ?>
                                <span class="badge bg-<?= $pInfo[0] ?>">
                                    <i class="fas fa-<?= $pInfo[1] ?>"></i> <?= ucfirst($wo['priority']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Mecanic:</strong> 
                                <?php if ($wo['mechanic_name']): ?>
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($wo['mechanic_name']) ?>
                                    <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showMechanicModal()">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Nealocat</span>
                                    <?php if ($canEdit): ?>
                                        <button class="btn btn-sm btn-primary" onclick="showMechanicModal()">
                                            <i class="fas fa-plus"></i> Alocă
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                            <p><strong>Ore Estimate:</strong> <?= $wo['estimated_hours'] ? number_format($wo['estimated_hours'], 1) . 'h' : '-' ?></p>
                            <p><strong>Ore Lucrate:</strong> <?= number_format($wo['hours_worked'], 1) ?>h</p>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <strong>Descriere Problemă:</strong>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($wo['description'])) ?></p>
                    </div>
                    <?php if ($wo['requested_work']): ?>
                        <div>
                            <strong>Lucrări Solicitate:</strong>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($wo['requested_work'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Checklist Inspecție -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Checklist Inspecție</h5>
                    <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                        <button class="btn btn-sm btn-success" onclick="saveChecklist()">
                            <i class="fas fa-save"></i> Salvează
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($checklist)): ?>
                        <div class="alert alert-info">
                            Nu există checklist pentru această ordine. 
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-primary" onclick="generateDefaultChecklist()">
                                    <i class="fas fa-magic"></i> Generează Checklist Implicit
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="50%">Element</th>
                                        <th width="20%">Status</th>
                                        <th width="25%">Observații</th>
                                    </tr>
                                </thead>
                                <tbody id="checklistBody">
                                    <?php foreach ($checklist as $idx => $item): ?>
                                        <tr data-item-id="<?= $idx + 1 ?>">
                                            <td><?= $idx + 1 ?></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm checklist-item" 
                                                       value="<?= htmlspecialchars($item['item_name']) ?>"
                                                       <?= $canEdit ? '' : 'readonly' ?>>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm checklist-status" 
                                                        <?= $canEdit ? '' : 'disabled' ?>>
                                                    <option value="ok" <?= $item['status'] === 'ok' ? 'selected' : '' ?>>✓ OK</option>
                                                    <option value="attention" <?= $item['status'] === 'attention' ? 'selected' : '' ?>>⚠ Atenție</option>
                                                    <option value="critical" <?= $item['status'] === 'critical' ? 'selected' : '' ?>>✗ Critic</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm checklist-notes" 
                                                       value="<?= htmlspecialchars($item['notes'] ?? '') ?>"
                                                       <?= $canEdit ? '' : 'readonly' ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Manoperă -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Manoperă (<?= count($labor) ?>)</h5>
                    <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#laborModal">
                            <i class="fas fa-play"></i> Start Lucru
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($labor)): ?>
                        <p class="text-muted">Nu există înregistrări de manoperă</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Mecanic</th>
                                        <th>Descriere</th>
                                        <th>Start</th>
                                        <th>Stop</th>
                                        <th>Ore</th>
                                        <th>Cost (RON)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalLaborHours = 0;
                                    $totalLaborCost = 0;
                                    foreach ($labor as $l): 
                                        $totalLaborHours += $l['hours_worked'];
                                        $totalLaborCost += $l['cost'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($l['mechanic_name']) ?></td>
                                            <td><?= htmlspecialchars($l['description']) ?></td>
                                            <td><small><?= date('d.m H:i', strtotime($l['start_time'])) ?></small></td>
                                            <td>
                                                <?php if ($l['end_time']): ?>
                                                    <small><?= date('d.m H:i', strtotime($l['end_time'])) ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">În lucru</span>
                                                    <?php if ($canEdit): ?>
                                                        <button class="btn btn-xs btn-danger" onclick="stopLabor(<?= $l['id'] ?>)">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= number_format($l['hours_worked'], 2) ?>h</td>
                                            <td><?= number_format($l['cost'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-secondary">
                                        <td colspan="4"><strong>TOTAL</strong></td>
                                        <td><strong><?= number_format($totalLaborHours, 2) ?>h</strong></td>
                                        <td><strong><?= number_format($totalLaborCost, 0) ?> RON</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Piese -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> Piese Utilizate (<?= count($parts) ?>)</h5>
                    <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#partModal">
                            <i class="fas fa-plus"></i> Adaugă Piesă
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($parts)): ?>
                        <p class="text-muted">Nu au fost adăugate piese</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Cod Piesă</th>
                                        <th>Denumire</th>
                                        <th>Cantitate</th>
                                        <th>Preț Unitar</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalPartsCost = 0;
                                    foreach ($parts as $p): 
                                        $totalPartsCost += $p['total_price'];
                                    ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($p['part_number']) ?></code></td>
                                            <td><?= htmlspecialchars($p['part_name']) ?></td>
                                            <td><?= $p['quantity'] ?></td>
                                            <td><?= number_format($p['unit_price'], 2) ?> RON</td>
                                            <td><?= number_format($p['total_price'], 2) ?> RON</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-secondary">
                                        <td colspan="4"><strong>TOTAL</strong></td>
                                        <td><strong><?= number_format($totalPartsCost, 2) ?> RON</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar dreapta -->
        <div class="col-md-4">
            <!-- Sumar Costuri -->
            <div class="card mb-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Sumar Costuri</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Manoperă:</span>
                        <strong><?= number_format($wo['labor_cost'], 0) ?> RON</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Piese:</span>
                        <strong><?= number_format($wo['parts_cost'], 0) ?> RON</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>TOTAL:</strong>
                        <h4 class="text-success mb-0"><?= number_format($wo['total_cost'], 0) ?> RON</h4>
                    </div>
                    <?php if ($wo['estimated_labor_cost'] || $wo['estimated_parts_cost']): ?>
                        <hr>
                        <small class="text-muted">
                            Estimat: <?= number_format($wo['estimated_labor_cost'] + $wo['estimated_parts_cost'], 0) ?> RON
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Date Importante -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Date Importante</h5>
                </div>
                <div class="card-body">
                    <p><strong>Intrare:</strong><br>
                        <small><?= date('d.m.Y H:i', strtotime($wo['entry_date'])) ?></small>
                    </p>
                    <?php if ($wo['estimated_completion_date']): ?>
                        <p><strong>Finalizare Estimată:</strong><br>
                            <small><?= date('d.m.Y H:i', strtotime($wo['estimated_completion_date'])) ?></small>
                        </p>
                    <?php endif; ?>
                    <?php if ($wo['completion_date']): ?>
                        <p><strong>Finalizare Efectivă:</strong><br>
                            <small class="text-success"><?= date('d.m.Y H:i', strtotime($wo['completion_date'])) ?></small>
                        </p>
                    <?php endif; ?>
                    <?php if ($wo['delivery_date']): ?>
                        <p><strong>Livrare:</strong><br>
                            <small class="text-info"><?= date('d.m.Y H:i', strtotime($wo['delivery_date'])) ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Observații -->
            <?php if ($wo['notes']): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comment"></i> Observații</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($wo['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acțiuni Rapide -->
            <?php if ($canEdit && $wo['status'] !== 'delivered'): ?>
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Acțiuni Rapide</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($wo['status'] === 'pending'): ?>
                                <button class="btn btn-primary" onclick="updateStatus('in_progress')">
                                    <i class="fas fa-play"></i> Începe Lucru
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($wo['status'] === 'in_progress'): ?>
                                <button class="btn btn-warning" onclick="updateStatus('waiting_parts')">
                                    <i class="fas fa-pause"></i> Așteptare Piese
                                </button>
                                <button class="btn btn-success" onclick="updateStatus('completed')">
                                    <i class="fas fa-check"></i> Marchează Finalizat
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($wo['status'] === 'waiting_parts'): ?>
                                <button class="btn btn-primary" onclick="updateStatus('in_progress')">
                                    <i class="fas fa-play"></i> Reia Lucru
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($wo['status'] === 'completed'): ?>
                                <button class="btn btn-info" onclick="updateStatus('delivered')">
                                    <i class="fas fa-truck"></i> Marchează Livrat
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adaugă Piesă -->
<div class="modal fade" id="partModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adaugă Piesă</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="partForm">
                    <div class="mb-3">
                        <label class="form-label">Cod Piesă</label>
                        <input type="text" name="part_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Denumire Piesă</label>
                        <input type="text" name="part_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantitate</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preț Unitar (RON)</label>
                        <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" onclick="addPart()">Adaugă</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Start Manoperă -->
<div class="modal fade" id="laborModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Lucru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="laborForm">
                    <div class="mb-3">
                        <label class="form-label">Descriere Lucru</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tarif Orar (RON/h)</label>
                        <input type="number" name="hourly_rate" class="form-control" step="0.01" value="150" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" onclick="startLabor()">Start</button>
            </div>
        </div>
    </div>
</div>

<script>
const workOrderId = <?= $wo['id'] ?>;

// Actualizare status
function updateStatus(newStatus) {
    if (!confirm('Sigur doriți să schimbați statusul?')) return;
    
    $.post('<?= ROUTE_BASE ?>service/workshop/update-status', {
        work_order_id: workOrderId,
        status: newStatus
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}

// Adaugă piesă
function addPart() {
    const formData = $('#partForm').serialize() + '&work_order_id=' + workOrderId;
    
    $.post('<?= ROUTE_BASE ?>service/workshop/add-part', formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}

// Start manoperă
function startLabor() {
    const formData = $('#laborForm').serialize() + '&work_order_id=' + workOrderId;
    
    $.post('<?= ROUTE_BASE ?>service/workshop/start-labor', formData, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}

// Stop manoperă
function stopLabor(laborId) {
    if (!confirm('Sigur doriți să opriți cronometrul?')) return;
    
    $.post('<?= ROUTE_BASE ?>service/workshop/end-labor', {
        labor_id: laborId
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}

// Salvează checklist
function saveChecklist() {
    const items = [];
    $('#checklistBody tr').each(function(index) {
        items.push({
            item_name: $(this).find('.checklist-item').val(),
            status: $(this).find('.checklist-status').val(),
            notes: $(this).find('.checklist-notes').val()
        });
    });
    
    $.post('<?= ROUTE_BASE ?>service/workshop/update-checklist', {
        work_order_id: workOrderId,
        items: JSON.stringify(items)
    }, function(response) {
        if (response.success) {
            alert('Checklist salvat cu succes!');
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}
</script>

<style>
.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
}
</style>
