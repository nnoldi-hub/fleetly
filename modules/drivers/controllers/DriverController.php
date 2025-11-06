
<?php
require_once __DIR__ . '/../../maintenance/models/Maintenance.php';

class DriverController extends Controller {
    private $driverModel;
    private $vehicleModel;
    
    public function __construct() {
        parent::__construct();
        $this->driverModel = new Driver();
        $this->vehicleModel = new Vehicle();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $vehicle_filter = $_GET['vehicle'] ?? '';
        
        $conditions = [];
        if (!empty($status_filter)) {
            $conditions['status'] = $status_filter;
        }
        if (!empty($vehicle_filter)) {
            if ($vehicle_filter === 'assigned') {
                $conditions['assigned_vehicle_id !='] = 'NULL';
            } elseif ($vehicle_filter === 'unassigned') {
                $conditions['assigned_vehicle_id'] = null;
            }
        }
        
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $drivers = $this->driverModel->getAllWithVehicle();
        
        // Aplicăm filtrele manual pentru că avem JOIN
        if (!empty($conditions) || !empty($search)) {
            $drivers = array_filter($drivers, function($driver) use ($conditions, $search) {
                foreach ($conditions as $key => $value) {
                    if (strpos($key, '!=') !== false) {
                        $realKey = str_replace(' !=', '', $key);
                        if ($driver[$realKey] === null) return false;
                    } else {
                        if ($driver[$key] !== $value) return false;
                    }
                }
                
                if (!empty($search)) {
                    $searchFields = ['name', 'license_number', 'phone', 'email'];
                    $found = false;
                    foreach ($searchFields as $field) {
                        if (isset($driver[$field]) && stripos($driver[$field], $search) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) return false;
                }
                
                return true;
            });
        }
        
        $totalDrivers = count($drivers);
        $drivers = array_slice($drivers, $offset, ITEMS_PER_PAGE);
        $totalPages = ceil($totalDrivers / ITEMS_PER_PAGE);
        
        // Statistici rapide
        $stats = [
            'total' => $this->driverModel->countActive(),
            'assigned' => $this->driverModel->countAssignedActive(),
            'inactive' => $this->driverModel->countInactive(),
            'accidents' => 0, // placeholder (no accidents table in schema)
            'expiring_licenses' => count($this->driverModel->getWithExpiringLicenses(30)),
            'expired_licenses' => count($this->driverModel->getExpiredLicenses())
        ];
        
        $this->render('list', [
            'drivers' => $drivers,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'status_filter' => $status_filter,
            'vehicle_filter' => $vehicle_filter
        ]);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'license_number' => $_POST['license_number'] ?? '',
                'license_category' => $_POST['license_category'] ?? '',
                'license_issue_date' => $_POST['license_issue_date'] ?? null,
                'license_expiry_date' => $_POST['license_expiry_date'] ?? null,
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'address' => $_POST['address'] ?? '',
                'date_of_birth' => $_POST['date_of_birth'] ?? null,
                'hire_date' => $_POST['hire_date'] ?? null,
                'status' => $_POST['status'] ?? 'active',
                'assigned_vehicle_id' => !empty($_POST['assigned_vehicle_id']) ? $_POST['assigned_vehicle_id'] : null,
                'notes' => $_POST['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $validationRules = [
                'name' => ['required' => true, 'max_length' => 100],
                'license_number' => ['required' => true, 'max_length' => 50],
                'phone' => ['type' => 'phone'],
                'email' => ['type' => 'email'],
                'license_issue_date' => ['type' => 'date'],
                'license_expiry_date' => ['type' => 'date'],
                'date_of_birth' => ['type' => 'date'],
                'hire_date' => ['type' => 'date']
            ];
            
            $errors = $this->validateInput($data, $validationRules);
            
            // Validări suplimentare
            if (!empty($data['license_issue_date']) && !empty($data['license_expiry_date'])) {
                if (strtotime($data['license_expiry_date']) <= strtotime($data['license_issue_date'])) {
                    $errors['license_expiry_date'] = 'Data expirării trebuie să fie ulterioară datei emiterii';
                }
            }
            
            // Verifică dacă numărul permisului este unic
            if (!empty($data['license_number'])) {
                $existingDriver = $this->driverModel->findAll(['license_number' => $data['license_number']]);
                if (!empty($existingDriver)) {
                    $errors['license_number'] = 'Numărul permisului există deja în sistem';
                }
            }
            
            // Verifică dacă vehiculul este disponibil
            if (!empty($data['assigned_vehicle_id'])) {
                $vehicle = $this->vehicleModel->find($data['assigned_vehicle_id']);
                if (!$vehicle) {
                    $errors['assigned_vehicle_id'] = 'Vehiculul selectat nu există';
                } else {
                    // Verifică dacă vehiculul nu este deja asignat
                    $assignedDriver = $this->driverModel->findAll(['assigned_vehicle_id' => $data['assigned_vehicle_id']]);
                    if (!empty($assignedDriver)) {
                        $errors['assigned_vehicle_id'] = 'Vehiculul este deja asignat altui șofer';
                    }
                }
            }
            
            if (empty($errors)) {
                try {
                    $driverId = $this->driverModel->create($data);
                    $_SESSION['success'] = 'Șoferul a fost adăugat cu succes!';
                    $this->redirect(BASE_URL . 'drivers/view?id=' . urlencode($driverId));
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la salvarea șoferului: ' . $e->getMessage();
                }
            }
            
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $data;
        }
        
        $availableVehicles = $this->getAvailableVehicles();
        $licenseCategories = $this->getLicenseCategories();
        
        $this->render('add', [
            'availableVehicles' => $availableVehicles,
            'licenseCategories' => $licenseCategories
        ]);
    }
    
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $driver = $this->driverModel->find($id);
        
