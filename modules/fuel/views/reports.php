<?php
// Safe defaults
$pageTitle = $pageTitle ?? 'Rapoarte Combustibil';
$report_type = $report_type ?? ($_GET['report_type'] ?? 'overview');
$filters = $filters ?? [
    'vehicle' => $_GET['vehicle'] ?? '',
    'driver' => $_GET['driver'] ?? '',
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
];
$stats = $stats ?? ['total_liters' => 0, 'total_cost' => 0, 'avg_consumption' => null, 'total_distance' => 0];
$reports = $reports ?? [];
$chartData = $chartData ?? ['consumption_trend' => [], 'cost_trend' => [], 'cost_distribution' => []];
$vehicles = $vehicles ?? [];
$drivers = $drivers ?? [];
?>

<style>
@media print {
    .btn, .report-tabs, .filters-panel, nav, .sidebar, header, footer { display: none !important; }
    .report-header { background: #007bff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; padding: 20px; }
    .container-fluid { max-width: 100%; }
}
.report-tabs { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 0; margin-bottom: 1.5rem; }
.report-tab { display: inline-block; padding: 1rem 1.5rem; text-decoration: none; color: #6c757d; border-bottom: 3px solid transparent; transition: all 0.3s; }
.report-tab:hover { color: #007bff; background: rgba(0,123,255,0.05); }
.report-tab.active { color: #007bff; border-bottom-color: #007bff; font-weight: 600; }
.metric-card { border-left: 4px solid #007bff; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="report-header p-4 mb-3 text-white" style="background:linear-gradient(135deg,#007bff,#0056b3);border-radius:10px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-chart-bar me-2"></i><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="mb-0 opacity-75">Analiza detaliata a consumului de combustibil ?i costuri</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <a href="<?= BASE_URL ?>fuel" class="btn btn-light"><i class="fas fa-list"></i> Lista</a>
                <a href="<?= BASE_URL ?>fuel/add" class="btn btn-success"><i class="fas fa-plus"></i> Adauga</a>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="report-tabs">
        <a href="?<?= http_build_query(array_merge($_GET, ['report_type' => 'overview'])) ?>" class="report-tab <?= $report_type === 'overview' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Prezentare
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['report_type' => 'vehicle'])) ?>" class="report-tab <?= $report_type === 'vehicle' ? 'active' : '' ?>">
            <i class="fas fa-car"></i> Vehicule
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['report_type' => 'driver'])) ?>" class="report-tab <?= $report_type === 'driver' ? 'active' : '' ?>">
            <i class="fas fa-user"></i> ?oferi
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['report_type' => 'cost'])) ?>" class="report-tab <?= $report_type === 'cost' ? 'active' : '' ?>">
            <i class="fas fa-money-bill"></i> Costuri
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['report_type' => 'efficiency'])) ?>" class="report-tab <?= $report_type === 'efficiency' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Eficien?a
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="report_type" value="<?= htmlspecialchars($report_type) ?>">
                <div class="col-md-2">
                    <label class="form-label">De la</label>
                    <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pâna la</label>
                    <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vehicul</label>
                    <select class="form-select" name="vehicle">
                        <option value="">Toate vehiculele</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $filters['vehicle'] == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['registration_number'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">?ofer</label>
                    <select class="form-select" name="driver">
                        <option value="">To?i ?oferii</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filters['driver'] == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtreaza</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="metric-card" style="border-left-color:#28a745;">
                <div class="fs-2 fw-bold"><?= number_format($stats['total_liters'], 1) ?></div>
                <div class="text-muted small">LITRI TOTALI</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card" style="border-left-color:#ffc107;">
                <div class="fs-2 fw-bold"><?= number_format($stats['total_cost'], 0) ?> RON</div>
                <div class="text-muted small">COST TOTAL</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card" style="border-left-color:#17a2b8;">
                <div class="fs-2 fw-bold"><?= $stats['avg_consumption'] ? number_format($stats['avg_consumption'], 2) : '' ?></div>
                <div class="text-muted small">CONSUM MEDIU (L/100km)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card" style="border-left-color:#6f42c1;">
                <div class="fs-2 fw-bold"><?= number_format($stats['total_distance'], 0) ?></div>
                <div class="text-muted small">KILOMETRI TOTALI</div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <?php if ($report_type === 'vehicle' && !empty($reports['vehicle_consumption'])): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-car me-2"></i>Raport Detaliat pe Vehicule
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Vehicul</th>
                                <th>Alimentari</th>
                                <th>Litri</th>
                                <th>Cost</th>
                                <th>Consum Mediu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports['vehicle_consumption'] as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['registration_number'] ?? '') ?></strong></td>
                                    <td><?= $r['fill_count'] ?? 0 ?></td>
                                    <td><?= number_format($r['total_liters'] ?? 0, 1) ?> L</td>
                                    <td><?= number_format($r['total_cost'] ?? 0, 0) ?> RON</td>
                                    <td><?= number_format($r['avg_consumption'] ?? 0, 2) ?> L/100km</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($report_type === 'driver' && !empty($reports['driver_consumption'])): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-user me-2"></i>Raport Detaliat pe ?oferi
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>?ofer</th>
                                <th>Vehicule</th>
                                <th>Alimentari</th>
                                <th>Litri</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports['driver_consumption'] as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['driver_name'] ?? '') ?></strong></td>
                                    <td><?= $r['vehicles_used'] ?? 0 ?></td>
                                    <td><?= $r['fill_count'] ?? 0 ?></td>
                                    <td><?= number_format($r['total_liters'] ?? 0, 1) ?> L</td>
                                    <td><?= number_format($r['total_cost'] ?? 0, 0) ?> RON</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Date pentru raport: <?= htmlspecialchars($report_type) ?></h5>
                <p class="text-muted">Perioada: <?= htmlspecialchars($filters['date_from']) ?> - <?= htmlspecialchars($filters['date_to']) ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
