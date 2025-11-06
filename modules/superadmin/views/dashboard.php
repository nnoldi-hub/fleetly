<?php /** @var array $metrics */ /** @var array $companies */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="fas fa-user-shield me-2"></i>Panou SuperAdmin</h3>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="<?= ROUTE_BASE ?>superadmin/companies/add"><i class="fas fa-plus me-1"></i> Adaugă companie</a>
      <a class="btn btn-outline-secondary" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-building me-1"></i> Toate companiile</a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 text-primary"><i class="fas fa-building fa-2x"></i></div>
          <div>
            <div class="small text-muted">Companii totale</div>
            <div class="h4 mb-2"><?= (int)$metrics['companies_total'] ?></div>
            <div class="d-flex gap-2">
              <a class="btn btn-sm btn-primary" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-list"></i></a>
              <a class="btn btn-sm btn-outline-primary" href="<?= ROUTE_BASE ?>superadmin/companies/add"><i class="fas fa-plus"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 text-success"><i class="fas fa-check-circle fa-2x"></i></div>
          <div>
            <div class="small text-muted">Active</div>
            <div class="h4 mb-0"><?= (int)$metrics['companies_active'] ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 text-warning"><i class="fas fa-hourglass-half fa-2x"></i></div>
          <div>
            <div class="small text-muted">Trial</div>
            <div class="h4 mb-0"><?= (int)$metrics['companies_trial'] ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="me-3 text-danger"><i class="fas fa-ban fa-2x"></i></div>
          <div>
            <div class="small text-muted">Suspendate</div>
            <div class="h4 mb-0"><?= (int)$metrics['companies_suspended'] ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="me-2 text-primary"><i class="fas fa-sitemap"></i></div>
            <h5 class="mb-0">Gestionează flota</h5>
          </div>
          <p class="text-muted small mb-3">Alege o companie și intră în modul de management flotă (intervenție).</p>
          <a class="btn btn-outline-primary" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-external-link-alt me-1"></i> Alege companie</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="me-2 text-secondary"><i class="fas fa-user-cog"></i></div>
            <h5 class="mb-0">Setări profil</h5>
          </div>
          <p class="text-muted small mb-3">Actualizează datele contului tău SuperAdmin.</p>
          <a class="btn btn-outline-secondary" href="<?= ROUTE_BASE ?>settings"><i class="fas fa-gear me-1"></i> Setări profil</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="me-2 text-danger"><i class="fas fa-sign-out-alt"></i></div>
            <h5 class="mb-0">Deconectare</h5>
          </div>
          <p class="text-muted small mb-3">Închide sesiunea curentă în siguranță.</p>
          <a class="btn btn-outline-danger" href="<?= ROUTE_BASE ?>logout"><i class="fas fa-power-off me-1"></i> Deconectează-te</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
      <div class="d-flex align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Companii recente</h5>
        <a class="btn btn-sm btn-outline-primary ms-auto" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-building me-1"></i> Toate companiile</a>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Nume</th>
              <th>Email</th>
              <th>Status</th>
              <th>Abonament</th>
              <th>Creat la</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($companies)): foreach ($companies as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['email']) ?></td>
              <td>
                <?php $badge = ['active'=>'success','trial'=>'warning','suspended'=>'danger','expired'=>'secondary'][$c['status']] ?? 'secondary'; ?>
                <span class="badge bg-<?= $badge ?> text-uppercase"><?= htmlspecialchars($c['status']) ?></span>
              </td>
              <td><?= htmlspecialchars($c['subscription_type'] ?? '-') ?></td>
              <td><?= htmlspecialchars($c['created_at']) ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-muted">Nu există companii încă.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
