<?php
// modules/fuel/controllers/FuelController.php

class FuelController extends Controller {
    private $fuelModel;
    private $vehicleModel;
    private $driverModel;
    
    public function __construct() {
        parent::__construct();
        $this->fuelModel = new FuelConsumption();
        $this->vehicleModel = new Vehicle();
        $this->driverModel = new Driver();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $vehicle_filter = $_GET['vehicle'] ?? '';
        $driver_filter = $_GET['driver'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $fuel_type_filter = $_GET['fuel_type'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 25);
        
        $conditions = [];
        
        // Aplicăm filtrele
        if (!empty($vehicle_filter)) {
            $conditions['vehicle_id'] = $vehicle_filter;
        }
        if (!empty($driver_filter)) {
            $conditions['driver_id'] = $driver_filter;
        }
        if (!empty($fuel_type_filter)) {
            $conditions['fuel_type'] = $fuel_type_filter;
        }
        if (!empty($date_from)) {
            $conditions['fuel_date >='] = $date_from;
        }
        if (!empty($date_to)) {
            $conditions['fuel_date <='] = $date_to;
        }
        
        $offset = ($page - 1) * $per_page;
        $fuelRecords = $this->fuelModel->getAllWithDetails($conditions, $offset, $per_page, $search);
        $totalRecords = $this->fuelModel->getTotalCount($conditions, $search);
        $totalPages = ceil($totalRecords / $per_page);
        
        // Obținem datele pentru filtre
        $vehicles = $this->vehicleModel->getActiveVehicles(); // Use the correct method to get active vehicles
        $drivers = $this->driverModel->getActiveDrivers();
        
        // Calculăm statistici
        $stats = $this->fuelModel->getStatistics($conditions);
        
        $data = [
            'fuelRecords' => $fuelRecords,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $per_page,
            'filters' => [
                'search' => $search,
                'vehicle' => $vehicle_filter,
                'driver' => $driver_filter,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'fuel_type' => $fuel_type_filter
            ]
        ];
        
        $this->render('list', $data);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAdd();
        } else {
            $vehicles = $this->vehicleModel->getActiveVehicles();
            $drivers = $this->driverModel->getActiveDrivers();
            $selectedVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
            
            $data = [
                'vehicles' => $vehicles,
                'drivers' => $drivers,
                'selectedVehicleId' => $selectedVehicleId
            ];
            
            $this->render('add', $data);
        }
    }
    
