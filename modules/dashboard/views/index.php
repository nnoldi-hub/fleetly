<?php
// modules/dashboard/views/index.php
$pageTitle = "Dashboard - Fleet Management System";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Dashboard Fleet Management</h1>
                
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Vehicule Total
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['total_vehicles']) ? (int)$stats['total_vehicles'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-car fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Șoferi Activi
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['active_drivers']) ? (int)$stats['active_drivers'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Mentenanță Programată
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['scheduled_maintenance']) ? (int)$stats['scheduled_maintenance'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wrench fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Alerte Active
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['active_alerts']) ? (int)$stats['active_alerts'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if (!empty($subscription)): ?>
                    <div class="col-lg-12">
                        <div class="alert alert-info alert-permanent d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Abonament:</strong> <?= htmlspecialchars(strtoupper($subscription['type'])) ?>
                                <span class="ml-3">Utilizatori: <strong><?= (int)$subscription['used_users'] ?></strong> / <?= (int)$subscription['max_users'] ?></span>
                                <span class="ml-3">Vehicule: <strong><?= (int)$subscription['used_vehicles'] ?></strong> / <?= (int)$subscription['max_vehicles'] ?></span>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>users" class="btn btn-sm btn-outline-primary">Gestionează utilizatori</a>
                                <a href="<?= BASE_URL ?>vehicles" class="btn btn-sm btn-outline-secondary">Gestionează vehicule</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Acces Rapid</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>vehicles" class="btn btn-primary btn-block">
                                            <i class="fas fa-car mr-2"></i>Gestionare Vehicule
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>drivers" class="btn btn-success btn-block">
                                            <i class="fas fa-users mr-2"></i>Gestionare Șoferi
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>fuel" class="btn btn-info btn-block">
                                            <i class="fas fa-gas-pump mr-2"></i>Gestionare Combustibil
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>maintenance" class="btn btn-warning btn-block">
                                            <i class="fas fa-wrench mr-2"></i>Gestionare Mentenanță
                                        </a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>insurance" class="btn btn-secondary btn-block">
                                            <i class="fas fa-shield-alt mr-2"></i>Gestionare Asigurări
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>documents" class="btn btn-dark btn-block">
                                            <i class="fas fa-file-alt mr-2"></i>Gestionare Documente
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>reports" class="btn btn-primary btn-block">
                                            <i class="fas fa-chart-bar mr-2"></i>Rapoarte
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="<?= BASE_URL ?>notifications" class="btn btn-danger btn-block">
                                            <i class="fas fa-bell mr-2"></i>Notificări
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Activitate Recentă</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Nu există activitate recentă.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Notificări Importante</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Nu există notificări importante.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
