<?php
/**
 * Admin - Formular Partener (Create/Edit)
 */
$isEdit = !empty($partner->id);
$pageTitle = $isEdit ? 'Editare Partener' : 'Adaugă Partener Nou';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle' ?> text-primary me-2"></i>
                <?= $pageTitle ?>
            </h1>
            <p class="text-muted mb-0">
                    <?= $isEdit ? 'Modifică informațiile partenerului' : 'Adaugă un nou partener/furnizor în marketplace' ?>
                </p>
            </div>
            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Înapoi la listă
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erori:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" action="<?= BASE_URL ?>modules/marketplace/?action=<?= $isEdit ? 'admin-partner-update' : 'admin-partner-store' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $partner->id ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Coloana principală -->
                <div class="col-lg-8">
                    <!-- Informații de bază -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Informații de bază</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Numele Partenerului <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-lg" 
                                           value="<?= htmlspecialchars($partner->name ?? '') ?>" required
                                           placeholder="Ex: AutoParts Pro SRL">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Categorie <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select form-select-lg" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ($partner->category_id ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descriere scurtă</label>
                                <textarea name="description" class="form-control" rows="2" 
                                          placeholder="O descriere scurtă a firmei (1-2 propoziții)"><?= htmlspecialchars($partner->description ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Text Promoțional</label>
                                <textarea name="promotional_text" class="form-control" rows="4" 
                                          placeholder="Textul complet de promovare care va fi afișat utilizatorilor. Include beneficii, oferte speciale, etc."><?= htmlspecialchars($partner->promotional_text ?? '') ?></textarea>
                                <small class="text-muted">Acest text va fi afișat pe pagina de detalii a partenerului.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact și Link-uri -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-link text-success me-2"></i>Contact și Link-uri</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Website URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                        <input type="url" name="website_url" class="form-control" 
                                               value="<?= htmlspecialchars($partner->website_url ?? '') ?>"
                                               placeholder="https://www.exemplu.ro">
                                    </div>
                                    <small class="text-muted">Link-ul către care vor fi redirecționați utilizatorii când dau click</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Telefon</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($partner->phone ?? '') ?>"
                                               placeholder="0722 123 456">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($partner->email ?? '') ?>"
                                               placeholder="contact@exemplu.ro">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cod Promoțional</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-ticket-alt"></i></span>
                                        <input type="text" name="promo_code" class="form-control" 
                                               value="<?= htmlspecialchars($partner->promo_code ?? '') ?>"
                                               placeholder="FLEET2024">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adresă</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Strada, număr, oraș"><?= htmlspecialchars($partner->address ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ofertă Specială / Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                    <input type="text" name="discount_info" class="form-control" 
                                           value="<?= htmlspecialchars($partner->discount_info ?? '') ?>"
                                           placeholder="Ex: 15% discount pentru clienții FleetManagement">
                                </div>
                                <small class="text-muted">Dacă oferiți un discount special, specificați-l aici</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imagini -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-image text-info me-2"></i>Imagini</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Logo Partener</label>
                                    <?php if (!empty($partner->logo)): ?>
                                        <div class="mb-2">
                                            <img src="<?= BASE_URL . htmlspecialchars($partner->logo) ?>" alt="Logo actual" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                    <small class="text-muted">Format: JPG, PNG, GIF. Max 2MB</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Banner Reclamă</label>
                                    <?php if (!empty($partner->banner_image)): ?>
                                        <div class="mb-2">
                                            <img src="<?= BASE_URL . htmlspecialchars($partner->banner_image) ?>" alt="Banner actual" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="banner_image" class="form-control" accept="image/*">
                                    <small class="text-muted">Recomandat: 1200x400px</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coloana laterală -->
                <div class="col-lg-4">
                    <!-- Publicare -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Setări Publicare</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       <?= ($partner->is_active ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    <strong>Activ</strong><br>
                                    <small class="text-muted">Partenerul va fi vizibil pentru utilizatori</small>
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" 
                                       <?= ($partner->is_featured ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_featured">
                                    <strong>Promovat (Featured)</strong><br>
                                    <small class="text-muted">Va apărea în secțiunea parteneri evidențiați</small>
                                </label>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label class="form-label">Ordine afișare</label>
                                <input type="number" name="sort_order" class="form-control" 
                                       value="<?= htmlspecialchars($partner->sort_order ?? 0) ?>" min="0">
                                <small class="text-muted">Număr mai mic = afișat primul</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Perioada de valabilitate -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Perioada de Valabilitate</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Data început</label>
                                <input type="date" name="valid_from" class="form-control" 
                                       value="<?= htmlspecialchars($partner->valid_from ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Data sfârșit</label>
                                <input type="date" name="valid_until" class="form-control" 
                                       value="<?= htmlspecialchars($partner->valid_until ?? '') ?>">
                            </div>
                            <small class="text-muted">Lasă gol pentru a fi afișat permanent</small>
                        </div>
                    </div>
                    
                    <?php if ($isEdit && !empty($stats)): ?>
                    <!-- Statistici -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistici (30 zile)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <h4 class="text-info mb-0"><?= number_format($stats['total_views'] ?? 0) ?></h4>
                                    <small class="text-muted">Vizualizări</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-primary mb-0"><?= number_format($stats['total_clicks'] ?? 0) ?></h4>
                                    <small class="text-muted">Click-uri</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-0"><?= number_format($stats['unique_users'] ?? 0) ?></h4>
                                    <small class="text-muted">Utilizatori unici</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning mb-0"><?= number_format($stats['unique_companies'] ?? 0) ?></h4>
                                    <small class="text-muted">Companii</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Butoane -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i><?= $isEdit ? 'Salvează Modificările' : 'Adaugă Partener' ?>
                        </button>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partners" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Anulează
                        </a>
                    </div>
                </div>
            </div>
        </form>
</div>
