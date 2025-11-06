<?php
// Test file pentru a verifica dacă FuelController funcționează
try {
    define('ROOT_PATH', __DIR__);
    require_once 'core/controller.php';
    require_once 'modules/fuel/controllers/FuelController.php';
    echo "FuelController a fost încărcat cu succes!\n";
    
} catch (Exception $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
    echo "Fișier: " . $e->getFile() . "\n";
    echo "Linia: " . $e->getLine() . "\n";
}
?>
