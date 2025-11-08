<?php
$pageTitle = 'Lista Intretinere';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3"><i class="fas fa-wrench"></i> Lista Intretinere</h1>
    <a href="<?= BASE_URL ?>maintenance/add" class="btn btn-primary">
      <i class="fas fa-plus"></i> Adauga Intretinere
    </a>
  </div>

  <?php if (!empty($_SESSION['flash_message'])): ?>
    <?php
      $flash = $_SESSION['flash_message'];
      $flashMsg = is_array($flash) ? ($flash['message'] ?? '') : (string)$flash;
      $flashType = is_array($flash) ? ($flash['type'] ?? 'info') : ($_SESSION['flash_type'] ?? 'info');
    ?>
    <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
      <?= htmlspecialchars($flashMsg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
  <?php endif; ?>

  <div class="card shadow">
    <div class="card-body">
      <?php if (empty($maintenanceRecords)): ?>
        <div class="text-center py-5">
          <i class="fas fa-wrench fa-3x text-muted mb-3"></i>
          <h5>Nu exista inregistrari de intretinere</h5>
          <a href="<?= BASE_URL ?>maintenance/add" class="btn btn-primary mt-3">
            <i class="fas fa-plus"></i> Adauga Prima Intretinere
          </a>
        </div>
      <?php else: ?>
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Vehicul</th>
              <th>Tip</th>
              <th>Data</th>
              <th>Status</th>
              <th>Prioritate</th>
              <th>Cost</th>
              <th>Actiuni</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($maintenanceRecords as $record): ?>
              <tr>
                <td><?= htmlspecialchars($record['registration_number'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($record['maintenance_type']) ?></td>
                <td><?= date('d.m.Y', strtotime($record['service_date'])) ?></td>
                <td>
                  <?php
                  $statusClass = [
                    'scheduled' => 'info',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger'
                  ];
                  $statusLabels = [
                    'scheduled' => 'Programat',
                    'in_progress' => 'In Progres',
                    'completed' => 'Finalizat',
                    'cancelled' => 'Anulat'
                  ];
                  ?>
                  <span class="badge bg-<?= $statusClass[$record['status']] ?? 'secondary' ?>">
                    <?= $statusLabels[$record['status']] ?? $record['status'] ?>
                  </span>
                </td>
                <td>
                  <?php
                  $priorityClass = [
                    'low' => 'secondary',
                    'medium' => 'primary',
                    'high' => 'warning',
                    'urgent' => 'danger'
                  ];
                  $priorityLabels = [
                    'low' => 'Scazuta',
                    'medium' => 'Medie',
                    'high' => 'Ridicata',
                    'urgent' => 'Urgenta'
                  ];
                  ?>
                  <span class="badge bg-<?= $priorityClass[$record['priority']] ?? 'secondary' ?>">
                    <?= $priorityLabels[$record['priority']] ?? $record['priority'] ?>
                  </span>
                </td>
                <td><?= number_format($record['cost'], 2) ?> RON</td>
                <td>
                  <a href="<?= BASE_URL ?>maintenance/edit?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form method="post" action="<?= BASE_URL ?>maintenance/delete" style="display:inline" onsubmit="return confirm('Sigur ?tergi aceasta înregistrare?');">
                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
          <nav class="mt-4">
            <ul class="pagination justify-content-center">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
