<?php
/**
 * Driver API Controller
 * 
 * CRUD pentru șoferi via API mobile.
 * Compatibil cu structura existentă a bazei de date.
 */
class DriverController {
    
    private $db;
    private $tenantDb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
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
     * GET /api/v1/drivers
     */
    public function index() {
        $this->initTenantDb();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'name';
        $sortDir = strtoupper($_GET['sort_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['1=1'];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(d.name LIKE ? OR d.phone LIKE ? OR d.email LIKE ? OR d.license_number LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }
            
            if ($status) {
                $where[] = "d.status = ?";
                $params[] = $status;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $allowedSorts = ['name', 'license_expiry_date', 'created_at', 'status'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'name';
            }
            
            // Count
            $stmt = $this->tenantDb->prepare("SELECT COUNT(*) as total FROM drivers d WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get drivers
            $sql = "
                SELECT d.id, d.name, d.phone, d.email,
                       d.license_number, d.license_category, d.license_expiry_date,
                       d.status, d.hire_date, d.assigned_vehicle_id,
                       v.registration_number as vehicle
                FROM drivers d
                LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id
                WHERE {$whereClause}
                ORDER BY d.{$sortBy} {$sortDir}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $drivers = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $drivers[] = $this->formatDriver($row);
            }
            
            ApiResponse::paginated($drivers, $page, $perPage, $total, 'Drivers retrieved');
            
        } catch (PDOException $e) {
            error_log('Drivers list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load drivers', 500);
        }
    }
    
    /**
     * GET /api/v1/drivers/{id}
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT d.*,
                       v.id as vehicle_id, v.registration_number as vehicle,
                       v.brand as vehicle_make, v.model as vehicle_model
                FROM drivers d
                LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $driver = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$driver) {
                ApiResponse::notFound('Driver not found');
            }
            
            $data = $this->formatDriver($driver, true);
            
            // Get documents count
            try {
                $stmt = $this->tenantDb->prepare("
                    SELECT COUNT(*) as total FROM documents WHERE driver_id = ?
                ");
                $stmt->execute([$id]);
                $data['documents_count'] = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            } catch (PDOException $e) {
                $data['documents_count'] = 0;
            }
            
            ApiResponse::success($data);
            
        } catch (PDOException $e) {
            error_log('Driver show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load driver', 500);
        }
    }
    
    /**
     * POST /api/v1/drivers
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $errors = $this->validateDriver($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO drivers (
                    name, phone, email, license_number, license_category,
                    license_issue_date, license_expiry_date, address, 
                    date_of_birth, hire_date, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                trim($input['name']),
                trim($input['phone'] ?? ''),
                trim($input['email'] ?? ''),
                trim($input['license_number'] ?? ''),
                $input['license_category'] ?? null,
                $input['license_issue_date'] ?? null,
                $input['license_expiry_date'] ?? null,
                trim($input['address'] ?? ''),
                $input['date_of_birth'] ?? null,
                $input['hire_date'] ?? null,
                $input['status'] ?? 'active'
            ]);
            
            $driverId = $this->tenantDb->lastInsertId();
            
            $stmt = $this->tenantDb->prepare("SELECT * FROM drivers WHERE id = ?");
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success($this->formatDriver($driver, true), 'Driver created', 201);
            
        } catch (PDOException $e) {
            error_log('Driver create error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'license_number') !== false) {
                    ApiResponse::validationError(['license_number' => 'License number already exists']);
                }
            }
            
            ApiResponse::error('Failed to create driver', 500);
        }
    }
    
    /**
     * PUT /api/v1/drivers/{id}
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM drivers WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Driver not found');
        }
        
        $allowed = ['name', 'phone', 'email', 'license_number', 'license_category',
                    'license_issue_date', 'license_expiry_date', 'address', 
                    'date_of_birth', 'hire_date', 'status', 'notes'];
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
            $sql = "UPDATE drivers SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Driver update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update driver', 500);
        }
    }
    
    /**
     * DELETE /api/v1/drivers/{id}
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Driver not found');
            }
            
            ApiResponse::success(null, 'Driver deleted');
            
        } catch (PDOException $e) {
            error_log('Driver delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete driver', 500);
        }
    }
    
    /**
     * GET /api/v1/drivers/{id}/documents
     */
    public function documents($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("SELECT id FROM drivers WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Driver not found');
            }
            
            $stmt = $this->tenantDb->prepare("
                SELECT id, name, type, file_path, expiry_date, 
                       issue_date, notes, created_at
                FROM documents
                WHERE driver_id = ?
                ORDER BY expiry_date ASC
            ");
            $stmt->execute([$id]);
            
            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $isExpired = $row->expiry_date && strtotime($row->expiry_date) < time();
                $daysUntil = $row->expiry_date 
                    ? (int)ceil((strtotime($row->expiry_date) - time()) / 86400) 
                    : null;
                
                $documents[] = [
                    'id' => (int)$row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                    'expiry_date' => $row->expiry_date,
                    'issue_date' => $row->issue_date,
                    'is_expired' => $isExpired,
                    'days_until_expiry' => $daysUntil,
                    'status' => $isExpired ? 'expired' : ($daysUntil !== null && $daysUntil <= 30 ? 'expiring_soon' : 'valid'),
                    'notes' => $row->notes
                ];
            }
            
            ApiResponse::success($documents);
            
        } catch (PDOException $e) {
            error_log('Driver documents error: ' . $e->getMessage());
            ApiResponse::error('Failed to load documents', 500);
        }
    }
    
    // ===== Helpers =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    private function validateDriver($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate && empty($input['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (isset($input['email']) && !empty($input['email'])) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }
        
        return $errors;
    }
    
    private function formatDriver($row, $detailed = false) {
        $data = [
            'id' => (int)$row->id,
            'name' => $row->name,
            'phone' => $row->phone ?? null,
            'email' => $row->email ?? null,
            'status' => $row->status ?? 'active'
        ];
        
        // License info
        $expiryDate = $row->license_expiry_date ?? null;
        if ($expiryDate) {
            $daysUntil = (int)ceil((strtotime($expiryDate) - time()) / 86400);
            $data['license'] = [
                'number' => $row->license_number,
                'category' => $row->license_category,
                'expiry_date' => $expiryDate,
                'is_expired' => $daysUntil < 0,
                'days_until_expiry' => $daysUntil
            ];
        } else {
            $data['license'] = [
                'number' => $row->license_number ?? null,
                'category' => $row->license_category ?? null,
                'expiry_date' => null,
                'is_expired' => false,
                'days_until_expiry' => null
            ];
        }
        
        // Assigned vehicle
        $vehicleId = $row->assigned_vehicle_id ?? $row->vehicle_id ?? null;
        if ($vehicleId) {
            $data['vehicle'] = [
                'id' => (int)$vehicleId,
                'registration_number' => $row->vehicle ?? null
            ];
            if ($detailed && isset($row->vehicle_make)) {
                $data['vehicle']['make'] = $row->vehicle_make;
                $data['vehicle']['model'] = $row->vehicle_model;
            }
        } else {
            $data['vehicle'] = null;
        }
        
        if ($detailed) {
            $data['address'] = $row->address ?? null;
            $data['date_of_birth'] = $row->date_of_birth ?? null;
            $data['hire_date'] = $row->hire_date ?? null;
            $data['notes'] = $row->notes ?? null;
            $data['created_at'] = $row->created_at ?? null;
            $data['updated_at'] = $row->updated_at ?? null;
        }
        
        return $data;
    }
}
