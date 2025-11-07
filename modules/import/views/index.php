<?php require_once 'includes/header.php'; ?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <?php require_once 'includes/breadcrumb.php'; ?>

    <!-- Mesaje -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['import_errors']) && !empty($_SESSION['import_errors'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-circle me-2"></i>Erori detaliate:</h5>
            <ul class="mb-0">
                <?php foreach ($_SESSION['import_errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['import_errors']); ?>
    <?php endif; ?>

    <!-- Sectiune Vehicule -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-truck me-2"></i>Import Vehicule
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 1: Descarcă template-ul CSV</h6>
                    <a href="<?= ROUTE_BASE ?>import/download-vehicles-template" class="btn btn-outline-primary mb-3">
                        <i class="bi bi-download me-2"></i>Descarcă Template Vehicule
                    </a>
                    
                    <div class="alert alert-info">
                        <strong>Coloane obligatorii:</strong>
                        <ul class="mb-0 mt-2">
                            <li><code>numar_inmatriculare</code> - Ex: B-123-ABC (UNIC)</li>
                            <li><code>marca</code> - Ex: Dacia, Ford, Mercedes</li>
                            <li><code>model</code> - Ex: Logan, Focus, Sprinter</li>
                            <li><code>an</code> - Ex: 2020, 2021</li>
                            <li><code>tip_vehicul_id</code> - ID-ul tipului (vezi mai jos)</li>
                        </ul>
                        
                        <strong class="mt-2 d-block">Tipuri vehicul (tip_vehicul_id):</strong>
                        <div class="row mt-1">
                            <div class="col-6">
                                <small>
                                    1 = Autoturism Personal<br>
                                    2 = Autoutilitara Mica<br>
                                    3 = Camion<br>
                                    4 = Autobus/Microbuz<br>
                                    5 = Motostivuitor
                                </small>
                            </div>
                            <div class="col-6">
                                <small>
                                    6 = Excavator<br>
                                    7 = Buldozer<br>
                                    8 = Trailer/Remorca<br>
                                    9 = Utilaj Agricol<br>
                                    10 = Generator/Compresor
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Exemplu structură CSV:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white">
                                    <thead class="table-dark">
                                        <tr style="font-size: 0.75rem;">
                                            <th>numar_inmatriculare</th>
                                            <th>marca</th>
                                            <th>model</th>
                                            <th>an</th>
                                            <th>tip_vehicul_id</th>
                                            <th>culoare</th>
                                            <th>tip_combustibil</th>
                                            <th>status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size: 0.75rem;">
                                            <td>B-123-ABC</td>
                                            <td>Dacia</td>
                                            <td>Logan</td>
                                            <td>2020</td>
                                            <td>1</td>
                                            <td>Alb</td>
                                            <td>petrol</td>
                                            <td>active</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                <strong>Tip combustibil:</strong> petrol, diesel, electric, hybrid, gas<br>
                                <strong>Status:</strong> active, inactive, maintenance, deleted
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 2: Încarcă fișierul CSV</h6>
                    <form action="<?= ROUTE_BASE ?>import/upload-vehicles" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="vehicles_csv" class="form-label">Selectează fișier CSV:</label>
                            <input type="file" class="form-control" id="vehicles_csv" name="csv_file" accept=".csv" required>
                            <div class="form-text">
                                Format acceptat: CSV (UTF-8). Maximum 2MB.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Începe Import Vehicule
                        </button>
                    </form>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Notă:</strong> Asigură-te că fișierul CSV este salvat în format UTF-8 pentru caracterele speciale (ă, â, î, ș, ț).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sectiune Documente -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Import Documente
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 1: Descarcă template-ul CSV</h6>
                    <a href="<?= ROUTE_BASE ?>import/download-documents-template" class="btn btn-outline-success mb-3">
                        <i class="bi bi-download me-2"></i>Descarcă Template Documente
                    </a>
                    
                    <div class="alert alert-info">
                        <strong>Coloane obligatorii:</strong>
                        <ul class="mb-0 mt-2">
                            <li><code>numar_inmatriculare_vehicul</code> - Trebuie să existe în baza de date</li>
                            <li><code>tip_document</code> - ITP, RCA, Rovinieta, Tahograf, etc.</li>
                            <li><code>data_expirare</code> - Format: YYYY-MM-DD</li>
                        </ul>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Exemplu structură CSV:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white">
                                    <thead class="table-dark">
                                        <tr style="font-size: 0.75rem;">
                                            <th>numar_inmatriculare_vehicul</th>
                                            <th>tip_document</th>
                                            <th>numar_document</th>
                                            <th>data_emitere</th>
                                            <th>data_expirare</th>
                                            <th>emitent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size: 0.75rem;">
                                            <td>B-123-ABC</td>
                                            <td>ITP</td>
                                            <td>ITP-2024-12345</td>
                                            <td>2024-01-15</td>
                                            <td>2025-01-15</td>
                                            <td>RAR București</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                <strong>Tipuri documente:</strong> ITP, RCA, Carte Identitate, Rovinieta, Tahograf, Autorizatie Transport, Contract Leasing
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 2: Încarcă fișierul CSV</h6>
                    <form action="<?= ROUTE_BASE ?>import/upload-documents" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="documents_csv" class="form-label">Selectează fișier CSV:</label>
                            <input type="file" class="form-control" id="documents_csv" name="csv_file" accept=".csv" required>
                            <div class="form-text">
                                Format acceptat: CSV (UTF-8). Maximum 2MB.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload me-2"></i>Începe Import Documente
                        </button>
                    </form>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Important:</strong> Vehiculele trebuie să existe deja în sistem. Importă mai întâi vehiculele!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sectiune Soferi -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-person-badge me-2"></i>Import Șoferi
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 1: Descarcă template-ul CSV</h6>
                    <a href="<?= ROUTE_BASE ?>import/download-drivers-template" class="btn btn-outline-warning mb-3">
                        <i class="bi bi-download me-2"></i>Descarcă Template Șoferi
                    </a>
                    
                    <div class="alert alert-info">
                        <strong>Coloane obligatorii:</strong>
                        <ul class="mb-0 mt-2">
                            <li><code>nume</code> - Numele șoferului</li>
                            <li><code>prenume</code> - Prenumele șoferului</li>
                        </ul>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Exemplu structură CSV:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white">
                                    <thead class="table-dark">
                                        <tr style="font-size: 0.75rem;">
                                            <th>nume</th>
                                            <th>prenume</th>
                                            <th>cnp</th>
                                            <th>telefon</th>
                                            <th>email</th>
                                            <th>numar_permis</th>
                                            <th>tip_permis</th>
                                            <th>data_angajare</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="font-size: 0.75rem;">
                                            <td>Popescu</td>
                                            <td>Ion</td>
                                            <td>1850101123456</td>
                                            <td>0721234567</td>
                                            <td>ion@email.ro</td>
                                            <td>AB123456</td>
                                            <td>B,C,D</td>
                                            <td>2020-06-01</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                <strong>Categorii permis:</strong> A, A1, A2, AM, B, B1, BE, C, C1, C1E, CE, D, D1, D1E, DE, Tr (separate prin virgulă)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Pasul 2: Încarcă fișierul CSV</h6>
                    <form action="<?= ROUTE_BASE ?>import/upload-drivers" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="drivers_csv" class="form-label">Selectează fișier CSV:</label>
                            <input type="file" class="form-control" id="drivers_csv" name="csv_file" accept=".csv" required>
                            <div class="form-text">
                                Format acceptat: CSV (UTF-8). Maximum 2MB.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-upload me-2"></i>Începe Import Șoferi
                        </button>
                    </form>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Notă:</strong> CNP-ul trebuie să fie unic. Dacă există deja, șoferul nu va fi importat.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructiuni generale -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="bi bi-question-circle me-2"></i>Instrucțiuni de utilizare
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Pregătirea fișierului CSV în Excel:</h6>
                    <ol>
                        <li>Deschide fișierul Excel existent sau descarcă template-ul</li>
                        <li>Completează datele conform coloanelor din exemplu</li>
                        <li>Mergi la <strong>File → Save As</strong></li>
                        <li>Selectează <strong>CSV UTF-8 (Comma delimited)</strong></li>
                        <li>Salvează fișierul</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>Recomandări:</h6>
                    <ul>
                        <li>Importă <strong>întâi vehiculele</strong>, apoi documentele</li>
                        <li>Verifică datele înainte de import (duplicate, formate)</li>
                        <li>Folosește formatul ISO pentru date: <code>YYYY-MM-DD</code></li>
                        <li>Pentru valori goale, lasă celula vidă (nu scrie "NULL" sau "-")</li>
                        <li>Testează cu câteva rânduri înainte de import masiv</li>
                    </ul>
                </div>
            </div>
            
            <hr>
            
            <div class="alert alert-light border">
                <h6 class="alert-heading">Format date CSV:</h6>
                <ul class="mb-0">
                    <li><strong>Date:</strong> YYYY-MM-DD (ex: 2024-01-15)</li>
                    <li><strong>Numere:</strong> Fără separatori de mii (ex: 45000, nu 45.000)</li>
                    <li><strong>Zecimale:</strong> Punct ca separator (ex: 1500.50, nu 1500,50)</li>
                    <li><strong>Text:</strong> Fără ghilimele (programul le va adăuga automat)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 8px;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
}

.table-responsive {
    overflow-x: auto;
}

.table-sm {
    font-size: 0.85rem;
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}
</style>

<?php require_once 'includes/footer.php'; ?>
