<?php 
$pageTitle = 'Detalii Comandă #' . $order['order_number'];
require_once __DIR__ . '/../../../includes/header.php'; 
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/">Marketplace</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=orders">Comenzile Mele</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_number']) ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8 mb-4">
            <!-- Order Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice me-2"></i>
                            Comandă #<?= htmlspecialchars($order['order_number']) ?>
                        </h5>
                        <?php
                        $statusColors = [
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'processing' => 'primary',
                            'shipped' => 'info',
                            'delivered' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $statusLabels = [
                            'pending' => 'În Procesare',
                            'confirmed' => 'Confirmată',
                            'processing' => 'Se Procesează',
                            'shipped' => 'Expediată',
                            'delivered' => 'Livrată',
                            'cancelled' => 'Anulată'
                        ];
                        $color = $statusColors[$order['status']] ?? 'secondary';
                        $label = $statusLabels[$order['status']] ?? $order['status'];
                        ?>
                        <span class="badge bg-<?= $color ?> fs-6"><?= $label ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Data Plasare:</small>
                            <p class="mb-0"><strong><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted">Ultima Actualizare:</small>
                            <p class="mb-0"><strong><?= date('d.m.Y H:i', strtotime($order['updated_at'])) ?></strong></p>
                        </div>
                        <?php if ($order['status_updated_at']): ?>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Status Actualizat La:</small>
                                <p class="mb-0"><strong><?= date('d.m.Y H:i', strtotime($order['status_updated_at'])) ?></strong></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['payment_method']): ?>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Metodă de Plată:</small>
                                <p class="mb-0">
                                    <strong>
                                        <?php
                                        $paymentLabels = [
                                            'invoice' => 'Factură (30 zile)',
                                            'card' => 'Card Bancar',
                                            'transfer' => 'Transfer Bancar'
                                        ];
                                        echo $paymentLabels[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </strong>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Produse Comandate</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produs</th>
                                <th class="text-center">Cantitate</th>
                                <th class="text-end">Preț Unitar</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['product_image']): ?>
                                                <img src="<?= BASE_URL . htmlspecialchars($item['product_image']) ?>" 
                                                     class="me-3 rounded" 
                                                     style="width: 50px; height: 50px; object-fit: cover;" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;" class="me-3">
                                                    <i class="fas fa-box text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-secondary"><?= $item['quantity'] ?></span>
                                    </td>
                                    <td class="text-end align-middle">
                                        <?= number_format($item['price_at_order'], 2) ?> RON
                                    </td>
                                    <td class="text-end align-middle">
                                        <strong><?= number_format($item['line_total'], 2) ?> RON</strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong><?= number_format($totals['subtotal'], 2) ?> RON</strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>TVA (19%):</strong></td>
                                <td class="text-end"><strong><?= number_format($totals['tax'], 2) ?> RON</strong></td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong class="fs-5 text-success"><?= number_format($totals['total'], 2) ?> RON</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Notes -->
            <?php if ($order['notes']): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-comment me-2"></i>Observații</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Delivery Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Detalii Livrare</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-user me-2 text-muted"></i>
                        <strong><?= htmlspecialchars($order['contact_person']) ?></strong>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-phone me-2 text-muted"></i>
                        <?= htmlspecialchars($order['contact_phone']) ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2 text-muted"></i>
                        <?= htmlspecialchars($order['contact_email']) ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                        <strong>Adresă:</strong><br>
                        <span class="ms-4">
                            <?= htmlspecialchars($order['delivery_address']) ?><br>
                            <?= htmlspecialchars($order['delivery_city']) ?>, 
                            <?= htmlspecialchars($order['delivery_county']) ?>
                            <?= $order['delivery_postal'] ? ', ' . htmlspecialchars($order['delivery_postal']) : '' ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Acțiuni</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=orders" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Toate Comenzile
                        </a>
                        <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-outline-secondary">
                            <i class="fas fa-store me-2"></i>Marketplace
                        </a>
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-outline-danger" onclick="cancelOrder()">
                                <i class="fas fa-times me-2"></i>Anulează Comanda
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Support -->
            <div class="alert alert-info">
                <h6><i class="fas fa-question-circle me-2"></i>Ai întrebări?</h6>
                <p class="small mb-0">
                    Contactează echipa noastră pentru suport:<br>
                    <i class="fas fa-envelope me-1"></i> support@fleetly.ro<br>
                    <i class="fas fa-phone me-1"></i> +40 123 456 789
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function cancelOrder() {
    if (confirm('Sigur dorești să anulezi această comandă?')) {
        // TODO: Implement order cancellation
        alert('Funcționalitatea de anulare va fi implementată în curând.');
    }
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
