<?php
// modules/reports/views/index.php
// Dashboard rapoarte - romana fara diacritice
?>

<div class="container-fluid px-0">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">Rapoarte</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-chart-bar text-primary me-2"></i>Rapoarte</h1>
    </div>

    <!-- Statistici generale -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <i class="fas fa-car fa-2x text-primary mb-2"></i>
                    <h3 class="mb-0"><?= number_format($totalVehicles ?? 0, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-0">Vehicule active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <i class="fas fa-gas-pump fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?= number_format($totalFuelRecords ?? 0, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-0">Inregistrari combustibil</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <i class="fas fa-wrench fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0"><?= number_format($totalMaintenanceRecords ?? 0, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-0">Inregistrari mentenanta</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-2x text-info mb-2"></i>
                    <h3 class="mb-0"><?= number_format($totalInsuranceRecords ?? 0, 0, ',', '.') ?></h3>
                    <p class="text-muted mb-0">Polite asigurare</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rapoarte disponibile -->
    <div class="row g-3">
        <!-- Raport Flota -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="fas fa-car-side fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Raport flota</h5>
                            <small class="text-muted">Analiza completa a flotei</small>
                        </div>
                    </div>
                    <p class="card-text">Rezumat combustibil, mentenanta si costuri pe intreaga flota, intr-o perioada selectata.</p>
                    <a class="btn btn-primary" href="<?= BASE_URL ?>reports/fleet">
                        <i class="fas fa-folder-open me-1"></i> Deschide
                    </a>
                </div>
            </div>
        </div>

        <!-- Analiza Costuri -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                            <i class="fas fa-chart-line fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Analiza costuri</h5>
                            <small class="text-muted">Evolutia cheltuielilor</small>
                        </div>
                    </div>
                    <p class="card-text">Evolutia costurilor (combustibil, mentenanta, asigurari) pe perioade (lunar, saptamanal, anual).</p>
                    <a class="btn btn-primary" href="<?= BASE_URL ?>reports/costs">
                        <i class="fas fa-chart-pie me-1"></i> Analizeaza
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
