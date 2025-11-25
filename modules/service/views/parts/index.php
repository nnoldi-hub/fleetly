<?php
// modules/service/views/parts/index.php
?>

<div class="container-fluid py-4">
    <!-- Header with breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Acasa</a></li>
                    <li class="breadcrumb-item"><a href="/service">Atelier</a></li>
                    <li class="breadcrumb-item active">Piese</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Gestiune Piese</h1>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-boxes text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted">Total Piese</h6>
                            <h3 class="mb-0"><?php echo $statistics['total_parts']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted">Valoare Stoc</h6>
                            <h3 class="mb-0"><?php echo number_format($statistics['total_stock_value'], 0, ',', '.'); ?> RON</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted">Stoc Minim</h6>
                            <h3 class="mb-0"><?php echo $statistics['low_stock_count']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <a href="/service/parts/add" class="text-white text-decoration-none">
                        <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                        <h6 class="mt-2 mb-0">Adauga Piesa</h6>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (count($lowStockParts) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Atentie!</strong> Aveti <?php echo count($lowStockParts); ?> piese cu stoc sub nivelul minim:
            <?php foreach ($lowStockParts as $idx => $lsp): ?>
                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($lsp['name']); ?> (<?php echo $lsp['available_quantity']; ?>)</span>
                <?php if ($idx < count($lowStockParts) - 1) echo ', '; ?>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/service/parts" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cauta</label>
                    <input type="text" name="search" class="form-control" placeholder="Nume, cod piesa, producator..." 
                           value="<?php echo htmlspecialchars($filters['search']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categorie</label>
                    <select name="category" class="form-select">
                        <option value="">Toate categoriile</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo $filters['category'] === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="low_stock" class="form-check-input" id="lowStock"
                               <?php echo $filters['low_stock'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="lowStock">Doar stoc minim</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Filtreaza
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Parts Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Lista Piese</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cod Piesa</th>
                            <th>Nume</th>
                            <th>Categorie</th>
                            <th>Producator</th>
                            <th>Pret Achizitie</th>
                            <th>Pret Vanzare</th>
                            <th class="text-center">Stoc</th>
                            <th class="text-center">Disponibil</th>
                            <th>Locatie</th>
                            <th class="text-end">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parts)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    Nu exista piese in stoc
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parts as $part): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($part['part_number']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="/service/parts/view/<?php echo $part['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($part['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($part['category']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($part['manufacturer'] ?? '-'); ?></td>
                                    <td><?php echo number_format($part['unit_price'], 2); ?> RON</td>
                                    <td><?php echo number_format($part['sale_price'], 2); ?> RON</td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $part['quantity_in_stock']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $available = $part['available_quantity'];
                                        $badgeClass = 'bg-success';
                                        if ($available <= $part['minimum_quantity']) {
                                            $badgeClass = 'bg-danger';
                                        } elseif ($available <= $part['minimum_quantity'] * 2) {
                                            $badgeClass = 'bg-warning text-dark';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $available; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($part['location'] ?? '-'); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/service/parts/view/<?php echo $part['id']; ?>" 
                                               class="btn btn-outline-primary" title="Detalii">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/service/parts/edit/<?php echo $part['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Editeaza">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="showStockModal(<?php echo $part['id']; ?>, '<?php echo htmlspecialchars($part['name']); ?>')" 
                                                    title="Ajusteaza Stoc">
                                                <i class="bi bi-plus-slash-minus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="stockForm">
                <div class="modal-header">
                    <h5 class="modal-title">Ajusteaza Stoc</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Piesa:</strong> <span id="stockPartName"></span></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Tip Operatie</label>
                        <select name="type" class="form-select" required>
                            <option value="in">Adauga in Stoc (intrare)</option>
                            <option value="out">Scade din Stoc (iesire)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantitate</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observatii</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Motiv, furnizor, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaza</button>
                    <button type="submit" class="btn btn-primary">Salveaza</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showStockModal(partId, partName) {
    document.getElementById('stockPartName').textContent = partName;
    document.getElementById('stockForm').action = '/service/parts/adjustStock/' + partId;
    new bootstrap.Modal(document.getElementById('stockModal')).show();
}
</script>
