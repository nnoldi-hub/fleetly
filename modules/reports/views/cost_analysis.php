<?php // modules/reports/views/cost_analysis.php (template-only)
// Expect: $analysisData, $vehicles, $filters
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <h1 class="h3 m-0"><i class="fas fa-chart-line text-primary me-2"></i> Analiză costuri</h1>
    <div>
      <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>reports"><i class="fas fa-arrow-left"></i> Înapoi</a>
      <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>reports/costs?<?= http_build_query(array_merge($filters,[ 'export'=>'csv' ])) ?>"><i class="fas fa-download"></i> Export CSV</a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?= BASE_URL ?>reports/costs" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">De la</label>
                    <input type="date" id="date_from" class="form-control" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? date('Y-01-01')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Până la</label>
                    <input type="date" id="date_to" class="form-control" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? date('Y-12-31')) ?>" required>
                </div>
        <div class="col-md-3">
          <label class="form-label">Vehicul</label>
          <select name="vehicle_id" class="form-select">
            <option value="">Toate</option>
            <?php foreach (($vehicles ?? []) as $v): ?>
              <option value="<?= $v['id'] ?>" <?= (!empty($filters['vehicle_id']) && (int)$filters['vehicle_id']===(int)$v['id'])?'selected':'' ?>>
                <?= htmlspecialchars($v['license_plate'] ?? ($v['registration_number'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Grupare</label>
          <select name="analysis_type" class="form-select">
            <?php $opts=['daily'=>'Zilnic','weekly'=>'Săptămânal','monthly'=>'Lunar','yearly'=>'Anual'];
                  $sel=$filters['analysis_type']??'monthly';
                  foreach($opts as $k=>$t): ?>
            <option value="<?= $k ?>" <?= $sel===$k?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tip cost</label>
          <select name="cost_type" class="form-select">
            <?php $copts=['all'=>'Toate','fuel'=>'Combustibil','maintenance'=>'Mentenanță','insurance'=>'Asigurări'];
                  $csel=$filters['cost_type']??'all';
                  foreach($copts as $k=>$t): ?>
            <option value="<?= $k ?>" <?= $csel===$k?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12 d-flex gap-2">
          <button class="btn btn-primary"><i class="fas fa-search"></i> Analizează</button>
          <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>reports/costs">Resetează</a>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($analysisData)): ?>
            <!-- Rezumat general -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?= number_format(($analysisData['summary']['total_cost'] ?? 0), 0, ',', '.') ?> RON</div>
                                    <div>Cost Total</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?= number_format(($analysisData['summary']['fuel_cost'] ?? 0), 0, ',', '.') ?> RON</div>
                                    <div>Combustibil</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-gas-pump fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?= number_format(($analysisData['summary']['maintenance_cost'] ?? 0), 0, ',', '.') ?> RON</div>
                                    <div>Mentenanță</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-wrench fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="h4 mb-0"><?= number_format(($analysisData['summary']['insurance_cost'] ?? 0), 0, ',', '.') ?> RON</div>
                                    <div>Asigurări</div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafic distribuție costuri -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie"></i> Distribuție Costuri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container chart-300">
                                <canvas id="costDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line"></i> Evoluție Costuri Lunare
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container chart-360">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top vehicule după cost -->
            <?php if (!empty($analysisData['by_vehicle'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy"></i> Top Vehicule după Cost
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Vehicul</th>
                                    <th>Combustibil</th>
                                    <th>Mentenanță</th>
                                    <th>Asigurări</th>
                                    <th>Total</th>
                                    <th>Cost/km</th>
                                    <th>% din total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach (array_slice($analysisData['by_vehicle'], 0, 10) as $vehicle): ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank <= 3): ?>
                                                <span class="badge bg-<?php echo $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'dark'); ?>">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">#<?php echo $rank; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></small>
                                        </td>
                                        <td><?php echo number_format($vehicle['fuel_cost'], 0); ?> RON</td>
                                        <td><?php echo number_format($vehicle['maintenance_cost'], 0); ?> RON</td>
                                        <td><?php echo number_format($vehicle['insurance_cost'], 0); ?> RON</td>
                                        <td>
                                            <strong><?php echo number_format($vehicle['total_cost'], 0); ?> RON</strong>
                                        </td>
                                        <td>
                                            <?php if ($vehicle['kilometers'] > 0): ?>
                                                <span class="badge bg-<?php echo ($vehicle['cost_per_km'] > 3) ? 'danger' : (($vehicle['cost_per_km'] > 2) ? 'warning' : 'success'); ?>">
                                                    <?php echo number_format($vehicle['cost_per_km'], 2); ?> RON/km
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">—</td>
                                    </tr>
                                    <?php $rank++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Analiză pe tipuri de vehicule -->
            <?php if (!empty($analysisData['by_type'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Costuri pe Tipuri de Vehicule
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($analysisData['by_type'] as $type): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-left-primary">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            <?php echo htmlspecialchars($type['vehicle_type']); ?>
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($type['total_cost'], 0); ?> RON
                                        </div>
                                        <div class="row no-gutters align-items-center mt-2">
                                            <div class="col-auto">
                                                <div class="text-xs">Vehicule: <?php echo $type['vehicle_count']; ?></div>
                                            </div>
                                        </div>
                                        <div class="row no-gutters align-items-center">
                                            <div class="col-auto">
                                                <div class="text-xs">Medie/vehicul: <?php echo number_format($type['avg_cost_per_vehicle'], 0); ?> RON</div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Combustibil: <?php echo number_format($type['fuel_cost'], 0); ?> RON<br>
                                                Mentenanță: <?php echo number_format($type['maintenance_cost'], 0); ?> RON<br>
                                                Asigurări: <?php echo number_format($type['insurance_cost'], 0); ?> RON
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Analiză lunară -->
            <?php if (!empty($analysisData['monthly'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt"></i> Analiză Lunară
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Luna</th>
                                    <th>Combustibil</th>
                                    <th>Mentenanță</th>
                                    <th>Asigurări</th>
                                    <th>Total</th>
                                    <th>Diferența față de luna anterioară</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $prevTotal = 0; ?>
                                <?php foreach ($analysisData['monthly'] as $month): ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('F Y', mktime(0,0,0, (int)$month['month'], 1, (int)$month['year'])) ?></strong>
                                        </td>
                                        <td><?= number_format($month['fuel_cost'], 0, ',', '.'); ?> RON</td>
                                        <td><?= number_format($month['maintenance_cost'], 0, ',', '.'); ?> RON</td>
                                        <td><?= number_format($month['insurance_cost'], 0, ',', '.'); ?> RON</td>
                                        <td><strong><?= number_format($month['total_cost'], 0, ',', '.'); ?> RON</strong></td>
                                        <td>
                                            <?php if ($prevTotal > 0): ?>
                                                <?php $diff = $month['total_cost'] - $prevTotal; $diffPercent = ($diff / $prevTotal) * 100; ?>
                                                <span class="badge bg-<?= $diff >= 0 ? 'danger' : 'success' ?>">
                                                    <?= $diff >= 0 ? '+' : '' ?><?= number_format($diff, 0, ',', '.') ?> RON
                                                    (<?= $diff >= 0 ? '+' : '' ?><?= number_format($diffPercent, 1) ?>%)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $prevTotal = $month['total_cost']; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recomandări de optimizare -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb"></i> Recomandări de Optimizare
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-info alert-permanent">
                                <h6><i class="fas fa-gas-pump"></i> Combustibil</h6>
                                <ul class="mb-0">
                                    <li>Monitorizați consumul lunar</li>
                                    <li>Identificați rutele eficiente</li>
                                    <li>Mențineți vehiculele în stare bună</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning alert-permanent">
                                <h6><i class="fas fa-wrench"></i> Mentenanță</h6>
                                <ul class="mb-0">
                                    <li>Programați mentenanțe preventive</li>
                                    <li>Evitați reparațiile costisitoare</li>
                                    <li>Negociați cu furnizorii</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success alert-permanent">
                                <h6><i class="fas fa-shield-alt"></i> Asigurări</h6>
                                <ul class="mb-0">
                                    <li>Comparați ofertele anual</li>
                                    <li>Adaptați acoperirea la utilizare</li>
                                    <li>Beneficiați de reduceri pentru flote</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                        <?php else: ?>
                        <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                                <h5>Selectați perioada pentru analiză</h5>
                                <p>Folosiți filtrele de mai sus pentru a genera analiza costurilor.</p>
                        </div>
                        <?php endif; ?>
</div>

<script>
// Grafic distribuție costuri
<?php if (!empty($analysisData)): ?>
function cssVar(name, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback || '#999999';
}
function hexToRgba(hex, alpha) {
    const h = hex.replace('#','');
    const bigint = parseInt(h, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
const cPrimary = cssVar('--bs-primary', '#0d6efd');
const cSuccess = cssVar('--bs-success', '#198754');
const cWarning = cssVar('--bs-warning', '#ffc107');
const cInfo    = cssVar('--bs-info', '#0dcaf0');

const costDistributionCanvas = document.getElementById('costDistributionChart');
const costDistributionCtx = costDistributionCanvas.getContext('2d');
const pieValues = [
    <?= $analysisData['summary']['fuel_cost'] ?? 0 ?>,
    <?= $analysisData['summary']['maintenance_cost'] ?? 0 ?>,
    <?= $analysisData['summary']['insurance_cost'] ?? 0 ?>
];
let costDistributionChart = null;
if (pieValues.reduce((a,b)=>a+b,0) === 0) {
    const holder = costDistributionCanvas.parentElement;
    holder.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">Nu există date pentru perioada selectată</div>';
} else {
costDistributionChart = new Chart(costDistributionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Combustibil', 'Mentenanță', 'Asigurări'],
        datasets: [{
            data: pieValues,
            backgroundColor: [ cSuccess, cWarning, cInfo ],
            borderColor: [
                '#ffffff',
                '#ffffff',
                '#ffffff'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
}

// Grafic evoluție lunară
<?php if (!empty($analysisData['monthly'])): ?>
const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
const monthlyTrendChart = new Chart(monthlyTrendCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($analysisData['monthly'] as $month): ?>
                '<?= date('M Y', mktime(0, 0, 0, (int)$month['month'], 1, (int)$month['year'])) ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Cost Total',
            data: [
                <?php foreach ($analysisData['monthly'] as $month): ?>
                    <?= $month['total_cost'] ?>,
                <?php endforeach; ?>
            ],
            borderColor: cPrimary,
            backgroundColor: hexToRgba(cPrimary, 0.12),
            tension: 0.4
        }, {
            label: 'Combustibil',
            data: [
                <?php foreach ($analysisData['monthly'] as $month): ?>
                    <?= $month['fuel_cost'] ?>,
                <?php endforeach; ?>
            ],
            borderColor: cSuccess,
            backgroundColor: hexToRgba(cSuccess, 0.12),
            tension: 0.4
        }, {
            label: 'Mentenanță',
            data: [
                <?php foreach ($analysisData['monthly'] as $month): ?>
                    <?= $month['maintenance_cost'] ?>,
                <?php endforeach; ?>
            ],
            borderColor: cWarning,
            backgroundColor: hexToRgba(cWarning, 0.12),
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' RON';
                    }
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
<?php endif; ?>

function exportReport(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    
    // Deschidem în tab nou pentru download
    window.open(currentUrl.toString(), '_blank');
}

// Validare form
document.querySelector('form').addEventListener('submit', function(e) {
    const dateFrom = new Date(document.getElementById('date_from').value);
    const dateTo = new Date(document.getElementById('date_to').value);
    
    if (dateTo < dateFrom) {
        e.preventDefault();
        alert('Data "Până La" trebuie să fie după data "De La"!');
        return false;
    }
});

// Recolor charts on theme change
window.addEventListener('theme:change', function(){
    try {
        const p = cssVar('--bs-primary', '#0d6efd');
        const s = cssVar('--bs-success', '#198754');
        const w = cssVar('--bs-warning', '#ffc107');
        const i = cssVar('--bs-info', '#0dcaf0');
        if (monthlyTrendChart) {
            monthlyTrendChart.data.datasets[0].borderColor = p;
            monthlyTrendChart.data.datasets[0].backgroundColor = hexToRgba(p, 0.12);
            monthlyTrendChart.data.datasets[1].borderColor = s;
            monthlyTrendChart.data.datasets[1].backgroundColor = hexToRgba(s, 0.12);
            monthlyTrendChart.data.datasets[2].borderColor = w;
            monthlyTrendChart.data.datasets[2].backgroundColor = hexToRgba(w, 0.12);
            monthlyTrendChart.update();
        }
        if (costDistributionChart) {
            costDistributionChart.data.datasets[0].backgroundColor = [s, w, i];
            costDistributionChart.update();
        }
    } catch(e) {}
});
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #007bff !important;
}

.card .card-body {
    padding: 1.5rem;
}

.progress {
    background-color: #e9ecef;
}
</style>

<!-- template-only: footer provided by Controller::render() -->
