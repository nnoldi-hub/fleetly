<?php
// modules/notifications/index.php

// Verificăm autentificarea
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/controllers/NotificationController.php';

$controller = new NotificationController();

// Determinăm acțiunea
$action = $_GET['action'] ?? 'index';

// Routing
switch ($action) {
    case 'index':
    case 'alerts':
        $controller->alerts();
        break;
        
    case 'create':
        $controller->create();
        break;
        
    case 'markAsRead':
        $controller->markAsRead();
        break;
        
    case 'markAllAsRead':
        $controller->markAllAsRead();
        break;
        
    case 'dismiss':
        $controller->dismiss();
        break;
        
    case 'getUnreadCount':
        $controller->getUnreadCount();
        break;
        
    case 'generateSystemNotifications':
        $controller->generateSystemNotifications();
        break;
        
    default:
        $controller->alerts();
        break;
}
?>
