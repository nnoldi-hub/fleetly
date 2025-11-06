<?php

class VehicleController extends Controller {
    private $vehicleModel;
    private $vehicleTypeModel;
    private function translit($s) {
        if ($s === null) return '';
        $map = [
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T'
        ];
        $s = strtr((string)$s, $map);
        // strip non-ascii
        return preg_replace('/[^\x20-\x7E]/', '', $s);
    }
    
    public function __construct() {
        parent::__construct();
        $this->vehicleModel = new Vehicle();
        $this->vehicleTypeModel = new VehicleType();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $type_filter = $_GET['type'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        
        // Folosim noile metode de căutare avansată
        $vehicles = $this->vehicleModel->searchWithFilters(
            $search, 
            $type_filter ?: null, 
            $status_filter ?: null, 
            ITEMS_PER_PAGE, 
            $offset
        );
        
        $totalVehicles = $this->vehicleModel->countWithFilters(
            $search, 
            $type_filter ?: null, 
            $status_filter ?: null
        );
        
        $totalPages = ceil($totalVehicles / ITEMS_PER_PAGE);
        
        $vehicleTypes = $this->vehicleTypeModel->findAll();

        // Plan limits
        $company = Auth::getInstance()->company();
        $maxVehicles = (int)($company->max_vehicles ?? 0);
        $usedVehicles = (int)$this->vehicleModel->countWithFilters('', null, null);
        $limitReached = ($maxVehicles > 0 && $usedVehicles >= $maxVehicles);
        
        $this->render('list', [
            'vehicles' => $vehicles,
            'vehicleTypes' => $vehicleTypes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'type_filter' => $type_filter,
            'status_filter' => $status_filter,
            'maxVehicles' => $maxVehicles,
            'usedVehicles' => $usedVehicles,
            'limitReached' => $limitReached,
        ]);
    }
    
    public function add() {
        $viewErrors = [];
        $viewFormData = [];
        // Plan limit check (both GET and POST)
        $company = Auth::getInstance()->company();
        $maxVehicles = (int)($company->max_vehicles ?? 0);
        $usedVehicles = (int)$this->vehicleModel->countWithFilters('', null, null);
        $limitReached = ($maxVehicles > 0 && $usedVehicles >= $maxVehicles);

        if ($limitReached && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = 'Ati atins limita de vehicule (' . $usedVehicles . ' / ' . $maxVehicles . ').';
            header('Location: ' . BASE_URL . 'vehicles');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($limitReached) {
                $viewErrors['general'] = 'Ati atins limita planului pentru vehicule (' . $usedVehicles . ' / ' . $maxVehicles . ').';
                try { $vehicleTypes = $this->vehicleTypeModel->findAll(); } catch (Exception $e) { $vehicleTypes = []; }
                $this->render('add', [ 'vehicleTypes' => $vehicleTypes, 'errors' => $viewErrors, 'formData' => $_POST, 'limitReached' => true, 'maxVehicles'=>$maxVehicles, 'usedVehicles'=>$usedVehicles ]);
                return;
            }
            $data = [
                'registration_number' => $_POST['registration_number'] ?? '',
                'vin_number' => $_POST['vin_number'] ?? '',
                'brand' => $_POST['brand'] ?? '',
                'model' => $_POST['model'] ?? '',
                'year' => $_POST['year'] ?? '',
                'vehicle_type_id' => $_POST['vehicle_type_id'] ?? '',
                'status' => $_POST['status'] ?? 'active',
                'color' => $_POST['color'] ?? '',
                'fuel_type' => $_POST['fuel_type'] ?? 'benzina',
                'current_mileage' => $_POST['mileage'] ?? 0,
                'engine_capacity' => $_POST['engine_capacity'] ?? null,
                'purchase_price' => $_POST['purchase_price'] ?? null,
                'purchase_date' => $_POST['purchase_date'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Curățăm și validăm datele
            $data['registration_number'] = strtoupper(trim($data['registration_number']));
            $data['vin_number'] = !empty($data['vin_number']) ? strtoupper(trim($data['vin_number'])) : null;
            $data['current_mileage'] = (int)$data['current_mileage'];
            $data['year'] = (int)$data['year'];
            $data['vehicle_type_id'] = (int)$data['vehicle_type_id'];
            
            // Convertim prețurile la format numeric
            if (!empty($data['purchase_price'])) {
                $data['purchase_price'] = (float)$data['purchase_price'];
            } else {
                $data['purchase_price'] = null;
            }
            
            // Validăm data de achiziție
            if (!empty($data['purchase_date'])) {
                $date = DateTime::createFromFormat('Y-m-d', $data['purchase_date']);
                if (!$date || $date->format('Y-m-d') !== $data['purchase_date']) {
                    $data['purchase_date'] = null;
                }
            } else {
                $data['purchase_date'] = null;
            }
            
            // Mapare fuel_type pentru baza de date
            $fuelTypeMap = [
                'benzina' => 'petrol',
                'motorina' => 'diesel',
                'electric' => 'electric',
                'hibrid' => 'hybrid',
                'gpl' => 'gas'
            ];
            $data['fuel_type'] = $fuelTypeMap[$data['fuel_type']] ?? 'diesel';
            
            $validationRules = [
                'registration_number' => ['required' => true, 'max_length' => 20],
                'brand' => ['required' => true, 'max_length' => 50],
                'model' => ['required' => true, 'max_length' => 50],
                'year' => ['required' => true, 'type' => 'numeric'],
                'vehicle_type_id' => ['required' => true, 'type' => 'numeric']
            ];
            
            $errors = $this->validateInput($data, $validationRules);
            // Asigură existența tipului de vehicul
            if (empty($errors) && $data['vehicle_type_id'] > 0) {
                $vt = $this->vehicleTypeModel->find($data['vehicle_type_id']);
                if (!$vt) {
                    $errors['vehicle_type_id'] = 'Tip vehicul inexistent.';
                }
            }
            if (empty($errors)) {
                try {
                    // Încearcă să creeze vehiculul
                    $vehicleId = $this->vehicleModel->create($data);
                    if ($vehicleId) {
                        $_SESSION['success'] = 'Vehiculul a fost adăugat cu succes!';
                        header('Location: ' . BASE_URL . 'vehicles');
                        exit;
                    } else {
                        $errors['general'] = 'Inserarea a eșuat (ID nul).';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la salvare: ' . $e->getMessage();
                }
            }
            if (!empty($errors)) {
                $viewErrors = $errors;
                $viewFormData = $_POST;
            }
        }
        try { $vehicleTypes = $this->vehicleTypeModel->findAll(); } catch (Exception $e) { $vehicleTypes = []; }
        $this->render('add', [ 'vehicleTypes' => $vehicleTypes, 'errors' => $viewErrors, 'formData' => $viewFormData, 'limitReached' => $limitReached ?? false, 'maxVehicles'=>$maxVehicles ?? 0, 'usedVehicles'=>$usedVehicles ?? 0 ]);
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'vehicles/add');
            exit;
        }
        
        // Mapare fuel_type
        $fuelTypeMap = [
            'benzina' => 'petrol',
            'motorina' => 'diesel',
            'electric' => 'electric',
            'hibrid' => 'hybrid',
            'gpl' => 'gas'
        ];
        
        $fuelTypeInput = $_POST['fuel_type'] ?? 'diesel';
        $fuelTypeDB = $fuelTypeMap[$fuelTypeInput] ?? $fuelTypeInput;
        
        $data = [
            'registration_number' => strtoupper(trim($_POST['registration_number'] ?? '')),
            'vin_number' => !empty($_POST['vin_number']) ? strtoupper(trim($_POST['vin_number'])) : null,
            'brand' => trim($_POST['brand'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'year' => (int)($_POST['year'] ?? 0),
            'vehicle_type_id' => (int)($_POST['vehicle_type_id'] ?? 0),
            'status' => $_POST['status'] ?? 'active',
            'color' => trim($_POST['color'] ?? '') ?: null,
            'fuel_type' => $fuelTypeDB,
            'current_mileage' => (int)($_POST['mileage'] ?? 0),
            'engine_capacity' => !empty($_POST['engine_capacity']) ? $_POST['engine_capacity'] : null,
            'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
            'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Validare
        $errors = [];
        if (empty($data['registration_number'])) $errors[] = 'Număr înmatriculare lipsă.';
        if (empty($data['brand'])) $errors[] = 'Marca lipsă.';
        if (empty($data['model'])) $errors[] = 'Model lipsă.';
        if (empty($data['year']) || $data['year'] < 1900) $errors[] = 'An invalid.';
        if (empty($data['vehicle_type_id'])) $errors[] = 'Tip vehicul lipsă.';
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'vehicles/add');
            exit;
        }
        
        // Enforce plan limit before insert
        $company = Auth::getInstance()->company();
        $maxVehicles = (int)($company->max_vehicles ?? 0);
        $usedVehicles = (int)$this->vehicleModel->countWithFilters('', null, null);
        if ($maxVehicles > 0 && $usedVehicles >= $maxVehicles) {
            $_SESSION['errors'] = ['Ati atins limita planului pentru vehicule (' . $usedVehicles . ' / ' . $maxVehicles . ').'];
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'vehicles/add');
            exit;
        }

        // Inserare
        try {
            $vehicleId = $this->vehicleModel->create($data);
            
            if ($vehicleId) {
                $_SESSION['flash_success'] = 'Vehiculul "' . $data['registration_number'] . '" a fost adăugat cu succes!';
                header('Location: ' . BASE_URL . 'vehicles');
                exit;
            } else {
                $_SESSION['errors'] = ['Eroare la inserare în baza de date.'];
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . BASE_URL . 'vehicles/add');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['errors'] = ['Eroare: ' . $e->getMessage()];
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'vehicles/add');
            exit;
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $vehicle = $this->vehicleModel->getById($id);
        
        if (!$vehicle) {
            $this->error404('Vehiculul nu a fost găsit');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'registration_number' => strtoupper(trim($_POST['registration_number'] ?? '')),
                'vin_number' => !empty($_POST['vin_number']) ? strtoupper(trim($_POST['vin_number'])) : null,
                'brand' => trim($_POST['brand'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'year' => (int)($_POST['year'] ?? 0),
                'vehicle_type_id' => (int)($_POST['vehicle_type_id'] ?? 0),
                'status' => $_POST['status'] ?? 'active',
                'color' => trim($_POST['color'] ?? '') ?: null,
                'fuel_type' => $_POST['fuel_type'] ?? 'diesel',
                'current_mileage' => (int)($_POST['current_mileage'] ?? 0),
                'engine_capacity' => !empty($_POST['engine_capacity']) ? $_POST['engine_capacity'] : null,
                'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validare
            $errors = [];
            if (empty($data['registration_number'])) $errors['registration_number'] = 'Numărul de înmatriculare este obligatoriu';
            if (empty($data['brand'])) $errors['brand'] = 'Marca este obligatorie';
            if (empty($data['model'])) $errors['model'] = 'Modelul este obligatoriu';
            if (empty($data['year']) || $data['year'] < 1900) $errors['year'] = 'Anul este invalid';
            if (empty($data['vehicle_type_id'])) $errors['vehicle_type_id'] = 'Tipul de vehicul este obligatoriu';
            
            // Verificăm dacă kilometrajul nu a scăzut
            if ($data['current_mileage'] < $vehicle['current_mileage']) {
                $errors['current_mileage'] = 'Kilometrajul nu poate fi mai mic decât cel anterior';
            }
            
            if (empty($errors)) {
                try {
                    $this->vehicleModel->update($id, $data);
                    $_SESSION['flash_success'] = 'Vehiculul a fost actualizat cu succes!';
                    header('Location: ' . BASE_URL . 'vehicles/view?id=' . $id);
                    exit;
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la actualizare: ' . $e->getMessage();
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['form_data'] = $_POST;
            }
        }
        
        $vehicleTypes = $this->vehicleTypeModel->findAll();
        $this->render('edit', [
            'vehicle' => $vehicle,
            'vehicleTypes' => $vehicleTypes
        ]);
    }
    
    public function view() {
        $id = $_GET['id'] ?? 0;
        $vehicle = $this->vehicleModel->getById($id);
        
        if (!$vehicle) {
            http_response_code(404);
            include 'includes/header.php';
            echo '<div class="container mt-5"><div class="alert alert-danger"><h4>404 - Vehiculul nu a fost găsit</h4></div></div>';
            include 'includes/footer.php';
            exit;
        }
        
        // Date simple pentru view
        $stats = [];
        $documents = [];
        $recentMaintenance = [];
        $recentFuel = [];
        
        $this->render('view', [
            'vehicle' => $vehicle,
            'stats' => $stats,
            'documents' => $documents,
            'recentMaintenance' => $recentMaintenance,
            'recentFuel' => $recentFuel
        ]);
    }
    
    public function delete() {
        $id = $_POST['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['flash_error'] = 'ID vehicul invalid';
            header('Location: ' . BASE_URL . 'vehicles');
            exit;
        }
        
        $vehicle = $this->vehicleModel->getById($id);
        
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Vehiculul nu a fost găsit';
            header('Location: ' . BASE_URL . 'vehicles');
            exit;
        }
        
        try {
            $this->vehicleModel->delete($id);
            $_SESSION['flash_success'] = 'Vehiculul "' . $vehicle['registration_number'] . '" a fost șters cu succes!';
        } catch (Exception $e) {
            // Dacă avem constraint de FK, dezactivăm în loc de ștergere
            try {
                $this->vehicleModel->update($id, [
                    'status' => 'inactive',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $_SESSION['flash_success'] = 'Vehiculul "' . $vehicle['registration_number'] . '" a fost dezactivat (are înregistrări asociate).';
            } catch (Exception $e2) {
                $_SESSION['flash_error'] = 'Eroare la ștergerea vehiculului: ' . $e2->getMessage();
            }
        }
        
        header('Location: ' . BASE_URL . 'vehicles');
        exit;
    }
    
    public function dashboard() {
        // Date pentru dashboard-ul vehiculelor
        $totalVehicles = $this->vehicleModel->count();
        $activeVehicles = $this->vehicleModel->count(['status' => 'active']);
        $inMaintenanceVehicles = $this->vehicleModel->count(['status' => 'maintenance']);
        
        // Vehicule cu documente ce expiră în următoarele 30 de zile
        $expiringDocuments = $this->vehicleModel->getExpiringDocuments(30);
        
        // Vehicule cu întreținere scadentă
        $maintenanceDue = $this->vehicleModel->getMaintenanceDue();
        
        // Statistici pe tipuri de vehicule
        $vehiclesByType = $this->vehicleTypeModel->getAllWithVehicleCount();
        
        $this->render('dashboard', [
            'totalVehicles' => $totalVehicles,
            'activeVehicles' => $activeVehicles,
            'inMaintenanceVehicles' => $inMaintenanceVehicles,
            'expiringDocuments' => $expiringDocuments,
            'maintenanceDue' => $maintenanceDue,
            'vehiclesByType' => $vehiclesByType
        ]);
    }
    
    public function updateMileage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['vehicle_id'] ?? 0;
            $mileage = $_POST['mileage'] ?? 0;
            
            if (!is_numeric($mileage) || $mileage < 0) {
                $this->json(['error' => 'Kilometrajul trebuie să fie un număr pozitiv'], 400);
            }
            
            $vehicle = $this->vehicleModel->find($id);
            if (!$vehicle) {
                $this->json(['error' => 'Vehiculul nu a fost găsit'], 404);
            }
            
            if ($mileage < $vehicle['current_mileage']) {
                $this->json(['error' => 'Kilometrajul nu poate fi mai mic decât cel actual'], 400);
            }
            
            try {
                $this->vehicleModel->updateMileage($id, $mileage);
                $this->json([
                    'success' => true, 
                    'message' => 'Kilometrajul a fost actualizat',
                    'new_mileage' => $mileage
                ]);
            } catch (Exception $e) {
                $this->json(['error' => 'Eroare la actualizarea kilometrajului'], 500);
            }
        }
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $vehicles = $this->vehicleModel->getAllWithType();
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="vehicule_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($output, [
                $this->translit('Nr. Inmatriculare'), 'VIN', $this->translit('Marca'), 'Model', 'An', 
                $this->translit('Tip Vehicul'), 'Status', 'Kilometraj', $this->translit('Data Achizitie'), $this->translit('Pret Achizitie')
            ]);
            
            // Date
            foreach ($vehicles as $vehicle) {
                fputcsv($output, [
                    $this->translit($vehicle['registration_number']),
                    $this->translit($vehicle['vin_number']),
                    $this->translit($vehicle['brand']),
                    $this->translit($vehicle['model']),
                    $vehicle['year'],
                    $this->translit($vehicle['vehicle_type_name']),
                    $this->translit($vehicle['status']),
                    $vehicle['current_mileage'],
                    $vehicle['purchase_date'],
                    $vehicle['purchase_price']
                ]);
            }
            
            fclose($output);
        } elseif ($format === 'pdf') {
            // Produce a simple PDF summary using core/PdfExporter (ASCII-safe)
            require_once __DIR__ . '/../../../core/pdf_exporter.php';
            $lines = [];
            $lines[] = 'Lista vehicule';
            $lines[] = '';
            foreach ($vehicles as $v) {
                $row = [
                    $this->translit($v['registration_number'] ?? ''),
                    $this->translit(($v['brand'] ?? '') . ' ' . ($v['model'] ?? '')),
                    (string)($v['year'] ?? ''),
                    $this->translit($v['vehicle_type_name'] ?? ''),
                    $this->translit($v['status'] ?? ''),
                ];
                $lines[] = implode(' | ', $row);
            }
            $pdf = new PdfExporter();
            // Use emitSimplePdf-like output through a public method: reuse vehicle report stub
            $pdf->outputVehicleReport(['vehicles' => $vehicles], date('Y-m-d'), date('Y-m-d'), 'vehicule_' . date('Ymd'));
        } else {
            // Pentru JSON sau alte formate
            $this->json($vehicles);
        }
    }
}
?>