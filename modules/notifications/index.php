<?php
// modules/notifications/index.php (fallback launcher pentru hosting-uri fără rewrite)

// Pornim sesiunea (dacă nu e pornită deja)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dacă este cerere AJAX, setăm content-type JSON și eliminăm output buffering existent
$isAjax = isset($_GET['ajax']) || isset($_POST['ajax']) || 
          (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if ($isAjax) {
    // Clean any existing output buffers to prevent transliteration filter corruption
    while (ob_get_level() > 0) { ob_end_clean(); }
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
        case 'mark-all-read': // alias pentru JavaScript
            $controller->markAllAsRead();
            break;
        case 'dismiss':
            $controller->dismiss();
            break;
        case 'getUnreadCount':
        case 'unread-count': // alias pentru JavaScript
            $controller->getUnreadCount();
            break;
        case 'generateSystemNotifications':
        case 'generate-system': // alias pentru JavaScript
            $controller->generateSystemNotifications();
            break;
        case 'check-system':
        case 'checkSystem':
            // Render direct view fără Controller::render (are propriul layout)
            include __DIR__ . '/views/check_system.php';
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
