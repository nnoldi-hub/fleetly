<?php // modules/reports/views/fleet_report.php (template-only)
// Expect: $reportData, $vehicles, $filters
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
        <h1 class="h3 m-0"><i class="fas fa-car-side text-primary me-2"></i> Raport flotă</h1>
        <div>
            <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>reports"><i class="fas fa-arrow-left"></i> Înapoi</a>
            <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>reports/fleet?<?= http_build_query(array_merge($filters,[ 'export'=>'csv' ])) ?>"><i class="fas fa-download"></i> Export CSV</a>
        </div>
    </div>

            <!-- Filtre -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= BASE_URL ?>reports/fleet" class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data De La</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($filters['date_from']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Până La</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($filters['date_to']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="vehicle_id" class="form-label">Vehicul (Opțional)</label>
                            <select class="form-select" id="vehicle_id" name="vehicle_id">
                                <option value="">Toate vehiculele</option>
                                <?php foreach (($vehicles ?? []) as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= (!empty($filters['vehicle_id']) && (int)$filters['vehicle_id']===(int)$v['id'])?'selected':'' ?>>
                                        <?= htmlspecialchars(($v['license_plate'] ?? ($v['registration_number'] ?? '')) . ' - ' . (($v['make'] ?? $v['brand'] ?? '') . ' ' . ($v['model'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="report_type" class="form-label">Tip Raport</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <?php $rt=$filters['report_type']??'summary'; ?>
                                <option value="summary" <?= $rt==='summary'?'selected':'' ?>>Sumar</option>
                                <option value="detailed" <?= $rt==='detailed'?'selected':'' ?>>Detaliat</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistici generale -->
            <?php if (!empty($reportData['summary'])): ?>
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $reportData['summary']['total_vehicles'] ?? 0; ?></div>
                                    <div>Vehicule</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-car fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($reportData['summary']['total_fuel_liters'] ?? 0, 0); ?> L</div>
                                    <div>Combustibil Consumat</div>
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
                                    <div class="h4 mb-0"><?php echo number_format($reportData['summary']['total_fuel_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Combustibil</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo number_format(($reportData['summary']['total_fuel_cost'] + $reportData['summary']['total_maintenance_cost'] + $reportData['summary']['total_insurance_cost']) ?? 0, 0); ?> RON</div>
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

            <!-- Tendințe -->
            <?php if (!empty($reportData['trends'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Tendințe și Comparații
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6>Cost Combustibil</h6>
                                <div class="h4 <?php echo ($reportData['trends']['changes']['fuel_cost_change'] >= 0) ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo ($reportData['trends']['changes']['fuel_cost_change'] >= 0) ? '+' : ''; ?><?php echo $reportData['trends']['changes']['fuel_cost_change']; ?>%
                                </div>
                                <small class="text-muted">vs perioada anterioară</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6>Cost Mentenanță</h6>
                                <div class="h4 <?php echo ($reportData['trends']['changes']['maintenance_cost_change'] >= 0) ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo ($reportData['trends']['changes']['maintenance_cost_change'] >= 0) ? '+' : ''; ?><?php echo $reportData['trends']['changes']['maintenance_cost_change']; ?>%
                                </div>
                                <small class="text-muted">vs perioada anterioară</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6>Cost Total</h6>
                                <div class="h4 <?php echo ($reportData['trends']['changes']['total_cost_change'] >= 0) ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo ($reportData['trends']['changes']['total_cost_change'] >= 0) ? '+' : ''; ?><?php echo $reportData['trends']['changes']['total_cost_change']; ?>%
                                </div>
                                <small class="text-muted">vs perioada anterioară</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detalii pe vehicule -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Detalii pe Vehicule
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Tip</th>
                                    <th>Combustibil (L)</th>
                                    <th>Cost Combustibil</th>
                                    <th>Cost Mentenanță</th>
                                    <th>Cost Asigurări</th>
                                    <th>Consum Mediu</th>
                                    <th>Cost Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reportData['vehicles'])): ?>
                                    <?php foreach ($reportData['vehicles'] as $vehicle): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>
                                                    <?php if ($vehicle['year']): ?>
                                                        (<?php echo $vehicle['year']; ?>)
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($vehicle['vehicle_type'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong><?php echo number_format($vehicle['fuel_consumed'] ?? 0, 2); ?> L</strong><br>
                                                <small class="text-muted"><?php echo $vehicle['fuel_records_count'] ?? 0; ?> înregistrări</small>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($vehicle['fuel_cost'] ?? 0, 2); ?> RON</strong>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($vehicle['maintenance_cost'] ?? 0, 2); ?> RON</strong><br>
                                                <small class="text-muted"><?php echo $vehicle['maintenance_records_count'] ?? 0; ?> intervenții</small>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($vehicle['insurance_cost'] ?? 0, 2); ?> RON</strong>
                                            </td>
                                            <td>
                                                <?php if ($vehicle['avg_consumption'] > 0): ?>
                                                    <span class="badge bg-<?php echo ($vehicle['avg_consumption'] > 10) ? 'danger' : (($vehicle['avg_consumption'] > 7) ? 'warning' : 'success'); ?>">
                                                        <?php echo number_format($vehicle['avg_consumption'], 2); ?> L/100km
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-primary">
                                                    <?php echo number_format($vehicle['total_cost'] ?? 0, 2); ?> RON
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                                Nu s-au găsit date pentru perioada selectată.
                                                <br><br>
                                                <small>Încercați să modificați filtrele sau perioada de raportare.</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Acțiuni rapide -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-download"></i> Export Date
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Exportă datele din acest raport în diferite formate:</p>
                                                        <div class="d-grid gap-2 d-md-flex align-items-start">
                                <button class="btn btn-outline-success" onclick="exportReport('csv')">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                                                                <button class="btn btn-outline-danger" onclick="exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                                                <div class="ms-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="exp_inline" checked>
                                                                        <label class="form-check-label" for="exp_inline">Deschide în browser</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                                                            <input class="form-check-input" type="checkbox" id="exp_print" checked>
                                                                        <label class="form-check-label" for="exp_print">Deschide fereastra de tipărire</label>
                                                                    </div>
                                                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-share"></i> Rapoarte Conexe
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Generează rapoarte suplimentare pentru analiză:</p>
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>reports/costs?date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                                   class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-chart-pie"></i> Analiză Costuri
                                </a>
                                <a href="<?= BASE_URL ?>fuel/reports?date_from=<?= urlencode($filters['date_from']) ?>&date_to=<?= urlencode($filters['date_to']) ?>" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-gas-pump"></i> Raport Combustibil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
<script>
function exportReport(format) {
    const baseUrl = '<?= BASE_URL ?>reports/fleet';
    const params = new URLSearchParams(<?= json_encode($filters ?? []) ?>);
    params.set('export', format);
    if (format === 'pdf') {
        const inline = document.getElementById('exp_inline').checked;
        const print = document.getElementById('exp_print').checked;
        if (inline) params.set('inline', '1');
        if (print) params.set('print', '1');
    }
    const target = (format === 'pdf' && document.getElementById('exp_inline').checked) ? '_blank' : '_self';
    window.open(baseUrl + '?' + params.toString(), target);
}

// Validare form
document.querySelector('form').addEventListener('submit', function(e) {
    const df = new Date(document.getElementById('date_from').value);
    const dt = new Date(document.getElementById('date_to').value);
    if (dt < df) { e.preventDefault(); alert('Data "Până La" trebuie să fie după data "De La"!'); }
});
</script>
<!-- template-only: footer provided by Controller::render() -->
