<?php
/**
 * View: Detalii Mecanic
 */

$mechanic = $mechanic ?? [];
$activeOrders = $activeOrders ?? [];
$stats = $stats ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-user-cog"></i> <?= htmlspecialchars($mechanic['name']) ?></h2>
            <?php if ($mechanic['specialization']): ?>
                <p class="text-muted"><?= htmlspecialchars($mechanic['specialization']) ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= ROUTE_BASE ?>service/mechanics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <a href="<?= ROUTE_BASE ?>service/mechanics/edit?id=<?= $mechanic['id'] ?>" class="btn btn-warning text-white">
                <i class="fas fa-edit"></i> Editează
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Informații Generale -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informații Contact</h5>
                </div>
                <div class="card-body">
                    <?php if ($mechanic['phone']): ?>
                        <div class="mb-2">
                            <i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($mechanic['phone']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($mechanic['email']): ?>
                        <div class="mb-2">
                            <i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($mechanic['email']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($mechanic['hire_date']): ?>
                        <div class="mb-2">
                            <i class="fas fa-calendar text-muted"></i> Angajat din <?= date('d.m.Y', strtotime($mechanic['hire_date'])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <i class="fas fa-money-bill-wave text-muted"></i> <?= number_format($mechanic['hourly_rate'], 0) ?> RON/oră
                    </div>
                    <div class="mt-3">
                        <?php if ($mechanic['is_active']): ?>
                            <span class="badge bg-success">Activ</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactiv</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistici -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Statistici</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Total Ordine</div>
                        <div class="h4"><?= (int)($stats['total_orders'] ?? 0) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Ordine Finalizate</div>
                        <div class="h4"><?= (int)($stats['completed_orders'] ?? 0) ?></div>
                    </div>
                    <?php if (isset($stats['avg_hours']) && $stats['avg_hours'] > 0): ?>
                        <div class="mb-3">
                            <div class="text-muted small">Ore Medii/Ordine</div>
                            <div class="h4"><?= number_format($stats['avg_hours'], 1) ?>h</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ordine Active -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Ordine Active</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($activeOrders)): ?>
                        <div class="alert alert-info">
                            Nu are ordine active momentan. Mecanic disponibil pentru alocări.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vehicul</th>
                                        <th>Intrare</th>
                                        <th>Status</th>
                                        <th>Prioritate</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($order['plate_number']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($order['make'] . ' ' . $order['model']) ?></small>
                                            </td>
                                            <td><?= date('d.m.Y H:i', strtotime($order['entry_date'])) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'bg-warning text-dark',
                                                    'in_progress' => 'bg-primary',
                                                ][$order['status']] ?? 'bg-secondary';
                                                
                                                $statusLabel = [
                                                    'pending' => 'În așteptare',
                                                    'in_progress' => 'În lucru',
                                                ][$order['status']] ?? $order['status'];
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $priorityIcon = [
                                                    'low' => '<i class="fas fa-arrow-down text-success"></i>',
                                                    'normal' => '<i class="fas fa-minus text-secondary"></i>',
                                                    'high' => '<i class="fas fa-arrow-up text-warning"></i>',
                                                    'urgent' => '<i class="fas fa-exclamation-triangle text-danger"></i>'
                                                ][$order['priority']] ?? '';
                                                ?>
                                                <?= $priorityIcon ?> <?= ucfirst($order['priority']) ?>
                                            </td>
                                            <td>
                                                <a href="<?= ROUTE_BASE ?>service/workshop/view?id=<?= $order['id'] ?>" class="btn btn-sm btn-info text-white">
                                                    <i class="fas fa-eye"></i> Detalii
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
