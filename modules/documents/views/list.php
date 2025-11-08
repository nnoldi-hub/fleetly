<?php
// modules/documents/views/list.php
$pageTitle = "Lista Documente";
require_once ROOT_PATH . '/includes/header.php';
require_once ROOT_PATH . '/includes/sidebar.php';

// Obținem parametrii de filtrare și sortare
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$vehicle_filter = $_GET['vehicle'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$sort_by = $_GET['sort'] ?? 'expiry_date';
$sort_order = $_GET['order'] ?? 'ASC';
$per_page = (int)($_GET['per_page'] ?? 25);

// Validăm parametrii
$allowed_sorts = ['expiry_date', 'document_type', 'vehicle', 'provider', 'cost', 'status'];
$sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'expiry_date';
$sort_order = in_array($sort_order, ['ASC', 'DESC']) ? $sort_order : 'ASC';
$per_page = in_array($per_page, [10, 25, 50, 100]) ? $per_page : 25;

// Obținem datele
$documentModel = new Document();
$vehicleModel = new Vehicle();

$documents = $documentModel->getAllWithVehicle();
$vehicles = method_exists($vehicleModel, 'all') 
    ? $vehicleModel->all() 
    : (method_exists($vehicleModel, 'getAllVehicles') 
        ? $vehicleModel->getAllVehicles() 
        : []); // fallback to empty array if neither method exists

// Aplicăm filtrele
if (!empty($search) || !empty($type_filter) || !empty($status_filter) || !empty($vehicle_filter)) {
    $documents = array_filter($documents, function($doc) use ($search, $type_filter, $status_filter, $vehicle_filter) {
        // Filtru tip
        if (!empty($type_filter) && $doc['document_type'] !== $type_filter) {
            return false;
        }
        
        // Filtru status
        if (!empty($status_filter) && $doc['status'] !== $status_filter) {
            return false;
        }
        
        // Filtru vehicul
        if (!empty($vehicle_filter) && $doc['vehicle_id'] != $vehicle_filter) {
            return false;
        }
        
        // Filtru căutare
        if (!empty($search)) {
            $searchFields = [
                $doc['registration_number'] ?? '',
                $doc['brand'] ?? '',
                $doc['model'] ?? '',
                $doc['document_number'] ?? '',
                $doc['provider'] ?? '',
                $doc['document_type'] ?? ''
            ];
            $searchText = implode(' ', $searchFields);
            if (stripos($searchText, $search) === false) {
                return false;
            }
        }
        
        return true;
    });
}

// Sortăm documentele
usort($documents, function($a, $b) use ($sort_by, $sort_order) {
    $valueA = $valueB = '';
    
    switch($sort_by) {
        case 'vehicle':
            $valueA = $a['registration_number'] ?? '';
            $valueB = $b['registration_number'] ?? '';
            break;
        case 'expiry_date':
            $valueA = $a['expiry_date'] ?? '';
            $valueB = $b['expiry_date'] ?? '';
            break;
        case 'cost':
            $valueA = (float)($a['cost'] ?? 0);
            $valueB = (float)($b['cost'] ?? 0);
            break;
        default:
            $valueA = $a[$sort_by] ?? '';
            $valueB = $b[$sort_by] ?? '';
    }
    
    if ($valueA == $valueB) return 0;
    
    $result = $valueA < $valueB ? -1 : 1;
    return $sort_order === 'DESC' ? -$result : $result;
});

// Calculăm paginarea
$total_documents = count($documents);
$total_pages = ceil($total_documents / $per_page);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $per_page;
$documents_page = array_slice($documents, $offset, $per_page);

// Calculăm statistici
$stats = [
    'total' => $total_documents,
    'active' => count(array_filter($documents, function($d) { return $d['status'] === 'active'; })),
    'expired' => count(array_filter($documents, function($d) { return $d['status'] === 'expired'; })),
    'expiring_soon' => count(array_filter($documents, function($d) {
        if ($d['status'] !== 'active') return false;
        $days_until = (strtotime($d['expiry_date']) - time()) / (24 * 3600);
        return $days_until <= 30;
    }))
];

// Tipuri de documente
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

// Funcție pentru generarea link-urilor de sortare
function getSortUrl($column, $currentSort, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}

// Funcție pentru afișarea săgeții de sortare
function getSortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) return '<i class="fas fa-sort text-muted"></i>';
    return $currentOrder === 'ASC' ? '<i class="fas fa-sort-up text-primary"></i>' : '<i class="fas fa-sort-down text-primary"></i>';
}
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Documente</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Gestionare Documente
            </h1>
            <div class="btn-group">
                <a href="<?= BASE_URL ?>documents/add" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Adaugă Document
                </a>
                <a href="<?= BASE_URL ?>documents/expiring" class="btn btn-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i> În Expirare
                </a>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportToExcel()"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportToPDF()"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                        <li><a class="dropdown-item" href="#" onclick="printList()"><i class="fas fa-print me-2"></i>Tipărește</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Statistici Rapide -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-alt fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-primary mb-0"><?= $stats['total'] ?></div>
                                <div class="text-muted small">Total Documente</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-success mb-0"><?= $stats['active'] ?></div>
                                <div class="text-muted small">Active</div>
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
                                <div class="fw-bold h4 text-warning mb-0"><?= $stats['expiring_soon'] ?></div>
                                <div class="text-muted small">Expiră în 30 zile</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold h4 text-danger mb-0"><?= $stats['expired'] ?></div>
                                <div class="text-muted small">Expirate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre și Căutare -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-search me-2"></i>
                    Căutare și Filtre
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <!-- Căutare -->
                    <div class="col-md-4">
                        <label for="search" class="form-label">Căutare</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Număr document, vehicul, furnizor...">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tip Document -->
                    <div class="col-md-2">
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

                    <!-- Status -->
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Toate statusurile</option>
                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Activ</option>
                            <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Expirat</option>
                            <option value="suspended" <?= $status_filter == 'suspended' ? 'selected' : '' ?>>Suspendat</option>
                            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Anulat</option>
                        </select>
                    </div>

                    <!-- Vehicul -->
                    <div class="col-md-2">
                        <label for="vehicle" class="form-label">Vehicul</label>
                        <select class="form-select" id="vehicle" name="vehicle">
                            <option value="">Toate vehiculele</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= $vehicle['id'] ?>" <?= $vehicle_filter == $vehicle['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vehicle['registration_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Elemente pe pagină -->
                    <div class="col-md-1">
                        <label for="per_page" class="form-label">Per pagină</label>
                        <select class="form-select" id="per_page" name="per_page" onchange="this.form.submit()">
                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>

                    <!-- Butoane -->
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="<?= BASE_URL ?>documents" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Inputs ascunse pentru sortare -->
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">
                </form>
            </div>
        </div>

        <!-- Tabel Documente -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista Documente (<?= $total_documents ?> rezultate)
                    </h6>
                    <div class="d-flex align-items-center">
                        <small class="text-muted me-3">
                            Afișez <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_documents) ?> din <?= $total_documents ?>
                        </small>
                        <?php if (!empty($search) || !empty($type_filter) || !empty($status_filter) || !empty($vehicle_filter)): ?>
                            <span class="badge bg-info">Filtrat</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($documents_page)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 5%">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th style="width: 15%">
                                    <a href="<?= getSortUrl('vehicle', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Vehicul <?= getSortIcon('vehicle', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 15%">
                                    <a href="<?= getSortUrl('document_type', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Tip Document <?= getSortIcon('document_type', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 12%">Număr Document</th>
                                <th style="width: 15%">
                                    <a href="<?= getSortUrl('provider', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Furnizor <?= getSortIcon('provider', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 10%">
                                    <a href="<?= getSortUrl('expiry_date', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Data Expirare <?= getSortIcon('expiry_date', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 8%">
                                    <a href="<?= getSortUrl('cost', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Cost <?= getSortIcon('cost', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 8%">
                                    <a href="<?= getSortUrl('status', $sort_by, $sort_order) ?>" class="text-decoration-none text-dark">
                                        Status <?= getSortIcon('status', $sort_by, $sort_order) ?>
                                    </a>
                                </th>
                                <th style="width: 12%">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents_page as $doc): 
                                // Calculăm zilele până la expirare
                                $daysUntilExpiry = ceil((strtotime($doc['expiry_date']) - time()) / (24 * 3600));
                                $urgencyClass = '';
                                $urgencyIcon = '';
                                
                                if ($doc['status'] === 'expired' || $daysUntilExpiry < 0) {
                                    $urgencyClass = 'table-danger';
                                    $urgencyIcon = 'fas fa-times-circle text-danger';
                                } elseif ($daysUntilExpiry <= 7) {
                                    $urgencyClass = 'table-danger';
                                    $urgencyIcon = 'fas fa-exclamation-circle text-danger';
                                } elseif ($daysUntilExpiry <= 30) {
                                    $urgencyClass = 'table-warning';
                                    $urgencyIcon = 'fas fa-exclamation-triangle text-warning';
                                } else {
                                    $urgencyIcon = 'fas fa-check-circle text-success';
                                }
                            ?>
                            <tr class="<?= $urgencyClass ?>" data-document-id="<?= $doc['id'] ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input document-checkbox" value="<?= $doc['id'] ?>">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="<?= $urgencyIcon ?> me-2"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($doc['registration_number'] ?? 'N/A') ?></strong><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(($doc['brand'] ?? '') . ' ' . ($doc['model'] ?? '')) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?= $documentTypes[$doc['document_type']] ?? $doc['document_type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($doc['document_number']) ?></strong>
                                    <?php if (!empty($doc['file_path'])): ?>
                                        <br><small><i class="fas fa-paperclip text-muted"></i> Fișier atașat</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($doc['provider']) ?></td>
                                <td>
                                    <strong><?= date('d.m.Y', strtotime($doc['expiry_date'])) ?></strong><br>
                                    <small class="<?= $daysUntilExpiry <= 0 ? 'text-danger' : ($daysUntilExpiry <= 30 ? 'text-warning' : 'text-muted') ?>">
                                        <?php if ($daysUntilExpiry <= 0): ?>
                                            Expirat cu <?= abs($daysUntilExpiry) ?> zile
                                        <?php else: ?>
                                            Expiră în <?= $daysUntilExpiry ?> zile
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($doc['cost']): ?>
                                        <strong><?= number_format($doc['cost'], 2) ?> RON</strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'active' => 'bg-success',
                                        'expired' => 'bg-danger',
                                        'suspended' => 'bg-warning text-dark',
                                        'cancelled' => 'bg-secondary'
                                    ];
                                    $statusLabels = [
                                        'active' => 'Activ',
                                        'expired' => 'Expirat',
                                        'suspended' => 'Suspendat',
                                        'cancelled' => 'Anulat'
                                    ];
                                    ?>
                                    <span class="badge <?= $statusClasses[$doc['status']] ?? 'bg-secondary' ?>">
                                        <?= $statusLabels[$doc['status']] ?? $doc['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= BASE_URL ?>documents/view?id=<?= $doc['id'] ?>" class="btn btn-outline-info" title="Vizualizează">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>documents/edit?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!empty($doc['file_path'])): ?>
                                        <a href="<?= BASE_URL ?>uploads/<?= $doc['file_path'] ?>" 
                                           target="_blank" class="btn btn-outline-success" title="Descarcă fișier">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteDocument(<?= $doc['id'] ?>)" title="Șterge">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Nu s-au găsit documente</h5>
                    <p class="text-muted mb-4">
                        <?php if (!empty($search) || !empty($type_filter) || !empty($status_filter) || !empty($vehicle_filter)): ?>
                            Nu s-au găsit documente care să corespundă criteriilor de căutare.
                        <?php else: ?>
                            Nu există documente înregistrate în sistem.
                        <?php endif; ?>
                    </p>
                    <a href="<?= BASE_URL ?>documents/add" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Adaugă primul document
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Paginare -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Navigare pagini">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <!-- Prima pagină -->
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Paginile din apropiere -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <!-- Ultima pagină -->
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center mt-2">
                    <small class="text-muted">
                        Pagina <?= $page ?> din <?= $total_pages ?> 
                        (<?= $total_documents ?> documente total)
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acțiuni în Masă -->
        <div class="card mt-4" id="bulkActions" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-tasks me-2"></i>
                    Acțiuni în Masă (<span id="selectedCount">0</span> documente selectate)
                </h6>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-success" onclick="bulkChangeStatus('active')">
                        <i class="fas fa-check me-1"></i> Marchează Activ
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="bulkChangeStatus('suspended')">
                        <i class="fas fa-pause me-1"></i> Suspendă
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="bulkExport()">
                        <i class="fas fa-download me-1"></i> Exportă Selectate
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash me-1"></i> Șterge Selectate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru vizualizare document -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalii Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="documentDetails">
                <!-- Conținutul va fi încărcat prin AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-primary" id="editFromModal">
                    <i class="fas fa-edit me-1"></i> Editează
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionarea checkbox-urilor
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.document-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const selected = document.querySelectorAll('.document-checkbox:checked');
        const count = selected.length;
        
        selectedCount.textContent = count;
        bulkActions.style.display = count > 0 ? 'block' : 'none';
        
        selectAll.checked = count === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }
});

