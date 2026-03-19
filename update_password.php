<?php
require_once __DIR__ . '/config/database.php';

$pdo = DatabaseConfig::getConnection();
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = 1');
$stmt->execute([$hash]);
echo "Password updated for superadmin. Hash: " . $hash . "\n";
