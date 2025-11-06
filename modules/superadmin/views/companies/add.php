<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="fas fa-plus me-2"></i>Adaugă companie</h3>
    <a class="btn btn-outline-secondary" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-arrow-left me-1"></i> Înapoi</a>
  </div>

  <form class="card border-0 shadow-sm" method="post" action="<?= ROUTE_BASE ?>superadmin/companies/add">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nume companie</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email companie</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">CUI/Registru (opțional)</label>
          <input type="text" name="registration_number" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefon (opțional)</label>
          <input type="text" name="phone" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Adresă (opțional)</label>
          <input type="text" name="address" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Oraș (opțional)</label>
          <input type="text" name="city" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Țară</label>
          <input type="text" name="country" class="form-control" value="România">
        </div>

        <div class="col-md-4">
          <label class="form-label">Abonament</label>
          <select name="subscription_type" class="form-select">
            <option value="trial">Trial</option>
            <option value="basic" selected>Basic</option>
            <option value="pro">Pro</option>
            <option value="enterprise">Enterprise</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Max. utilizatori</label>
          <input type="number" name="max_users" class="form-control" min="1" value="5">
        </div>
        <div class="col-md-4">
          <label class="form-label">Max. vehicule</label>
          <input type="number" name="max_vehicles" class="form-control" min="1" value="10">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12">
          <h5 class="mb-2"><i class="fas fa-user-shield me-2"></i>Administrator companie (opțional)</h5>
          <p class="text-muted small">Dacă completezi aceste câmpuri, va fi creat automat un utilizator cu rol de Admin pentru companie.</p>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email administrator</label>
          <input type="email" name="admin_email" class="form-control" placeholder="admin@exemplu.ro">
        </div>
        <div class="col-md-6">
          <label class="form-label">Utilizator administrator (opțional)</label>
          <input type="text" name="admin_username" class="form-control" placeholder="admin">
        </div>
        <div class="col-md-6">
          <label class="form-label">Prenume administrator (opțional)</label>
          <input type="text" name="admin_first_name" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nume administrator (opțional)</label>
          <input type="text" name="admin_last_name" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Parolă administrator (opțional)</label>
          <input type="text" name="admin_password" class="form-control" placeholder="lăsați gol pentru generare automată">
        </div>
      </div>
    </div>
    <div class="card-footer bg-white text-end">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvează</button>
    </div>
  </form>
</div>
