<?php
/**
 * View: Raport Activitate (Audit Log)
 */

$activities = $activities ?? [];
$stats = $stats ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-tasks"></i> Raport Activitate</h2>
            <p class="text-muted">
                Perioadă: <?= date('d.m.Y', strtotime($dateFrom)) ?> - <?= date('d.m.Y', strtotime($dateTo)) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>service/reports" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/activity-log" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tip Activitate</label>
                    <select name="activity_type" class="form-select">
                        <option value="">Toate</option>
                        <option value="work_order" <?= $activityType == 'work_order' ? 'selected' : '' ?>>Ordine de Lucru</option>
                        <option value="status_change" <?= $activityType == 'status_change' ? 'selected' : '' ?>>Schimbări Status</option>
                        <option value="part" <?= $activityType == 'part' ? 'selected' : '' ?>>Adăugare Piese</option>
                        <option value="labor" <?= $activityType == 'labor' ? 'selected' : '' ?>>Sesiuni Lucru</option>
                    </select>
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

    <!-- Statistici -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['total_activities'] ?? 0 ?></h3>
                    <p class="mb-0">Activități Totale</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['work_orders_created'] ?? 0 ?></h3>
                    <p class="mb-0">Ordine Create</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['status_changes'] ?? 0 ?></h3>
                    <p class="mb-0">Schimbări Status</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['parts_added'] ?? 0 ?></h3>
                    <p class="mb-0">Piese Adăugate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['labor_sessions'] ?? 0 ?></h3>
                    <p class="mb-0">Sesiuni Lucru</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Activități -->
    <?php if (empty($activities)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există activități pentru perioada selectată.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Istoric Activități (ultimele 200)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 160px;">Data/Ora</th>
                                <th style="width: 100px;">Tip</th>
                                <th style="width: 120px;">Acțiune</th>
                                <th>Descriere</th>
                                <th style="width: 150px;">Entitate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $lastDate = '';
                            foreach ($activities as $activity): 
                                $currentDate = date('Y-m-d', strtotime($activity['activity_date']));
                                $showDateSeparator = ($currentDate != $lastDate);
                                $lastDate = $currentDate;
                            ?>
                                <?php if ($showDateSeparator): ?>
                                    <tr class="table-secondary">
                                        <td colspan="5">
                                            <strong><i class="fas fa-calendar-day"></i> <?= date('d F Y', strtotime($currentDate)) ?></strong>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('H:i:s', strtotime($activity['activity_date'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $typeIcons = [
                                            'work_order' => ['icon' => 'file-alt', 'color' => 'primary'],
                                            'part' => ['icon' => 'cog', 'color' => 'warning'],
                                            'labor' => ['icon' => 'user-clock', 'color' => 'info']
                                        ];
                                        $typeInfo = $typeIcons[$activity['type']] ?? ['icon' => 'circle', 'color' => 'secondary'];
                                        ?>
                                        <span class="badge bg-<?= $typeInfo['color'] ?>">
                                            <i class="fas fa-<?= $typeInfo['icon'] ?>"></i> 
                                            <?= ucfirst($activity['type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $actionIcons = [
                                            'created' => ['icon' => 'plus-circle', 'color' => 'success'],
                                            'status_changed' => ['icon' => 'exchange-alt', 'color' => 'info'],
                                            'added' => ['icon' => 'plus', 'color' => 'primary'],
                                            'started' => ['icon' => 'play', 'color' => 'warning']
                                        ];
                                        $actionInfo = $actionIcons[$activity['action']] ?? ['icon' => 'dot-circle', 'color' => 'secondary'];
                                        ?>
                                        <span class="badge bg-<?= $actionInfo['color'] ?>">
                                            <i class="fas fa-<?= $actionInfo['icon'] ?>"></i>
                                            <?= ucfirst(str_replace('_', ' ', $activity['action'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($activity['description']) ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <strong><?= htmlspecialchars($activity['entity_name']) ?></strong>
                                            <br>ID: <?= $activity['entity_id'] ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                <small>
                    <i class="fas fa-info-circle"></i> 
                    Afișate ultimele 200 de activități. Folosește filtrele pentru a limita rezultatele.
                </small>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.table tbody tr.table-secondary td {
    font-weight: 600;
    padding: 10px 15px;
    background-color: #e9ecef;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>
