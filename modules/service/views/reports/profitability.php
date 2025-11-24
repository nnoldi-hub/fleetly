<?php
/**
 * View: Raport Rentabilitate Service
 */

$revenue = $revenue ?? [];
$laborCosts = $laborCosts ?? [];
$topMechanics = $topMechanics ?? [];
$monthlyTrend = $monthlyTrend ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-money-bill-wave"></i> Raport Rentabilitate Service</h2>
            <p class="text-muted">
                Perioadă: <?= date('d.m.Y', strtotime($dateFrom)) ?> - <?= date('d.m.Y', strtotime($dateTo)) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>service/reports" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <a href="<?= ROUTE_BASE ?>service/reports/export?type=profitability&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
               class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export
            </a>
        </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/profitability" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">De la</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Până la</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Aplică
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $revenue['total_orders'] ?? 0 ?></h3>
                    <p class="mb-0">Ordine Finalizate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($revenue['parts_revenue'] ?? 0, 0) ?> RON</h3>
                    <p class="mb-0">Venit Piese</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($revenue['labor_revenue'] ?? 0, 0) ?> RON</h3>
                    <p class="mb-0">Venit Manoperă</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($revenue['total_revenue'] ?? 0, 0) ?> RON</h3>
                    <p class="mb-0">Venit Total</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-muted">Valoare Medie Ordine</h5>
                    <h2 class="text-primary"><?= number_format($revenue['avg_order_value'] ?? 0, 2) ?> RON</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-muted">Ore Totale Lucrate</h5>
                    <h2 class="text-info"><?= number_format($laborCosts['total_hours'] ?? 0, 1) ?>h</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-muted">Mecanici Activi</h5>
                    <h2 class="text-warning"><?= $laborCosts['active_mechanics'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Evoluție Lunară -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Evoluție Lunară</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($monthlyTrend)): ?>
                        <canvas id="monthlyTrendChart" height="80"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center">Nu există date pentru grafic</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Breakdown Venituri -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Structura Veniturilor</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $totalRev = ($revenue['total_revenue'] ?? 0);
                    $partsRev = ($revenue['parts_revenue'] ?? 0);
                    $laborRev = ($revenue['labor_revenue'] ?? 0);
                    
                    $partsPercent = $totalRev > 0 ? ($partsRev / $totalRev * 100) : 0;
                    $laborPercent = $totalRev > 0 ? ($laborRev / $totalRev * 100) : 0;
                    ?>
                    <canvas id="revenueBreakdownChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Mecanici -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Mecanici după Venit Generat</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($topMechanics)): ?>
                <div class="p-4 text-center text-muted">
                    Nu există date pentru această perioadă
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Mecanic</th>
                                <th class="text-center">Ordine Finalizate</th>
                                <th class="text-center">Ore Lucrate</th>
                                <th class="text-end">Venit Generat</th>
                                <th class="text-end">Venit/Oră</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topMechanics as $index => $mechanic): ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <i class="fas fa-trophy text-warning"></i>
                                        <?php elseif ($index === 1): ?>
                                            <i class="fas fa-medal" style="color: silver;"></i>
                                        <?php elseif ($index === 2): ?>
                                            <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                        <?php else: ?>
                                            <?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($mechanic['mechanic_name']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $mechanic['orders_completed'] ?></span>
                                    </td>
                                    <td class="text-center"><?= number_format($mechanic['hours_worked'] ?? 0, 1) ?>h</td>
                                    <td class="text-end">
                                        <strong><?= number_format($mechanic['revenue_generated'], 2) ?> RON</strong>
                                    </td>
                                    <td class="text-end">
                                        <?php 
                                        $revenuePerHour = ($mechanic['hours_worked'] ?? 0) > 0 
                                            ? $mechanic['revenue_generated'] / $mechanic['hours_worked'] 
                                            : 0;
                                        ?>
                                        <?= number_format($revenuePerHour, 2) ?> RON
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Monthly Trend Chart
<?php if (!empty($monthlyTrend)): ?>
const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
if (monthlyTrendCtx) {
    new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>,
            datasets: [{
                label: 'Venit (RON)',
                data: <?= json_encode(array_column($monthlyTrend, 'revenue')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Număr Ordine',
                data: <?= json_encode(array_column($monthlyTrend, 'orders')) ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Venit (RON)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Număr Ordine'
                    },
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });
}
<?php endif; ?>

// Revenue Breakdown Pie Chart
const revenueBreakdownCtx = document.getElementById('revenueBreakdownChart');
if (revenueBreakdownCtx) {
    new Chart(revenueBreakdownCtx, {
        type: 'doughnut',
        data: {
            labels: ['Piese', 'Manoperă'],
            datasets: [{
                data: [<?= $partsRev ?>, <?= $laborRev ?>],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value.toFixed(2) + ' RON (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}
</script>
