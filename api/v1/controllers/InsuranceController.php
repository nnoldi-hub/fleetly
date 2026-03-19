<?php
/**
 * Insurance API Controller
 * 
 * CRUD pentru asigurări via API mobile.
 */
class InsuranceController {
    
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
     * GET /api/v1/insurance
     */
    public function index() {
        $this->initTenantDb();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $vehicleId = $_GET['vehicle_id'] ?? null;
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null; // active, expired, expiring
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['i.deleted_at IS NULL'];
            $params = [];
            
            if ($vehicleId) {
                $where[] = "i.vehicle_id = ?";
                $params[] = (int)$vehicleId;
            }
            
            if ($type) {
                $where[] = "i.insurance_type = ?";
                $params[] = $type;
            }
            
            if ($status === 'expired') {
                $where[] = "i.end_date < CURDATE()";
            } elseif ($status === 'expiring') {
                $where[] = "i.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($status === 'active') {
                $where[] = "i.end_date >= CURDATE()";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Count
            $stmt = $this->tenantDb->prepare("SELECT COUNT(*) as total FROM insurance i WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get records
            $sql = "
                SELECT i.*, v.registration_number as vehicle, v.make, v.model
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE {$whereClause}
                ORDER BY i.end_date ASC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $records[] = $this->formatInsurance($row);
            }
            
            ApiResponse::paginated($records, $page, $perPage, $total);
            
        } catch (PDOException $e) {
            error_log('Insurance list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load insurance records', 500);
        }
    }
    
    /**
     * GET /api/v1/insurance/{id}
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT i.*, v.registration_number as vehicle, v.make, v.model, v.vin
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.id = ? AND i.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$record) {
                ApiResponse::notFound('Insurance record not found');
            }
            
            ApiResponse::success($this->formatInsurance($record, true));
            
        } catch (PDOException $e) {
            error_log('Insurance show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load insurance record', 500);
        }
    }
    
    /**
     * POST /api/v1/insurance
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $errors = $this->validateInsurance($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO insurance (
                    vehicle_id, insurance_type, policy_number, provider, 
                    start_date, end_date, premium, coverage_amount,
                    notes, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                (int)$input['vehicle_id'],
                $input['insurance_type'] ?? 'rca',
                trim($input['policy_number'] ?? ''),
                trim($input['provider'] ?? ''),
                $input['start_date'] ?? date('Y-m-d'),
                $input['end_date'],
                $input['premium'] ?? null,
                $input['coverage_amount'] ?? null,
                trim($input['notes'] ?? '')
            ]);
            
            $recordId = $this->tenantDb->lastInsertId();
            
            $stmt = $this->tenantDb->prepare("
                SELECT i.*, v.registration_number as vehicle FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id WHERE i.id = ?
            ");
            $stmt->execute([$recordId]);
            
            ApiResponse::success($this->formatInsurance($stmt->fetch(PDO::FETCH_OBJ)), 'Insurance record created', 201);
            
        } catch (PDOException $e) {
            error_log('Insurance create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create insurance record', 500);
        }
    }
    
    /**
     * PUT /api/v1/insurance/{id}
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM insurance WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Insurance record not found');
        }
        
        $allowed = ['insurance_type', 'policy_number', 'provider', 'start_date', 'end_date',
                    'premium', 'coverage_amount', 'notes'];
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
            $sql = "UPDATE insurance SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Insurance update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update insurance record', 500);
        }
    }
    
    /**
     * DELETE /api/v1/insurance/{id}
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                UPDATE insurance SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Insurance record not found');
            }
            
            ApiResponse::success(null, 'Insurance record deleted');
            
        } catch (PDOException $e) {
            error_log('Insurance delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete insurance record', 500);
        }
    }
    
    /**
     * GET /api/v1/insurance/expiring
     */
    public function expiring() {
        $this->initTenantDb();
        
        $days = (int)($_GET['days'] ?? 30);
        $days = min(90, max(1, $days));
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT i.id, i.insurance_type, i.policy_number, i.provider, i.end_date,
                       v.id as vehicle_id, v.registration_number as vehicle
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.deleted_at IS NULL 
                AND i.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY i.end_date ASC
            ");
            $stmt->execute([$days]);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $daysUntil = (int)ceil((strtotime($row->end_date) - time()) / 86400);
                $records[] = [
                    'id' => (int)$row->id,
                    'type' => $row->insurance_type,
                    'policy_number' => $row->policy_number,
                    'provider' => $row->provider,
                    'expiry_date' => $row->end_date,
                    'days_until_expiry' => $daysUntil,
                    'priority' => $daysUntil <= 7 ? 'high' : ($daysUntil <= 14 ? 'medium' : 'low'),
                    'vehicle' => [
                        'id' => (int)$row->vehicle_id,
                        'registration_number' => $row->vehicle
                    ]
                ];
            }
            
            ApiResponse::success([
                'records' => $records,
                'total' => count($records),
                'period_days' => $days
            ]);
            
        } catch (PDOException $e) {
            error_log('Expiring insurance error: ' . $e->getMessage());
            ApiResponse::error('Failed to load expiring insurance', 500);
        }
    }
    
    // ===== Helpers =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    private function validateInsurance($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['vehicle_id'])) {
                $errors['vehicle_id'] = 'Vehicle ID is required';
            }
            if (empty($input['end_date'])) {
                $errors['end_date'] = 'End date is required';
            }
        }
        
        if (isset($input['premium']) && $input['premium'] < 0) {
            $errors['premium'] = 'Premium cannot be negative';
        }
        
        return $errors;
    }
    
    private function formatInsurance($row, $detailed = false) {
        $isExpired = strtotime($row->end_date) < time();
        $daysUntil = (int)ceil((strtotime($row->end_date) - time()) / 86400);
        
        $data = [
            'id' => (int)$row->id,
            'type' => $row->insurance_type,
            'policy_number' => $row->policy_number,
            'provider' => $row->provider,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'is_expired' => $isExpired,
            'days_until_expiry' => $daysUntil,
            'status' => $isExpired ? 'expired' : ($daysUntil <= 30 ? 'expiring_soon' : 'active'),
            'premium' => $row->premium ? (float)$row->premium : null
        ];
        
        if (isset($row->vehicle)) {
            $data['vehicle'] = [
                'id' => (int)$row->vehicle_id,
                'registration_number' => $row->vehicle,
                'name' => trim(($row->make ?? '') . ' ' . ($row->model ?? ''))
            ];
        }
        
        if ($detailed) {
            $data['coverage_amount'] = $row->coverage_amount ? (float)$row->coverage_amount : null;
            $data['notes'] = $row->notes ?? null;
            $data['created_at'] = $row->created_at;
            $data['updated_at'] = $row->updated_at ?? null;
        }
        
        return $data;
    }
}
