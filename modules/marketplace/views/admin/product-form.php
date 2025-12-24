<?php 
$pageTitle = ($product ? 'Editează' : 'Adaugă') . ' Produs';
require_once __DIR__ . '/../../../../includes/header.php'; 
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=admin-dashboard">Admin Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?action=admin-products">Produse</a></li>
            <li class="breadcrumb-item active"><?= $product ? 'Editează' : 'Adaugă' ?> Produs</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-<?= $product ? 'edit' : 'plus-circle' ?> me-3"></i>
            <?= $product ? 'Editează Produs' : 'Adaugă Produs Nou' ?>
        </h1>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-circle me-2"></i>Erori:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8 mb-4">
                <!-- Basic Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informații de Bază</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nume Produs <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="name" 
                                   value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categorie <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Selectează Categoria...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                            <?= ($product && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descriere <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            <small class="text-muted">Descriere detaliată a produsului</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="sku" 
                                       value="<?= htmlspecialchars($product['sku'] ?? '') ?>" required>
                                <small class="text-muted">Cod unic de identificare</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preț (RON) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="price" 
                                       value="<?= $product['price'] ?? '' ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Specifications -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Specificații Tehnice (Opțional)</h5>
                    </div>
                    <div class="card-body">
                        <div id="specifications-container">
                            <?php 
                            $specs = $product['specifications'] ?? [];
                            if (empty($specs)) {
                                $specs = ['', '']; // Show 2 empty rows by default
                            }
                            $specIndex = 0;
                            foreach ($specs as $key => $value):
                            ?>
                                <div class="row mb-2 spec-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" 
                                               name="spec_keys[]" 
                                               value="<?= htmlspecialchars($key) ?>" 
                                               placeholder="Ex: Perioada">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" 
                                               name="spec_values[]" 
                                               value="<?= htmlspecialchars($value) ?>" 
                                               placeholder="Ex: 12 luni">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                                onclick="removeSpecRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php 
                            $specIndex++;
                            endforeach; 
                            ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSpecRow()">
                            <i class="fas fa-plus me-1"></i>Adaugă Specificație
                        </button>
                    </div>
                </div>
                
                <!-- Images -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-images me-2"></i>Imagini Produs</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($product && $product['image_main']): ?>
                            <div class="mb-3">
                                <label class="form-label">Imagine Actuală:</label><br>
                                <img src="<?= BASE_URL . htmlspecialchars($product['image_main']) ?>" 
                                     class="img-thumbnail" style="max-width: 300px;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Imagine Principală<?= !$product ? ' <span class="text-danger">*</span>' : '' ?></label>
                            <input type="file" class="form-control" name="image_main" accept="image/*" 
                                   <?= !$product ? 'required' : '' ?>>
                            <small class="text-muted">Format: JPG, PNG (max 5MB)</small>
                        </div>
                        
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Sfat:</strong> Folosește imagini de calitate (min. 800x800px) pentru prezentare optimă.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Publish Settings -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Setări Publicare</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" 
                                   id="is_active" value="1" 
                                   <?= (!$product || $product['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <strong>Produs Activ</strong><br>
                                <small class="text-muted">Vizibil în marketplace</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_featured" 
                                   id="is_featured" value="1" 
                                   <?= ($product && $product['is_featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">
                                <strong>Produs Recomandat</strong><br>
                                <small class="text-muted">Afișat în evidență</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- SEO -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-search me-2"></i>SEO</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Slug URL</label>
                            <input type="text" class="form-control form-control-sm" name="slug" 
                                   value="<?= htmlspecialchars($product['slug'] ?? '') ?>"
                                   placeholder="Generat automat din nume">
                            <small class="text-muted">Lasă gol pentru generare automată</small>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>
                                <?= $product ? 'Actualizează Produs' : 'Salvează Produs' ?>
                            </button>
                            <?php if ($product): ?>
                                <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $product['slug'] ?>" 
                                   class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-eye me-2"></i>Previzualizează
                                </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-products" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Anulează
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addSpecRow() {
    const container = document.getElementById('specifications-container');
    const row = document.createElement('div');
    row.className = 'row mb-2 spec-row';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="spec_keys[]" placeholder="Ex: Perioada">
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="spec_values[]" placeholder="Ex: 12 luni">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeSpecRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function removeSpecRow(button) {
    button.closest('.spec-row').remove();
}
</script>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>
