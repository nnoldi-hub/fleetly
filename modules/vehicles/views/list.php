<?php
// modules/vehicles/views/list.php
$pageTitle = 'Lista Vehicule';
$vehicles = $vehicles ?? [];
$vehicleTypes = $vehicleTypes ?? [];
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$search = $search ?? '';
$type_filter = $type_filter ?? '';
$status_filter = $status_filter ?? '';
$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="container-fluid">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"></a>Dashboard</a></li>
      <li class="breadcrumb-item active">Vehicule</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h3 mb-0">
      <i class="fas fa-car text-primary me-2"></i>
      Gestionare Vehicule
    </h1>
    <?php $limitReached = $limitReached ?? false; $usedVehicles = $usedVehicles ?? 0; $maxVehicles = $maxVehicles ?? 0; ?>
    <a href="<?= BASE_URL ?>vehicles/add" class="btn btn-primary <?= $limitReached ? 'disabled' : '' ?>" <?= $limitReached ? 'aria-disabled="true" tabindex="-1"' : '' ?> >
      <i class="fas fa-plus me-1"></i> Adaugă Vehicul
    </a>
  </div>

  <?php if ($maxVehicles > 0): ?>
    <div class="alert alert-info alert-permanent d-flex justify-content-between align-items-center mb-4">
      <div>
        <strong>Plan vehicule:</strong> <?= (int)$usedVehicles ?> / <?= (int)$maxVehicles ?> utilizate.
        <?php if ($limitReached): ?> <span class="text-danger ms-2">Limita atinsa: nu mai poti adauga vehicule.</span><?php endif; ?>
      </div>
      <?php if ($limitReached): ?>
        <a href="#" class="btn btn-sm btn-outline-secondary disabled" tabindex="-1" aria-disabled="true">Upgrade</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Mesaj de succes -->
  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Mesaj de eroare -->
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filtre -->
  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-filter"></i> Filtre
      </h6>
    </div>
    <div class="card-body">
      <form method="get" action="<?= BASE_URL ?>vehicles" class="row g-3">
        <div class="col-md-4">
          <label for="search" class="form-label">Căutare</label>
          <input type="text" class="form-control" id="search" name="search" 
                 value="<?= htmlspecialchars($search) ?>" 
                 placeholder="Nr. înmatriculare, marcă, model...">
        </div>
        <div class="col-md-3">
          <label for="type" class="form-label">Tip Vehicul</label>
          <select class="form-control" id="type" name="type">
            <option value="">Toate tipurile</option>
            <?php foreach ($vehicleTypes as $type): ?>
              <option value="<?= $type['id'] ?>" <?= $type_filter == $type['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($type['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-control" id="status" name="status">
            <option value="">Toate statusurile</option>
            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Activ</option>
            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactiv</option>
            <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>În întreținere</option>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search"></i> Caută
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista Vehicule -->
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-list"></i> Lista Vehicule (<?= count($vehicles) ?> rezultate)
      </h6>
      <div class="btn-group">
        <button onclick="exportData('csv')" class="btn btn-sm btn-success">
          <i class="fas fa-file-csv"></i> Export CSV
        </button>
        <button onclick="exportData('pdf')" class="btn btn-sm btn-danger">
          <i class="fas fa-file-pdf"></i> Export PDF
        </button>
      </div>
    </div>
    <div class="card-body">
      <?php if (empty($vehicles)): ?>
        <div class="alert alert-info text-center">
          <i class="fas fa-info-circle"></i> Nu există vehicule înregistrate.
          <a href="<?= BASE_URL ?>vehicles/add" class="alert-link">Adaugă primul vehicul</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-light">
              <tr>
                <th>Nr. Înmatriculare</th>
                <th>Marca</th>
                <th>Model</th>
                <th>An</th>
                <th>Tip</th>
                <th>Combustibil</th>
                <th>Kilometraj</th>
                <th>Status</th>
                <th class="text-center">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($vehicle['registration_number']) ?></strong></td>
                  <td><?= htmlspecialchars($vehicle['brand']) ?></td>
                  <td><?= htmlspecialchars($vehicle['model']) ?></td>
                  <td><?= htmlspecialchars($vehicle['year']) ?></td>
                  <td>
                    <?php
                    // Găsim tipul vehiculului
                    $typeName = 'N/A';
                    foreach ($vehicleTypes as $type) {
                      if ($type['id'] == $vehicle['vehicle_type_id']) {
                        $typeName = $type['name'];
                        break;
                      }
                    }
                    echo htmlspecialchars($typeName);
                    ?>
                  </td>
                  <td>
                    <?php
                    $fuelTypes = [
                      'petrol' => 'Benzină',
                      'diesel' => 'Motorină',
                      'electric' => 'Electric',
                      'hybrid' => 'Hibrid',
                      'gas' => 'GPL'
                    ];
                    echo $fuelTypes[$vehicle['fuel_type']] ?? $vehicle['fuel_type'];
                    ?>
                  </td>
                  <td><?= number_format($vehicle['current_mileage'], 0, ',', '.') ?> km</td>
                  <td>
                    <?php
                    $statusClass = [
                      'active' => 'success',
                      'inactive' => 'secondary',
                      'maintenance' => 'warning'
                    ];
                    $statusText = [
                      'active' => 'Activ',
                      'inactive' => 'Inactiv',
                      'maintenance' => 'În service'
                    ];
                    $class = $statusClass[$vehicle['status']] ?? 'secondary';
                    $text = $statusText[$vehicle['status']] ?? $vehicle['status'];
                    ?>
                    <span class="badge bg-<?= $class ?>"><?= $text ?></span>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <a href="<?= BASE_URL ?>vehicles/view?id=<?= $vehicle['id'] ?>" 
                         class="btn btn-sm btn-info" title="Vizualizare">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="<?= BASE_URL ?>vehicles/edit?id=<?= $vehicle['id'] ?>" 
                         class="btn btn-sm btn-warning" title="Editare">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form method="post" action="<?= BASE_URL ?>vehicles/delete" style="display:inline;" onsubmit="return confirm('Sigur vrei să ștergi vehiculul <?= htmlspecialchars($vehicle['registration_number']) ?>?');">
                        <input type="hidden" name="id" value="<?= $vehicle['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger" title="Ștergere">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Paginare -->
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Navigare pagini">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>vehicles?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type_filter) ?>&status=<?= urlencode($status_filter) ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                  <a class="page-link" href="<?= BASE_URL ?>vehicles?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type_filter) ?>&status=<?= urlencode($status_filter) ?>">
                    <?= $i ?>
                  </a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>vehicles?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type_filter) ?>&status=<?= urlencode($status_filter) ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function deleteVehicle(vehicleId, registrationNumber) {
  if (confirm('Sigur doriți să ștergeți vehiculul "' + registrationNumber + '"?\n\nAtenție: Dacă vehiculul are înregistrări asociate, va fi dezactivat în loc de șters.')) {
    window.location.href = '<?= BASE_URL ?>vehicles/delete?id=' + vehicleId;
  }
}

function exportData(format) {
  const search = document.getElementById('search').value;
  const type = document.getElementById('type').value;
  const status = document.getElementById('status').value;
  
  let url = '<?= BASE_URL ?>vehicles/export?';
  if (search) url += 'search=' + encodeURIComponent(search) + '&';
  if (type) url += 'type=' + encodeURIComponent(type) + '&';
  if (status) url += 'status=' + encodeURIComponent(status);
  if (format) url += (url.includes('?') && !url.endsWith('&') && !url.endsWith('?') ? '&' : '') + 'format=' + encodeURIComponent(format);
  
  window.open(url, '_blank');
}
</script>
