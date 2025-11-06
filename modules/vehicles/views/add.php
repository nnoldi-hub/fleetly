<?php
// modules/vehicles/views/add.php
$pageTitle = 'Adaugă Vehicul Nou';

// Tipurile de vehicule vin din controller
$vehicleTypes = $vehicleTypes ?? [];
// Preferă datele primite direct de la controller
$errors = isset($errors) ? $errors : ($_SESSION['errors'] ?? []);
$success = $_SESSION['success'] ?? '';
$formData = isset($formData) ? $formData : ($_SESSION['form_data'] ?? []);
if (!isset($errors)) { unset($_SESSION['errors']); }
if (!isset($formData)) { unset($_SESSION['form_data']); }
unset($_SESSION['success']);
?>

<div class="container-fluid">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>vehicles">Vehicule</a></li>
      <li class="breadcrumb-item active">Adaugă Vehicul</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="fas fa-plus-circle text-primary me-2"></i>
      Adaugă Vehicul Nou
    </h1>
    <a href="<?= BASE_URL ?>vehicles" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> Înapoi la Listă
    </a>
  </div>

  <!-- Mesaje -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <h6><i class="fas fa-exclamation-triangle"></i> Erori de validare:</h6>
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars(is_string($err) ? $err : json_encode($err)) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>

  <?php $limitReached = $limitReached ?? false; $usedVehicles = $usedVehicles ?? 0; $maxVehicles = $maxVehicles ?? 0; ?>
  <?php if ($limitReached): ?>
    <div class="alert alert-warning alert-permanent">
      Ati atins limita planului pentru vehicule (<?= (int)$usedVehicles ?> / <?= (int)$maxVehicles ?>). Nu puteti adauga un nou vehicul.
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-car"></i> Informații Vehicul
          </h6>
        </div>
        <div class="card-body">
          <form method="post" action="<?= BASE_URL ?>vehicles/store">
            <!-- Informații Generale -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="registration_number" class="form-label">Număr Înmatriculare <span class="text-danger">*</span></label>
                  <input type="text" class="form-control <?= !empty($errors['registration_number']) ? 'is-invalid' : '' ?>" id="registration_number" name="registration_number" value="<?= htmlspecialchars($formData['registration_number'] ?? '') ?>" required placeholder="ex: B123ABC">
                  <small class="form-text text-muted">Numărul de înmatriculare al vehiculului</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="vin_number" class="form-label">Număr VIN</label>
                  <input type="text" class="form-control" id="vin_number" name="vin_number" value="<?= htmlspecialchars($formData['vin_number'] ?? '') ?>" maxlength="17" placeholder="17 caractere">
                  <small class="form-text text-muted">Numărul de identificare al vehiculului (opțional)</small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="brand" class="form-label">Marca <span class="text-danger">*</span></label>
                  <input type="text" class="form-control <?= !empty($errors['brand']) ? 'is-invalid' : '' ?>" id="brand" name="brand" value="<?= htmlspecialchars($formData['brand'] ?? '') ?>" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                  <input type="text" class="form-control <?= !empty($errors['model']) ? 'is-invalid' : '' ?>" id="model" name="model" value="<?= htmlspecialchars($formData['model'] ?? '') ?>" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="year" class="form-label">An Fabricație <span class="text-danger">*</span></label>
                  <input type="number" class="form-control <?= !empty($errors['year']) ? 'is-invalid' : '' ?>" id="year" name="year" value="<?= htmlspecialchars($formData['year'] ?? '') ?>" min="1900" max="<?= date('Y') + 1 ?>" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="vehicle_type_id" class="form-label">Tip Vehicul <span class="text-danger">*</span></label>
                  <select class="form-control <?= !empty($errors['vehicle_type_id']) ? 'is-invalid' : '' ?>" id="vehicle_type_id" name="vehicle_type_id" required>
                    <option value="">Selectează tipul vehiculului</option>
                    <?php foreach ($vehicleTypes as $type): ?>
                      <option value="<?= $type['id'] ?>" <?= (($formData['vehicle_type_id'] ?? '') == $type['id']) ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="fuel_type" class="form-label">Tip Combustibil <span class="text-danger">*</span></label>
                  <select class="form-control" id="fuel_type" name="fuel_type" required>
                    <?php $ft = $formData['fuel_type'] ?? ''; ?>
                    <option value="">Selectează combustibilul</option>
                    <option value="benzina" <?= $ft==='benzina'?'selected':'' ?>>Benzină</option>
                    <option value="motorina" <?= $ft==='motorina'?'selected':'' ?>>Motorină</option>
                    <option value="electric" <?= $ft==='electric'?'selected':'' ?>>Electric</option>
                    <option value="hibrid" <?= $ft==='hibrid'?'selected':'' ?>>Hibrid</option>
                    <option value="gpl" <?= $ft==='gpl'?'selected':'' ?>>GPL</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="color" class="form-label">Culoare</label>
                  <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($formData['color'] ?? '') ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="status" class="form-label">Status</label>
                  <?php $st = $formData['status'] ?? 'active'; ?>
                  <select class="form-control" id="status" name="status">
                    <option value="active" <?= $st==='active'?'selected':'' ?>>Activ</option>
                    <option value="inactive" <?= $st==='inactive'?'selected':'' ?>>Inactiv</option>
                    <option value="maintenance" <?= $st==='maintenance'?'selected':'' ?>>În întreținere</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="mileage" class="form-label">Kilometraj Actual</label>
                  <input type="number" class="form-control" id="mileage" name="mileage" value="<?= htmlspecialchars($formData['mileage'] ?? '0') ?>" min="0">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="engine_capacity" class="form-label">Capacitate Motor (L)</label>
                  <input type="number" class="form-control" id="engine_capacity" name="engine_capacity" value="<?= htmlspecialchars($formData['engine_capacity'] ?? '') ?>" step="0.1" min="0">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="purchase_price" class="form-label">Preț Achiziție (RON)</label>
                  <input type="number" class="form-control" id="purchase_price" name="purchase_price" value="<?= htmlspecialchars($formData['purchase_price'] ?? '') ?>" step="0.01" min="0">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="purchase_date" class="form-label">Data Achiziției</label>
              <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= htmlspecialchars($formData['purchase_date'] ?? '') ?>">
            </div>

            <hr>
            <div class="d-flex justify-content-between">
              <a href="<?= BASE_URL ?>vehicles" class="btn btn-secondary"><i class="fas fa-times"></i> Anulează</a>
              <button type="submit" class="btn btn-primary" <?= $limitReached ? 'disabled' : '' ?>><i class="fas fa-save"></i> Salvează Vehicul</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-info-circle"></i> Informații Utile</h6>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <ul class="mb-0">
              <li>Completează câmpurile marcate cu *</li>
              <li>VIN-ul are exact 17 caractere</li>
              <li>Kilometrajul trebuie să fie actual</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
