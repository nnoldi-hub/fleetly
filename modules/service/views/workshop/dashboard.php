<?php
/**
 * View: Dashboard Atelier
 * Dashboard pentru gestionare ordine de lucru service intern
 */

$stats = $stats ?? [];
$workOrders = $workOrders ?? [];
$filters = $filters ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-tools"></i> Dashboard Atelier</h2>
            <p class="text-muted"><?= htmlspecialchars($service['name'] ?? 'Atelier Intern') ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>/service/workshop/add" class="btn btn-success">
                <i class="fas fa-plus"></i> Ordine de Lucru Nouă
            </a>
            <a href="<?= ROUTE_BASE ?>/service/workshop/vehicles" class="btn btn-primary">
                <i class="fas fa-car"></i> Vehicule în Service
            </a>
        </div>
    </div>

    <!-- Mesaje -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Statistici -->
    <div class="row mb-4">
        <!-- Capacitate Atelier -->
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="mb-0">
                        <span class="text-primary"><?= $stats['occupied_posts'] ?? 0 ?></span> / 
                        <span class="text-muted"><?= $stats['capacity'] ?? 0 ?></span>
                    </h3>
                    <p class="text-muted mb-0">Posturi Ocupate</p>
                    <div class="progress mt-2" style="height: 8px;">
                        <?php 
                        $capacity = $stats['capacity'] ?? 1;
                        $occupied = $stats['occupied_posts'] ?? 0;
                        $percentage = ($occupied / $capacity) * 100;
                        $progressColor = $percentage >= 90 ? 'danger' : ($percentage >= 70 ? 'warning' : 'success');
                        ?>
                        <div class="progress-bar bg-<?= $progressColor ?>" 
                             style="width: <?= min(100, $percentage) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistici Astăzi -->
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success">
                        <?= $stats['today']['completed_today'] ?? 0 ?>
                    </h3>
                    <p class="text-muted mb-0">Finalizate Astăzi</p>
                    <small class="text-muted">
                        <?= number_format($stats['today']['hours_worked_today'] ?? 0, 1) ?>h lucrate
                    </small>
                </div>
            </div>
        </div>

        <!-- În Lucru -->
        <div class="col-md-3 mb-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-warning">
                        <?php
                        $inProgress = 0;
                        if (isset($stats['by_status']) && is_array($stats['by_status'])) {
                            foreach ($stats['by_status'] as $statusRow) {
                                if ($statusRow['status'] === 'in_progress') {
                                    $inProgress = $statusRow['count'];
                                    break;
                                }
                            }
                        }
                        echo $inProgress;
                        ?>
                    </h3>
                    <p class="text-muted mb-0">În Lucru Acum</p>
                </div>
            </div>
        </div>

        <!-- Venit Astăzi -->
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info">
                        <?= number_format($stats['today']['revenue_today'] ?? 0, 0) ?> RON
                    </h3>
                    <p class="text-muted mb-0">Venit Astăzi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= ROUTE_BASE ?>/service/workshop" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Toate</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>În Așteptare</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>În Lucru</option>
                        <option value="waiting_parts" <?= ($filters['status'] ?? '') === 'waiting_parts' ? 'selected' : '' ?>>Așteptare Piese</option>
                        <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Finalizat</option>
                        <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Livrat</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prioritate</label>
                    <select name="priority" class="form-select">
                        <option value="">Toate</option>
                        <option value="urgent" <?= ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Ridicată</option>
                        <option value="normal" <?= ($filters['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>Normală</option>
                        <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Scăzută</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mecanic</label>
                    <select name="mechanic_id" class="form-select">
                        <option value="">Toți</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?= $mechanic['id'] ?>" 
                                    <?= ($filters['mechanic_id'] ?? '') == $mechanic['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mechanic['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?= $filters['date_from'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?= $filters['date_to'] ?? '' ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista Ordine de Lucru -->
    <?php if (empty($workOrders)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există ordine de lucru pentru criteriile selectate.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Ordine de Lucru (<?= count($workOrders) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Număr</th>
                                <th>Vehicul</th>
                                <th>Intrare</th>
                                <th>Mecanic</th>
                                <th>Status</th>
                                <th>Prioritate</th>
                                <th>Timp Estimat</th>
                                <th>Cost</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workOrders as $wo): ?>
                                <tr class="<?= $wo['priority'] === 'urgent' ? 'table-danger' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($wo['work_order_number']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($wo['plate_number']) ?></span>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($wo['make'] . ' ' . $wo['model']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?= date('d.m.Y H:i', strtotime($wo['entry_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($wo['mechanic_name']): ?>
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($wo['mechanic_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nealocat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusLabels = [
                                            'pending' => ['badge' => 'secondary', 'text' => 'În Așteptare'],
                                            'in_progress' => ['badge' => 'primary', 'text' => 'În Lucru'],
                                            'waiting_parts' => ['badge' => 'warning', 'text' => 'Aștept. Piese'],
                                            'completed' => ['badge' => 'success', 'text' => 'Finalizat'],
                                            'delivered' => ['badge' => 'info', 'text' => 'Livrat']
                                        ];
                                        $statusInfo = $statusLabels[$wo['status']] ?? ['badge' => 'secondary', 'text' => $wo['status']];
                                        ?>
                                        <span class="badge bg-<?= $statusInfo['badge'] ?>">
                                            <?= $statusInfo['text'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priorityLabels = [
                                            'urgent' => ['badge' => 'danger', 'icon' => 'exclamation-triangle'],
                                            'high' => ['badge' => 'warning', 'icon' => 'arrow-up'],
                                            'normal' => ['badge' => 'info', 'icon' => 'minus'],
                                            'low' => ['badge' => 'secondary', 'icon' => 'arrow-down']
                                        ];
                                        $priorityInfo = $priorityLabels[$wo['priority']] ?? ['badge' => 'secondary', 'icon' => 'minus'];
                                        ?>
                                        <span class="badge bg-<?= $priorityInfo['badge'] ?>">
                                            <i class="fas fa-<?= $priorityInfo['icon'] ?>"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($wo['estimated_hours']): ?>
                                            <small><?= number_format($wo['estimated_hours'], 1) ?>h</small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($wo['total_cost'] > 0): ?>
                                            <strong><?= number_format($wo['total_cost'], 0) ?> RON</strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= ROUTE_BASE ?>/service/workshop/view?id=<?= $wo['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Detalii">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    transition: all 0.3s;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.table tbody tr {
    transition: background-color 0.2s;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>

<script>
// Auto-refresh dashboard la fiecare 60 secunde
let autoRefresh = setInterval(function() {
    location.reload();
}, 60000);

// Oprește auto-refresh dacă utilizatorul interacționează
document.addEventListener('click', function() {
    clearInterval(autoRefresh);
});
</script>
