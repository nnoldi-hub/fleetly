<?php
/**
 * View: Raport Costuri pe Vehicul
 */
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-car"></i> Raport Costuri pe Vehicul</h2>
            <p class="text-muted">
                Perioadă: <?= date('d.m.Y', strtotime($dateFrom)) ?> - <?= date('d.m.Y', strtotime($dateTo)) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>service/reports" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <a href="<?= ROUTE_BASE ?>service/reports/export?type=vehicle_costs&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
               class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/vehicle-costs" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicul Specific (opțional)</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">Toate vehiculele</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $vehicleId == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['registration_number'] . ' - ' . $v['brand'] . ' ' . $v['model']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Caută
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel costuri pe vehicul -->
    <?php if (empty($vehicleCosts)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există date pentru perioada selectată.
        </div>
    <?php else: ?>
        <!-- Sumar -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= count($vehicleCosts) ?></h3>
                        <p class="mb-0">Vehicule</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">
                            <?php
                            $totalVisits = array_sum(array_column($vehicleCosts, 'service_visits'));
                            echo $totalVisits;
                            ?>
                        </h3>
                        <p class="mb-0">Vizite Service</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">
                            <?php
                            $totalParts = array_sum(array_column($vehicleCosts, 'total_parts_cost'));
                            echo number_format($totalParts, 0);
                            ?> RON
                        </h3>
                        <p class="mb-0">Cost Piese</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">
                            <?php
                            $totalCost = array_sum(array_column($vehicleCosts, 'total_service_cost'));
                            echo number_format($totalCost, 0);
                            ?> RON
                        </h3>
                        <p class="mb-0">Cost Total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel detaliat -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Costuri Detaliate pe Vehicul</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicul</th>
                                <th>Marcă/Model</th>
                                <th>An</th>
                                <th class="text-center">Vizite</th>
                                <th class="text-end">Piese</th>
                                <th class="text-end">Manoperă</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Cost/Vizită</th>
                                <th>Ultima Vizită</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicleCosts as $vc): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($vc['registration_number']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($vc['brand'] . ' ' . $vc['model']) ?></td>
                                    <td><?= $vc['year'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $vc['service_visits'] ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($vc['total_parts_cost'] ?? 0, 2) ?> RON</td>
                                    <td class="text-end"><?= number_format($vc['total_labor_cost'] ?? 0, 2) ?> RON</td>
                                    <td class="text-end">
                                        <strong><?= number_format($vc['total_service_cost'] ?? 0, 2) ?> RON</strong>
                                    </td>
                                    <td class="text-end">
                                        <?= $vc['service_visits'] > 0 ? number_format($vc['avg_cost_per_visit'], 2) : '0.00' ?> RON
                                    </td>
                                    <td>
                                        <?php if ($vc['last_service_date']): ?>
                                            <small><?= date('d.m.Y', strtotime($vc['last_service_date'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3">TOTAL</th>
                                <th class="text-center"><?= array_sum(array_column($vehicleCosts, 'service_visits')) ?></th>
                                <th class="text-end"><?= number_format(array_sum(array_column($vehicleCosts, 'total_parts_cost')), 2) ?> RON</th>
                                <th class="text-end"><?= number_format(array_sum(array_column($vehicleCosts, 'total_labor_cost')), 2) ?> RON</th>
                                <th class="text-end">
                                    <strong><?= number_format(array_sum(array_column($vehicleCosts, 'total_service_cost')), 2) ?> RON</strong>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detalii vehicul specific -->
        <?php if ($vehicleDetails): ?>
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Istoric Service</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Număr Ordine</th>
                                    <th>Data</th>
                                    <th>Mecanic</th>
                                    <th>Descriere</th>
                                    <th>Status</th>
                                    <th class="text-end">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicleDetails as $detail): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= ROUTE_BASE ?>service/workshop/view?id=<?= $detail['id'] ?>">
                                                <?= htmlspecialchars($detail['work_order_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($detail['entry_date'])) ?></td>
                                        <td><?= htmlspecialchars($detail['mechanic_name'] ?? 'Nealocat') ?></td>
                                        <td><?= htmlspecialchars(substr($detail['work_description'], 0, 50)) ?></td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'completed' => 'success',
                                                'delivered' => 'info',
                                                'in_progress' => 'primary',
                                                'pending' => 'secondary'
                                            ];
                                            $badge = $badges[$detail['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= $detail['status'] ?></span>
                                        </td>
                                        <td class="text-end"><?= number_format($detail['total_cost'], 2) ?> RON</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
</style>
