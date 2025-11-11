<?php
// Script temporar pentru a genera hash de parolă
$parola = 'FleetAdmin2025!';  // Schimbă această parolă cu ce vrei
$hash = password_hash($parola, PASSWORD_BCRYPT);

echo "Parola: " . $parola . "\n";
echo "Hash: " . $hash . "\n";
echo "\nQuery SQL pentru a actualiza userul:\n";
echo "UPDATE users SET password_hash = '$hash', username = 'admin', email = 'admin@fleetly.ro' WHERE id = 1;\n";
?>
