
<?php

class MaintenanceController extends Controller {
    private $maintenanceModel;
    private $vehicleModel;
    private $driverModel;
    
    public function __construct() {
        parent::__construct();
        $this->maintenanceModel = new Maintenance();
        $this->vehicleModel = new Vehicle();
        $this->driverModel = new Driver();
    }
    
    public function index() {
        // Get filter parameters
        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $vehicle_filter = $_GET['vehicle'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $type_filter = $_GET['type'] ?? '';
        $priority_filter = $_GET['priority'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 25);
        $sort_by = $_GET['sort_by'] ?? 'service_date';
        $sort_order = $_GET['sort_order'] ?? 'DESC';
        
        // Build conditions
        $conditions = [];
        if (!empty($vehicle_filter)) $conditions['vehicle_id'] = $vehicle_filter;
        if (!empty($status_filter)) $conditions['status'] = $status_filter;
        if (!empty($type_filter)) $conditions['maintenance_type'] = $type_filter;
        if (!empty($priority_filter)) $conditions['priority'] = $priority_filter;
        if (!empty($date_from)) $conditions['service_date >='] = $date_from;
        if (!empty($date_to)) $conditions['service_date <='] = $date_to;
        
        $offset = ($page - 1) * $per_page;
        $maintenanceRecords = $this->maintenanceModel->getAllWithDetails($conditions, $offset, $per_page, $search, $sort_by, $sort_order);
        $totalRecords = $this->maintenanceModel->getTotalCount($conditions, $search);
        $totalPages = ceil($totalRecords / $per_page);
        
        $this->render('list', [
            'maintenanceRecords' => $maintenanceRecords,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'vehicles' => $this->vehicleModel->getAllWithType(),
            'summaryStats' => $this->maintenanceModel->getSummaryStats(),
            'filters' => [
                'search' => $search,
                'vehicle' => $vehicle_filter,
                'status' => $status_filter,
                'type' => $type_filter,
                'priority' => $priority_filter,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ]
        ]);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleAdd();
        }
        
        // Pre-select vehicle if coming from vehicle details
        $selectedVehicleId = $_GET['vehicle_id'] ?? null;
        
        $this->render('add', [
            'vehicles' => $this->vehicleModel->getAllWithType(),
            'selectedVehicleId' => $selectedVehicleId
        ]);
    }
    
    private function handleAdd() {
        try {
            // Validate required fields
            $required = ['vehicle_id', 'maintenance_type', 'scheduled_date', 'priority'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Câmpul {$field} este obligatoriu");
                }
            }
            
            // Check vehicle exists
            $vehicle = $this->vehicleModel->getById($_POST['vehicle_id']);
            if (!$vehicle) {
                throw new Exception('Vehiculul selectat nu există');
            }
            
            // Validate dates
            if (!empty($_POST['scheduled_date'])) {
                $scheduledDate = strtotime($_POST['scheduled_date']);
                if (!$scheduledDate) {
                    throw new Exception('Data programată nu este validă');
                }
            }
            
            // Validate cost
            $cost = !empty($_POST['estimated_cost']) ? floatval($_POST['estimated_cost']) : 0;
            if ($cost < 0) {
                throw new Exception('Costul nu poate fi negativ');
            }
            
            // Prepare data
            $data = [
                'vehicle_id' => $_POST['vehicle_id'],
                'maintenance_type' => $_POST['maintenance_type'],
                'description' => $_POST['description'] ?? '',
                'service_date' => $_POST['scheduled_date'], // Map to service_date
                'mileage_at_service' => !empty($_POST['odometer_reading']) ? intval($_POST['odometer_reading']) : null,
                'next_service_date' => null,
                'next_service_mileage' => null,
                'provider' => '',
                'cost' => $cost,
                'priority' => $_POST['priority'],
                'status' => 'scheduled',
                'notes' => '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $maintenanceId = $this->maintenanceModel->create($data);
            
            if ($maintenanceId) {
                $this->setFlashMessage('Înregistrarea de mentenanță a fost adăugată cu succes', 'success');
                header('Location: ' . BASE_URL . 'maintenance');
                exit;
            } else {
                throw new Exception('Eroare la salvarea înregistrării');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('Eroare: ' . $e->getMessage(), 'error');
            return [
                'error' => $e->getMessage(),
                'vehicles' => $this->vehicleModel->getAllWithType(),
                'formData' => $_POST
            ];
        }
    }
    
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleEdit($id);
        }
        
