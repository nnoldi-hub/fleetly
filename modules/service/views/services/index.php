<?php
/**
 * View: Lista Service-uri
 * Afișează toate service-urile (interne și externe) pentru tenant
 */

$currentType = $currentType ?? 'all';
$searchTerm = $searchTerm ?? '';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-wrench"></i> Service-uri</h2>
            <p class="text-muted">Gestionare service-uri partenere și atelier intern</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($userRole === 'admin'): ?>
                <?php if (!$hasInternal): ?>
                    <a href="<?= ROUTE_BASE ?>/service/services/internal-setup" class="btn btn-success me-2">
                        <i class="fas fa-plus-circle"></i> Activează Service Intern
                    </a>
                <?php endif; ?>
                <a href="<?= ROUTE_BASE ?>/service/services/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Adaugă Service
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mesaje -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filtre și căutare -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= ROUTE_BASE ?>/service/services" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tip Service</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $currentType === 'all' ? 'selected' : '' ?>>Toate</option>
                        <option value="internal" <?= $currentType === 'internal' ? 'selected' : '' ?>>Service Intern</option>
                        <option value="external" <?= $currentType === 'external' ? 'selected' : '' ?>>Parteneri Externi</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Căutare</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Caută după nume, adresă, persoană contact..." 
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Caută
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista service-uri -->
    <?php if (empty($services)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <?php if (!empty($searchTerm)): ?>
                Nu au fost găsite service-uri care să corespundă criteriilor de căutare.
            <?php else: ?>
                Nu există service-uri înregistrate. 
                <?php if ($userRole === 'admin'): ?>
                    <a href="<?= ROUTE_BASE ?>/service/services/add">Adaugă primul service</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 <?= $service['is_active'] ? '' : 'border-danger' ?>">
                        <!-- Header card cu tip service -->
                        <div class="card-header <?= $service['service_type'] === 'internal' ? 'bg-success text-white' : 'bg-primary text-white' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-<?= $service['service_type'] === 'internal' ? 'building' : 'handshake' ?>"></i>
                                    <?= $service['service_type'] === 'internal' ? 'SERVICE INTERN' : 'PARTENER EXTERN' ?>
                                </span>
                                <?php if (!$service['is_active']): ?>
                                    <span class="badge bg-danger">Inactiv</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?= ROUTE_BASE ?>/service/services/view/<?= $service['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($service['name']) ?>
                                </a>
                            </h5>
                            
                            <?php if ($service['rating']): ?>
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $service['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-muted">(<?= number_format($service['rating'], 1) ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($service['address']): ?>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(substr($service['address'], 0, 60)) ?>
                                    <?= strlen($service['address']) > 60 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($service['contact_phone']): ?>
                                <p class="card-text mb-1">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($service['contact_phone']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($service['contact_person']): ?>
                                <p class="card-text mb-1">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($service['contact_person']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($service['service_type'] === 'internal' && $service['capacity']): ?>
                                <p class="card-text mb-1">
                                    <i class="fas fa-car"></i> Capacitate: <?= $service['capacity'] ?> posturi
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($service['service_type'] === 'internal' && $service['hourly_rate']): ?>
                                <p class="card-text mb-1">
                                    <i class="fas fa-money-bill-wave"></i> Tarif: <?= number_format($service['hourly_rate'], 2) ?> RON/oră
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($service['working_hours']): ?>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-clock"></i> <?= htmlspecialchars($service['working_hours']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?= ROUTE_BASE ?>/service/services/view/<?= $service['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Detalii
                                </a>
                                
                                <?php if ($userRole === 'admin'): ?>
                                    <div class="btn-group">
                                        <a href="<?= ROUTE_BASE ?>/service/services/edit/<?= $service['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($service['is_active']): ?>
                                            <button onclick="toggleService(<?= $service['id'] ?>, 'deactivate')" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    title="Dezactivează">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="toggleService(<?= $service['id'] ?>, 'activate')" 
                                                    class="btn btn-sm btn-outline-success" 
                                                    title="Activează">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Info box pentru service intern -->
    <?php if ($hasInternal && $userRole === 'admin'): ?>
        <div class="alert alert-success mt-4">
            <h5><i class="fas fa-check-circle"></i> Service Intern Activ</h5>
            <p class="mb-2">Ai activat modulul de service intern. Poți accesa:</p>
            <ul class="mb-0">
                <li><a href="<?= ROUTE_BASE ?>/service/workshop" class="alert-link">Dashboard Atelier</a> - Gestionare ordine de lucru</li>
                <li><a href="<?= ROUTE_BASE ?>/service/mechanics" class="alert-link">Mecanici</a> - Gestionare personal atelier</li>
                <li><a href="<?= ROUTE_BASE ?>/service/workshop/vehicles" class="alert-link">Vehicule în Service</a> - Status vehicule</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleService(serviceId, action) {
    if (!confirm('Sigur vrei să ' + (action === 'activate' ? 'activezi' : 'dezactivezi') + ' acest service?')) {
        return;
    }
    
    const url = '<?= ROUTE_BASE ?>/service/services/' + action + '/' + serviceId;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare: ' + data.message);
        }
    })
    .catch(error => {
        alert('Eroare de comunicare cu serverul');
        console.error('Error:', error);
    });
}
</script>
