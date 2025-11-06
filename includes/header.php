<?php
// includes/header.php ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
  <!-- Bootstrap CSS (CDN 5.3.x) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/main.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/components/cards.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/components/forms.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/components/tables.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/vehicles.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/documents.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/drivers.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/insurance.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/maintenance.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/modules/reports.css" rel="stylesheet">
  <script>window.BASE_URL = '<?= BASE_URL ?>'.replace(/\/$/, '');</script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
      body { background: #f8f9fa; }
      #sidebarMenu { min-width:220px; max-width:220px; }
      .main-content { margin-left:220px; min-height:100vh; padding:2rem 2rem 2rem 2rem; }
      .app-header { margin-left:220px; }
      @media (max-width: 991px) {
        #sidebarMenu { position:relative; width:100%; min-width:unset; max-width:unset; height:auto; }
        .main-content { margin-left:0; }
        .app-header { margin-left:0; }
      }
    </style>
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <header class="bg-primary text-white py-2 px-3 app-header">
    <div class="d-flex align-items-center">
      <!-- Mobile: offcanvas toggle -->
      <button class="btn btn-outline-light d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Meniu">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0"><i class="fas fa-truck me-2"></i> Fleet Management System</h5>
      <span class="ms-auto"></span>
      <!-- Theme toggle -->
      <button id="themeToggle" class="btn btn-outline-light btn-sm ms-2" type="button" title="Comută tema">
        <i class="fas fa-moon"></i>
      </button>
    </div>
  </header>
  <?php if (!empty($_SESSION['acting_company']['id'])): ?>
    <div class="app-header" style="margin-left:220px;">
      <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center justify-content-between">
        <div>
          <i class="fas fa-user-shield me-2"></i>
          Modul intervenție activ – gestionezi compania:
          <strong><?= htmlspecialchars($_SESSION['acting_company']['name'] ?? ('#'.($_SESSION['acting_company']['id'] ?? ''))) ?></strong>
        </div>
        <a class="btn btn-sm btn-outline-dark" href="<?= ROUTE_BASE ?>superadmin/stop-acting">
          <i class="fas fa-door-open me-1"></i> Ieși din modul intervenție
        </a>
      </div>
    </div>
  <?php endif; ?>
  <main class="main-content">
    <!-- Mesaje flash -->
    <?php if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type'] ?? 'info') ?> alert-dismissible fade show m-3" role="alert">
        <i class="fas fa-info-circle"></i> <?= nl2br(htmlspecialchars($_SESSION['flash']['message'] ?? '')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
      <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Erori de validare:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($_SESSION['errors'] as $error): ?>
            <li><?= $error ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>
    <!-- Conținutul paginii va fi inclus aici -->