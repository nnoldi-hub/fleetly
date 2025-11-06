<?php /** @var array $companies */ /** @var array $filters */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="fas fa-building me-2"></i>Companii</h3>
    <a class="btn btn-primary" href="<?= ROUTE_BASE ?>superadmin/companies/add"><i class="fas fa-plus me-1"></i> Adaugă companie</a>
  </div>

  <form class="card border-0 shadow-sm mb-3" method="get" action="">
    <div class="card-body row g-2 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label">Căutare</label>
        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Nume, email, CUI...">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">Toate</option>
          <?php foreach (['active' => 'Active', 'trial' => 'Trial', 'suspended' => 'Suspendate', 'expired' => 'Expirate'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= (($filters['status'] ?? '')===$k?'selected':'') ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Abonament</label>
        <select name="subscription_type" class="form-select">
          <option value="">Toate</option>
          <?php foreach (['basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= (($filters['subscription_type'] ?? '')===$k?'selected':'') ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit"><i class="fas fa-search me-1"></i> Filtrează</button>
      </div>
    </div>
  </form>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nume</th>
            <th>Email</th>
            <th>Status</th>
            <th>Abonament</th>
            <th>Limite</th>
            <th>Creat la</th>
            <th class="text-end">Acțiuni</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($companies)): foreach ($companies as $c): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($c->name) ?></td>
            <td><?= htmlspecialchars($c->email) ?></td>
            <td>
              <?php $badge = ['active'=>'success','trial'=>'warning','suspended'=>'danger','expired'=>'secondary'][$c->status] ?? 'secondary'; ?>
              <span class="badge bg-<?= $badge ?> text-uppercase"><?= htmlspecialchars($c->status) ?></span>
            </td>
            <td><?= htmlspecialchars($c->subscription_type ?? '-') ?></td>
            <td><small class="text-muted">U: <?= (int)($c->max_users ?? 0) ?> • V: <?= (int)($c->max_vehicles ?? 0) ?></small></td>
            <td><?= htmlspecialchars($c->created_at) ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-outline-success" title="Gestionează flota" href="<?= ROUTE_BASE ?>superadmin/act-as?company_id=<?= (int)$c->id ?>">
                  <i class="fas fa-sign-in-alt"></i>
                </a>
                <a class="btn btn-outline-primary" title="Editează" href="<?= ROUTE_BASE ?>superadmin/companies/edit?id=<?= (int)$c->id ?>">
                  <i class="fas fa-pen"></i>
                </a>
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <?php foreach (['active'=>'Activează','trial'=>'Setează Trial','suspended'=>'Suspendă','expired'=>'Marchează Expirată'] as $st=>$label): ?>
                    <li>
                      <form method="post" action="<?= ROUTE_BASE ?>superadmin/companies/change-status" class="px-3 py-1">
                        <input type="hidden" name="id" value="<?= (int)$c->id ?>">
                        <input type="hidden" name="status" value="<?= $st ?>">
                        <button type="submit" class="dropdown-item<?= $c->status===$st ? ' disabled' : '' ?>"><?= $label ?></button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-muted">Nu există companii.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
