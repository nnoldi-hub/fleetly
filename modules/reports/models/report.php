<?php
// modules/reports/models/report.php

require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class Report extends Model {
    protected $table = 'reports';
    private $hasInsurance = null;
    
    public function __construct() {
        parent::__construct();
        $this->hasInsurance = $this->checkInsuranceTable();
    }

    private function checkInsuranceTable() {
        try {
            // Check once per request and cache
            $row = $this->db->fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'insurance'", []);
            return (bool)$row;
        } catch (Throwable $e) {
            return false;
        }
    }
    
    public function generateFleetReport($dateFrom, $dateTo, $vehicleId = '', $reportType = 'summary') {
        $vehicleFilter = '';
        $params = [$dateFrom, $dateTo];
        
        if (!empty($vehicleId)) {
            $vehicleFilter = 'AND v.id = ?';
            $params[] = $vehicleId;
        }
        
        // Statistici generale
        $summaryData = $this->getFleetSummary($dateFrom, $dateTo, $vehicleId);
        
        // Date pe vehicule
        $vehiclesData = $this->getVehiclesData($dateFrom, $dateTo, $vehicleId, $reportType);
        
        // Tendințe și comparații
        $trendsData = $this->getFleetTrends($dateFrom, $dateTo, $vehicleId);
        
        return [
            'summary' => $summaryData,
            'vehicles' => $vehiclesData,
            'trends' => $trendsData,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
    }
    
    public function generateVehicleReport($vehicleId, $dateFrom, $dateTo, $reportType = 'detailed') {
        // Informații vehicul
        $vehicleInfo = $this->getVehicleInfo($vehicleId);
        
        // Consumul de combustibil
        $fuelData = $this->getVehicleFuelData($vehicleId, $dateFrom, $dateTo);
        
        // Mentenanța
        $maintenanceData = $this->getVehicleMaintenanceData($vehicleId, $dateFrom, $dateTo);
        
        // Asigurări
        $insuranceData = $this->getVehicleInsuranceData($vehicleId, $dateFrom, $dateTo);
        
        // Timeline activități
        $timeline = $this->getVehicleTimeline($vehicleId, $dateFrom, $dateTo);
        
        // Costuri totale
        $costsData = $this->getVehicleCosts($vehicleId, $dateFrom, $dateTo);
        
        return [
            'vehicle' => $vehicleInfo,
            'fuel' => $fuelData,
            'maintenance' => $maintenanceData,
            'insurance' => $insuranceData,
            'timeline' => $timeline,
            'costs' => $costsData,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
    }
    
    public function generateCostAnalysis($dateFrom, $dateTo, $vehicleId = '', $analysisType = 'monthly', $costType = 'all') {
        $data = [];
        
        switch ($analysisType) {
            case 'daily':
                $data = $this->getDailyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType);
                break;
            case 'weekly':
                $data = $this->getWeeklyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType);
                break;
            case 'monthly':
                $data = $this->getMonthlyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType);
                break;
            case 'yearly':
                $data = $this->getYearlyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType);
                break;
        }
        
        // Adăugăm comparații și tendințe
        $data['trends'] = $this->getCostTrends($dateFrom, $dateTo, $vehicleId, $costType);
        $data['comparisons'] = $this->getCostComparisons($dateFrom, $dateTo, $vehicleId);
        
        return $data;
    }
    
    public function generateMaintenanceReport($dateFrom, $dateTo, $vehicleId = '', $maintenanceType = '', $status = '') {
        $whereConditions = ['m.service_date BETWEEN ? AND ?'];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($vehicleId)) {
            $whereConditions[] = 'm.vehicle_id = ?';
            $params[] = $vehicleId;
        }
        
        if (!empty($maintenanceType)) {
            $whereConditions[] = 'm.maintenance_type = ?';
            $params[] = $maintenanceType;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'm.status = ?';
            $params[] = $status;
        }
        
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
    // Înregistrări de mentenanță (alias-uri pentru compatibilitate cu view/CSV)
    $sql = "SELECT 
               m.id,
               m.vehicle_id,
               m.maintenance_type,
               m.description,
               m.provider AS service_provider,
               m.cost,
               m.status,
               m.service_date AS scheduled_date,
               NULL AS completed_date,
               v.registration_number AS license_plate,
               v.brand AS make,
               v.model,
               CONCAT(v.registration_number, ' - ', v.brand, ' ', v.model) as vehicle_info
        FROM maintenance m
        LEFT JOIN vehicles v ON m.vehicle_id = v.id
        {$whereClause}
        ORDER BY m.service_date DESC";
        
        $maintenanceRecords = $this->db->fetchAll($sql, $params);
        
        // Statistici
        $stats = $this->getMaintenanceStats($dateFrom, $dateTo, $vehicleId, $maintenanceType, $status);
        
        // Costuri pe tipuri de mentenanță
        $costBreakdown = $this->getMaintenanceCostBreakdown($dateFrom, $dateTo, $vehicleId);
        
        return [
            'maintenance_records' => $maintenanceRecords,
            'statistics' => $stats,
            'cost_breakdown' => $costBreakdown,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
    }
    
    public function generateFuelReport($dateFrom, $dateTo, $vehicleId = '', $reportType = 'consumption') {
        $whereConditions = ['f.fuel_date BETWEEN ? AND ?'];
        $params = [$dateFrom, $dateTo];
        
        if (!empty($vehicleId)) {
            $whereConditions[] = 'f.vehicle_id = ?';
            $params[] = $vehicleId;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Înregistrări combustibil
    $sql = "SELECT f.*, 
               v.registration_number AS license_plate, v.brand AS make, v.model,
               CONCAT(v.registration_number, ' - ', v.brand, ' ', v.model) as vehicle_info
                FROM fuel_consumption f
                LEFT JOIN vehicles v ON f.vehicle_id = v.id
                {$whereClause}
                ORDER BY f.fuel_date DESC";
        
        $fuelRecords = $this->db->fetchAll($sql, $params);
        
        // Calculăm consumul pentru fiecare înregistrare
        foreach ($fuelRecords as &$record) {
            $record['consumption'] = $this->calculateConsumption($record['vehicle_id'], $record['fuel_date'], $record['mileage']);
        }
        
        // Statistici combustibil
        $stats = $this->getFuelStats($dateFrom, $dateTo, $vehicleId);
        
        // Tendințe consum
        $trends = $this->getFuelTrends($dateFrom, $dateTo, $vehicleId);
        
        // Eficiența pe vehicul
        $efficiency = $this->getFuelEfficiency($dateFrom, $dateTo, $vehicleId);
        
        return [
            'fuel_records' => $fuelRecords,
            'statistics' => $stats,
            'trends' => $trends,
            'efficiency' => $efficiency,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
    }
    
    public function generateCustomReport($config) {
        $data = [
            'headers' => [],
            'rows' => [],
            'summary' => []
        ];
        
        $vehicleFilter = '';
        $params = [$config['date_from'], $config['date_to']];
        
        if (!empty($config['vehicle_ids'])) {
            $placeholders = str_repeat('?,', count($config['vehicle_ids']) - 1) . '?';
            $vehicleFilter = "AND v.id IN ($placeholders)";
            $params = array_merge($params, $config['vehicle_ids']);
        }
        
        // Construim query-ul în funcție de configurație
        $selectFields = ['v.license_plate', 'v.make', 'v.model'];
        $data['headers'] = ['Vehicul', 'Marca', 'Model'];
        
        if ($config['include_fuel']) {
            $data['headers'][] = 'Combustibil Consumat (L)';
            $data['headers'][] = 'Cost Combustibil (RON)';
        }
        
        if ($config['include_maintenance']) {
            $data['headers'][] = 'Nr. Mentenanțe';
            $data['headers'][] = 'Cost Mentenanță (RON)';
        }
        
        if ($config['include_insurance']) {
            $data['headers'][] = 'Cost Asigurări (RON)';
        }
        
        if ($config['include_costs']) {
            $data['headers'][] = 'Cost Total (RON)';
        }
        
        // Executăm query-ul principal
        $vehicles = $this->getVehiclesForCustomReport($config, $params, $vehicleFilter);
        
        foreach ($vehicles as $vehicle) {
            $row = [$vehicle['license_plate'], $vehicle['make'], $vehicle['model']];
            
            if ($config['include_fuel']) {
                $fuelData = $this->getVehicleFuelSummary($vehicle['id'], $config['date_from'], $config['date_to']);
                $row[] = number_format($fuelData['total_liters'], 2);
                $row[] = number_format($fuelData['total_cost'], 2);
            }
            
            if ($config['include_maintenance']) {
                $maintenanceData = $this->getVehicleMaintenanceSummary($vehicle['id'], $config['date_from'], $config['date_to']);
                $row[] = $maintenanceData['count'];
                $row[] = number_format($maintenanceData['total_cost'], 2);
            }
            
            if ($config['include_insurance']) {
                $insuranceData = $this->getVehicleInsuranceSummary($vehicle['id'], $config['date_from'], $config['date_to']);
                $row[] = number_format($insuranceData['total_cost'], 2);
            }
            
            if ($config['include_costs']) {
                $totalCost = 0;
                if ($config['include_fuel']) $totalCost += $fuelData['total_cost'] ?? 0;
                if ($config['include_maintenance']) $totalCost += $maintenanceData['total_cost'] ?? 0;
                if ($config['include_insurance']) $totalCost += $insuranceData['total_cost'] ?? 0;
                $row[] = number_format($totalCost, 2);
            }
            
            $data['rows'][] = $row;
        }
        
        return $data;
    }
    
    // Metode helper pentru calcule și statistici
    private function getFleetSummary($dateFrom, $dateTo, $vehicleId = '') {
        $vehicleFilter = '';
        $params = [$dateFrom, $dateTo];
        
        if (!empty($vehicleId)) {
            $vehicleFilter = 'AND v.id = ?';
            $params[] = $vehicleId;
        }
        
        if ($this->hasInsurance) {
            $sql = "SELECT 
                        COUNT(DISTINCT v.id) as total_vehicles,
                        COALESCE(SUM(f.liters), 0) as total_fuel_liters,
                        COALESCE(SUM(f.total_cost), 0) as total_fuel_cost,
                        COALESCE(SUM(m.cost), 0) as total_maintenance_cost,
                        COALESCE(SUM(i.annual_premium), 0) as total_insurance_cost,
                        AVG(f.cost_per_liter) as avg_fuel_price
                    FROM vehicles v
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    LEFT JOIN insurance i ON v.id = i.vehicle_id AND i.start_date <= ? AND i.end_date >= ?
                    WHERE v.status = 'active' {$vehicleFilter}";

            $params = array_merge($params, [$dateFrom, $dateTo], [$dateFrom, $dateTo]);
            if (!empty($vehicleId)) { $params[] = $vehicleId; }
            return $this->db->fetch($sql, $params);
        } else {
            // Fallback without insurance table
            $sql = "SELECT 
                        COUNT(DISTINCT v.id) as total_vehicles,
                        COALESCE(SUM(f.liters), 0) as total_fuel_liters,
                        COALESCE(SUM(f.total_cost), 0) as total_fuel_cost,
                        COALESCE(SUM(m.cost), 0) as total_maintenance_cost,
                        0 as total_insurance_cost,
                        AVG(f.cost_per_liter) as avg_fuel_price
                    FROM vehicles v
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    WHERE v.status = 'active' {$vehicleFilter}";

            $params = array_merge([$dateFrom, $dateTo], [$dateFrom, $dateTo]);
            if (!empty($vehicleId)) { $params[] = $vehicleId; }
            return $this->db->fetch($sql, $params);
        }
    }
    
    private function getVehiclesData($dateFrom, $dateTo, $vehicleId = '', $reportType = 'summary') {
        $vehicleFilter = '';
        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];
        
        if (!empty($vehicleId)) {
            $vehicleFilter = 'AND v.id = ?';
            $params[] = $vehicleId;
        }
        
        if ($this->hasInsurance) {
            $sql = "SELECT 
                        v.id,
                        v.registration_number AS license_plate,
                        v.brand AS make,
                        v.model,
                        v.year,
                        v.current_mileage AS odometer,
                        vt.name as vehicle_type,
                        COALESCE(SUM(f.liters), 0) as fuel_consumed,
                        COALESCE(SUM(f.total_cost), 0) as fuel_cost,
                        COALESCE(SUM(m.cost), 0) as maintenance_cost,
                        COALESCE(SUM(i.annual_premium), 0) as insurance_cost,
                        COUNT(DISTINCT f.id) as fuel_records_count,
                        COUNT(DISTINCT m.id) as maintenance_records_count
                    FROM vehicles v
                    LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    LEFT JOIN insurance i ON v.id = i.vehicle_id AND i.start_date <= ? AND i.end_date >= ?
                    WHERE v.status = 'active' {$vehicleFilter}
                    GROUP BY v.id
                    ORDER BY v.registration_number";
            // add date range for insurance
            $params = array_merge($params, [$dateFrom, $dateTo]);
        } else {
            $sql = "SELECT 
                        v.id,
                        v.registration_number AS license_plate,
                        v.brand AS make,
                        v.model,
                        v.year,
                        v.current_mileage AS odometer,
                        vt.name as vehicle_type,
                        COALESCE(SUM(f.liters), 0) as fuel_consumed,
                        COALESCE(SUM(f.total_cost), 0) as fuel_cost,
                        COALESCE(SUM(m.cost), 0) as maintenance_cost,
                        0 as insurance_cost,
                        COUNT(DISTINCT f.id) as fuel_records_count,
                        COUNT(DISTINCT m.id) as maintenance_records_count
                    FROM vehicles v
                    LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    WHERE v.status = 'active' {$vehicleFilter}
                    GROUP BY v.id
                    ORDER BY v.registration_number";
        }
        
        $vehicles = $this->db->fetchAll($sql, $params);
        
        // Calculăm consumul mediu pentru fiecare vehicul
        foreach ($vehicles as &$vehicle) {
            $vehicle['avg_consumption'] = $this->calculateAverageConsumption($vehicle['id'], $dateFrom, $dateTo);
            $vehicle['total_cost'] = $vehicle['fuel_cost'] + $vehicle['maintenance_cost'] + $vehicle['insurance_cost'];
        }
        
        return $vehicles;
    }
    
    private function getFleetTrends($dateFrom, $dateTo, $vehicleId = '') {
        // Calculăm tendințele pentru perioada anterioară
        $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / (24 * 3600);
        $prevDateFrom = date('Y-m-d', strtotime($dateFrom . " -{$daysDiff} days"));
        $prevDateTo = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
        
        $currentData = $this->getFleetSummary($dateFrom, $dateTo, $vehicleId);
        $previousData = $this->getFleetSummary($prevDateFrom, $prevDateTo, $vehicleId);
        
        return [
            'current' => $currentData,
            'previous' => $previousData,
            'changes' => [
                'fuel_cost_change' => $this->calculatePercentageChange($previousData['total_fuel_cost'], $currentData['total_fuel_cost']),
                'maintenance_cost_change' => $this->calculatePercentageChange($previousData['total_maintenance_cost'], $currentData['total_maintenance_cost']),
                'total_cost_change' => $this->calculatePercentageChange(
                    $previousData['total_fuel_cost'] + $previousData['total_maintenance_cost'],
                    $currentData['total_fuel_cost'] + $currentData['total_maintenance_cost']
                )
            ]
        ];
    }
    
    private function calculatePercentageChange($oldValue, $newValue) {
        if ($oldValue == 0) return $newValue > 0 ? 100 : 0;
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
    
        private function calculateAverageConsumption($vehicleId, $dateFrom, $dateTo) {
                $sql = "SELECT 
                                        SUM(f.liters) as total_liters,
                                        MAX(f.mileage) - MIN(f.mileage) as distance_traveled
                                FROM fuel_consumption f
                                WHERE f.vehicle_id = ? 
                                    AND f.fuel_date BETWEEN ? AND ?
                                    AND f.mileage > 0
                                ORDER BY f.fuel_date";
        
        $result = $this->db->fetch($sql, [$vehicleId, $dateFrom, $dateTo]);
        
        if ($result && $result['distance_traveled'] > 0) {
            return round(($result['total_liters'] / $result['distance_traveled']) * 100, 2);
        }
        
        return 0;
    }
    
        private function calculateConsumption($vehicleId, $currentDate, $currentOdometer) {
        // Găsim înregistrarea anterioară
                $sql = "SELECT mileage, liters
                FROM fuel_consumption 
                WHERE vehicle_id = ? 
                  AND fuel_date < ? 
                                    AND mileage > 0
                ORDER BY fuel_date DESC 
                LIMIT 1";
        
        $prevRecord = $this->db->fetch($sql, [$vehicleId, $currentDate]);
        
        if ($prevRecord && $currentOdometer > $prevRecord['mileage']) {
            $distance = $currentOdometer - $prevRecord['mileage'];
            return round(($prevRecord['liters'] / $distance) * 100, 2);
        }
        
        return 0;
    }
    
    // Metode pentru rapoarte specifice
    private function getVehicleInfo($vehicleId) {
    $sql = "SELECT 
            v.id,
            v.registration_number AS license_plate,
            v.brand AS make,
            v.model,
            v.year,
            v.current_mileage AS odometer,
            vt.name as vehicle_type_name,
            v.status,
            v.vin_number AS vin,
            v.color
        FROM vehicles v
        LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        WHERE v.id = ?";
        
    return $this->db->fetch($sql, [$vehicleId]);
    }
    
    private function getVehicleFuelData($vehicleId, $dateFrom, $dateTo) {
                $sql = "SELECT f.*, 
                                             LAG(f.mileage) OVER (ORDER BY f.fuel_date) as prev_odometer,
                                             LAG(f.liters) OVER (ORDER BY f.fuel_date) as prev_liters
                                FROM fuel_consumption f
                                WHERE f.vehicle_id = ? 
                                    AND f.fuel_date BETWEEN ? AND ?
                                ORDER BY f.fuel_date";
        
        $records = $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo]);
        
        // Calculăm consumul pentru fiecare înregistrare
        foreach ($records as &$record) {
            if ($record['prev_odometer'] && $record['mileage'] > $record['prev_odometer']) {
                $distance = $record['mileage'] - $record['prev_odometer'];
                $record['consumption'] = round(($record['prev_liters'] / max($distance,1)) * 100, 2);
            } else {
                $record['consumption'] = 0;
            }
        }
        
        return $records;
    }
    
    private function getVehicleMaintenanceData($vehicleId, $dateFrom, $dateTo) {
                $sql = "SELECT 
                                        m.id,
                                        m.service_date AS scheduled_date,
                                        NULL AS completed_date,
                                        m.maintenance_type,
                                        m.description,
                                        m.provider AS service_provider,
                                        m.cost,
                                        m.status
                                FROM maintenance m
                                WHERE m.vehicle_id = ? 
                                    AND m.service_date BETWEEN ? AND ?
                                ORDER BY m.service_date DESC";
        
                return $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo]);
    }
    
        private function getVehicleInsuranceData($vehicleId, $dateFrom, $dateTo) {
                if (!$this->hasInsurance) { return []; }
                $sql = "SELECT i.*
                                FROM insurance i
                                WHERE i.vehicle_id = ? 
                                    AND (i.start_date BETWEEN ? AND ? OR i.end_date BETWEEN ? AND ?)
                                ORDER BY i.start_date DESC";
        
                return $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo, $dateFrom, $dateTo]);
    }
    
    private function getVehicleTimeline($vehicleId, $dateFrom, $dateTo) {
        $timeline = [];
        
        // Fuel records
    $sql = "SELECT fuel_date as date, 'fuel' as type, 
               CONCAT('Alimentare: ', liters, 'L, ', total_cost, ' RON') as description,
               total_cost as cost, mileage as odometer, notes
                FROM fuel_consumption
                WHERE vehicle_id = ? AND fuel_date BETWEEN ? AND ?";
        
        $fuelRecords = $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo]);
        $timeline = array_merge($timeline, $fuelRecords);
        
        // Maintenance records
    $sql = "SELECT service_date as date, 'maintenance' as type,
                       CONCAT('Mentenanță: ', maintenance_type, ' - ', description) as description,
               cost, mileage_at_service as odometer, notes
                FROM maintenance
        WHERE vehicle_id = ? AND service_date BETWEEN ? AND ?";
        
        $maintenanceRecords = $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo]);
        $timeline = array_merge($timeline, $maintenanceRecords);
        
        // Insurance records
    $sql = "SELECT start_date as date, 'insurance' as type,
               CONCAT('Asigurare: ', insurance_type, ' - ', insurance_company) as description,
               premium_amount as cost, NULL as odometer, notes
                FROM insurance
                WHERE vehicle_id = ? AND start_date BETWEEN ? AND ?";
        
        $insuranceRecords = $this->db->fetchAll($sql, [$vehicleId, $dateFrom, $dateTo]);
        $timeline = array_merge($timeline, $insuranceRecords);
        
        // Sortăm cronologic
        usort($timeline, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $timeline;
    }
    
    private function getVehicleCosts($vehicleId, $dateFrom, $dateTo) {
        if ($this->hasInsurance) {
            $sql = "SELECT 
                        COALESCE(SUM(f.total_cost), 0) as fuel_cost,
                        COALESCE(SUM(m.cost), 0) as maintenance_cost,
                        COALESCE(SUM(i.premium_amount), 0) as insurance_cost
                    FROM vehicles v
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    LEFT JOIN insurance i ON v.id = i.vehicle_id AND i.start_date BETWEEN ? AND ?
                    WHERE v.id = ?";
            $result = $this->db->fetch($sql, [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo, $vehicleId]);
        } else {
            $sql = "SELECT 
                        COALESCE(SUM(f.total_cost), 0) as fuel_cost,
                        COALESCE(SUM(m.cost), 0) as maintenance_cost,
                        0 as insurance_cost
                    FROM vehicles v
                    LEFT JOIN fuel_consumption f ON v.id = f.vehicle_id AND f.fuel_date BETWEEN ? AND ?
                    LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.service_date BETWEEN ? AND ?
                    WHERE v.id = ?";
            $result = $this->db->fetch($sql, [$dateFrom, $dateTo, $dateFrom, $dateTo, $vehicleId]);
        }
        
        if ($result) {
            $result['total_cost'] = $result['fuel_cost'] + $result['maintenance_cost'] + $result['insurance_cost'];
        }
        
        return $result;
    }
    
    // Metode pentru salvarea rapoartelor generate
    public function saveGeneratedReport($data) {
        $sql = "INSERT INTO generated_reports (type, period_start, period_end, data, generated_at)
                VALUES (?, ?, ?, ?, ?)";
        
        return $this->db->query($sql, [
            $data['type'],
            $data['period_start'],
            $data['period_end'],
            $data['data'],
            $data['generated_at']
        ]);
    }
    
    public function getGeneratedReports($type = '', $limit = 10) {
        $whereClause = '';
        $params = [];
        
        if (!empty($type)) {
            $whereClause = 'WHERE type = ?';
            $params[] = $type;
        }
        
        $sql = "SELECT * FROM generated_reports 
                {$whereClause}
                ORDER BY generated_at DESC 
                LIMIT ?";
        
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Metode helper pentru analiza costurilor
    private function getMonthlyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType) {
        // Generate month buckets between dates (inclusive)
        $periods = [];
        $start = new DateTime($dateFrom);
        $start->modify('first day of this month');
        $end = new DateTime($dateTo);
        $end->modify('first day of next month');
        $cursor = clone $start;
        while ($cursor < $end) {
            $periods[$cursor->format('Y-m')] = [
                'period' => $cursor->format('Y-m'),
                'fuel_cost' => 0.0,
                'maintenance_cost' => 0.0,
                'insurance_cost' => 0.0,
                'other_cost' => 0.0,
                'total_cost' => 0.0,
            ];
            $cursor->modify('+1 month');
        }

        $vehicleFilter = '';
        $paramsFuel = [$dateFrom, $dateTo];
        $paramsMaint = [$dateFrom, $dateTo];
        if (!empty($vehicleId)) {
            $vehicleFilter = ' AND vehicle_id = ?';
            $paramsFuel[] = $vehicleId;
            $paramsMaint[] = $vehicleId;
        }

        // Fuel monthly sums
        if ($costType === 'all' || $costType === 'fuel') {
        $sql = "SELECT DATE_FORMAT(fuel_date,'%Y-%m') AS period, COALESCE(SUM(total_cost),0) AS amount
                    FROM fuel_consumption
                    WHERE fuel_date BETWEEN ? AND ?{$vehicleFilter}
                    GROUP BY period";
            foreach ($this->db->fetchAll($sql, $paramsFuel) as $row) {
                if (isset($periods[$row['period']])) {
                    $periods[$row['period']]['fuel_cost'] = (float)$row['amount'];
                }
            }
        }

        // Maintenance monthly sums
        if ($costType === 'all' || $costType === 'maintenance') {
        $sql = "SELECT DATE_FORMAT(service_date,'%Y-%m') AS period, COALESCE(SUM(cost),0) AS amount
                    FROM maintenance
            WHERE service_date BETWEEN ? AND ?{$vehicleFilter}
                    GROUP BY period";
            foreach ($this->db->fetchAll($sql, $paramsMaint) as $row) {
                if (isset($periods[$row['period']])) {
                    $periods[$row['period']]['maintenance_cost'] = (float)$row['amount'];
                }
            }
        }

        // Insurance monthly allocation (distribute premium by overlapping days per month)
        if ($costType === 'all' || $costType === 'insurance') {
            try {
                $paramsIns = [$dateTo, $dateFrom];
                $vehCond = '';
                if (!empty($vehicleId)) { $vehCond = ' AND vehicle_id = ?'; $paramsIns[] = $vehicleId; }
                $sql = "SELECT vehicle_id, start_date, end_date, annual_premium
                        FROM insurance
                        WHERE start_date <= ? AND end_date >= ?{$vehCond}";
                $rows = $this->db->fetchAll($sql, $paramsIns);
                foreach ($rows as $r) {
                    $s = new DateTime(max($r['start_date'], $dateFrom));
                    $e = new DateTime(min($r['end_date'], $dateTo));
                    if ($e < $s) continue;
                    $daily = ((float)$r['annual_premium']) / 365.0;
                    // Iterate months overlapped
                    $mc = new DateTime($s->format('Y-m-01'));
                    while ($mc <= new DateTime($e->format('Y-m-01'))) {
                        $monthStart = new DateTime(max($s->format('Y-m-d'), $mc->format('Y-m-01')));
                        $monthEnd = new DateTime(min($e->format('Y-m-t'), $mc->format('Y-m-t')));
                        $days = (int)$monthEnd->diff($monthStart)->format('%a') + 1;
                        $key = $mc->format('Y-m');
                        if (isset($periods[$key])) {
                            $periods[$key]['insurance_cost'] += $daily * $days;
                        }
                        $mc->modify('+1 month');
                    }
                }
            } catch (Throwable $ex) {
                // Table may not exist; ignore insurance breakdown
            }
        }

        // Final totals per period
        $summary = [
            'fuel_cost' => 0.0,
            'maintenance_cost' => 0.0,
            'insurance_cost' => 0.0,
            'other_cost' => 0.0,
            'total_cost' => 0.0,
        ];
        foreach ($periods as &$p) {
            $p['total_cost'] = $p['fuel_cost'] + $p['maintenance_cost'] + $p['insurance_cost'] + $p['other_cost'];
            $summary['fuel_cost'] += $p['fuel_cost'];
            $summary['maintenance_cost'] += $p['maintenance_cost'];
            $summary['insurance_cost'] += $p['insurance_cost'];
            $summary['other_cost'] += $p['other_cost'];
            $summary['total_cost'] += $p['total_cost'];
        }

        // CSV breakdown expects 'breakdown' array with period strings
        $breakdown = array_values($periods);

        // Also provide a 'monthly' alias used by some views
        $monthly = [];
        foreach ($periods as $key => $row) {
            [$y, $m] = explode('-', $key);
            $monthly[] = [
                'year' => (int)$y,
                'month' => (int)$m,
                'fuel_cost' => $row['fuel_cost'],
                'maintenance_cost' => $row['maintenance_cost'],
                'insurance_cost' => $row['insurance_cost'],
                'total_cost' => $row['total_cost'],
            ];
        }

        return [
            'summary' => $summary,
            'breakdown' => $breakdown,
            'monthly' => $monthly,
        ];
    }
    
    private function getWeeklyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType) {
        // Implementare pentru breakdown săptămânal
        return ['breakdown' => [], 'summary' => []];
    }
    
    private function getDailyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType) {
        // Implementare pentru breakdown zilnic
        return ['breakdown' => [], 'summary' => []];
    }
    
    private function getYearlyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType) {
        // Implementare pentru breakdown anual
        return ['breakdown' => [], 'summary' => []];
    }
    
    private function getCostTrends($dateFrom, $dateTo, $vehicleId, $costType) {
        // Simple trend: compare current period vs previous period of same length
        $days = (new DateTime($dateTo))->diff(new DateTime($dateFrom))->days + 1;
        $prevTo = (new DateTime($dateFrom))->modify('-1 day');
        $prevFrom = (clone $prevTo)->modify('-' . ($days - 1) . ' days');

        $current = $this->getMonthlyCostBreakdown($dateFrom, $dateTo, $vehicleId, $costType)['summary'];
        $previous = $this->getMonthlyCostBreakdown($prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d'), $vehicleId, $costType)['summary'];

        $pct = function($old, $new) { return $old == 0 ? ($new > 0 ? 100.0 : 0.0) : round(($new - $old) / $old * 100, 2); };
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => [
                'fuel' => $pct($previous['fuel_cost'], $current['fuel_cost']),
                'maintenance' => $pct($previous['maintenance_cost'], $current['maintenance_cost']),
                'insurance' => $pct($previous['insurance_cost'], $current['insurance_cost']),
                'total' => $pct($previous['total_cost'], $current['total_cost']),
            ],
        ];
    }
    
    private function getCostComparisons($dateFrom, $dateTo, $vehicleId) {
        // Implementare pentru comparații costuri
        return [];
    }
    
    private function getMaintenanceStats($dateFrom, $dateTo, $vehicleId, $maintenanceType, $status) {
        // Implementare pentru statistici mentenanță
        return [];
    }
    
    private function getMaintenanceCostBreakdown($dateFrom, $dateTo, $vehicleId) {
        // Implementare pentru breakdown costuri mentenanță
        return [];
    }
    
    private function getFuelStats($dateFrom, $dateTo, $vehicleId) {
        // Returneaza cel putin consumul mediu in perioada selectata
        if (!empty($vehicleId)) {
            $avg = $this->calculateAverageConsumption($vehicleId, $dateFrom, $dateTo);
            return ['avg_consumption' => $avg];
        }
        // Fara vehicul specificat: calculeaza media simpla pe toate vehiculele active
        $vehicles = $this->db->fetchAll("SELECT id FROM vehicles WHERE status = 'active'");
        $sum = 0.0; $count = 0;
        foreach ($vehicles as $v) {
            $a = $this->calculateAverageConsumption($v['id'], $dateFrom, $dateTo);
            if ($a > 0) { $sum += $a; $count++; }
        }
        return ['avg_consumption' => $count ? round($sum/$count, 2) : 0.0];
    }
    
    private function getFuelTrends($dateFrom, $dateTo, $vehicleId) {
        // Implementare pentru tendințe combustibil
        return [];
    }
    
    private function getFuelEfficiency($dateFrom, $dateTo, $vehicleId) {
        // Implementare pentru eficiența combustibilului
        return [];
    }
    
    private function getVehiclesForCustomReport($config, $params, $vehicleFilter) {
    $sql = "SELECT v.id, v.registration_number AS license_plate, v.brand AS make, v.model
        FROM vehicles v
        WHERE v.status = 'active' {$vehicleFilter}
        ORDER BY v.registration_number";
        
    return $this->db->fetchAll($sql, array_slice($params, 2)); // Skip date params
    }
    
    private function getVehicleFuelSummary($vehicleId, $dateFrom, $dateTo) {
        $sql = "SELECT 
                    COALESCE(SUM(liters), 0) as total_liters,
                    COALESCE(SUM(total_cost), 0) as total_cost
                FROM fuel_consumption
                WHERE vehicle_id = ? AND fuel_date BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$vehicleId, $dateFrom, $dateTo]);
    }
    
    private function getVehicleMaintenanceSummary($vehicleId, $dateFrom, $dateTo) {
        $sql = "SELECT 
                    COUNT(*) as count,
                    COALESCE(SUM(cost), 0) as total_cost
                FROM maintenance
                WHERE vehicle_id = ? AND service_date BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$vehicleId, $dateFrom, $dateTo]);
    }
    
    private function getVehicleInsuranceSummary($vehicleId, $dateFrom, $dateTo) {
        if (!$this->hasInsurance) { return ['total_cost' => 0]; }
        $sql = "SELECT 
                    COALESCE(SUM(premium_amount), 0) as total_cost
                FROM insurance
                WHERE vehicle_id = ? AND start_date BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$vehicleId, $dateFrom, $dateTo]);
    }
}
?>
