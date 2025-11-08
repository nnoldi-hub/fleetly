<?php
// modules/reports/views/index.php (template-only)
// Expect: $totalVehicles, $totalFuelRecords, $totalMaintenanceRecords, $totalInsuranceRecords
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-chart-bar text-primary me-2"></i> Rapoarte</h1>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-primary h-100">
        <div class="card-body text-center">
          <div class="text-muted">Vehicule active</div>
          <div class="display-6 fw-bold text-primary"><?= (int)($totalVehicles ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-success h-100">
        <div class="card-body text-center">
          <div class="text-muted">Înregistrari combustibil</div>
          <div class="display-6 fw-bold text-success"><?= (int)($totalFuelRecords ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-warning h-100">
        <div class="card-body text-center">
          <div class="text-muted">Înregistrari mentenan?a</div>
          <div class="display-6 fw-bold text-warning"><?= (int)($totalMaintenanceRecords ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-secondary h-100">
        <div class="card-body text-center">
          <div class="text-muted">Poli?e asigurare</div>
          <div class="display-6 fw-bold text-secondary"><?= (int)($totalInsuranceRecords ?? 0) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-clipboard-list me-2"></i> Raport flota</h5>
          <p class="card-text text-muted">Rezumat combustibil, mentenan?a ?i costuri pe întreaga flota, într-o perioada selectata.</p>
          <a class="btn btn-primary" href="<?= BASE_URL ?>reports/fleet">Deschide</a>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-coins me-2"></i> Analiza costuri</h5>
          <p class="card-text text-muted">Evolu?ia costurilor (combustibil, mentenan?a, asigurari) pe perioade (lunar, saptamânal, anual).</p>
          <a class="btn btn-primary" href="<?= BASE_URL ?>reports/costs">Analizeaza</a>
        </div>
      </div>
    </div>
  </div>
</div>
