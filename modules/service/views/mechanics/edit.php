<?php
/**
 * View: Editare Mecanic
 */

$mechanic = $mechanic ?? [];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-user-edit"></i> Editare Mecanic</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= ROUTE_BASE ?>service/mechanics" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Informații Personale</h5>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nume Complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($mechanic['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="specialization" class="form-label">Specializare</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?= htmlspecialchars($mechanic['specialization'] ?? '') ?>" placeholder="ex: Mecanic Auto General">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data Angajare</label>
                            <input type="text" class="form-control" value="<?= $mechanic['hire_date'] ? date('d.m.Y', strtotime($mechanic['hire_date'])) : '-' ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="mb-3">Contact și Tarife</h5>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($mechanic['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($mechanic['email'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="hourly_rate" class="form-label">Tarif Orar (RON)</label>
                            <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" step="0.01" value="<?= htmlspecialchars($mechanic['hourly_rate'] ?? 150) ?>">
                            <div class="form-text">Tarif pentru calculul costurilor de manoperă</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= $mechanic['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Activ (disponibil pentru alocări)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="<?= ROUTE_BASE ?>service/mechanics" class="btn btn-secondary">Anulează</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvează Modificările
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
