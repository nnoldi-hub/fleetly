<?php
// Expect: $document, $vehicle, $daysUntilExpiry, $renewalHistory
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

function humanStatus($status) {
    $map = [
        'active' => 'Activ',
        'expired' => 'Expirat',
        'cancelled' => 'Anulat',
        'suspended' => 'Suspendat'
    ];
    return $map[$status] ?? $status;
}

function docTypeName($type) {
    $map = [
        'insurance_rca' => 'Asigurare RCA',
        'insurance_casco' => 'Asigurare CASCO',
        'itp' => 'ITP',
        'vignette' => 'Rovinietă',
        'registration' => 'Certificat Înmatriculare',
        'authorization' => 'Autorizație Transport',
        'other' => 'Altele'
    ];
    return $map[$type] ?? $type;
}
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="fas fa-file-alt text-primary me-2"></i>
      Detalii Document
    </h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>documents" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Înapoi la Liste
      </a>
      <a href="<?= BASE_URL ?>documents/edit?id=<?= (int)$document['id'] ?>" class="btn btn-primary">
        <i class="fas fa-edit me-1"></i> Editează
      </a>
      <button class="btn btn-danger" id="deleteDocBtn">
        <i class="fas fa-trash-alt me-1"></i> Șterge
      </button>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">Informații document</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Vehicul</div>
              <div>
                <a href="<?= BASE_URL ?>vehicles/view?id=<?= (int)$vehicle['id'] ?>">
                  <?= htmlspecialchars($vehicle['registration_number']) ?> — <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                </a>
              </div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Tip document</div>
              <div><?= htmlspecialchars(docTypeName($document['document_type'])) ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Număr document</div>
              <div><?= htmlspecialchars($document['document_number'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Furnizor/Emitent</div>
              <div><?= htmlspecialchars($document['provider'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Data emiterii</div>
              <div><?= htmlspecialchars($document['issue_date'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Data expirării</div>
              <div>
                <?= htmlspecialchars($document['expiry_date'] ?? '') ?>
                <?php if ($daysUntilExpiry !== null): ?>
                  <span class="badge ms-2 <?= ($daysUntilExpiry < 0 ? 'bg-danger' : ($daysUntilExpiry <= 30 ? 'bg-warning text-dark' : 'bg-success')) ?>">
                    <?= $daysUntilExpiry < 0 ? ('Expirat de ' . abs($daysUntilExpiry) . ' zile') : ('Expiră în ' . $daysUntilExpiry . ' zile') ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Cost</div>
              <div><?= htmlspecialchars(number_format((float)($document['cost'] ?? 0), 2)) ?> RON</div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Status</div>
              <div>
                <span class="badge <?= ($document['status'] === 'active' ? 'bg-success' : ($document['status'] === 'expired' ? 'bg-danger' : 'bg-secondary')) ?>">
                  <?= htmlspecialchars(humanStatus($document['status'])) ?>
                </span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="fw-semibold text-muted">Zile reminder</div>
              <div><?= htmlspecialchars($document['reminder_days'] ?? '') ?></div>
            </div>
            <div class="col-12">
              <div class="fw-semibold text-muted">Observații</div>
              <div><?= nl2br(htmlspecialchars($document['notes'] ?? '—')) ?></div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($renewalHistory)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="fas fa-history me-2"></i>Istoric reînnoiri</h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Număr</th>
                  <th>Emitent</th>
                  <th>Emis</th>
                  <th>Expiră</th>
                  <th>Cost</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($renewalHistory as $h): ?>
                <tr>
                  <td><?= htmlspecialchars($h['document_number'] ?? '') ?></td>
                  <td><?= htmlspecialchars($h['provider'] ?? '') ?></td>
                  <td><?= htmlspecialchars($h['issue_date'] ?? '') ?></td>
                  <td><?= htmlspecialchars($h['expiry_date'] ?? '') ?></td>
                  <td><?= htmlspecialchars(number_format((float)($h['cost'] ?? 0), 2)) ?> RON</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Fișier atașat</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($document['file_path'])): ?>
            <div class="mb-3">
              <a class="btn btn-outline-primary btn-sm" target="_blank" href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($document['file_path']) ?>">
                <i class="fas fa-download me-1"></i> Descarcă fișier
              </a>
            </div>
            <?php $ext = strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
              <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($document['file_path']) ?>" class="img-fluid rounded border" alt="Preview document">
            <?php elseif ($ext === 'pdf'): ?>
              <iframe src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($document['file_path']) ?>" style="width:100%;height:480px;border:1px solid #eee;border-radius:.5rem;"></iframe>
            <?php else: ?>
              <div class="text-muted small">Formatul nu poate fi previzualizat. Folosește butonul de descărcare.</div>
            <?php endif; ?>
          <?php else: ?>
            <div class="text-muted">Nu există fișier atașat.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteDocBtn')?.addEventListener('click', function() {
  if (!confirm('Sigur dorești să ștergi acest document?')) return;
  fetch('<?= BASE_URL ?>documents/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id: '<?= (int)$document['id'] ?>' })
  }).then(r => r.json()).then(data => {
    if (data.success) {
      window.location.href = '<?= BASE_URL ?>documents';
    } else {
      alert(data.error || 'Eroare la ștergere');
    }
  }).catch(() => alert('Eroare la ștergere'));
});
</script>
<!-- View template only: header/footer are included by Controller::render() -->