        if (!$driver) {
            $this->error404('Șoferul nu a fost găsit');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'license_number' => $_POST['license_number'] ?? '',
                'license_category' => $_POST['license_category'] ?? '',
                'license_issue_date' => $_POST['license_issue_date'] ?? null,
                'license_expiry_date' => $_POST['license_expiry_date'] ?? null,
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'address' => $_POST['address'] ?? '',
                'date_of_birth' => $_POST['date_of_birth'] ?? null,
                'hire_date' => $_POST['hire_date'] ?? null,
                'status' => $_POST['status'] ?? 'active',
                'assigned_vehicle_id' => !empty($_POST['assigned_vehicle_id']) ? $_POST['assigned_vehicle_id'] : null,
                'notes' => $_POST['notes'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $validationRules = [
                'name' => ['required' => true, 'max_length' => 100],
                'license_number' => ['required' => true, 'max_length' => 50],
                'phone' => ['type' => 'phone'],
                'email' => ['type' => 'email'],
                'license_issue_date' => ['type' => 'date'],
                'license_expiry_date' => ['type' => 'date'],
                'date_of_birth' => ['type' => 'date'],
                'hire_date' => ['type' => 'date']
            ];
            
            $errors = $this->validateInput($data, $validationRules);
            
            // Verifică dacă numărul permisului este unic (exceptând șoferul curent)
            if (!empty($data['license_number'])) {
                $existingDriver = $this->driverModel->findAll(['license_number' => $data['license_number']]);
                $existingDriver = array_filter($existingDriver, function($d) use ($id) {
                    return $d['id'] != $id;
                });
                if (!empty($existingDriver)) {
                    $errors['license_number'] = 'Numărul permisului există deja în sistem';
                }
            }
            
            // Verifică vehiculul asignat
            if (!empty($data['assigned_vehicle_id'])) {
                $vehicle = $this->vehicleModel->find($data['assigned_vehicle_id']);
                if (!$vehicle) {
                    $errors['assigned_vehicle_id'] = 'Vehiculul selectat nu există';
                } else {
                    // Verifică dacă vehiculul nu este asignat altui șofer
                    $assignedDriver = $this->driverModel->findAll(['assigned_vehicle_id' => $data['assigned_vehicle_id']]);
                    $assignedDriver = array_filter($assignedDriver, function($d) use ($id) {
                        return $d['id'] != $id;
                    });
                    if (!empty($assignedDriver)) {
                        $errors['assigned_vehicle_id'] = 'Vehiculul este deja asignat altui șofer';
                    }
                }
            }
            
            if (empty($errors)) {
                try {
                    $this->driverModel->update($id, $data);
                    $_SESSION['success'] = 'Șoferul a fost actualizat cu succes!';
                    $this->redirect(BASE_URL . 'drivers/view?id=' . urlencode($id));
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la actualizarea șoferului: ' . $e->getMessage();
                }
            }
            
            $_SESSION['errors'] = $errors;
            $driver = array_merge($driver, $data);
        }
        
        $availableVehicles = $this->getAvailableVehicles($driver['assigned_vehicle_id']);
        $licenseCategories = $this->getLicenseCategories();
        
        $this->render('edit', [
            'driver' => $driver,
            'availableVehicles' => $availableVehicles,
            'licenseCategories' => $licenseCategories
        ]);
    }
    
