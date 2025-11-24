<?php
/**
 * View: Raport Performanță Mecanici
 */

$mechanicStats = $mechanicStats ?? [];
$commonIssues = $commonIssues ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-user-check"></i> Raport Performanță Mecanici</h2>
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
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/mechanic-performance" class="row g-3">
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

    <!-- Statistici Mecanici -->
    <?php if (empty($mechanicStats)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există date pentru perioada selectată.
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Performanță Detaliat pe Mecanic</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mecanic</th>
                                <th>Specializare</th>
                                <th class="text-center">Ordine<br>Totale</th>
                                <th class="text-center">Ordine<br>Finalizate</th>
                                <th class="text-center">Rate<br>Finalizare</th>
                                <th class="text-center">Ore<br>Lucrate</th>
                                <th class="text-end">Venit<br>Generat</th>
                                <th class="text-center">Eficiență<br>(Est/Real)</th>
                                <th class="text-center">Timp Mediu<br>Finalizare</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mechanicStats as $stat): ?>
                                <?php
                                $completionRate = $stat['total_orders'] > 0 
                                    ? ($stat['completed_orders'] / $stat['total_orders'] * 100) 
                                    : 0;
                                $efficiencyRatio = $stat['efficiency_ratio'] ?? 1;
                                $avgCompletionTime = $stat['avg_completion_time_hours'] ?? 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($stat['name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= number_format($stat['hourly_rate'], 0) ?> RON/h</small>
                                    </td>
                                    <td><?= htmlspecialchars($stat['specialization'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $stat['total_orders'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= $stat['completed_orders'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $badgeColor = $completionRate >= 90 ? 'success' : ($completionRate >= 70 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $badgeColor ?>">
                                            <?= number_format($completionRate, 1) ?>%
                                        </span>
                                    </td>
                                    <td class="text-center"><?= number_format($stat['total_hours_worked'] ?? 0, 1) ?>h</td>
                                    <td class="text-end">
                                        <strong><?= number_format($stat['revenue_generated'] ?? 0, 0) ?> RON</strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($efficiencyRatio > 0): ?>
                                            <?php
                                            $effPercent = $efficiencyRatio * 100;
                                            $effBadge = $effPercent <= 100 ? 'success' : ($effPercent <= 120 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?= $effBadge ?>">
                                                <?= number_format($effPercent, 0) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($avgCompletionTime > 0): ?>
                                            <?= number_format($avgCompletionTime, 1) ?>h
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Grafice Comparative -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Ordine Finalizate per Mecanic</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ordersChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Venit Generat per Mecanic</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Top Probleme Întâlnite -->
    <?php if (!empty($commonIssues)): ?>
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Top 10 Probleme Frecvente</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Descriere Problemă</th>
                                <th class="text-center">Frecvență</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commonIssues as $issue): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($issue['work_description'], 0, 100)) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-warning"><?= $issue['frequency'] ?>x</span>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
<?php if (!empty($mechanicStats)): ?>
// Orders Chart
const ordersCtx = document.getElementById('ordersChart');
if (ordersCtx) {
    new Chart(ordersCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($mechanicStats, 'name')) ?>,
            datasets: [{
                label: 'Ordine Finalizate',
                data: <?= json_encode(array_column($mechanicStats, 'completed_orders')) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($mechanicStats, 'name')) ?>,
            datasets: [{
                label: 'Venit Generat (RON)',
                data: <?= json_encode(array_column($mechanicStats, 'revenue_generated')) ?>,
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
                    beginAtZero: true
                }
            }
        }
    });
}
<?php endif; ?>
</script>
