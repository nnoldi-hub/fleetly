<?php
// modules/notifications/index.php (fallback launcher pentru hosting-uri fără rewrite)

// Pornim sesiunea (dacă nu e pornită deja)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dacă este cerere AJAX, setăm content-type JSON (pentru a expune erorile corect)
$isAjax = isset($_GET['ajax']) || isset($_POST['ajax']);
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../core/Controller.php';
    require_once __DIR__ . '/../../core/Model.php';
    require_once __DIR__ . '/../../core/Database.php';
    // Opțional dar sigur pentru metodele din controller
    @require_once __DIR__ . '/../../core/Auth.php';
    @require_once __DIR__ . '/../../core/User.php';

    require_once __DIR__ . '/models/Notification.php';
    require_once __DIR__ . '/controllers/NotificationController.php';

    $controller = new NotificationController();

    // Determinăm acțiunea
    $action = $_GET['action'] ?? 'index';

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
} catch (Throwable $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Eroare critică: ' . $e->getMessage(),
        ]);
    } else {
        http_response_code(500);
        echo '<h3>Eroare critică</h3><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
    exit;
}
?>