    private function handleAdd() {
        $data = [
            'vehicle_id' => $_POST['vehicle_id'] ?? null,
            'driver_id' => $_POST['driver_id'] ?? null,
            'fuel_date' => $_POST['fuel_date'] ?? ($_POST['fill_date'] ?? null),
            'mileage' => $_POST['mileage'] ?? ($_POST['odometer_reading'] ?? null),
            'liters' => $_POST['liters'] ?? null,
            'cost_per_liter' => $_POST['cost_per_liter'] ?? ($_POST['price_per_liter'] ?? null),
            'total_cost' => $_POST['total_cost'] ?? null,
            'fuel_type' => $_POST['fuel_type'] ?? null,
            'station' => $_POST['station'] ?? ($_POST['station_name'] ?? null),
            'location' => $_POST['location'] ?? ($_POST['station_location'] ?? null),
            'receipt_number' => $_POST['receipt_number'] ?? null,
            'is_full_tank' => isset($_POST['is_full_tank']) ? 1 : 0,
            'notes' => $_POST['notes'] ?? null
        ];
        
        // Validare
        $rules = [
            'vehicle_id' => ['required' => true],
            'driver_id' => ['required' => true],
            'fuel_date' => ['required' => true],
            'mileage' => ['required' => true, 'numeric' => true],
            'liters' => ['required' => true, 'numeric' => true, 'min' => 0.1],
            'cost_per_liter' => ['required' => true, 'numeric' => true, 'min' => 0.01],
            'fuel_type' => ['required' => true]
        ];
        
        $errors = $this->validateFuelData($data, $rules);
        
        if (!empty($errors)) {
            $_SESSION['flash'] = ['message' => implode("\n", $errors), 'type' => 'danger'];
            $_SESSION['old'] = $data;
            $this->redirect(BASE_URL . 'fuel/add');
        }
        
        // Calculăm costul total dacă nu e specificat
        if (empty($data['total_cost'])) {
            $data['total_cost'] = $data['liters'] * $data['cost_per_liter'];
        }
        
        // Calculăm consumul dacă este posibil
        // Not stored in schema; skip persisting consumption per 100km
        
        // Gestionăm fișierul chitanței dacă există
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleReceiptUpload($_FILES['receipt_file']);
            if ($uploadResult['success']) {
                $data['receipt_file'] = $uploadResult['filename'];
            } else {
                $_SESSION['flash'] = ['message' => $uploadResult['error'], 'type' => 'danger'];
                $_SESSION['old'] = $data;
                $this->redirect(BASE_URL . 'fuel/add');
            }
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        
        if ($this->fuelModel->create($data)) {
            // Actualizăm kilometrajul vehiculului
            if (!empty($data['mileage'])) {
                $this->vehicleModel->updateMileage($data['vehicle_id'], $data['mileage']);
            }
            
            $_SESSION['flash'] = ['message' => 'Înregistrarea de combustibil a fost adăugată cu succes!', 'type' => 'success'];
            if (!empty($_POST['save_and_add_another'])) {
                $this->redirect(BASE_URL . 'fuel/add?vehicle_id=' . urlencode($data['vehicle_id']));
            } else if (!empty($data['vehicle_id'])) {
                $this->redirect(BASE_URL . 'vehicles/view?id=' . urlencode($data['vehicle_id']));
            } else {
                $this->redirect(BASE_URL . 'fuel');
            }
        } else {
            $_SESSION['flash'] = ['message' => 'Eroare la salvarea înregistrării de combustibil', 'type' => 'danger'];
            $_SESSION['old'] = $data;
            $this->redirect(BASE_URL . 'fuel/add');
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/fuel/');
            exit;
        }
        
        $fuelRecord = $this->fuelModel->findById($id);
        if (!$fuelRecord) {
            $_SESSION['errors'] = ['Înregistrarea de combustibil nu a fost găsită'];
            header('Location: ' . BASE_URL . 'modules/fuel/');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
        } else {
            $vehicles = $this->vehicleModel->getActiveVehicles();
            $drivers = $this->driverModel->getActiveDrivers();
            
            $data = [
                'fuelRecord' => $fuelRecord,
                'vehicles' => $vehicles,
                'drivers' => $drivers
            ];
            
            include 'modules/fuel/views/edit.php';
        }
    }
    
    private function handleEdit($id) {
        $data = [
            'vehicle_id' => $_POST['vehicle_id'] ?? null,
            'driver_id' => $_POST['driver_id'] ?? null,
            'fill_date' => $_POST['fill_date'] ?? null,
            'fill_time' => $_POST['fill_time'] ?? null,
            'odometer_reading' => $_POST['odometer_reading'] ?? null,
            'liters' => $_POST['liters'] ?? null,
            'price_per_liter' => $_POST['price_per_liter'] ?? null,
            'total_cost' => $_POST['total_cost'] ?? null,
            'fuel_type' => $_POST['fuel_type'] ?? null,
            'station_name' => $_POST['station_name'] ?? null,
            'station_location' => $_POST['station_location'] ?? null,
            'receipt_number' => $_POST['receipt_number'] ?? null,
            'is_full_tank' => isset($_POST['is_full_tank']) ? 1 : 0,
            'notes' => $_POST['notes'] ?? null
        ];
        
        // Validare
        $rules = [
            'vehicle_id' => ['required' => true],
            'driver_id' => ['required' => true],
            'fill_date' => ['required' => true],
            'odometer_reading' => ['required' => true, 'numeric' => true],
            'liters' => ['required' => true, 'numeric' => true, 'min' => 0.1],
            'price_per_liter' => ['required' => true, 'numeric' => true, 'min' => 0.01],
            'fuel_type' => ['required' => true]
        ];
        
        $errors = $this->validateFuelData($data, $rules);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ' . BASE_URL . 'modules/fuel/views/edit.php?id=' . $id);
            exit;
        }
        
        // Calculăm costul total dacă nu e specificat
        if (empty($data['total_cost'])) {
            $data['total_cost'] = $data['liters'] * $data['price_per_liter'];
        }
        
        // Recalculăm consumul
        $consumption = $this->calculateConsumption($data['vehicle_id'], $data['odometer_reading'], $data['liters']);
        if ($consumption !== null) {
            $data['consumption_per_100km'] = $consumption;
        }
        
