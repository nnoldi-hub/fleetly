<?php 
$pageTitle = 'Comenzile Mele';
require_once __DIR__ . '/../../../includes/header.php'; 
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-list-alt me-3"></i>Comenzile Mele</h1>
        <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-primary">
            <i class="fas fa-store me-2"></i>Marketplace
        </a>
    </div>
    
    <!-- Status Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group w-100" role="group">
                <a href="?action=orders" 
                   class="btn btn-outline-primary <?= empty($statusFilter) ? 'active' : '' ?>">
                    <i class="fas fa-list me-2"></i>Toate (<?= $stats['total'] ?? 0 ?>)
                </a>
                <a href="?action=orders&status=pending" 
                   class="btn btn-outline-warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    <i class="fas fa-clock me-2"></i>În Procesare (<?= $stats['pending'] ?? 0 ?>)
                </a>
                <a href="?action=orders&status=confirmed" 
                   class="btn btn-outline-info <?= $statusFilter === 'confirmed' ? 'active' : '' ?>">
                    <i class="fas fa-check me-2"></i>Confirmate (<?= $stats['confirmed'] ?? 0 ?>)
                </a>
                <a href="?action=orders&status=delivered" 
                   class="btn btn-outline-success <?= $statusFilter === 'delivered' ? 'active' : '' ?>">
                    <i class="fas fa-truck me-2"></i>Livrate (<?= $stats['delivered'] ?? 0 ?>)
                </a>
                <a href="?action=orders&status=cancelled" 
                   class="btn btn-outline-danger <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
                    <i class="fas fa-times me-2"></i>Anulate (<?= $stats['cancelled'] ?? 0 ?>)
                </a>
            </div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-5x text-muted mb-4"></i>
                <h3>Nu Ai Comenzi <?= $statusFilter ? ucfirst($statusFilter) : '' ?></h3>
                <p class="text-muted mb-4">Comenzile tale vor apărea aici după plasare.</p>
                <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Începe să Cumperi
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Orders List -->
        <?php foreach ($orders as $order): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <small class="text-muted">Număr Comandă:</small><br>
                            <strong class="text-primary"><?= htmlspecialchars($order['order_number']) ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Data:</small><br>
                            <strong><?= date('d.m.Y', strtotime($order['created_at'])) ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Total:</small><br>
                            <strong class="text-success"><?= number_format($order['total_amount'], 2) ?> RON</strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Status:</small><br>
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
                            <span class="badge bg-<?= $color ?>"><?= $label ?></span>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=order&id=<?= $order['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Vezi Detalii
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Livrare la:</small>
                            <p class="mb-0">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                <?= htmlspecialchars($order['delivery_address']) ?>,
                                <?= htmlspecialchars($order['delivery_city']) ?>,
                                <?= htmlspecialchars($order['delivery_county']) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Contact:</small>
                            <p class="mb-0">
                                <i class="fas fa-user me-2 text-muted"></i>
                                <?= htmlspecialchars($order['contact_person']) ?> - 
                                <?= htmlspecialchars($order['contact_phone']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=orders&page=<?= $currentPage - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?action=orders&page=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=orders&page=<?= $currentPage + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
