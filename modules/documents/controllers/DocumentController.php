<?php
// modules/documents/controllers/DocumentController.php
class DocumentController extends Controller {
    private $documentModel;
    private $vehicleModel;
    
    public function __construct() {
        parent::__construct();
        $this->documentModel = new Document();
        $this->vehicleModel = new Vehicle();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $type_filter = $_GET['type'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $vehicle_filter = $_GET['vehicle'] ?? '';
        
        $conditions = [];
        if (!empty($type_filter)) {
            $conditions['document_type'] = $type_filter;
        }
        if (!empty($status_filter)) {
            $conditions['status'] = $status_filter;
        }
        if (!empty($vehicle_filter)) {
            $conditions['vehicle_id'] = $vehicle_filter;
        }
        
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $documents = $this->documentModel->getAllWithVehicle() ?? [];
        
        // Aplicăm filtrele manual pentru că avem JOIN
        if (!empty($conditions) || !empty($search)) {
            $documents = array_filter($documents, function($doc) use ($conditions, $search) {
                foreach ($conditions as $key => $value) {
                    if ($doc[$key] !== $value) return false;
                }
                
                if (!empty($search)) {
                    $searchFields = ['registration_number', 'brand', 'model', 'document_number', 'provider'];
                    $found = false;
                    foreach ($searchFields as $field) {
                        if (isset($doc[$field]) && stripos($doc[$field], $search) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) return false;
                }
                
                return true;
            });
        }
        
        $totalDocuments = count($documents);
        $documents = array_slice($documents, $offset, ITEMS_PER_PAGE);
        $totalPages = ceil($totalDocuments / ITEMS_PER_PAGE);
        
        // Pentru filtre
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Statistici rapide
        $stats = [
            'total' => $this->documentModel->count(['status' => 'active']),
            'expiring_30' => count($this->documentModel->getExpiring(30)),
            'expired' => count($this->documentModel->getExpired())
        ];
        
        $this->render('list', [
            'documents' => $documents,
            'vehicles' => $vehicles,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'type_filter' => $type_filter,
            'status_filter' => $status_filter,
            'vehicle_filter' => $vehicle_filter
        ]);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'vehicle_id' => $_POST['vehicle_id'] ?? '',
                'document_type' => $_POST['document_type'] ?? '',
                'document_number' => $_POST['document_number'] ?? '',
                'issue_date' => $_POST['issue_date'] ?? null,
                'expiry_date' => $_POST['expiry_date'] ?? null,
                'provider' => $_POST['provider'] ?? '',
                'cost' => $_POST['cost'] ?? 0,
                'currency' => $_POST['currency'] ?? 'RON',
                'status' => $_POST['status'] ?? 'active',
                'reminder_days' => $_POST['reminder_days'] ?? ($_POST['notification_days'] ?? 30),
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $validationRules = [
                'vehicle_id' => ['required' => true, 'type' => 'numeric'],
                'document_type' => ['required' => true],
                'expiry_date' => ['type' => 'date'],
                'cost' => ['type' => 'numeric'],
                'reminder_days' => ['type' => 'numeric']
            ];
            
            $errors = $this->validateInput($data, $validationRules);
            
            // Verifică dacă vehiculul există
            if (!empty($data['vehicle_id'])) {
                $vehicle = $this->vehicleModel->find($data['vehicle_id']);
                if (!$vehicle) {
                    $errors['vehicle_id'] = 'Vehiculul selectat nu există';
                }
            }
            
            // Validare date - data expirării să fie după data emiterii
            if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
                if (strtotime($data['expiry_date']) <= strtotime($data['issue_date'])) {
                    $errors['expiry_date'] = 'Data expirării trebuie să fie ulterioară datei emiterii';
                }
            }
            
            // Upload fișier dacă există
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
                $uploadResult = $this->uploadFile($_FILES['document_file'], 'documents');
                if (isset($uploadResult['error'])) {
                    $errors['document_file'] = $uploadResult['error'];
                } else {
                    $data['file_path'] = $uploadResult['file_path'];
                }
            }
            
            if (empty($errors)) {
                try {
                    $documentId = $this->documentModel->create($data);
                    $_SESSION['success'] = 'Documentul a fost adăugat cu succes!';
                    // Dacă avem un vehicul, mergem la pagina vehiculului; altfel la lista de documente
                    if (!empty($data['vehicle_id'])) {
                        $this->redirect(BASE_URL . 'vehicles/view?id=' . urlencode($data['vehicle_id']));
                    }
                    $this->redirect(BASE_URL . 'documents');
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la salvarea documentului: ' . $e->getMessage();
                }
            }
            
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $data;
            // Păstrează selecția vehiculului în URL pentru preselectare
            $redirectUrl = BASE_URL . 'documents/add';
            if (!empty($data['vehicle_id'])) {
                $redirectUrl .= '?vehicle_id=' . urlencode($data['vehicle_id']);
            }
            $this->redirect($redirectUrl);
        }
        
