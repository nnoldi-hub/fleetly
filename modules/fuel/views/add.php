<?php
// Expect: $vehicles, $drivers, $selectedVehicleId provided by controller
$oldData = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Adaugă consum combustibil</h2>
        <?php if (!empty($selectedVehicleId)): ?>
            <a href="<?= BASE_URL ?>vehicles/view?id=<?= urlencode($selectedVehicleId) ?>" class="btn btn-secondary">Înapoi la vehicul</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>fuel" class="btn btn-secondary">Înapoi la listă</a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= nl2br(htmlspecialchars($flash['message'])) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form id="fuelForm" method="POST" action="<?= BASE_URL ?>fuel/add" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required-field" for="vehicle_id">Vehicul</label>
                            <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                <option value="">Selectează vehiculul…</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['id'] ?>"
                                                    data-fuel-type="<?= htmlspecialchars($vehicle['fuel_type']) ?>"
                                                    <?= (($oldData['vehicle_id'] ?? $selectedVehicleId) == $vehicle['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selectează vehiculul pentru care se înregistrează consumul</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="driver_id">Șofer</label>
                            <select class="form-select" id="driver_id" name="driver_id">
                                <option value="">Selectează șoferul…</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>" <?= (($oldData['driver_id'] ?? '') == $driver['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($driver['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Opțional</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required-field" for="fuel_date">Data alimentării</label>
                            <input type="date" class="form-control" id="fuel_date" name="fuel_date" value="<?= $oldData['fuel_date'] ?? date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required-field" for="mileage">Kilometraj (km)</label>
                            <input type="number" class="form-control" id="mileage" name="mileage" min="0" step="1" value="<?= htmlspecialchars($oldData['mileage'] ?? '') ?>" placeholder="Ex: 125000" required>
                            <div class="form-text">Kilometrajul la momentul alimentării</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required-field" for="liters">Cantitate (L)</label>
                            <input type="number" class="form-control" id="liters" name="liters" min="0" step="0.01" value="<?= htmlspecialchars($oldData['liters'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required-field" for="cost_per_liter">Preț/Litru (RON)</label>
                            <input type="number" class="form-control" id="cost_per_liter" name="cost_per_liter" min="0" step="0.01" value="<?= htmlspecialchars($oldData['cost_per_liter'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label" for="total_cost">Cost total (RON)</label>
                            <input type="number" class="form-control" id="total_cost" name="total_cost" min="0" step="0.01" value="<?= htmlspecialchars($oldData['total_cost'] ?? '') ?>" readonly>
                            <div class="form-text">Se calculează automat</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required-field" for="fuel_type">Tip combustibil</label>
                            <select class="form-select" id="fuel_type" name="fuel_type" required>
                                <option value="">Selectează…</option>
                                <option value="petrol"  <?= (($oldData['fuel_type'] ?? '') === 'petrol') ? 'selected' : '' ?>>Benzină</option>
                                <option value="diesel"  <?= (($oldData['fuel_type'] ?? '') === 'diesel') ? 'selected' : '' ?>>Motorină</option>
                                <option value="electric"<?= (($oldData['fuel_type'] ?? '') === 'electric') ? 'selected' : '' ?>>Electric</option>
                                <option value="gas"     <?= (($oldData['fuel_type'] ?? '') === 'gas') ? 'selected' : '' ?>>GPL/Gaz</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label" for="station">Stația</label>
                            <input type="text" class="form-control" id="station" name="station" value="<?= htmlspecialchars($oldData['station'] ?? '') ?>" placeholder="Ex: Petrom, OMV, Rompetrol">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label" for="receipt_number">Nr. bon</label>
                            <input type="text" class="form-control" id="receipt_number" name="receipt_number" value="<?= htmlspecialchars($oldData['receipt_number'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_full_tank" name="is_full_tank" value="1" <?= !empty($oldData['is_full_tank']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_full_tank">Rezervor plin</label>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="notes">Observații</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($oldData['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="receipt_file">Bon fiscal / factură</label>
                            <input class="form-control" type="file" id="receipt_file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text">PDF, JPG sau PNG – max 5MB</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <div>
                        <button type="submit" class="btn btn-primary">Salvează alimentarea</button>
                        <button type="button" id="save-and-add-another" class="btn btn-outline-primary ms-2">Salvează și adaugă alta</button>
                    </div>
                    <div>
                        <?php if (!empty($selectedVehicleId)): ?>
                            <a href="<?= BASE_URL ?>vehicles/view?id=<?= urlencode($selectedVehicleId) ?>" class="btn btn-secondary">Anulează</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>fuel" class="btn btn-secondary">Anulează</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    let lastMileage = 0;

    function recalc() {
        const liters = parseFloat(document.getElementById('liters').value) || 0;
        const price = parseFloat(document.getElementById('cost_per_liter').value) || 0;
        document.getElementById('total_cost').value = (liters * price).toFixed(2);
    }

    document.getElementById('liters').addEventListener('input', recalc);
    document.getElementById('cost_per_liter').addEventListener('input', recalc);

    // Load last mileage when vehicle changes
    const vehicleSel = document.getElementById('vehicle_id');
    function loadLast(vehicleId){
        if(!vehicleId) return;
        fetch('<?= BASE_URL ?>fuel/last-odometer?vehicle_id=' + encodeURIComponent(vehicleId))
            .then(r => r.json())
            .then(j => {
                if (j && j.success && j.last_odometer) {
                    lastMileage = parseInt(j.last_odometer, 10) || 0;
                    const m = document.getElementById('mileage');
                    if (m) m.placeholder = 'Ultimul kilometraj: ' + lastMileage + ' km';
                }
            })
            .catch(()=>{});
    }
    vehicleSel.addEventListener('change', function(){ loadLast(this.value); });
    if (vehicleSel.value) loadLast(vehicleSel.value);

    // Save and add another
    document.getElementById('save-and-add-another').addEventListener('click', function(){
        const f = document.getElementById('fuelForm');
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'save_and_add_another'; h.value = '1';
        f.appendChild(h); f.submit();
    });

    // Client validation
    document.getElementById('fuelForm').addEventListener('submit', function(e){
        const mileage = parseInt(document.getElementById('mileage').value, 10) || 0;
        const liters = parseFloat(document.getElementById('liters').value) || 0;
        const price  = parseFloat(document.getElementById('cost_per_liter').value) || 0;
        if (lastMileage && mileage <= lastMileage) {
            e.preventDefault();
            alert('Kilometrajul trebuie să fie mai mare decât ' + lastMileage + ' km.');
            return false;
        }
        if (liters <= 0 || price <= 0) {
            e.preventDefault();
            alert('Cantitatea și prețul pe litru trebuie să fie > 0.');
            return false;
        }
    });

    // File validation
    const rf = document.getElementById('receipt_file');
    if (rf) rf.addEventListener('change', function(){
        const file = this.files && this.files[0];
        if (!file) return;
        const max = 5 * 1024 * 1024;
        const ok = ['image/jpeg','image/jpg','image/png','application/pdf'];
        if (file.size > max) { alert('Fișierul este prea mare (max 5MB).'); this.value=''; }
        else if (!ok.includes(file.type)) { alert('Tip de fișier nepermis. Doar PDF/JPG/PNG.'); this.value=''; }
    });
})();
</script>

