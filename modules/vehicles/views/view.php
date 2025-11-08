<?php
$pageTitle = 'Detalii Vehicul';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>
<div class="container-fluid">
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-car"></i> Detalii Vehicul</h1>
    <div>
      <a href="<?= BASE_URL ?>vehicles" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Inapoi</a>
      <a href="<?= BASE_URL ?>vehicles/edit?id=<?= $vehicle['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editeaza</a>
    </div>
  </div>
  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Informatii Vehicul</h6>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Nr. Inmatriculare:</strong>
              <p class="h5"><?= htmlspecialchars($vehicle['registration_number']) ?></p>
            </div>
            <div class="col-md-6">
              <strong>VIN:</strong>
              <p><?= htmlspecialchars($vehicle['vin_number'] ?? 'N/A') ?></p>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Marca:</strong>
              <p><?= htmlspecialchars($vehicle['brand']) ?></p>
            </div>
            <div class="col-md-6">
              <strong>Model:</strong>
              <p><?= htmlspecialchars($vehicle['model']) ?></p>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <strong>An:</strong>
              <p><?= htmlspecialchars($vehicle['year']) ?></p>
            </div>
            <div class="col-md-4">
              <strong>Culoare:</strong>
              <p><?= htmlspecialchars($vehicle['color'] ?? 'N/A') ?></p>
            </div>
            <div class="col-md-4">
              <strong>Status:</strong>
              <?php
              $statusMap = ['active' => 'Activ', 'inactive' => 'Inactiv', 'maintenance' => 'Service'];
              $statusClass = ['active' => 'success', 'inactive' => 'secondary', 'maintenance' => 'warning'];
              ?>
              <p><span class="badge bg-<?= $statusClass[$vehicle['status']] ?? 'secondary' ?>"><?= $statusMap[$vehicle['status']] ?? $vehicle['status'] ?></span></p>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <strong>Combustibil:</strong>
              <?php
              $fuelMap = ['petrol' => 'Benzina', 'diesel' => 'Motorina', 'electric' => 'Electric', 'hybrid' => 'Hibrid', 'gas' => 'GPL'];
              ?>
              <p><?= $fuelMap[$vehicle['fuel_type']] ?? $vehicle['fuel_type'] ?></p>
            </div>
            <div class="col-md-4">
              <strong>Kilometraj:</strong>
              <p><?= number_format($vehicle['current_mileage'], 0, ',', '.') ?> km</p>
            </div>
            <div class="col-md-4">
              <strong>Motor:</strong>
              <p><?= $vehicle['engine_capacity'] ? $vehicle['engine_capacity'] . ' L' : 'N/A' ?></p>
            </div>
          </div>
          <?php if ($vehicle['purchase_price'] || $vehicle['purchase_date']): ?>
            <hr>
            <div class="row mb-3">
              <?php if ($vehicle['purchase_price']): ?>
                <div class="col-md-6">
                  <strong>Pret Achizitie:</strong>
                  <p><?= number_format($vehicle['purchase_price'], 2, ',', '.') ?> RON</p>
                </div>
              <?php endif; ?>
              <?php if ($vehicle['purchase_date']): ?>
                <div class="col-md-6">
                  <strong>Data Achizitiei:</strong>
                  <p><?= date('d.m.Y', strtotime($vehicle['purchase_date'])) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($vehicle['notes'])): ?>
            <hr>
            <strong>Notite:</strong>
            <p><?= nl2br(htmlspecialchars($vehicle['notes'])) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-info">Actiuni Rapide</h6>
        </div>
        <div class="card-body">
          <a href="<?= BASE_URL ?>maintenance/add?vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
            <i class="fas fa-wrench"></i> Intretinere
          </a>
          <a href="<?= BASE_URL ?>fuel/add?vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-success btn-sm w-100 mb-2">
            <i class="fas fa-gas-pump"></i> Combustibil
          </a>
          <a href="<?= BASE_URL ?>documents/add?vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-outline-info btn-sm w-100 mb-2">
            <i class="fas fa-file-alt"></i> Document
          </a>
        </div>
      </div>
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-secondary">Statistici</h6>
        </div>
        <div class="card-body text-center">
          <p class="text-muted">Statistici disponibile curand</p>
        </div>
      </div>
    </div>
  </div>
</div>
