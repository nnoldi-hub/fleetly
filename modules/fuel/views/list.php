<?php
// modules/fuel/views/list.php (template-only)
// Expect: $fuelRecords, $vehicles, $drivers, $stats, $currentPage, $totalPages, $totalRecords, $perPage, $filters
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-gas-pump text-primary me-2"></i> Consum combustibil</h1>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>fuel/add" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Adaugă</a>
      <a href="<?= BASE_URL ?>fuel/reports" class="btn btn-outline-info"><i class="fas fa-chart-bar me-1"></i> Rapoarte</a>
    </div>
  </div>

  <!-- Statistici rapide -->
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100 border-success"><div class="card-body text-center">
      <div class="display-6 fw-bold text-success"><?= number_format((float)($stats['total_liters'] ?? 0),1) ?></div>
      <div class="text-muted">Litri total</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 border-primary"><div class="card-body text-center">
      <div class="display-6 fw-bold text-primary"><?= number_format((float)($stats['total_cost'] ?? 0),0) ?> RON</div>
      <div class="text-muted">Cost total</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 border-secondary"><div class="card-body text-center">
      <div class="display-6 fw-bold text-secondary"><?= number_format((float)($stats['avg_price_per_liter'] ?? 0),2) ?> RON</div>
      <div class="text-muted">Preț mediu/L</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 border-info"><div class="card-body text-center">
      <div class="display-6 fw-bold text-info"><?= (int)($stats['vehicles_count'] ?? 0) ?>/<?= (int)($stats['drivers_count'] ?? 0) ?></div>
      <div class="text-muted">Vehicule / Șoferi</div>
    </div></div></div>
  </div>

  <!-- Filtre -->
  <div class="card mb-3">
    <div class="card-header"><i class="fas fa-filter me-2"></i> Filtre</div>
    <div class="card-body">
      <form method="GET" action="<?= BASE_URL ?>fuel" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Căutare</label>
          <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="nr. înmatriculare, șofer, stație...">
        </div>
        <div class="col-md-3">
          <label class="form-label">Vehicul</label>
          <select class="form-select" name="vehicle">
            <option value="">Toate</option>
            <?php foreach (($vehicles ?? []) as $v): ?>
              <option value="<?= (int)$v['id'] ?>" <?= (($filters['vehicle'] ?? '')==$v['id'])?'selected':'' ?>>
                <?= htmlspecialchars(($v['registration_number'] ?? '') . ' ' . (($v['brand'] ?? '') . ' ' . ($v['model'] ?? ''))) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Șofer</label>
          <select class="form-select" name="driver">
            <option value="">Toți</option>
            <?php foreach (($drivers ?? []) as $d): ?>
              <option value="<?= (int)$d['id'] ?>" <?= (($filters['driver'] ?? '')==$d['id'])?'selected':'' ?>><?= htmlspecialchars($d['name'] ?? '-') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tip combustibil</label>
          <select class="form-select" name="fuel_type">
            <option value="">Toate</option>
            <?php foreach (['petrol'=>'Benzină','diesel'=>'Motorină','electric'=>'Electric','gas'=>'GPL'] as $k=>$label): ?>
              <option value="<?= $k ?>" <?= (($filters['fuel_type'] ?? '')===$k)?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">De la</label>
          <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Până la</label>
          <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Înregistrări/pagină</label>
          <select class="form-select" name="per_page">
            <?php foreach ([10,25,50,100] as $n): ?>
              <option value="<?= $n ?>" <?= ((int)($perPage ?? 25)===$n)?'selected':'' ?>><?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Filtrează</button>
          <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>fuel">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Data</th>
            <th>Vehicul</th>
            <th>Șofer</th>
            <th>Km</th>
            <th>Litri</th>
            <th>Preț/L</th>
            <th>Total</th>
            <th>Tip</th>
            <th>Stație</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fuelRecords)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Nu s-au găsit înregistrări.</td></tr>
          <?php else: ?>
            <?php foreach ($fuelRecords as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['fuel_date'] ?? '') ?></td>
                <td>
                  <?php if (!empty($r['registration_number'])): ?>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($r['registration_number']) ?></span>
                    <div class="small text-muted"><?= htmlspecialchars(($r['brand'] ?? '').' '.($r['model'] ?? '')) ?></div>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['driver_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars(number_format((float)($r['mileage'] ?? 0))) ?> km</td>
                <td><?= htmlspecialchars(number_format((float)($r['liters'] ?? 0),2)) ?></td>
                <td><?= htmlspecialchars(number_format((float)($r['cost_per_liter'] ?? 0),2)) ?></td>
                <td><?= htmlspecialchars(number_format((float)($r['total_cost'] ?? 0),2)) ?> RON</td>
                <td><?= htmlspecialchars($r['fuel_type'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['station'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (($totalPages ?? 1) > 1): ?>
      <div class="card-footer">
        <nav aria-label="Paginare">
          <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php if (($currentPage ?? 1) > 1): ?>
              <li class="page-item"><a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page'=>($currentPage-1)])) ?>">&laquo;</a></li>
            <?php endif; ?>
            <?php for ($i=max(1,($currentPage-2)); $i<=min($totalPages, ($currentPage+2)); $i++): ?>
              <li class="page-item <?= $i===$currentPage?'active':'' ?>"><a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
              <li class="page-item"><a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['page'=>($currentPage+1)])) ?>">&raquo;</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- View template only: header/footer are included by Controller::render() -->
