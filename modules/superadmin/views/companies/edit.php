<?php /** @var object $company */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="fas fa-pen me-2"></i>Editează companie</h3>
    <a class="btn btn-outline-secondary" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-arrow-left me-1"></i> Înapoi</a>
  </div>

    <form class="card border-0 shadow-sm" method="post" action="<?= ROUTE_BASE ?>superadmin/companies/edit?id=<?= (int)$company->id ?>">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nume companie</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($company->name) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email companie</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($company->email) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">CUI/Registru</label>
          <input type="text" name="registration_number" class="form-control" value="<?= htmlspecialchars($company->registration_number ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefon</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company->phone ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Adresă</label>
          <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($company->address ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Oraș</label>
          <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($company->city ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Țară</label>
          <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($company->country ?? 'România') ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php foreach (['active'=>'Active','trial'=>'Trial','suspended'=>'Suspendată','expired'=>'Expirată'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $company->status===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Abonament</label>
          <select name="subscription_type" class="form-select">
            <?php foreach (['basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise','trial'=>'Trial'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($company->subscription_type ?? '')===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Max. utilizatori</label>
          <input type="number" name="max_users" class="form-control" min="1" value="<?= (int)($company->max_users ?? 5) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Max. vehicule</label>
          <input type="number" name="max_vehicles" class="form-control" min="1" value="<?= (int)($company->max_vehicles ?? 10) ?>">
        </div>
      </div>
    </div>
    <div class="card-footer bg-white text-end">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvează</button>
    </div>
  </form>

  <div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
      <strong><i class="fas fa-user-shield me-2"></i>Resetare cont administrator</strong>
    </div>
    <div class="card-body">
      <p class="text-muted mb-3">Resetează parola contului de administrator al acestei companii. Opțional poți actualiza și username-ul sau emailul administratorului. Noua parolă va fi afișată o singură dată după resetare.</p>
      <form method="post" action="<?= ROUTE_BASE ?>superadmin/companies/reset-admin?company_id=<?= (int)$company->id ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Username administrator (opțional)</label>
            <input type="text" name="admin_username" class="form-control" placeholder="lăsați gol pentru a păstra actualul">
          </div>
          <div class="col-md-4">
            <label class="form-label">Email administrator (opțional)</label>
            <input type="email" name="admin_email" class="form-control" placeholder="lăsați gol pentru a păstra actualul">
          </div>
          <div class="col-md-4">
            <label class="form-label">Parolă nouă (opțional, min. 6 caractere)</label>
            <input type="password" name="admin_password" class="form-control" placeholder="lăsați gol pentru generare automată">
          </div>
          <div class="col-12 d-flex align-items-end justify-content-end">
            <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i> Resetează parola admin</button>
          </div>
        </div>
      </form>
      <div class="small text-muted mt-2"><i class="fas fa-info-circle me-1"></i> După resetare, comunică parola nouă administratorului sau setează o parolă proprie în contul lui.</div>
    </div>
  </div>
</div>
