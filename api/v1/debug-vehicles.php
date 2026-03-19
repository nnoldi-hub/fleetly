<?php
/**
 * Debug script pentru vehicles API
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load dependencies
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';

try {
    // Test database connection
    $db = Database::getInstance()->getConnection();
    echo json_encode(['step' => 1, 'message' => 'Database connected'], JSON_PRETTY_PRINT);
    echo "\n\n";

    // Check if vehicles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'vehicles'");
    $tableExists = $stmt->rowCount() > 0;
    echo json_encode(['step' => 2, 'vehicles_table_exists' => $tableExists], JSON_PRETTY_PRINT);
    echo "\n\n";

    // Check if vehicle_types table exists
    $stmt = $db->query("SHOW TABLES LIKE 'vehicle_types'");
    $typesTableExists = $stmt->rowCount() > 0;
    echo json_encode(['step' => 3, 'vehicle_types_table_exists' => $typesTableExists], JSON_PRETTY_PRINT);
    echo "\n\n";

    // Count vehicles
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM vehicles");
    $vehicleCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo json_encode(['step' => 4, 'vehicle_count' => $vehicleCount], JSON_PRETTY_PRINT);
    echo "\n\n";

    // Check vehicles table structure
    $stmt = $db->query("DESCRIBE vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['step' => 5, 'vehicles_columns' => $columns], JSON_PRETTY_PRINT);
    echo "\n\n";

    // Try the actual query from VehicleController
    $sql = "
        SELECT v.id, v.registration_number, v.brand, v.model, v.year,
               v.vin_number, v.current_mileage, v.status, v.fuel_type, v.color,
               v.vehicle_type_id, vt.name as vehicle_type,
               v.created_at, v.updated_at
        FROM vehicles v
        LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        WHERE v.deleted_at IS NULL
        ORDER BY v.registration_number ASC
        LIMIT 20 OFFSET 0
    ";
    
    $stmt = $db->query($sql);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['step' => 6, 'query_result' => $vehicles], JSON_PRETTY_PRINT);
    echo "\n\n";

} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