        // Gestionăm fișierul chitanței dacă există
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleReceiptUpload($_FILES['receipt_file']);
            if ($uploadResult['success']) {
                // Ștergem vechiul fișier dacă există
                $oldRecord = $this->fuelModel->findById($id);
                if ($oldRecord && $oldRecord['receipt_file']) {
                    $oldFile = 'uploads/receipts/' . $oldRecord['receipt_file'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $data['receipt_file'] = $uploadResult['filename'];
            } else {
                $_SESSION['errors'] = [$uploadResult['error']];
                header('Location: ' . BASE_URL . 'modules/fuel/views/edit.php?id=' . $id);
                exit;
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->fuelModel->update($id, $data)) {
            // Actualizăm kilometrajul vehiculului
            $this->vehicleModel->updateMileage($data['vehicle_id'], $data['odometer_reading']);
            
            $_SESSION['success'] = 'Înregistrarea de combustibil a fost actualizată cu succes!';
            header('Location: ' . BASE_URL . 'modules/fuel/');
        } else {
            $_SESSION['errors'] = ['Eroare la actualizarea înregistrării de combustibil'];
            header('Location: ' . BASE_URL . 'modules/fuel/views/edit.php?id=' . $id);
        }
        exit;
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/fuel/');
            exit;
        }
        
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID invalid'], 400);
            return;
        }
        
        $fuelRecord = $this->fuelModel->findById($id);
        if (!$fuelRecord) {
            $this->json(['success' => false, 'message' => 'Înregistrarea nu a fost găsită'], 404);
            return;
        }
        
        // Ștergem fișierul chitanței dacă există
        if ($fuelRecord['receipt_file']) {
            $receiptFile = 'uploads/receipts/' . $fuelRecord['receipt_file'];
            if (file_exists($receiptFile)) {
                unlink($receiptFile);
            }
        }
        
