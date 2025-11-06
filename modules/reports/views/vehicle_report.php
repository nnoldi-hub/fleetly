<?php // modules/reports/views/vehicle_report.php (template-only)
// Expect: $vehicle, $reportData, $vehicles, $filters
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
        <h1 class="h3 m-0"><i class="fas fa-car text-primary me-2"></i> Raport vehicul <?= htmlspecialchars($vehicle['license_plate'] ?? '') ?></h1>
        <div>
            <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>reports"><i class="fas fa-arrow-left"></i> Înapoi</a>
            <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>reports/vehicle?<?= http_build_query(array_merge($filters,[ 'export'=>'csv' ])) ?>"><i class="fas fa-download"></i> Export CSV</a>
        </div>
    </div>

            <!-- Selector vehicul și filtre -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= BASE_URL ?>reports/vehicle" class="row g-3">
                        <div class="col-md-4">
                            <label for="vehicle_id" class="form-label">Vehicul <span class="text-danger">*</span></label>
                            <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                <option value="">Selectați vehiculul</option>
                                <?php foreach (($vehicles ?? []) as $opt): ?>
                                    <option value="<?= $opt['id'] ?>" <?= (!empty($filters['vehicle_id']) && (int)$filters['vehicle_id']===(int)$opt['id'])?'selected':'' ?>>
                                        <?= htmlspecialchars(($opt['license_plate'] ?? ($opt['registration_number'] ?? '')) . ' - ' . (($opt['make'] ?? $opt['brand'] ?? '') . ' ' . ($opt['model'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data De La</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Până La</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Generează
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($vehicle)): ?>
            <!-- Informații vehicul -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Informații Vehicul
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Număr de Înmatriculare:</strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Marca și Model:</strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>An Fabricație:</strong></td>
                                    <td><?php echo $vehicle['year'] ?? 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tip Vehicul:</strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['vehicle_type_name'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Kilometraj Actual:</strong></td>
                                    <td><?php echo number_format($vehicle['odometer'] ?? 0, 0, ',', '.'); ?> km</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($vehicle['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($vehicle['status'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>VIN:</strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['vin'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Culoare:</strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['color'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rezumat costuri -->
            <?php if (!empty($reportData['costs'])): ?>
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($reportData['costs']['fuel_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Combustibil</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-gas-pump fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($reportData['costs']['maintenance_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Mentenanță</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-wrench fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($reportData['costs']['insurance_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Asigurări</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($reportData['costs']['total_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Total</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timeline activități -->
            <?php if (!empty($reportData['timeline'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-timeline"></i> Timeline Activități
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($reportData['timeline'] as $entry): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?php echo $entry['type'] == 'fuel' ? 'success' : ($entry['type'] == 'maintenance' ? 'warning' : 'info'); ?>">
                                    <i class="fas fa-<?php echo $entry['type'] == 'fuel' ? 'gas-pump' : ($entry['type'] == 'maintenance' ? 'wrench' : 'shield-alt'); ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="badge bg-<?php echo $entry['type'] == 'fuel' ? 'success' : ($entry['type'] == 'maintenance' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($entry['type']); ?>
                                        </span>
                                        <small class="text-muted"><?php echo date('d.m.Y', strtotime($entry['date'])); ?></small>
                                    </div>
                                    <div class="timeline-body">
                                        <p class="mb-1"><?php echo htmlspecialchars($entry['description']); ?></p>
                                        <?php if ($entry['cost'] > 0): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-money-bill-wave"></i> <?php echo number_format($entry['cost'], 2); ?> RON
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($entry['odometer']): ?>
                                            <small class="text-muted ms-3">
                                                <i class="fas fa-tachometer-alt"></i> <?php echo number_format($entry['odometer'], 0); ?> km
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detalii combustibil -->
            <?php if (!empty($reportData['fuel'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-gas-pump"></i> Detalii Combustibil
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cantitate (L)</th>
                                    <th>Cost (RON)</th>
                                    <th>Preț/L (RON)</th>
                                    <th>Kilometraj</th>
                                    <th>Consum (L/100km)</th>
                                    <th>Stația</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['fuel'] as $fuel): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($fuel['fuel_date'])); ?></td>
                                        <td><?php echo number_format($fuel['liters'], 2); ?> L</td>
                                        <td><?php echo number_format($fuel['cost'], 2); ?> RON</td>
                                        <td><?php echo number_format($fuel['cost'] / $fuel['liters'], 2); ?> RON</td>
                                        <td><?php echo number_format($fuel['odometer'], 0); ?> km</td>
                                        <td>
                                            <?php if ($fuel['consumption'] > 0): ?>
                                                <span class="badge bg-<?php echo ($fuel['consumption'] > 10) ? 'danger' : (($fuel['consumption'] > 7) ? 'warning' : 'success'); ?>">
                                                    <?php echo number_format($fuel['consumption'], 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($fuel['station'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detalii mentenanță -->
            <?php if (!empty($reportData['maintenance'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-wrench"></i> Detalii Mentenanță
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Data Programată</th>
                                    <th>Data Realizării</th>
                                    <th>Tip Mentenanță</th>
                                    <th>Descriere</th>
                                    <th>Furnizor</th>
                                    <th>Cost (RON)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['maintenance'] as $maintenance): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                        <td>
                                            <?php if ($maintenance['completed_date']): ?>
                                                <?php echo date('d.m.Y', strtotime($maintenance['completed_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Necompletat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($maintenance['maintenance_type']); ?></td>
                                        <td><?php echo htmlspecialchars($maintenance['description']); ?></td>
                                        <td><?php echo htmlspecialchars($maintenance['service_provider'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($maintenance['cost'], 2); ?> RON</td>
                                        <td>
                                            <span class="badge bg-<?php echo $maintenance['status'] == 'completed' ? 'success' : ($maintenance['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($maintenance['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detalii asigurări -->
            <?php if (!empty($reportData['insurance'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt"></i> Detalii Asigurări
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Tip Asigurare</th>
                                    <th>Companie</th>
                                    <th>Număr Poliță</th>
                                    <th>Data Început</th>
                                    <th>Data Sfârșit</th>
                                    <th>Primă Anuală (RON)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['insurance'] as $insurance): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $insurance['insurance_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($insurance['company']); ?></td>
                                        <td><?php echo htmlspecialchars($insurance['policy_number']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($insurance['start_date'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($insurance['end_date'])); ?></td>
                                        <td><?php echo number_format($insurance['annual_premium'], 2); ?> RON</td>
                                        <td>
                                            <?php
                                            $endDate = $insurance['end_date'];
                                            $today = date('Y-m-d');
                                            if ($endDate < $today): ?>
                                                <span class="badge bg-danger">Expirat</span>
                                            <?php elseif ($endDate <= date('Y-m-d', strtotime('+30 days'))): ?>
                                                <span class="badge bg-warning">În expirare</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Activ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                <h5>Selectați un vehicul pentru a genera raportul</h5>
                <p>Folosiți formularul de mai sus pentru a selecta vehiculul și perioada dorită.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border-left: 3px solid #dee2e6;
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-body p {
    margin-bottom: 5px;
}
</style>

<script>
function exportReport(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    
    // Deschidem în tab nou pentru download
    window.open(currentUrl.toString(), '_blank');
}

// Validare form
document.querySelector('form').addEventListener('submit', function(e) {
    const vehicleId = document.getElementById('vehicle_id').value;
    const dateFrom = new Date(document.getElementById('date_from').value);
    const dateTo = new Date(document.getElementById('date_to').value);
    
    if (!vehicleId) {
        e.preventDefault();
        alert('Vă rugăm să selectați un vehicul!');
        return false;
    }
    
    if (dateTo < dateFrom) {
        e.preventDefault();
        alert('Data "Până La" trebuie să fie după data "De La"!');
        return false;
    }
});
</script>

<!-- template-only: footer provided by Controller::render() -->
