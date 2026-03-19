<?php
/**
 * Maintenance API Controller
 * 
 * CRUD pentru înregistrări de mentenanță via API mobile.
 * Compatibil cu structura existentă a bazei de date.
 */
class MaintenanceController {
    
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
     * GET /api/v1/maintenance
     */
    public function index() {
        $this->initTenantDb();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $vehicleId = $_GET['vehicle_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['1=1'];
            $params = [];
            
            if ($vehicleId) {
                $where[] = "m.vehicle_id = ?";
                $params[] = (int)$vehicleId;
            }
            
            if ($status) {
                $where[] = "m.status = ?";
                $params[] = $status;
            }
            
            if ($type) {
                $where[] = "m.maintenance_type = ?";
                $params[] = $type;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Count
            $stmt = $this->tenantDb->prepare("SELECT COUNT(*) as total FROM maintenance m WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get records
            $sql = "
                SELECT m.*, v.registration_number as vehicle, v.brand, v.model
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE {$whereClause}
                ORDER BY COALESCE(m.service_date, m.created_at) DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $records[] = $this->formatMaintenance($row);
            }
            
            ApiResponse::paginated($records, $page, $perPage, $total, 'Maintenance records retrieved');
            
        } catch (PDOException $e) {
            error_log('Maintenance list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load maintenance records', 500);
        }
    }
    
    /**
     * GET /api/v1/maintenance/scheduled
     */
    public function scheduled() {
        $this->initTenantDb();
        
        $days = min(90, max(1, (int)($_GET['days'] ?? 30)));
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT m.*, v.registration_number as vehicle, v.brand, v.model
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.status = 'scheduled'
                  AND m.next_service_date IS NOT NULL
                  AND m.next_service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY m.next_service_date ASC
            ");
            $stmt->execute([$days]);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $records[] = $this->formatMaintenance($row);
            }
            
            ApiResponse::success($records);
            
        } catch (PDOException $e) {
            error_log('Scheduled maintenance error: ' . $e->getMessage());
            ApiResponse::error('Failed to load scheduled maintenance', 500);
        }
    }
    
    /**
     * GET /api/v1/maintenance/{id}
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT m.*, v.registration_number as vehicle, v.brand, v.model,
                       d.name as driver_name
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                LEFT JOIN drivers d ON m.driver_id = d.id
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$record) {
                ApiResponse::notFound('Maintenance record not found');
            }
            
            ApiResponse::success($this->formatMaintenance($record, true));
            
        } catch (PDOException $e) {
            error_log('Maintenance show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load maintenance record', 500);
        }
    }
    
    /**
     * POST /api/v1/maintenance
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $errors = $this->validateMaintenance($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO maintenance (
                    vehicle_id, driver_id, maintenance_type, description,
                    cost, currency, mileage_at_service, service_date,
                    next_service_date, next_service_mileage, provider,
                    invoice_number, work_order_number, warranty_expiry_date,
                    parts_replaced, status, priority, file_path, notes,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['vehicle_id'],
                $input['driver_id'] ?? null,
                $input['maintenance_type'],
                trim($input['description']),
                $input['cost'] ?? 0,
                $input['currency'] ?? 'RON',
                $input['mileage_at_service'] ?? null,
                $input['service_date'],
                $input['next_service_date'] ?? null,
                $input['next_service_mileage'] ?? null,
                trim($input['provider'] ?? ''),
                trim($input['invoice_number'] ?? ''),
                trim($input['work_order_number'] ?? ''),
                $input['warranty_expiry_date'] ?? null,
                trim($input['parts_replaced'] ?? ''),
                $input['status'] ?? 'completed',
                $input['priority'] ?? 'medium',
                $input['file_path'] ?? null,
                trim($input['notes'] ?? '')
            ]);
            
            $recordId = $this->tenantDb->lastInsertId();
            
            $stmt = $this->tenantDb->prepare("SELECT * FROM maintenance WHERE id = ?");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success($this->formatMaintenance($record, true), 'Maintenance record created', 201);
            
        } catch (PDOException $e) {
            error_log('Maintenance create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create maintenance record', 500);
        }
    }
    
    /**
     * PUT /api/v1/maintenance/{id}
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM maintenance WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Maintenance record not found');
        }
        
        $allowed = ['vehicle_id', 'driver_id', 'maintenance_type', 'description',
                    'cost', 'currency', 'mileage_at_service', 'service_date',
                    'next_service_date', 'next_service_mileage', 'provider',
                    'invoice_number', 'work_order_number', 'warranty_expiry_date',
                    'parts_replaced', 'status', 'priority', 'file_path', 'notes'];
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
            $sql = "UPDATE maintenance SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Maintenance update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update maintenance record', 500);
        }
    }
    
    /**
     * DELETE /api/v1/maintenance/{id}
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("DELETE FROM maintenance WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Maintenance record not found');
            }
            
            ApiResponse::success(null, 'Maintenance record deleted');
            
        } catch (PDOException $e) {
            error_log('Maintenance delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete maintenance record', 500);
        }
    }
    
    // ===== Helpers =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    private function validateMaintenance($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['vehicle_id'])) {
                $errors['vehicle_id'] = 'Vehicle is required';
            }
            if (empty($input['maintenance_type'])) {
                $errors['maintenance_type'] = 'Maintenance type is required';
            }
            if (empty($input['description'])) {
                $errors['description'] = 'Description is required';
            }
            if (empty($input['service_date'])) {
                $errors['service_date'] = 'Service date is required';
            }
        }
        
        $validTypes = ['preventive', 'corrective', 'inspection', 'repair', 'service'];
        if (isset($input['maintenance_type']) && !in_array($input['maintenance_type'], $validTypes)) {
            $errors['maintenance_type'] = 'Invalid maintenance type';
        }
        
        $validStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        if (isset($input['status']) && !in_array($input['status'], $validStatuses)) {
            $errors['status'] = 'Invalid status';
        }
        
        return $errors;
    }
    
    private function formatMaintenance($row, $detailed = false) {
        // Type names
        $typeNames = [
            'preventive' => 'Preventive Maintenance',
            'corrective' => 'Corrective Maintenance',
            'inspection' => 'Inspection',
            'repair' => 'Repair',
            'service' => 'Service'
        ];
        
        $data = [
            'id' => (int)$row->id,
            'maintenance_type' => $row->maintenance_type,
            'maintenance_type_name' => $typeNames[$row->maintenance_type] ?? $row->maintenance_type,
            'description' => $row->description,
            'service_date' => $row->service_date,
            'next_service_date' => $row->next_service_date ?? null,
            'cost' => (float)($row->cost ?? 0),
            'currency' => $row->currency ?? 'RON',
            'status' => $row->status ?? 'completed',
            'priority' => $row->priority ?? 'medium',
            'vehicle' => [
                'id' => (int)$row->vehicle_id,
                'registration_number' => $row->vehicle ?? null,
                'make' => $row->brand ?? null,
                'model' => $row->model ?? null
            ]
        ];
        
        if ($detailed) {
            $data['driver_id'] = $row->driver_id ? (int)$row->driver_id : null;
            $data['driver_name'] = $row->driver_name ?? null;
            $data['mileage_at_service'] = $row->mileage_at_service ? (int)$row->mileage_at_service : null;
            $data['next_service_mileage'] = $row->next_service_mileage ? (int)$row->next_service_mileage : null;
            $data['provider'] = $row->provider ?? null;
            $data['invoice_number'] = $row->invoice_number ?? null;
            $data['work_order_number'] = $row->work_order_number ?? null;
            $data['warranty_expiry_date'] = $row->warranty_expiry_date ?? null;
            $data['parts_replaced'] = $row->parts_replaced ?? null;
            $data['file_path'] = $row->file_path ?? null;
            $data['notes'] = $row->notes ?? null;
            $data['created_at'] = $row->created_at ?? null;
            $data['updated_at'] = $row->updated_at ?? null;
        }
        
        return $data;
    }
}
