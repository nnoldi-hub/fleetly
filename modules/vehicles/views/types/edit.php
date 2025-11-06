<?php
// modules/vehicles/views/types/edit.php
$pageTitle = "Editează Tip Vehicul - Fleet Management";
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editează Tip Vehicul</h1>
        <a href="<?= BASE_URL ?>vehicle-types" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Înapoi la lista
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informații Tip Vehicul</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>vehicle-types/edit?id=<?= $vehicleType['id'] ?>" id="editVehicleTypeForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Nume Tip Vehicul <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?= htmlspecialchars($data['name'] ?? $vehicleType['name']) ?>" 
                                           required>
                                    <small class="form-text text-muted">Ex: Autoturism, Camion, Autobuz, etc.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fuel_type" class="form-label">Tip Combustibil <span class="text-danger">*</span></label>
                                    <select class="form-control" id="fuel_type" name="fuel_type" required>
                                        <option value="">Selectează tipul de combustibil</option>
                                        <?php 
                                        $selectedFuelType = $data['fuel_type'] ?? $vehicleType['fuel_type'];
                                        $fuelTypes = [
                                            'benzina' => 'Benzină',
                                            'motorina' => 'Motorină', 
                                            'electric' => 'Electric',
                                            'hibrid' => 'Hibrid',
                                            'gpl' => 'GPL'
                                        ];
                                        foreach ($fuelTypes as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $selectedFuelType == $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Descriere</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Descriere detaliată a tipului de vehicul..."><?= htmlspecialchars($data['description'] ?? $vehicleType['description']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capacity_min" class="form-label">Capacitate Minimă (persoane)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="capacity_min" 
                                           name="capacity_min" 
                                           min="0" 
                                           value="<?= htmlspecialchars($data['capacity_min'] ?? $vehicleType['capacity_min']) ?>">
                                    <small class="form-text text-muted">Numărul minim de persoane pe care le poate transporta</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capacity_max" class="form-label">Capacitate Maximă (persoane)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="capacity_max" 
                                           name="capacity_max" 
                                           min="0" 
                                           value="<?= htmlspecialchars($data['capacity_max'] ?? $vehicleType['capacity_max']) ?>">
                                    <small class="form-text text-muted">Numărul maxim de persoane pe care le poate transporta</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="maintenance_interval" class="form-label">Interval Întreținere (km) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="maintenance_interval" 
                                   name="maintenance_interval" 
                                   min="1000" 
                                   step="1000" 
                                   value="<?= htmlspecialchars($data['maintenance_interval'] ?? $vehicleType['maintenance_interval']) ?>" 
                                   required>
                            <small class="form-text text-muted">Intervalul recomandat între întrețineri (în kilometri)</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>vehicle-types" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Anulează
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizează Tipul de Vehicul
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Informații Tip</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?= $vehicleType['id'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Creat la:</strong></td>
                            <td>
                                <?php if (!empty($vehicleType['created_at'])): ?>
                                    <?= date('d.m.Y H:i', strtotime($vehicleType['created_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Nu este disponibil</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Actualizat la:</strong></td>
                            <td>
                                <?php if (!empty($vehicleType['updated_at'])): ?>
                                    <?= date('d.m.Y H:i', strtotime($vehicleType['updated_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Nu a fost actualizat</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Atenție</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        Modificările la tipul de vehicul vor afecta toate vehiculele care folosesc acest tip.
                    </p>
                    <p class="text-muted small">
                        Intervalul de întreținere va fi aplicat pentru toate vehiculele noi de acest tip.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Validare capacitate
    $('#capacity_min, #capacity_max').on('input', function() {
        const min = parseInt($('#capacity_min').val()) || 0;
        const max = parseInt($('#capacity_max').val()) || 0;
        
        if (max > 0 && min > max) {
            $('#capacity_max')[0].setCustomValidity('Capacitatea maximă trebuie să fie mai mare sau egală cu capacitatea minimă');
        } else {
            $('#capacity_max')[0].setCustomValidity('');
        }
    });

    // Validare formular
    $('#editVehicleTypeForm').on('submit', function(e) {
        const name = $('#name').val().trim();
        const fuelType = $('#fuel_type').val();
        const maintenanceInterval = parseInt($('#maintenance_interval').val());

        if (name.length < 2) {
            e.preventDefault();
            alert('Numele tipului de vehicul trebuie să aibă minim 2 caractere.');
            return false;
        }

        if (!fuelType) {
            e.preventDefault();
            alert('Te rog să selectezi tipul de combustibil.');
            return false;
        }

        if (maintenanceInterval < 1000) {
            e.preventDefault();
            alert('Intervalul de întreținere trebuie să fie de minim 1000 km.');
            return false;
        }
    });
});
</script>
