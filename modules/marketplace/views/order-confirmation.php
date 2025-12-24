<?php 
$pageTitle = 'Comandă Confirmată';
require_once __DIR__ . '/../../../includes/header.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Card -->
            <div class="card shadow-lg border-0">
                <div class="card-body text-center py-5">
                    <!-- Success Icon -->
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 100px;"></i>
                    </div>
                    
                    <h1 class="text-success mb-3">Comandă Plasată cu Succes!</h1>
                    <p class="lead text-muted mb-4">
                        Vă mulțumim pentru comandă! Confirmarea a fost trimisă pe email.
                    </p>
                    
                    <!-- Order Details -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row text-start">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Număr Comandă:</small>
                                    <h5 class="mb-0 text-primary"><?= htmlspecialchars($order['order_number']) ?></h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Data Comandă:</small>
                                    <h6 class="mb-0"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Total Comandă:</small>
                                    <h5 class="mb-0 text-success"><?= number_format($order['total_amount'], 2) ?> RON</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Status:</small>
                                    <h6 class="mb-0">
                                        <span class="badge bg-warning">În Procesare</span>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next Steps -->
                    <div class="alert alert-info text-start mb-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Ce urmează?</h6>
                        <ul class="mb-0 ps-4">
                            <li>Veți primi un email de confirmare la adresa: <strong><?= htmlspecialchars($order['contact_email']) ?></strong></li>
                            <li>Echipa noastră va verifica comanda în următoarele 24 de ore</li>
                            <li>Veți fi notificat când comanda este pregătită pentru livrare</li>
                            <li>Puteți urmări statusul comenzii în secțiunea "Comenzile Mele"</li>
                        </ul>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-3 d-md-block">
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=order&id=<?= $order['id'] ?>" 
                           class="btn btn-primary btn-lg me-md-2">
                            <i class="fas fa-file-invoice me-2"></i>Vezi Detalii Comandă
                        </a>
                        <a href="<?= BASE_URL ?>modules/marketplace/?action=orders" 
                           class="btn btn-outline-primary btn-lg me-md-2">
                            <i class="fas fa-list me-2"></i>Toate Comenzile
                        </a>
                        <a href="<?= BASE_URL ?>modules/marketplace/" 
                           class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-store me-2"></i>Înapoi la Marketplace
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Support Info -->
            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-question-circle me-2"></i>
                    Ai întrebări despre comandă? 
                    <a href="mailto:support@fleetly.ro">Contactează-ne</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
