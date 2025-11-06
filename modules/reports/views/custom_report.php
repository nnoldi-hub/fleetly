<?php
// Verificăm dacă utilizatorul este autentificat
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$pageTitle = 'Raport Personalizat';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php 
            $breadcrumbs = [
                'Acasă' => '/',
                'Rapoarte' => '/modules/reports/',
                'Raport Personalizat' => ''
            ];
            include __DIR__ . '/../../../includes/breadcrumb.php'; 
            ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-pie"></i> Raport Personalizat
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (!empty($reportData)): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportReport('csv')">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="btn-group">
                        <a href="/modules/reports/" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Înapoi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Constructor raport -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs"></i> Constructor Raport
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="reportForm">
                        <input type="hidden" name="action" value="custom_report">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="report_type" class="form-label">Tip Raport <span class="text-danger">*</span></label>
                                <select class="form-select" id="report_type" name="report_type" required onchange="updateFields()">
                                    <option value="">Selectați tipul de raport</option>
                                    <option value="vehicle_usage" <?php echo ($filters['report_type'] == 'vehicle_usage') ? 'selected' : ''; ?>>Utilizarea Vehiculelor</option>
                                    <option value="cost_comparison" <?php echo ($filters['report_type'] == 'cost_comparison') ? 'selected' : ''; ?>>Comparație Costuri</option>
                                    <option value="fuel_efficiency" <?php echo ($filters['report_type'] == 'fuel_efficiency') ? 'selected' : ''; ?>>Eficiența Combustibilului</option>
                                    <option value="maintenance_schedule" <?php echo ($filters['report_type'] == 'maintenance_schedule') ? 'selected' : ''; ?>>Program Mentenanță</option>
                                    <option value="driver_performance" <?php echo ($filters['report_type'] == 'driver_performance') ? 'selected' : ''; ?>>Performanța Șoferilor</option>
                                    <option value="financial_summary" <?php echo ($filters['report_type'] == 'financial_summary') ? 'selected' : ''; ?>>Rezumat Financiar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date_range" class="form-label">Perioada <span class="text-danger">*</span></label>
                                <select class="form-select" id="date_range" name="date_range" required onchange="updateDates()">
                                    <option value="">Selectați perioada</option>
                                    <option value="last_month" <?php echo ($filters['date_range'] == 'last_month') ? 'selected' : ''; ?>>Luna trecută</option>
                                    <option value="last_3_months" <?php echo ($filters['date_range'] == 'last_3_months') ? 'selected' : ''; ?>>Ultimele 3 luni</option>
                                    <option value="last_6_months" <?php echo ($filters['date_range'] == 'last_6_months') ? 'selected' : ''; ?>>Ultimele 6 luni</option>
                                    <option value="last_year" <?php echo ($filters['date_range'] == 'last_year') ? 'selected' : ''; ?>>Ultimul an</option>
                                    <option value="current_year" <?php echo ($filters['date_range'] == 'current_year') ? 'selected' : ''; ?>>Anul curent</option>
                                    <option value="custom" <?php echo ($filters['date_range'] == 'custom') ? 'selected' : ''; ?>>Personalizat</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3" id="custom_dates" style="display: <?php echo ($filters['date_range'] == 'custom') ? 'flex' : 'none'; ?>;">
                            <div class="col-md-6">
                                <label for="date_from" class="form-label">Data De La</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo $filters['date_from'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="date_to" class="form-label">Data Până La</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo $filters['date_to'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="vehicles" class="form-label">Vehicule</label>
                                <select class="form-select" id="vehicles" name="vehicles[]" multiple>
                                    <?php if (!empty($vehicles)): ?>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?php echo $vehicle['id']; ?>"
                                                    <?php echo (in_array($vehicle['id'], $filters['vehicles'] ?? [])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Ctrl+Click pentru selecție multiplă</small>
                            </div>
                            <div class="col-md-4">
                                <label for="vehicle_types" class="form-label">Tipuri Vehicule</label>
                                <select class="form-select" id="vehicle_types" name="vehicle_types[]" multiple>
                                    <?php if (!empty($vehicle_types)): ?>
                                        <?php foreach ($vehicle_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"
                                                    <?php echo (in_array($type['id'], $filters['vehicle_types'] ?? [])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="drivers" class="form-label">Șoferi</label>
                                <select class="form-select" id="drivers" name="drivers[]" multiple>
                                    <?php if (!empty($drivers)): ?>
                                        <?php foreach ($drivers as $driver): ?>
                                            <option value="<?php echo $driver['id']; ?>"
                                                    <?php echo (in_array($driver['id'], $filters['drivers'] ?? [])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($driver['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="group_by" class="form-label">Grupează după</label>
                                <select class="form-select" id="group_by" name="group_by">
                                    <option value="">Fără grupare</option>
                                    <option value="vehicle" <?php echo ($filters['group_by'] == 'vehicle') ? 'selected' : ''; ?>>Vehicul</option>
                                    <option value="vehicle_type" <?php echo ($filters['group_by'] == 'vehicle_type') ? 'selected' : ''; ?>>Tip vehicul</option>
                                    <option value="driver" <?php echo ($filters['group_by'] == 'driver') ? 'selected' : ''; ?>>Șofer</option>
                                    <option value="month" <?php echo ($filters['group_by'] == 'month') ? 'selected' : ''; ?>>Lună</option>
                                    <option value="quarter" <?php echo ($filters['group_by'] == 'quarter') ? 'selected' : ''; ?>>Trimestru</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="chart_type" class="form-label">Tip grafic</label>
                                <select class="form-select" id="chart_type" name="chart_type">
                                    <option value="bar" <?php echo ($filters['chart_type'] == 'bar') ? 'selected' : ''; ?>>Bare</option>
                                    <option value="line" <?php echo ($filters['chart_type'] == 'line') ? 'selected' : ''; ?>>Linie</option>
                                    <option value="pie" <?php echo ($filters['chart_type'] == 'pie') ? 'selected' : ''; ?>>Plăcintă</option>
                                    <option value="doughnut" <?php echo ($filters['chart_type'] == 'doughnut') ? 'selected' : ''; ?>>Gogoașă</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar"></i> Generează Raport
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Resetează
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($reportData)): ?>
            <!-- Rezultatele raportului -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Rezultate: <?php echo htmlspecialchars($reportData['title']); ?>
                        <small class="text-muted ms-2">
                            (<?php echo date('d.m.Y', strtotime($reportData['period']['start'])); ?> - 
                             <?php echo date('d.m.Y', strtotime($reportData['period']['end'])); ?>)
                        </small>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Metrici cheie -->
                    <?php if (!empty($reportData['metrics'])): ?>
                    <div class="row mb-4">
                        <?php foreach ($reportData['metrics'] as $metric): ?>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card bg-<?php echo $metric['color'] ?? 'primary'; ?> text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="h4 mb-0"><?php echo htmlspecialchars($metric['value']); ?></div>
                                                <div><?php echo htmlspecialchars($metric['label']); ?></div>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-<?php echo $metric['icon'] ?? 'chart-bar'; ?> fa-2x"></i>
                                            </div>
                                        </div>
                                        <?php if (!empty($metric['trend'])): ?>
                                            <div class="mt-2">
                                                <small>
                                                    <i class="fas fa-<?php echo $metric['trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                                    <?php echo abs($metric['trend']); ?>% față de perioada anterioară
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Grafic principal -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <canvas id="mainChart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Tabel detaliat -->
                    <?php if (!empty($reportData['table_data'])): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <?php foreach ($reportData['table_headers'] as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['table_data'] as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?php echo htmlspecialchars($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Observații și recomandări -->
                    <?php if (!empty($reportData['insights'])): ?>
                    <div class="mt-4">
                        <h6><i class="fas fa-lightbulb"></i> Observații și Recomandări</h6>
                        <div class="row">
                            <?php foreach ($reportData['insights'] as $insight): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="alert alert-<?php echo $insight['type'] ?? 'info'; ?> alert-sm">
                                        <strong><?php echo htmlspecialchars($insight['title']); ?>:</strong>
                                        <?php echo htmlspecialchars($insight['message']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rapoarte salvate -->
            <?php if (!empty($savedReports)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-save"></i> Rapoarte Salvate
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Nume Raport</th>
                                    <th>Tip</th>
                                    <th>Creat la</th>
                                    <th>Perioada</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($savedReports as $saved): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($saved['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($saved['description'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($saved['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($saved['created_at'])); ?></td>
                                        <td>
                                            <?php echo date('d.m.Y', strtotime($saved['date_from'])); ?> - 
                                            <?php echo date('d.m.Y', strtotime($saved['date_to'])); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="loadSavedReport(<?php echo $saved['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Vizualizează
                                                </button>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="runSavedReport(<?php echo $saved['id']; ?>)">
                                                    <i class="fas fa-play"></i> Rulează
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteSavedReport(<?php echo $saved['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Șterge
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                <h5>Creați un raport personalizat</h5>
                <p>Folosiți constructorul de mai sus pentru a genera raportul dorit cu metrici și vizualizări personalizate.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal pentru salvarea raportului -->
<div class="modal fade" id="saveReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Salvează Raportul</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saveReportForm">
                    <div class="mb-3">
                        <label for="report_name" class="form-label">Nume raport <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="report_name" name="report_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="report_description" class="form-label">Descriere</label>
                        <textarea class="form-control" id="report_description" name="report_description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-primary" onclick="saveReport()">Salvează</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function updateFields() {
    const reportType = document.getElementById('report_type').value;
    // Actualizam campurile în funcție de tipul de raport
    // Aici putem adăuga logică specifică pentru fiecare tip
}

function updateDates() {
    const dateRange = document.getElementById('date_range').value;
    const customDates = document.getElementById('custom_dates');
    
    if (dateRange === 'custom') {
        customDates.style.display = 'flex';
    } else {
        customDates.style.display = 'none';
        
        // Setăm datele automat în funcție de selecție
        const today = new Date();
        let startDate, endDate = today;
        
        switch(dateRange) {
            case 'last_month':
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'last_3_months':
                startDate = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                break;
            case 'last_6_months':
                startDate = new Date(today.getFullYear(), today.getMonth() - 6, 1);
                break;
            case 'last_year':
                startDate = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                break;
            case 'current_year':
                startDate = new Date(today.getFullYear(), 0, 1);
                break;
        }
        
        if (startDate) {
            document.getElementById('date_from').value = startDate.toISOString().split('T')[0];
            document.getElementById('date_to').value = endDate.toISOString().split('T')[0];
        }
    }
}

function resetForm() {
    document.getElementById('reportForm').reset();
    document.getElementById('custom_dates').style.display = 'none';
}

<?php if (!empty($reportData) && !empty($reportData['chart_data'])): ?>
// Generarea graficului principal
const ctx = document.getElementById('mainChart').getContext('2d');
const chart = new Chart(ctx, {
    type: '<?php echo $filters['chart_type'] ?? 'bar'; ?>',
    data: {
        labels: <?php echo json_encode($reportData['chart_data']['labels']); ?>,
        datasets: <?php echo json_encode($reportData['chart_data']['datasets']); ?>
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: '<?php echo addslashes($reportData['title']); ?>'
            },
            legend: {
                position: '<?php echo in_array($filters['chart_type'], ['pie', 'doughnut']) ? 'bottom' : 'top'; ?>'
            }
        },
        scales: <?php echo in_array($filters['chart_type'], ['pie', 'doughnut']) ? '{}' : '{
            y: {
                beginAtZero: true
            }
        }'; ?>
    }
});
<?php endif; ?>

function exportReport(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    
    window.open(currentUrl.toString(), '_blank');
}

function saveReport() {
    const name = document.getElementById('report_name').value;
    const description = document.getElementById('report_description').value;
    
    if (!name.trim()) {
        alert('Vă rugăm să introduceți un nume pentru raport!');
        return;
    }
    
    // Salvăm configurația curentă
    const formData = new FormData(document.getElementById('reportForm'));
    formData.append('save_report', '1');
    formData.append('report_name', name);
    formData.append('report_description', description);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('saveReportModal')).hide();
            alert('Raportul a fost salvat cu succes!');
            location.reload();
        } else {
            alert('Eroare la salvarea raportului: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A apărut o eroare la salvarea raportului.');
    });
}

function loadSavedReport(reportId) {
    window.location.href = '?action=custom_report&load_saved=' + reportId;
}

function runSavedReport(reportId) {
    window.location.href = '?action=custom_report&run_saved=' + reportId;
}

function deleteSavedReport(reportId) {
    if (confirm('Sunteți sigur că doriți să ștergeți acest raport salvat?')) {
        fetch('?action=delete_saved_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({id: reportId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la ștergerea raportului.');
        });
    }
}

// Validare form
document.getElementById('reportForm').addEventListener('submit', function(e) {
    const reportType = document.getElementById('report_type').value;
    const dateRange = document.getElementById('date_range').value;
    
    if (!reportType) {
        e.preventDefault();
        alert('Vă rugăm să selectați tipul de raport!');
        return false;
    }
    
    if (!dateRange) {
        e.preventDefault();
        alert('Vă rugăm să selectați perioada!');
        return false;
    }
    
    if (dateRange === 'custom') {
        const dateFrom = new Date(document.getElementById('date_from').value);
        const dateTo = new Date(document.getElementById('date_to').value);
        
        if (!document.getElementById('date_from').value || !document.getElementById('date_to').value) {
            e.preventDefault();
            alert('Vă rugăm să completați datele pentru perioada personalizată!');
            return false;
        }
        
        if (dateTo < dateFrom) {
            e.preventDefault();
            alert('Data "Până La" trebuie să fie după data "De La"!');
            return false;
        }
    }
});

// Adăugare buton pentru salvarea raportului
<?php if (!empty($reportData)): ?>
document.querySelector('.btn-toolbar').insertAdjacentHTML('afterbegin', 
    '<div class="btn-group me-2">' +
    '<button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#saveReportModal">' +
    '<i class="fas fa-save"></i> Salvează Raportul' +
    '</button>' +
    '</div>'
);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