// Funcții pentru acțiuni
function viewDocument(id) {
    // Încărcăm detaliile prin AJAX
    fetch(`<?= BASE_URL ?>modules/documents/controllers/DocumentController.php?action=view&id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('documentDetails').innerHTML = html;
            document.getElementById('editFromModal').onclick = () => {
                window.location.href = `<?= BASE_URL ?>modules/documents/views/edit.php?id=${id}`;
            };
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la încărcarea detaliilor documentului');
        });
}

function deleteDocument(id) {
    if (!confirm('Ești sigur că vrei să ștergi acest document? Această acțiune nu poate fi anulată.')) return;
    const formData = new URLSearchParams();
    formData.set('id', id);
    fetch('<?= BASE_URL ?>documents/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) { location.reload(); }
        else { alert('Eroare la ștergerea documentului'); }
    })
    .catch(() => alert('Eroare la ștergerea documentului'));
}

function bulkChangeStatus(status) {
    const selected = Array.from(document.querySelectorAll('.document-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Nu ai selectat niciun document');
        return;
    }
    
    if (confirm(`Vrei să schimbi statusul pentru ${selected.length} documente?`)) {
        fetch(`<?= BASE_URL ?>modules/documents/controllers/DocumentController.php?action=bulkStatus`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: selected, status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        });
    }
}

function bulkDelete() {
    const selected = Array.from(document.querySelectorAll('.document-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Nu ai selectat niciun document');
        return;
    }
    
    if (confirm(`Ești sigur că vrei să ștergi ${selected.length} documente? Această acțiune nu poate fi anulată.`)) {
        fetch(`<?= BASE_URL ?>modules/documents/controllers/DocumentController.php?action=bulkDelete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: selected })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        });
    }
}

function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'excel');
    window.location.href = `<?= BASE_URL ?>documents/export?${params.toString()}`;
}

function exportToPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'pdf');
    window.location.href = `<?= BASE_URL ?>documents/export?${params.toString()}`;
}

function printList() {
    window.print();
}

function bulkExport() {
    const selected = Array.from(document.querySelectorAll('.document-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) { alert('Nu ai selectat niciun document'); return; }
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'excel');
    params.set('ids', selected.join(','));
    window.location.href = `<?= BASE_URL ?>documents/export?${params.toString()}`;
}

// Stiluri pentru tipărire
const printStyles = `
<style>
@media print {
    .btn, .card-header, nav, .no-print, #bulkActions { display: none !important; }
    .card { border: 1px solid #000 !important; box-shadow: none !important; }
    .table-danger { background-color: #f8d7da !important; }
    .table-warning { background-color: #fff3cd !important; }
    .badge { border: 1px solid #000 !important; color: #000 !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .container-fluid { padding: 0 !important; }
}
</style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
