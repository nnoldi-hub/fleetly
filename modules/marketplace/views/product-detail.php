<?php 
$pageTitle = $product['name'] ?? 'Produs';

// Categorii cu preț variabil (solicită ofertă)
$quoteCategories = ['asigurari', 'roviniete'];
$isQuotePrice = in_array($product['category_slug'] ?? '', $quoteCategories);

// Categorii care necesită selectarea unui vehicul
$vehicleCategories = ['asigurari', 'roviniete', 'anvelope', 'piese-auto'];
$requiresVehicle = in_array($product['category_slug'] ?? '', $vehicleCategories);
?>

<link href="<?= BASE_URL ?>assets/css/marketplace.css" rel="stylesheet">

<style>
.product-detail-image {
    max-height: 400px;
    object-fit: contain;
    background: #f8f9fa;
    border-radius: 10px;
}
.product-detail-placeholder {
    height: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 100px;
    border-radius: 10px;
}
.spec-table th {
    width: 30%;
    background-color: #f8f9fa;
}
.price-large {
    font-size: 2.5rem;
    font-weight: bold;
    color: #28a745;
}
.related-product-card {
    transition: transform 0.2s;
}
.related-product-card:hover {
    transform: scale(1.05);
}
</style>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/">Marketplace</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/marketplace/?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($product['image_main']): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($product['image_main']) ?>" 
                             class="img-fluid product-detail-image w-100" 
                             alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="product-detail-placeholder">
                            <i class="fas fa-box"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['is_featured']): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-star me-2"></i><strong>Produs Recomandat</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h1 class="h2 mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                    <p class="text-muted mb-3">SKU: <?= htmlspecialchars($product['sku']) ?></p>
                    
                    <?php if ($isQuotePrice): ?>
                        <!-- Preț variabil - Solicită ofertă -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Prețul depinde de caracteristicile vehiculului.</strong><br>
                            Completează formularul de mai jos pentru a primi o ofertă personalizată.
                        </div>
                    <?php else: ?>
                        <div class="price-large mb-4">
                            <?= number_format($product['price'], 2) ?> <?= $product['currency'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h5 class="mb-3">Descriere</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    
                    <hr>
                    
                    <?php if ($isQuotePrice): ?>
                        <!-- Formular Solicitare Ofertă -->
                        <h5 class="mb-3"><i class="fas fa-file-alt me-2"></i>Solicită Ofertă</h5>
                        
                        <form id="quoteRequestForm" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                            
                            <!-- Selectare tip vehicul -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tipul vehiculului:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="vehicle_type" id="vehicleFleet" value="fleet" checked>
                                    <label class="form-check-label" for="vehicleFleet">
                                        <i class="fas fa-car me-1"></i> Vehicul din flotă
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="vehicle_type" id="vehicleNew" value="new">
                                    <label class="form-check-label" for="vehicleNew">
                                        <i class="fas fa-plus-circle me-1"></i> Vehicul nou (încarcă documente)
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Vehicul din flotă -->
                            <div id="fleetVehicleSection" class="mb-4">
                                <label for="fleet_vehicle_id" class="form-label">Selectează vehiculul:</label>
                                <select class="form-select form-select-lg" id="fleet_vehicle_id" name="fleet_vehicle_id">
                                    <option value="">-- Alege un vehicul --</option>
                                    <?php if (!empty($vehicles)): ?>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>" 
                                                data-plate="<?= htmlspecialchars($vehicle['registration_number']) ?>"
                                                data-brand="<?= htmlspecialchars($vehicle['brand']) ?>"
                                                data-model="<?= htmlspecialchars($vehicle['model']) ?>"
                                                data-vin="<?= htmlspecialchars($vehicle['vin'] ?? '') ?>">
                                            <?= htmlspecialchars($vehicle['registration_number']) ?> - 
                                            <?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Nu ai vehicule în flotă</option>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($vehicles)): ?>
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Nu ai vehicule înregistrate. Selectează "Vehicul nou" pentru a încărca documentele.</small>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Vehicul nou - încărcare documente -->
                            <div id="newVehicleSection" class="mb-4" style="display: none;">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fas fa-upload me-2"></i>Încarcă documentele vehiculului</h6>
                                        
                                        <div class="mb-3">
                                            <label for="talon_file" class="form-label">
                                                Talon vehicul <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" class="form-control" id="talon_file" name="talon_file" 
                                                   accept=".jpg,.jpeg,.png,.pdf">
                                            <small class="text-muted">Format: JPG, PNG sau PDF. Max 5MB</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="owner_doc_file" class="form-label">
                                                Act proprietar (CI firmă / Buletin persoană fizică) <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" class="form-control" id="owner_doc_file" name="owner_doc_file" 
                                                   accept=".jpg,.jpeg,.png,.pdf">
                                            <small class="text-muted">Format: JPG, PNG sau PDF. Max 5MB</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_vehicle_plate" class="form-label">
                                                Număr înmatriculare <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="new_vehicle_plate" name="new_vehicle_plate" 
                                                   placeholder="Ex: B 123 ABC">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="new_vehicle_brand" class="form-label">Marca</label>
                                                <input type="text" class="form-control" id="new_vehicle_brand" name="new_vehicle_brand" 
                                                       placeholder="Ex: Volkswagen">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="new_vehicle_model" class="form-label">Model</label>
                                                <input type="text" class="form-control" id="new_vehicle_model" name="new_vehicle_model" 
                                                       placeholder="Ex: Passat">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_vehicle_vin" class="form-label">Serie șasiu (VIN)</label>
                                            <input type="text" class="form-control" id="new_vehicle_vin" name="new_vehicle_vin" 
                                                   placeholder="17 caractere" maxlength="17">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informații contact -->
                            <div class="mb-4">
                                <label for="contact_phone" class="form-label">
                                    Telefon contact <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       placeholder="07XX XXX XXX" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="additional_notes" class="form-label">Note adiționale</label>
                                <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3" 
                                          placeholder="Alte informații relevante pentru ofertă..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-paper-plane me-2"></i>Trimite Cerere Ofertă
                            </button>
                        </form>
                        
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const vehicleTypeRadios = document.querySelectorAll('input[name="vehicle_type"]');
                            const fleetSection = document.getElementById('fleetVehicleSection');
                            const newSection = document.getElementById('newVehicleSection');
                            
                            vehicleTypeRadios.forEach(radio => {
                                radio.addEventListener('change', function() {
                                    if (this.value === 'fleet') {
                                        fleetSection.style.display = 'block';
                                        newSection.style.display = 'none';
                                    } else {
                                        fleetSection.style.display = 'none';
                                        newSection.style.display = 'block';
                                    }
                                });
                            });
                            
                            // Form submission
                            document.getElementById('quoteRequestForm').addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const formData = new FormData(this);
                                const vehicleType = formData.get('vehicle_type');
                                
                                // Validare
                                if (vehicleType === 'fleet' && !formData.get('fleet_vehicle_id')) {
                                    alert('Te rugăm să selectezi un vehicul din flotă.');
                                    return;
                                }
                                
                                if (vehicleType === 'new') {
                                    if (!formData.get('talon_file').name) {
                                        alert('Te rugăm să încarci talonul vehiculului.');
                                        return;
                                    }
                                    if (!formData.get('owner_doc_file').name) {
                                        alert('Te rugăm să încarci actul proprietarului.');
                                        return;
                                    }
                                    if (!formData.get('new_vehicle_plate')) {
                                        alert('Te rugăm să introduci numărul de înmatriculare.');
                                        return;
                                    }
                                }
                                
                                if (!formData.get('contact_phone')) {
                                    alert('Te rugăm să introduci numărul de telefon.');
                                    return;
                                }
                                
                                // TODO: Trimite cererea la server
                                alert('Cererea de ofertă a fost trimisă cu succes! Vei fi contactat în curând.');
                                // window.location.href = '<?= BASE_URL ?>modules/marketplace/';
                            });
                        });
                        </script>
                    <?php else: ?>
                        <!-- Add to Cart Form (produse cu preț fix) -->
                        <form id="addToCartForm" class="mb-3">
                            <?php if ($requiresVehicle): ?>
                                <!-- Selectare vehicul pentru piese/anvelope -->
                                <div class="mb-3">
                                    <label for="cart_vehicle_id" class="form-label fw-bold">
                                        <i class="fas fa-car me-1"></i> Selectează vehiculul:
                                    </label>
                                    <select class="form-select" id="cart_vehicle_id" name="vehicle_id" required>
                                        <option value="">-- Alege un vehicul --</option>
                                        <?php if (!empty($vehicles)): ?>
                                            <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?= $vehicle['id'] ?>">
                                                <?= htmlspecialchars($vehicle['registration_number']) ?> - 
                                                <?= htmlspecialchars($vehicle['brand']) ?> <?= htmlspecialchars($vehicle['model']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>Nu ai vehicule în flotă</option>
                                        <?php endif; ?>
                                    </select>
                                    <small class="text-muted">Selectează vehiculul pentru care comanzi acest produs</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row g-3 align-items-end">
                                <div class="col-auto">
                                    <label for="quantity" class="form-label">Cantitate:</label>
                                    <input type="number" class="form-control form-control-lg" 
                                           id="quantity" name="quantity" value="1" min="1" max="100" style="width: 100px;">
                                </div>
                                <div class="col">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-cart-plus me-2"></i>Adaugă în Coș
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>modules/marketplace/" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Înapoi la Catalog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Specifications -->
    <?php if (!empty($product['specifications'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Specificații Tehnice</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered spec-table mb-0">
                            <?php foreach ($product['specifications'] as $key => $value): ?>
                                <tr>
                                    <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?></th>
                                    <td><?= htmlspecialchars($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-th-large me-2"></i>Produse Similare</h4>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php foreach ($relatedProducts as $related): ?>
                <div class="col">
                    <div class="card related-product-card shadow-sm h-100">
                        <?php if ($related['image_main']): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($related['image_main']) ?>" 
                                 class="card-img-top" style="height: 150px; object-fit: cover;" 
                                 alt="<?= htmlspecialchars($related['name']) ?>">
                        <?php else: ?>
                            <div style="height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-box fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($related['name']) ?></h6>
                            <p class="text-success fw-bold mb-2"><?= number_format($related['price'], 2) ?> RON</p>
                            <a href="<?= BASE_URL ?>modules/marketplace/?action=product&slug=<?= $related['slug'] ?>" 
                               class="btn btn-sm btn-outline-primary w-100">
                                Vezi Detalii
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const quantity = document.getElementById('quantity').value;
    const button = this.querySelector('button[type="submit"]');
    const originalHtml = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adăugare...';
    
    fetch('<?= BASE_URL ?>modules/marketplace/?action=cart-add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=<?= $product['id'] ?>&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>Produs adăugat în coș! 
                <a href="<?= BASE_URL ?>modules/marketplace/?action=cart" class="alert-link">Vezi coșul</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
            
            button.innerHTML = '<i class="fas fa-check me-2"></i>Adăugat!';
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-success');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success');
                button.disabled = false;
            }, 2000);
        } else {
            alert('Eroare: ' + data.message);
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la adăugare în coș');
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
});
</script>
