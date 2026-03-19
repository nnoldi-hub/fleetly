<?php
/**
 * Fuel API Controller
 * 
 * CRUD pentru înregistrări carburant via API mobile.
 * Folosește tabela fuel_consumption.
 */
class FuelController {
    
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
     * GET /api/v1/fuel
     */
    public function index() {
        $this->initTenantDb();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $vehicleId = $_GET['vehicle_id'] ?? null;
        $driverId = $_GET['driver_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['1=1'];
            $params = [];
            
            if ($vehicleId) {
                $where[] = "f.vehicle_id = ?";
                $params[] = (int)$vehicleId;
            }
            
            if ($driverId) {
                $where[] = "f.driver_id = ?";
                $params[] = (int)$driverId;
            }
            
            if ($dateFrom) {
                $where[] = "f.fuel_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $where[] = "f.fuel_date <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Count
            $stmt = $this->tenantDb->prepare("SELECT COUNT(*) as total FROM fuel_consumption f WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get records
            $sql = "
                SELECT f.*, v.registration_number as vehicle, v.brand, v.model,
                       d.name as driver_name
                FROM fuel_consumption f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE {$whereClause}
                ORDER BY f.fuel_date DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $records = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $records[] = $this->formatFuelEntry($row);
            }
            
            ApiResponse::paginated($records, $page, $perPage, $total, 'Fuel entries retrieved');
            
        } catch (PDOException $e) {
            error_log('Fuel list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load fuel entries', 500);
        }
    }
    
    /**
     * GET /api/v1/fuel/stats
     */
    public function stats() {
        $this->initTenantDb();
        
        $vehicleId = $_GET['vehicle_id'] ?? null;
        $period = $_GET['period'] ?? 'month'; // week, month, year
        
        try {
            $params = [];
            $vehicleFilter = '';
            if ($vehicleId) {
                $vehicleFilter = " AND f.vehicle_id = ?";
                $params[] = (int)$vehicleId;
            }
            
            switch ($period) {
                case 'week':
                    $dateFilter = " AND f.fuel_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'year':
                    $dateFilter = " AND f.fuel_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
                default:
                    $dateFilter = " AND f.fuel_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            }
            
            // Overall stats
            $sql = "
                SELECT 
                    COUNT(*) as total_entries,
                    COALESCE(SUM(liters), 0) as total_liters,
                    COALESCE(SUM(total_cost), 0) as total_cost,
                    COALESCE(AVG(cost_per_liter), 0) as avg_price_per_liter
                FROM fuel_consumption f
                WHERE 1=1 {$dateFilter} {$vehicleFilter}
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            $overall = $stmt->fetch(PDO::FETCH_OBJ);
            
            // By vehicle (top 5 consumers)
            $sql = "
                SELECT v.id, v.registration_number, v.brand, v.model,
                       SUM(f.liters) as total_liters,
                       SUM(f.total_cost) as total_cost
                FROM fuel_consumption f
                JOIN vehicles v ON f.vehicle_id = v.id
                WHERE 1=1 {$dateFilter}
                GROUP BY v.id
                ORDER BY total_liters DESC
                LIMIT 5
            ";
            
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $byVehicle = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $byVehicle[] = [
                    'vehicle_id' => (int)$row->id,
                    'registration_number' => $row->registration_number,
                    'make' => $row->brand,
                    'model' => $row->model,
                    'total_liters' => (float)$row->total_liters,
                    'total_cost' => (float)$row->total_cost
                ];
            }
            
            ApiResponse::success([
                'period' => $period,
                'overall' => [
                    'total_entries' => (int)$overall->total_entries,
                    'total_liters' => round((float)$overall->total_liters, 2),
                    'total_cost' => round((float)$overall->total_cost, 2),
                    'avg_price_per_liter' => round((float)$overall->avg_price_per_liter, 3)
                ],
                'top_consumers' => $byVehicle
            ]);
            
        } catch (PDOException $e) {
            error_log('Fuel stats error: ' . $e->getMessage());
            ApiResponse::error('Failed to load fuel statistics', 500);
        }
    }
    
    /**
     * GET /api/v1/fuel/{id}
     */
    public function show($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("
                SELECT f.*, v.registration_number as vehicle, v.brand, v.model,
                       d.name as driver_name
                FROM fuel_consumption f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                LEFT JOIN drivers d ON f.driver_id = d.id
                WHERE f.id = ?
            ");
            $stmt->execute([$id]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$entry) {
                ApiResponse::notFound('Fuel entry not found');
            }
            
            ApiResponse::success($this->formatFuelEntry($entry, true));
            
        } catch (PDOException $e) {
            error_log('Fuel show error: ' . $e->getMessage());
            ApiResponse::error('Failed to load fuel entry', 500);
        }
    }
    
    /**
     * POST /api/v1/fuel
     */
    public function store() {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $errors = $this->validateFuelEntry($input);
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        // Calculate total_cost if not provided
        $liters = (float)$input['liters'];
        $costPerLiter = (float)$input['cost_per_liter'];
        $totalCost = $input['total_cost'] ?? ($liters * $costPerLiter);
        
        try {
            $stmt = $this->tenantDb->prepare("
                INSERT INTO fuel_consumption (
                    vehicle_id, driver_id, fuel_date, liters, cost_per_liter,
                    total_cost, currency, mileage, fuel_type, station,
                    receipt_number, payment_method, is_full_tank, trip_purpose,
                    location, notes, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['vehicle_id'],
                $input['driver_id'] ?? null,
                $input['fuel_date'],
                $liters,
                $costPerLiter,
                $totalCost,
                $input['currency'] ?? 'RON',
                $input['mileage'] ?? null,
                $input['fuel_type'],
                trim($input['station'] ?? ''),
                trim($input['receipt_number'] ?? ''),
                $input['payment_method'] ?? 'card',
                $input['is_full_tank'] ?? 1,
                $input['trip_purpose'] ?? 'business',
                trim($input['location'] ?? ''),
                trim($input['notes'] ?? '')
            ]);
            
            $entryId = $this->tenantDb->lastInsertId();
            
            // Update vehicle mileage if provided
            if (!empty($input['mileage'])) {
                $stmt = $this->tenantDb->prepare("
                    UPDATE vehicles SET current_mileage = ? WHERE id = ? AND (current_mileage IS NULL OR current_mileage < ?)
                ");
                $stmt->execute([$input['mileage'], $input['vehicle_id'], $input['mileage']]);
            }
            
            $stmt = $this->tenantDb->prepare("SELECT * FROM fuel_consumption WHERE id = ?");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success($this->formatFuelEntry($entry, true), 'Fuel entry created', 201);
            
        } catch (PDOException $e) {
            error_log('Fuel create error: ' . $e->getMessage());
            ApiResponse::error('Failed to create fuel entry', 500);
        }
    }
    
    /**
     * PUT /api/v1/fuel/{id}
     */
    public function update($id) {
        $this->initTenantDb();
        $input = $this->getJsonInput();
        
        $stmt = $this->tenantDb->prepare("SELECT id FROM fuel_consumption WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            ApiResponse::notFound('Fuel entry not found');
        }
        
        $allowed = ['vehicle_id', 'driver_id', 'fuel_date', 'liters', 'cost_per_liter',
                    'total_cost', 'currency', 'mileage', 'fuel_type', 'station',
                    'receipt_number', 'payment_method', 'is_full_tank', 'trip_purpose',
                    'location', 'notes'];
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
            $sql = "UPDATE fuel_consumption SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->tenantDb->prepare($sql);
            $stmt->execute($params);
            
            $this->show($id);
            
        } catch (PDOException $e) {
            error_log('Fuel update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update fuel entry', 500);
        }
    }
    
    /**
     * DELETE /api/v1/fuel/{id}
     */
    public function destroy($id) {
        $this->initTenantDb();
        
        try {
            $stmt = $this->tenantDb->prepare("DELETE FROM fuel_consumption WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Fuel entry not found');
            }
            
            ApiResponse::success(null, 'Fuel entry deleted');
            
        } catch (PDOException $e) {
            error_log('Fuel delete error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete fuel entry', 500);
        }
    }
    
    // ===== Helpers =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    private function validateFuelEntry($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['vehicle_id'])) {
                $errors['vehicle_id'] = 'Vehicle is required';
            }
            if (empty($input['fuel_date'])) {
                $errors['fuel_date'] = 'Fuel date is required';
            }
            if (empty($input['liters']) || $input['liters'] <= 0) {
                $errors['liters'] = 'Liters must be positive';
            }
            if (empty($input['cost_per_liter']) || $input['cost_per_liter'] <= 0) {
                $errors['cost_per_liter'] = 'Cost per liter must be positive';
            }
            if (empty($input['fuel_type'])) {
                $errors['fuel_type'] = 'Fuel type is required';
            }
        }
        
        $validFuelTypes = ['petrol', 'diesel', 'electric', 'gas'];
        if (isset($input['fuel_type']) && !in_array($input['fuel_type'], $validFuelTypes)) {
            $errors['fuel_type'] = 'Invalid fuel type';
        }
        
        $validPaymentMethods = ['cash', 'card', 'voucher', 'corporate_card'];
        if (isset($input['payment_method']) && !in_array($input['payment_method'], $validPaymentMethods)) {
            $errors['payment_method'] = 'Invalid payment method';
        }
        
        return $errors;
    }
    
    private function formatFuelEntry($row, $detailed = false) {
        $data = [
            'id' => (int)$row->id,
            'fuel_date' => $row->fuel_date,
            'liters' => (float)$row->liters,
            'cost_per_liter' => (float)$row->cost_per_liter,
            'total_cost' => (float)$row->total_cost,
            'currency' => $row->currency ?? 'RON',
            'fuel_type' => $row->fuel_type,
            'is_full_tank' => (bool)($row->is_full_tank ?? true),
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
            $data['mileage'] = $row->mileage ? (int)$row->mileage : null;
            $data['station'] = $row->station ?? null;
            $data['receipt_number'] = $row->receipt_number ?? null;
            $data['payment_method'] = $row->payment_method ?? 'card';
            $data['trip_purpose'] = $row->trip_purpose ?? 'business';
            $data['location'] = $row->location ?? null;
            $data['notes'] = $row->notes ?? null;
            $data['created_at'] = $row->created_at ?? null;
            $data['updated_at'] = $row->updated_at ?? null;
        }
        
        return $data;
    }
}
