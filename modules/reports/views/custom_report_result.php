<?php
// modules/reports/views/custom_report_result.php
// Expect: $reportData, $reportConfig
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/../../../config/config.php';
$pageTitle = 'Rezultat Raport Personalizat';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <h1 class="h3 m-0"><i class="fas fa-chart-pie text-primary me-2"></i> Raport personalizat</h1>
    <div>
      <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>reports/custom"><i class="fas fa-arrow-left"></i> Înapoi</a>
      <form method="post" action="" class="d-inline">
        <input type="hidden" name="export" value="csv" />
        <button class="btn btn-outline-success btn-sm"><i class="fas fa-download"></i> Export CSV</button>
      </form>
    </div>
  </div>

  <?php if (!empty($reportData)): ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?= htmlspecialchars($reportConfig['title'] ?? 'Raport') ?></strong>
        <small class="text-muted ms-2">
          (<?= htmlspecialchars($reportConfig['date_from'] ?? '') ?> - <?= htmlspecialchars($reportConfig['date_to'] ?? '') ?>)
        </small>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <?php foreach (($reportData['headers'] ?? []) as $h): ?>
                  <th><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($reportData['rows'] ?? []) as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars((string)$cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($reportData['rows'])): ?>
                <tr><td colspan="<?= max(1, count($reportData['headers'] ?? [])) ?>" class="text-center text-muted py-4">Nu există date pentru criteriile selectate</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info">Nu există date de afișat.</div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
