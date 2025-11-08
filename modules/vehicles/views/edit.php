<?php
$pageTitle = 'Editeaza Vehicul';
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? $vehicle;
unset($_SESSION['errors'], $_SESSION['form_data']);
?>
<div class="container-fluid">
  <h1 class="h3 mb-4"><i class="fas fa-edit"></i> Editeaza Vehicul: <?= htmlspecialchars($vehicle['registration_number']) ?></h1>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></div>
  <?php endif; ?>
  <div class="card shadow">
    <div class="card-body">
      <form method="post">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Numar Înmatriculare *</label>
            <input type="text" class="form-control" name="registration_number" value="<?= htmlspecialchars($formData['registration_number'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>VIN</label>
            <input type="text" class="form-control" name="vin_number" value="<?= htmlspecialchars($formData['vin_number'] ?? '') ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Marca *</label>
            <input type="text" class="form-control" name="brand" value="<?= htmlspecialchars($formData['brand'] ?? '') ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Model *</label>
            <input type="text" class="form-control" name="model" value="<?= htmlspecialchars($formData['model'] ?? '') ?>" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label>An *</label>
            <input type="number" class="form-control" name="year" value="<?= $formData['year'] ?? '' ?>" required>
          </div>
          <div class="col-md-4 mb-3">
            <label>Tip *</label>
            <select class="form-control" name="vehicle_type_id" required>
              <?php foreach ($vehicleTypes as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($formData['vehicle_type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label>Combustibil *</label>
            <select class="form-control" name="fuel_type" required>
              <option value="petrol" <?= ($formData['fuel_type'] ?? '') == 'petrol' ? 'selected' : '' ?>>Benzina</option>
              <option value="diesel" <?= ($formData['fuel_type'] ?? '') == 'diesel' ? 'selected' : '' ?>>Motorina</option>
              <option value="electric" <?= ($formData['fuel_type'] ?? '') == 'electric' ? 'selected' : '' ?>>Electric</option>
              <option value="hybrid" <?= ($formData['fuel_type'] ?? '') == 'hybrid' ? 'selected' : '' ?>>Hibrid</option>
              <option value="gas" <?= ($formData['fuel_type'] ?? '') == 'gas' ? 'selected' : '' ?>>GPL</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label>Culoare</label>
            <input type="text" class="form-control" name="color" value="<?= htmlspecialchars($formData['color'] ?? '') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label>Status</label>
            <select class="form-control" name="status">
              <option value="active" <?= ($formData['status'] ?? '') == 'active' ? 'selected' : '' ?>>Activ</option>
              <option value="inactive" <?= ($formData['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactiv</option>
              <option value="maintenance" <?= ($formData['status'] ?? '') == 'maintenance' ? 'selected' : '' ?>>Service</option>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label>Kilometraj</label>
            <input type="number" class="form-control" name="current_mileage" value="<?= $formData['current_mileage'] ?? '' ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label>Motor (L)</label>
            <input type="number" step="0.1" class="form-control" name="engine_capacity" value="<?= $formData['engine_capacity'] ?? '' ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Pre? (RON)</label>
            <input type="number" step="0.01" class="form-control" name="purchase_price" value="<?= $formData['purchase_price'] ?? '' ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label>Data Achizi?iei</label>
            <input type="date" class="form-control" name="purchase_date" value="<?= $formData['purchase_date'] ?? '' ?>">
          </div>
        </div>
        <div class="mb-3">
          <label>Noti?e</label>
          <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
        </div>
        <hr>
        <a href="<?= BASE_URL ?>vehicles/view?id=<?= $vehicle['id'] ?>" class="btn btn-secondary">Anuleaza</a>
        <button type="submit" class="btn btn-primary">Salveaza</button>
      </form>
    </div>
  </div>
</div>
