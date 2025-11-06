<?php
// Verificăm dacă utilizatorul este autentificat
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$pageTitle = 'Raport Combustibil';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php 
            $breadcrumbs = [
                'Acasă' => '/',
                'Rapoarte' => '/modules/reports/',
                'Raport Combustibil' => ''
            ];
            include __DIR__ . '/../../../includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-gas-pump"></i> Raport Combustibil
                    <?php if (!empty($filters['date_from']) && !empty($filters['date_to'])): ?>
                        <small class="text-muted">
                            (<?php echo date('d.m.Y', strtotime($filters['date_from'])); ?> - <?php echo date('d.m.Y', strtotime($filters['date_to'])); ?>)
                        </small>
                    <?php endif; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportReport('csv')">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                    <div class="btn-group">
                        <a href="/modules/reports/" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Înapoi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filtre -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="action" value="fuel_report">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data De La</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $filters['date_from'] ?? date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Până La</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $filters['date_to'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="vehicle_id" class="form-label">Vehicul</label>
                            <select class="form-select" id="vehicle_id" name="vehicle_id">
                                <option value="">Toate vehiculele</option>
                                <?php if (!empty($vehicles)): ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['id']; ?>" 
                                                <?php echo ($filters['vehicle_id'] == $vehicle['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
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

            <?php if (!empty($fuelData)): ?>
            <!-- Statistici generale -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo number_format($fuelData['stats']['total_liters'] ?? 0, 0); ?> L</div>
                                    <div>Total Combustibil</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-gas-pump fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo number_format($fuelData['stats']['total_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Total</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo number_format($fuelData['stats']['avg_price_per_liter'] ?? 0, 2); ?> RON/L</div>
                                    <div>Preț Mediu/Litru</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo number_format($fuelData['stats']['avg_consumption'] ?? 0, 2); ?> L/100km</div>
                                    <div>Consum Mediu</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tachometer-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafice -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line"></i> Evoluția Prețului/Litru
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="priceChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar"></i> Consumul Lunar
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="consumptionChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top vehicule după consum -->
            <?php if (!empty($fuelData['by_vehicle'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy"></i> Top Vehicule după Consum
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Vehicul</th>
                                    <th>Total Litri</th>
                                    <th>Cost Total</th>
                                    <th>Kilometri Parcurși</th>
                                    <th>Consum L/100km</th>
                                    <th>Eficiența</th>
                                    <th>Nr. Alimentări</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach (array_slice($fuelData['by_vehicle'], 0, 10) as $vehicle): ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank <= 3): ?>
                                                <span class="badge bg-<?php echo $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'dark'); ?>">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">#<?php echo $rank; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></small>
                                        </td>
                                        <td><?php echo number_format($vehicle['total_liters'], 0); ?> L</td>
                                        <td><?php echo number_format($vehicle['total_cost'], 0); ?> RON</td>
                                        <td><?php echo number_format($vehicle['kilometers'], 0); ?> km</td>
                                        <td>
                                            <?php if ($vehicle['consumption'] > 0): ?>
                                                <span class="badge bg-<?php echo ($vehicle['consumption'] > 12) ? 'danger' : (($vehicle['consumption'] > 8) ? 'warning' : 'success'); ?>">
                                                    <?php echo number_format($vehicle['consumption'], 2); ?> L/100km
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $efficiency = 0;
                                            if ($vehicle['consumption'] > 0) {
                                                if ($vehicle['consumption'] <= 6) $efficiency = 'Excelentă';
                                                elseif ($vehicle['consumption'] <= 8) $efficiency = 'Bună';
                                                elseif ($vehicle['consumption'] <= 10) $efficiency = 'Medie';
                                                elseif ($vehicle['consumption'] <= 12) $efficiency = 'Slabă';
                                                else $efficiency = 'Foarte slabă';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo ($vehicle['consumption'] <= 6) ? 'success' : (($vehicle['consumption'] <= 10) ? 'warning' : 'danger'); ?>">
                                                <?php echo $efficiency ?: 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $vehicle['fuel_count']; ?></span>
                                        </td>
                                    </tr>
                                    <?php $rank++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Analiză pe stații -->
            <?php if (!empty($fuelData['by_station'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt"></i> Analiza pe Stații de Alimentare
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Stația</th>
                                    <th>Nr. Alimentări</th>
                                    <th>Total Litri</th>
                                    <th>Cost Total</th>
                                    <th>Preț Mediu/L</th>
                                    <th>% din Total</th>
                                    <th>Avantaj Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelData['by_station'] as $station): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($station['station'] ?: 'Stație Necunoscută'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $station['fuel_count']; ?></span>
                                        </td>
                                        <td><?php echo number_format($station['total_liters'], 0); ?> L</td>
                                        <td><?php echo number_format($station['total_cost'], 0); ?> RON</td>
                                        <td>
                                            <span class="badge bg-<?php echo ($station['avg_price'] < $fuelData['stats']['avg_price_per_liter']) ? 'success' : 'warning'; ?>">
                                                <?php echo number_format($station['avg_price'], 2); ?> RON/L
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo ($station['total_cost'] / $fuelData['stats']['total_cost']) * 100; ?>%"
                                                     aria-valuenow="<?php echo ($station['total_cost'] / $fuelData['stats']['total_cost']) * 100; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format(($station['total_cost'] / $fuelData['stats']['total_cost']) * 100, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $advantage = $fuelData['stats']['avg_price_per_liter'] - $station['avg_price'];
                                            if (abs($advantage) > 0.01):
                                            ?>
                                                <span class="badge bg-<?php echo $advantage > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $advantage > 0 ? '-' : '+'; ?><?php echo number_format(abs($advantage), 2); ?> RON/L
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">~</span>
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

            <!-- Evoluție lunară -->
            <?php if (!empty($fuelData['monthly'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Evoluție Lunară
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alertele de consum -->
            <?php if (!empty($fuelData['alerts'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Alerte de Consum
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($fuelData['alerts'] as $alert): ?>
                            <div class="col-md-4 mb-3">
                                <div class="alert alert-<?php echo $alert['severity'] == 'high' ? 'danger' : ($alert['severity'] == 'medium' ? 'warning' : 'info'); ?>">
                                    <h6><i class="fas fa-<?php echo $alert['type'] == 'consumption' ? 'tachometer-alt' : 'gas-pump'; ?>"></i> <?php echo htmlspecialchars($alert['title']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($alert['message']); ?></p>
                                    <small><strong>Vehicul:</strong> <?php echo htmlspecialchars($alert['vehicle']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista completă alimentări -->
            <?php if (!empty($fuelData['fuel_list'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Lista Completă Alimentări
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Vehicul</th>
                                    <th>Litri</th>
                                    <th>Cost Total</th>
                                    <th>Preț/L</th>
                                    <th>Kilometraj</th>
                                    <th>Consum</th>
                                    <th>Stația</th>
                                    <th>Observații</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelData['fuel_list'] as $fuel): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($fuel['fuel_date'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($fuel['license_plate']); ?></strong>
                                        </td>
                                        <td><?php echo number_format($fuel['liters'], 2); ?> L</td>
                                        <td><?php echo number_format($fuel['cost'], 2); ?> RON</td>
                                        <td>
                                            <span class="badge bg-<?php echo ($fuel['price_per_liter'] < $fuelData['stats']['avg_price_per_liter']) ? 'success' : 'warning'; ?>">
                                                <?php echo number_format($fuel['cost'] / $fuel['liters'], 2); ?> RON/L
                                            </span>
                                        </td>
                                        <td><?php echo number_format($fuel['odometer'], 0); ?> km</td>
                                        <td>
                                            <?php if ($fuel['consumption'] > 0): ?>
                                                <span class="badge bg-<?php echo ($fuel['consumption'] > 12) ? 'danger' : (($fuel['consumption'] > 8) ? 'warning' : 'success'); ?>">
                                                    <?php echo number_format($fuel['consumption'], 2); ?> L/100km
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($fuel['station'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($fuel['notes']): ?>
                                                <i class="fas fa-comment" title="<?php echo htmlspecialchars($fuel['notes']); ?>"></i>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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

            <!-- Recomandări de optimizare -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Recomandări de Optimizare
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-route"></i> Rutele</h6>
                                <ul class="mb-0">
                                    <li>Planificați rutele eficient</li>
                                    <li>Evitați traficul aglomerat</li>
                                    <li>Folosiți aplicații de navigație</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-wrench"></i> Mentenanța</h6>
                                <ul class="mb-0">
                                    <li>Verificați presiunea anvelopelor</li>
                                    <li>Înlocuiți filtrele de aer</li>
                                    <li>Mențineți motorul în stare bună</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-car"></i> Conducerea</h6>
                                <ul class="mb-0">
                                    <li>Conduceți economic</li>
                                    <li>Evitați accelerațiile bruște</li>
                                    <li>Opriți motorul la staționări lungi</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                <h5>Generați raportul de combustibil</h5>
                <p>Folosiți filtrele de mai sus pentru a genera raportul dorit.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($fuelData)): ?>
// Grafic preț
<?php if (!empty($fuelData['price_evolution'])): ?>
const priceCtx = document.getElementById('priceChart').getContext('2d');
const priceChart = new Chart(priceCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($fuelData['price_evolution'] as $price): ?>
                '<?php echo date('d.m.Y', strtotime($price['fuel_date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Preț/Litru (RON)',
            data: [
                <?php foreach ($fuelData['price_evolution'] as $price): ?>
                    <?php echo number_format($price['price_per_liter'], 2); ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return value.toFixed(2) + ' RON';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Grafic consum lunar
<?php if (!empty($fuelData['monthly'])): ?>
const consumptionCtx = document.getElementById('consumptionChart').getContext('2d');
const consumptionChart = new Chart(consumptionCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($fuelData['monthly'] as $month): ?>
                '<?php echo date('M Y', mktime(0, 0, 0, $month['month'], 1, $month['year'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Litri consumați',
            data: [
                <?php foreach ($fuelData['monthly'] as $month): ?>
                    <?php echo $month['total_liters']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '#28a745'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + ' L';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Grafic evoluție lunară
<?php if (!empty($fuelData['monthly'])): ?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($fuelData['monthly'] as $month): ?>
                '<?php echo date('M Y', mktime(0, 0, 0, $month['month'], 1, $month['year'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Cost total (RON)',
            data: [
                <?php foreach ($fuelData['monthly'] as $month): ?>
                    <?php echo $month['total_cost']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }, {
            label: 'Litri consumați',
            data: [
                <?php foreach ($fuelData['monthly'] as $month): ?>
                    <?php echo $month['total_liters']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }, {
            label: 'Preț mediu/litru (RON)',
            data: [
                <?php foreach ($fuelData['monthly'] as $month): ?>
                    <?php echo number_format($month['avg_price'], 2); ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4,
            yAxisID: 'y2'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cost (RON)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: 'Litri'
                }
            },
            y2: {
                type: 'linear',
                display: false,
                beginAtZero: false
            }
        }
    }
});
<?php endif; ?>
<?php endif; ?>

function exportReport(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    
    window.open(currentUrl.toString(), '_blank');
}

// Validare form
document.querySelector('form').addEventListener('submit', function(e) {
    const dateFrom = new Date(document.getElementById('date_from').value);
    const dateTo = new Date(document.getElementById('date_to').value);
    
    if (dateTo < dateFrom) {
        e.preventDefault();
        alert('Data "Până La" trebuie să fie după data "De La"!');
        return false;
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
