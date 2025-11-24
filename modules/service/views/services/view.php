<?php
/**
 * View: Detalii Service
 * Afișare detalii despre un service (extern sau intern)
 */

$service = $service ?? [];
$stats = $stats ?? [];
$recentHistory = $recentHistory ?? [];
$canEdit = $canEdit ?? false;
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-<?= $service['service_type'] === 'internal' ? 'tools' : 'handshake' ?>"></i>
                <?= htmlspecialchars($service['name']) ?>
                <?php if ($service['service_type'] === 'internal'): ?>
                    <span class="badge bg-success">Intern</span>
                <?php else: ?>
                    <span class="badge bg-primary">Extern</span>
                <?php endif; ?>
                <?php if (!$service['is_active']): ?>
                    <span class="badge bg-secondary">Inactiv</span>
                <?php endif; ?>
            </h2>
            <?php if ($service['rating'] > 0): ?>
                <p class="mb-0">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="fas fa-star <?= $i < $service['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                    <?php endfor; ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= ROUTE_BASE ?>/service/services" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Înapoi
            </a>
            <?php if ($canEdit): ?>
                <a href="<?= ROUTE_BASE ?>/service/services/edit?id=<?= $service['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editează
                </a>
                <?php if ($service['service_type'] === 'internal'): ?>
                    <a href="<?= ROUTE_BASE ?>/service/workshop" class="btn btn-success">
                        <i class="fas fa-th-large"></i> Dashboard Atelier
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Coloana stâng -->
        <div class="col-md-8">
            <!-- Informații de Contact -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-address-card"></i> Informații de Contact</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php if ($service['phone']): ?>
                                <p>
                                    <i class="fas fa-phone text-primary"></i>
                                    <strong>Telefon:</strong>
                                    <a href="tel:<?= htmlspecialchars($service['phone']) ?>">
                                        <?= htmlspecialchars($service['phone']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if ($service['email']): ?>
                                <p>
                                    <i class="fas fa-envelope text-primary"></i>
                                    <strong>Email:</strong>
                                    <a href="mailto:<?= htmlspecialchars($service['email']) ?>">
                                        <?= htmlspecialchars($service['email']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if ($service['website']): ?>
                                <p>
                                    <i class="fas fa-globe text-primary"></i>
                                    <strong>Website:</strong>
                                    <a href="<?= htmlspecialchars($service['website']) ?>" target="_blank">
                                        <?= htmlspecialchars($service['website']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($service['contact_person']): ?>
                                <p>
                                    <i class="fas fa-user text-primary"></i>
                                    <strong>Persoană Contact:</strong>
                                    <?= htmlspecialchars($service['contact_person']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($service['contact_phone']): ?>
                                <p>
                                    <i class="fas fa-mobile-alt text-primary"></i>
                                    <strong>Telefon Contact:</strong>
                                    <a href="tel:<?= htmlspecialchars($service['contact_phone']) ?>">
                                        <?= htmlspecialchars($service['contact_phone']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($service['address'] || $service['city']): ?>
                        <hr>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            <strong>Adresă:</strong><br>
                            <?= htmlspecialchars($service['address'] ?? '') ?>
                            <?php if ($service['city'] || $service['state']): ?>
                                <br><?= htmlspecialchars(trim(($service['city'] ?? '') . ', ' . ($service['state'] ?? ''), ', ')) ?>
                            <?php endif; ?>
                            <?php if ($service['postal_code']): ?>
                                <?= htmlspecialchars($service['postal_code']) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Descriere și Servicii -->
            <?php if ($service['description'] || $service['specialties']): ?>
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Despre Service</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($service['description']): ?>
                            <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                        <?php endif; ?>
                        <?php if ($service['specialties']): ?>
                            <p class="mb-0">
                                <strong><i class="fas fa-star"></i> Specialități:</strong>
                                <?php 
                                $specs = explode(',', $service['specialties']);
                                foreach ($specs as $spec): 
                                ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars(trim($spec)) ?></span>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Configurare Atelier (doar pentru interne) -->
            <?php if ($service['service_type'] === 'internal'): ?>
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Configurare Atelier</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p>
                                    <strong>Capacitate:</strong> 
                                    <?= $service['capacity'] ?? '-' ?> posturi
                                </p>
                                <p>
                                    <strong>Tarif Orar Manoperă:</strong> 
                                    <?= number_format($service['hourly_labor_rate'] ?? 0, 0) ?> RON/h
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if ($service['working_hours']): ?>
                                    <p>
                                        <strong>Program Lucru:</strong><br>
                                        <?= htmlspecialchars($service['working_hours']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($service['equipment']): ?>
                            <hr>
                            <p class="mb-0">
                                <strong>Echipamente:</strong><br>
                                <?= nl2br(htmlspecialchars($service['equipment'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Certificate (doar pentru externe) -->
            <?php if ($service['service_type'] === 'external' && $service['certifications']): ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-certificate"></i> Certificate și Autorizații</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($service['certifications'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Istoric Recent -->
            <?php if (!empty($recentHistory)): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Istoric Recent (ultimele 10 intervenții)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Vehicul</th>
                                        <th>Tip Serviciu</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentHistory as $h): ?>
                                        <tr>
                                            <td><small><?= date('d.m.Y', strtotime($h['service_date'])) ?></small></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($h['plate_number']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($h['service_type']) ?></td>
                                            <td><?= number_format($h['cost'], 0) ?> RON</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Observații -->
            <?php if ($service['notes']): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Observații</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($service['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar dreapta - Statistici -->
        <div class="col-md-4">
            <!-- Statistici Generale -->
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistici</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h3 class="text-primary mb-0"><?= $stats['total_services'] ?? 0 ?></h3>
                        <small class="text-muted">Intervenții Totale</small>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Anul acesta:</span>
                        <strong><?= $stats['services_this_year'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Luna aceasta:</span>
                        <strong><?= $stats['services_this_month'] ?? 0 ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Cost Total:</span>
                        <strong class="text-success"><?= number_format($stats['total_cost'] ?? 0, 0) ?> RON</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cost Mediu:</span>
                        <strong><?= number_format($stats['avg_cost'] ?? 0, 0) ?> RON</strong>
                    </div>
                </div>
            </div>

            <!-- Info Rapid -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info"></i> Info Rapid</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>ID Service:</strong> #<?= $service['id'] ?>
                    </p>
                    <p>
                        <strong>Creat la:</strong><br>
                        <small><?= date('d.m.Y H:i', strtotime($service['created_at'])) ?></small>
                    </p>
                    <?php if ($service['updated_at'] && $service['updated_at'] !== $service['created_at']): ?>
                        <p>
                            <strong>Actualizat la:</strong><br>
                            <small><?= date('d.m.Y H:i', strtotime($service['updated_at'])) ?></small>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <strong>Status:</strong>
                        <span class="badge bg-<?= $service['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $service['is_active'] ? 'Activ' : 'Inactiv' ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Acțiuni Rapide -->
            <?php if ($canEdit): ?>
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Acțiuni Rapide</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= ROUTE_BASE ?>/service/services/edit?id=<?= $service['id'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editează Date
                            </a>
                            <?php if ($service['service_type'] === 'internal'): ?>
                                <a href="<?= ROUTE_BASE ?>/service/workshop" 
                                   class="btn btn-success">
                                    <i class="fas fa-tools"></i> Deschide Atelier
                                </a>
                            <?php else: ?>
                                <a href="<?= ROUTE_BASE ?>/service/appointments/add?service_id=<?= $service['id'] ?>" 
                                   class="btn btn-info">
                                    <i class="fas fa-calendar-plus"></i> Programare Nouă
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-<?= $service['is_active'] ? 'warning' : 'success' ?>" 
                                    onclick="toggleActive(<?= $service['id'] ?>, <?= $service['is_active'] ? 0 : 1 ?>)">
                                <i class="fas fa-power-off"></i> 
                                <?= $service['is_active'] ? 'Dezactivează' : 'Activează' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleActive(serviceId, newStatus) {
    const action = newStatus ? 'activat' : 'dezactivat';
    if (!confirm(`Sigur doriți să fie ${action} acest service?`)) return;
    
    $.post('<?= ROUTE_BASE ?>/service/services/activate', {
        service_id: serviceId,
        is_active: newStatus
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Eroare: ' + response.message);
        }
    }, 'json');
}
</script>
