<?php 
$pageTitle = 'Coș de Cumpărături';
?>

<link href="<?= BASE_URL ?>assets/css/marketplace.css" rel="stylesheet">

<div class="container py-4">
    <h1 class="mb-4"><i class="fas fa-shopping-cart me-3"></i>Coș de Cumpărături</h1>
    
    <?php if (!empty($issues)): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Atenție!</h5>
            <ul class="mb-0">
                <?php foreach ($issues as $issue): ?>
                    <li><?= htmlspecialchars($issue['message']) ?>: <strong><?= htmlspecialchars($issue['product_name']) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (empty($items)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                <h3>Coșul Tău Este Gol</h3>
                <p class="text-muted mb-4">Adaugă produse din catalog pentru a începe comanda.</p>
                <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-primary btn-lg">
                    <i class="fas fa-store me-2"></i>Browse Marketplace
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Produse în Coș (<?= count($items) ?>)</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($items as $item): ?>
                            <div class="list-group-item cart-item" data-cart-id="<?= $item['id'] ?>">
                                <div class="row align-items-center">
                                    <!-- Product Image -->
                                    <div class="col-md-2">
                                        <?php if ($item['product_image']): ?>
                                            <img src="<?= BASE_URL . htmlspecialchars($item['product_image']) ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 80px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                <i class="fas fa-box fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Product Info -->
                                    <div class="col-md-4">
                                        <h6 class="mb-1">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $item['product_slug'] ?>">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </a>
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            Preț unitar: <?= number_format($item['price'], 2) ?> RON
                                        </p>
                                    </div>
                                    
                                    <!-- Quantity -->
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary btn-sm qty-minus" type="button">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm text-center quantity-input" 
                                                   value="<?= $item['quantity'] ?>" min="1" max="100">
                                            <button class="btn btn-outline-secondary btn-sm qty-plus" type="button">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Price & Remove -->
                                    <div class="col-md-2 text-end">
                                        <p class="fw-bold text-success mb-1 item-total">
                                            <?= number_format($item['item_total'], 2) ?> RON
                                        </p>
                                        <button class="btn btn-sm btn-outline-danger remove-item">
                                            <i class="fas fa-trash"></i> Șterge
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Continuă Cumpărăturile
                    </a>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Sumar Comandă</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Produse:</td>
                                <td class="text-end"><span id="item-count"><?= $summary['item_count'] ?></span></td>
                            </tr>
                            <tr>
                                <td>Cantitate totală:</td>
                                <td class="text-end"><span id="total-quantity"><?= $summary['total_quantity'] ?></span></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong class="text-success" id="subtotal"><?= number_format($summary['subtotal'], 2) ?> RON</strong></td>
                            </tr>
                        </table>
                        
                        <hr>
                        
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            TVA și costuri de livrare vor fi calculate la checkout.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=checkout" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Finalizează Comanda
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>modules/marketplace/?action=cart-clear" 
                                  onsubmit="return confirm('Sigur dorești să golești coșul?');">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-trash me-2"></i>Golește Coșul
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Quantity controls
document.querySelectorAll('.cart-item').forEach(item => {
    const cartId = item.dataset.cartId;
    const input = item.querySelector('.quantity-input');
    const minusBtn = item.querySelector('.qty-minus');
    const plusBtn = item.querySelector('.qty-plus');
    const removeBtn = item.querySelector('.remove-item');
    
    minusBtn.addEventListener('click', () => {
        if (input.value > 1) {
            input.value = parseInt(input.value) - 1;
            updateQuantity(cartId, input.value);
        }
    });
    
    plusBtn.addEventListener('click', () => {
        input.value = parseInt(input.value) + 1;
        updateQuantity(cartId, input.value);
    });
    
    input.addEventListener('change', () => {
        if (input.value < 1) input.value = 1;
        updateQuantity(cartId, input.value);
    });
    
    removeBtn.addEventListener('click', () => {
        if (confirm('Ștergi acest produs din coș?')) {
            removeItem(cartId);
        }
    });
});

function updateQuantity(cartId, quantity) {
    fetch('<?= BASE_URL ?>modules/marketplace/?action=cart-update', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_item_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update totals
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function removeItem(cartId) {
    fetch('<?= BASE_URL ?>modules/marketplace/?action=cart-remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_item_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
