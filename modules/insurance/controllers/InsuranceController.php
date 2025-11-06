<?php
// modules/insurance/controllers/InsuranceController.php

class InsuranceController extends Controller {
    private $insuranceModel;
    private $vehicleModel;
    
    public function __construct() {
        parent::__construct();
        $this->insuranceModel = new Insurance();
        $this->vehicleModel = new Vehicle();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $vehicle_filter = $_GET['vehicle'] ?? '';
        $type_filter = $_GET['type'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $expiry_filter = $_GET['expiry'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 25);
        
        $conditions = [];
        
        // Aplicăm filtrele
        if (!empty($vehicle_filter)) {
            $conditions['vehicle_id'] = $vehicle_filter;
        }
        if (!empty($type_filter)) {
            $conditions['insurance_type'] = $type_filter;
        }
        if (!empty($status_filter)) {
            $conditions['status'] = $status_filter;
        }
        if (!empty($expiry_filter)) {
            switch ($expiry_filter) {
                case 'expired':
                    $conditions['expiry_date <'] = date('Y-m-d');
                    break;
                case 'expiring_soon':
                    $conditions['expiry_date >='] = date('Y-m-d');
                    $conditions['expiry_date <='] = date('Y-m-d', strtotime('+30 days'));
                    break;
                case 'valid':
                    $conditions['expiry_date >'] = date('Y-m-d');
                    break;
            }
        }
        
        $offset = ($page - 1) * $per_page;
        $insuranceRecords = $this->insuranceModel->getAllWithDetails($conditions, $offset, $per_page, $search);
        $totalRecords = $this->insuranceModel->getTotalCount($conditions, $search);
        $totalPages = ceil($totalRecords / $per_page);
        
        // Obținem datele pentru filtre
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Calculăm statistici
        $stats = $this->insuranceModel->getStatistics();
        
        $data = [
            'insuranceRecords' => $insuranceRecords,
            'vehicles' => $vehicles,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $per_page,
            'filters' => [
                'search' => $search,
                'vehicle' => $vehicle_filter,
                'type' => $type_filter,
                'status' => $status_filter,
                'expiry' => $expiry_filter
            ]
        ];
        
        include 'modules/insurance/views/list.php';
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAdd();
        } else {
            $vehicles = $this->vehicleModel->getActiveVehicles();
            
            $data = [
                'vehicles' => $vehicles
            ];
            
            include 'modules/insurance/views/add.php';
        }
    }
    
    private function handleAdd() {
        $data = [
            'vehicle_id' => $_POST['vehicle_id'] ?? null,
            'insurance_type' => $_POST['insurance_type'] ?? null,
            'policy_number' => $_POST['policy_number'] ?? null,
            'insurance_company' => $_POST['insurance_company'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'expiry_date' => $_POST['expiry_date'] ?? null,
            'coverage_amount' => $_POST['coverage_amount'] ?? null,
            'premium_amount' => $_POST['premium_amount'] ?? null,
            'deductible' => $_POST['deductible'] ?? null,
            'payment_frequency' => $_POST['payment_frequency'] ?? null,
            'agent_name' => $_POST['agent_name'] ?? null,
            'agent_phone' => $_POST['agent_phone'] ?? null,
            'agent_email' => $_POST['agent_email'] ?? null,
            'coverage_details' => $_POST['coverage_details'] ?? null,
            'status' => $_POST['status'] ?? 'active',
            'notes' => $_POST['notes'] ?? null
        ];
        
        // Validare
        $errors = $this->validateInsuranceData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/modules/insurance/views/add.php');
            return;
        }
        
        // Gestionăm fișierul poliței dacă există
        if (isset($_FILES['policy_file']) && $_FILES['policy_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadFile($_FILES['policy_file'], 'policies');
            if ($uploadResult['success']) {
                $data['policy_file'] = $uploadResult['file_path'];
            } else {
                $_SESSION['errors'] = [$uploadResult['error']];
                $_SESSION['old'] = $data;
                $this->redirect('/modules/insurance/views/add.php');
                return;
            }
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->insuranceModel->create($data)) {
            $_SESSION['success'] = 'Polița de asigurare a fost adăugată cu succes!';
            $this->redirect('/modules/insurance/');
        } else {
            $_SESSION['errors'] = ['Eroare la salvarea poliței de asigurare'];
            $_SESSION['old'] = $data;
            $this->redirect('/modules/insurance/views/add.php');
        }
    }
    
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/modules/insurance/');
            return;
        }
        
