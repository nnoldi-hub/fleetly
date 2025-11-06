<?php
// modules/vehicles/views/types/list.php
$pageTitle = "Tipuri de Vehicule - Fleet Management";
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tipuri de Vehicule</h1>
        <a href="<?= BASE_URL ?>vehicle-types/add" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Adaugă Tip Vehicul</span>
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista Tipurilor de Vehicule</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($vehicleTypes)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="vehicleTypesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume</th>
                                <th>Descriere</th>
                                <th>Tip Combustibil</th>
                                <th>Capacitate</th>
                                <th>Interval Întreținere (km)</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicleTypes as $type): ?>
                                <tr>
                                    <td><?= htmlspecialchars($type['id']) ?></td>
                                    <td><?= htmlspecialchars($type['name']) ?></td>
                                    <td><?= htmlspecialchars($type['description']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $type['fuel_type'] == 'electric' ? 'success' : ($type['fuel_type'] == 'hibrid' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst(htmlspecialchars($type['fuel_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($type['capacity_min'] > 0 || $type['capacity_max'] > 0): ?>
                                            <?= $type['capacity_min'] ?> - <?= $type['capacity_max'] ?> persoane
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($type['maintenance_interval']) ?> km</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= BASE_URL ?>vehicle-types/edit?id=<?= $type['id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Editează">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm delete-btn" 
                                                    data-id="<?= $type['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($type['name']) ?>"
                                                    title="Șterge">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-car fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">Nu există tipuri de vehicule înregistrate.</p>
                    <a href="<?= BASE_URL ?>vehicle-types/add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adaugă primul tip de vehicul
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmare ștergere -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmare Ștergere</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi tipul de vehicul <strong id="deleteTypeName"></strong>?
                <br><small class="text-muted">Această acțiune nu poate fi anulată.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Șterge</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inițializare DataTable
    $('#vehicleTypesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Romanian.json"
        },
        "pageLength": 25,
        "order": [[ 1, "asc" ]]
    });

    // Handle delete
    let deleteId = null;
    $('.delete-btn').click(function() {
        deleteId = $(this).data('id');
        const typeName = $(this).data('name');
        $('#deleteTypeName').text(typeName);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').click(function() {
        if (deleteId) {
            $.post('<?= BASE_URL ?>vehicle-types/delete', { id: deleteId })
                .done(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Eroare: ' + response.error);
                    }
                })
                .fail(function() {
                    alert('Eroare la ștergerea tipului de vehicul.');
                });
        }
    });
});
</script>
