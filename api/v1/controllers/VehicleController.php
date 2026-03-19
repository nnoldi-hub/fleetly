<?php
/**
 * Vehicle API Controller
 * 
 * CRUD pentru vehicule via API mobile.
 */
class VehicleController {
    
    private $db;
    private $tenantDb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Initialize tenant database
     */
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
     * GET /api/v1/vehicles
     * 
     * List vehicles with pagination, search, and filters
     */
    public function index() {
        $this->initTenantDb();
        
        // Get query parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? null;
        $typeId = $_GET['type_id'] ?? null;
        $sortBy = $_GET['sort_by'] ?? 'registration_number';
        $sortDir = strtoupper($_GET['sort_dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        
        $offset = ($page - 1) * $perPage;
        
        try {
            // Build WHERE clause
            $where = ['v.deleted_at IS NULL'];
            $params = [];
            
            if (!empty($search)) {
                $where[] = "(v.registration_number LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.vin_number LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }
            
            if ($status) {
                $where[] = "v.status = ?";
                $params[] = $status;
            }
            
            if ($typeId) {
                $where[] = "v.vehicle_type_id = ?";
                $params[] = (int)$typeId;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Allowed sort columns
            $allowedSorts = ['registration_number', 'brand', 'model', 'year', 'current_mileage', 'created_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'registration_number';
            }
            
            // Count total
            $countSql = "SELECT COUNT(*) as total FROM vehicles v WHERE {$whereClause}";
            $stmt = $this->tenantDb->prepare($countSql);
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get vehicles
            $sql = "
                SELECT v.id, v.registration_number, v.brand, v.model, v.year,
                       v.vin_number, v.current_mileage, v.status, v.fuel_type, v.color,
                       v.vehicle_type_id, vt.name as vehicle_type,
                       v.created_at, v.updated_at
                FROM vehicles v
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
                WHERE {$whereClause}
                ORDER BY v.{$sortBy} {$sortDir}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $vehicles = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $vehicles[] = $this->formatVehicle($row);
            }
            
            ApiResponse::paginated($vehicles, $page, $perPage, $total, 'Vehicles retrieved');
            
        } catch (PDOException $e) {
            error_log('Vehicles list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load vehicles', 500);
        }
    }
    
    /**
     * GET /api/v1/vehicles/{id}
     * 
     * Get single vehicle details
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT v.*, vt.name as vehicle_type
                FROM vehicles v
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
                WHERE v.id = ? AND (v.status != 'deleted' OR v.status IS NULL)
            ");
            $stmt->execute([$id]);
            $vehicle = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$vehicle) {
                ApiResponse::notFound('Vehicle not found');
            }
            
            $data = $this->formatVehicle($vehicle, true);
            
            // Get documents count
            try {
                $stmt = $this->tenantDb->prepare("
                    SELECT COUNT(*) as total FROM documents 
                    WHERE vehicle_id = ?
                ");
                $stmt->execute([$id]);
                $data['documents_count'] = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            } catch (PDOException $e) {
                $data['documents_count'] = 0;
            }
            
            // Get maintenance count
            try {
                $stmt = $this->tenantDb->prepare("
                    SELECT COUNT(*) as total FROM maintenance 
                    WHERE vehicle_id = ?
                ");
                $stmt->execute([$id]);
                $data['maintenance_count'] = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            } catch (PDOException $e) {
                $data['maintenance_count'] = 0;
            }
            
            ApiResponse::success($data);
            
        } catch (PDOException $e) {
            error_log('Vehicle show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load vehicle', 500);
        }
    }
    
    /**
     * POST /api/v1/vehicles
     * 
     * Create new vehicle
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        // Validate required fields
        $errors = $this->validateVehicle($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO vehicles (
                    registration_number, make, model, year, vin, mileage,
                    fuel_type, color, vehicle_type_id, status, current_driver_id,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                trim($input['registration_number']),
                trim($input['make'] ?? ''),
                trim($input['model'] ?? ''),
                $input['year'] ?? null,
                trim($input['vin'] ?? ''),
                $input['mileage'] ?? 0,
                $input['fuel_type'] ?? null,
                trim($input['color'] ?? ''),
                $input['vehicle_type_id'] ?? null,
                $input['status'] ?? 'active',
                $input['driver_id'] ?? null
            ]);
            
            $vehicleId = $this->tenantDb->lastInsertId();
            
            // Get created vehicle
            $stmt = $this->tenantDb->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicleId]);
            $vehicle = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success($this->formatVehicle($vehicle), 'Vehicle created', 201);
            
        } catch (PDOException $e) {
            error_log('Vehicle create error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                ApiResponse::validationError(['registration_number' => 'Registration number already exists']);
            }
            
            ApiResponse::error('Failed to create vehicle', 500);
        }
    }
    
    /**
     * PUT /api/v1/vehicles/{id}
     * 
     * Update vehicle
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        // Check vehicle exists
        $stmt = $this->tenantDb->prepare("SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Vehicle not found');
        }
        
        // Build update
        $allowed = ['registration_number', 'make', 'model', 'year', 'vin', 'mileage', 
                    'fuel_type', 'color', 'vehicle_type_id', 'status', 'current_driver_id'];
        $updates = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $dbField = $field === 'driver_id' ? 'current_driver_id' : $field;
                $updates[] = "{$dbField} = ?";
                $params[] = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            }
        }
        
        if (empty($updates)) {
            ApiResponse::validationError(['fields' => 'No valid fields to update']);
        }
        
        $params[] = $id;
        
        try {
            $sql = "UPDATE vehicles SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            // Return updated vehicle
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Vehicle update error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                ApiResponse::validationError(['registration_number' => 'Registration number already exists']);
            }
            
            ApiResponse::error('Failed to update vehicle', 500);
        }
    }
    
    /**
     * DELETE /api/v1/vehicles/{id}
     * 
     * Soft delete vehicle
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                UPDATE vehicles SET deleted_at = NOW(), updated_at = NOW() 
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Vehicle not found');
            }
            
            ApiResponse::success(null, 'Vehicle deleted');
            
        } catch (PDOException $e) {
            error_log('Vehicle delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete vehicle', 500);
        }
    }
    
    /**
     * GET /api/v1/vehicles/{id}/documents
     * 
     * Get vehicle documents
     */
    public function documents($id) {
        $this->initTenantDb();
        
        try {
            // Check vehicle exists
            $stmt = $this->tenantDb->prepare("SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Vehicle not found');
            }
            
            $stmt = $this->tenantDb->prepare("
                SELECT id, name, document_type, file_path, expiry_date, 
                       issue_date, notes, created_at
                FROM documents
                WHERE vehicle_id = ? AND deleted_at IS NULL
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
                    'type' => $row->document_type,
                    'expiry_date' => $row->expiry_date,
                    'issue_date' => $row->issue_date,
                    'is_expired' => $isExpired,
                    'days_until_expiry' => $daysUntil,
                    'status' => $isExpired ? 'expired' : ($daysUntil !== null && $daysUntil <= 30 ? 'expiring_soon' : 'valid'),
                    'notes' => $row->notes,
                    'created_at' => $row->created_at
                ];
            }
            
            ApiResponse::success($documents);
            
        } catch (PDOException $e) {
            error_log('Vehicle documents error: ' . $e->getMessage());
            ApiResponse::error('Failed to load documents', 500);
        }
    }
    
    /**
     * GET /api/v1/vehicles/{id}/maintenance
     * 
     * Get vehicle maintenance history
     */
    public function maintenance($id) {
        $this->initTenantDb();
        
        try {
            // Check vehicle exists
            $stmt = $this->tenantDb->prepare("SELECT id FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Vehicle not found');
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
            $offset = ($page - 1) * $perPage;
            
            // Count
            $stmt = $this->tenantDb->prepare("
                SELECT COUNT(*) as total FROM maintenance WHERE vehicle_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get records
            $stmt = $this->tenantDb->prepare("
                SELECT id, maintenance_type, description, scheduled_date, completed_date,
                       mileage_at_service, cost, status, notes, created_at
                FROM maintenance
                WHERE vehicle_id = ? AND deleted_at IS NULL
                ORDER BY COALESCE(completed_date, scheduled_date, created_at) DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute([$id]);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $records[] = [
                    'id' => (int)$row->id,
                    'type' => $row->maintenance_type,
                    'description' => $row->description,
                    'scheduled_date' => $row->scheduled_date,
                    'completed_date' => $row->completed_date,
                    'mileage' => $row->mileage_at_service ? (int)$row->mileage_at_service : null,
                    'cost' => $row->cost ? (float)$row->cost : null,
                    'status' => $row->status,
                    'notes' => $row->notes
                ];
            }
            
            ApiResponse::paginated($records, $page, $perPage, $total);
            
        } catch (PDOException $e) {
            error_log('Vehicle maintenance error: ' . $e->getMessage());
            ApiResponse::error('Failed to load maintenance history', 500);
        }
    }
    
    /**
     * POST /api/v1/vehicles/{id}/mileage
     * 
     * Update vehicle mileage
     */
    public function updateMileage($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        if (!isset($input['mileage']) || !is_numeric($input['mileage'])) {
            ApiResponse::validationError(['mileage' => 'Valid mileage is required']);
        }
        
        $mileage = (int)$input['mileage'];
        
        try {
            // Get current mileage
            $stmt = $this->tenantDb->prepare("SELECT mileage FROM vehicles WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $vehicle = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$vehicle) {
                ApiResponse::notFound('Vehicle not found');
            }
            
            if ($mileage < $vehicle->mileage) {
                ApiResponse::validationError(['mileage' => 'New mileage cannot be less than current mileage']);
            }
            
            $stmt = $this->tenantDb->prepare("
                UPDATE vehicles SET mileage = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$mileage, $id]);
            
            ApiResponse::success([
                'id' => (int)$id,
                'mileage' => $mileage,
                'previous_mileage' => (int)$vehicle->mileage
            ], 'Mileage updated');
            
        } catch (PDOException $e) {
            error_log('Mileage update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update mileage', 500);
        }
    }
    
    // ===== Helper Methods =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? [];
    }
    
    private function validateVehicle($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate && empty($input['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (isset($input['year']) && $input['year']) {
            $year = (int)$input['year'];
            if ($year < 1900 || $year > date('Y') + 1) {
                $errors['year'] = 'Invalid year';
            }
        }
        
        if (isset($input['mileage']) && $input['mileage'] < 0) {
            $errors['mileage'] = 'Mileage cannot be negative';
        }
        
        return $errors;
    }
    
    private function formatVehicle($row, $detailed = false) {
        // Handle both column naming conventions (brand/make, current_mileage/mileage)
        $brand = $row->brand ?? $row->make ?? null;
        $mileage = $row->current_mileage ?? $row->mileage ?? 0;
        $vin = $row->vin_number ?? $row->vin ?? null;
        
        $data = [
            'id' => (int)$row->id,
            'registration_number' => $row->registration_number,
            'make' => $brand,  // API returns as 'make' for compatibility
            'model' => $row->model,
            'year' => $row->year ? (int)$row->year : null,
            'mileage' => $mileage ? (int)$mileage : 0,
            'status' => $row->status ?? 'active',
            'fuel_type' => $row->fuel_type ?? null,
            'vehicle_type' => $row->vehicle_type ?? null
        ];
        
        // Driver is no longer joined - removed from output
        $data['driver'] = null;
        
        if ($detailed) {
            $data['vin'] = $vin;
            $data['color'] = $row->color ?? null;
            $data['vehicle_type_id'] = $row->vehicle_type_id ? (int)$row->vehicle_type_id : null;
            $data['created_at'] = $row->created_at;
            $data['updated_at'] = $row->updated_at;
        }
        
        return $data;
    }
}
