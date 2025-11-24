<?php
/**
 * View: Raport Timpi de Lucru
 */

$timeStats = $timeStats ?? [];
$delayedOrders = $delayedOrders ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-clock"></i> Raport Timpi de Lucru</h2>
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
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/work-times" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Aplică
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($timeStats) || ($timeStats['total_orders'] ?? 0) == 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există date pentru perioada selectată.
        </div>
    <?php else: ?>
        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= $timeStats['total_orders'] ?></h3>
                        <p class="mb-0">Ordine Finalizate</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= number_format($timeStats['avg_estimated_hours'] ?? 0, 1) ?>h</h3>
                        <p class="mb-0">Timp Mediu Estimat</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= number_format($timeStats['avg_actual_hours'] ?? 0, 1) ?>h</h3>
                        <p class="mb-0">Timp Mediu Real</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= number_format($timeStats['avg_total_time_hours'] ?? 0, 1) ?>h</h3>
                        <p class="mb-0">Timp Total Mediu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistici On-Time vs Delayed -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Ordine La Timp</h5>
                    </div>
                    <div class="card-body text-center">
                        <h1 class="display-3 text-success"><?= $timeStats['on_time_count'] ?? 0 ?></h1>
                        <p class="text-muted">
                            <?php 
                            $onTimePercent = $timeStats['total_orders'] > 0 
                                ? ($timeStats['on_time_count'] / $timeStats['total_orders'] * 100) 
                                : 0;
                            ?>
                            <?= number_format($onTimePercent, 1) ?>% din total
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Ordine Întârziate</h5>
                    </div>
                    <div class="card-body text-center">
                        <h1 class="display-3 text-danger"><?= $timeStats['delayed_count'] ?? 0 ?></h1>
                        <p class="text-muted">
                            <?php 
                            $delayedPercent = $timeStats['total_orders'] > 0 
                                ? ($timeStats['delayed_count'] / $timeStats['total_orders'] * 100) 
                                : 0;
                            ?>
                            <?= number_format($delayedPercent, 1) ?>% din total
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafic Comparativ -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Comparație Estimat vs Real</h5>
            </div>
            <div class="card-body">
                <canvas id="timeComparisonChart" height="60"></canvas>
            </div>
        </div>

        <!-- Tabel Ordine Întârziate -->
        <?php if (!empty($delayedOrders)): ?>
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Top 20 Ordine cu Cele Mai Mari Întârzieri</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Număr Ordine</th>
                                    <th>Vehicul</th>
                                    <th>Mecanic</th>
                                    <th>Data Intrare</th>
                                    <th>Data Finalizare</th>
                                    <th class="text-center">Estimat</th>
                                    <th class="text-center">Real</th>
                                    <th class="text-center">Întârziere</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delayedOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= ROUTE_BASE ?>service/workshop/view?id=<?= $order['id'] ?? '' ?>">
                                                <?= htmlspecialchars($order['work_order_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($order['registration_number']) ?></strong><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($order['brand'] . ' ' . $order['model']) ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($order['mechanic_name'] ?? 'Nealocat') ?></td>
                                        <td><small><?= date('d.m.Y H:i', strtotime($order['entry_date'])) ?></small></td>
                                        <td><small><?= date('d.m.Y H:i', strtotime($order['actual_completion'])) ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= number_format($order['estimated_hours'], 1) ?>h</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning"><?= number_format($order['actual_hours'], 1) ?>h</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">
                                                +<?= number_format($order['hours_over'], 1) ?>h
                                                (<?= number_format(($order['hours_over'] / $order['estimated_hours'] * 100), 0) ?>%)
                                            </span>
                                        </td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
<?php if (!empty($timeStats) && ($timeStats['total_orders'] ?? 0) > 0): ?>
const timeComparisonCtx = document.getElementById('timeComparisonChart');
if (timeComparisonCtx) {
    new Chart(timeComparisonCtx, {
        type: 'bar',
        data: {
            labels: ['Timp Mediu'],
            datasets: [{
                label: 'Estimat (ore)',
                data: [<?= $timeStats['avg_estimated_hours'] ?? 0 ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }, {
                label: 'Real (ore)',
                data: [<?= $timeStats['avg_actual_hours'] ?? 0 ?>],
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                borderColor: 'rgb(255, 206, 86)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Ore'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}
<?php endif; ?>
</script>
