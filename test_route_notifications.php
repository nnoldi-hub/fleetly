<?php
// test_route_notifications.php - Test direct rută notificări
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Rută Notificări</h1>";
echo "<pre>";

// Simulăm request către /notifications
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/notifications';
$_SERVER['SCRIPT_NAME'] = '/index.php';

echo "Simulare: GET /notifications\n\n";

try {
    // Redirecționăm către index.php
    include 'index.php';
} catch (Throwable $e) {
    echo "\n\n=== EROARE CAPTURATĂ ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
