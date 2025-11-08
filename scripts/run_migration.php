<?php
// Simple migration runner for a single SQL file
// Usage examples:
//   php scripts/run_migration.php sql/migrations/2025_11_05_001_add_user_and_read_columns_to_notifications.sql
//   http://localhost/fleet-management/scripts/run_migration.php?file=sql/migrations/2025_11_05_001_add_user_and_read_columns_to_notifications.sql

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Database.php';

function respond($message, $statusCode = 200) {
    if (php_sapi_name() !== 'cli') {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $message;
    if (php_sapi_name() !== 'cli') { exit; }
}

$file = $argv[1] ?? ($_GET['file'] ?? '');
if (!$file) {
    respond("Missing 'file' argument.\n", 400);
}

$path = realpath(__DIR__ . '/../' . $file);
if (!$path || !file_exists($path)) {
    respond("Migration file not found: {$file}\n", 404);
}

$sql = file_get_contents($path);
if ($sql === false || trim($sql) === '') {
    respond("Empty migration file: {$file}\n", 400);
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    // Basic splitter by semicolon; naive but OK for our simple migration
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n|;\r?\n|;\s*$/m', $sql)));
    $executed = 0;
    foreach ($statements as $stmt) {
        if ($stmt === '' || stripos($stmt, 'DELIMITER') === 0) continue;
        try {
            $pdo->exec($stmt);
            $executed++;
        } catch (PDOException $ex) {
            $msg = $ex->getMessage();
            // Ignore idempotent cases (duplicate column/index/foreign key exists)
            if (stripos($msg, 'Duplicate column name') !== false ||
                stripos($msg, 'Duplicate key name') !== false ||
                stripos($msg, 'already exists') !== false ||
                stripos($msg, 'errno: 150') !== false ||
                stripos($msg, 'Cannot add foreign key constraint') !== false) {
                continue;
            }
            throw $ex;
        }
    }
    respond("Applied migration: {$file}\nStatements executed: {$executed}\n", 200);
} catch (Throwable $e) {
    respond("Migration failed: " . $e->getMessage() . "\n", 500);
}
