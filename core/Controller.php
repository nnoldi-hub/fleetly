<?php
// Ensure Database class is loaded regardless of include order
if (!class_exists('Database', false)) {
    $dbPath = __DIR__ . '/Database.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    } elseif (file_exists(__DIR__ . '/Database.php')) {
        require_once __DIR__ . '/Database.php';
    } elseif (file_exists(__DIR__ . '/../core/Database.php')) {
        require_once __DIR__ . '/../core/Database.php';
    } elseif (file_exists(__DIR__ . '/../Database.php')) {
        require_once __DIR__ . '/../Database.php';
    } else {
        throw new Exception('Database class file not found in: ' . __DIR__);
    }
}

// Verify Database class is now available
if (!class_exists('Database')) {
    throw new Exception('Database class could not be loaded');
}

abstract class Controller {
    protected $db;
    protected $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Initialize Auth if not already loaded
        if (!class_exists('Auth')) {
            require_once __DIR__ . '/Auth.php';
        }
        $this->auth = Auth::getInstance();
    }
    
    protected function render($view, $data = []) {
        // Curăță orice output buffer anterior DOAR dacă nu e JSON/AJAX
        // Nu curățăm dacă avem debug logs importante
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                  isset($_GET['ajax']) || isset($_POST['ajax']);
        
        if (ob_get_level() && !$isAjax && !isset($_GET['debug_render'])) {
            // Only clean if buffer is not empty and not debugging
            $bufferContent = ob_get_contents();
            if (trim($bufferContent) === '') {
                ob_clean();
            }
        }
        
        extract($data);
        $viewFile = "modules/" . $this->getModuleName() . "/views/$view.php";
        
        if (file_exists($viewFile)) {
            error_log("[Controller::render] Including header...");
            include 'includes/header.php';
            error_log("[Controller::render] Including view: $viewFile");
            include $viewFile;
            error_log("[Controller::render] Including footer...");
            include 'includes/footer.php';
            error_log("[Controller::render] Render complete");
        } else {
            $this->error404("View not found: $viewFile");
        }
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        // If URL starts with /, build full route with ROUTE_BASE (supports no mod_rewrite)
        if (!empty($url) && $url[0] === '/') {
            $url = rtrim(ROUTE_BASE, '/') . '/' . ltrim($url, '/');
        }
        header("Location: $url");
        exit;
    }
    
    protected function error404($message = 'Page not found') {
        http_response_code(404);
        include 'includes/header.php';
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger'><h4>404 - $message</h4></div>";
        echo "</div>";
        include 'includes/footer.php';
        exit;
    }
    
    protected function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Câmpul $field este obligatoriu";
                continue;
            }
            
            if (!empty($value)) {
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = "Câmpul $field nu poate avea mai mult de {$rule['max_length']} caractere";
                }
                
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = "Câmpul $field trebuie să aibă minimum {$rule['min_length']} caractere";
                }
                
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field] = "Câmpul $field trebuie să fie o adresă de email validă";
                            }
                            break;
                        case 'numeric':
                            if (!is_numeric($value)) {
                                $errors[$field] = "Câmpul $field trebuie să fie numeric";
                            }
                            break;
                        case 'date':
                            if (!DateTime::createFromFormat('Y-m-d', $value)) {
                                $errors[$field] = "Câmpul $field trebuie să fie o dată validă (YYYY-MM-DD)";
                            }
                            break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    protected function getModuleName() {
        $className = get_class($this);
        $moduleName = strtolower(str_replace('Controller', '', $className));
        
        // Mapare specială pentru controllere cu nume diferite de folderelor lor
        $moduleMapping = [
            'vehicle' => 'vehicles',
            'driver' => 'drivers',
            'document' => 'documents',
            'maintenance' => 'maintenance',
            'fuel' => 'fuel',
            'report' => 'reports',
            'notification' => 'notifications',
            'insurance' => 'insurance',
            'dashboard' => 'dashboard',
            // Marketplace controllers
            'marketplace' => 'marketplace',
            'product' => 'marketplace',
            'cart' => 'marketplace',
            'checkout' => 'marketplace',
            'order' => 'marketplace',
            'catalogadmin' => 'marketplace',
            'orderadmin' => 'marketplace'
        ];
        
        return $moduleMapping[$moduleName] ?? $moduleName;
    }
    
    protected function uploadFile($file, $folder = 'documents') {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['error' => 'Nu a fost selectat niciun fișier'];
        }
        
        $uploadDir = UPLOAD_PATH . $folder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            return ['error' => 'Tip de fișier nepermis'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['error' => 'Fișierul este prea mare'];
        }
        
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => true, 'file_path' => $folder . '/' . $fileName];
        } else {
            return ['error' => 'Eroare la încărcarea fișierului'];
        }
    }
}
