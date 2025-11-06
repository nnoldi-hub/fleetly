<?php
// modules/drivers/views/edit.php (template-only)
// Expect: $driver, $availableVehicles, $licenseCategories
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Helper safe value
$val = function($arr, $key){ return htmlspecialchars($arr[$key] ?? '', ENT_QUOTES, 'UTF-8'); };
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-user-edit text-primary me-2"></i> Editează Șofer</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>drivers/view?id=<?= (int)$driver['id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Înapoi</a>
      <a href="<?= BASE_URL ?>drivers" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i> Listă</a>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form action="<?= BASE_URL ?>drivers/edit?id=<?= (int)$driver['id'] ?>" method="POST" id="editDriverForm">
    <!-- Informații personale -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-user me-2"></i> Informații personale</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nume complet</label>
            <input type="text" class="form-control" name="name" required value="<?= $val($driver,'name') ?>" placeholder="ex: Popescu Ion">
          </div>
          <div class="col-md-6">
            <label class="form-label">Data nașterii</label>
            <input type="date" class="form-control" name="date_of_birth" value="<?= $val($driver,'date_of_birth') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefon</label>
            <input type="tel" class="form-control" name="phone" value="<?= $val($driver,'phone') ?>" placeholder="0723xxxxxx">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= $val($driver,'email') ?>" placeholder="email@exemplu.ro">
          </div>
          <div class="col-12">
            <label class="form-label">Adresă</label>
            <input type="text" class="form-control" name="address" value="<?= $val($driver,'address') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Permis de conducere -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-id-badge me-2"></i> Permis de conducere</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Număr permis</label>
            <input type="text" class="form-control" name="license_number" required value="<?= $val($driver,'license_number') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Categorie</label>
            <select class="form-select" name="license_category">
              <option value="">—</option>
              <?php foreach (($licenseCategories ?? []) as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= (($driver['license_category'] ?? '')===$key)?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Data obținerii</label>
            <input type="date" class="form-control" name="license_issue_date" value="<?= $val($driver,'license_issue_date') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Data expirării</label>
            <input type="date" class="form-control" name="license_expiry_date" value="<?= $val($driver,'license_expiry_date') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Angajare și alocare vehicul -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-briefcase me-2"></i> Angajare & Vehicul</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Data angajării</label>
            <input type="date" class="form-control" name="hire_date" value="<?= $val($driver,'hire_date') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <?php $status=$driver['status'] ?? 'active'; ?>
              <option value="active" <?= $status==='active'?'selected':'' ?>>Activ</option>
              <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactiv</option>
              <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspendat</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Vehicul asignat</label>
            <select class="form-select" name="assigned_vehicle_id">
              <option value="">— Niciun vehicul —</option>
              <?php foreach (($availableVehicles ?? []) as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= (($driver['assigned_vehicle_id'] ?? null)==$v['id'])?'selected':'' ?>>
                  <?= htmlspecialchars(($v['registration_number'] ?? '')) ?> <?= htmlspecialchars(($v['brand'] ?? '').' '.($v['model'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Note</label>
            <textarea class="form-control" name="notes" rows="3"><?= $val($driver,'notes') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>drivers/view?id=<?= (int)$driver['id'] ?>"><i class="fas fa-times me-1"></i> Anulează</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvează</button>
    </div>
  </form>
</div>

<script>
// Validare simplă: expirarea trebuie să fie după data obținerii
document.getElementById('editDriverForm')?.addEventListener('submit', function(e){
  const issue = this.querySelector('input[name="license_issue_date"]').value;
  const expiry = this.querySelector('input[name="license_expiry_date"]').value;
  if(issue && expiry && new Date(expiry) <= new Date(issue)){
    e.preventDefault();
    alert('Data expirării permisului trebuie să fie ulterioară datei obținerii.');
  }
});
</script>

<!-- View template only: header/footer are included by Controller::render() -->
