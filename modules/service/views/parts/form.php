<?php
// modules/service/views/parts/form.php
$isEdit = isset($part) && $part !== null;
$pageTitle = $isEdit ? 'Editeaza Piesa' : 'Adauga Piesa';
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Acasa</a></li>
                    <li class="breadcrumb-item"><a href="/service">Atelier</a></li>
                    <li class="breadcrumb-item"><a href="/service/parts">Piese</a></li>
                    <li class="breadcrumb-item active"><?php echo $isEdit ? 'Editeaza' : 'Adauga'; ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Informatii Piesa</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $isEdit ? '/service/parts/edit/' . $part['id'] : '/service/parts/add'; ?>">
                        <div class="row g-3">
                            <!-- Part Number -->
                            <div class="col-md-6">
                                <label class="form-label">Cod Piesa <span class="text-danger">*</span></label>
                                <input type="text" name="part_number" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['part_number']) : ''; ?>" required>
                                <small class="text-muted">Cod unic pentru identificare</small>
                            </div>

                            <!-- Name -->
                            <div class="col-md-6">
                                <label class="form-label">Nume Piesa <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['name']) : ''; ?>" required>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label">Descriere</label>
                                <textarea name="description" class="form-control" rows="2"><?php echo $isEdit ? htmlspecialchars($part['description'] ?? '') : ''; ?></textarea>
                            </div>

                            <!-- Category -->
                            <div class="col-md-6">
                                <label class="form-label">Categorie <span class="text-danger">*</span></label>
                                <input type="text" name="category" class="form-control" list="categoryList"
                                       value="<?php echo $isEdit ? htmlspecialchars($part['category']) : ''; ?>" required>
                                <datalist id="categoryList">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                    <?php endforeach; ?>
                                    <option value="Lubrifianti">
                                    <option value="Filtre">
                                    <option value="Frane">
                                    <option value="Electrica">
                                    <option value="Accesorii">
                                    <option value="Caroserie">
                                </datalist>
                            </div>

                            <!-- Manufacturer -->
                            <div class="col-md-6">
                                <label class="form-label">Producator</label>
                                <input type="text" name="manufacturer" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['manufacturer'] ?? '') : ''; ?>">
                            </div>

                            <!-- Pricing -->
                            <div class="col-md-4">
                                <label class="form-label">Pret Achizitie (RON) <span class="text-danger">*</span></label>
                                <input type="number" name="unit_price" class="form-control" step="0.01" min="0"
                                       value="<?php echo $isEdit ? $part['unit_price'] : ''; ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Pret Vanzare (RON) <span class="text-danger">*</span></label>
                                <input type="number" name="sale_price" class="form-control" step="0.01" min="0"
                                       value="<?php echo $isEdit ? $part['sale_price'] : ''; ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Unitate Masura</label>
                                <select name="unit_of_measure" class="form-select">
                                    <option value="buc" <?php echo ($isEdit && $part['unit_of_measure'] === 'buc') ? 'selected' : ''; ?>>Bucata (buc)</option>
                                    <option value="set" <?php echo ($isEdit && $part['unit_of_measure'] === 'set') ? 'selected' : ''; ?>>Set</option>
                                    <option value="kg" <?php echo ($isEdit && $part['unit_of_measure'] === 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                    <option value="l" <?php echo ($isEdit && $part['unit_of_measure'] === 'l') ? 'selected' : ''; ?>>Litru (l)</option>
                                    <option value="m" <?php echo ($isEdit && $part['unit_of_measure'] === 'm') ? 'selected' : ''; ?>>Metru (m)</option>
                                </select>
                            </div>

                            <!-- Stock -->
                            <div class="col-md-6">
                                <label class="form-label">Cantitate in Stoc</label>
                                <input type="number" name="quantity_in_stock" class="form-control" min="0"
                                       value="<?php echo $isEdit ? $part['quantity_in_stock'] : '0'; ?>">
                                <?php if (!$isEdit): ?>
                                    <small class="text-muted">Se va genera automat o tranzactie de intrare</small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Stoc Minim</label>
                                <input type="number" name="minimum_quantity" class="form-control" min="0"
                                       value="<?php echo $isEdit ? $part['minimum_quantity'] : '0'; ?>">
                                <small class="text-muted">Nivel de alerta pentru reaprovizionare</small>
                            </div>

                            <!-- Location -->
                            <div class="col-md-6">
                                <label class="form-label">Locatie Depozitare</label>
                                <input type="text" name="location" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['location'] ?? '') : ''; ?>"
                                       placeholder="Ex: Depozit A1, Raft 3">
                            </div>

                            <!-- Supplier -->
                            <div class="col-md-6">
                                <label class="form-label">Furnizor</label>
                                <input type="text" name="supplier" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['supplier'] ?? '') : ''; ?>">
                            </div>

                            <!-- Supplier Part Number -->
                            <div class="col-md-6">
                                <label class="form-label">Cod Piesa Furnizor</label>
                                <input type="text" name="supplier_part_number" class="form-control" 
                                       value="<?php echo $isEdit ? htmlspecialchars($part['supplier_part_number'] ?? '') : ''; ?>">
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label class="form-label">Observatii</label>
                                <textarea name="notes" class="form-control" rows="3"><?php echo $isEdit ? htmlspecialchars($part['notes'] ?? '') : ''; ?></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="col-12">
                                <hr>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>
                                    <?php echo $isEdit ? 'Actualizeaza Piesa' : 'Adauga Piesa'; ?>
                                </button>
                                <a href="/service/parts" class="btn btn-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Anuleaza
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informatii Utile</h6>
                </div>
                <div class="card-body">
                    <h6>Campuri obligatorii:</h6>
                    <ul class="small mb-3">
                        <li>Cod piesa (unic)</li>
                        <li>Nume piesa</li>
                        <li>Categorie</li>
                        <li>Preturi (achizitie si vanzare)</li>
                    </ul>

                    <h6>Categorii sugerate:</h6>
                    <ul class="small mb-3">
                        <li>Lubrifianti</li>
                        <li>Filtre</li>
                        <li>Frane</li>
                        <li>Electrica</li>
                        <li>Caroserie</li>
                        <li>Accesorii</li>
                    </ul>

                    <h6>Stoc Minim:</h6>
                    <p class="small mb-0">
                        Setati o cantitate minima pentru a primi alerte cand stocul scade sub acest nivel.
                    </p>
                </div>
            </div>

            <?php if ($isEdit): ?>
                <!-- Quick Stats -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Informatii Rapide</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Stoc Total:</span>
                            <strong><?php echo $part['quantity_in_stock']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Folosit:</span>
                            <strong><?php echo $part['total_used']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Disponibil:</span>
                            <strong class="<?php echo $part['available_quantity'] <= $part['minimum_quantity'] ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $part['available_quantity']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?>
                            </strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Valoare Stoc:</span>
                            <strong><?php echo number_format($part['quantity_in_stock'] * $part['unit_price'], 2); ?> RON</strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../../includes/header.php';
echo $content;
include __DIR__ . '/../../../../includes/footer.php';
?>
