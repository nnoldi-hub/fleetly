<?php 
$pageTitle = $product['name'] ?? 'Produs';
?>

<link href="<?= BASE_URL ?>assets/css/marketplace.css" rel="stylesheet">

<style>
.product-detail-image {
    max-height: 400px;
    object-fit: contain;
    background: #f8f9fa;
    border-radius: 10px;
}
.product-detail-placeholder {
    height: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 100px;
    border-radius: 10px;
}
.spec-table th {
    width: 30%;
    background-color: #f8f9fa;
}
.price-large {
    font-size: 2.5rem;
    font-weight: bold;
    color: #28a745;
}
.related-product-card {
    transition: transform 0.2s;
}
.related-product-card:hover {
    transform: scale(1.05);
}
</style>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/">Marketplace</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($product['image_main']): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($product['image_main']) ?>" 
                             class="img-fluid product-detail-image w-100" 
                             alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="product-detail-placeholder">
                            <i class="fas fa-box"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['is_featured']): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-star me-2"></i><strong>Produs Recomandat</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h1 class="h2 mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                    <p class="text-muted mb-3">SKU: <?= htmlspecialchars($product['sku']) ?></p>
                    
                    <div class="price-large mb-4">
                        <?= number_format($product['price'], 2) ?> <?= $product['currency'] ?>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Descriere</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    
                    <hr>
                    
                    <!-- Add to Cart Form -->
                    <form id="addToCartForm" class="mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <label for="quantity" class="form-label">Cantitate:</label>
                                <input type="number" class="form-control form-control-lg" 
                                       id="quantity" name="quantity" value="1" min="1" max="100" style="width: 100px;">
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-cart-plus me-2"></i>Adaugă în Coș
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Înapoi la Catalog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Specifications -->
    <?php if (!empty($product['specifications'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Specificații Tehnice</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered spec-table mb-0">
                            <?php foreach ($product['specifications'] as $key => $value): ?>
                                <tr>
                                    <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?></th>
                                    <td><?= htmlspecialchars($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-th-large me-2"></i>Produse Similare</h4>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php foreach ($relatedProducts as $related): ?>
                <div class="col">
                    <div class="card related-product-card shadow-sm h-100">
                        <?php if ($related['image_main']): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($related['image_main']) ?>" 
                                 class="card-img-top" style="height: 150px; object-fit: cover;" 
                                 alt="<?= htmlspecialchars($related['name']) ?>">
                        <?php else: ?>
                            <div style="height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-box fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($related['name']) ?></h6>
                            <p class="text-success fw-bold mb-2"><?= number_format($related['price'], 2) ?> RON</p>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $related['slug'] ?>" 
                               class="btn btn-sm btn-outline-primary w-100">
                                Vezi Detalii
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const quantity = document.getElementById('quantity').value;
    const button = this.querySelector('button[type="submit"]');
    const originalHtml = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adăugare...';
    
    fetch('<?= BASE_URL ?>modules/marketplace/?action=cart-add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=<?= $product['id'] ?>&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>Produs adăugat în coș! 
                <a href="<?= BASE_URL ?>modules/marketplace/?action=cart" class="alert-link">Vezi coșul</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
            
            button.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat!';
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-success');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success');
                button.disabled = false;
            }, 2000);
        } else {
            alert('Eroare: ' + data.message);
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la adăugare în coș');
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
});
</script>