        $maintenance = $this->maintenanceModel->getById($id);
        if (!$maintenance) {
            $this->setFlashMessage('Înregistrarea de mentenanță nu a fost găsită', 'error');
            header('Location: ' . BASE_URL . 'maintenance');
            exit;
        }
        
        $this->render('edit', [
            'maintenance' => $maintenance,
            'vehicles' => $this->vehicleModel->getAllWithType()
        ]);
    }
    
    private function handleEdit($id) {
        try {
            $maintenance = $this->maintenanceModel->getById($id);
            if (!$maintenance) {
                throw new Exception('Înregistrarea de mentenanță nu a fost găsită');
            }
            
            // Validate required fields
            $required = ['vehicle_id', 'maintenance_type', 'scheduled_date', 'priority'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Câmpul {$field} este obligatoriu");
                }
            }
            
            // Validate vehicle exists
            $vehicle = $this->vehicleModel->getById($_POST['vehicle_id']);
            if (!$vehicle) {
                throw new Exception('Vehiculul selectat nu există');
            }
            
            // Prepare data
            $data = [
                'vehicle_id' => $_POST['vehicle_id'],
                'maintenance_type' => $_POST['maintenance_type'],
                'description' => $_POST['description'] ?? '',
                'service_date' => $_POST['scheduled_date'],
                'mileage_at_service' => $_POST['odometer_reading'] ?? null,
                'next_service_mileage' => $_POST['next_service_km'] ?? null,
                'next_service_date' => $_POST['next_service_date'] ?? null,
                'provider' => $_POST['service_provider'] ?? '',
                'cost' => $_POST['estimated_cost'] ?? ($_POST['cost'] ?? 0),
                'priority' => $_POST['priority'],
                'status' => $_POST['status'] ?? 'scheduled',
                'notes' => $_POST['notes'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->maintenanceModel->update($id, $data);
            
            if ($result) {
                $this->setFlashMessage('Înregistrarea de mentenanță a fost actualizată cu succes', 'success');
                header('Location: ' . BASE_URL . 'maintenance');
                exit;
            } else {
                throw new Exception('Eroare la actualizarea înregistrării');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('Eroare: ' . $e->getMessage(), 'error');
            $this->render('edit', [
                'error' => $e->getMessage(),
                'maintenance' => $this->maintenanceModel->getById($id),
                'vehicles' => $this->vehicleModel->getAllWithType(),
                'formData' => $_POST
            ]);
        }
    }
    
    public function view($id) {
        $maintenance = $this->maintenanceModel->getByIdWithDetails($id);
        if (!$maintenance) {
            $this->setFlashMessage('Înregistrarea de mentenanță nu a fost găsită', 'error');
            $this->redirect('/modules/maintenance/views/list.php');
        }
        
        // Get maintenance history for this vehicle
        $maintenanceHistory = $this->maintenanceModel->getVehicleHistory($maintenance['vehicle_id']);
        
        return [
            'maintenance' => $maintenance,
            'maintenanceHistory' => $maintenanceHistory
        ];
    }
    
    public function delete() {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        try {
            $maintenance = $this->maintenanceModel->getById($id);
            if (!$maintenance) {
                throw new Exception('Înregistrarea de mentenanță nu a fost găsită');
            }
            
            $result = $this->maintenanceModel->delete($id);
            
            if ($result) {
                $this->setFlashMessage('Înregistrarea de mentenanță a fost ștearsă cu succes', 'success');
            } else {
                throw new Exception('Eroare la ștergerea înregistrării');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('Eroare: ' . $e->getMessage(), 'error');
        }
        
        header('Location: ' . BASE_URL . 'maintenance');
        exit;
    }
    
    public function history($vehicleId) {
        $vehicle = $this->vehicleModel->find($vehicleId);
        if (!$vehicle) {
            $this->setFlashMessage('Vehiculul nu a fost găsit', 'error');
            $this->redirect('/modules/maintenance/views/list.php');
        }
        
        $maintenanceHistory = $this->maintenanceModel->getVehicleHistory($vehicleId);
        $upcomingMaintenance = $this->maintenanceModel->getUpcomingMaintenance($vehicleId);
        $maintenanceStats = $this->maintenanceModel->getVehicleMaintenanceStats($vehicleId);
        
        return [
            'vehicle' => $vehicle,
            'maintenanceHistory' => $maintenanceHistory,
            'upcomingMaintenance' => $upcomingMaintenance,
            'maintenanceStats' => $maintenanceStats
        ];
    }
    
    public function upcoming() {
        $upcomingMaintenance = $this->maintenanceModel->getUpcomingMaintenanceAll();
        $overdueMaintenance = $this->maintenanceModel->getOverdueMaintenance();
        
        return [
            'upcomingMaintenance' => $upcomingMaintenance,
            'overdueMaintenance' => $overdueMaintenance
        ];
    }
    
    public function reports() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->generateReports();
        }
        
        return [
            'vehicles' => $this->vehicleModel->getActiveVehicles(),
            'maintenanceTypes' => $this->maintenanceModel->getMaintenanceTypes()
        ];
    }
    
    private function generateReports() {
        try {
            $reportType = $_POST['report_type'] ?? 'cost_analysis';
            $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
            $dateTo = $_POST['date_to'] ?? date('Y-m-t');
            $vehicleId = $_POST['vehicle_id'] ?? null;
            
            $reportData = [];
            
            switch ($reportType) {
                case 'cost_analysis':
                    $reportData = $this->maintenanceModel->getCostAnalysis($dateFrom, $dateTo, $vehicleId);
                    break;
                case 'maintenance_schedule':
                    $reportData = $this->maintenanceModel->getMaintenanceScheduleReport($dateFrom, $dateTo, $vehicleId);
                    break;
                case 'vehicle_performance':
                    $reportData = $this->maintenanceModel->getVehiclePerformanceReport($dateFrom, $dateTo, $vehicleId);
                    break;
                case 'provider_analysis':
                    $reportData = $this->maintenanceModel->getProviderAnalysis($dateFrom, $dateTo, $vehicleId);
                    break;
            }
            
            return [
                'reportData' => $reportData,
                'reportType' => $reportType,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'vehicleId' => $vehicleId,
                'vehicles' => $this->vehicleModel->getActiveVehicles(),
                'maintenanceTypes' => $this->maintenanceModel->getMaintenanceTypes()
            ];
        } catch (Exception $e) {
            $this->setFlashMessage('Eroare la generarea raportului: ' . $e->getMessage(), 'error');
            return [
                'error' => $e->getMessage(),
                'vehicles' => $this->vehicleModel->getActiveVehicles(),
                'maintenanceTypes' => $this->maintenanceModel->getMaintenanceTypes()
            ];
        }
    }
    
    public function export() {
        try {
            $format = $_POST['format'] ?? 'excel';
            $conditions = [];
            
            // Build conditions from filters
            if (!empty($_POST['vehicle_id'])) $conditions['vehicle_id'] = $_POST['vehicle_id'];
            if (!empty($_POST['status'])) $conditions['status'] = $_POST['status'];
            if (!empty($_POST['date_from'])) $conditions['scheduled_date >='] = $_POST['date_from'];
            if (!empty($_POST['date_to'])) $conditions['scheduled_date <='] = $_POST['date_to'];
            
            $maintenanceRecords = $this->maintenanceModel->getAllForExport($conditions);
            
            switch ($format) {
                case 'excel':
                    $this->exportToExcel($maintenanceRecords);
                    break;
                case 'pdf':
                    $this->exportToPDF($maintenanceRecords);
                    break;
                case 'csv':
                    $this->exportToCSV($maintenanceRecords);
                    break;
            }
        } catch (Exception $e) {
            $this->setFlashMessage('Eroare la export: ' . $e->getMessage(), 'error');
            $this->redirect('/modules/maintenance/views/list.php');
        }
    }
    
    private function exportToExcel($data) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="maintenance_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Vehicul</th>";
        echo "<th>Tip Mentenanță</th>";
        echo "<th>Descriere</th>";
        echo "<th>Data Programată</th>";
        echo "<th>Data Finalizării</th>";
        echo "<th>Kilometraj</th>";
        echo "<th>Furnizor</th>";
        echo "<th>Cost Total</th>";
        echo "<th>Status</th>";
        echo "<th>Prioritate</th>";
        echo "</tr>";
        
        foreach ($data as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['license_plate']) . "</td>";
            echo "<td>" . htmlspecialchars($record['maintenance_type']) . "</td>";
            echo "<td>" . htmlspecialchars($record['description']) . "</td>";
            echo "<td>" . date('d.m.Y', strtotime($record['scheduled_date'])) . "</td>";
            echo "<td>" . ($record['completed_date'] ? date('d.m.Y', strtotime($record['completed_date'])) : '') . "</td>";
            echo "<td>" . number_format($record['odometer_at_service']) . "</td>";
            echo "<td>" . htmlspecialchars($record['service_provider']) . "</td>";
            echo "<td>" . number_format($record['cost'], 2) . " RON</td>";
            echo "<td>" . htmlspecialchars($record['status']) . "</td>";
            echo "<td>" . htmlspecialchars($record['priority']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }
    
    private function exportToCSV($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="maintenance_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header
        fputcsv($output, [
            'Vehicul', 'Tip Mentenanță', 'Descriere', 'Data Programată', 
            'Data Finalizării', 'Kilometraj', 'Furnizor', 'Cost Total', 'Status', 'Prioritate'
        ]);
        
        foreach ($data as $record) {
            fputcsv($output, [
                $record['license_plate'],
                $record['maintenance_type'],
                $record['description'],
                date('d.m.Y', strtotime($record['scheduled_date'])),
                $record['completed_date'] ? date('d.m.Y', strtotime($record['completed_date'])) : '',
                number_format($record['odometer_at_service']),
                $record['service_provider'],
                number_format($record['cost'], 2) . ' RON',
                $record['status'],
                $record['priority']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportToPDF($data) {
        // Simplified PDF export - in a real application, use a library like TCPDF or FPDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="maintenance_' . date('Y-m-d') . '.pdf"');
        
        // For now, output HTML that browsers can print to PDF
        echo "<!DOCTYPE html><html><head><title>Raport Mentenanță</title>";
        echo "<style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>";
        echo "</head><body>";
        echo "<h1>Raport Mentenanță - " . date('d.m.Y') . "</h1>";
        echo "<table>";
        echo "<tr><th>Vehicul</th><th>Tip</th><th>Data</th><th>Cost</th><th>Status</th></tr>";
        
        foreach ($data as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['license_plate']) . "</td>";
            echo "<td>" . htmlspecialchars($record['maintenance_type']) . "</td>";
            echo "<td>" . date('d.m.Y', strtotime($record['scheduled_date'])) . "</td>";
            echo "<td>" . number_format($record['cost'], 2) . " RON</td>";
            echo "<td>" . htmlspecialchars($record['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table></body></html>";
        exit;
    }
    
    private function createMaintenanceNotification($vehicleId, $nextServiceDate, $maintenanceType) {
        // Create notification 7 days before maintenance
        $notificationDate = date('Y-m-d', strtotime($nextServiceDate . ' -7 days'));
        
        if ($notificationDate >= date('Y-m-d')) {
            // Check if notification model exists
            $notificationModelPath = '../../notifications/models/Notification.php';
            if (file_exists($notificationModelPath)) {
                require_once $notificationModelPath;
                
                if (class_exists('Notification')) {
                    $notificationModel = new Notification();
                    
                    $vehicle = $this->vehicleModel->find($vehicleId);
                    $message = "Mentenanța '{$maintenanceType}' pentru vehiculul {$vehicle['license_plate']} este programată pentru " . date('d.m.Y', strtotime($nextServiceDate));
                    
                    $notificationModel->create([
                        'type' => 'maintenance_reminder',
                        'title' => 'Memento Mentenanță',
                        'message' => $message,
                        'vehicle_id' => $vehicleId,
                        'scheduled_date' => $notificationDate,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
    
    // AJAX endpoints
    public function ajaxHandler() {
        if (!isset($_POST['action'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Acțiune nespecificată']);
        }
        
        switch ($_POST['action']) {
            case 'delete':
                $this->ajaxDelete();
                break;
            case 'bulkDelete':
                $this->ajaxBulkDelete();
                break;
            case 'updateStatus':
                $this->ajaxUpdateStatus();
                break;
            case 'getMaintenanceDetails':
                $this->ajaxGetMaintenanceDetails();
                break;
            case 'calculateNextService':
                $this->ajaxCalculateNextService();
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Acțiune necunoscută']);
        }
    }
    
    private function ajaxDelete() {
        try {
            $id = $_POST['id'] ?? 0;
            $maintenance = $this->maintenanceModel->getById($id);
            
            if (!$maintenance) {
                throw new Exception('Înregistrarea nu a fost găsită');
            }
            
            // Delete file if exists
            if (!empty($maintenance['receipt_file'])) {
                $this->deleteFile($maintenance['receipt_file'], 'maintenance');
            }
            
            $result = $this->maintenanceModel->delete($id);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Înregistrarea a fost ștearsă cu succes']);
            } else {
                throw new Exception('Eroare la ștergerea înregistrării');
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function ajaxBulkDelete() {
        try {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) {
                throw new Exception('Nu au fost selectate înregistrări');
            }
            
            $deletedCount = 0;
            foreach ($ids as $id) {
                $maintenance = $this->maintenanceModel->getById($id);
                if ($maintenance) {
                    // Delete file if exists
                    if (!empty($maintenance['receipt_file'])) {
                        $this->deleteFile($maintenance['receipt_file'], 'maintenance');
                    }
                    
                    if ($this->maintenanceModel->delete($id)) {
                        $deletedCount++;
                    }
                }
            }
            
            $this->jsonResponse([
                'success' => true, 
                'message' => "Au fost șterse {$deletedCount} înregistrări"
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function ajaxUpdateStatus() {
        try {
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if (!in_array($status, ['scheduled', 'in_progress', 'completed', 'cancelled'])) {
                throw new Exception('Status invalid');
            }
            
            $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            
            if ($status === 'completed' && empty($_POST['completed_date'])) {
                $data['completed_date'] = date('Y-m-d');
            }
            
            $result = $this->maintenanceModel->update($id, $data);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Statusul a fost actualizat']);
            } else {
                throw new Exception('Eroare la actualizarea statusului');
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function ajaxGetMaintenanceDetails() {
        try {
            $id = $_POST['id'] ?? 0;
            $maintenance = $this->maintenanceModel->getByIdWithDetails($id);
            
            if (!$maintenance) {
                throw new Exception('Înregistrarea nu a fost găsită');
            }
            
            $this->jsonResponse(['success' => true, 'data' => $maintenance]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    private function ajaxCalculateNextService() {
        try {
            $maintenanceType = $_POST['maintenance_type'] ?? '';
            $currentDate = $_POST['current_date'] ?? date('Y-m-d');
            $currentKm = (int)($_POST['current_km'] ?? 0);
            
            // Standard maintenance intervals
            $intervals = [
                'oil_change' => ['months' => 6, 'km' => 10000],
                'filter_change' => ['months' => 12, 'km' => 15000],
                'tire_rotation' => ['months' => 6, 'km' => 10000],
                'brake_check' => ['months' => 12, 'km' => 20000],
                'transmission' => ['months' => 24, 'km' => 50000],
                'inspection' => ['months' => 12, 'km' => 0],
                'other' => ['months' => 12, 'km' => 15000]
            ];
            
            $interval = $intervals[$maintenanceType] ?? $intervals['other'];
            
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +' . $interval['months'] . ' months'));
            $nextKm = $currentKm + $interval['km'];
            
            $this->jsonResponse([
                'success' => true,
                'next_service_date' => $nextDate,
                'next_service_km' => $nextKm
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Helper methods that should be in the base Controller class
    protected function setFlashMessage($message, $type = 'info') {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function handleFileUpload($file, $directory) {
        if (empty($file['name'])) {
            return '';
        }
        
        $uploadDir = "../../../uploads/$directory/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception('Tip de fișier neacceptat');
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Fișierul este prea mare');
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        } else {
            throw new Exception('Eroare la încărcarea fișierului');
        }
    }
    
    protected function deleteFile($fileName, $directory) {
        if (empty($fileName)) {
            return;
        }
        
        $filePath = "../../../uploads/$directory/$fileName";
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

// Handle direct requests only when this file is executed directly, not when included by index.php
$executedDirect = isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);
if ($executedDirect && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new MaintenanceController();
    $controller->ajaxHandler();
}
?>