        if ($this->fuelModel->delete($id)) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => true, 'message' => 'Înregistrarea a fost ștearsă cu succes']);
            } else {
                $_SESSION['success'] = 'Înregistrarea de combustibil a fost ștearsă cu succes!';
                header('Location: ' . BASE_URL . 'modules/fuel/');
            }
        } else {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Eroare la ștergerea înregistrării'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la ștergerea înregistrării de combustibil'];
                header('Location: ' . BASE_URL . 'modules/fuel/');
            }
        }
    }
    
    public function reports() {
        // Read filters and defaults
        $vehicle_filter = $_GET['vehicle'] ?? '';
        $driver_filter  = $_GET['driver'] ?? '';
        $fuel_type      = $_GET['fuel_type'] ?? '';
        $date_from      = $_GET['date_from'] ?? date('Y-m-01'); // Prima zi a lunii curente
        $date_to        = $_GET['date_to'] ?? date('Y-m-d');   // Azi
        $report_type    = $_GET['report_type'] ?? 'overview';

        $vehicles = $this->vehicleModel->getActiveVehicles();
        $drivers  = $this->driverModel->getActiveDrivers();

        // Prepare base conditions for stats
        $conditions = [];
        if (!empty($vehicle_filter)) $conditions['vehicle_id'] = $vehicle_filter;
        if (!empty($driver_filter))  $conditions['driver_id'] = $driver_filter;
        if (!empty($fuel_type))      $conditions['fuel_type'] = $fuel_type;
        if (!empty($date_from))      $conditions['fuel_date >='] = $date_from;
        if (!empty($date_to))        $conditions['fuel_date <='] = $date_to;

        $reports = [];
        $stats = [];
        $chartData = [
            'consumption_trend' => [],
            'cost_trend' => [],
            'cost_distribution' => [],
        ];

        try {
            // Core reports
            $reports = $this->fuelModel->generateReports([
                'vehicle_id'  => $vehicle_filter,
                'driver_id'   => $driver_filter,
                'date_from'   => $date_from,
                'date_to'     => $date_to,
                'fuel_type'   => $fuel_type,
            ]);

            // High-level stats
            $stats = $this->fuelModel->getStatistics($conditions) ?: [];

            // Derived metrics for overview
            $totalDistance = 0;
            $totalLiters = (float)($stats['total_liters'] ?? 0);
            foreach (($reports['vehicle_consumption'] ?? []) as $row) {
                $totalDistance += (float)($row['distance_traveled'] ?? 0);
            }
            $avgConsumption = ($totalDistance > 0 && $totalLiters > 0)
                ? ($totalLiters / $totalDistance) * 100
                : null;
            $stats['total_distance'] = $totalDistance;
            $stats['avg_consumption'] = $avgConsumption;

            // Chart data: monthly trends
            foreach (($reports['monthly_costs'] ?? []) as $m) {
                $chartData['consumption_trend'][] = [
                    'date' => $m['month_name'] ?? $m['month'] ?? '',
                    'consumption' => (float)($m['total_liters'] ?? 0)
                ];
                $chartData['cost_trend'][] = [
                    'date' => $m['month_name'] ?? $m['month'] ?? '',
                    'cost' => (float)($m['total_cost'] ?? 0)
                ];
            }

            // Chart data: cost distribution by vehicle
            foreach (($reports['vehicle_consumption'] ?? []) as $v) {
                $chartData['cost_distribution'][] = [
                    'vehicle' => trim(($v['registration_number'] ?? '') . ' ' . (($v['brand'] ?? '') . ' ' . ($v['model'] ?? ''))),
                    'cost' => (float)($v['total_cost'] ?? 0)
                ];
            }
        } catch (\Throwable $e) {
            error_log('[FuelController::reports] ERROR: ' . $e->getMessage());
            // Safe defaults to avoid 500/404 and still show the page
            $reports = [
                'vehicle_consumption' => [],
                'driver_consumption'  => [],
                'monthly_costs'       => [],
                'efficiency'          => [],
                'stations'            => [],
                'fuel_types'          => [],
            ];
            $stats = [
                'total_liters' => 0,
                'total_cost' => 0,
                'avg_price_per_liter' => 0,
                'vehicles_count' => 0,
                'drivers_count' => 0,
                'total_distance' => 0,
                'avg_consumption' => null,
            ];
            $_SESSION['error'] = 'Nu s-a putut genera raportul de combustibil (detaliile au fost înregistrate în jurnal).';
        }

        $data = [
            'pageTitle' => 'Rapoarte Combustibil',
            'vehicles' => $vehicles,
            'drivers'  => $drivers,
            'reports'  => $reports,
            'stats'    => $stats,
            'chartData'=> $chartData,
            'report_type' => $report_type,
            'filters'  => [
                'vehicle'     => $vehicle_filter,
                'driver'      => $driver_filter,
                'fuel_type'   => $fuel_type,
                'date_from'   => $date_from,
                'date_to'     => $date_to,
                'report_type' => $report_type,
            ],
        ];

        // Standard render (header/footer and inner view)
        $this->render('reports', $data);
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'excel';
        $filters = [
            'vehicle_id' => $_GET['vehicle'] ?? '',
            'driver_id' => $_GET['driver'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'fuel_type' => $_GET['fuel_type'] ?? ''
        ];
        
        $data = $this->fuelModel->getAllWithDetails($filters);
        
        if ($format === 'excel') {
            $this->exportToExcel($data);
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data);
        } else {
            $_SESSION['errors'] = ['Format de export invalid'];
            header('Location: ' . BASE_URL . 'modules/fuel/');
        }
    }
    
    public function bulkDelete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        
        if (empty($ids) || !is_array($ids)) {
            $this->json(['success' => false, 'message' => 'Nu au fost selectate înregistrări'], 400);
            return;
        }
        
        $deletedCount = 0;
        foreach ($ids as $id) {
            $fuelRecord = $this->fuelModel->findById($id);
            if ($fuelRecord) {
                // Ștergem fișierul chitanței dacă există
                if ($fuelRecord['receipt_file']) {
                    $receiptFile = 'uploads/receipts/' . $fuelRecord['receipt_file'];
                    if (file_exists($receiptFile)) {
                        unlink($receiptFile);
                    }
                }
                
                if ($this->fuelModel->delete($id)) {
                    $deletedCount++;
                }
            }
        }
        
        $this->json([
            'success' => true,
            'message' => "Au fost șterse $deletedCount înregistrări din " . count($ids) . " selectate"
        ]);
    }
    
    public function getVehicleLastOdometer() {
        $vehicleId = $_GET['vehicle_id'] ?? null;
        if (!$vehicleId) {
            $this->json(['success' => false, 'message' => 'Vehicle ID invalid'], 400);
            return;
        }
        
        $lastOdometer = $this->fuelModel->getLastOdometerReading($vehicleId);
        $this->json(['success' => true, 'last_odometer' => $lastOdometer]);
    }
    
    public function calculateCost() {
        $liters = $_POST['liters'] ?? 0;
        $pricePerLiter = $_POST['price_per_liter'] ?? 0;
        
        $totalCost = $liters * $pricePerLiter;
        $this->json(['success' => true, 'total_cost' => number_format($totalCost, 2)]);
    }
    
    private function validateFuelData($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[] = "Câmpul '$field' este obligatoriu";
                continue;
            }
            
            if (!empty($value)) {
                if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                    $errors[] = "Câmpul '$field' trebuie să fie numeric";
                }
                
                if (isset($rule['min']) && is_numeric($value) && $value < $rule['min']) {
                    $errors[] = "Câmpul '$field' trebuie să fie mai mare sau egal cu " . $rule['min'];
                }
                
                if (isset($rule['max']) && is_numeric($value) && $value > $rule['max']) {
                    $errors[] = "Câmpul '$field' trebuie să fie mai mic sau egal cu " . $rule['max'];
                }
            }
        }
        
        // Validări specifice
        if (!empty($data['fuel_date']) && strtotime($data['fuel_date']) > time()) {
            $errors[] = 'Data alimentării nu poate fi în viitor';
        }
        
        if (!empty($data['vehicle_id']) && !empty($data['mileage'])) {
            $lastOdometer = $this->fuelModel->getLastOdometerReading($data['vehicle_id']);
            if ($lastOdometer && $data['mileage'] < $lastOdometer) {
                $errors[] = "Kilometrajul nu poate fi mai mic decât ultima înregistrare ($lastOdometer km)";
            }
        }
        
        return $errors;
    }
    
    private function calculateConsumption($vehicleId, $currentOdometer, $liters) {
        $lastRecord = $this->fuelModel->getLastFuelRecord($vehicleId, $currentOdometer);
        
        if (!$lastRecord || !is_array($lastRecord) || empty($lastRecord['is_full_tank'])) {
            return null;
        }
        
        $distance = $currentOdometer - $lastRecord['mileage'];
        if ($distance <= 0) {
            return null;
        }
        
        return ($liters / $distance) * 100;
    }
    
    private function handleReceiptUpload($file) {
        $uploadDir = 'uploads/receipts/';
        
        // Creăm directorul dacă nu există
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validăm fișierul
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Format de fișier neacceptat. Folosește JPG, PNG sau PDF.'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fișierul este prea mare. Dimensiunea maximă este 10MB.'];
        }
        
        // Generăm numele fișierului
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => 'Eroare la încărcarea fișierului.'];
        }
    }
    
    private function exportToExcel($data) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="fuel_records_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Data</th>";
        echo "<th>Vehicul</th>";
        echo "<th>Șofer</th>";
        echo "<th>Kilometraj</th>";
        echo "<th>Litri</th>";
        echo "<th>Preț/Litru</th>";
        echo "<th>Cost Total</th>";
        echo "<th>Tip Combustibil</th>";
        echo "<th>Stație</th>";
        // no stored consumption column in schema
        echo "</tr>";
        
        foreach ($data as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['fuel_date']) . "</td>";
            echo "<td>" . htmlspecialchars($record['registration_number']) . "</td>";
            echo "<td>" . htmlspecialchars($record['driver_name']) . "</td>";
            echo "<td>" . htmlspecialchars($record['mileage']) . "</td>";
            echo "<td>" . htmlspecialchars($record['liters']) . "</td>";
            echo "<td>" . htmlspecialchars($record['cost_per_liter']) . "</td>";
            echo "<td>" . htmlspecialchars($record['total_cost']) . "</td>";
            echo "<td>" . htmlspecialchars($record['fuel_type']) . "</td>";
            echo "<td>" . htmlspecialchars($record['station']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit;
    }
    
    private function exportToPDF($data) {
        // Implementare simplă pentru PDF - poate fi îmbunătățită cu o bibliotecă dedicată
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="fuel_records_' . date('Y-m-d') . '.pdf"');
        
        // Pentru o implementare completă, ar trebui folosită o bibliotecă ca TCPDF sau FPDF
        echo "PDF export nu este încă implementat complet. Folosește exportul Excel.";
        exit;
    }
    
    
    protected function getModuleName() {
        return 'fuel';
    }
}
?>
