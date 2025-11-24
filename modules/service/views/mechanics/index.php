<?php
/**
 * View: Listă Mecanici
 */

$mechanics = $mechanics ?? [];
$service = $service ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-user-cog"></i> Mecanici Atelier</h2>
            <p class="text-muted"><?= htmlspecialchars($service['name']) ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= ROUTE_BASE ?>service/services" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <a href="<?= ROUTE_BASE ?>service/mechanics/add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Adaugă Mecanic
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($mechanics)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nu există mecanici adăugați. 
                    <a href="<?= ROUTE_BASE ?>service/mechanics/add">Adaugă primul mecanic</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nume</th>
                                <th>Specializare</th>
                                <th>Contact</th>
                                <th>Tarif Orar</th>
                                <th>Ordine Active</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($mechanic['name']) ?></div>
                                        <?php if ($mechanic['hire_date']): ?>
                                            <small class="text-muted">Din <?= date('d.m.Y', strtotime($mechanic['hire_date'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($mechanic['specialization'] ?: '-') ?>
                                    </td>
                                    <td>
                                        <?php if ($mechanic['phone']): ?>
                                            <div><i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($mechanic['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($mechanic['email']): ?>
                                            <div><i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($mechanic['email']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!$mechanic['phone'] && !$mechanic['email']): ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mechanic['hourly_rate'] > 0): ?>
                                            <?= number_format($mechanic['hourly_rate'], 0) ?> RON/h
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mechanic['active_work_orders'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $mechanic['active_work_orders'] ?> ordine</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Disponibil</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mechanic['is_active']): ?>
                                            <span class="badge bg-success">Activ</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= ROUTE_BASE ?>service/mechanics/view?id=<?= $mechanic['id'] ?>" class="btn btn-sm btn-info text-white" title="Vezi detalii">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= ROUTE_BASE ?>service/mechanics/edit?id=<?= $mechanic['id'] ?>" class="btn btn-sm btn-warning text-white" title="Editează">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($mechanic['is_active']): ?>
                                            <a href="<?= ROUTE_BASE ?>service/mechanics/delete?id=<?= $mechanic['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Sigur vrei să dezactivezi acest mecanic?')" 
                                               title="Dezactivează">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
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
