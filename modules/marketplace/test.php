<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Test deployment working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";

try {
    require_once __DIR__ . '/../../config/config.php';
    echo "Config loaded OK<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
    
    require_once __DIR__ . '/../../core/Database.php';
    echo "Database class loaded OK<br>";
    
    $db = Database::getInstance();
    echo "Database instance OK<br>";
    
    require_once __DIR__ . '/../../core/Auth.php';
    echo "Auth class loaded OK<br>";
    
    $auth = Auth::getInstance();
    echo "Auth instance OK<br>";
    
    if ($auth->check()) {
        echo "User authenticated!<br>";
        $user = $auth->user();
        echo "User ID: " . $user->id . "<br>";
        echo "User role: " . $user->role . "<br>";
    } else {
        echo "User NOT authenticated<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
