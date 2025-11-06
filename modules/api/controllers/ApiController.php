<?php
class ApiController extends Controller {
    public function notifications() {
        // Simple proxy to existing api/notifications.php returning JSON
        $file = __DIR__ . '/../../../api/../api/notifications.php';
        $alt = __DIR__ . '/../../../../api/notifications.php';
        $path = is_file($alt) ? $alt : (is_file($file) ? $file : null);
        if ($path) {
            // Ensure JSON header
            header('Content-Type: application/json');
            include $path;
            return;
        }
        $this->json(['success' => false, 'message' => 'Endpoint indisponibil'], 404);
    }
}
