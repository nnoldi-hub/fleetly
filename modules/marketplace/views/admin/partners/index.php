<?php
/**
 * Admin - Lista Parteneri
 */
$pageTitle = 'Administrare Parteneri & Reclame';
include __DIR__ . '/../../../../../includes/header.php';
include __DIR__ . '/../../../../../includes/sidebar.php';
?>

<main class="main-content" style="margin-left: 220px; padding: 20px;">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-handshake text-primary me-2"></i>Parteneri & Reclame</h1>
                <p class="text-muted mb-0">Gestionează linkurile și reclamele către firmele partenere</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-categories" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-folder me-1"></i>Categorii
                </a>
                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-create" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Adaugă Partener
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
        
        <!-- Filtre -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="action" value="admin-partners">
                    <div class="col-md-4">
                        <label class="form-label">Categorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">Toate categoriile</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($_GET['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Căutare</label>
                        <input type="text" name="search" class="form-control" placeholder="Nume partener..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>Filtrează
                        </button>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabel Parteneri -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($partners)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-handshake fa-4x text-muted mb-3"></i>
                        <h5>Nu există parteneri încă</h5>
                        <p class="text-muted">Adaugă primul partener pentru a începe</p>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-create" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Adaugă Partener
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">Logo</th>
                                    <th>Partener</th>
                                    <th>Categorie</th>
                                    <th>Contact</th>
                                    <th class="text-center">Vizualizări</th>
                                    <th class="text-center">Click-uri</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Featured</th>
                                    <th width="150">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partners as $partner): ?>
                                    <tr class="<?= !$partner['is_active'] ? 'table-secondary' : '' ?>">
                                        <td>
                                            <?php if ($partner['logo']): ?>
                                                <img src="<?= BASE_URL . htmlspecialchars($partner['logo']) ?>" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-building text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($partner['name']) ?></strong>
                                            <?php if ($partner['discount_info']): ?>
                                                <br><small class="text-success"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($partner['discount_info']) ?></small>
                                            <?php endif; ?>
                                            <?php if ($partner['website_url']): ?>
                                                <br><small class="text-muted"><i class="fas fa-link me-1"></i><?= htmlspecialchars(parse_url($partner['website_url'], PHP_URL_HOST)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: <?= htmlspecialchars($partner['category_color'] ?? '#6c757d') ?>">
                                                <i class="fas <?= htmlspecialchars($partner['category_icon'] ?? 'fa-folder') ?> me-1"></i>
                                                <?= htmlspecialchars($partner['category_name'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($partner['phone']): ?>
                                                <small><i class="fas fa-phone me-1"></i><?= htmlspecialchars($partner['phone']) ?></small><br>
                                            <?php endif; ?>
                                            <?php if ($partner['email']): ?>
                                                <small><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($partner['email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= number_format($partner['views_count'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= number_format($partner['clicks_count'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-toggle-status&id=<?= $partner['id'] ?>" 
                                               class="btn btn-sm <?= $partner['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                                <i class="fas <?= $partner['is_active'] ? 'fa-check' : 'fa-times' ?>"></i>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-toggle-featured&id=<?= $partner['id'] ?>" 
                                               class="btn btn-sm <?= $partner['is_featured'] ? 'btn-warning' : 'btn-outline-warning' ?>">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-edit&id=<?= $partner['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Editează">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>modules/marketplace/?action=partner-show&id=<?= $partner['id'] ?>" 
                                                   class="btn btn-outline-info" title="Vizualizează" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-delete&id=<?= $partner['id'] ?>" 
                                                   class="btn btn-outline-danger" title="Șterge"
                                                   onclick="return confirm('Sigur doriți să ștergeți acest partener?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistici sumar -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= count($partners) ?></h3>
                        <small>Total Parteneri</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= count(array_filter($partners, fn($p) => $p['is_active'])) ?></h3>
                        <small>Parteneri Activi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= count(array_filter($partners, fn($p) => $p['is_featured'])) ?></h3>
                        <small>Parteneri Promovați</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?= array_sum(array_column($partners, 'clicks_count')) ?></h3>
                        <small>Total Click-uri</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../../../../includes/footer.php'; ?>
