<?php
/**
 * Pagina principală Parteneri - vizualizare pentru utilizatori
 */
$pageTitle = 'Parteneri & Oferte';
include __DIR__ . '/../../../../includes/header.php';
include __DIR__ . '/../../../../includes/sidebar.php';
?>

<main class="main-content" style="margin-left: 220px; padding: 20px;">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-primary text-white rounded-3 p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 mb-2"><i class="fas fa-handshake me-3"></i>Parteneri & Oferte Speciale</h1>
                            <p class="lead mb-0">Descoperă ofertele exclusive de la partenerii noștri - piese auto, cauciucuri, asigurări, roviniete și multe altele!</p>
                        </div>
                        <div class="col-md-4 text-end d-none d-md-block">
                            <i class="fas fa-tags fa-5x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Căutare și filtre -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="action" value="partners">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control form-control-lg" 
                               placeholder="Caută parteneri, produse, servicii..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-4">Caută</button>
                </form>
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-lg" onchange="window.location.href=this.value">
                    <option value="<?= BASE_URL ?>modules/marketplace/?action=partners">Toate categoriile</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= BASE_URL ?>modules/marketplace/?action=partners&category=<?= urlencode($cat['slug']) ?>"
                                <?= ($_GET['category'] ?? '') == $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?> (<?= $cat['active_partners_count'] ?? 0 ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if ($selectedCategory): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas <?= htmlspecialchars($selectedCategory['icon']) ?> me-2"></i>
                    Afișare parteneri din categoria: <strong><?= htmlspecialchars($selectedCategory['name']) ?></strong>
                </span>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=partners" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-times me-1"></i>Șterge filtrul
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Parteneri Featured -->
        <?php if (!empty($featuredPartners) && empty($_GET['category']) && empty($_GET['search'])): ?>
            <div class="mb-5">
                <h4 class="mb-3"><i class="fas fa-star text-warning me-2"></i>Parteneri Recomandați</h4>
                <div class="row">
                    <?php foreach ($featuredPartners as $partner): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-warning shadow-sm">
                                <div class="position-relative">
                                    <?php if ($partner['banner_image']): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($partner['banner_image']) ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($partner['name']) ?>"
                                             style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-gradient text-white d-flex align-items-center justify-content-center" 
                                             style="height: 150px; background: linear-gradient(135deg, <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?> 0%, #333 100%);">
                                            <i class="fas <?= htmlspecialchars($partner['category_icon'] ?? 'fa-building') ?> fa-4x opacity-50"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>Recomandat
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if ($partner['logo']): ?>
                                            <img src="<?= BASE_URL . htmlspecialchars($partner['logo']) ?>" 
                                                 alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($partner['name']) ?></h5>
                                            <small class="text-muted"><?= htmlspecialchars($partner['category_name']) ?></small>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted"><?= htmlspecialchars(substr($partner['description'] ?? '', 0, 100)) ?>...</p>
                                    <?php if ($partner['discount_info']): ?>
                                        <div class="alert alert-success py-2 mb-2">
                                            <i class="fas fa-gift me-1"></i><?= htmlspecialchars($partner['discount_info']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-show&id=<?= $partner['id'] ?>" 
                                           class="btn btn-primary flex-grow-1">
                                            <i class="fas fa-eye me-1"></i>Detalii
                                        </a>
                                        <?php if ($partner['website_url']): ?>
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-redirect&id=<?= $partner['id'] ?>" 
                                               class="btn btn-success" target="_blank">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Categorii rapide -->
        <?php if (empty($_GET['category']) && empty($_GET['search'])): ?>
            <div class="mb-5">
                <h4 class="mb-3"><i class="fas fa-th-large me-2"></i>Categorii</h4>
                <div class="row">
                    <?php foreach ($categories as $cat): ?>
                        <?php if (($cat['active_partners_count'] ?? 0) > 0): ?>
                            <div class="col-lg-3 col-md-4 col-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/marketplace/?action=partners&category=<?= urlencode($cat['slug']) ?>" 
                                   class="card text-decoration-none h-100 category-card">
                                    <div class="card-body text-center py-4">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                             style="width: 60px; height: 60px; background-color: <?= htmlspecialchars($cat['color'] ?? '#007bff') ?>20;">
                                            <i class="fas <?= htmlspecialchars($cat['icon'] ?? 'fa-folder') ?> fa-2x"
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#007bff') ?>"></i>
                                        </div>
                                        <h6 class="mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                                        <small class="text-muted"><?= $cat['active_partners_count'] ?? 0 ?> parteneri</small>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Lista parteneri -->
        <?php if (!empty($partnersByCategory)): ?>
            <?php foreach ($partnersByCategory as $catSlug => $catData): ?>
                <div class="mb-5">
                    <h4 class="mb-3 d-flex align-items-center">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center me-2"
                              style="width: 40px; height: 40px; background-color: <?= htmlspecialchars($catData['category']['color'] ?? '#007bff') ?>20;">
                            <i class="fas <?= htmlspecialchars($catData['category']['icon'] ?? 'fa-folder') ?>"
                               style="color: <?= htmlspecialchars($catData['category']['color'] ?? '#007bff') ?>"></i>
                        </span>
                        <?= htmlspecialchars($catData['category']['name']) ?>
                        <span class="badge bg-secondary ms-2"><?= count($catData['partners']) ?></span>
                    </h4>
                    
                    <div class="row">
                        <?php foreach ($catData['partners'] as $partner): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 partner-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <?php if ($partner['logo']): ?>
                                                <img src="<?= BASE_URL . htmlspecialchars($partner['logo']) ?>" 
                                                     alt="" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded me-3 d-flex align-items-center justify-content-center"
                                                     style="width: 60px; height: 60px; background-color: <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?>20;">
                                                    <i class="fas fa-building fa-2x" style="color: <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?>"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1"><?= htmlspecialchars($partner['name']) ?></h5>
                                                <?php if ($partner['is_featured']): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Recomandat</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="text-muted small mb-3">
                                            <?= htmlspecialchars(substr($partner['description'] ?? 'Fără descriere', 0, 120)) ?>
                                            <?= strlen($partner['description'] ?? '') > 120 ? '...' : '' ?>
                                        </p>
                                        
                                        <?php if ($partner['discount_info']): ?>
                                            <div class="bg-success bg-opacity-10 text-success rounded p-2 mb-3">
                                                <i class="fas fa-tag me-1"></i>
                                                <small><strong><?= htmlspecialchars($partner['discount_info']) ?></strong></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($partner['promo_code']): ?>
                                            <div class="bg-light rounded p-2 mb-3 text-center">
                                                <small class="text-muted">Cod promoțional:</small><br>
                                                <code class="fs-5"><?= htmlspecialchars($partner['promo_code']) ?></code>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-show&id=<?= $partner['id'] ?>" 
                                               class="btn btn-outline-primary flex-grow-1">
                                                <i class="fas fa-info-circle me-1"></i>Detalii
                                            </a>
                                            <?php if ($partner['website_url']): ?>
                                                <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-redirect&id=<?= $partner['id'] ?>" 
                                                   class="btn btn-success flex-grow-1" target="_blank">
                                                    <i class="fas fa-external-link-alt me-1"></i>Vizitează
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif (!empty($_GET['search']) || !empty($_GET['category'])): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4>Nu am găsit parteneri</h4>
                <p class="text-muted">Încearcă să modifici criteriile de căutare</p>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=partners" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i>Vezi toți partenerii
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-handshake fa-4x text-muted mb-3"></i>
                <h4>Nu există parteneri încă</h4>
                <p class="text-muted">Partenerii vor fi adăugați în curând</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.category-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}
.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    border-color: #007bff;
}
.partner-card {
    transition: all 0.3s ease;
}
.partner-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}
</style>

<?php include __DIR__ . '/../../../../includes/footer.php'; ?>
