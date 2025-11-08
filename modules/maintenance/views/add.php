<?php
$pageTitle = 'Adauga Intretinere';
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);
?>
<div class="container-fluid">
  <h1 class="h3 mb-4"><i class="fas fa-wrench"></i> Adauga Intretinere</h1>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
    </div>
  <?php endif; ?>
  <div class="card shadow">
    <div class="card-body">
      <form method="post">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Vehicul *</label>
            <select class="form-control" name="vehicle_id" required>
              <option value="">Selecteaza vehicul</option>
              <?php foreach ($vehicles as $v): ?>
                <option value="<?= $v['id'] ?>" <?= (($formData['vehicle_id'] ?? $selectedVehicleId ?? '') == $v['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($v['registration_number']) ?> - <?= htmlspecialchars($v['brand']) ?> <?= htmlspecialchars($v['model']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label>Tip Intretinere *</label>
            <select class="form-control" name="maintenance_type" required>
              <option value="">Selecteaza tip</option>
              <option value="routine">Rutina</option>
              <option value="repair">Reparatie</option>
              <option value="inspection">Inspectie</option>
              <option value="tire_change">Schimb anvelope</option>
              <option value="oil_change">Schimb ulei</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Data Programata *</label>
            <input type="date" class="form-control" name="scheduled_date" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>Prioritate *</label>
            <select class="form-control" name="priority" required>
              <option value="low">Scazuta</option>
              <option value="medium" selected>Medie</option>
              <option value="high">Ridicata</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label>Descriere</label>
          <textarea class="form-control" name="description" rows="3"></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Cost Estimat (RON)</label>
            <input type="number" step="0.01" class="form-control" name="estimated_cost">
          </div>
          <div class="col-md-6 mb-3">
            <label>Kilometraj</label>
            <input type="number" class="form-control" name="odometer_reading">
          </div>
        </div>
        <hr>
        <a href="<?= BASE_URL ?>maintenance" class="btn btn-secondary">Anuleaza</a>
        <button type="submit" class="btn btn-primary">Salveaza</button>
      </form>
    </div>
  </div>
</div>