    public function view() {
        $id = $_GET['id'] ?? 0;
        $driver = $this->driverModel->find($id);
        
        if (!$driver) {
            $this->error404('Șoferul nu a fost găsit');
        }
        
        // Obține vehiculul asignat
        $assignedVehicle = null;
        if ($driver['assigned_vehicle_id']) {
            $assignedVehicle = $this->vehicleModel->find($driver['assigned_vehicle_id']);
        }
        
        // Calculează zile până la expirarea permisului
        $daysUntilExpiry = null;
        if ($driver['license_expiry_date']) {
            $expiryDate = new DateTime($driver['license_expiry_date']);
            $today = new DateTime();
            $diff = $today->diff($expiryDate);
            $daysUntilExpiry = $expiryDate < $today ? -$diff->days : $diff->days;
        }
        
        // Obține performanța șoferului
        $performance = $this->driverModel->getDriverPerformance($id);
        
        // Obține istoricul recent de combustibil
        $fuelModel = new FuelConsumption();
        $recentFuel = $fuelModel->findAll(['driver_id' => $id], 10);
        
        // Obține întreținerea pentru vehiculul asignat
        $recentMaintenance = [];
        if ($driver['assigned_vehicle_id']) {
            $maintenanceModel = new Maintenance();
            $recentMaintenance = $maintenanceModel->findAll(['vehicle_id' => $driver['assigned_vehicle_id']], 5);
        }
        
        $this->render('view', [
            'driver' => $driver,
            'assignedVehicle' => $assignedVehicle,
            'daysUntilExpiry' => $daysUntilExpiry,
            'performance' => $performance,
            'recentFuel' => $recentFuel,
            'recentMaintenance' => $recentMaintenance
        ]);
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $driver = $this->driverModel->find($id);
            
            if (!$driver) {
                $this->json(['error' => 'Șoferul nu a fost găsit'], 404);
            }
            
            try {
                $this->db->beginTransaction();
                
                // Verifică dacă șoferul are înregistrări asociate
                $fuelModel = new FuelConsumption();
                $hasFuelRecords = $fuelModel->count(['driver_id' => $id]) > 0;
                
                if ($hasFuelRecords) {
                    // Dezactivează șoferul în loc să-l ștergi
                    $this->driverModel->update($id, [
                        'status' => 'inactive',
                        'assigned_vehicle_id' => null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Șoferul a fost dezactivat (avea înregistrări asociate)';
                } else {
                    // Șterge șoferul dacă nu are înregistrări
                    $this->driverModel->delete($id);
                    $message = 'Șoferul a fost șters definitiv';
                }
                
                $this->db->commit();
                $this->json(['success' => true, 'message' => $message]);
                
            } catch (Exception $e) {
                $this->db->rollback();
                $this->json(['error' => 'Eroare la ștergerea șoferului: ' . $e->getMessage()], 500);
            }
        }
    }
    
    public function assignVehicle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $driverId = $_POST['driver_id'] ?? 0;
            $vehicleId = $_POST['vehicle_id'] ?? 0;
            
            $driver = $this->driverModel->find($driverId);
            $vehicle = $this->vehicleModel->find($vehicleId);
            
            if (!$driver || !$vehicle) {
                $this->json(['error' => 'Șoferul sau vehiculul nu au fost găsite'], 404);
            }
            
            try {
                $this->driverModel->assignVehicle($driverId, $vehicleId);
                $this->json([
                    'success' => true,
                    'message' => "Vehiculul {$vehicle['registration_number']} a fost asignat șoferului {$driver['name']}"
                ]);
            } catch (Exception $e) {
                $this->json(['error' => 'Eroare la asignarea vehiculului'], 500);
            }
        }
    }
    
    public function unassignVehicle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $driverId = $_POST['driver_id'] ?? 0;
            
            $driver = $this->driverModel->find($driverId);
            if (!$driver) {
                $this->json(['error' => 'Șoferul nu a fost găsit'], 404);
            }
            
            try {
                $this->driverModel->unassignVehicle($driverId);
                $this->json([
                    'success' => true,
                    'message' => "Vehiculul a fost dezasignat de la șoferul {$driver['name']}"
                ]);
            } catch (Exception $e) {
                $this->json(['error' => 'Eroare la dezasignarea vehiculului'], 500);
            }
        }
    }
    
    public function renewLicense() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $driverId = $_POST['driver_id'] ?? 0;
            $newExpiryDate = $_POST['new_expiry_date'] ?? '';
            
            $driver = $this->driverModel->find($driverId);
            if (!$driver) {
                $this->json(['error' => 'Șoferul nu a fost găsit'], 404);
            }
            
            if (empty($newExpiryDate)) {
                $this->json(['error' => 'Data de expirare este obligatorie'], 400);
            }
            
            if (strtotime($newExpiryDate) <= time()) {
                $this->json(['error' => 'Data expirării trebuie să fie în viitor'], 400);
            }
            
            try {
                $this->driverModel->updateLicenseExpiry($driverId, $newExpiryDate);
                $this->json([
                    'success' => true,
                    'message' => 'Permisul a fost reînnoit cu succes',
                    'new_expiry_date' => $newExpiryDate
                ]);
            } catch (Exception $e) {
                $this->json(['error' => 'Eroare la reînnoirea permisului'], 500);
            }
        }
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $drivers = $this->driverModel->getAllWithVehicle();
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="soferi_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM pentru UTF-8
            
            // Header CSV
            fputcsv($output, [
                'Nume', 'Număr Permis', 'Categorie', 'Telefon', 'Email',
                'Data Expirării', 'Zile până la Expirare', 'Status',
                'Vehicul Asignat', 'Data Angajării'
            ]);
            
            // Date
            foreach ($drivers as $driver) {
                fputcsv($output, [
                    $driver['name'],
                    $driver['license_number'],
                    $driver['license_category'] ?? '',
                    $driver['phone'] ?? '',
                    $driver['email'] ?? '',
                    $driver['license_expiry_date'] ?? '',
                    $driver['days_until_expiry'] ?? '',
                    $this->getStatusName($driver['status']),
                    ($driver['registration_number'] ?? '') . ' ' . ($driver['brand'] ?? '') . ' ' . ($driver['model'] ?? ''),
                    $driver['hire_date'] ?? ''
                ]);
            }
            
            fclose($output);
        } else {
            $this->json($drivers);
        }
    }
    
    private function getAvailableVehicles($excludeVehicleId = null) {
        // Obține vehiculele care nu sunt asignate
        $sql = "SELECT v.* FROM vehicles v 
                LEFT JOIN drivers d ON v.id = d.assigned_vehicle_id 
                WHERE d.assigned_vehicle_id IS NULL AND v.status = 'active'";
        
        $params = [];
        if ($excludeVehicleId) {
            $sql .= " OR v.id = ?";
            $params[] = $excludeVehicleId;
        }
        
        $sql .= " ORDER BY v.registration_number";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getLicenseCategories() {
        return [
            'A' => 'Categoria A (Motociclete)',
            'A1' => 'Categoria A1 (Motociclete ușoare)',
            'A2' => 'Categoria A2 (Motociclete mijlocii)',
            'B' => 'Categoria B (Autoturisme)',
            'B1' => 'Categoria B1 (Autovehicule cu 3-4 roți)',
            'BE' => 'Categoria BE (Autoturisme cu remorcă)',
            'C' => 'Categoria C (Camioane)',
            'C1' => 'Categoria C1 (Camioane ușoare)',
            'CE' => 'Categoria CE (Camioane cu remorcă)',
            'C1E' => 'Categoria C1E (Camioane ușoare cu remorcă)',
            'D' => 'Categoria D (Autobuze)',
            'D1' => 'Categoria D1 (Microbuze)',
            'DE' => 'Categoria DE (Autobuze cu remorcă)',
            'D1E' => 'Categoria D1E (Microbuze cu remorcă)'
        ];
    }
    
    private function getStatusName($status) {
        $statuses = [
            'active' => 'Activ',
            'inactive' => 'Inactiv',
            'suspended' => 'Suspendat'
        ];
        return $statuses[$status] ?? $status;
    }
    
    // API endpoint pentru obținerea șoferilor (AJAX)
    public function getDriversAjax() {
        $status = $_GET['status'] ?? 'active';
        $drivers = $this->driverModel->getDriversByStatus($status);
        $this->json(['drivers' => $drivers]);
    }
    
    // API endpoint pentru statistici
    public function getStats() {
        $stats = [
            'total_active' => $this->driverModel->count(['status' => 'active']),
            'total_assigned' => $this->driverModel->count(['status' => 'active', 'assigned_vehicle_id !=' => 'NULL']),
            'expiring_licenses_30' => count($this->driverModel->getWithExpiringLicenses(30)),
            'expiring_licenses_7' => count($this->driverModel->getWithExpiringLicenses(7)),
            'expired_licenses' => count($this->driverModel->getExpiredLicenses()),
        ];
        
        $this->json(['stats' => $stats]);
    }
}
?>