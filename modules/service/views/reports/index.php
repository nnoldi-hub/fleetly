<?php
/**
 * View: Dashboard Rapoarte Service
 */
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-line"></i> Rapoarte Service</h2>
            <p class="text-muted"><?= htmlspecialchars($service['name'] ?? 'Service Intern') ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>service/workshop" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Înapoi la Atelier
            </a>
        </div>
    </div>

    <!-- Selector perioadă -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Aplică
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grid rapoarte disponibile -->
    <div class="row">
        <!-- Rentabilitate Service -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-money-bill-wave fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Rentabilitate Service</h5>
                    <p class="card-text text-muted">
                        Analiză venituri, costuri, profit și evoluție în timp
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/profitability?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>

        <!-- Costuri pe Vehicul -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-car fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Costuri pe Vehicul</h5>
                    <p class="card-text text-muted">
                        Analiza costurilor de întreținere per vehicul din flotă
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/vehicle-costs?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>

        <!-- Performanță Mecanici -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-check fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">Performanță Mecanici</h5>
                    <p class="card-text text-muted">
                        Statistici productivitate și eficiență mecanici
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/mechanic-performance?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-info">
                        <i class="fas fa-award"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>

        <!-- Timpi de Lucru -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-clock fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title">Timpi de Lucru</h5>
                    <p class="card-text text-muted">
                        Analiza timpilor estimați vs reali și întârzieri
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/work-times?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-warning">
                        <i class="fas fa-stopwatch"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistici Piese -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-pie fa-3x text-danger"></i>
                    </div>
                    <h5 class="card-title">Statistici Piese</h5>
                    <p class="card-text text-muted">
                        Analiza consumului de piese și inventory
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/parts-stats?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-danger">
                        <i class="fas fa-chart-pie"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>

        <!-- Raport Activitate -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-tasks fa-3x text-dark"></i>
                    </div>
                    <h5 class="card-title">Raport Activitate</h5>
                    <p class="card-text text-muted">
                        Istoric complet activități și modificări
                    </p>
                    <a href="<?= ROUTE_BASE ?>service/reports/activity-log?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                       class="btn btn-dark">
                        <i class="fas fa-history"></i> Vezi Raport
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15) !important;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
</style>
