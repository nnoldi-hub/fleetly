<?php
// Verificăm dacă utilizatorul este autentificat
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$pageTitle = 'Raport Mentenanță';
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
                'Raport Mentenanță' => ''
            ];
            include __DIR__ . '/../../../includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-wrench"></i> Raport Mentenanță
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
                        <input type="hidden" name="action" value="maintenance_report">
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
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Toate statusurile</option>
                                <option value="scheduled" <?php echo ($filters['status'] == 'scheduled') ? 'selected' : ''; ?>>Programat</option>
                                <option value="in_progress" <?php echo ($filters['status'] == 'in_progress') ? 'selected' : ''; ?>>În progres</option>
                                <option value="completed" <?php echo ($filters['status'] == 'completed') ? 'selected' : ''; ?>>Completat</option>
                                <option value="cancelled" <?php echo ($filters['status'] == 'cancelled') ? 'selected' : ''; ?>>Anulat</option>
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

            <?php if (!empty($maintenanceData)): ?>
            <!-- Statistici generale -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?php echo $maintenanceData['stats']['total_count'] ?? 0; ?></div>
                                    <div>Total Mentenanțe</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo $maintenanceData['stats']['completed_count'] ?? 0; ?></div>
                                    <div>Completate</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo $maintenanceData['stats']['pending_count'] ?? 0; ?></div>
                                    <div>În așteptare</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
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
                                    <div class="h4 mb-0"><?php echo number_format($maintenanceData['stats']['total_cost'] ?? 0, 0); ?> RON</div>
                                    <div>Cost Total</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
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
                                <i class="fas fa-chart-pie"></i> Mentenanțe pe Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar"></i> Tipuri de Mentenanță
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="typeChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top vehicule după costuri mentenanță -->
            <?php if (!empty($maintenanceData['by_vehicle'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy"></i> Top Vehicule după Costuri Mentenanță
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Vehicul</th>
                                    <th>Mentenanțe Programate</th>
                                    <th>Mentenanțe Completate</th>
                                    <th>Rata de Completare</th>
                                    <th>Cost Total</th>
                                    <th>Cost Mediu/Mentenanță</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach (array_slice($maintenanceData['by_vehicle'], 0, 10) as $vehicle): ?>
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
                                        <td><?php echo $vehicle['scheduled_count']; ?></td>
                                        <td><?php echo $vehicle['completed_count']; ?></td>
                                        <td>
                                            <?php $completion_rate = $vehicle['scheduled_count'] > 0 ? ($vehicle['completed_count'] / $vehicle['scheduled_count']) * 100 : 0; ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?php echo $completion_rate >= 80 ? 'success' : ($completion_rate >= 60 ? 'warning' : 'danger'); ?>" 
                                                     role="progressbar" style="width: <?php echo $completion_rate; ?>%"
                                                     aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format($completion_rate, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo number_format($vehicle['total_cost'], 0); ?> RON</strong></td>
                                        <td>
                                            <?php if ($vehicle['completed_count'] > 0): ?>
                                                <?php echo number_format($vehicle['total_cost'] / $vehicle['completed_count'], 0); ?> RON
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
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

            <!-- Analiză pe tipuri de mentenanță -->
            <?php if (!empty($maintenanceData['by_type'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs"></i> Analiză pe Tipuri de Mentenanță
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Tip Mentenanță</th>
                                    <th>Numărul de Mentenanțe</th>
                                    <th>Cost Total</th>
                                    <th>Cost Mediu</th>
                                    <th>Frecvența (%)</th>
                                    <th>Tendință Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceData['by_type'] as $type): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($type['maintenance_type']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $type['count']; ?></span>
                                        </td>
                                        <td><?php echo number_format($type['total_cost'], 0); ?> RON</td>
                                        <td><?php echo number_format($type['avg_cost'], 0); ?> RON</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo ($type['count'] / $maintenanceData['stats']['total_count']) * 100; ?>%"
                                                     aria-valuenow="<?php echo ($type['count'] / $maintenanceData['stats']['total_count']) * 100; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format(($type['count'] / $maintenanceData['stats']['total_count']) * 100, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (isset($type['cost_trend'])): ?>
                                                <span class="badge bg-<?php echo $type['cost_trend'] > 0 ? 'danger' : ($type['cost_trend'] < 0 ? 'success' : 'secondary'); ?>">
                                                    <?php echo $type['cost_trend'] > 0 ? '↗' : ($type['cost_trend'] < 0 ? '↘' : '→'); ?>
                                                    <?php echo abs($type['cost_trend']); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
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
            <?php if (!empty($maintenanceData['monthly'])): ?>
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

            <!-- Mentenanțe în întârziere -->
            <?php if (!empty($maintenanceData['overdue'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Mentenanțe în Întârziere
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Tip Mentenanță</th>
                                    <th>Data Programată</th>
                                    <th>Întârziere (zile)</th>
                                    <th>Cost Estimat</th>
                                    <th>Urgență</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceData['overdue'] as $overdue): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($overdue['license_plate']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($overdue['make'] . ' ' . $overdue['model']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($overdue['maintenance_type']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($overdue['scheduled_date'])); ?></td>
                                        <td>
                                            <?php $days_overdue = (time() - strtotime($overdue['scheduled_date'])) / (60 * 60 * 24); ?>
                                            <span class="badge bg-<?php echo $days_overdue > 30 ? 'danger' : ($days_overdue > 7 ? 'warning' : 'info'); ?>">
                                                <?php echo floor($days_overdue); ?> zile
                                            </span>
                                        </td>
                                        <td><?php echo number_format($overdue['cost'], 0); ?> RON</td>
                                        <td>
                                            <?php if ($days_overdue > 30): ?>
                                                <span class="badge bg-danger">Critică</span>
                                            <?php elseif ($days_overdue > 7): ?>
                                                <span class="badge bg-warning">Ridicată</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Normală</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/modules/maintenance/?action=edit&id=<?php echo $overdue['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i> Editare
                                                </a>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="markCompleted(<?php echo $overdue['id']; ?>)">
                                                    <i class="fas fa-check"></i> Completează
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista completă mentenanțe -->
            <?php if (!empty($maintenanceData['maintenance_list'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i> Lista Completă Mentenanțe
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Tip</th>
                                    <th>Data Programată</th>
                                    <th>Data Completării</th>
                                    <th>Descriere</th>
                                    <th>Furnizor</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceData['maintenance_list'] as $maintenance): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($maintenance['license_plate']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($maintenance['maintenance_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                        <td>
                                            <?php if ($maintenance['completed_date']): ?>
                                                <?php echo date('d.m.Y', strtotime($maintenance['completed_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Necompletat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($maintenance['description'], 0, 50) . '...'); ?></td>
                                        <td><?php echo htmlspecialchars($maintenance['service_provider'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($maintenance['cost'], 0); ?> RON</td>
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

            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                <h5>Generați raportul de mentenanță</h5>
                <p>Folosiți filtrele de mai sus pentru a genera raportul dorit.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($maintenanceData)): ?>
// Grafic status
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Programat', 'În progres', 'Completat', 'Anulat'],
        datasets: [{
            data: [
                <?php echo $maintenanceData['stats']['scheduled_count'] ?? 0; ?>,
                <?php echo $maintenanceData['stats']['in_progress_count'] ?? 0; ?>,
                <?php echo $maintenanceData['stats']['completed_count'] ?? 0; ?>,
                <?php echo $maintenanceData['stats']['cancelled_count'] ?? 0; ?>
            ],
            backgroundColor: [
                '#6c757d',
                '#ffc107',
                '#28a745',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Grafic tipuri mentenanță
<?php if (!empty($maintenanceData['by_type'])): ?>
const typeCtx = document.getElementById('typeChart').getContext('2d');
const typeChart = new Chart(typeCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($maintenanceData['by_type'] as $type): ?>
                '<?php echo addslashes($type['maintenance_type']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Numărul de mentenanțe',
            data: [
                <?php foreach ($maintenanceData['by_type'] as $type): ?>
                    <?php echo $type['count']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>

// Grafic evoluție lunară
<?php if (!empty($maintenanceData['monthly'])): ?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($maintenanceData['monthly'] as $month): ?>
                '<?php echo date('M Y', mktime(0, 0, 0, $month['month'], 1, $month['year'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Numărul de mentenanțe',
            data: [
                <?php foreach ($maintenanceData['monthly'] as $month): ?>
                    <?php echo $month['count']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }, {
            label: 'Cost total (RON)',
            data: [
                <?php foreach ($maintenanceData['monthly'] as $month): ?>
                    <?php echo $month['total_cost']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
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
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false
                }
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

function markCompleted(maintenanceId) {
    if (confirm('Sunteți sigur că doriți să marcați această mentenanță ca fiind completată?')) {
        fetch('/modules/maintenance/?action=mark_completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: maintenanceId,
                completed_date: new Date().toISOString().split('T')[0]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la procesarea cererii.');
        });
    }
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
