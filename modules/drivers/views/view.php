<?php
// Expect: $driver, $assignedVehicle, $daysUntilExpiry, $performance, $recentFuel, $recentMaintenance
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-id-card text-primary me-2"></i> Detalii Șofer</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>drivers" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Înapoi la listă</a>
      <a href="<?= BASE_URL ?>drivers/edit?id=<?= (int)$driver['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i> Editează</a>
      <button class="btn btn-danger" id="deleteDriverBtn"><i class="fas fa-trash me-1"></i> Șterge</button>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Informații generale</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><div class="text-muted small">Nume</div><div class="fw-semibold"><?= htmlspecialchars($driver['name']) ?></div></div>
            <div class="col-md-6"><div class="text-muted small">Telefon</div><div><?= htmlspecialchars($driver['phone'] ?? '-') ?></div></div>
            <div class="col-md-6"><div class="text-muted small">Email</div><div><?= htmlspecialchars($driver['email'] ?? '-') ?></div></div>
            <div class="col-md-6"><div class="text-muted small">Status</div><div><span class="badge <?= $driver['status']==='active'?'bg-success':($driver['status']==='suspended'?'bg-warning text-dark':'bg-secondary') ?>"><?= htmlspecialchars(ucfirst($driver['status'])) ?></span></div></div>
            <div class="col-md-6"><div class="text-muted small">Permis</div><div><?= htmlspecialchars($driver['license_number'] ?? '-') ?> (<?= htmlspecialchars($driver['license_category'] ?? '-') ?>)</div></div>
            <div class="col-md-6"><div class="text-muted small">Expirare permis</div><div>
              <?= htmlspecialchars($driver['license_expiry_date'] ?? '-') ?>
              <?php if ($daysUntilExpiry !== null): ?>
                <span class="badge ms-2 <?= ($daysUntilExpiry < 0 ? 'bg-danger' : ($daysUntilExpiry <= 30 ? 'bg-warning text-dark' : 'bg-success')) ?>">
                  <?= $daysUntilExpiry < 0 ? ('Expirat de ' . abs($daysUntilExpiry) . ' zile') : ('Expiră în ' . $daysUntilExpiry . ' zile') ?>
                </span>
              <?php endif; ?>
            </div></div>
            <div class="col-md-6"><div class="text-muted small">Angajat din</div><div><?= htmlspecialchars($driver['hire_date'] ?? '-') ?></div></div>
            <div class="col-md-6"><div class="text-muted small">Adresă</div><div><?= htmlspecialchars($driver['address'] ?? '-') ?></div></div>
            <div class="col-12"><div class="text-muted small">Note</div><div><?= nl2br(htmlspecialchars($driver['notes'] ?? '—')) ?></div></div>
          </div>
        </div>
      </div>

      <?php if (!empty($recentFuel)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-gas-pump me-2"></i> Alimentări recente</h6></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Vehicul</th><th>Litri</th><th>Cost</th></tr></thead>
            <tbody>
              <?php foreach ($recentFuel as $f): ?>
              <tr>
                <td><?= htmlspecialchars($f['fuel_date'] ?? '') ?></td>
                <td><?= htmlspecialchars($assignedVehicle['registration_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($f['liters'] ?? '') ?></td>
                <td><?= htmlspecialchars(number_format((float)($f['total_cost'] ?? 0), 2)) ?> RON</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($recentMaintenance)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-wrench me-2"></i> Ultimele întrețineri</h6></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Vehicul</th><th>Descriere</th><th>Cost</th></tr></thead>
            <tbody>
              <?php foreach ($recentMaintenance as $m): ?>
              <tr>
                <td><?= htmlspecialchars($m['service_date'] ?? '') ?></td>
                <td><?= htmlspecialchars($assignedVehicle['registration_number'] ?? '-') ?></td>
                <td><?= htmlspecialchars($m['description'] ?? '') ?></td>
                <td><?= htmlspecialchars(number_format((float)($m['cost'] ?? 0), 2)) ?> RON</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-car me-2"></i> Vehicul asignat</h6></div>
        <div class="card-body">
          <?php if ($assignedVehicle): ?>
            <div class="mb-2 fw-semibold">
              <a href="<?= BASE_URL ?>vehicles/view?id=<?= (int)$assignedVehicle['id'] ?>">
                <?= htmlspecialchars($assignedVehicle['registration_number']) ?> — <?= htmlspecialchars($assignedVehicle['brand'].' '.$assignedVehicle['model']) ?>
              </a>
            </div>
          <?php else: ?>
            <div class="text-muted">Niciun vehicul asignat.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteDriverBtn')?.addEventListener('click', function(){
  if(!confirm('Sigur ștergi acest șofer?')) return;
  fetch('<?= BASE_URL ?>drivers/delete', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({id: '<?= (int)$driver['id'] ?>'})})
    .then(r=>r.json()).then(d=>{ if(d.success){ window.location.href = '<?= BASE_URL ?>drivers'; } else { alert(d.error||'Eroare'); } });
});
</script>
<!-- View template only: header/footer are included by Controller::render() -->
