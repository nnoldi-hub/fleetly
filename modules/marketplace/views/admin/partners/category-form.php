<?php
/**
 * Admin - Formular Categorie (Create/Edit)
 */
$isEdit = !empty($category->id);
$pageTitle = $isEdit ? 'Editare Categorie' : 'Adaugă Categorie Nouă';
include __DIR__ . '/../../../../../includes/header.php';
include __DIR__ . '/../../../../../includes/sidebar.php';

// Iconițe disponibile
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
    'fa-industry' => 'Industrie',
    'fa-box' => 'Cutie/Pachet',
    'fa-file-invoice-dollar' => 'Factură',
    'fa-battery-full' => 'Baterie',
    'fa-oil-can' => 'Ulei',
    'fa-tachometer-alt' => 'Vitezometru'
];

// Culori disponibile
$availableColors = [
    '#007bff' => 'Albastru',
    '#28a745' => 'Verde',
    '#17a2b8' => 'Cyan',
    '#ffc107' => 'Galben',
    '#fd7e14' => 'Portocaliu',
    '#dc3545' => 'Roșu',
    '#6f42c1' => 'Violet',
    '#e83e8c' => 'Roz',
    '#20c997' => 'Turcoaz',
    '#6c757d' => 'Gri'
];
?>

<main class="main-content" style="margin-left: 220px; padding: 20px;">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus-circle' ?> text-primary me-2"></i>
                    <?= $pageTitle ?>
                </h1>
            </div>
            <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-categories" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Înapoi la categorii
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="<?= BASE_URL ?>modules/marketplace/?action=<?= $isEdit ? 'admin-partner-category-update' : 'admin-partner-category-store' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $category->id ?>">
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Detalii Categorie</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nume Categorie <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg" 
                                       value="<?= htmlspecialchars($category->name ?? '') ?>" required
                                       placeholder="Ex: Piese Auto, Cauciucuri, Asigurări...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descriere</label>
                                <textarea name="description" class="form-control" rows="3" 
                                          placeholder="O scurtă descriere a categoriei..."><?= htmlspecialchars($category->description ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Iconiță</label>
                                    <select name="icon" class="form-select" id="iconSelect">
                                        <?php foreach ($availableIcons as $icon => $label): ?>
                                            <option value="<?= $icon ?>" <?= ($category->icon ?? 'fa-handshake') == $icon ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="mt-2">
                                        <span class="text-muted">Preview: </span>
                                        <i class="fas <?= htmlspecialchars($category->icon ?? 'fa-handshake') ?> fa-2x" id="iconPreview" 
                                           style="color: <?= htmlspecialchars($category->color ?? '#007bff') ?>"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Culoare</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($availableColors as $color => $label): ?>
                                            <label class="color-option">
                                                <input type="radio" name="color" value="<?= $color ?>" 
                                                       <?= ($category->color ?? '#007bff') == $color ? 'checked' : '' ?>
                                                       style="display: none;">
                                                <span class="color-swatch" style="background-color: <?= $color ?>" 
                                                      title="<?= $label ?>"></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ordine afișare</label>
                                    <input type="number" name="sort_order" class="form-control" 
                                           value="<?= htmlspecialchars($category->sort_order ?? 0) ?>" min="0">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                               <?= ($category->is_active ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Categorie activă</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i><?= $isEdit ? 'Salvează' : 'Adaugă Categoria' ?>
                        </button>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=admin-partner-categories" class="btn btn-outline-secondary btn-lg">
                            Anulează
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Preview Card -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Preview</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                             id="previewCircle"
                             style="width: 80px; height: 80px; background-color: <?= htmlspecialchars($category->color ?? '#007bff') ?>20;">
                            <i class="fas <?= htmlspecialchars($category->icon ?? 'fa-handshake') ?> fa-3x" 
                               id="previewIcon"
                               style="color: <?= htmlspecialchars($category->color ?? '#007bff') ?>"></i>
                        </div>
                        <h4 id="previewName"><?= htmlspecialchars($category->name ?? 'Nume Categorie') ?></h4>
                        <p class="text-muted" id="previewDesc"><?= htmlspecialchars($category->description ?? 'Descrierea categoriei va apărea aici') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.color-option .color-swatch {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.2s;
}
.color-option input:checked + .color-swatch {
    border-color: #333;
    transform: scale(1.1);
}
.color-option:hover .color-swatch {
    transform: scale(1.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iconSelect = document.getElementById('iconSelect');
    const colorInputs = document.querySelectorAll('input[name="color"]');
    const nameInput = document.querySelector('input[name="name"]');
    const descInput = document.querySelector('textarea[name="description"]');
    
    function updatePreview() {
        const icon = iconSelect.value;
        const color = document.querySelector('input[name="color"]:checked')?.value || '#007bff';
        const name = nameInput.value || 'Nume Categorie';
        const desc = descInput.value || 'Descrierea categoriei va apărea aici';
        
        document.getElementById('previewIcon').className = 'fas ' + icon + ' fa-3x';
        document.getElementById('previewIcon').style.color = color;
        document.getElementById('previewCircle').style.backgroundColor = color + '20';
        document.getElementById('iconPreview').className = 'fas ' + icon + ' fa-2x';
        document.getElementById('iconPreview').style.color = color;
        document.getElementById('previewName').textContent = name;
        document.getElementById('previewDesc').textContent = desc;
    }
    
    iconSelect.addEventListener('change', updatePreview);
    colorInputs.forEach(input => input.addEventListener('change', updatePreview));
    nameInput.addEventListener('input', updatePreview);
    descInput.addEventListener('input', updatePreview);
});
</script>

<?php include __DIR__ . '/../../../../../includes/footer.php'; ?>
