<?php 
$pageTitle = 'Admin - Detalii Comandă #' . $order['order_number'];
require_once __DIR__ . '/../../../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=admin-dashboard">Admin Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=admin-orders">Comenzi</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_number']) ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-file-invoice me-3"></i>
            Comandă #<?= htmlspecialchars($order['order_number']) ?>
        </h1>
        <div>
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
            <span class="badge bg-<?= $color ?> fs-5 me-3"><?= $label ?></span>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                <i class="fas fa-edit me-2"></i>Schimbă Status
            </button>
        </div>
    </div>
    
    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8 mb-4">
            <!-- Company & Contact Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Informații Companie & Contact</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Companie:</h6>
                            <p class="mb-0"><strong class="fs-5"><?= htmlspecialchars($order['company_name']) ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Persoană Contact:</h6>
                            <p class="mb-0"><strong><?= htmlspecialchars($order['contact_person']) ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Telefon:</h6>
                            <p class="mb-0">
                                <i class="fas fa-phone text-success me-2"></i>
                                <a href="tel:<?= htmlspecialchars($order['contact_phone']) ?>"><?= htmlspecialchars($order['contact_phone']) ?></a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Email:</h6>
                            <p class="mb-0">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <a href="mailto:<?= htmlspecialchars($order['contact_email']) ?>"><?= htmlspecialchars($order['contact_email']) ?></a>
                            </p>
                        </div>
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
                                                     style="width: 60px; height: 60px; object-fit: cover;" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;" class="me-3">
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
                                        <span class="badge bg-secondary fs-6"><?= $item['quantity'] ?></span>
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
                                <td class="text-end"><strong class="fs-4 text-success"><?= number_format($totals['total'], 2) ?> RON</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Notes -->
            <?php if ($order['notes']): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-comment me-2"></i>Observații Client</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Order Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Detalii Comandă</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Data Plasare:</td>
                            <td class="text-end"><strong><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ultima Actualizare:</td>
                            <td class="text-end"><strong><?= date('d.m.Y H:i', strtotime($order['updated_at'])) ?></strong></td>
                        </tr>
                        <?php if ($order['status_updated_at']): ?>
                            <tr>
                                <td class="text-muted">Status Actualizat:</td>
                                <td class="text-end"><strong><?= date('d.m.Y H:i', strtotime($order['status_updated_at'])) ?></strong></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted">Metodă de Plată:</td>
                            <td class="text-end">
                                <strong>
                                    <?php
                                    $paymentLabels = [
                                        'invoice' => 'Factură',
                                        'card' => 'Card',
                                        'transfer' => 'Transfer'
                                    ];
                                    echo $paymentLabels[$order['payment_method']] ?? $order['payment_method'];
                                    ?>
                                </strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Delivery Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Adresă Livrare</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong><?= htmlspecialchars($order['delivery_address']) ?></strong></p>
                    <p class="mb-1"><?= htmlspecialchars($order['delivery_city']) ?>, <?= htmlspecialchars($order['delivery_county']) ?></p>
                    <?php if ($order['delivery_postal']): ?>
                        <p class="mb-0">Cod Poștal: <?= htmlspecialchars($order['delivery_postal']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Acțiuni</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <i class="fas fa-edit me-2"></i>Schimbă Status
                        </button>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-orders" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Înapoi la Comenzi
                        </a>
                        <button class="btn btn-outline-info" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Printează
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Schimbă Status Comandă</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Actual:</label>
                        <p class="fs-5"><span class="badge bg-<?= $color ?>"><?= $label ?></span></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Nou: <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="status" required>
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>În Procesare</option>
                            <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmată</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Se Procesează</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Expediată</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Livrată</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Anulată</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvează Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const newStatus = formData.get('status');
    
    fetch('<?= BASE_URL ?>modules/marketplace/?action=admin-order-status', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=<?= $order['id'] ?>&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la actualizare status');
    });
});
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>
