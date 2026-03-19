<?php
/**
 * Document API Controller
 * 
 * CRUD + Upload pentru documente via API mobile.
 * Compatibil cu structura existentă a bazei de date.
 */
class DocumentController {
    
    private $db;
    private $tenantDb;
    private $uploadDir;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = APP_ROOT . '/uploads/documents/';
    }
    
    private function initTenantDb() {
        // In single-tenant mode, use core DB directly
        if (method_exists('DatabaseConfig', 'getTenancyMode') && DatabaseConfig::getTenancyMode() === 'single') {
            $this->tenantDb = $this->db;
            return;
        }
        
        $companyId = AuthMiddleware::companyId();
        if ($companyId) {
            Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
            $this->tenantDb = Database::getInstance()->getTenantPdo();
        }
        if (!$this->tenantDb) {
            $this->tenantDb = $this->db;
        }
    }
    
    /**
     * GET /api/v1/documents
     */
    public function index() {
        $this->initTenantDb();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $search = trim($_GET['search'] ?? '');
        $type = $_GET['type'] ?? null;
        $vehicleId = $_GET['vehicle_id'] ?? null;
        $status = $_GET['status'] ?? null; // active, expired, expiring
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['1=1'];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(d.document_number LIKE ? OR d.provider LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($type) {
                $where[] = "d.document_type = ?";
                $params[] = $type;
            }
            
            if ($vehicleId) {
                $where[] = "d.vehicle_id = ?";
                $params[] = $vehicleId;
            }
            
            if ($status === 'expired') {
                $where[] = "d.expiry_date < CURDATE()";
            } elseif ($status === 'expiring') {
                $where[] = "d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($status === 'active') {
                $where[] = "d.status = 'active' AND (d.expiry_date IS NULL OR d.expiry_date >= CURDATE())";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Count
            $stmt = $this->tenantDb->prepare("SELECT COUNT(*) as total FROM documents d WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get documents
            $sql = "
                SELECT d.*, 
                       v.registration_number as vehicle,
                       v.brand as vehicle_make, v.model as vehicle_model
                FROM documents d
                LEFT JOIN vehicles v ON d.vehicle_id = v.id
                WHERE {$whereClause}
                ORDER BY COALESCE(d.expiry_date, '9999-12-31') ASC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $documents[] = $this->formatDocument($row);
            }
            
            ApiResponse::paginated($documents, $page, $perPage, $total, 'Documents retrieved');
            
        } catch (PDOException $e) {
            error_log('Documents list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load documents', 500);
        }
    }
    
    /**
     * GET /api/v1/documents/expiring
     */
    public function expiring() {
        $this->initTenantDb();
        
        $days = min(90, max(1, (int)($_GET['days'] ?? 30)));
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT d.*, 
                       v.registration_number as vehicle,
                       v.brand as vehicle_make, v.model as vehicle_model
                FROM documents d
                LEFT JOIN vehicles v ON d.vehicle_id = v.id
                WHERE d.expiry_date IS NOT NULL
                  AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND d.status = 'active'
                ORDER BY d.expiry_date ASC
            ");
            $stmt->execute([$days]);
            
            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $documents[] = $this->formatDocument($row);
            }
            
            ApiResponse::success($documents);
            
        } catch (PDOException $e) {
            error_log('Expiring documents error: ' . $e->getMessage());
            ApiResponse::error('Failed to load expiring documents', 500);
        }
    }
    
    /**
     * GET /api/v1/documents/{id}
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT d.*,
                       v.registration_number as vehicle,
                       v.brand as vehicle_make, v.model as vehicle_model
                FROM documents d
                LEFT JOIN vehicles v ON d.vehicle_id = v.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $doc = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$doc) {
                ApiResponse::notFound('Document not found');
            }
            
            ApiResponse::success($this->formatDocument($doc, true));
            
        } catch (PDOException $e) {
            error_log('Document show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load document', 500);
        }
    }
    
    /**
     * POST /api/v1/documents
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $errors = $this->validateDocument($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO documents (
                    vehicle_id, document_type, document_number,
                    issue_date, expiry_date, provider, cost, currency,
                    file_path, status, reminder_days, auto_renew, notes,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['vehicle_id'],
                $input['document_type'],
                trim($input['document_number'] ?? ''),
                $input['issue_date'] ?? null,
                $input['expiry_date'] ?? null,
                trim($input['provider'] ?? ''),
                $input['cost'] ?? null,
                $input['currency'] ?? 'RON',
                $input['file_path'] ?? null,
                $input['status'] ?? 'active',
                $input['reminder_days'] ?? 30,
                $input['auto_renew'] ?? 0,
                trim($input['notes'] ?? '')
            ]);
            
            $docId = $this->tenantDb->lastInsertId();
            
            $stmt = $this->tenantDb->prepare("SELECT * FROM documents WHERE id = ?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success($this->formatDocument($doc, true), 'Document created', 201);
            
        } catch (PDOException $e) {
            error_log('Document create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create document', 500);
        }
    }
    
    /**
     * PUT /api/v1/documents/{id}
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Document not found');
        }
        
        $allowed = ['vehicle_id', 'document_type', 'document_number', 
                    'issue_date', 'expiry_date', 'provider', 'cost', 'currency',
                    'file_path', 'status', 'reminder_days', 'auto_renew', 'notes'];
        $updates = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $updates[] = "{$field} = ?";
                $params[] = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            }
        }
        
        if (empty($updates)) {
            ApiResponse::validationError(['fields' => 'No valid fields to update']);
        }
        
        $params[] = $id;
        
        try {
            $sql = "UPDATE documents SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Document update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update document', 500);
        }
    }
    
    /**
     * DELETE /api/v1/documents/{id}
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Document not found');
            }
            
            ApiResponse::success(null, 'Document deleted');
            
        } catch (PDOException $e) {
            error_log('Document delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete document', 500);
        }
    }
    
    /**
     * POST /api/v1/documents/{id}/upload
     */
    public function upload($id) {
        $this->initTenantDb();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Document not found');
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::validationError(['file' => 'No file uploaded or upload error']);
        }
        
        $file = $_FILES['file'];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            ApiResponse::validationError(['file' => 'Invalid file type. Allowed: PDF, JPG, PNG']);
        }
        
        if ($file['size'] > $maxSize) {
            ApiResponse::validationError(['file' => 'File too large. Max 10MB']);
        }
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'doc_' . $id . '_' . time() . '.' . $ext;
        $filepath = $this->uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            ApiResponse::error('Failed to save file', 500);
        }
        
        try {
            $relativePath = 'uploads/documents/' . $filename;
            $stmt = $this->tenantDb->prepare("UPDATE documents SET file_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$relativePath, $id]);
            
            ApiResponse::success(['file_path' => $relativePath], 'File uploaded');
            
        } catch (PDOException $e) {
            error_log('Document upload error: ' . $e->getMessage());
            ApiResponse::error('Failed to update document', 500);
        }
    }
    
    // ===== Helpers =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    private function validateDocument($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['vehicle_id'])) {
                $errors['vehicle_id'] = 'Vehicle is required';
            }
            if (empty($input['document_type'])) {
                $errors['document_type'] = 'Document type is required';
            }
        }
        
        $validTypes = ['insurance_rca', 'insurance_casco', 'itp', 'vignette', 
                       'registration', 'authorization', 'other'];
        if (isset($input['document_type']) && !in_array($input['document_type'], $validTypes)) {
            $errors['document_type'] = 'Invalid document type';
        }
        
        return $errors;
    }
    
    private function formatDocument($row, $detailed = false) {
        $expiryDate = $row->expiry_date ?? null;
        $daysUntil = null;
        $isExpired = false;
        
        if ($expiryDate) {
            $daysUntil = (int)ceil((strtotime($expiryDate) - time()) / 86400);
            $isExpired = $daysUntil < 0;
        }
        
        // Friendly type names
        $typeNames = [
            'insurance_rca' => 'RCA Insurance',
            'insurance_casco' => 'CASCO Insurance',
            'itp' => 'ITP (Technical Inspection)',
            'vignette' => 'Vignette',
            'registration' => 'Registration',
            'authorization' => 'Authorization',
            'other' => 'Other'
        ];
        
        $data = [
            'id' => (int)$row->id,
            'document_type' => $row->document_type,
            'document_type_name' => $typeNames[$row->document_type] ?? $row->document_type,
            'document_number' => $row->document_number,
            'expiry_date' => $expiryDate,
            'is_expired' => $isExpired,
            'days_until_expiry' => $daysUntil,
            'status' => $isExpired ? 'expired' : ($daysUntil !== null && $daysUntil <= 30 ? 'expiring_soon' : 'valid'),
            'vehicle' => [
                'id' => (int)$row->vehicle_id,
                'registration_number' => $row->vehicle ?? null
            ]
        ];
        
        if ($detailed) {
            $data['issue_date'] = $row->issue_date ?? null;
            $data['provider'] = $row->provider ?? null;
            $data['cost'] = $row->cost ? (float)$row->cost : null;
            $data['currency'] = $row->currency ?? 'RON';
            $data['file_path'] = $row->file_path ?? null;
            $data['reminder_days'] = (int)($row->reminder_days ?? 30);
            $data['auto_renew'] = (bool)($row->auto_renew ?? false);
            $data['notes'] = $row->notes ?? null;
            $data['created_at'] = $row->created_at ?? null;
            $data['updated_at'] = $row->updated_at ?? null;
            
            if (isset($row->vehicle_make)) {
                $data['vehicle']['make'] = $row->vehicle_make;
                $data['vehicle']['model'] = $row->vehicle_model;
            }
        }
        
        return $data;
    }
}
