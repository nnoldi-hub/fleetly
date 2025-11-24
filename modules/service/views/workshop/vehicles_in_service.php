<?php
/**
 * View: Vehicule în Atelier
 */

$workOrders = $workOrders ?? [];
$service = $service ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-warehouse"></i> Vehicule în Atelier</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= ROUTE_BASE ?>service/workshop" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi la Dashboard
            </a>
            <a href="<?= ROUTE_BASE ?>service/workshop/add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ordine Nouă
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($workOrders)): ?>
                <div class="alert alert-info">
                    Nu există vehicule în atelier momentan.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicul</th>
                                <th>Intrare</th>
                                <th>Status</th>
                                <th>Prioritate</th>
                                <th>Mecanic</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workOrders as $wo): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($wo['plate_number']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($wo['make'] . ' ' . $wo['model']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= date('d.m.Y', strtotime($wo['entry_date'])) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($wo['entry_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'bg-warning text-dark',
                                            'in_progress' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ][$wo['status']] ?? 'bg-secondary';
                                        
                                        $statusLabel = [
                                            'pending' => 'În așteptare',
                                            'in_progress' => 'În lucru',
                                            'completed' => 'Finalizat',
                                            'cancelled' => 'Anulat'
                                        ][$wo['status']] ?? $wo['status'];
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
                                        ][$wo['priority']] ?? '';
                                        ?>
                                        <?= $priorityIcon ?> <?= ucfirst($wo['priority']) ?>
                                    </td>
                                    <td>
                                        <?php if ($wo['mechanic_name']): ?>
                                            <i class="fas fa-user-cog text-muted"></i> <?= htmlspecialchars($wo['mechanic_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><em>Nealocat</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= ROUTE_BASE ?>service/workshop/view/<?= $wo['id'] ?>" class="btn btn-sm btn-info text-white" title="Vezi detalii">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= ROUTE_BASE ?>service/workshop/edit/<?= $wo['id'] ?>" class="btn btn-sm btn-warning text-white" title="Editează">
                                            <i class="fas fa-edit"></i>
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
