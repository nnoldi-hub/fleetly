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
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>settings"><i class="fas fa-user-cog me-2"></i>Setări profil</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>logout"><i class="fas fa-sign-out-alt me-2"></i>Deconectare</a>
            </li>
        <?php else: ?>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#vehicleSubmenu" role="button" aria-expanded="false" aria-controls="vehicleSubmenu"><i class="fas fa-car me-2"></i>Vehicule</a>
                <div class="collapse" id="vehicleSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a class="nav-link text-white" href="<?= ROUTE_BASE ?>vehicles">Lista Vehicule</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="<?= ROUTE_BASE ?>vehicles/add">Adaugă Vehicul</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#documentsSubmenu" role="button" aria-expanded="false" aria-controls="documentsSubmenu"><i class="fas fa-file-contract me-2"></i>Documente</a>
                <div class="collapse" id="documentsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a class="nav-link text-white" href="<?= ROUTE_BASE ?>documents">Toate Documentele</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="<?= ROUTE_BASE ?>documents/expiring">Ce Expiră</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>drivers"><i class="fas fa-users me-2"></i>Șoferi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>maintenance"><i class="fas fa-tools me-2"></i>Întreținere</a>
            </li>
            <!-- Service Module links -->
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/services"><i class="fas fa-tools me-2"></i>Service-uri</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>service/workshop"><i class="fas fa-warehouse me-2"></i>Atelier Intern</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>fuel"><i class="fas fa-gas-pump me-2"></i>Combustibil</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>reports"><i class="fas fa-chart-bar me-2"></i>Rapoarte</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>import"><i class="fas fa-file-import me-2"></i>Import CSV</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>notifications"><i class="fas fa-bell me-2"></i>Notificări</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?= ROUTE_BASE ?>profile"><i class="fas fa-user me-2"></i>Utilizator</a>
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
                <a class="nav-link" href="<?= ROUTE_BASE ?>settings"><i class="fas fa-user-cog me-2"></i>Setări profil</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>logout"><i class="fas fa-sign-out-alt me-2"></i>Deconectare</a>
            </li>
        <?php else: ?>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" data-bs-toggle="collapse" href="#vehicleSubmenuMobile" role="button" aria-expanded="false" aria-controls="vehicleSubmenuMobile"><i class="fas fa-car me-2"></i>Vehicule</a>
                <div class="collapse" id="vehicleSubmenuMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a class="nav-link" href="<?= ROUTE_BASE ?>vehicles">Lista Vehicule</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ROUTE_BASE ?>vehicles/add">Adaugă Vehicul</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" data-bs-toggle="collapse" href="#documentsSubmenuMobile" role="button" aria-expanded="false" aria-controls="documentsSubmenuMobile"><i class="fas fa-file-contract me-2"></i>Documente</a>
                <div class="collapse" id="documentsSubmenuMobile">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item"><a class="nav-link" href="<?= ROUTE_BASE ?>documents">Toate Documentele</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ROUTE_BASE ?>documents/expiring">Ce Expiră</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>drivers"><i class="fas fa-users me-2"></i>Șoferi</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>maintenance"><i class="fas fa-tools me-2"></i>Întreținere</a>
            </li>
            <!-- Service Module mobile links -->
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>service/services"><i class="fas fa-tools me-2"></i>Service-uri</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>service/workshop"><i class="fas fa-warehouse me-2"></i>Atelier Intern</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>fuel"><i class="fas fa-gas-pump me-2"></i>Combustibil</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>reports"><i class="fas fa-chart-bar me-2"></i>Rapoarte</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>import"><i class="fas fa-file-import me-2"></i>Import CSV</a>
            </li>
            <li class="nav-item mt-auto mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>notifications"><i class="fas fa-bell me-2"></i>Notificări</a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="<?= ROUTE_BASE ?>profile"><i class="fas fa-user me-2"></i>Utilizator</a>
            </li>
        <?php endif; ?>
    </ul>
  </div>
</div>