        $insurance = $this->insuranceModel->getByIdWithDetails($id);
        if (!$insurance || empty($insurance)) {
            $_SESSION['errors'] = ['Polița de asigurare nu a fost găsită'];
            $this->redirect('/modules/insurance/');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
        } else {
            $vehicles = $this->vehicleModel->getActiveVehicles();
            
            $data = [
                'insurance' => $insurance,
                'vehicles' => $vehicles
            ];
            
            include 'modules/insurance/views/edit.php';
        }
    }
    
    private function handleEdit($id) {
        $data = [
            'vehicle_id' => $_POST['vehicle_id'] ?? null,
            'insurance_type' => $_POST['insurance_type'] ?? null,
            'policy_number' => $_POST['policy_number'] ?? null,
            'insurance_company' => $_POST['insurance_company'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'expiry_date' => $_POST['expiry_date'] ?? null,
            'coverage_amount' => $_POST['coverage_amount'] ?? null,
            'premium_amount' => $_POST['premium_amount'] ?? null,
            'deductible' => $_POST['deductible'] ?? null,
            'payment_frequency' => $_POST['payment_frequency'] ?? null,
            'agent_name' => $_POST['agent_name'] ?? null,
            'agent_phone' => $_POST['agent_phone'] ?? null,
            'agent_email' => $_POST['agent_email'] ?? null,
            'coverage_details' => $_POST['coverage_details'] ?? null,
            'status' => $_POST['status'] ?? 'active',
            'notes' => $_POST['notes'] ?? null
        ];
        
        // Validare
        $errors = $this->validateInsuranceData($data, $id);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/modules/insurance/views/edit.php?id=' . $id);
            return;
        }
        
        // Gestionăm fișierul poliței dacă există
        if (isset($_FILES['policy_file']) && $_FILES['policy_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadFile($_FILES['policy_file'], 'policies');
            if ($uploadResult['success']) {
                // Ștergem vechiul fișier dacă există
                $oldRecord = $this->insuranceModel->getById($id);
                if ($oldRecord && $oldRecord['policy_file']) {
                    $filePath = 'uploads/' . $oldRecord['policy_file'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $data['policy_file'] = $uploadResult['file_path'];
            } else {
                $_SESSION['errors'] = [$uploadResult['error']];
                $this->redirect('/modules/insurance/views/edit.php?id=' . $id);
                return;
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->insuranceModel->update($id, $data)) {
            $_SESSION['success'] = 'Polița de asigurare a fost actualizată cu succes!';
            $this->redirect('/modules/insurance/');
        } else {
            $_SESSION['errors'] = ['Eroare la actualizarea poliței de asigurare'];
            $this->redirect('/modules/insurance/views/edit.php?id=' . $id);
        }
    }
    
    public function view() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/modules/insurance/');
            return;
        }
        
        $insurance = $this->insuranceModel->getByIdWithDetails($id);
        if (!$insurance) {
            $_SESSION['errors'] = ['Polița de asigurare nu a fost găsită'];
            $this->redirect('/modules/insurance/');
            return;
        }
        
        // Calculăm zile până la expirare
        $daysUntilExpiry = null;
        if ($insurance['expiry_date']) {
            $expiryDate = new DateTime($insurance['expiry_date']);
            $today = new DateTime();
            $daysUntilExpiry = $today->diff($expiryDate)->days;
            if ($expiryDate < $today) {
                $daysUntilExpiry = -$daysUntilExpiry;
            }
        }
        
        $data = [
            'insurance' => $insurance,
            'daysUntilExpiry' => $daysUntilExpiry
        ];
        
        include 'modules/insurance/views/view.php';
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/modules/insurance/');
            return;
        }
        
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID invalid'], 400);
            return;
        }
        
        $insurance = $this->insuranceModel->getById($id);
        if (!$insurance) {
            $this->json(['success' => false, 'message' => 'Polița nu a fost găsită'], 404);
            return;
        }
        
        // Ștergem fișierul poliței dacă există
        if ($insurance['policy_file']) {
            $filePath = 'uploads/' . $insurance['policy_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        if ($this->insuranceModel->delete($id)) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => true, 'message' => 'Polița a fost ștearsă cu succes']);
            } else {
                $_SESSION['success'] = 'Polița de asigurare a fost ștearsă cu succes!';
                $this->redirect('/modules/insurance/');
            }
        } else {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Eroare la ștergerea poliței'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la ștergerea poliței de asigurare'];
                $this->redirect('/modules/insurance/');
            }
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
            $this->json(['success' => false, 'message' => 'Nu au fost selectate polițe'], 400);
            return;
        }
        
        $deletedCount = 0;
        foreach ($ids as $id) {
            $insurance = $this->insuranceModel->getById($id);
            if ($insurance) {
                // Ștergem fișierul poliței dacă există
                if ($insurance['policy_file']) {
                    $filePath = 'uploads/' . $insurance['policy_file'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                if ($this->insuranceModel->delete($id)) {
                    $deletedCount++;
                }
            }
        }
        
        $this->json([
            'success' => true,
            'message' => "Au fost șterse $deletedCount polițe din " . count($ids) . " selectate"
        ]);
    }
    
    public function getExpiringInsurance() {
        $days = $_GET['days'] ?? 30;
        $expiring = $this->insuranceModel->getExpiringInsurance($days);
        
        $data = [
            'expiring' => $expiring,
            'days' => $days
        ];
        
        include 'modules/insurance/views/expiring.php';
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'excel';
        $filters = [
            'vehicle_id' => $_GET['vehicle'] ?? '',
            'insurance_type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $data = $this->insuranceModel->getAllForExport($filters);
        
        if ($format === 'excel') {
            $this->exportToExcel($data);
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data);
        } else {
            $_SESSION['errors'] = ['Format de export invalid'];
            $this->redirect('/modules/insurance/');
        }
    }
    
    private function validateInsuranceData($data, $excludeId = null) {
        $errors = [];
        
        // Validări obligatorii
        if (empty($data['vehicle_id'])) {
            $errors[] = 'Vehiculul este obligatoriu';
        }
        if (empty($data['insurance_type'])) {
            $errors[] = 'Tipul de asigurare este obligatoriu';
        }
        if (empty($data['policy_number'])) {
            $errors[] = 'Numărul poliței este obligatoriu';
        }
        if (empty($data['insurance_company'])) {
            $errors[] = 'Compania de asigurări este obligatorie';
        }
        if (empty($data['start_date'])) {
            $errors[] = 'Data de început este obligatorie';
        }
        if (empty($data['expiry_date'])) {
            $errors[] = 'Data de expirare este obligatorie';
        }
        
        // Validări specifice
        if (!empty($data['start_date']) && !empty($data['expiry_date'])) {
            if (strtotime($data['start_date']) >= strtotime($data['expiry_date'])) {
                $errors[] = 'Data de expirare trebuie să fie după data de început';
            }
        }
        
        if (!empty($data['coverage_amount']) && !is_numeric($data['coverage_amount'])) {
            $errors[] = 'Suma asigurată trebuie să fie un număr';
        }
        
        if (!empty($data['premium_amount']) && !is_numeric($data['premium_amount'])) {
            $errors[] = 'Prima de asigurare trebuie să fie un număr';
        }
        
        if (!empty($data['deductible']) && !is_numeric($data['deductible'])) {
            $errors[] = 'Deductibilul trebuie să fie un număr';
        }
        
        if (!empty($data['agent_email']) && !filter_var($data['agent_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresa de email a agentului nu este validă';
        }
        
        // Verificăm unicitatea numărului poliței
        if (!empty($data['policy_number'])) {
            $existing = $this->insuranceModel->findByPolicyNumber($data['policy_number'], $excludeId);
            if ($existing) {
                $errors[] = 'Numărul poliței există deja în sistem';
            }
        }
        
        return $errors;
    }
    
    private function exportToExcel($data) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="insurance_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Vehicul</th>";
        echo "<th>Tip Asigurare</th>";
        echo "<th>Număr Poliță</th>";
        echo "<th>Companie</th>";
        echo "<th>Data Început</th>";
        echo "<th>Data Expirare</th>";
        echo "<th>Sumă Asigurată</th>";
        echo "<th>Primă</th>";
        echo "<th>Status</th>";
        echo "</tr>";
        
        foreach ($data as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['vehicle_info']) . "</td>";
            echo "<td>" . htmlspecialchars($record['insurance_type']) . "</td>";
            echo "<td>" . htmlspecialchars($record['policy_number']) . "</td>";
            echo "<td>" . htmlspecialchars($record['insurance_company']) . "</td>";
            echo "<td>" . htmlspecialchars($record['start_date']) . "</td>";
            echo "<td>" . htmlspecialchars($record['expiry_date']) . "</td>";
            echo "<td>" . htmlspecialchars($record['coverage_amount']) . "</td>";
            echo "<td>" . htmlspecialchars($record['premium_amount']) . "</td>";
            echo "<td>" . htmlspecialchars($record['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit;
    }
    
    private function exportToPDF($data) {
        // Implementare simplă pentru PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="insurance_' . date('Y-m-d') . '.pdf"');
        
        echo "PDF export nu este încă implementat complet. Folosește exportul Excel.";
        exit;
    }
    
    protected function getModuleName() {
        return 'insurance';
    }
}
?>
