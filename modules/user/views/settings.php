<?php
// View simplificat: layout gestionat de Controller::render()
?>

<div class="container-fluid py-4">
    <?php include ROOT_PATH . '/includes/breadcrumb.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-cog text-primary me-2"></i>
            Setări Aplicație
        </h1>
    </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-palette me-2"></i>
                            Interfață
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Setările de interfață vor fi disponibile în versiunile viitoare.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tema</label>
                            <select class="form-select" disabled>
                                <option>Tema Clară (implicit)</option>
                                <option>Tema Întunecată</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Limba</label>
                            <select class="form-select" disabled>
                                <option>Română (implicit)</option>
                                <option>Engleză</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Notificări
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        require_once __DIR__ . '/../../../core/Auth.php';
                        $auth = Auth::getInstance();
                        $currentUser = $auth->user();
                        $userRole = $currentUser->role_slug ?? $currentUser->role ?? 'user';
                        $isAdminOrManager = in_array($userRole, ['admin', 'manager', 'superadmin']);
                        ?>
                        
                        <?php if ($isAdminOrManager): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Rol de administrator:</strong> Puteți configura notificările avansate (SMTP, SMS, broadcast).
                        </div>
                        
                        <a href="<?= ROUTE_BASE ?>notifications/settings" class="btn btn-success w-100 mb-3">
                            <i class="fas fa-cog me-2"></i>
                            Configurare Avansată Notificări
                        </a>
                        
                        <small class="text-muted">
                            Setări disponibile: categorii, metode (email/SMS), broadcast către companie, SMTP, Twilio/SMS Gateway.
                        </small>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Setările de notificări sunt gestionate de administrator.
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked disabled>
                            <label class="form-check-label" for="emailNotifications">
                                Notificări prin email
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="documentExpiry" checked disabled>
                            <label class="form-check-label" for="documentExpiry">
                                Alertă documente expirate
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="maintenanceAlerts" checked disabled>
                            <label class="form-check-label" for="maintenanceAlerts">
                                Alertă întreținere scadentă
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informații Sistem
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Aplicație</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Nume:</strong> <?= APP_NAME ?></li>
                                    <li><strong>Versiune:</strong> <?= APP_VERSION ?></li>
                                    <li><strong>Mediu:</strong> Dezvoltare</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Server</h6>
                                <ul class="list-unstyled">
                                    <li><strong>PHP:</strong> <?= phpversion() ?></li>
                                    <li><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Necunoscut' ?></li>
                                    <li><strong>OS:</strong> <?= php_uname('s') ?></li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Database</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Tip:</strong> MySQL</li>
                                    <li><strong>Host:</strong> localhost</li>
                                    <li><strong>Baza:</strong> fleet_management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
