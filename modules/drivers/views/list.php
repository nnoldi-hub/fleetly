<?php
// modules/drivers/views/list.php (template-only)
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-users text-primary me-2"></i> Gestiune Șoferi</h1>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>drivers/add" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Adaugă Șofer</a>
            <a href="<?= BASE_URL ?>drivers/export?format=csv" class="btn btn-outline-success"><i class="fas fa-download me-1"></i> Export CSV</a>
        </div>
    </div>

    <!-- Statistici rapide -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success h-100"><div class="card-body text-center">
                <div class="display-6 fw-bold text-success"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="text-muted">Șoferi Activi</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100"><div class="card-body text-center">
                <div class="display-6 fw-bold text-primary"><?= (int)($stats['assigned'] ?? 0) ?></div>
                <div class="text-muted">Mașini Atribuite</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary h-100"><div class="card-body text-center">
                <div class="display-6 fw-bold text-secondary"><?= (int)($stats['inactive'] ?? 0) ?></div>
                <div class="text-muted">Șoferi Inactivi</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100"><div class="card-body text-center">
                <div class="display-6 fw-bold text-warning"><?= (int)($stats['accidents'] ?? 0) ?></div>
                <div class="text-muted">Accidente</div>
            </div></div>
        </div>
    </div>

    <!-- Căutare -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-list me-2"></i> Lista Șoferi
        </div>
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>drivers" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Caută șoferi..." value="<?= htmlspecialchars($search ?? '') ?>">
                        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:6%">ID</th>
                        <th style="width:22%">Nume</th>
                        <th style="width:22%">Email</th>
                        <th style="width:16%">Telefon</th>
                        <th style="width:14%">Vehicul</th>
                        <th style="width:10%">Status</th>
                        <th style="width:10%">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($drivers)): ?>
                    <?php foreach ($drivers as $d): ?>
                    <tr>
                        <td><?= (int)$d['id'] ?></td>
                        <td class="fw-semibold">
                            <a href="<?= BASE_URL ?>drivers/view?id=<?= (int)$d['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($d['name']) ?>
                            </a>
                            <?php if (!empty($d['license_number'])): ?>
                                <div class="small text-muted">Permis: <?= htmlspecialchars($d['license_number']) ?> (<?= htmlspecialchars($d['license_category'] ?? '-') ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($d['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($d['phone'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($d['registration_number'])): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($d['registration_number']) ?></span>
                                <div class="small text-muted"><?= htmlspecialchars(($d['brand'] ?? '') . ' ' . ($d['model'] ?? '')) ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= ($d['status']==='active'?'bg-success':($d['status']==='suspended'?'bg-warning text-dark':'bg-secondary')) ?>">
                                <?= htmlspecialchars(ucfirst($d['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-info" href="<?= BASE_URL ?>drivers/view?id=<?= (int)$d['id'] ?>" title="Vizualizează"><i class="fas fa-eye"></i></a>
                                <a class="btn btn-outline-primary" href="<?= BASE_URL ?>drivers/edit?id=<?= (int)$d['id'] ?>" title="Editează"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-outline-danger" onclick="deleteDriver(<?= (int)$d['id'] ?>)" title="Șterge"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nu s-au găsit șoferi.</td></tr>
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

<script>
function deleteDriver(id){
    if(!confirm('Ești sigur că vrei să ștergi acest șofer?')) return;
    fetch('<?= BASE_URL ?>drivers/delete', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({id:id})})
        .then(r=>r.json()).then(d=>{ if(d && d.success){ location.reload(); } else { alert(d.error||'Eroare la ștergere'); } })
        .catch(()=>alert('Eroare la ștergere'));
}
</script>

<!-- View template only: header/footer are included by Controller::render() -->