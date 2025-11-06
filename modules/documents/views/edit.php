<?php
// Expect: $document, $vehicles, $documentTypes
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="fas fa-edit text-primary me-2"></i>
      Editează Document
    </h1>
    <a href="<?= BASE_URL ?>documents/view?id=<?= (int)$document['id'] ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> Înapoi la detalii
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong>Erori:</strong>
      <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i>
      <?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="fas fa-file-alt me-2"></i>
            Informații Document
          </h5>
        </div>
        <div class="card-body">
          <form action="<?= BASE_URL ?>documents/edit?id=<?= (int)$document['id'] ?>" method="POST" enctype="multipart/form-data" id="editDocumentForm">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label required" for="document_type">
                  <i class="fas fa-tags me-1"></i> Tip Document
                </label>
                <select class="form-select" name="document_type" id="document_type" required>
                  <?php foreach ($documentTypes as $key => $value): ?>
                    <option value="<?= $key ?>" <?= ($document['document_type'] == $key ? 'selected' : '') ?>><?= htmlspecialchars($value) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required" for="vehicle_id">
                  <i class="fas fa-car me-1"></i> Vehicul
                </label>
                <select class="form-select" name="vehicle_id" id="vehicle_id" required>
                  <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= $vehicle['id'] ?>" <?= ($document['vehicle_id'] == $vehicle['id'] ? 'selected' : '') ?>>
                      <?= htmlspecialchars($vehicle['registration_number']) ?> - <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label required" for="document_number"><i class="fas fa-hashtag me-1"></i> Număr Document</label>
                <input type="text" class="form-control" id="document_number" name="document_number" value="<?= htmlspecialchars($document['document_number'] ?? '') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required" for="provider"><i class="fas fa-building me-1"></i> Furnizor/Emitent</label>
                <input type="text" class="form-control" id="provider" name="provider" value="<?= htmlspecialchars($document['provider'] ?? '') ?>" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label required" for="issue_date"><i class="fas fa-calendar-alt me-1"></i> Data Emitere</label>
                <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?= htmlspecialchars($document['issue_date'] ?? '') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required" for="expiry_date"><i class="fas fa-calendar-times me-1"></i> Data Expirare</label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars($document['expiry_date'] ?? '') ?>" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="cost"><i class="fas fa-money-bill-wave me-1"></i> Cost (RON)</label>
                <div class="input-group">
                  <input type="number" step="0.01" class="form-control" id="cost" name="cost" value="<?= htmlspecialchars($document['cost'] ?? '') ?>" placeholder="0.00">
                  <span class="input-group-text">RON</span>
                </div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label required" for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                <select class="form-select" id="status" name="status" required>
                  <option value="active" <?= ($document['status'] === 'active' ? 'selected' : '') ?>>Activ</option>
                  <option value="expired" <?= ($document['status'] === 'expired' ? 'selected' : '') ?>>Expirat</option>
                  <option value="suspended" <?= ($document['status'] === 'suspended' ? 'selected' : '') ?>>Suspendat</option>
                  <option value="cancelled" <?= ($document['status'] === 'cancelled' ? 'selected' : '') ?>>Anulat</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="reminder_days"><i class="fas fa-bell me-1"></i> Notificare cu (zile înainte)</label>
                <input type="number" class="form-control" id="reminder_days" name="reminder_days" value="<?= htmlspecialchars($document['reminder_days'] ?? '30') ?>" min="1" max="365">
              </div>

              <div class="col-12 mb-3">
                <label class="form-label" for="document_file"><i class="fas fa-file-upload me-1"></i> Încarcă Document nou (PDF, JPG, PNG)</label>
                <input type="file" class="form-control" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($document['file_path'])): ?>
                  <div class="form-text">
                    Fișier existent: <a target="_blank" href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($document['file_path']) ?>">descarcă</a>. Dacă alegi un fișier nou, acesta îl va înlocui pe cel existent.
                  </div>
                <?php endif; ?>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label" for="notes"><i class="fas fa-sticky-note me-1"></i> Observații</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($document['notes'] ?? '') ?></textarea>
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="<?= BASE_URL ?>documents/view?id=<?= (int)$document['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i> Anulează
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Salvează modificările
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informații</h6>
        </div>
        <div class="card-body">
          <ul class="small mb-0">
            <li>Dacă încarci un fișier nou, cel vechi va fi înlocuit.</li>
            <li>Asigură-te că data expirării este după data emiterii.</li>
            <li>Notificările se trimit automat cu nr. de zile selectat.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const issue = document.getElementById('issue_date');
  const expiry = document.getElementById('expiry_date');
  function validateDates(){
    if (issue.value && expiry.value && new Date(expiry.value) <= new Date(issue.value)) {
      expiry.setCustomValidity('Data expirării trebuie să fie după data emiterii');
    } else {
      expiry.setCustomValidity('');
    }
  }
  issue.addEventListener('change', validateDates);
  expiry.addEventListener('change', validateDates);
})();
</script>
<!-- View template only: header/footer are included by Controller::render() -->
