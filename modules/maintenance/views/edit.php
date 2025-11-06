<?php
$pageTitle = 'Editeaza Intretinere';
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);
?>
<div class="container-fluid">
  <h1 class="h3 mb-4"><i class="fas fa-wrench"></i> Editeaza Intretinere</h1>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
    </div>
  <?php endif; ?>
  <div class="card shadow">
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($maintenance->id ?? '') ?>">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Vehicul *</label>
            <select class="form-control" name="vehicle_id" required>
              <option value="">Selecteaza vehicul</option>
              <?php foreach ($vehicles as $v): ?>
                <option value="<?= $v['id'] ?>" <?= (($formData['vehicle_id'] ?? $maintenance->vehicle_id ?? '') == $v['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($v['registration_number']) ?> - <?= htmlspecialchars($v['brand']) ?> <?= htmlspecialchars($v['model']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label>Tip Intretinere *</label>
            <select class="form-control" name="maintenance_type" required>
              <?php $typeSel = $formData['maintenance_type'] ?? $maintenance->maintenance_type ?? ''; ?>
              <option value="">Selecteaza tip</option>
              <option value="routine" <?= $typeSel==='routine'?'selected':'' ?>>Rutina</option>
              <option value="repair" <?= $typeSel==='repair'?'selected':'' ?>>Reparatie</option>
              <option value="inspection" <?= $typeSel==='inspection'?'selected':'' ?>>Inspectie</option>
              <option value="tire_change" <?= $typeSel==='tire_change'?'selected':'' ?>>Schimb anvelope</option>
              <option value="oil_change" <?= $typeSel==='oil_change'?'selected':'' ?>>Schimb ulei</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Data *</label>
            <?php $dateVal = $formData['scheduled_date'] ?? ($maintenance->service_date ?? ''); ?>
            <input type="date" class="form-control" name="scheduled_date" required value="<?= htmlspecialchars($dateVal) ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label>Prioritate *</label>
            <?php $prio = $formData['priority'] ?? $maintenance->priority ?? 'medium'; ?>
            <select class="form-control" name="priority" required>
              <option value="low" <?= $prio==='low'?'selected':'' ?>>Scazuta</option>
              <option value="medium" <?= $prio==='medium'?'selected':'' ?>>Medie</option>
              <option value="high" <?= $prio==='high'?'selected':'' ?>>Ridicata</option>
              <option value="urgent" <?= $prio==='urgent'?'selected':'' ?>>Urgenta</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label>Descriere</label>
          <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($formData['description'] ?? ($maintenance->description ?? '')) ?></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Cost (RON)</label>
            <input type="number" step="0.01" class="form-control" name="estimated_cost" value="<?= htmlspecialchars($formData['estimated_cost'] ?? ($maintenance->cost ?? '')) ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label>Kilometraj</label>
            <input type="number" class="form-control" name="odometer_reading" value="<?= htmlspecialchars($formData['odometer_reading'] ?? ($maintenance->mileage_at_service ?? '')) ?>">
          </div>
        </div>
        <hr>
        <a href="<?= BASE_URL ?>maintenance" class="btn btn-secondary">Anuleaza</a>
        <button type="submit" class="btn btn-primary">Salveaza</button>
      </form>
    </div>
  </div>
</div>
