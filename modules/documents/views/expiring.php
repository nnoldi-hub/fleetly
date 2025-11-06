<?php
// modules/documents/views/expiring.php
$pageTitle = "Documente în Expirare";
require_once ROOT_PATH . '/includes/header.php';
require_once ROOT_PATH . '/includes/sidebar.php';

// Obținem parametrii de filtrare
$days = $_GET['days'] ?? 30;
$type_filter = $_GET['type'] ?? '';
$vehicle_filter = $_GET['vehicle'] ?? '';

// Obținem documentele în expirare
$documentModel = new Document();
$vehicleModel = new Vehicle();

$expiringDocuments = $documentModel->getExpiring($days);
$vehicles = method_exists($vehicleModel, 'all') 
    ? $vehicleModel->all() 
    : (method_exists($vehicleModel, 'getAllVehicles') 
        ? $vehicleModel->getAllVehicles() 
        : []); // fallback to empty array if neither method exists

// Aplicăm filtrele suplimentare
if (!empty($type_filter) || !empty($vehicle_filter)) {
    $expiringDocuments = array_filter($expiringDocuments, function($doc) use ($type_filter, $vehicle_filter) {
        if (!empty($type_filter) && $doc['document_type'] !== $type_filter) {
            return false;
        }
        if (!empty($vehicle_filter) && $doc['vehicle_id'] != $vehicle_filter) {
            return false;
        }
        return true;
    });
}

// Grupăm documentele pe categorii de urgență
$critical = []; // < 7 zile
$warning = [];  // 7-14 zile
$normal = [];   // > 14 zile

foreach ($expiringDocuments as $doc) {
    $daysUntil = $doc['days_until_expiry'];
    if ($daysUntil < 7) {
        $critical[] = $doc;
    } elseif ($daysUntil <= 14) {
        $warning[] = $doc;
    } else {
        $normal[] = $doc;
    }
}

