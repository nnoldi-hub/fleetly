<?php 
$pageTitle = 'Marketplace Admin - Dashboard';
?>

<link href="<?= BASE_URL ?>assets/css/marketplace.css" rel="stylesheet">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tachometer-alt me-3"></i>Marketplace Dashboard</h1>
        <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-outline-primary">
            <i class="fas fa-store me-2"></i>Marketplace Public
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Produse</h6>
                            <h2 class="mb-0"><?= $stats['total_products'] ?></h2>
                        </div>
                        <i class="fas fa-box fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-primary bg-opacity-75 border-0">
                    <small><i class="fas fa-check-circle me-1"></i><?= $stats['active_products'] ?> active</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Comenzi Astăzi</h6>
                            <h2 class="mb-0"><?= $stats['orders_today'] ?></h2>
                        </div>
                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-success bg-opacity-75 border-0">
                    <small>Total: <?= number_format($stats['revenue_today'], 2) ?> RON</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Comenzi Pending</h6>
                            <h2 class="mb-0"><?= $stats['orders_pending'] ?></h2>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-warning bg-opacity-75 border-0">
                    <small><i class="fas fa-exclamation-circle me-1"></i>Necesită atenție</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Venit Luna</h6>
                            <h2 class="mb-0"><?= number_format($stats['revenue_month'], 0) ?></h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-info bg-opacity-75 border-0">
                    <small>RON (<?= $stats['orders_month'] ?> comenzi)</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Comenzi Recente</h5>
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-orders" class="btn btn-sm btn-outline-primary">
                        Vezi Toate
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Număr</th>
                                <th>Companie</th>
                                <th>Data</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        Nu există comenzi recente
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?= htmlspecialchars($order['order_number']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($order['company_name']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
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
                                            $color = $statusColors[$order['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($order['status']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-order&id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acțiuni Rapide</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-product-form" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        <strong>Adaugă Produs Nou</strong>
                    </a>
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-products" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-boxes text-primary me-2"></i>
                        <strong>Gestionează Produse</strong>
                    </a>
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-orders&status=pending" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <strong>Comenzi în Așteptare (<?= $stats['orders_pending'] ?>)</strong>
                    </a>
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-orders" 
                       class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt text-info me-2"></i>
                        <strong>Toate Comenzile</strong>
                    </a>
                </div>
            </div>
            
            <!-- Category Stats -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Produse pe Categorii</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($categoryStats as $cat): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                                <span class="badge bg-primary rounded-pill"><?= $cat['product_count'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
