<!-- Sidebar vertical fix stanga Bootstrap 5 -->
<!-- Desktop sidebar -->
<nav id="sidebarMenu" class="d-none d-lg-flex flex-column bg-primary text-white vh-100 position-fixed" style="width: 220px; z-index: 1040;">
    <div class="sidebar-header py-4 px-3 border-bottom border-light">
        <h4 class="mb-0"><i class="fas fa-truck me-2"></i>Fleet Management</h4>
    </div>
    <ul class="nav flex-column mt-3">
        <?php $auth = class_exists('Auth') ? Auth::getInstance() : null; ?>
        <?php if ($auth && $auth->isSuperAdmin()): ?>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>superadmin/dashboard"><i class="fas fa-user-shield me-2"></i>Panou SuperAdmin</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-building me-2"></i>Companii</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>superadmin/companies/add"><i class="fas fa-plus me-2"></i>Adaugă companie</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-sitemap me-2"></i>Gestionează flota</a>
            </li>
            <!-- PARTENERI & RECLAME ADMIN -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners"><i class="fas fa-handshake me-2"></i>Parteneri & Reclame</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>settings"><i class="fas fa-user-cog me-2"></i>Setări profil</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>logout"><i class="fas fa-sign-out-alt me-2"></i>Deconectare</a>
            </li>
        <?php else: ?>
            <!-- 1. ADMINISTRARE FLOTĂ -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" data-bs-toggle="collapse" href="#fleetManagement" role="button" aria-expanded="true" aria-controls="fleetManagement">
                    <i class="fas fa-truck-moving me-2"></i>Administrare Flotă
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse show" id="fleetManagement">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>vehicles">
                                <i class="fas fa-car me-2"></i>Vehicule
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>drivers">
                                <i class="fas fa-users me-2"></i>Șoferi
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>documents">
                                <i class="fas fa-file-contract me-2"></i>Documente
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 2. ÎNTREȚINERE ȘI SERVICE -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" data-bs-toggle="collapse" href="#maintenanceService" role="button" aria-expanded="false" aria-controls="maintenanceService">
                    <i class="fas fa-wrench me-2"></i>Întreținere și Service
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="maintenanceService">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>maintenance">
                                <i class="fas fa-tools me-2"></i>Întreținere
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/services">
                                <i class="fas fa-cogs me-2"></i>Service-uri
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/workshop">
                                <i class="fas fa-warehouse me-2"></i>Atelier Intern
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/mechanics">
                                <i class="fas fa-user-cog me-2"></i>Mecanici
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/parts">
                                <i class="fas fa-boxes me-2"></i>Piese
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/reports">
                                <i class="fas fa-chart-line me-2"></i>Rapoarte Service
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 3. CONSUM ȘI RESURSE -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" data-bs-toggle="collapse" href="#consumption" role="button" aria-expanded="false" aria-controls="consumption">
                    <i class="fas fa-gas-pump me-2"></i>Consum și Resurse
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="consumption">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>fuel">
                                <i class="fas fa-gas-pump me-2"></i>Combustibil
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>reports">
                                <i class="fas fa-chart-bar me-2"></i>Rapoarte
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 4. IMPORTURI ȘI NOTIFICĂRI -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" data-bs-toggle="collapse" href="#importNotifications" role="button" aria-expanded="false" aria-controls="importNotifications">
                    <i class="fas fa-exchange-alt me-2"></i>Importuri și Notificări
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="importNotifications">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>import">
                                <i class="fas fa-file-import me-2"></i>Import CSV
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>notifications">
                                <i class="fas fa-bell me-2"></i>Notificări
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 5. PARTENERI & OFERTE -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" href="<?= BASE_URL ?>modules/marketplace/?action=partners">
                    <i class="fas fa-handshake me-2"></i>Parteneri & Oferte
                </a>
            </li>

            <!-- 6. CONT UTILIZATOR -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white fw-bold" data-bs-toggle="collapse" href="#userAccount" role="button" aria-expanded="false" aria-controls="userAccount">
                    <i class="fas fa-user-circle me-2"></i>Cont Utilizator
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="userAccount">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white" href="<?= ROUTE_BASE ?>profile">
                                <i class="fas fa-user me-2"></i>Profil
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Mobile offcanvas sidebar -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header bg-primary text-white">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="fas fa-truck me-2"></i>Fleet Management</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column mt-3">
        <?php $auth = class_exists('Auth') ? Auth::getInstance() : null; ?>
        <?php if ($auth && $auth->isSuperAdmin()): ?>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>superadmin/dashboard"><i class="fas fa-user-shield me-2"></i>Panou SuperAdmin</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-building me-2"></i>Companii</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>superadmin/companies/add"><i class="fas fa-plus me-2"></i>Adaugă companie</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>superadmin/companies"><i class="fas fa-sitemap me-2"></i>Gestionează flota</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners"><i class="fas fa-handshake me-2"></i>Parteneri & Reclame</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>settings"><i class="fas fa-user-cog me-2"></i>Setări profil</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>logout"><i class="fas fa-sign-out-alt me-2"></i>Deconectare</a>
            </li>
        <?php else: ?>
            <!-- 1. ADMINISTRARE FLOTĂ (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" data-bs-toggle="collapse" href="#fleetManagementMobile" role="button" aria-expanded="true" aria-controls="fleetManagementMobile">
                    <i class="fas fa-truck-moving me-2"></i>Administrare Flotă
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse show" id="fleetManagementMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>vehicles">
                                <i class="fas fa-car me-2"></i>Vehicule
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>drivers">
                                <i class="fas fa-users me-2"></i>Șoferi
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>documents">
                                <i class="fas fa-file-contract me-2"></i>Documente
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 2. ÎNTREȚINERE ȘI SERVICE (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" data-bs-toggle="collapse" href="#maintenanceServiceMobile" role="button" aria-expanded="false" aria-controls="maintenanceServiceMobile">
                    <i class="fas fa-wrench me-2"></i>Întreținere și Service
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="maintenanceServiceMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>maintenance">
                                <i class="fas fa-tools me-2"></i>Întreținere
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>service/services">
                                <i class="fas fa-cogs me-2"></i>Service-uri
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>service/workshop">
                                <i class="fas fa-warehouse me-2"></i>Atelier Intern
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>service/mechanics">
                                <i class="fas fa-user-cog me-2"></i>Mecanici
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>service/parts">
                                <i class="fas fa-boxes me-2"></i>Piese
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>service/reports">
                                <i class="fas fa-chart-line me-2"></i>Rapoarte Service
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 3. CONSUM ȘI RESURSE (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" data-bs-toggle="collapse" href="#consumptionMobile" role="button" aria-expanded="false" aria-controls="consumptionMobile">
                    <i class="fas fa-gas-pump me-2"></i>Consum și Resurse
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="consumptionMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>fuel">
                                <i class="fas fa-gas-pump me-2"></i>Combustibil
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>reports">
                                <i class="fas fa-chart-bar me-2"></i>Rapoarte
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 4. IMPORTURI ȘI NOTIFICĂRI (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" data-bs-toggle="collapse" href="#importNotificationsMobile" role="button" aria-expanded="false" aria-controls="importNotificationsMobile">
                    <i class="fas fa-exchange-alt me-2"></i>Importuri și Notificări
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="importNotificationsMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>import">
                                <i class="fas fa-file-import me-2"></i>Import CSV
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>notifications">
                                <i class="fas fa-bell me-2"></i>Notificări
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 5. PARTENERI & OFERTE (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" href="<?= BASE_URL ?>modules/marketplace/?action=partners">
                    <i class="fas fa-handshake me-2"></i>Parteneri & Oferte
                </a>
            </li>

            <!-- 6. CONT UTILIZATOR (Mobile) -->
            <li class="nav-item mb-2">
                <a class="nav-link fw-bold" data-bs-toggle="collapse" href="#userAccountMobile" role="button" aria-expanded="false" aria-controls="userAccountMobile">
                    <i class="fas fa-user-circle me-2"></i>Cont Utilizator
                    <i class="fas fa-chevron-down float-end mt-1"></i>
                </a>
                <div class="collapse" id="userAccountMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="<?= ROUTE_BASE ?>profile">
                                <i class="fas fa-user me-2"></i>Profil
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        <?php endif; ?>
    </ul>
  </div>
</div>
