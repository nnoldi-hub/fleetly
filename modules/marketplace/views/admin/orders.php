<?php 
$pageTitle = 'Admin - Comenzi';
require_once __DIR__ . '/../../../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-shopping-cart me-3"></i>Gestionare Comenzi</h1>
        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-dashboard" class="btn btn-outline-primary">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
    </div>
    
    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">În Procesare</h6>
                    <h2 class="mb-0 text-warning"><?= $stats['pending'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Confirmate</h6>
                    <h2 class="mb-0 text-info"><?= $stats['confirmed'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Livrate</h6>
                    <h2 class="mb-0 text-success"><?= $stats['delivered'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Total Comenzi</h6>
                    <h2 class="mb-0 text-primary"><?= $totalOrders ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>modules/marketplace/" class="row g-3">
                <input type="hidden" name="action" value="admin-orders">
                
                <div class="col-md-3">
                    <label class="form-label">Status:</label>
                    <select class="form-select" name="status">
                        <option value="">Toate Statusurile</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>În Procesare</option>
                        <option value="confirmed" <?= ($statusFilter === 'confirmed') ? 'selected' : '' ?>>Confirmate</option>
                        <option value="processing" <?= ($statusFilter === 'processing') ? 'selected' : '' ?>>Se Procesează</option>
                        <option value="shipped" <?= ($statusFilter === 'shipped') ? 'selected' : '' ?>>Expediate</option>
                        <option value="delivered" <?= ($statusFilter === 'delivered') ? 'selected' : '' ?>>Livrate</option>
                        <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Anulate</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Companie:</label>
                    <select class="form-select" name="company">
                        <option value="">Toate Companiile</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>" <?= ($companyFilter == $comp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($comp['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">De la Data:</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Până la Data:</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrează
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Comenzi (<?= $totalOrders ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Număr Comandă</th>
                        <th>Companie</th>
                        <th>Contact</th>
                        <th>Data</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                Nu s-au găsit comenzi
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($order['order_number']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($order['company_name']) ?></strong>
                                </td>
                                <td>
                                    <small>
                                        <?= htmlspecialchars($order['contact_person']) ?><br>
                                        <i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($order['contact_phone']) ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success"><?= number_format($order['total_amount'], 2) ?> RON</strong>
                                </td>
                                <td>
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
                                    <select class="form-select form-select-sm status-select" 
                                            data-order-id="<?= $order['id'] ?>"
                                            onchange="updateStatus(this, <?= $order['id'] ?>)">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>În Procesare</option>
                                        <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmată</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Se Procesează</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Expediată</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Livrată</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Anulată</option>
                                    </select>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-order&id=<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Detalii
                                    </a>
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
                                <a class="page-link" href="?action=admin-orders&page=<?= $currentPage - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $companyFilter ? '&company=' . $companyFilter : '' ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?action=admin-orders&page=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $companyFilter ? '&company=' . $companyFilter : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?action=admin-orders&page=<?= $currentPage + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $companyFilter ? '&company=' . $companyFilter : '' ?>">
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
function updateStatus(select, orderId) {
    const newStatus = select.value;
    const originalValue = select.dataset.originalValue || select.value;
    
    if (!confirm(`Schimbi statusul comenzii la "${select.options[select.selectedIndex].text}"?`)) {
        select.value = originalValue;
        return;
    }
    
    fetch('<?= BASE_URL ?>modules/marketplace/?action=admin-order-status', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            select.dataset.originalValue = newStatus;
            // Show success notification
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>Status actualizat cu succes!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('Eroare: ' + data.message);
            select.value = originalValue;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la actualizare status');
        select.value = originalValue;
    });
}

// Store original values
document.querySelectorAll('.status-select').forEach(select => {
    select.dataset.originalValue = select.value;
});
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>