        $vehicles = $this->vehicleModel->getActiveVehicles();
        $documentTypes = $this->getDocumentTypes();
        $selectedVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        
        $this->render('add', [
            'vehicles' => $vehicles,
            'documentTypes' => $documentTypes,
            'selectedVehicleId' => $selectedVehicleId,
            'formData' => $formData
        ]);
    }
    
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            $this->error404('Documentul nu a fost găsit');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'vehicle_id' => $_POST['vehicle_id'] ?? '',
                'document_type' => $_POST['document_type'] ?? '',
                'document_number' => $_POST['document_number'] ?? '',
                'issue_date' => $_POST['issue_date'] ?? null,
                'expiry_date' => $_POST['expiry_date'] ?? null,
                'provider' => $_POST['provider'] ?? '',
                'cost' => $_POST['cost'] ?? 0,
                'currency' => $_POST['currency'] ?? 'RON',
                'status' => $_POST['status'] ?? 'active',
                'reminder_days' => $_POST['reminder_days'] ?? 30,
                'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $validationRules = [
                'vehicle_id' => ['required' => true, 'type' => 'numeric'],
                'document_type' => ['required' => true],
                'expiry_date' => ['type' => 'date'],
                'cost' => ['type' => 'numeric'],
                'reminder_days' => ['type' => 'numeric']
            ];
            
            $errors = $this->validateInput($data, $validationRules);
            
            // Validare date
            if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
                if (strtotime($data['expiry_date']) <= strtotime($data['issue_date'])) {
                    $errors['expiry_date'] = 'Data expirării trebuie să fie ulterioară datei emiterii';
                }
            }
            
            // Upload fișier nou dacă există
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
                $uploadResult = $this->uploadFile($_FILES['document_file'], 'documents');
                if (isset($uploadResult['error'])) {
                    $errors['document_file'] = $uploadResult['error'];
                } else {
                    // Șterge fișierul vechi dacă există
                    if (!empty($document['file_path']) && file_exists(UPLOAD_PATH . $document['file_path'])) {
                        unlink(UPLOAD_PATH . $document['file_path']);
                    }
                    $data['file_path'] = $uploadResult['file_path'];
                }
            }
            
            if (empty($errors)) {
                try {
                    $this->documentModel->update($id, $data);
                    $_SESSION['success'] = 'Documentul a fost actualizat cu succes!';
                    // Redirect consistent with router: use query parameter id
                    $this->redirect(BASE_URL . 'documents/view?id=' . urlencode($id));
                } catch (Exception $e) {
                    $errors['general'] = 'Eroare la actualizarea documentului: ' . $e->getMessage();
                }
            }
            
            $_SESSION['errors'] = $errors;
            $document = array_merge($document, $data);
        }
        
        $vehicles = $this->vehicleModel->getActiveVehicles();
        $documentTypes = $this->getDocumentTypes();
        
        $this->render('edit', [
            'document' => $document,
            'vehicles' => $vehicles,
            'documentTypes' => $documentTypes
        ]);
    }
    
    public function view() {
        $id = $_GET['id'] ?? 0;
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            $this->error404('Documentul nu a fost găsit');
        }
        
        // Obține informații despre vehicul
        $vehicle = $this->vehicleModel->find($document['vehicle_id']);
        
        // Calculează zile până la expirare
        $daysUntilExpiry = null;
        if ($document['expiry_date']) {
            $expiryDate = new DateTime($document['expiry_date']);
            $today = new DateTime();
            $diff = $today->diff($expiryDate);
            $daysUntilExpiry = $expiryDate < $today ? -$diff->days : $diff->days;
        }
        
        // Istorie reînnoiri (documentele anterioare de același tip pentru același vehicul)
        $renewalHistory = $this->documentModel->findAll([
            'vehicle_id' => $document['vehicle_id'],
            'document_type' => $document['document_type']
        ]);
        
        // Exclude documentul curent din istoric
        $renewalHistory = array_filter($renewalHistory, function($doc) use ($id) {
            return $doc['id'] != $id;
        });
        
        // Sortează după data expirării descrescător
        usort($renewalHistory, function($a, $b) {
            return strtotime($b['expiry_date']) - strtotime($a['expiry_date']);
        });
        
        $this->render('view', [
            'document' => $document,
            'vehicle' => $vehicle,
            'daysUntilExpiry' => $daysUntilExpiry,
            'renewalHistory' => $renewalHistory
        ]);
    }
    
    public function expiring() {
        $days = $_GET['days'] ?? 30;
        $expiringDocuments = $this->documentModel->getExpiring($days);
        $expiredDocuments = $this->documentModel->getExpired();
        
        // Grupează documentele după tip pentru o vizualizare mai bună
        $groupedExpiring = [];
        foreach ($expiringDocuments as $doc) {
            $type = $doc['document_type'];
            if (!isset($groupedExpiring[$type])) {
                $groupedExpiring[$type] = [];
            }
            $groupedExpiring[$type][] = $doc;
        }
        
        $groupedExpired = [];
        foreach ($expiredDocuments as $doc) {
            $type = $doc['document_type'];
            if (!isset($groupedExpired[$type])) {
                $groupedExpired[$type] = [];
            }
            $groupedExpired[$type][] = $doc;
        }
        
        $this->render('expiring', [
            'expiringDocuments' => $expiringDocuments,
            'expiredDocuments' => $expiredDocuments,
            'groupedExpiring' => $groupedExpiring,
            'groupedExpired' => $groupedExpired,
            'days' => $days,
            'documentTypes' => $this->getDocumentTypes()
        ]);
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $document = $this->documentModel->find($id);
            
            if (!$document) {
                $this->json(['error' => 'Documentul nu a fost găsit'], 404);
            }
            
            try {
                $this->db->beginTransaction();
                
                // Șterge fișierul asociat dacă există
                if (!empty($document['file_path']) && file_exists(UPLOAD_PATH . $document['file_path'])) {
                    unlink(UPLOAD_PATH . $document['file_path']);
                }
                
                // Șterge notificările asociate
                $this->db->query(
                    "DELETE FROM notifications WHERE type = 'document_expiry' AND related_id = ?",
                    [$id]
                );
                
                // Șterge documentul
                $this->documentModel->delete($id);
                
                $this->db->commit();
                $this->json(['success' => true, 'message' => 'Documentul a fost șters cu succes']);
                
            } catch (Exception $e) {
                $this->db->rollback();
                $this->json(['error' => 'Eroare la ștergerea documentului: ' . $e->getMessage()], 500);
            }
        }
    }
    
    public function renew() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['document_id'] ?? 0;
            $newExpiryDate = $_POST['new_expiry_date'] ?? '';
            $newCost = $_POST['new_cost'] ?? null;
            $newProvider = $_POST['new_provider'] ?? null;
            
            $document = $this->documentModel->find($id);
            if (!$document) {
                $this->json(['error' => 'Documentul nu a fost găsit'], 404);
            }
            
            if (empty($newExpiryDate)) {
                $this->json(['error' => 'Data de expirare este obligatorie'], 400);
            }
            
            // Validează că noua dată este în viitor
            if (strtotime($newExpiryDate) <= time()) {
                $this->json(['error' => 'Data expirării trebuie să fie în viitor'], 400);
            }
            
            try {
                $this->db->beginTransaction();
                
                // Actualizează documentul
                $this->documentModel->renewDocument($id, $newExpiryDate, $newCost, $newProvider);
                
                // Crează o nouă notificare pentru noul termen de expirare
                $reminderDays = $document['reminder_days'] ?? 30;
                $reminderDate = date('Y-m-d', strtotime($newExpiryDate . " -$reminderDays days"));
                
                // Șterge notificările vechi pentru acest document
                $this->db->query(
                    "DELETE FROM notifications WHERE type = 'document_expiry' AND related_id = ?",
                    [$id]
                );
                
                // Creează notificare nouă doar dacă data reminder-ului este în viitor
                if (strtotime($reminderDate) > time()) {
                    $vehicle = $this->vehicleModel->find($document['vehicle_id']);
                    $docTypeName = $this->getDocumentTypeName($document['document_type']);
                    
                    $notificationData = [
                        'type' => 'document_expiry',
                        'vehicle_id' => $document['vehicle_id'],
                        'related_id' => $id,
                        'title' => "Document $docTypeName expiră",
                        'message' => "Documentul $docTypeName pentru vehiculul {$vehicle['registration_number']} va expira pe $newExpiryDate",
                        'target_date' => $newExpiryDate,
                        'due_date' => $reminderDate,
                        'priority' => 'high',
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->db->query(
                        "INSERT INTO notifications (type, vehicle_id, related_id, title, message, target_date, due_date, priority, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        array_values($notificationData)
                    );
                }
                
                $this->db->commit();
                
                $this->json([
                    'success' => true, 
                    'message' => 'Documentul a fost reînnoit cu succes',
                    'new_expiry_date' => $newExpiryDate
                ]);
                
            } catch (Exception $e) {
                $this->db->rollback();
                $this->json(['error' => 'Eroare la reînnoirea documentului: ' . $e->getMessage()], 500);
            }
        }
    }
    
    public function reports() {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        $vehicleId = $_GET['vehicle_id'] ?? '';
        
        $costsSummary = $this->documentModel->getCostsSummary($startDate, $endDate);
        $expiringDocuments = $this->documentModel->getExpiring(60);
        
        // Filtrează pe vehicul dacă este specificat
        if ($vehicleId) {
            $expiringDocuments = array_filter($expiringDocuments, function($doc) use ($vehicleId) {
                return $doc['vehicle_id'] == $vehicleId;
            });
        }
        
        // Calculează statistici
        $totalCosts = array_sum(array_column($costsSummary, 'total_cost'));
        $totalDocuments = array_sum(array_column($costsSummary, 'total_documents'));
        
        // Obține lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Costuri pe lună pentru grafic
        $monthlyCosts = $this->getMonthlyCosts($startDate, $endDate, $vehicleId);
        
        $this->render('reports', [
            'costsSummary' => $costsSummary,
            'expiringDocuments' => $expiringDocuments,
            'totalCosts' => $totalCosts,
            'totalDocuments' => $totalDocuments,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'vehicleId' => $vehicleId,
            'vehicles' => $vehicles,
            'monthlyCosts' => $monthlyCosts
        ]);
    }
    
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $type = $_GET['type'] ?? 'all';
        $vehicleId = $_GET['vehicle_id'] ?? '';
        
        $documents = [];
        switch ($type) {
            case 'expiring':
                $days = $_GET['days'] ?? 30;
                $documents = $this->documentModel->getExpiring($days);
                break;
            case 'expired':
                $documents = $this->documentModel->getExpired();
                break;
            case 'by_vehicle':
                if ($vehicleId) {
                    $documents = $this->documentModel->getByVehicle($vehicleId);
                    // Adaugă informații vehicul
                    $vehicle = $this->vehicleModel->find($vehicleId);
                    foreach ($documents as &$doc) {
                        $doc['registration_number'] = $vehicle['registration_number'];
                        $doc['brand'] = $vehicle['brand'];
                        $doc['model'] = $vehicle['model'];
                    }
                }
                break;
            default:
                $documents = $this->documentModel->getAllWithVehicle();
                break;
        }
        
        if ($format === 'csv') {
            $filename = 'documente_' . $type . '_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // BOM pentru UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header CSV
            fputcsv($output, [
                'Vehicul', 'Tip Document', 'Număr Document', 'Furnizor', 
                'Data Emiterii', 'Data Expirării', 'Zile până la Expirare',
                'Cost', 'Moneda', 'Status', 'Zile Reminder', 'Note'
            ]);
            
            // Date
            foreach ($documents as $doc) {
                $daysUntilExpiry = '';
                if ($doc['expiry_date']) {
                    $expiryDate = new DateTime($doc['expiry_date']);
                    $today = new DateTime();
                    $diff = $today->diff($expiryDate);
                    $daysUntilExpiry = $expiryDate < $today ? -$diff->days : $diff->days;
                }
                
                fputcsv($output, [
                    ($doc['registration_number'] ?? '') . ' - ' . ($doc['brand'] ?? '') . ' ' . ($doc['model'] ?? ''),
                    $this->getDocumentTypeName($doc['document_type']),
                    $doc['document_number'] ?? '',
                    $doc['provider'] ?? '',
                    $doc['issue_date'] ?? '',
                    $doc['expiry_date'] ?? '',
                    $daysUntilExpiry,
                    $doc['cost'] ?? '',
                    $doc['currency'] ?? 'RON',
                    $this->getStatusName($doc['status']),
                    $doc['reminder_days'] ?? '',
                    $doc['notes'] ?? ''
                ]);
            }
            
            fclose($output);
        } else {
            // JSON export
            $this->json($documents);
        }
    }
    
    public function bulk_renew() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentIds = $_POST['document_ids'] ?? [];
            $newExpiryDate = $_POST['new_expiry_date'] ?? '';
            $addMonths = $_POST['add_months'] ?? 12;
            
            if (empty($documentIds)) {
                $this->json(['error' => 'Nu au fost selectate documente'], 400);
            }
            
            try {
                $this->db->beginTransaction();
                $renewedCount = 0;
                
                foreach ($documentIds as $id) {
                    $document = $this->documentModel->find($id);
                    if (!$document || $document['status'] !== 'active') continue;
                    
                    // Calculează noua dată de expirare
                    if ($newExpiryDate) {
                        $renewalDate = $newExpiryDate;
                    } else {
                        $currentExpiry = $document['expiry_date'] ?? date('Y-m-d');
                        $renewalDate = date('Y-m-d', strtotime($currentExpiry . " +$addMonths months"));
                    }
                    
                    $this->documentModel->renewDocument($id, $renewalDate);
                    $renewedCount++;
                }
                
                $this->db->commit();
                $this->json([
                    'success' => true,
                    'message' => "Au fost reînnoite $renewedCount documente"
                ]);
                
            } catch (Exception $e) {
                $this->db->rollback();
                $this->json(['error' => 'Eroare la reînnoirea în masă: ' . $e->getMessage()], 500);
            }
        }
    }
    
    private function getMonthlyCosts($startDate, $endDate, $vehicleId = null) {
        $sql = "SELECT 
                    YEAR(issue_date) as year,
                    MONTH(issue_date) as month,
                    SUM(cost) as total_cost,
                    COUNT(*) as total_documents
                FROM documents 
                WHERE issue_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        
        if ($vehicleId) {
            $sql .= " AND vehicle_id = ?";
            $params[] = $vehicleId;
        }
        
        $sql .= " GROUP BY YEAR(issue_date), MONTH(issue_date) 
                  ORDER BY YEAR(issue_date), MONTH(issue_date)";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getDocumentTypes() {
        return [
            'insurance_rca' => 'Asigurare RCA',
            'insurance_casco' => 'Asigurare CASCO',
            'itp' => 'ITP',
            'vignette' => 'Rovinietă',
            'registration' => 'Certificat Înmatriculare',
            'authorization' => 'Autorizație Transport',
            'other' => 'Altele'
        ];
    }
    
    private function getDocumentTypeName($type) {
        $types = $this->getDocumentTypes();
        return $types[$type] ?? $type;
    }
    
    private function getStatusName($status) {
        $statuses = [
            'active' => 'Activ',
            'expired' => 'Expirat',
            'cancelled' => 'Anulat'
        ];
        return $statuses[$status] ?? $status;
    }
    
    // API endpoint pentru obținerea documentelor unui vehicul (AJAX)
    public function getByVehicleAjax() {
        $vehicleId = $_GET['vehicle_id'] ?? 0;
        
        if (!$vehicleId) {
            $this->json(['error' => 'ID vehicul lipsă'], 400);
        }
        
        $documents = $this->documentModel->getByVehicle($vehicleId);
        $this->json(['documents' => $documents]);
    }
    
    // API endpoint pentru dashboard
    public function getStats() {
        $stats = [
            'total_active' => $this->documentModel->count(['status' => 'active']),
            'expiring_30' => count($this->documentModel->getExpiring(30)),
            'expiring_7' => count($this->documentModel->getExpiring(7)),
            'expired' => count($this->documentModel->getExpired()),
        ];
        
        // Costuri pe tipuri de documente
        $costsByType = $this->documentModel->getCostsSummary();
        
        $this->json([
            'stats' => $stats,
            'costs_by_type' => $costsByType
        ]);
    }
}
?>