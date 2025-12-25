<?php
/**
 * Pagina detalii Partener
 */
$pageTitle = htmlspecialchars($partner['name']) . ' - Parteneri';
include __DIR__ . '/../../../../includes/header.php';
include __DIR__ . '/../../../../includes/sidebar.php';
?>

<main class="main-content" style="margin-left: 220px; padding: 20px;">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=partners">Parteneri</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=partners&category=<?= urlencode($partner['category_slug']) ?>"><?= htmlspecialchars($partner['category_name']) ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($partner['name']) ?></li>
            </ol>
        </nav>
        
        <!-- Banner -->
        <?php if ($partner['banner_image']): ?>
            <div class="mb-4 rounded-3 overflow-hidden">
                <img src="<?= BASE_URL . htmlspecialchars($partner['banner_image']) ?>" 
                     alt="<?= htmlspecialchars($partner['name']) ?>" 
                     class="w-100" style="max-height: 300px; object-fit: cover;">
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Coloana principală -->
            <div class="col-lg-8">
                <!-- Header partener -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <?php if ($partner['logo']): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($partner['logo']) ?>" 
                                     alt="<?= htmlspecialchars($partner['name']) ?>" 
                                     class="rounded me-4" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded me-4 d-flex align-items-center justify-content-center"
                                     style="width: 100px; height: 100px; background-color: <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?>20;">
                                    <i class="fas fa-building fa-3x" style="color: <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h1 class="h2 mb-2"><?= htmlspecialchars($partner['name']) ?></h1>
                                        <span class="badge mb-2" style="background-color: <?= htmlspecialchars($partner['category_color'] ?? '#007bff') ?>">
                                            <i class="fas <?= htmlspecialchars($partner['category_icon'] ?? 'fa-folder') ?> me-1"></i>
                                            <?= htmlspecialchars($partner['category_name']) ?>
                                        </span>
                                        <?php if ($partner['is_featured']): ?>
                                            <span class="badge bg-warning text-dark ms-1">
                                                <i class="fas fa-star me-1"></i>Partener Recomandat
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($partner['description']): ?>
                                    <p class="text-muted mt-2 mb-0"><?= htmlspecialchars($partner['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ofertă specială -->
                <?php if ($partner['discount_info'] || $partner['promo_code']): ?>
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Ofertă Specială pentru Clienții FleetManagement</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($partner['discount_info']): ?>
                                <div class="alert alert-success mb-3">
                                    <i class="fas fa-tag me-2"></i>
                                    <strong><?= htmlspecialchars($partner['discount_info']) ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($partner['promo_code']): ?>
                                <div class="text-center py-3">
                                    <p class="mb-2">Folosește codul promoțional:</p>
                                    <div class="bg-light rounded p-3 d-inline-block">
                                        <code class="fs-3 user-select-all"><?= htmlspecialchars($partner['promo_code']) ?></code>
                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyPromoCode('<?= htmlspecialchars($partner['promo_code']) ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">Click pentru a copia codul</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Text promoțional -->
                <?php if ($partner['promotional_text']): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Despre <?= htmlspecialchars($partner['name']) ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="promotional-text">
                                <?= nl2br(htmlspecialchars($partner['promotional_text'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Call to Action -->
                <div class="card mb-4 bg-primary text-white">
                    <div class="card-body text-center py-4">
                        <h4 class="mb-3">Interesat de ofertele <?= htmlspecialchars($partner['name']) ?>?</h4>
                        <?php if ($partner['website_url']): ?>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-redirect&id=<?= $partner['id'] ?>" 
                               class="btn btn-light btn-lg" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Vizitează Website-ul
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Buton principal -->
                <?php if ($partner['website_url']): ?>
                    <div class="d-grid mb-4">
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-redirect&id=<?= $partner['id'] ?>" 
                           class="btn btn-success btn-lg py-3" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>Accesează Oferta
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Informații contact -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($partner['phone']): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-phone text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Telefon</small><br>
                                    <a href="tel:<?= htmlspecialchars($partner['phone']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($partner['phone']) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($partner['email']): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-envelope text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Email</small><br>
                                    <a href="mailto:<?= htmlspecialchars($partner['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($partner['email']) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($partner['website_url']): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-globe text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Website</small><br>
                                    <a href="<?= htmlspecialchars($partner['website_url']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars(parse_url($partner['website_url'], PHP_URL_HOST)) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($partner['address']): ?>
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Adresă</small><br>
                                    <?= nl2br(htmlspecialchars($partner['address'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$partner['phone'] && !$partner['email'] && !$partner['address']): ?>
                            <p class="text-muted mb-0">Informații de contact indisponibile.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Parteneri similari -->
                <?php if (!empty($similarPartners)): ?>
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-th-list me-2"></i>Parteneri Similari</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($similarPartners as $similar): ?>
                                <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-show&id=<?= $similar['id'] ?>" 
                                   class="list-group-item list-group-item-action d-flex align-items-center">
                                    <?php if ($similar['logo']): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($similar['logo']) ?>" 
                                             alt="" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded me-3 d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px; background-color: #f8f9fa;">
                                            <i class="fas fa-building text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($similar['name']) ?></strong>
                                        <?php if ($similar['discount_info']): ?>
                                            <br><small class="text-success"><i class="fas fa-tag me-1"></i>Ofertă specială</small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Înapoi -->
        <div class="mt-4">
            <a href="<?= BASE_URL ?>modules/marketplace/?action=partners" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Înapoi la toți partenerii
            </a>
        </div>
    </div>
</main>

<script>
function copyPromoCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Codul promoțional a fost copiat: ' + code);
    });
}
</script>

<style>
.promotional-text {
    font-size: 1.1rem;
    line-height: 1.8;
}
</style>

<?php include __DIR__ . '/../../../../includes/footer.php'; ?>
