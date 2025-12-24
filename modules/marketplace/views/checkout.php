<?php 
$pageTitle = 'Finalizare Comandă';
?>

<link href="<?= BASE_URL ?>assets/css/marketplace.css" rel="stylesheet">

<div class="container py-4">
    <h1 class="mb-4"><i class="fas fa-clipboard-check me-3"></i>Finalizare Comandă</h1>
    
    <?php if (!empty($validation->errors)): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-circle me-2"></i>Erori la Validare:</h5>
            <ul class="mb-0">
                <?php foreach ($validation->errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Order Form -->
        <div class="col-lg-7 mb-4">
            <form method="POST" action="<?= BASE_URL ?>modules/marketplace/?action=checkout-submit" id="checkoutForm">
                <!-- Delivery Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Detalii Livrare</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Persoană de Contact <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_person" 
                                       value="<?= htmlspecialchars($defaults['contact_person'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefon <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="contact_phone" 
                                       value="<?= htmlspecialchars($defaults['contact_phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="contact_email" 
                                       value="<?= htmlspecialchars($defaults['contact_email'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresă Livrare <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="delivery_address" 
                                       placeholder="Strada, număr" 
                                       value="<?= htmlspecialchars($defaults['delivery_address'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Oraș <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="delivery_city" 
                                       value="<?= htmlspecialchars($defaults['delivery_city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Județ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="delivery_county" 
                                       value="<?= htmlspecialchars($defaults['delivery_county'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cod Poștal</label>
                                <input type="text" class="form-control" name="delivery_postal" 
                                       value="<?= htmlspecialchars($defaults['delivery_postal'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Metodă de Plată</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="payment_invoice" value="invoice" checked>
                            <label class="form-check-label" for="payment_invoice">
                                <strong>Factură (Plata la 30 zile)</strong><br>
                                <small class="text-muted">Veți primi factura prin email. Plata se efectuează în termen de 30 zile.</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="payment_card" value="card">
                            <label class="form-check-label" for="payment_card">
                                <strong>Card Bancar</strong><br>
                                <small class="text-muted">Plata securizată cu card bancar (disponibil curând).</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="payment_transfer" value="transfer">
                            <label class="form-check-label" for="payment_transfer">
                                <strong>Transfer Bancar</strong><br>
                                <small class="text-muted">Veți primi detaliile bancare pentru transfer manual.</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-comment me-2"></i>Observații (Opțional)</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Adaugă orice observații sau instrucțiuni speciale pentru comandă..."><?= htmlspecialchars($defaults['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <!-- Submit -->
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>modules/marketplace/?action=cart" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Înapoi la Coș
                    </a>
                    <button type="submit" class="btn btn-success btn-lg flex-grow-1">
                        <i class="fas fa-check-circle me-2"></i>Plasează Comanda
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-5">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Sumar Comandă</h5>
                </div>
                <div class="card-body">
                    <!-- Items List -->
                    <h6 class="mb-3">Produse (<?= $summary['item_count'] ?>)</h6>
                    <div class="list-group list-group-flush mb-3">
                        <?php foreach ($items as $item): ?>
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 small"><?= htmlspecialchars($item['product_name']) ?></h6>
                                        <small class="text-muted">
                                            <?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?> RON
                                        </small>
                                    </div>
                                    <span class="fw-bold small"><?= number_format($item['item_total'], 2) ?> RON</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Totals -->
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end"><?= number_format($summary['subtotal'], 2) ?> RON</td>
                        </tr>
                        <tr>
                            <td>TVA (19%):</td>
                            <td class="text-end"><?= number_format($summary['tax'], 2) ?> RON</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Total:</strong></td>
                            <td class="text-end">
                                <strong class="text-success fs-5"><?= number_format($summary['total'], 2) ?> RON</strong>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Vei primi confirmarea comenzii prin email.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Disable card payment temporarily
document.getElementById('payment_card').disabled = true;
document.getElementById('payment_card').nextElementSibling.style.opacity = '0.5';
</script>
