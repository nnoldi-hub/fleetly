<?php
// modules/reports/index.php

// Verificăm autentificarea
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/controllers/ReportController.php';

$controller = new ReportController();

// Determinăm acțiunea
$action = $_GET['action'] ?? 'index';

// Routing
switch ($action) {
    case 'index':
        $controller->index();
        break;
        
    case 'fleet':
    case 'fleet_report':
        $controller->fleetReport();
        break;
        
    case 'vehicle':
    case 'vehicle_report':
        $controller->vehicleReport();
        break;
        
    case 'cost':
    case 'cost_analysis':
        $controller->costAnalysis();
        break;
        
    case 'maintenance':
    case 'maintenance_report':
        $controller->maintenanceReport();
        break;
        
    case 'fuel':
    case 'fuel_report':
        $controller->fuelReport();
        break;
        
    case 'custom':
    case 'custom_report':
        $controller->customReport();
        break;
        
    case 'generate_periodical':
        $controller->generatePeriodicalReports();
        break;
        
    default:
        $controller->index();
        break;
}
?>
