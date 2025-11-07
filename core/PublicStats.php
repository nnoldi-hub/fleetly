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
            $activeCompanies = $stmtCompanies->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Total vehicule din toate tenant-urile
            $totalVehicles = 0;
            $stmtTenants = $this->coreDb->prepare(
                "SELECT id, tenant_db FROM companies WHERE status = 'active' AND tenant_db IS NOT NULL"
            );
            $stmtTenants->execute();
            $companies = $stmtTenants->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($companies as $company) {
                try {
                    $tenantDb = $company['tenant_db'];
                    $stmtVehicles = $this->coreDb->prepare(
                        "SELECT COUNT(*) as total FROM {$tenantDb}.vehicles"
                    );
                    $stmtVehicles->execute();
                    $count = $stmtVehicles->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                    $totalVehicles += $count;
                } catch (Exception $e) {
                    // Daca tenant DB nu exista sau nu are tabelul vehicles, continua
                    continue;
                }
            }
            
            // Total utilizatori
            $stmtUsers = $this->coreDb->prepare(
                "SELECT COUNT(*) as total FROM users WHERE company_id IN (SELECT id FROM companies WHERE status = 'active')"
            );
            $stmtUsers->execute();
            $totalUsers = $stmtUsers->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            return [
                'companies' => $activeCompanies > 0 ? $activeCompanies : 1, // Minim 1 pentru display
                'vehicles' => $totalVehicles > 0 ? $totalVehicles : 1,
                'uptime' => 99.9, // Poate fi calculat dintr-un log de monitoring
                'support' => '24/7' // Static
            ];
            
        } catch (Exception $e) {
            // Fallback la valori default daca apare o eroare
            error_log("Error fetching public stats: " . $e->getMessage());
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