// Tipuri de documente pentru filtru
$documentTypes = [
    'ITP' => 'Inspecție Tehnică Periodică',
    'RCA' => 'Asigurare RCA',
    'CASCO' => 'Asigurare CASCO',
    'CARTA_VERDE' => 'Carta Verde',
    'VINIETA' => 'Vinietă',
    'AUTORIZATIE_TRANSPORT' => 'Autorizație Transport',
    'PERMIS_CONDUCERE' => 'Permis Conducere',
    'CERTIFICAT_INMATRICULARE' => 'Certificat Înmatriculare',
    'FISA_TEHNICA' => 'Fișă Tehnică',
    'CONTRACT_LEASING' => 'Contract Leasing',
    'POLITA_ASIGURARE' => 'Poliță Asigurare',
    'ALTE_DOCUMENTE' => 'Alte Documente'
];
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/documents/">Documente</a></li>
                <li class="breadcrumb-item active">În Expirare</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Documente în Expirare
            </h1>
            <div class="btn-group">
                <a href="<?= BASE_URL ?>modules/documents/views/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Adaugă Document
                </a>
                <a href="<?= BASE_URL ?>modules/documents/" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-1"></i> Toate Documentele
                </a>
            </div>
        </div>

        <!-- Statistici Rapide -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-danger mb-0"><?= count($critical) ?></div>
                                <div class="text-muted small">Critice (&lt; 7 zile)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-warning mb-0"><?= count($warning) ?></div>
                                <div class="text-muted small">Atenție (7-14 zile)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-info mb-0"><?= count($normal) ?></div>
                                <div class="text-muted small">Normale (&gt; 14 zile)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-alt fa-2x text-secondary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-secondary mb-0"><?= count($expiringDocuments) ?></div>
                                <div class="text-muted small">Total în Expirare</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtre și Opțiuni
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="days" class="form-label">Zile în Avans</label>
                        <select class="form-select" id="days" name="days">
                            <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 zile</option>
                            <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 zile</option>
                            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 zile</option>
                            <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 zile</option>
                            <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 zile</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Tip Document</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Toate tipurile</option>
                            <?php foreach ($documentTypes as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $type_filter == $key ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="vehicle" class="form-label">Vehicul</label>
                        <select class="form-select" id="vehicle" name="vehicle">
                            <option value="">Toate vehiculele</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= $vehicle['id'] ?>" <?= $vehicle_filter == $vehicle['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehicle['registration_number']) ?> - 
                                    <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i> Filtrează
                        </button>
                        <a href="<?= BASE_URL ?>modules/documents/views/expiring.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documente Critice (< 7 zile) -->
        <?php if (!empty($critical)): ?>
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Documente Critice - Expiră în mai puțin de 7 zile (<?= count($critical) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicul</th>
                                <th>Tip Document</th>
                                <th>Număr</th>
                                <th>Furnizor</th>
                                <th>Data Expirare</th>
                                <th>Zile Rămase</th>
                                <th>Cost</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($critical as $doc): ?>
                            <tr class="table-danger">
                                <td>
                                    <strong><?= htmlspecialchars($doc['registration_number']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($doc['brand'] . ' ' . $doc['model']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-danger">
                                        <?= $documentTypes[$doc['document_type']] ?? $doc['document_type'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($doc['document_number']) ?></td>
                                <td><?= htmlspecialchars($doc['provider']) ?></td>
                                <td>
                                    <strong class="text-danger">
                                        <?= date('d.m.Y', strtotime($doc['expiry_date'])) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-danger fs-6">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $doc['days_until_expiry'] ?> zile
                                    </span>
                                </td>
                                <td>
                                    <?php if ($doc['cost']): ?>
                                        <span class="fw-bold"><?= number_format($doc['cost'], 2) ?> RON</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>modules/documents/views/edit.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-success" onclick="renewDocument(<?= $doc['id'] ?>)" title="Reînnoiește">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documente cu Atenție (7-14 zile) -->
        <?php if (!empty($warning)): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Documente cu Atenție - Expiră în 7-14 zile (<?= count($warning) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicul</th>
                                <th>Tip Document</th>
                                <th>Număr</th>
                                <th>Furnizor</th>
                                <th>Data Expirare</th>
                                <th>Zile Rămase</th>
                                <th>Cost</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warning as $doc): ?>
                            <tr class="table-warning">
                                <td>
                                    <strong><?= htmlspecialchars($doc['registration_number']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($doc['brand'] . ' ' . $doc['model']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <?= $documentTypes[$doc['document_type']] ?? $doc['document_type'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($doc['document_number']) ?></td>
                                <td><?= htmlspecialchars($doc['provider']) ?></td>
                                <td>
                                    <strong class="text-warning">
                                        <?= date('d.m.Y', strtotime($doc['expiry_date'])) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark fs-6">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $doc['days_until_expiry'] ?> zile
                                    </span>
                                </td>
                                <td>
                                    <?php if ($doc['cost']): ?>
                                        <span class="fw-bold"><?= number_format($doc['cost'], 2) ?> RON</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>modules/documents/views/edit.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-success" onclick="renewDocument(<?= $doc['id'] ?>)" title="Reînnoiește">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documente Normale (> 14 zile) -->
        <?php if (!empty($normal)): ?>
        <div class="card mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Documente Normale - Expiră în peste 14 zile (<?= count($normal) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicul</th>
                                <th>Tip Document</th>
                                <th>Număr</th>
                                <th>Furnizor</th>
                                <th>Data Expirare</th>
                                <th>Zile Rămase</th>
                                <th>Cost</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($normal as $doc): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($doc['registration_number']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($doc['brand'] . ' ' . $doc['model']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $documentTypes[$doc['document_type']] ?? $doc['document_type'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($doc['document_number']) ?></td>
                                <td><?= htmlspecialchars($doc['provider']) ?></td>
                                <td>
                                    <?= date('d.m.Y', strtotime($doc['expiry_date'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-info fs-6">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $doc['days_until_expiry'] ?> zile
                                    </span>
                                </td>
                                <td>
                                    <?php if ($doc['cost']): ?>
                                        <span class="fw-bold"><?= number_format($doc['cost'], 2) ?> RON</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>modules/documents/views/edit.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-success" onclick="renewDocument(<?= $doc['id'] ?>)" title="Reînnoiește">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mesaj dacă nu sunt documente în expirare -->
        <?php if (empty($expiringDocuments)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="text-success">Excelent!</h4>
                <p class="text-muted mb-4">
                    Nu există documente care să expire în următoarele <?= $days ?> zile.
                </p>
                <a href="<?= BASE_URL ?>modules/documents/" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i> Vezi Toate Documentele
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acțiuni în Masă -->
        <?php if (!empty($expiringDocuments)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Acțiuni Rapide
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i>
                            Exportă Excel
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-info w-100" onclick="printReport()">
                            <i class="fas fa-print me-1"></i>
                            Tipărește Raport
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-warning w-100" onclick="sendReminders()">
                            <i class="fas fa-envelope me-1"></i>
                            Trimite Reminder-uri
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pentru reînnoirea documentului -->
<div class="modal fade" id="renewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reînnoiește Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Vrei să reînnoiești acest document? Aceasta va crea un nou document cu date actualizate.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    Documentul vechi va fi marcat ca "înlocuit" și cel nou va prelua toate informațiile.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" id="confirmRenew">
                    <i class="fas fa-sync-alt me-1"></i> Reînnoiește
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh la fiecare 5 minute pentru a păstra datele actuale
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 300000); // 5 minute
});

// Funcție pentru reînnoirea documentului
function renewDocument(documentId) {
    const modal = new bootstrap.Modal(document.getElementById('renewModal'));
    modal.show();
    
    document.getElementById('confirmRenew').onclick = function() {
        // Redirecționează la pagina de adăugare cu ID-ul documentului pentru precompletare
        window.location.href = `<?= BASE_URL ?>modules/documents/views/add.php?renew=${documentId}`;
    };
}

// Funcție pentru export Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = `<?= BASE_URL ?>modules/documents/controllers/DocumentController.php?action=expiring&${params.toString()}`;
}

// Funcție pentru tipărire
function printReport() {
    window.print();
}

// Funcție pentru trimiterea reminder-urilor
function sendReminders() {
    if (confirm('Vrei să trimiți reminder-uri email pentru toate documentele critice?')) {
        fetch('<?= BASE_URL ?>modules/documents/controllers/DocumentController.php?action=sendReminders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                documents: <?= json_encode(array_merge($critical, $warning)) ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder-urile au fost trimise cu succes!');
            } else {
                alert('Eroare la trimiterea reminder-urilor: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la trimiterea reminder-urilor');
        });
    }
}

// Stilizare pentru tipărire
const printStyles = `
<style>
@media print {
    .btn, .card-header, nav, .no-print { display: none !important; }
    .card { border: 1px solid #000 !important; }
    .table-danger { background-color: #f8d7da !important; }
    .table-warning { background-color: #fff3cd !important; }
    .text-danger { color: #000 !important; }
    .text-warning { color: #000 !important; }
    .badge { border: 1px solid #000 !important; }
}
</style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
