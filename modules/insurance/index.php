<?php
// modules/insurance/index.php

// Verificăm autentificarea
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/controllers/InsuranceController.php';

$controller = new InsuranceController();

// Determinăm acțiunea
$action = $_GET['action'] ?? 'index';

// Routing
switch ($action) {
    case 'index':
    case 'list':
        $controller->index();
        break;
        
    case 'add':
        $controller->add();
        break;
        
    case 'edit':
        $controller->edit();
        break;
        
    case 'view':
        $controller->view();
        break;
        
    case 'delete':
        $controller->delete();
        break;
        
    case 'bulkDelete':
        $controller->bulkDelete();
        break;
        
    case 'expiring':
        $controller->getExpiringInsurance();
        break;
        
    case 'export':
        $controller->export();
        break;
        
    default:
        $controller->index();
        break;
}
?>
