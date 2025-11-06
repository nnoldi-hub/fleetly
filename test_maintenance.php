<?php
// Test file pentru a verifica dacă modelul Maintenance funcționează
try {
    require_once 'modules/maintenance/models/maintenance.php';
    echo "Modelul Maintenance a fost încărcat cu succes!\n";
    
    $maintenance = new Maintenance();
    echo "Instanța Maintenance a fost creată cu succes!\n";
    
} catch (Exception $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
    echo "Fișier: " . $e->getFile() . "\n";
    echo "Linia: " . $e->getLine() . "\n";
}
?>
