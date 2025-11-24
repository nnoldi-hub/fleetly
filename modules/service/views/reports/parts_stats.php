<?php
/**
 * View: Statistici Piese
 */

$topParts = $topParts ?? [];
$partsOverview = $partsOverview ?? [];
$topSuppliers = $topSuppliers ?? [];
$monthlyTrend = $monthlyTrend ?? [];
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-pie"></i> Statistici Piese & Inventory</h2>
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
            <form method="GET" action="<?= ROUTE_BASE ?>service/reports/parts-stats" class="row g-3">
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

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $partsOverview['total_parts_used'] ?? 0 ?></h3>
                    <p class="mb-0">Piese Utilizate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($partsOverview['total_quantity'] ?? 0, 0) ?></h3>
                    <p class="mb-0">Cantitate Totală</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($partsOverview['total_cost'] ?? 0, 0) ?> RON</h3>
                    <p class="mb-0">Cost Total Piese</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= number_format($partsOverview['avg_part_cost'] ?? 0, 2) ?> RON</h3>
                    <p class="mb-0">Cost Mediu/Piesă</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($topParts)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nu există date despre piese pentru perioada selectată.
        </div>
    <?php else: ?>
        <!-- Charts -->
        <div class="row mb-4">
            <!-- Top 10 Piese Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Top 10 Piese Utilizate</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topPartsChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Evoluție Lunară</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($monthlyTrend)): ?>
                            <canvas id="monthlyTrendChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted text-center">Nu există date</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Piese Table -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-cogs"></i> Top 50 Piese Utilizate</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Denumire Piesă</th>
                                <th>Cod Piesă</th>
                                <th class="text-center">Frecvență<br>Utilizare</th>
                                <th class="text-center">Cantitate<br>Totală</th>
                                <th class="text-end">Preț Mediu<br>Unitar</th>
                                <th class="text-end">Cost Total</th>
                                <th>Furnizori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topParts as $index => $part): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($part['part_name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($part['part_number'] ?: '-') ?></code></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $part['usage_count'] ?>x</span>
                                    </td>
                                    <td class="text-center"><?= $part['total_quantity'] ?></td>
                                    <td class="text-end"><?= number_format($part['avg_price'], 2) ?> RON</td>
                                    <td class="text-end">
                                        <strong><?= number_format($part['total_cost'], 2) ?> RON</strong>
                                    </td>
                                    <td><small><?= htmlspecialchars(substr($part['suppliers'] ?: '-', 0, 40)) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Furnizori -->
        <?php if (!empty($topSuppliers)): ?>
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-truck"></i> Top 10 Furnizori</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Furnizor</th>
                                    <th class="text-center">Număr Comenzi</th>
                                    <th class="text-end">Total Achizi ționat</th>
                                    <th class="text-end">Mediu/Comandă</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topSuppliers as $index => $supplier): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($supplier['supplier']) ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= $supplier['order_count'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= number_format($supplier['total_spent'], 2) ?> RON</strong>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($supplier['total_spent'] / $supplier['order_count'], 2) ?> RON
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
<?php if (!empty($topParts)): ?>
// Top Parts Chart
const topPartsCtx = document.getElementById('topPartsChart');
if (topPartsCtx) {
    const topPartsData = <?= json_encode(array_slice($topParts, 0, 10)) ?>;
    new Chart(topPartsCtx, {
        type: 'bar',
        data: {
            labels: topPartsData.map(p => p.part_name.substring(0, 30)),
            datasets: [{
                label: 'Frecvență Utilizare',
                data: topPartsData.map(p => p.usage_count),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgb(54, 162, 235)',
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
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
<?php endif; ?>

<?php if (!empty($monthlyTrend)): ?>
// Monthly Trend Chart
const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
if (monthlyTrendCtx) {
    new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>,
            datasets: [{
                label: 'Cost (RON)',
                data: <?= json_encode(array_column($monthlyTrend, 'total_cost')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
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
