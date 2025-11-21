<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Direct PDO Test</title></head><body>";
echo "<h1>Direct PDO Connection Test</h1>";

// Direct PDO test fără clase
$host = 'localhost';
$dbname = 'wclsgzyf_fleetly';
$username = 'wclsgzyf_nnoldi';
$password = 'PetreIonel205!';

echo "<h2>Attempting Direct PDO Connection</h2>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Password: " . str_repeat('*', strlen($password)) . "<br><br>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    echo "DSN: $dsn<br><br>";
    
    echo "<strong>Creating PDO connection...</strong><br>";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "✅ <strong style='color:green'>CONNECTION SUCCESSFUL!</strong><br><br>";
    
    // Test query
    echo "<h3>Test Query</h3>";
    $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user, NOW() as time, VERSION() as version");
    $result = $stmt->fetch();
    
    echo "Current Database: <strong>" . $result['db'] . "</strong><br>";
    echo "Current User: <strong>" . $result['user'] . "</strong><br>";
    echo "Server Time: <strong>" . $result['time'] . "</strong><br>";
    echo "MySQL Version: <strong>" . $result['version'] . "</strong><br><br>";
    
    // List tables
    echo "<h3>Tables in Database</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables:<br>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>CONNECTION FAILED!</strong><br><br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Stack Trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
    
    echo "<hr>";
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li><strong>Error 1045</strong> (Access denied): Wrong username or password</li>";
    echo "<li><strong>Error 1044</strong> (Access denied to database): User doesn't have permission to this database</li>";
    echo "<li><strong>Error 2002</strong> (Can't connect): MySQL server not running or wrong host</li>";
    echo "<li><strong>Error 1049</strong> (Unknown database): Database doesn't exist</li>";
    echo "</ul>";
}

echo "</body></html>";
