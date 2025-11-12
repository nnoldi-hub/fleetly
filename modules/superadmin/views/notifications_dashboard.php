<?php
/**
 * modules/superadmin/views/notifications_dashboard.php
 * 
 * Cross-tenant notification analytics dashboard for SuperAdmin
 * Provides KPIs, charts, company comparison, and template management
 */

// Security check
if (!Auth::getInstance()->isSuperAdmin()) {
    http_response_code(403);
    die('Acces interzis - SuperAdmin only');
}

$db = Database::getInstance();

// Date range filter (default: last 30 days)
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$selectedCompany = $_GET['company_id'] ?? 'all';

// === KPI METRICS ===
$kpiQuery = "
    SELECT 
        COUNT(*) as total_notifications,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        ROUND(SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as delivery_rate
    FROM notifications
    WHERE created_at BETWEEN ? AND ?
";
$kpiParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

if ($selectedCompany !== 'all') {
    $kpiQuery .= " AND company_id = ?";
    $kpiParams[] = (int)$selectedCompany;
}

$kpi = $db->fetch($kpiQuery, $kpiParams) ?: [
    'total_notifications' => 0,
    'sent_count' => 0,
    'failed_count' => 0,
    'pending_count' => 0,
    'delivery_rate' => 0
];

// Queue backlog
$queueBacklog = $db->fetch("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM notification_queue
") ?: ['total' => 0, 'pending' => 0, 'processing' => 0, 'failed' => 0];

// === COMPANY COMPARISON (Top 10 by volume) ===
$companyStatsQuery = "
    SELECT 
        c.id,
        c.name,
        COUNT(n.id) as total_notifications,
        SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN n.status = 'failed' THEN 1 ELSE 0 END) as failed,
        ROUND(SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(n.id), 1) as delivery_rate
    FROM companies c
    LEFT JOIN notifications n ON n.company_id = c.id 
        AND n.created_at BETWEEN ? AND ?
    WHERE c.status = 'active'
    GROUP BY c.id, c.name
    ORDER BY total_notifications DESC
    LIMIT 10
";
$companyStats = $db->fetchAll($companyStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

// === CHANNEL DISTRIBUTION ===
$channelStatsQuery = "
    SELECT 
        channel,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM notification_queue
    WHERE created_at BETWEEN ? AND ?
    GROUP BY channel
    ORDER BY total DESC
";
$channelStats = $db->fetchAll($channelStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

// === NOTIFICATION TYPES ===
$typeStatsQuery = "
    SELECT 
        type,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
    FROM notifications
    WHERE created_at BETWEEN ? AND ?
    GROUP BY type
    ORDER BY total DESC
";
$typeStats = $db->fetchAll($typeStatsQuery, [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

// === TIMELINE (Last 7 days) ===
$timelineQuery = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM notifications
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$timeline = $db->fetchAll($timelineQuery);

// === TEMPLATES (Global) ===
$templates = $db->fetchAll("
    SELECT 
        id,
        slug,
        name,
        enabled,
        company_id,
        (SELECT COUNT(*) FROM notifications WHERE template_id = notification_templates.id) as usage_count
    FROM notification_templates
    WHERE company_id IS NULL
    ORDER BY usage_count DESC
");

// Companies for filter dropdown
$companies = $db->fetchAll("SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC");
?>

<div class="container-fluid py-4">
    <?php 
    $breadcrumb = [
        ['title' => 'SuperAdmin', 'url' => ROUTE_BASE . 'superadmin'],
        ['title' => 'Notifications Dashboard', 'url' => '']
    ];
    include ROOT_PATH . '/includes/breadcrumb.php'; 
    ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-line text-primary me-2"></i>
            Notifications Analytics Dashboard
        </h1>
        <div>
            <a href="<?= ROUTE_BASE ?>superadmin/notifications/export" class="btn btn-outline-success">
                <i class="fas fa-file-excel me-2"></i>Export Report
            </a>
            <a href="<?= ROUTE_BASE ?>superadmin" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to SuperAdmin
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="col-md-4">
                    <label for="company_id" class="form-label">Company Filter</label>
                    <select class="form-select" id="company_id" name="company_id">
                        <option value="all" <?= $selectedCompany === 'all' ? 'selected' : '' ?>>All Companies</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>" <?= $selectedCompany == $company['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Notifications</h6>
                            <h2 class="mb-0"><?= number_format($kpi['total_notifications']) ?></h2>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Delivered</h6>
                            <h2 class="mb-0"><?= number_format($kpi['sent_count']) ?></h2>
                            <small><?= $kpi['delivery_rate'] ?>% success rate</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Failed</h6>
                            <h2 class="mb-0"><?= number_format($kpi['failed_count']) ?></h2>
                            <small>Requires attention</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Queue Backlog</h6>
                            <h2 class="mb-0"><?= number_format($queueBacklog['pending']) ?></h2>
                            <small><?= number_format($queueBacklog['processing']) ?> processing</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Timeline Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-area text-primary me-2"></i>
                        Timeline (Last 7 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="timelineChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Channel Distribution -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Channel Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="channelChart"></canvas>
                    <div class="mt-3">
                        <?php foreach ($channelStats as $channel): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <i class="fas fa-circle text-<?= $channel['channel'] === 'email' ? 'primary' : ($channel['channel'] === 'sms' ? 'success' : 'warning') ?> me-2"></i>
                                <?= ucfirst($channel['channel']) ?>
                            </span>
                            <span class="badge bg-secondary"><?= number_format($channel['total']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Comparison Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building text-info me-2"></i>
                        Company Comparison (Top 10)
                    </h5>
                    <a href="#" class="btn btn-sm btn-outline-primary" onclick="exportCompanyComparison(); return false;">
                        <i class="fas fa-download me-1"></i>Export
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Company</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Sent</th>
                                    <th class="text-end">Failed</th>
                                    <th class="text-end">Delivery Rate</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($companyStats)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox me-2"></i>No data available
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($companyStats as $idx => $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary me-2"><?= $idx + 1 ?></span>
                                        <strong><?= htmlspecialchars($stat['name']) ?></strong>
                                    </td>
                                    <td class="text-end"><?= number_format($stat['total_notifications']) ?></td>
                                    <td class="text-end">
                                        <span class="text-success">
                                            <i class="fas fa-check me-1"></i><?= number_format($stat['sent']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-danger">
                                            <i class="fas fa-times me-1"></i><?= number_format($stat['failed']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="progress" style="height: 20px; min-width: 80px;">
                                            <div class="progress-bar <?= $stat['delivery_rate'] >= 95 ? 'bg-success' : ($stat['delivery_rate'] >= 80 ? 'bg-warning' : 'bg-danger') ?>" 
                                                 style="width: <?= $stat['delivery_rate'] ?>%">
                                                <?= $stat['delivery_rate'] ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="?company_id=<?= $stat['id'] ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= ROUTE_BASE ?>superadmin/act-as?company=<?= $stat['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Act As Company">
                                            <i class="fas fa-user-secret"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Types & Templates Row -->
    <div class="row mb-4">
        <!-- Notification Types -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tags text-warning me-2"></i>
                        Notification Types
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($typeStats)): ?>
                    <p class="text-muted text-center py-4">No data available</p>
                    <?php else: ?>
                    <?php foreach ($typeStats as $type): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="flex-grow-1">
                            <strong><?= ucwords(str_replace('_', ' ', $type['type'])) ?></strong>
                            <div class="progress mt-1" style="height: 8px;">
                                <div class="progress-bar bg-info" 
                                     style="width: <?= ($type['total'] / max(array_column($typeStats, 'total'))) * 100 ?>%">
                                </div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <strong><?= number_format($type['total']) ?></strong>
                            <small class="text-muted d-block"><?= number_format($type['sent']) ?> sent</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Global Templates -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        Global Templates
                    </h5>
                    <a href="<?= ROUTE_BASE ?>superadmin/notifications/templates" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Template</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        No global templates
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($template['name']) ?></strong>
                                        <small class="text-muted d-block"><?= htmlspecialchars($template['slug']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($template['enabled']): ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-info"><?= number_format($template['usage_count']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Failed Notifications -->
    <?php
    $recentFailed = $db->fetchAll("
        SELECT 
            nq.id,
            nq.channel,
            nq.error_message,
            nq.attempts,
            nq.created_at,
            n.type,
            c.name as company_name,
            u.username
        FROM notification_queue nq
        LEFT JOIN notifications n ON n.id = nq.notification_id
        LEFT JOIN companies c ON c.id = nq.company_id
        LEFT JOIN users u ON u.id = nq.user_id
        WHERE nq.status = 'failed'
        ORDER BY nq.created_at DESC
        LIMIT 10
    ");
    ?>
    <?php if (!empty($recentFailed)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Recent Failed Notifications (Attention Required)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Company</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Channel</th>
                                    <th>Attempts</th>
                                    <th>Error</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFailed as $failed): ?>
                                <tr>
                                    <td><code><?= $failed['id'] ?></code></td>
                                    <td><?= htmlspecialchars($failed['company_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($failed['username'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-secondary"><?= $failed['type'] ?></span></td>
                                    <td><span class="badge bg-<?= $failed['channel'] === 'email' ? 'primary' : 'success' ?>"><?= $failed['channel'] ?></span></td>
                                    <td class="text-center"><?= $failed['attempts'] ?>/3</td>
                                    <td><small class="text-danger"><?= htmlspecialchars(mb_substr($failed['error_message'], 0, 50)) ?>...</small></td>
                                    <td><small><?= date('Y-m-d H:i', strtotime($failed['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Timeline Chart
const timelineCtx = document.getElementById('timelineChart').getContext('2d');
const timelineData = {
    labels: [<?php foreach ($timeline as $t) echo "'" . date('M d', strtotime($t['date'])) . "',"; ?>],
    datasets: [
        {
            label: 'Sent',
            data: [<?php foreach ($timeline as $t) echo $t['sent'] . ','; ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4
        },
        {
            label: 'Failed',
            data: [<?php foreach ($timeline as $t) echo $t['failed'] . ','; ?>],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.4
        }
    ]
};

new Chart(timelineCtx, {
    type: 'line',
    data: timelineData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Channel Distribution Chart
const channelCtx = document.getElementById('channelChart').getContext('2d');
const channelData = {
    labels: [<?php foreach ($channelStats as $c) echo "'" . ucfirst($c['channel']) . "',"; ?>],
    datasets: [{
        data: [<?php foreach ($channelStats as $c) echo $c['total'] . ','; ?>],
        backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545']
    }]
};

new Chart(channelCtx, {
    type: 'doughnut',
    data: channelData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Export company comparison
function exportCompanyComparison() {
    const dateFrom = '<?= $dateFrom ?>';
    const dateTo = '<?= $dateTo ?>';
    window.location.href = '<?= ROUTE_BASE ?>superadmin/notifications/export?type=company&date_from=' + dateFrom + '&date_to=' + dateTo;
}
</script>
