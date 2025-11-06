<?php
// modules/vehicles/views/dashboard.php
$pageTitle = "Dashboard Vehicule";
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-car text-primary me-2"></i>
                Dashboard Vehicule
            </h1>
            <div class="btn-group">
                <a href="<?= BASE_URL ?>vehicles/add" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Adaugă Vehicul
                </a>
                <a href="<?= BASE_URL ?>vehicles" class="btn btn-outline-primary">
                    <i class="fas fa-list me-1"></i> Vezi Toate
                </a>
            </div>
        </div>

        <!-- Statistici Principale -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Vehicule
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $totalVehicles ?? 0 ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-car fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Vehicule Active
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $activeVehicles ?? 0 ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    În Service
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $inMaintenanceVehicles ?? 0 ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tools fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Atenție Necesară
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= (count($expiringDocuments ?? []) + count($maintenanceDue ?? [])) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Vehicule cu Documente în Expirare -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-file-contract me-2"></i>
                            Documente în Expirare (30 zile)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($expiringDocuments)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Document</th>
                                            <th>Expirare</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiringDocuments as $doc): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($doc['registration_number'] ?? 'N/A') ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($doc['brand'] ?? '') ?> <?= htmlspecialchars($doc['model'] ?? '') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($doc['document_type'] ?? 'Necunoscut') ?></td>
                                                <td>
                                                    <span class="badge bg-warning">
                                                        <?= date('d.m.Y', strtotime($doc['expiry_date'] ?? '')) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>Niciun document nu expiră în următoarele 30 de zile</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Vehicule cu Întreținere Scadentă -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-danger">
                            <i class="fas fa-tools me-2"></i>
                            Întreținere Scadentă
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($maintenanceDue)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Vehicul</th>
                                            <th>Tip Service</th>
                                            <th>Scadența</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenanceDue as $maintenance): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($maintenance['registration_number'] ?? 'N/A') ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($maintenance['brand'] ?? '') ?> <?= htmlspecialchars($maintenance['model'] ?? '') ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($maintenance['maintenance_type'] ?? 'General') ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?= date('d.m.Y', strtotime($maintenance['due_date'] ?? '')) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>Toate vehiculele sunt la zi cu întreținerea</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistici pe Tipuri de Vehicule -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>
                            Distribuția pe Tipuri de Vehicule
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($vehiclesByType)): ?>
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="vehicleTypeChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                <p>Nu există date pentru afișarea graficului</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-list me-2"></i>
                            Tipuri de Vehicule
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($vehiclesByType)): ?>
                            <?php foreach ($vehiclesByType as $type): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?= htmlspecialchars($type['name'] ?? 'Necunoscut') ?></span>
                                    <span class="badge bg-primary"><?= $type['vehicle_count'] ?? 0 ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <p>Nu există tipuri de vehicule definite</p>
                                <a href="<?= BASE_URL ?>vehicle-types/add" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Adaugă Tip
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grafic pentru tipuri de vehicule
<?php if (!empty($vehiclesByType)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('vehicleTypeChart').getContext('2d');
    const vehicleTypeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($vehiclesByType as $type): ?>
                    '<?= addslashes($type['name'] ?? 'Necunoscut') ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($vehiclesByType as $type): ?>
                        <?= $type['vehicle_count'] ?? 0 ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#2e59d9', '#17a2b8', '#28a745'
                ],
                hoverBackgroundColor: [
                    '#2e59d9', '#17a673', '#2c9faf', '#f4b619', '#e02424',
                    '#717384', '#484956', '#2653d4', '#148b9a', '#1e7e34'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    displayColors: false,
                }
            },
        },
    });
});
<?php endif; ?>
</script>
