<?php
/**
 * Model Service
 * Gestionare service-uri partenere și interne (ateliere proprii)
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

require_once __DIR__ . '/../../core/Model.php';

class Service extends Model {
    protected $table = 'services';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Obține toate service-urile pentru un tenant
     * 
     * @param int $tenantId ID tenant
     * @param string $type Tip service: 'all', 'internal', 'external'
     * @param bool $activeOnly Doar service-uri active
     * @return array Lista service-uri
     */
    public function getAllByTenant($tenantId, $type = 'all', $activeOnly = true) {
        $sql = "SELECT * FROM services WHERE tenant_id = ?";
        $params = [$tenantId];
        
        if ($type !== 'all') {
            $sql .= " AND service_type = ?";
            $params[] = $type;
        }
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY service_type, name";
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    /**
     * Obține service-ul intern al unui tenant (dacă există)
     * 
     * @param int $tenantId ID tenant
     * @return array|null Datele service-ului intern sau null
     */
    public function getInternalService($tenantId) {
        $sql = "SELECT * FROM services 
                WHERE tenant_id = ? AND service_type = 'internal' AND is_active = 1 
                LIMIT 1";
        return $this->db->fetchOn($this->table, $sql, [$tenantId]);
    }
    
    /**
     * Verifică dacă un tenant are service intern configurat
     * 
     * @param int $tenantId ID tenant
     * @return bool True dacă există service intern activ
     */
    public function hasInternalService($tenantId) {
        $sql = "SELECT COUNT(*) as count FROM services 
                WHERE tenant_id = ? AND service_type = 'internal' AND is_active = 1";
        $result = $this->db->fetchOn($this->table, $sql, [$tenantId]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Creare service nou
     * 
     * @param array $data Date service
     * @return int ID-ul service-ului creat
     */
    public function createService($data) {
        // Validare date obligatorii
        $required = ['tenant_id', 'name', 'service_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Câmpul $field este obligatoriu");
            }
        }
        
        // Pentru service intern, verificăm dacă există deja unul
        if ($data['service_type'] === 'internal') {
            if ($this->hasInternalService($data['tenant_id'])) {
                throw new Exception("Există deja un service intern configurat pentru această companie");
            }
        }
        
        // Preparare date pentru insert
        $insertData = [
            'tenant_id' => $data['tenant_id'],
            'name' => $data['name'],
            'service_type' => $data['service_type'],
            'address' => $data['address'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'working_hours' => $data['working_hours'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ];
        
        // Câmpuri specifice pentru service intern
        if ($data['service_type'] === 'internal') {
            $insertData['capacity'] = $data['capacity'] ?? null;
            $insertData['hourly_rate'] = $data['hourly_rate'] ?? null;
        }
        
        // Procesare service_types (JSON)
        if (isset($data['service_types']) && is_array($data['service_types'])) {
            $insertData['service_types'] = json_encode($data['service_types']);
        } elseif (isset($data['service_types'])) {
            $insertData['service_types'] = $data['service_types'];
        }
        
        return $this->create($insertData);
    }
    
    /**
     * Actualizare service
     * 
     * @param int $id ID service
     * @param array $data Date noi
     * @return bool Success
     */
    public function updateService($id, $data) {
        $allowedFields = [
            'name', 'address', 'contact_phone', 'contact_email', 'contact_person',
            'working_hours', 'capacity', 'hourly_rate', 'rating', 'notes', 'is_active'
        ];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Procesare service_types (JSON)
        if (isset($data['service_types'])) {
            if (is_array($data['service_types'])) {
                $updateData['service_types'] = json_encode($data['service_types']);
            } else {
                $updateData['service_types'] = $data['service_types'];
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($id, $updateData);
    }
    
    /**
     * Ștergere soft (dezactivare) service
     * 
     * @param int $id ID service
     * @return bool Success
     */
    public function deleteService($id) {
        return $this->update($id, ['is_active' => 0]);
    }
    
    /**
     * Activare service
     * 
     * @param int $id ID service
     * @return bool Success
     */
    public function activateService($id) {
        return $this->update($id, ['is_active' => 1]);
    }
    
    /**
     * Obține statistici pentru un service
     * 
     * @param int $serviceId ID service
     * @param string $period Perioadă: 'month', 'quarter', 'year', 'all'
     * @return array Statistici
     */
    public function getServiceStats($serviceId, $period = 'month') {
        $dateFilter = $this->getDateFilter($period);
        
        // Total intervenții
        $sql = "SELECT COUNT(*) as total_services, 
                       SUM(cost_total) as total_revenue,
                       AVG(cost_total) as avg_cost
                FROM service_history 
                WHERE service_id = ? $dateFilter";
        
        $stats = $this->db->fetchOn($this->table, $sql, [$serviceId]);
        
        // Distribuție pe tipuri
        $sql = "SELECT service_type, COUNT(*) as count, SUM(cost_total) as total_cost
                FROM service_history 
                WHERE service_id = ? $dateFilter
                GROUP BY service_type
                ORDER BY count DESC";
        
        $stats['by_type'] = $this->db->fetchAllOn($this->table, $sql, [$serviceId]);
        
        // Pentru service intern, adăugăm statistici ordine de lucru
        $service = $this->find($serviceId);
        if ($service && $service['service_type'] === 'internal') {
            $sql = "SELECT 
                        COUNT(*) as total_work_orders,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                        AVG(actual_hours) as avg_hours,
                        SUM(total_cost) as total_revenue
                    FROM work_orders 
                    WHERE service_id = ? $dateFilter";
            
            $stats['work_orders'] = $this->db->fetchOn($this->table, $sql, [$serviceId]);
        }
        
        return $stats;
    }
    
    /**
     * Căutare service-uri
     * 
     * @param int $tenantId ID tenant
     * @param string $searchTerm Termen căutare
     * @param string $type Tip service
     * @return array Rezultate căutare
     */
    public function searchServices($tenantId, $searchTerm, $type = 'all') {
        $sql = "SELECT * FROM services 
                WHERE tenant_id = ? 
                AND (name LIKE ? OR address LIKE ? OR contact_person LIKE ?)";
        
        $params = [$tenantId, "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
        
        if ($type !== 'all') {
            $sql .= " AND service_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY is_active DESC, name";
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    /**
     * Obține service-urile cu rating-ul cel mai mare
     * 
     * @param int $tenantId ID tenant
     * @param int $limit Număr rezultate
     * @return array Top service-uri
     */
    public function getTopRatedServices($tenantId, $limit = 5) {
        $sql = "SELECT * FROM services 
                WHERE tenant_id = ? AND rating IS NOT NULL AND is_active = 1
                ORDER BY rating DESC, name
                LIMIT ?";
        
        return $this->db->fetchAllOn($this->table, $sql, [$tenantId, $limit]);
    }
    
    /**
     * Actualizare rating service bazat pe feedback-uri
     * 
     * @param int $serviceId ID service
     * @return bool Success
     */
    public function updateRating($serviceId) {
        // Această metodă poate fi extinsă când adăugăm sistem de review-uri
        // Deocamdată, poate fi apelată manual pentru actualizare rating
        
        $sql = "SELECT AVG(rating) as avg_rating 
                FROM service_history 
                WHERE service_id = ? AND rating IS NOT NULL";
        
        $result = $this->db->fetchOn($this->table, $sql, [$serviceId]);
        
        if ($result && $result['avg_rating']) {
            return $this->update($serviceId, ['rating' => round($result['avg_rating'], 2)]);
        }
        
        return false;
    }
    
    /**
     * Helper: Generare filtru dată pentru perioade
     * 
     * @param string $period Perioadă
     * @return string SQL date filter
     */
    private function getDateFilter($period) {
        switch ($period) {
            case 'month':
                return " AND DATE(service_date) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            case 'quarter':
                return " AND DATE(service_date) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            case 'year':
                return " AND DATE(service_date) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            case 'all':
            default:
                return "";
        }
    }
    
    /**
     * Obține tipurile de servicii pentru un service (parse JSON)
     * 
     * @param int $serviceId ID service
     * @return array Tipuri de servicii
     */
    public function getServiceTypes($serviceId) {
        $service = $this->find($serviceId);
        
        if (!$service || empty($service['service_types'])) {
            return [];
        }
        
        $types = json_decode($service['service_types'], true);
        return is_array($types) ? $types : [];
    }
    
    /**
     * Verificare acces tenant la service
     * 
     * @param int $serviceId ID service
     * @param int $tenantId ID tenant
     * @return bool True dacă tenant-ul are acces
     */
    public function checkTenantAccess($serviceId, $tenantId) {
        $sql = "SELECT COUNT(*) as count FROM services 
                WHERE id = ? AND tenant_id = ?";
        $result = $this->db->fetchOn($this->table, $sql, [$serviceId, $tenantId]);
        return $result && $result['count'] > 0;
    }
}
