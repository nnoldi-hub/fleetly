<?php
/**
 * Admin - Lista Categorii Parteneri
 */
$pageTitle = 'Categorii Parteneri';

// Iconițe disponibile pentru selectare
$availableIcons = [
    'fa-cogs' => 'Piese/Rotiță',
    'fa-circle' => 'Cerc/Cauciuc',
    'fa-shield-alt' => 'Scut/Protecție',
    'fa-road' => 'Drum',
    'fa-gas-pump' => 'Pompă benzină',
    'fa-wrench' => 'Cheie/Service',
    'fa-hand-holding-usd' => 'Finanțare',
    'fa-map-marker-alt' => 'Locație/GPS',
    'fa-truck' => 'Camion',
    'fa-car' => 'Mașină',
    'fa-tools' => 'Unelte',
    'fa-certificate' => 'Certificat',
    'fa-handshake' => 'Parteneriat',
    'fa-building' => 'Clădire',
    'fa-industry' => 'Industrie'
];
?>

<div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-folder text-primary me-2"></i>Categorii Parteneri</h1>
                <p class="text-muted mb-0">Gestionează categoriile pentru parteneri și reclame</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Înapoi la Parteneri
                </a>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-category-create" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Adaugă Categorie
                </a>
            </div>
        </div>
        
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Categorii Grid -->
        <div class="row">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5>Nu există categorii</h5>
                            <p class="text-muted">Adaugă prima categorie pentru parteneri</p>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-category-create" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Adaugă Categorie
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 <?= !$category['is_active'] ? 'border-secondary' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 50px; height: 50px; background-color: <?= htmlspecialchars($category['color'] ?? '#007bff') ?>20;">
                                        <i class="fas <?= htmlspecialchars($category['icon'] ?? 'fa-folder') ?> fa-lg" 
                                           style="color: <?= htmlspecialchars($category['color'] ?? '#007bff') ?>"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?= htmlspecialchars($category['name']) ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($category['slug']) ?></small>
                                    </div>
                                </div>
                                
                                <?php if ($category['description']): ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($category['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary"><?= (int)$category['active_partners_count'] ?> parteneri activi</span>
                                        <?php if (!$category['is_active']): ?>
                                            <span class="badge bg-secondary">Inactiv</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">Ordine: <?= $category['sort_order'] ?></small>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="btn-group btn-group-sm w-100">
                                    <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-category-edit&id=<?= $category['id'] ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i>Editează
                                    </a>
                                    <?php if (($category['partners_count'] ?? 0) == 0): ?>
                                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-category-delete&id=<?= $category['id'] ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Sigur doriți să ștergeți această categorie?')">
                                            <i class="fas fa-trash me-1"></i>Șterge
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
