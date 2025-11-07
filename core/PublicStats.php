<?php

class PublicStats
{
    private static $instance = null;
    private $coreDb;
    
    private function __construct()
    {
        $this->coreDb = Database::getInstance()->getConnection();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtine statistici globale pentru landing page
     * @return array
     */
    public function getGlobalStats(): array
    {
        try {
            // Total companii active
            $stmtCompanies = $this->coreDb->prepare(
                "SELECT COUNT(*) as total FROM companies WHERE status = 'active'"
            );
            $stmtCompanies->execute();
            $result = $stmtCompanies->fetch(PDO::FETCH_ASSOC);
            $activeCompanies = (int)($result['total'] ?? 0);
            
            // Debug logging
            error_log("PublicStats - Active companies query result: " . print_r($result, true));
            error_log("PublicStats - Active companies count: " . $activeCompanies);
            
            // Total vehicule din toate tenant-urile
            $totalVehicles = 0;
            $stmtTenants = $this->coreDb->prepare(
                "SELECT id, database_name FROM companies WHERE status = 'active' AND database_name IS NOT NULL AND database_name != ''"
            );
            $stmtTenants->execute();
            $companies = $stmtTenants->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("PublicStats - Found " . count($companies) . " companies with tenant DB");
            
            foreach ($companies as $company) {
                try {
                    $tenantDb = trim($company['database_name']);
                    if (empty($tenantDb)) {
                        continue;
                    }
                    
                    // Verifică dacă database există
                    $checkDb = $this->coreDb->prepare("SHOW DATABASES LIKE ?");
                    $checkDb->execute([$tenantDb]);
                    if (!$checkDb->fetch()) {
                        error_log("PublicStats - Tenant DB not found: " . $tenantDb);
                        continue;
                    }
                    
                    // Verifică dacă tabela vehicles există
                    $checkTable = $this->coreDb->prepare("SHOW TABLES FROM `{$tenantDb}` LIKE 'vehicles'");
                    $checkTable->execute();
                    if (!$checkTable->fetch()) {
                        error_log("PublicStats - Vehicles table not found in: " . $tenantDb);
                        continue;
                    }
                    
                    $stmtVehicles = $this->coreDb->prepare(
                        "SELECT COUNT(*) as total FROM `{$tenantDb}`.vehicles"
                    );
                    $stmtVehicles->execute();
                    $vehicleResult = $stmtVehicles->fetch(PDO::FETCH_ASSOC);
                    $count = (int)($vehicleResult['total'] ?? 0);
                    $totalVehicles += $count;
                    
                    error_log("PublicStats - Tenant {$tenantDb} has {$count} vehicles");
                } catch (Exception $e) {
                    // Daca tenant DB nu exista sau nu are tabelul vehicles, continua
                    error_log("PublicStats - Error processing tenant {$tenantDb}: " . $e->getMessage());
                    continue;
                }
            }
            
            error_log("PublicStats - Total vehicles: " . $totalVehicles);
            
            return [
                'companies' => max(1, $activeCompanies), // Minim 1 pentru display
                'vehicles' => max(1, $totalVehicles),
                'uptime' => 99.9,
                'support' => '24/7'
            ];
            
        } catch (Exception $e) {
            // Fallback la valori default daca apare o eroare
            error_log("PublicStats - Error fetching stats: " . $e->getMessage());
            error_log("PublicStats - Stack trace: " . $e->getTraceAsString());
            return [
                'companies' => 1,
                'vehicles' => 1,
                'uptime' => 99.9,
                'support' => '24/7'
            ];
        }
    }
    
    /**
     * Formateaza numere mari pentru display (ex: 15000 -> 15,000+)
     * @param int $number
     * @return string
     */
    public static function formatNumber(int $number): string
    {
        if ($number >= 1000) {
            return number_format($number, 0, '.', ',') . '+';
        }
        return (string)$number;
    }
}
