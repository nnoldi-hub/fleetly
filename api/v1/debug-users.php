<?php
/**
 * Debug users and database structure
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';

try {
    $results = [];
    
    // Step 1: Connect to core database
    $db = Database::getInstance()->getConnection();
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $results[] = ['step' => 1, 'database' => $dbName];
    
    // Step 2: List all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $results[] = ['step' => 2, 'tables' => $tables];
    
    // Step 3: Check if users table exists
    if (in_array('users', $tables)) {
        $stmt = $db->query("SELECT id, email, company_id, role FROM users LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        $results[] = ['step' => 3, 'users' => $users];
    } else {
        $results[] = ['step' => 3, 'error' => 'users table not found'];
    }
    
    // Step 4: Check companies table
    if (in_array('companies', $tables)) {
        $stmt = $db->query("SELECT id, name, db_name FROM companies LIMIT 10");
        $companies = $stmt->fetchAll(PDO::FETCH_OBJ);
        $results[] = ['step' => 4, 'companies' => $companies];
    } else {
        $results[] = ['step' => 4, 'error' => 'companies table not found'];
    }
    
    // Step 5: Check tenancy mode
    $tenancyMode = 'unknown';
    if (method_exists('DatabaseConfig', 'getTenancyMode')) {
        $tenancyMode = DatabaseConfig::getTenancyMode();
    }
    $results[] = ['step' => 5, 'tenancy_mode' => $tenancyMode];
    
    // Step 6: Try to connect to tenant_1 directly
    try {
        $tenantDbName = str_replace('_fleetly', '_fm_tenant_1', $dbName);
        $host = DatabaseConfig::getHost();
        $user = DatabaseConfig::getUsername();
        $pass = DatabaseConfig::getPassword();
        
        $tenantPdo = new PDO(
            "mysql:host={$host};dbname={$tenantDbName};charset=utf8mb4",
            $user,
            $pass
        );
        
        // List tables in tenant
        $stmt = $tenantPdo->query("SHOW TABLES");
        $tenantTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $results[] = ['step' => 6, 'tenant_db' => $tenantDbName, 'tenant_tables' => $tenantTables];
        
        // Check vehicles in tenant
        if (in_array('vehicles', $tenantTables)) {
            $stmt = $tenantPdo->query("SELECT * FROM vehicles LIMIT 5");
            $vehicles = $stmt->fetchAll(PDO::FETCH_OBJ);
            $results[] = ['step' => 7, 'vehicles_in_tenant' => $vehicles];
        }
        
        // Check users in tenant
        if (in_array('users', $tenantTables)) {
            $stmt = $tenantPdo->query("SELECT id, email, company_id FROM users LIMIT 5");
            $tenantUsers = $stmt->fetchAll(PDO::FETCH_OBJ);
            $results[] = ['step' => 8, 'users_in_tenant' => $tenantUsers];
        }
        
    } catch (Exception $e) {
        $results[] = ['step' => 6, 'tenant_error' => $e->getMessage()];
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
