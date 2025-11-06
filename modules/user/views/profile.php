<?php
// modules/user/views/profile.php
$pageTitle = "Profil Utilizator";
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Profil</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-cog text-primary me-2"></i>
                Profil Utilizator
            </h1>
        </div>

                <div class="row">
                        <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            Informații Profil
                        </h5>
                    </div>
                    <div class="card-body">
                                                                        <?php if (!empty($_SESSION['success'])): ?>
                                                    <div class="alert alert-success" role="alert">
                                                        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                                        <?php if (!empty($_SESSION['errors'])): ?>
                                                                            <div class="alert alert-danger" role="alert">
                                                                                <?php $errs = $_SESSION['errors']; unset($_SESSION['errors']);
                                                                                    if (is_array($errs)) { echo '<ul class="mb-0 ps-3">'; foreach($errs as $e){ echo '<li>'.htmlspecialchars($e).'</li>'; } echo '</ul>'; }
                                                                                    else { echo htmlspecialchars($errs); }
                                                                                ?>
                                                                            </div>
                                                                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informații Utilizator</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nume:</strong> <?= $_SESSION['user_name'] ?? 'Utilizator' ?></li>
                                    <li><strong>Rol:</strong> Administrator</li>
                                    <li><strong>Ultima conectare:</strong> <?= date('d.m.Y H:i') ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Statistici</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Sesiuni active:</strong> 1</li>
                                    <li><strong>Ultima activitate:</strong> Acum</li>
                                </ul>
                            </div>
                        </div>

                                                <hr class="my-4" />
                                                <h6 class="mb-3"><i class="fas fa-sms me-1"></i> Notificări SMS</h6>
                                                <form method="post" action="<?= BASE_URL ?>profile">
                                                    <div class="row g-3">
                                                        <div class="col-12 col-md-6">
                                                            <label class="form-label">Telefon SMS</label>
                                                                                            <?php $old = $_SESSION['old_sms_phone'] ?? null; if ($old !== null) unset($_SESSION['old_sms_phone']); ?>
                                                                                            <input type="text" class="form-control" name="sms_phone" value="<?= htmlspecialchars($old !== null ? $old : ($smsPhone ?? '')) ?>" placeholder="+407xxxxxxxx" />
                                                            <div class="form-text">Numărul folosit pentru trimiterea notificărilor prin SMS.</div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Salvează</button>
                                                    </div>
                                                </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cog me-1"></i>
                            Acțiuni Rapide
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>settings" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-1"></i> Setări
                            </a>
                            <a href="<?= BASE_URL ?>notifications" class="btn btn-outline-info">
                                <i class="fas fa-bell me-1"></i> Notificări
                            </a>
                            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Deconectare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
