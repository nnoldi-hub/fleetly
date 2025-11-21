<?php
/**
 * Model WorkOrder
 * Gestionare ordine de lucru pentru service intern (atelier propriu)
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

require_once __DIR__ . '/../../core/Model.php';

class WorkOrder extends Model {
    protected $table = 'work_orders';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Generare număr unic ordine de lucru
     * 
     * @param int $tenantId ID tenant
     * @return string Număr ordine (ex: WO-2025-001)
     */
    public function generateWorkOrderNumber($tenantId) {
        $year = date('Y');
        
        // Găsim ultima ordine din acest an pentru acest tenant
        $sql = "SELECT work_order_number FROM work_orders 
                WHERE tenant_id = ? AND work_order_number LIKE ?
                ORDER BY id DESC LIMIT 1";
        
        $result = $this->db->fetchOn($this->table, $sql, [$tenantId, "WO-$year-%"]);
        
        if ($result) {
            // Extragem numărul și incrementăm
            preg_match('/WO-\d{4}-(\d+)/', $result['work_order_number'], $matches);
            $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf("WO-%d-%03d", $year, $nextNumber);
    }
    
    /**
     * Creare ordine de lucru nouă
     * 
     * @param array $data Date ordine
     * @return int ID ordine creată
     */
    public function createWorkOrder($data) {
        // Validare câmpuri obligatorii
        $required = ['tenant_id', 'vehicle_id', 'service_id', 'created_by'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Câmpul $field este obligatoriu");
            }
        }
        
        // Generare număr ordine dacă nu există
        if (!isset($data['work_order_number'])) {
            $data['work_order_number'] = $this->generateWorkOrderNumber($data['tenant_id']);
        }
        
        // Setare dată intrare dacă nu există
        if (!isset($data['entry_date'])) {
            $data['entry_date'] = date('Y-m-d H:i:s');
        }
        
        // Preparare date pentru insert
        $insertData = [
            'tenant_id' => $data['tenant_id'],
            'vehicle_id' => $data['vehicle_id'],
            'service_id' => $data['service_id'],
            'work_order_number' => $data['work_order_number'],
            'entry_date' => $data['entry_date'],
            'created_by' => $data['created_by'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending'
        ];
        
        // Câmpuri opționale
        $optionalFields = [
            'appointment_id', 'estimated_completion', 'odometer_reading',
            'assigned_mechanic_id', 'diagnosis', 'work_description',
            'customer_notes', 'internal_notes', 'estimated_hours'
        ];
        
        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $insertData[$field] = $data[$field];
            }
        }
        
        return $this->create($insertData);
    }
    
    /**
     * Obține toate ordinele de lucru pentru un tenant
     * 
     * @param int $tenantId ID tenant
     * @param array $filters Filtre (status, priority, mechanic_id, date_from, date_to)
     * @param int $limit Limită rezultate
     * @param int $offset Offset
     * @return array Lista ordine de lucru
     */
    public function getAllByTenant($tenantId, $filters = [], $limit = null, $offset = null) {
        $sql = "SELECT wo.*, 
                       v.plate_number, v.make, v.model, v.year,
                       m.name as mechanic_name,
                       s.name as service_name
                FROM work_orders wo
                JOIN vehicles v ON wo.vehicle_id = v.id
                LEFT JOIN service_mechanics m ON wo.assigned_mechanic_id = m.id
                LEFT JOIN services s ON wo.service_id = s.id
                WHERE wo.tenant_id = ?";
        
        $params = [$tenantId];
        
        // Aplicare filtre
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
                $sql .= " AND wo.status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $sql .= " AND wo.status = ?";
                $params[] = $filters['status'];
            }
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND wo.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['mechanic_id'])) {
            $sql .= " AND wo.assigned_mechanic_id = ?";
            $params[] = $filters['mechanic_id'];
        }
        
        if (!empty($filters['vehicle_id'])) {
            $sql .= " AND wo.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(wo.entry_date) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(wo.entry_date) <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Sortare
        $sql .= " ORDER BY 
                  CASE wo.priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'normal' THEN 3 
                    WHEN 'low' THEN 4 
                  END,
                  wo.entry_date DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    /**
     * Obține ordine de lucru active (în lucru)
     * 
     * @param int $tenantId ID tenant
     * @param int $serviceId ID service intern (opțional)
     * @return array Ordine active
     */
    public function getActiveWorkOrders($tenantId, $serviceId = null) {
        $filters = [
            'status' => ['pending', 'in_progress', 'waiting_parts']
        ];
        
        if ($serviceId) {
            $sql = "SELECT wo.*, 
                           v.plate_number, v.make, v.model,
                           m.name as mechanic_name
                    FROM work_orders wo
                    JOIN vehicles v ON wo.vehicle_id = v.id
                    LEFT JOIN service_mechanics m ON wo.assigned_mechanic_id = m.id
                    WHERE wo.tenant_id = ? AND wo.service_id = ?
                    AND wo.status IN ('pending', 'in_progress', 'waiting_parts')
                    ORDER BY wo.priority, wo.entry_date";
            
            return $this->db->fetchAllOn($this->table, $sql, [$tenantId, $serviceId]);
        }
        
        return $this->getAllByTenant($tenantId, $filters);
    }
    
    /**
     * Obține detalii complete ordine de lucru
     * 
     * @param int $id ID ordine
     * @param int $tenantId ID tenant (pentru verificare acces)
     * @return array|null Detalii ordine
     */
    public function getWorkOrderDetails($id, $tenantId) {
        $sql = "SELECT wo.*, 
                       v.plate_number, v.make, v.model, v.year, v.vin,
                       m.name as mechanic_name, m.specialization, m.hourly_rate as mechanic_rate,
                       s.name as service_name,
                       u.name as created_by_name
                FROM work_orders wo
                JOIN vehicles v ON wo.vehicle_id = v.id
                LEFT JOIN service_mechanics m ON wo.assigned_mechanic_id = m.id
                LEFT JOIN services s ON wo.service_id = s.id
                LEFT JOIN users u ON wo.created_by = u.id
                WHERE wo.id = ? AND wo.tenant_id = ?";
        
        $workOrder = $this->db->fetchOn($this->table, $sql, [$id, $tenantId]);
        
        if (!$workOrder) {
            return null;
        }
        
        // Adăugăm piese utilizate
        $sql = "SELECT * FROM work_order_parts WHERE work_order_id = ? ORDER BY id";
        $workOrder['parts'] = $this->db->fetchAllOn('work_order_parts', $sql, [$id]);
        
        // Adăugăm manoperă
        $sql = "SELECT wol.*, m.name as mechanic_name 
                FROM work_order_labor wol
                JOIN service_mechanics m ON wol.mechanic_id = m.id
                WHERE wol.work_order_id = ? 
                ORDER BY wol.start_time";
        $workOrder['labor'] = $this->db->fetchAllOn('work_order_labor', $sql, [$id]);
        
        // Adăugăm checklist
        $sql = "SELECT * FROM work_order_checklist WHERE work_order_id = ? ORDER BY id";
        $workOrder['checklist'] = $this->db->fetchAllOn('work_order_checklist', $sql, [$id]);
        
        return $workOrder;
    }
    
    /**
     * Actualizare status ordine de lucru
     * 
     * @param int $id ID ordine
     * @param string $status Noul status
     * @param int $tenantId ID tenant (verificare acces)
     * @return bool Success
     */
    public function updateStatus($id, $status, $tenantId) {
        // Verificare acces
        if (!$this->checkTenantAccess($id, $tenantId)) {
            throw new Exception("Acces interzis");
        }
        
        $updateData = ['status' => $status];
        
        // Dacă se marchează ca finalizat, setăm actual_completion
        if ($status === 'completed' || $status === 'delivered') {
            $updateData['actual_completion'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($id, $updateData);
    }
    
    /**
     * Alocare mecanic la ordine de lucru
     * 
     * @param int $id ID ordine
     * @param int $mechanicId ID mecanic
     * @param int $tenantId ID tenant
     * @return bool Success
     */
    public function assignMechanic($id, $mechanicId, $tenantId) {
        if (!$this->checkTenantAccess($id, $tenantId)) {
            throw new Exception("Acces interzis");
        }
        
        $updateData = [
            'assigned_mechanic_id' => $mechanicId,
            'status' => 'in_progress' // Schimbăm automat în "în lucru"
        ];
        
        return $this->update($id, $updateData);
    }
    
    /**
     * Adăugare piesă utilizată
     * 
     * @param int $workOrderId ID ordine
     * @param array $partData Date piesă
     * @return int ID piesă adăugată
     */
    public function addPart($workOrderId, $partData) {
        $required = ['part_name', 'quantity', 'unit_price'];
        foreach ($required as $field) {
            if (!isset($partData[$field])) {
                throw new Exception("Câmpul $field este obligatoriu");
            }
        }
        
        $insertData = [
            'work_order_id' => $workOrderId,
            'part_name' => $partData['part_name'],
            'quantity' => $partData['quantity'],
            'unit_price' => $partData['unit_price'],
            'part_number' => $partData['part_number'] ?? null,
            'supplier' => $partData['supplier'] ?? null,
            'notes' => $partData['notes'] ?? null
        ];
        
        $sql = "INSERT INTO work_order_parts (" . implode(', ', array_keys($insertData)) . ") 
                VALUES (" . implode(', ', array_fill(0, count($insertData), '?')) . ")";
        
        $this->db->queryOn('work_order_parts', $sql, array_values($insertData));
        return $this->db->lastInsertIdOn('work_order_parts');
    }
    
    /**
     * Începere task manoperă
     * 
     * @param int $workOrderId ID ordine
     * @param int $mechanicId ID mecanic
     * @param array $taskData Date task
     * @return int ID labor record
     */
    public function startLaborTask($workOrderId, $mechanicId, $taskData = []) {
        // Obținem tariful mecanic
        $sql = "SELECT hourly_rate FROM service_mechanics WHERE id = ?";
        $mechanic = $this->db->fetchOn('service_mechanics', $sql, [$mechanicId]);
        
        if (!$mechanic) {
            throw new Exception("Mecanic invalid");
        }
        
        $insertData = [
            'work_order_id' => $workOrderId,
            'mechanic_id' => $mechanicId,
            'start_time' => date('Y-m-d H:i:s'),
            'hourly_rate' => $mechanic['hourly_rate'],
            'task_description' => $taskData['task_description'] ?? null
        ];
        
        $sql = "INSERT INTO work_order_labor (" . implode(', ', array_keys($insertData)) . ") 
                VALUES (" . implode(', ', array_fill(0, count($insertData), '?')) . ")";
        
        $this->db->queryOn('work_order_labor', $sql, array_values($insertData));
        return $this->db->lastInsertIdOn('work_order_labor');
    }
    
    /**
     * Finalizare task manoperă
     * 
     * @param int $laborId ID labor record
     * @return bool Success
     */
    public function endLaborTask($laborId) {
        $sql = "UPDATE work_order_labor 
                SET end_time = ? 
                WHERE id = ?";
        
        $this->db->queryOn('work_order_labor', $sql, [date('Y-m-d H:i:s'), $laborId]);
        return true;
    }
    
    /**
     * Adăugare item în checklist
     * 
     * @param int $workOrderId ID ordine
     * @param array $itemData Date item
     * @return int ID item
     */
    public function addChecklistItem($workOrderId, $itemData) {
        $insertData = [
            'work_order_id' => $workOrderId,
            'item' => $itemData['item'],
            'is_checked' => $itemData['is_checked'] ?? 0,
            'status' => $itemData['status'] ?? 'ok',
            'notes' => $itemData['notes'] ?? null
        ];
        
        $sql = "INSERT INTO work_order_checklist (" . implode(', ', array_keys($insertData)) . ") 
                VALUES (" . implode(', ', array_fill(0, count($insertData), '?')) . ")";
        
        $this->db->queryOn('work_order_checklist', $sql, array_values($insertData));
        return $this->db->lastInsertIdOn('work_order_checklist');
    }
    
    /**
     * Actualizare item checklist
     * 
     * @param int $itemId ID item
     * @param array $data Date noi
     * @return bool Success
     */
    public function updateChecklistItem($itemId, $data) {
        $allowedFields = ['is_checked', 'status', 'notes'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        $setParts = [];
        foreach ($updateData as $field => $value) {
            $setParts[] = "$field = ?";
        }
        
        $sql = "UPDATE work_order_checklist SET " . implode(', ', $setParts) . " WHERE id = ?";
        $params = array_merge(array_values($updateData), [$itemId]);
        
        $this->db->queryOn('work_order_checklist', $sql, $params);
        return true;
    }
    
    /**
     * Statistici dashboard atelier
     * 
     * @param int $tenantId ID tenant
     * @param int $serviceId ID service intern
     * @return array Statistici
     */
    public function getWorkshopStats($tenantId, $serviceId) {
        $stats = [];
        
        // Ordine active pe status
        $sql = "SELECT status, COUNT(*) as count 
                FROM work_orders 
                WHERE tenant_id = ? AND service_id = ? 
                AND status IN ('pending', 'in_progress', 'waiting_parts')
                GROUP BY status";
        $stats['by_status'] = $this->db->fetchAllOn($this->table, $sql, [$tenantId, $serviceId]);
        
        // Ordine pe prioritate
        $sql = "SELECT priority, COUNT(*) as count 
                FROM work_orders 
                WHERE tenant_id = ? AND service_id = ? 
                AND status IN ('pending', 'in_progress')
                GROUP BY priority";
        $stats['by_priority'] = $this->db->fetchAllOn($this->table, $sql, [$tenantId, $serviceId]);
        
        // Statistici astăzi
        $sql = "SELECT 
                    COUNT(*) as total_today,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today,
                    SUM(actual_hours) as hours_worked_today,
                    SUM(total_cost) as revenue_today
                FROM work_orders 
                WHERE tenant_id = ? AND service_id = ? 
                AND DATE(entry_date) = CURDATE()";
        $stats['today'] = $this->db->fetchOn($this->table, $sql, [$tenantId, $serviceId]);
        
        // Capacitate atelier
        $sql = "SELECT capacity, hourly_rate FROM services WHERE id = ?";
        $service = $this->db->fetchOn('services', $sql, [$serviceId]);
        $stats['capacity'] = $service['capacity'] ?? 0;
        
        // Posturi ocupate
        $sql = "SELECT COUNT(*) as occupied 
                FROM work_orders 
                WHERE tenant_id = ? AND service_id = ? 
                AND status IN ('in_progress', 'waiting_parts')";
        $result = $this->db->fetchOn($this->table, $sql, [$tenantId, $serviceId]);
        $stats['occupied_posts'] = $result['occupied'] ?? 0;
        $stats['available_posts'] = $stats['capacity'] - $stats['occupied_posts'];
        
        return $stats;
    }
    
    /**
     * Verificare acces tenant
     * 
     * @param int $workOrderId ID ordine
     * @param int $tenantId ID tenant
     * @return bool True dacă are acces
     */
    public function checkTenantAccess($workOrderId, $tenantId) {
        $sql = "SELECT COUNT(*) as count FROM work_orders 
                WHERE id = ? AND tenant_id = ?";
        $result = $this->db->fetchOn($this->table, $sql, [$workOrderId, $tenantId]);
        return $result && $result['count'] > 0;
    }
}
