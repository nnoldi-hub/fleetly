<?php 
$pageTitle = 'Admin - Produse';
require_once __DIR__ . '/../../../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-boxes me-3"></i>Gestionare Produse</h1>
        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-product-form" class="btn btn-success btn-lg">
            <i class="fas fa-plus-circle me-2"></i>Adaugă Produs Nou
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>modules/marketplace/" class="row g-3">
                <input type="hidden" name="action" value="admin-products">
                
                <div class="col-md-3">
                    <label class="form-label">Caută Produs:</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                           placeholder="Nume sau SKU...">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Categorie:</label>
                    <select class="form-select" name="category">
                        <option value="">Toate Categoriile</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?> (<?= $cat['product_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status:</label>
                    <select class="form-select" name="status">
                        <option value="">Toate</option>
                        <option value="1" <?= ($statusFilter === '1') ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($statusFilter === '0') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sortare:</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?= ($sortBy === 'newest') ? 'selected' : '' ?>>Cele mai noi</option>
                        <option value="oldest" <?= ($sortBy === 'oldest') ? 'selected' : '' ?>>Cele mai vechi</option>
                        <option value="name" <?= ($sortBy === 'name') ? 'selected' : '' ?>>Nume A-Z</option>
                        <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : '' ?>>Preț crescător</option>
                        <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : '' ?>>Preț descrescător</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrează
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Produse (<?= $totalProducts ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Imagine</th>
                        <th>Produs</th>
                        <th>Categorie</th>
                        <th>SKU</th>
                        <th class="text-end">Preț</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Recomandat</th>
                        <th class="text-end">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                Nu s-au găsit produse
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image_main']): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($product['image_main']) ?>" 
                                             class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; border-radius: 4px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                    <small class="text-muted"><?= substr(htmlspecialchars($product['description']), 0, 60) ?>...</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($product['category_name']) ?></span>
                                </td>
                                <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                <td class="text-end">
                                    <strong class="text-success"><?= number_format($product['price'], 2) ?> RON</strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge bg-success">Activ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($product['is_featured']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $product['slug'] ?>" 
                                           class="btn btn-outline-info" title="Previzualizare" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-product-form&id=<?= $product['id'] ?>" 
                                           class="btn btn-outline-primary" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>')" 
                                                title="Șterge">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?action=admin-products&page=<?= $currentPage - 1 ?><?= $searchQuery ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $statusFilter !== '' ? '&status=' . $statusFilter : '' ?>&sort=<?= $sortBy ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?action=admin-products&page=<?= $i ?><?= $searchQuery ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $statusFilter !== '' ? '&status=' . $statusFilter : '' ?>&sort=<?= $sortBy ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?action=admin-products&page=<?= $currentPage + 1 ?><?= $searchQuery ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?><?= $statusFilter !== '' ? '&status=' . $statusFilter : '' ?>&sort=<?= $sortBy ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteProduct(id, name) {
    if (confirm(`Sigur dorești să ștergi produsul "${name}"?\n\nAceastă acțiune nu poate fi anulată.`)) {
        // TODO: Implement delete functionality
        alert('Funcționalitatea de ștergere va fi implementată în curând.');
    }
}
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>
