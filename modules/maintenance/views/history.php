<?php
require_once '../../../config/config.php';
require_once '../../../core/database.php';
require_once '../controllers/MaintenanceController.php';

$vehicleId = $_GET['vehicle_id'] ?? null;

if (!$vehicleId) {
    header('Location: list.php');
    exit;
}

$maintenanceController = new MaintenanceController();
$data = $maintenanceController->history($vehicleId);

$vehicle = $data['vehicle'] ?? null;
$maintenanceHistory = $data['maintenanceHistory'] ?? [];
$upcomingMaintenance = $data['upcomingMaintenance'] ?? [];
$maintenanceStats = $data['maintenanceStats'] ?? [];

if (!$vehicle) {
    header('Location: list.php');
    exit;
}

$pageTitle = 'Istoric Mentenanță - ' . $vehicle['license_plate'];
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Fleet Management</title>
    <link href="../../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../assets/css/main.css" rel="stylesheet">
    <link href="../../../assets/css/modules/maintenance.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../../../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include '../../../includes/breadcrumb.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-history text-primary"></i>
                        Istoric Mentenanță
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add.php?vehicle_id=<?php echo $vehicleId; ?>" class="btn btn-success">
                                <i class="fas fa-plus"></i> Adaugă Mentenanță
                            </a>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Înapoi la listă
                            </a>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportHistory('pdf')">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportHistory('excel')">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportHistory('csv')">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-car"></i>
                                    Informații Vehicul
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="vehicle-icon me-3">
                                                <i class="fas fa-car fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-0"><?php echo htmlspecialchars($vehicle['license_plate']); ?></h4>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></p>
                                                <small class="text-muted">Anul: <?php echo $vehicle['year']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="stat-box">
                                                    <div class="stat-icon">
                                                        <i class="fas fa-tachometer-alt text-info"></i>
                                                    </div>
                                                    <div class="stat-content">
                                                        <h6>Kilometraj Actual</h6>
                                                        <h4><?php echo number_format($vehicle['current_odometer']); ?> km</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-box">
                                                    <div class="stat-icon">
                                                        <i class="fas fa-wrench text-success"></i>
                                                    </div>
                                                    <div class="stat-content">
                                                        <h6>Total Mentenanțe</h6>
                                                        <h4><?php echo $maintenanceStats['total_maintenance'] ?? 0; ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-box">
                                                    <div class="stat-icon">
                                                        <i class="fas fa-euro-sign text-warning"></i>
                                                    </div>
                                                    <div class="stat-content">
                                                        <h6>Cost Total</h6>
                                                        <h4><?php echo number_format($maintenanceStats['total_cost'] ?? 0, 2); ?> RON</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-box">
                                                    <div class="stat-icon">
                                                        <i class="fas fa-clock text-danger"></i>
                                                    </div>
                                                    <div class="stat-content">
                                                        <h6>Mentenanțe Restante</h6>
                                                        <h4><?php echo $maintenanceStats['overdue_maintenance'] ?? 0; ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Maintenance -->
                <?php if (!empty($upcomingMaintenance)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt"></i>
                                    Mentenanțe Programate
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($upcomingMaintenance as $upcoming): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card border-start border-4 border-<?php 
                                                echo $upcoming['urgency_status'] == 'overdue' ? 'danger' : 
                                                    ($upcoming['urgency_status'] == 'due_today' ? 'warning' : 
                                                    ($upcoming['urgency_status'] == 'due_soon' ? 'info' : 'success')); 
                                            ?>">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <h6 class="card-title mb-1"><?php echo ucfirst(str_replace('_', ' ', $upcoming['maintenance_type'])); ?></h6>
                                                            <p class="card-text mb-1">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar"></i>
                                                                    <?php echo date('d.m.Y', strtotime($upcoming['scheduled_date'])); ?>
                                                                </small>
                                                            </p>
                                                            <span class="badge bg-<?php 
                                                                echo $upcoming['urgency_status'] == 'overdue' ? 'danger' : 
                                                                    ($upcoming['urgency_status'] == 'due_today' ? 'warning' : 
                                                                    ($upcoming['urgency_status'] == 'due_soon' ? 'info' : 'success')); 
                                                            ?>">
                                                                <?php 
                                                                if ($upcoming['urgency_status'] == 'overdue') {
                                                                    echo 'Întârziat (' . abs($upcoming['days_until_due']) . ' zile)';
                                                                } elseif ($upcoming['urgency_status'] == 'due_today') {
                                                                    echo 'Astăzi';
                                                                } elseif ($upcoming['urgency_status'] == 'due_soon') {
                                                                    echo 'În ' . $upcoming['days_until_due'] . ' zile';
                                                                } else {
                                                                    echo 'Programată';
                                                                }
                                                                ?>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-<?php 
                                                                echo $upcoming['priority'] == 'urgent' ? 'dark' : 
                                                                    ($upcoming['priority'] == 'high' ? 'danger' : 
                                                                    ($upcoming['priority'] == 'medium' ? 'warning' : 'success')); 
                                                            ?>">
                                                                <?php 
                                                                $priorities = ['low' => 'Scăzută', 'medium' => 'Medie', 'high' => 'Ridicată', 'urgent' => 'Urgentă'];
                                                                echo $priorities[$upcoming['priority']] ?? $upcoming['priority'];
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($upcoming['description'])): ?>
                                                        <p class="card-text mt-2 mb-0">
                                                            <small><?php echo htmlspecialchars(substr($upcoming['description'], 0, 80)) . (strlen($upcoming['description']) > 80 ? '...' : ''); ?></small>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Maintenance Statistics Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-pie"></i>
                                    Distribuție Costuri Mentenanță
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="costDistributionChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line"></i>
                                    Trend Costuri Mentenanță
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="costTrendChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance History Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list"></i>
                                        Istoric Mentenanțe (<?php echo count($maintenanceHistory); ?> înregistrări)
                                    </h5>
                                    <div class="card-tools">
                                        <div class="input-group input-group-sm" style="width: 250px;">
                                            <input type="text" id="searchHistory" class="form-control" placeholder="Caută în istoric...">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($maintenanceHistory)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-wrench fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Nu există istoric de mentenanță</h5>
                                        <p class="text-muted">Acest vehicul nu are încă înregistrări de mentenanță.</p>
                                        <a href="add.php?vehicle_id=<?php echo $vehicleId; ?>" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Adaugă prima mentenanță
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="historyTable">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Tip Mentenanță</th>
                                                    <th>Descriere</th>
                                                    <th>Kilometraj</th>
                                                    <th>Cost</th>
                                                    <th>Status</th>
                                                    <th>Furnizor</th>
                                                    <th>Acțiuni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($maintenanceHistory as $maintenance): ?>
                                                    <tr class="maintenance-row" data-maintenance-type="<?php echo $maintenance['maintenance_type']; ?>">
                                                        <td>
                                                            <div>
                                                                <?php if (!empty($maintenance['completed_date'])): ?>
                                                                    <strong><?php echo date('d.m.Y', strtotime($maintenance['completed_date'])); ?></strong>
                                                                    <br><small class="text-muted">Programată: <?php echo date('d.m.Y', strtotime($maintenance['scheduled_date'])); ?></small>
                                                                    <?php if ($maintenance['delay_days'] > 0): ?>
                                                                        <br><small class="text-warning">
                                                                            <i class="fas fa-clock"></i> +<?php echo $maintenance['delay_days']; ?> zile
                                                                        </small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?php echo date('d.m.Y', strtotime($maintenance['scheduled_date'])); ?>
                                                                    <br><small class="text-muted">Programată</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="maintenance-type-badge">
                                                                <?php
                                                                $typeIcons = [
                                                                    'oil_change' => 'fas fa-oil-can',
                                                                    'filter_change' => 'fas fa-filter',
                                                                    'tire_rotation' => 'fas fa-circle-notch',
                                                                    'tire_change' => 'fas fa-circle',
                                                                    'brake_check' => 'fas fa-brake-warning',
                                                                    'brake_service' => 'fas fa-tools',
                                                                    'transmission' => 'fas fa-cogs',
                                                                    'engine_service' => 'fas fa-car-crash',
                                                                    'cooling_system' => 'fas fa-thermometer-half',
                                                                    'electrical' => 'fas fa-bolt',
                                                                    'suspension' => 'fas fa-car-side',
                                                                    'inspection' => 'fas fa-search',
                                                                    'repair' => 'fas fa-wrench',
                                                                    'other' => 'fas fa-tools'
                                                                ];
                                                                $icon = $typeIcons[$maintenance['maintenance_type']] ?? 'fas fa-tools';
                                                                
                                                                $typeNames = [
                                                                    'oil_change' => 'Schimb Ulei',
                                                                    'filter_change' => 'Schimb Filtre',
                                                                    'tire_rotation' => 'Rotire Anvelope',
                                                                    'tire_change' => 'Schimb Anvelope',
                                                                    'brake_check' => 'Verificare Frâne',
                                                                    'brake_service' => 'Service Frâne',
                                                                    'transmission' => 'Service Transmisie',
                                                                    'engine_service' => 'Service Motor',
                                                                    'cooling_system' => 'Sistem Răcire',
                                                                    'electrical' => 'Sistem Electric',
                                                                    'suspension' => 'Suspensie',
                                                                    'inspection' => 'Inspecție Tehnică',
                                                                    'repair' => 'Reparație',
                                                                    'other' => 'Altele'
                                                                ];
                                                                ?>
                                                                <i class="<?php echo $icon; ?>"></i>
                                                                <?php echo $typeNames[$maintenance['maintenance_type']] ?? ucfirst(str_replace('_', ' ', $maintenance['maintenance_type'])); ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($maintenance['description'])): ?>
                                                                <div class="description-cell" title="<?php echo htmlspecialchars($maintenance['description']); ?>">
                                                                    <?php echo htmlspecialchars(substr($maintenance['description'], 0, 50)) . (strlen($maintenance['description']) > 50 ? '...' : ''); ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($maintenance['odometer_at_service'])): ?>
                                                                <strong><?php echo number_format($maintenance['odometer_at_service']); ?> km</strong>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($maintenance['cost'] > 0): ?>
                                                                <div>
                                                                    <strong><?php echo number_format($maintenance['cost'], 2); ?> RON</strong>
                                                                    <?php if ($maintenance['parts_cost'] > 0 || $maintenance['labor_cost'] > 0): ?>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            <?php if ($maintenance['parts_cost'] > 0): ?>
                                                                                Piese: <?php echo number_format($maintenance['parts_cost'], 2); ?> RON
                                                                            <?php endif; ?>
                                                                            <?php if ($maintenance['labor_cost'] > 0): ?>
                                                                                <?php echo $maintenance['parts_cost'] > 0 ? '<br>' : ''; ?>
                                                                                Manoperă: <?php echo number_format($maintenance['labor_cost'], 2); ?> RON
                                                                            <?php endif; ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statusClasses = [
                                                                'scheduled' => 'bg-info',
                                                                'in_progress' => 'bg-warning',
                                                                'completed' => 'bg-success',
                                                                'cancelled' => 'bg-secondary'
                                                            ];
                                                            $statusNames = [
                                                                'scheduled' => 'Programată',
                                                                'in_progress' => 'În Desfășurare',
                                                                'completed' => 'Finalizată',
                                                                'cancelled' => 'Anulată'
                                                            ];
                                                            $statusClass = $statusClasses[$maintenance['status']] ?? 'bg-secondary';
                                                            $statusName = $statusNames[$maintenance['status']] ?? $maintenance['status'];
                                                            ?>
                                                            <span class="badge <?php echo $statusClass; ?>">
                                                                <?php echo $statusName; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($maintenance['service_provider'])): ?>
                                                                <div>
                                                                    <?php echo htmlspecialchars($maintenance['service_provider']); ?>
                                                                    <?php if (!empty($maintenance['service_location'])): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($maintenance['service_location']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="view.php?id=<?php echo $maintenance['id']; ?>" 
                                                                   class="btn btn-outline-info" title="Vezi detalii">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit.php?id=<?php echo $maintenance['id']; ?>" 
                                                                   class="btn btn-outline-warning" title="Editează">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <?php if (!empty($maintenance['receipt_file'])): ?>
                                                                    <a href="../../../uploads/maintenance/<?php echo $maintenance['receipt_file']; ?>" 
                                                                       class="btn btn-outline-secondary" target="_blank" title="Vezi documentul">
                                                                        <i class="fas fa-paperclip"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../../../includes/footer.php'; ?>
    
    <script src="../../../assets/js/jquery.min.js"></script>
    <script src="../../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/main.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize charts
            initializeCharts();
            
            // Search functionality
            $('#searchHistory').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterHistory(searchTerm);
            });
            
            // Tooltip initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function initializeCharts() {
            // Cost Distribution Chart
            const costData = <?php 
                $costData = [
                    'parts' => 0,
                    'labor' => 0,
                    'other' => 0
                ];
                
                foreach ($maintenanceHistory as $maintenance) {
                    if ($maintenance['status'] === 'completed') {
                        $costData['parts'] += $maintenance['parts_cost'] ?? 0;
                        $costData['labor'] += $maintenance['labor_cost'] ?? 0;
                        $other = ($maintenance['cost'] ?? 0) - ($maintenance['parts_cost'] ?? 0) - ($maintenance['labor_cost'] ?? 0);
                        if ($other > 0) $costData['other'] += $other;
                    }
                }
                echo json_encode($costData);
            ?>;

            if (costData.parts > 0 || costData.labor > 0 || costData.other > 0) {
                const ctx1 = document.getElementById('costDistributionChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ['Piese', 'Manoperă', 'Altele'],
                        datasets: [{
                            data: [costData.parts, costData.labor, costData.other],
                            backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } else {
                document.getElementById('costDistributionChart').innerHTML = '<p class="text-center text-muted">Nu există date pentru grafic</p>';
            }

            // Cost Trend Chart
            const trendData = <?php
                $trendData = [];
                $monthlyData = [];
                
                foreach ($maintenanceHistory as $maintenance) {
                    if ($maintenance['status'] === 'completed' && !empty($maintenance['completed_date'])) {
                        $month = date('Y-m', strtotime($maintenance['completed_date']));
                        if (!isset($monthlyData[$month])) {
                            $monthlyData[$month] = 0;
                        }
                        $monthlyData[$month] += $maintenance['cost'] ?? 0;
                    }
                }
                
                ksort($monthlyData);
                $labels = [];
                $costs = [];
                
                foreach ($monthlyData as $month => $cost) {
                    $labels[] = date('M Y', strtotime($month . '-01'));
                    $costs[] = $cost;
                }
                
                echo json_encode(['labels' => $labels, 'costs' => $costs]);
            ?>;

            if (trendData.labels.length > 0) {
                const ctx2 = document.getElementById('costTrendChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: trendData.labels,
                        datasets: [{
                            label: 'Costuri Mentenanță (RON)',
                            data: trendData.costs,
                            borderColor: '#36A2EB',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
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
                                        return value.toLocaleString() + ' RON';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            } else {
                document.getElementById('costTrendChart').innerHTML = '<p class="text-center text-muted">Nu există date pentru grafic</p>';
            }
        }

        function filterHistory(searchTerm) {
            const rows = document.querySelectorAll('#historyTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function exportHistory(format) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../controllers/MaintenanceController.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'export';
            form.appendChild(actionInput);
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = format;
            form.appendChild(formatInput);
            
            const vehicleInput = document.createElement('input');
            vehicleInput.type = 'hidden';
            vehicleInput.name = 'vehicle_id';
            vehicleInput.value = '<?php echo $vehicleId; ?>';
            form.appendChild(vehicleInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // Print functionality
        function printHistory() {
            const printContent = document.querySelector('.card-body').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h2>Istoric Mentenanță - <?php echo htmlspecialchars($vehicle['license_plate']); ?></h2>
                    <p>Generat la: ${new Date().toLocaleDateString('ro-RO')}</p>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl + P - Print
            if (e.ctrlKey && e.keyCode === 80) {
                e.preventDefault();
                printHistory();
            }
            
            // Ctrl + F - Focus search
            if (e.ctrlKey && e.keyCode === 70) {
                e.preventDefault();
                $('#searchHistory').focus();
            }
        });
    </script>
</body>
</html>
