<?php
// modules/service/views/parts/view.php
$pageTitle = 'Detalii Piesa - ' . $part['name'];
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
                    <li class="breadcrumb-item active">Detalii</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($part['name']); ?></h1>
                <div>
                    <a href="/service/parts/edit/<?php echo $part['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Editeaza
                    </a>
                    <a href="/service/parts" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Inapoi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Part Details -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Informatii Generale</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Cod Piesa:</td>
                            <td><strong><?php echo htmlspecialchars($part['part_number']); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Categorie:</td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($part['category']); ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Producator:</td>
                            <td><?php echo htmlspecialchars($part['manufacturer'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Unitate:</td>
                            <td><?php echo htmlspecialchars($part['unit_of_measure']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Locatie:</td>
                            <td><?php echo htmlspecialchars($part['location'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Furnizor:</td>
                            <td><?php echo htmlspecialchars($part['supplier'] ?? '-'); ?></td>
                        </tr>
                        <?php if (!empty($part['supplier_part_number'])): ?>
                        <tr>
                            <td class="text-muted">Cod Furnizor:</td>
                            <td><?php echo htmlspecialchars($part['supplier_part_number']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if (!empty($part['description'])): ?>
                        <hr>
                        <h6 class="text-muted mb-2">Descriere:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($part['description'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($part['notes'])): ?>
                        <hr>
                        <h6 class="text-muted mb-2">Observatii:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($part['notes'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pricing -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Preturi</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Pret Achizitie:</span>
                        <strong><?php echo number_format($part['unit_price'], 2); ?> RON</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Pret Vanzare:</span>
                        <strong class="text-success"><?php echo number_format($part['sale_price'], 2); ?> RON</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Marja:</span>
                        <strong class="text-info">
                            <?php 
                            $margin = (($part['sale_price'] - $part['unit_price']) / $part['unit_price']) * 100;
                            echo number_format($margin, 1); 
                            ?>%
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Status -->
        <div class="col-lg-8">
            <!-- Stock Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-primary text-white">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Stoc Total</h6>
                            <h2 class="mb-0"><?php echo $part['quantity_in_stock']; ?></h2>
                            <small><?php echo htmlspecialchars($part['unit_of_measure']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-warning text-dark">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Utilizat</h6>
                            <h2 class="mb-0"><?php echo $part['total_used']; ?></h2>
                            <small><?php echo htmlspecialchars($part['unit_of_measure']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm <?php echo $part['available_quantity'] <= $part['minimum_quantity'] ? 'bg-danger text-white' : 'bg-success text-white'; ?>">
                        <div class="card-body text-center">
                            <h6 class="mb-1">Disponibil</h6>
                            <h2 class="mb-0"><?php echo $part['available_quantity']; ?></h2>
                            <small><?php echo htmlspecialchars($part['unit_of_measure']); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Management -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gestiune Stoc</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                        <i class="bi bi-plus-slash-minus me-1"></i> Ajusteaza Stoc
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <h6><i class="bi bi-info-circle me-2"></i>Stoc Minim</h6>
                                <p class="mb-2">Nivel de alerta: <strong><?php echo $part['minimum_quantity']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?></strong></p>
                                <?php if ($part['available_quantity'] <= $part['minimum_quantity']): ?>
                                    <span class="badge bg-danger">Necesita reaprovizionare!</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Stoc OK</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-secondary mb-0">
                                <h6><i class="bi bi-currency-dollar me-2"></i>Valoare Stoc</h6>
                                <p class="mb-0">
                                    Achizitie: <strong><?php echo number_format($part['quantity_in_stock'] * $part['unit_price'], 2); ?> RON</strong><br>
                                    Vanzare: <strong class="text-success"><?php echo number_format($part['quantity_in_stock'] * $part['sale_price'], 2); ?> RON</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage History -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Istoric Utilizare</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Comanda</th>
                                    <th>Vehicul</th>
                                    <th class="text-end">Cantitate</th>
                                    <th class="text-end">Pret</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usageHistory)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Nu exista istoric de utilizare
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usageHistory as $usage): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($usage['created_at'])); ?></td>
                                            <td>
                                                <a href="/service/work-orders/view/<?php echo $usage['work_order_id']; ?>">
                                                    <?php echo htmlspecialchars($usage['work_order_number']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($usage['registration_number']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($usage['vehicle_name']); ?></small>
                                            </td>
                                            <td class="text-end"><?php echo $usage['quantity']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?></td>
                                            <td class="text-end"><?php echo number_format($usage['total_price'], 2); ?> RON</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stock Transactions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Istoric Tranzactii Stoc</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Tip</th>
                                    <th class="text-end">Cantitate</th>
                                    <th>Observatii</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            Nu exista tranzactii
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($trans['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'in' => '<span class="badge bg-success">Intrare</span>',
                                                    'out' => '<span class="badge bg-danger">Iesire</span>',
                                                    'adjustment' => '<span class="badge bg-warning text-dark">Ajustare</span>',
                                                    'return' => '<span class="badge bg-info">Retur</span>'
                                                ];
                                                echo $badges[$trans['transaction_type']] ?? $trans['transaction_type'];
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo $trans['transaction_type'] === 'in' ? '+' : '-'; ?>
                                                <?php echo $trans['quantity']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($trans['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/service/parts/adjustStock/<?php echo $part['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Ajusteaza Stoc - <?php echo htmlspecialchars($part['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tip Operatie</label>
                        <select name="type" class="form-select" required>
                            <option value="in">Adauga in Stoc (intrare)</option>
                            <option value="out">Scade din Stoc (iesire)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantitate</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observatii</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Motiv, furnizor, etc." required></textarea>
                    </div>

                    <div class="alert alert-info mb-0">
                        <small>
                            <strong>Stoc actual:</strong> <?php echo $part['quantity_in_stock']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?><br>
                            <strong>Disponibil:</strong> <?php echo $part['available_quantity']; ?> <?php echo htmlspecialchars($part['unit_of_measure']); ?>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaza</button>
                    <button type="submit" class="btn btn-primary">Salveaza</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../../includes/header.php';
echo $content;
include __DIR__ . '/../../../../includes/footer.php';
?>
