<?php 
$pageTitle = $pageTitle ?? 'Marketplace';
require_once __DIR__ . '/../../../includes/header.php'; 
?>

<style>
.marketplace-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
    border-radius: 10px;
}
.category-sidebar {
    position: sticky;
    top: 20px;
}
.product-card {
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.product-image {
    height: 200px;
    object-fit: cover;
    background: #f8f9fa;
}
.product-image-placeholder {
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
}
.price-tag {
    font-size: 1.5rem;
    font-weight: bold;
    color: #28a745;
}
.category-item {
    transition: all 0.2s;
}
.category-item:hover {
    background-color: #f8f9fa;
    border-left: 3px solid #667eea;
}
.category-item.active {
    background-color: #667eea;
    color: white;
    border-left: 3px solid #764ba2;
}
.badge-featured {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ffc107;
    color: #000;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}
</style>

<div class="container-fluid py-4">
    <!-- Hero Section -->
    <div class="marketplace-hero">
        <div class="container">
            <h1 class="display-4 mb-3"><i class="fas fa-store me-3"></i>Marketplace Fleet Management</h1>
            <p class="lead mb-0">Asigurări, Roviniete, Anvelope și Piese Auto pentru Flota Ta</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Sidebar Categorii -->
            <div class="col-md-3">
                <div class="category-sidebar">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Categorii</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="<?= BASE_URL ?>modules/marketplace/" 
                               class="list-group-item list-group-item-action category-item <?= !$currentCategory ? 'active' : '' ?>">
                                <i class="fas fa-th-large me-2"></i>Toate Produsele
                                <span class="badge bg-secondary float-end"><?= $total ?></span>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="<?= BASE_URL ?>modules/marketplace/?category=<?= $cat['id'] ?>" 
                                   class="list-group-item list-group-item-action category-item <?= $currentCategory == $cat['id'] ? 'active' : '' ?>">
                                    <i class="fas fa-<?= htmlspecialchars($cat['icon']) ?> me-2"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                    <span class="badge bg-secondary float-end"><?= $cat['product_count'] ?? 0 ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Shopping Cart Widget -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                            <h5>Coșul Tău</h5>
                            <p class="text-muted mb-3"><?= $cartCount ?> produse</p>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=cart" class="btn btn-primary btn-block">
                                Vezi Coș <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Search & Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <?php if ($currentCategory): ?>
                                <input type="hidden" name="category" value="<?= $currentCategory ?>">
                            <?php endif; ?>
                            
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control form-control-lg" 
                                           placeholder="Caută produse..." 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    Caută
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Results Info -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <?php if ($search): ?>
                            Rezultate pentru: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                        <?php elseif ($currentCategory): ?>
                            <?php
                            $catName = '';
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $currentCategory) {
                                    $catName = $cat['name'];
                                    break;
                                }
                            }
                            ?>
                            Categorie: <strong><?= htmlspecialchars($catName) ?></strong>
                        <?php else: ?>
                            Toate Produsele
                        <?php endif; ?>
                        <span class="text-muted">(<?= $total ?> produse)</span>
                    </h5>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Nu am găsit produse. Încearcă o altă căutare sau categorie.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card product-card">
                                    <?php if ($product['is_featured']): ?>
                                        <span class="badge-featured">★ RECOMANDAT</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($product['image_main']): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($product['image_main']) ?>" 
                                             class="card-img-top product-image" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="fas fa-<?= htmlspecialchars($product['icon'] ?? 'box') ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <span class="badge bg-secondary mb-2 align-self-start">
                                            <?= htmlspecialchars($product['category_name']) ?>
                                        </span>
                                        
                                        <h5 class="card-title">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </h5>
                                        
                                        <p class="card-text text-muted small flex-grow-1">
                                            <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="price-tag">
                                                <?= number_format($product['price'], 2) ?> <?= $product['currency'] ?>
                                            </span>
                                        </div>
                                        
                                        <div class="btn-group mt-3" role="group">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $product['slug'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-info-circle me-1"></i> Detalii
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-primary add-to-cart-btn"
                                                    data-product-id="<?= $product['id'] ?>"
                                                    data-product-name="<?= htmlspecialchars($product['name']) ?>">
                                                <i class="fas fa-cart-plus me-1"></i> Adaugă
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Product pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $currentPage - 1 ?>&category=<?= $currentCategory ?>&search=<?= urlencode($search) ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&category=<?= $currentCategory ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $currentPage + 1 ?>&category=<?= $currentCategory ?>&search=<?= urlencode($search) ?>">
                                        Următor <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Add to Cart AJAX
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const productName = this.dataset.productName;
        const button = this;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adăugare...';
        
        fetch('<?= BASE_URL ?>modules/marketplace/?action=cart-add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                alert.style.zIndex = '9999';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>${productName} adăugat în coș!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
                
                // Update cart count (reload page for now)
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Eroare la adăugare în coș');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Adaugă';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
