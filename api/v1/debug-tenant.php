<?php
/**
 * Debug tenant database structure
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';

try {
    $results = [];
    
    // Step 1: Connect to core database
    $db = Database::getInstance()->getConnection();
    $results[] = ['step' => 1, 'message' => 'Core database connected'];
    
    // Step 2: Get company_id for user
    $stmt = $db->prepare("SELECT id, company_id, email FROM users WHERE email = ?");
    $stmt->execute(['noldi@smartcables.ro']);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    $results[] = ['step' => 2, 'user' => $user];
    
    // Step 3: Get company details
    if ($user && $user->company_id) {
        $stmt = $db->prepare("SELECT id, name, db_name FROM companies WHERE id = ?");
        $stmt->execute([$user->company_id]);
        $company = $stmt->fetch(PDO::FETCH_OBJ);
        $results[] = ['step' => 3, 'company' => $company];
        
        // Step 4: Connect to tenant database
        if ($company && $company->db_name) {
            Database::getInstance()->setTenantDatabaseByCompanyId($user->company_id);
            $tenantDb = Database::getInstance()->getTenantPdo();
            
            if ($tenantDb) {
                $results[] = ['step' => 4, 'message' => 'Tenant database connected: ' . $company->db_name];
                
                // Step 5: List all tables in tenant
                $stmt = $tenantDb->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $results[] = ['step' => 5, 'tenant_tables' => $tables];
                
                // Step 6: Check if vehicles table exists and has data
                if (in_array('vehicles', $tables)) {
                    $stmt = $tenantDb->query("SELECT COUNT(*) as count FROM vehicles");
                    $count = $stmt->fetch(PDO::FETCH_OBJ)->count;
                    $results[] = ['step' => 6, 'vehicles_count' => $count];
                    
                    // Step 7: Get vehicles data
                    $stmt = $tenantDb->query("SELECT * FROM vehicles LIMIT 5");
                    $vehicles = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $results[] = ['step' => 7, 'vehicles_sample' => $vehicles];
                } else {
                    $results[] = ['step' => 6, 'error' => 'vehicles table not found in tenant'];
                }
                
                // Step 8: Check vehicle_types
                if (in_array('vehicle_types', $tables)) {
                    $stmt = $tenantDb->query("SELECT * FROM vehicle_types");
                    $types = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $results[] = ['step' => 8, 'vehicle_types' => $types];
                } else {
                    $results[] = ['step' => 8, 'vehicle_types_exists' => false, 'message' => 'vehicle_types NOT in tenant - this is the problem!'];
                    
                    // Check in core database
                    $stmt = $db->query("SELECT * FROM vehicle_types");
                    $types = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $results[] = ['step' => '8b', 'vehicle_types_in_core' => $types];
                }
                
            } else {
                $results[] = ['step' => 4, 'error' => 'Could not connect to tenant database'];
            }
        } else {
            $results[] = ['step' => 4, 'error' => 'No db_name for company'];
        }
    } else {
        $results[] = ['step' => 3, 'error' => 'User or company_id not found'];
    }
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
