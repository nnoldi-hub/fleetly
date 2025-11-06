<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../controllers/InsuranceController.php';

$controller = new InsuranceController();

// Apelăm metoda index care va seta datele și va include view-ul
// Pentru aceasta, redirectăm la controllerul principal
if (!isset($insuranceRecords)) {
    // Dacă nu avem datele, redirectăm la controller
    header('Location: /modules/insurance/controllers/InsuranceController.php?action=index');
    exit;
}

$pageTitle = 'Lista Polițelor de Asigurare';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php 
            $breadcrumbs = [
                'Acasă' => '/',
                'Asigurări' => '/modules/insurance/',
                'Lista Polițelor' => ''
            ];
            include __DIR__ . '/../../../includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Lista Polițelor de Asigurare</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/modules/insurance/views/add.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> Adaugă Poliță
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportData()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; unset($_SESSION['errors']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtre -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="vehicle_id" class="form-label">Vehicul</label>
                            <select class="form-select" id="vehicle_id" name="vehicle_id">
                                <option value="">Toate vehiculele</option>
                                <?php
                                require_once __DIR__ . '/../../vehicles/models/vehicle.php';
                                $vehicleModel = new Vehicle();
                                $vehicles = $vehicleModel->getActiveVehicles();
                                foreach ($vehicles as $vehicle):
                                ?>
                                    <option value="<?php echo $vehicle['id']; ?>" 
                                            <?php echo (isset($_GET['vehicle_id']) && $_GET['vehicle_id'] == $vehicle['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="company" class="form-label">Companie</label>
                            <input type="text" class="form-control" id="company" name="company" 
                                   value="<?php echo htmlspecialchars($_GET['company'] ?? ''); ?>" 
                                   placeholder="Numele companiei">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Toate</option>
                                <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Activ</option>
                                <option value="expired" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'selected' : ''; ?>>Expirat</option>
                                <option value="expiring" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expiring') ? 'selected' : ''; ?>>În expirare</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="per_page" class="form-label">Pe pagină</label>
                            <select class="form-select" id="per_page" name="per_page">
                                <option value="25" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '25') ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '50') ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '100') ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrează
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Acțiuni în bloc -->
            <div class="card mb-4" id="bulk-actions" style="display: none;">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <strong>Acțiuni pentru elementele selectate:</strong>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                                <i class="fas fa-trash"></i> Șterge
                            </button>
                        </div>
                        <div class="col-auto">
                            <span id="selected-count">0</span> elemente selectate
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel polițe -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Vehicul</th>
                                    <th>Tip Asigurare</th>
                                    <th>Companie</th>
                                    <th>Număr Poliță</th>
                                    <th>Valabilitate</th>
                                    <th>Primă Anuală</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($insuranceRecords)): ?>
                                    <?php foreach ($insuranceRecords as $insurance): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input row-select" 
                                                       value="<?php echo $insurance['id']; ?>">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($insurance['license_plate']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($insurance['vehicle_make'] . ' ' . $insurance['vehicle_model']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $insurance['insurance_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($insurance['company']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($insurance['policy_number']); ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>Început:</strong> <?php echo date('d.m.Y', strtotime($insurance['start_date'])); ?><br>
                                                    <strong>Sfârșit:</strong> <?php echo date('d.m.Y', strtotime($insurance['end_date'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($insurance['annual_premium'], 2); ?> RON</strong>
                                            </td>
                                            <td>
                                                <?php
                                                $today = date('Y-m-d');
                                                $endDate = $insurance['end_date'];
                                                $daysUntilExpiry = (strtotime($endDate) - strtotime($today)) / (24 * 3600);
                                                
                                                if ($daysUntilExpiry < 0): ?>
                                                    <span class="badge bg-danger">Expirat</span>
                                                <?php elseif ($daysUntilExpiry <= 30): ?>
                                                    <span class="badge bg-warning">În expirare (<?php echo ceil($daysUntilExpiry); ?> zile)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Activ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/modules/insurance/views/view.php?id=<?php echo $insurance['id']; ?>" 
                                                       class="btn btn-outline-info" title="Vizualizează">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/modules/insurance/views/edit.php?id=<?php echo $insurance['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Editează">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($insurance['policy_file']): ?>
                                                        <a href="/uploads/<?php echo $insurance['policy_file']; ?>" 
                                                           class="btn btn-outline-secondary" title="Descarcă fișier" target="_blank">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteInsurance(<?php echo $insurance['id']; ?>)" title="Șterge">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-shield-alt fa-3x mb-3 d-block"></i>
                                                Nu au fost găsite polițe de asigurare.
                                                <br><br>
                                                <a href="/modules/insurance/views/add.php" class="btn btn-success">
                                                    <i class="fas fa-plus"></i> Adaugă prima poliță
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Selectarea în bloc
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-select');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

document.querySelectorAll('.row-select').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.row-select:checked');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    selectedCount.textContent = selectedCheckboxes.length;
    
    if (selectedCheckboxes.length > 0) {
        bulkActions.style.display = 'block';
    } else {
        bulkActions.style.display = 'none';
    }
}

// Ștergerea unei polițe
function deleteInsurance(id) {
    if (confirm('Sigur doriți să ștergeți această poliță de asigurare?')) {
        fetch('/modules/insurance/controllers/InsuranceController.php?action=delete&id=' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            alert('A apărut o eroare la ștergere.');
        });
    }
}

// Ștergerea în bloc
function bulkDelete() {
    const selectedCheckboxes = document.querySelectorAll('.row-select:checked');
    const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Nu ați selectat nicio poliță.');
        return;
    }
    
    if (confirm(`Sigur doriți să ștergeți ${ids.length} polițe selectate?`)) {
        fetch('/modules/insurance/controllers/InsuranceController.php?action=bulkDelete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ids=' + JSON.stringify(ids)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Eroare:', error);
            alert('A apărut o eroare la ștergere.');
        });
    }
}

// Export date
function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '/modules/insurance/controllers/InsuranceController.php?action=export&' + params.toString();
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
