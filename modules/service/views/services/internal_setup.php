<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fa fa-cogs text-primary"></i> <?= $pageTitle ?></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?= ROUTE_BASE ?>service/services" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Înapoi
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detalii Atelier Intern</h5>
    </div>
    <div class="card-body">
        <form action="<?= ROUTE_BASE ?>service/services/internal-setup" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informații Generale</h6>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Denumire Atelier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= $service['name'] ?? 'Atelier Intern' ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresă</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?= $service['address'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?= $service['contact_phone'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= $service['contact_email'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Parametri Operaționali</h6>
                    
                    <div class="mb-3">
                        <label for="working_hours" class="form-label">Program de Lucru</label>
                        <input type="text" class="form-control" id="working_hours" name="working_hours" value="<?= $service['working_hours'] ?? 'L-V: 08:00 - 17:00' ?>" placeholder="ex: L-V: 08:00 - 17:00">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Capacitate (nr. posturi)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" value="<?= $service['capacity'] ?? 4 ?>" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hourly_rate" class="form-label">Tarif Orar (RON)</label>
                            <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" value="<?= $service['hourly_rate'] ?? 150.00 ?>" step="0.01" min="0">
                            <div class="form-text">Folosit pentru calculul costurilor interne</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Note / Observații</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= $service['notes'] ?? '' ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Salvează Configurare
                </button>
            </div>
        </form>
    </div>
</div>