<?php
// modules/notifications/controllers/NotificationController.php

require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Notification.php';
// Asigurăm încărcarea modelelor folosite de generator, chiar și când controllerul este invocat din modules/notifications/index.php
@require_once __DIR__ . '/../../insurance/models/Insurance.php';
@require_once __DIR__ . '/../../maintenance/models/Maintenance.php';

class NotificationController extends Controller {
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->notificationModel = new Notification();
    }
    
    public function index() {
        // Verificăm dacă e cerere de generare notificări
        if (isset($_GET['action']) && $_GET['action'] === 'generate') {
            $this->generateSystemNotifications();
            return;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'generate') {
            $this->generateSystemNotifications();
            return;
        }
        
        $this->alerts();
    }
    
    public function alerts() {
        // Timeout protection
        set_time_limit(30);
        $startTime = microtime(true);
        error_log("[NotificationController::alerts] START");
        
        $page = $_GET['page'] ?? 1;
        $type = $_GET['type'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $status = $_GET['status'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 25);
        
        $conditions = [];
        // Determine user (fallback to 1 when auth/session not wired yet)
        $userId = $_SESSION['user_id'] ?? 1;
        $conditions['user_id'] = $userId;
        error_log("[NotificationController::alerts] User ID: $userId");
        
        // Aplicăm filtrele
        if (!empty($type)) {
            $conditions['type'] = $type;
        }
        if (!empty($priority)) {
            $conditions['priority'] = $priority;
        }
        if ($status === 'read') {
            $conditions['is_read'] = 1;
        } elseif ($status === 'unread') {
            $conditions['is_read'] = 0;
        }
        
        $offset = ($page - 1) * $per_page;
        try {
            error_log("[NotificationController::alerts] Calling getAllWithDetails...");
            $notifications = $this->notificationModel->getAllWithDetails($conditions, $offset, $per_page);
            error_log("[NotificationController::alerts] Got " . count($notifications) . " notifications");
            
            error_log("[NotificationController::alerts] Calling getTotalCount...");
            $totalRecords = $this->notificationModel->getTotalCount($conditions);
            $totalPages = ceil($totalRecords / $per_page);
            error_log("[NotificationController::alerts] Total: $totalRecords");
            
            // Obținem statistici
            error_log("[NotificationController::alerts] Calling getStatistics...");
            $stats = $this->notificationModel->getStatistics($userId);
            error_log("[NotificationController::alerts] Calling getUnreadCount...");
            $unreadCount = $this->notificationModel->getUnreadCount($userId);
            error_log("[NotificationController::alerts] Unread: $unreadCount");
        } catch (Throwable $e) {
            error_log("[NotificationController::alerts] ERROR: " . $e->getMessage());
            // Fallback prietenos în loc de 404 când lipsesc coloane sau există probleme de schemă
            $msg = $e->getMessage();
            $needsMigration = (stripos($msg, 'Unknown column') !== false || stripos($msg, 'doesn\'t exist') !== false);
            include 'includes/header.php';
            echo "<div class='container py-4'>";
            echo "<div class='alert alert-danger'><strong>Eroare la încărcarea notificărilor</strong><br>" . htmlspecialchars($msg) . "</div>";
            if ($needsMigration) {
                $mig = BASE_URL . "scripts/run_migration.php?file=sql/migrations/2025_11_05_001_add_user_and_read_columns_to_notifications.sql";
                echo "<div class='alert alert-warning'>Pare că lipsesc unele coloane în tabela <code>notifications</code>.<br>Apasă <a href='" . $mig . "' class='alert-link'>aici</a> pentru a aplica migrarea necesară și apoi reîncarcă pagina.</div>";
            }
            echo "<a href='" . BASE_URL . "' class='btn btn-outline-secondary mt-2'>Înapoi</a>";
            echo "</div>";
            include 'includes/footer.php';
            return;
        }

        // Aplicăm preferințele utilizatorului dacă nu sunt setate filtre explicite
        $appliedPrefsFilter = false;
        try {
            $prefsKey = 'notifications_prefs_user_' . $userId;
            $prefsRow = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$prefsKey]);
            if ($prefsRow && empty($type) && empty($priority)) {
                $prefs = json_decode($prefsRow['setting_value'] ?? '', true) ?: [];
                $enabledCategories = $prefs['enabledCategories'] ?? [];
                $minPriority = $prefs['minPriority'] ?? 'low';

                if (!empty($enabledCategories)) {
                    $notifications = array_values(array_filter($notifications, function($n) use ($enabledCategories) {
                        return in_array($n['type'] ?? '', $enabledCategories);
                    }));
                }

                $priorityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
                $minOrder = $priorityOrder[$minPriority] ?? 1;
                $notifications = array_values(array_filter($notifications, function($n) use ($priorityOrder, $minOrder) {
                    $p = $n['priority'] ?? 'medium';
                    $order = $priorityOrder[$p] ?? 2;
                    return $order >= $minOrder;
                }));
                $appliedPrefsFilter = true;
            }
        } catch (Throwable $e) {
            // Dacă system_settings lipsește sau altă problemă DB, ignorăm preferințele fără a opri pagina
            $appliedPrefsFilter = false;
        }
        
        $data = [
            'notifications' => $notifications,
            'stats' => $stats,
            'unreadCount' => $unreadCount,
            'currentPage' => $page,
            'totalPages' => $appliedPrefsFilter ? 1 : $totalPages,
            'totalRecords' => $appliedPrefsFilter ? count($notifications) : $totalRecords,
            'perPage' => $per_page,
            'filters' => [
                'type' => $type,
                'priority' => $priority,
                'status' => $status
            ],
            'pageTitle' => 'Alerte și Notificări'
        ];
        
        $elapsed = microtime(true) - $startTime;
        error_log("[NotificationController::alerts] Data prepared in " . round($elapsed, 3) . "s");
        error_log("[NotificationController::alerts] Calling render...");
        
        // DEBUG: Test if render is the problem
        if (isset($_GET['debug_render'])) {
            error_log("[NotificationController::alerts] DEBUG MODE - bypassing full render");
            echo "<h1>DEBUG: Render Bypass</h1>";
            echo "<pre>";
            echo "Notifications count: " . count($notifications) . "\n";
            echo "Unread count: $unreadCount\n";
            echo "Stats: " . print_r($stats, true) . "\n";
            echo "Elapsed: " . round($elapsed, 3) . "s\n";
            echo "</pre>";
            echo "<p>If you see this, the problem is in header.php or footer.php includes.</p>";
            exit;
        }
        
        // Folosim sistemul de layout standard
        $this->render('alerts', $data);
        
        error_log("[NotificationController::alerts] Render completed");
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            // Dacă va exista o vedere dedicată, o vom reda prin layout.
            // Momentan nu există un fișier create.php în views, așa că redirecționăm la listă.
            $this->redirect('/notifications');
        }
    }
    
    private function handleCreate() {
        $data = [
            'user_id' => $_POST['user_id'] ?? null,
            'type' => $_POST['type'] ?? '',
            'title' => $_POST['title'] ?? '',
            'message' => $_POST['message'] ?? '',
            'priority' => $_POST['priority'] ?? 'medium',
            'related_id' => $_POST['related_id'] ?? null,
            'related_type' => $_POST['related_type'] ?? null,
            'action_url' => $_POST['action_url'] ?? null
        ];
        
        $errors = $this->validateNotificationData($data);
        
        if (empty($errors)) {
            $result = $this->notificationModel->create($data);
            if ($result) {
                if (isset($_POST['ajax'])) {
                    $this->json(['success' => true, 'message' => 'Notificarea a fost creată cu succes', 'id' => $result]);
                } else {
                    $_SESSION['success'] = 'Notificarea a fost creată cu succes!';
                    $this->redirect('/notifications');
                }
            } else {
                if (isset($_POST['ajax'])) {
                    $this->json(['success' => false, 'message' => 'Eroare la crearea notificării'], 500);
                } else {
                    $_SESSION['errors'] = ['Eroare la crearea notificării'];
                    $_SESSION['old_input'] = $_POST;
                    $this->redirect('/notifications');
                }
            }
        } else {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => 'Datele introduse nu sunt valide', 'errors' => $errors], 400);
            } else {
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirect('/notifications');
            }
        }
    }
    
    public function markAsRead() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID invalid'], 400);
            return;
        }
        
        $notification = $this->notificationModel->getById($id);
        $userId = $_SESSION['user_id'] ?? 1;
        if (!$notification || $notification['user_id'] != $userId) {
            $this->json(['success' => false, 'message' => 'Notificarea nu a fost găsită'], 404);
            return;
        }
        
        // Detect AJAX request
        $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
                  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        if ($this->notificationModel->markAsRead($id)) {
            if ($isAjax) {
                $this->json(['success' => true, 'message' => 'Notificarea a fost marcată ca citită']);
            } else {
                $_SESSION['success'] = 'Notificarea a fost marcată ca citită!';
                $this->redirect('/notifications');
            }
        } else {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Eroare la marcarea notificării'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la marcarea notificării'];
                $this->redirect('/notifications');
            }
        }
    }
    
    public function markAllAsRead() {
        $userId = $_SESSION['user_id'] ?? 1;
        
        // Detect AJAX request
        $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
                  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        if ($this->notificationModel->markAllAsRead($userId)) {
            if ($isAjax) {
                $this->json(['success' => true, 'message' => 'Toate notificările au fost marcate ca citite']);
            } else {
                $_SESSION['success'] = 'Toate notificările au fost marcate ca citite!';
                $this->redirect('/notifications');
            }
        } else {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Eroare la marcarea notificărilor'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la marcarea notificărilor'];
                $this->redirect('/notifications');
            }
        }
    }
    
    public function dismiss() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID invalid'], 400);
            return;
        }
        
        $notification = $this->notificationModel->getById($id);
        $userId = $_SESSION['user_id'] ?? 1;
        if (!$notification || $notification['user_id'] != $userId) {
            $this->json(['success' => false, 'message' => 'Notificarea nu a fost găsită'], 404);
            return;
        }
        
        // Detect AJAX request
        $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
                  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        if ($this->notificationModel->delete($id)) {
            if ($isAjax) {
                $this->json(['success' => true, 'message' => 'Notificarea a fost ștearsă']);
            } else {
                $_SESSION['success'] = 'Notificarea a fost ștearsă!';
                $this->redirect('/notifications');
            }
        } else {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Eroare la ștergerea notificării'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la ștergerea notificării'];
                $this->redirect('/notifications');
            }
        }
    }
    
    public function getUnreadCount() {
        $userId = $_SESSION['user_id'] ?? 1;
        $count = $this->notificationModel->getUnreadCount($userId);
        
        $this->json(['success' => true, 'count' => $count]);
    }
    
    public function generateSystemNotifications() {
        try {
            // Generăm notificări automate pentru sistem
            $notifications = [];

            // Obținem company_id al utilizatorului curent pentru broadcast
            $userId = $_SESSION['user_id'] ?? 1;
            // Schema diferențiată: în varianta RBAC avansată nu există coloana `role`, ci `role_id` cu legătură la tabela `roles`.
            // Luăm doar company_id (rolul nu este necesar aici pentru generare) pentru compatibilitate cu ambele scheme.
            $user = $this->db->fetch("SELECT company_id, role_id FROM users WHERE id = ?", [$userId]);
            $companyId = $user['company_id'] ?? null;

            // IMPORTANT: setăm contextul de tenant înainte de a atinge tabele de flotă (insurance/maintenance/vehicles)
            if ($companyId) {
                try { $this->db->setTenantDatabaseByCompanyId((int)$companyId); } catch (Throwable $e) { /* fallback pe core */ }
            } else {
                // Dacă nu avem company_id (ex. superadmin), folosim DB-ul curent ca "tenant" ca să instalăm schema flotă dacă lipsește
                try { $this->db->setTenantDatabase(DatabaseConfig::getDbName()); } catch (Throwable $e) {}
                // Fallback: dacă rulăm pe core și nu există încă tabele flotă, le creăm minim necesar
                try { $this->db->ensureFleetTablesOnCoreIfMissing(); } catch (Throwable $e) {}
            }

            // Zile înainte de expirare din preferințe (fallback 30)
            $daysBefore = 30; $daysBeforeInt = 30;
            try {
                $prefsKey = 'notifications_prefs_user_' . $userId;
                $prefsRow = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$prefsKey]);
                if ($prefsRow && !empty($prefsRow['setting_value'])) {
                    $prefs = json_decode($prefsRow['setting_value'], true);
                    if (isset($prefs['daysBefore'])) {
                        $daysBefore = max(0, (int)$prefs['daysBefore']);
                        $daysBeforeInt = $daysBefore;
                    }
                }
            } catch (Throwable $pe) { /* folosim fallback-ul 30 */ }

            // Verificăm asigurările care expiră (folosim metoda pe expiry_date care expune days_until_expiry)
            $expiringInsurance = [];
            try {
                $insuranceModel = new Insurance();
                if (method_exists($insuranceModel, 'getExpiringInsurance')) {
                    $expiringInsurance = $insuranceModel->getExpiringInsurance($daysBeforeInt);
                } else {
                    // fallback pe metoda existentă
                    $expiringInsurance = $insuranceModel->getExpiring($daysBeforeInt);
                }
            } catch (Throwable $ie) {
                // Fallback compatibilitate direct pe DB (diferențe de schemă/tabele)
                // dacă tabela insurance nu există, o creăm rapid (poate fi prima rulare tenant)
                try { $this->db->queryOn('insurance', "SELECT 1 FROM insurance LIMIT 1"); } catch (Throwable $tMissing) {
                    try {
                        $this->db->queryOn('insurance', "CREATE TABLE IF NOT EXISTS insurance (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            vehicle_id INT NOT NULL,
                            insurance_type VARCHAR(50) NOT NULL,
                            policy_number VARCHAR(100),
                            insurance_company VARCHAR(100),
                            start_date DATE,
                            expiry_date DATE,
                            coverage_amount DECIMAL(12,2),
                            premium_amount DECIMAL(12,2),
                            deductible DECIMAL(12,2),
                            payment_frequency VARCHAR(50),
                            agent_name VARCHAR(100),
                            agent_phone VARCHAR(30),
                            agent_email VARCHAR(100),
                            coverage_details TEXT,
                            policy_file VARCHAR(255),
                            status ENUM('active','inactive','cancelled','expired') DEFAULT 'active',
                            notes TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_expiry_date (expiry_date),
                            INDEX idx_status (status)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } catch (Throwable $createFail) {
                        throw $createFail; // dacă nu putem crea, raportăm
                    }
                }
                $expiringInsurance = $this->db->fetchAllOn('insurance',
                    "SELECT i.*, 
                            v.registration_number AS license_plate,
                            CONCAT(v.brand, ' ', v.model, ' (', v.registration_number, ')') AS vehicle_info,
                            DATEDIFF(i.expiry_date, CURDATE()) AS days_until_expiry,
                            i.insurance_type
                     FROM insurance i
                     LEFT JOIN vehicles v ON i.vehicle_id = v.id
                     WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$daysBeforeInt} DAY)
                     ORDER BY i.expiry_date ASC",
                    []
                );
            }

            foreach ($expiringInsurance as $insurance) {
                // days_until_expiry poate veni direct din query; dacă nu, îl calculăm
                $daysUntilExpiry = isset($insurance['days_until_expiry'])
                    ? (int)$insurance['days_until_expiry']
                    : (isset($insurance['expiry_date'])
                        ? (int)floor((strtotime($insurance['expiry_date']) - time()) / (24 * 3600))
                        : (isset($insurance['end_date'])
                            ? (int)floor((strtotime($insurance['end_date']) - time()) / (24 * 3600))
                            : 30));

                Notification::createInsuranceExpiryNotification(
                    $insurance['id'],
                    $insurance['license_plate'] ?? ($insurance['vehicle_info'] ?? 'Vehicul'),
                    $insurance['insurance_type'] ?? 'asigurare',
                    $daysUntilExpiry,
                    $companyId
                );
            }

            // Verificăm mentenanța scadentă
            $dueMaintenance = [];
            try {
                $maintenanceModel = new Maintenance();
                $dueMaintenance = $maintenanceModel->getDueMaintenance();
            } catch (Throwable $me) {
                // Fallback compatibilitate: folosim coloane din schema tenant (registration_number, brand, model, current_mileage, next_service_mileage)
                try {
                                        $dueMaintenance = $this->db->fetchAllOn('maintenance',
                        "SELECT m.*, 
                                v.registration_number AS license_plate,
                                CONCAT(v.brand, ' ', v.model, ' (', v.registration_number, ')') AS vehicle_info,
                                'mentenanță' AS maintenance_type
                         FROM maintenance m
                         LEFT JOIN vehicles v ON m.vehicle_id = v.id
                         WHERE m.status IN ('scheduled','in_progress')
                           AND (
                                (m.next_service_date IS NOT NULL AND m.next_service_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY))
                                OR
                                (m.next_service_mileage IS NOT NULL AND v.current_mileage IS NOT NULL AND v.current_mileage >= (m.next_service_mileage - 2000))
                           )
                         ORDER BY COALESCE(m.next_service_date, '9999-12-31') ASC, m.next_service_mileage ASC",
                        []
                    );
                } catch (Throwable $me2) {
                    // Dacă și fallback-ul eșuează, raportăm eroarea originală
                    throw $me;
                }
            }

            foreach ($dueMaintenance as $maintenance) {
                Notification::createMaintenanceNotification(
                    $maintenance['vehicle_id'],
                    $maintenance['license_plate'] ?? ($maintenance['vehicle_info'] ?? 'Vehicul'),
                    $maintenance['maintenance_type'] ?? 'mentenanță',
                    $companyId
                );
            }

            // Verificăm documentele care expiră în fereastra definită
            $expiringDocuments = [];
            try {
                $expiringDocuments = $this->db->fetchAllOn('documents',
                    "SELECT d.*, 
                            v.registration_number AS license_plate,
                            CONCAT(v.brand, ' ', v.model, ' (', v.registration_number, ')') AS vehicle_info,
                            DATEDIFF(d.expiry_date, CURDATE()) AS days_until_expiry,
                            d.document_type
                     FROM documents d
                     LEFT JOIN vehicles v ON d.vehicle_id = v.id
                     WHERE d.status = 'active'
                       AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$daysBeforeInt} DAY)
                     ORDER BY d.expiry_date ASC",
                    []
                );
            } catch (Throwable $de) { /* dacă lipsește tabela, continuăm fără docs */ }

            foreach ($expiringDocuments as $doc) {
                $daysUntilExpiry = isset($doc['days_until_expiry'])
                    ? (int)$doc['days_until_expiry']
                    : (isset($doc['expiry_date']) ? (int)floor((strtotime($doc['expiry_date']) - time()) / (24 * 3600)) : $daysBeforeInt);

                Notification::createDocumentExpiryNotification(
                    $doc['id'],
                    $doc['license_plate'] ?? ($doc['vehicle_info'] ?? 'Vehicul'),
                    $doc['document_type'] ?? 'document',
                    $daysUntilExpiry,
                    $companyId
                );
            }

            // Contorizăm notificările create (broadcast creează mai multe înregistrări)
            $created = (is_array($expiringInsurance) ? count($expiringInsurance) : 0)
                     + (is_array($dueMaintenance) ? count($dueMaintenance) : 0)
                     + (is_array($expiringDocuments) ? count($expiringDocuments) : 0);

            // Detect AJAX request
            $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
                      (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

            if ($isAjax) {
                $this->json(['success' => true, 'message' => "Au fost generate notificări pentru $created evenimente", 'created' => $created]);
            } else {
                $_SESSION['success'] = "Au fost generate notificări automate pentru $created evenimente!";
                $this->redirect('/notifications');
            }
        } catch (Throwable $e) {
            // Detect AJAX request
            $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']) || 
                      (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
            
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Eroare la generare: ' . $e->getMessage()], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la generare: ' . $e->getMessage()];
                $this->redirect('/notifications');
            }
        }
    }
    
    private function validateNotificationData($data) {
        $errors = [];
        
        if (empty($data['user_id'])) {
            $errors[] = 'ID-ul utilizatorului este obligatoriu';
        }
        
        if (empty($data['type'])) {
            $errors[] = 'Tipul notificării este obligatoriu';
        }
        
        if (empty($data['title'])) {
            $errors[] = 'Titlul este obligatoriu';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Titlul nu poate avea mai mult de 255 de caractere';
        }
        
        if (empty($data['message'])) {
            $errors[] = 'Mesajul este obligatoriu';
        }
        
        if (!in_array($data['priority'], ['low', 'medium', 'high'])) {
            $errors[] = 'Prioritatea nu este validă';
        }
        
        if (!empty($data['action_url']) && !filter_var($data['action_url'], FILTER_VALIDATE_URL) && !preg_match('/^\//', $data['action_url'])) {
            $errors[] = 'URL-ul acțiunii nu este valid';
        }
        
        return $errors;
    }

    // Setări notificări: vizualizare și salvare preferințe per utilizator
    public function settings() {
        // Asigurăm existența tabelei system_settings în baza CORE pentru a evita 404 pe shared hosting
        $this->ensureSystemSettingsTable();
        $userId = $_SESSION['user_id'] ?? 1;

        // Wrap entire method in try/catch to render friendly error instead of bubbling 404
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $section = $_POST['section'] ?? 'prefs';

                if ($section === 'prefs') {
                    // Colectăm preferințele din formular
                    $enabledCategories = isset($_POST['categories']) && is_array($_POST['categories']) ? array_values($_POST['categories']) : [];
                    $methods = [
                        'in_app' => isset($_POST['method_in_app']) ? 1 : 0,
                        'email'  => isset($_POST['method_email']) ? 1 : 0,
                        'sms'    => isset($_POST['method_sms']) ? 1 : 0,
                    ];
                    $daysBefore = isset($_POST['days_before']) ? max(0, (int)$_POST['days_before']) : 30;
                    $minPriority = $_POST['min_priority'] ?? 'low';
                    
                    // Doar admin/manager poate seta broadcast la companie
                    require_once __DIR__ . '/../../../core/Auth.php';
                    $auth = Auth::getInstance();
                    $currentUser = $auth->user();
                    $userRole = $currentUser->role_slug ?? $currentUser->role ?? 'user';
                    
                    $broadcastToCompany = 0;
                    if (in_array($userRole, ['admin', 'manager', 'superadmin'])) {
                        $broadcastToCompany = isset($_POST['broadcast_to_company']) ? 1 : 0;
                    }

                    $prefs = [
                        'enabledCategories' => $enabledCategories,
                        'methods' => $methods,
                        'daysBefore' => $daysBefore,
                        'minPriority' => in_array($minPriority, ['low','medium','high','critical']) ? $minPriority : 'low',
                        'broadcastToCompany' => $broadcastToCompany,
                    ];

                    $this->setSetting('notifications_prefs_user_' . $userId, $prefs, 'json', 'Preferințe notificări utilizator');
                    $_SESSION['success'] = 'Preferințele de notificări au fost salvate.';
                    $this->redirect('/notifications/settings');
                    return;
                }

                if ($section === 'smtp') {
                    $smtp = [
                        'transport'  => $_POST['smtp_transport'] ?? 'smtp', // smtp | php_mail
                        'host'       => trim($_POST['smtp_host'] ?? ''),
                        'port'       => (int)($_POST['smtp_port'] ?? 587),
                        'encryption' => $_POST['smtp_encryption'] ?? 'tls', // none|ssl|tls
                        'username'   => trim($_POST['smtp_username'] ?? ''),
                        'password'   => $_POST['smtp_password'] ?? '',
                        'from_email' => trim($_POST['smtp_from_email'] ?? ''),
                        'from_name'  => trim($_POST['smtp_from_name'] ?? ''),
                    ];

                    $this->setSetting('smtp_settings', $smtp, 'json', 'Setări email SMTP');

                    if (isset($_POST['action']) && $_POST['action'] === 'test_email') {
                        require_once __DIR__ . '/../services/Notifier.php';
                        $to = trim($_POST['test_email_to'] ?? '');
                        $ok = false; $err = '';
                        if ($to) {
                            $notifier = new Notifier();
                            [$ok, $err] = $notifier->sendEmail($to, 'Test email - ' . (APP_NAME ?? 'Fleet Management'), 'Acesta este un mesaj de test din aplicatie.', $smtp);
                        } else {
                            $err = 'Introduceți adresa destinatarului pentru test.';
                        }
                        $_SESSION[$ok ? 'success' : 'errors'] = $ok ? 'Emailul de test a fost trimis (sau programat) cu succes.' : [$err];
                    } else {
                        $_SESSION['success'] = 'Setările SMTP au fost salvate.';
                    }
                    $this->redirect('/notifications/settings');
                    return;
                }

                if ($section === 'sms') {
                    $sms = [
                        'provider'   => $_POST['sms_provider'] ?? 'twilio', // twilio|http
                        'from'       => trim($_POST['sms_from'] ?? ''),
                        'account_sid'=> trim($_POST['sms_account_sid'] ?? ''),
                        'auth_token' => $_POST['sms_auth_token'] ?? '',
                        // HTTP generic
                        'http_url'   => trim($_POST['sms_http_url'] ?? ''),
                        'http_method'=> $_POST['sms_http_method'] ?? 'GET',
                        'http_params'=> $_POST['sms_http_params'] ?? '',
                        'sms_default_to' => trim($_POST['sms_default_to'] ?? '')
                    ];
                    $this->setSetting('sms_settings', $sms, 'json', 'Setări SMS');

                    if (isset($_POST['action']) && $_POST['action'] === 'test_sms') {
                        require_once __DIR__ . '/../services/Notifier.php';
                        $to = trim($_POST['test_sms_to'] ?? '');
                        $msg = trim($_POST['test_sms_message'] ?? 'Test SMS din Fleet Management');
                        $ok = false; $err = '';
                        if ($to) {
                            $notifier = new Notifier();
                            [$ok, $err] = $notifier->sendSms($to, $msg, $sms);
                        } else {
                            $err = 'Introduceți numărul de telefon pentru test.';
                        }
                        $_SESSION[$ok ? 'success' : 'errors'] = $ok ? 'SMS-ul de test a fost trimis.' : [$err];
                    } else {
                        $_SESSION['success'] = 'Setările SMS au fost salvate.';
                    }
                    $this->redirect('/notifications/settings');
                    return;
                }
            }

            // GET: încărcăm preferințele din system_settings sau valorile implicite
            $key = 'notifications_prefs_user_' . $userId;
            $row = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
            $prefs = [
                'enabledCategories' => ['insurance_expiry','maintenance_due','document_expiry'],
                'methods' => ['in_app' => 1, 'email' => 0, 'sms' => 0],
                'daysBefore' => 30,
                'minPriority' => 'low',
                'broadcastToCompany' => 0, // Preferința de broadcast (doar admin/manager)
            ];
            if ($row && !empty($row['setting_value'])) {
                $decoded = json_decode($row['setting_value'], true);
                if (is_array($decoded)) {
                    $prefs = array_replace_recursive($prefs, $decoded);
                }
            }

            // SMTP & SMS config
            $smtpRow = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'smtp_settings'");
            $smsRow  = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
            $smtp = [
                'transport' => 'smtp',
                'host' => defined('EMAIL_HOST') ? EMAIL_HOST : 'smtp.example.com',
                'port' => defined('EMAIL_PORT') ? EMAIL_PORT : 587,
                'encryption' => 'tls',
                'username' => defined('EMAIL_USERNAME') ? EMAIL_USERNAME : '',
                'password' => defined('EMAIL_PASSWORD') ? EMAIL_PASSWORD : '',
                'from_email' => defined('EMAIL_USERNAME') ? EMAIL_USERNAME : 'noreply@example.com',
                'from_name' => APP_NAME ?? 'Fleet Management',
            ];
            if ($smtpRow && !empty($smtpRow['setting_value'])) {
                $dec = json_decode($smtpRow['setting_value'], true);
                if (is_array($dec)) { $smtp = array_replace($smtp, $dec); }
            }

            $sms = [
                'provider' => 'twilio',
                'from' => '',
                'account_sid' => '',
                'auth_token' => '',
                'http_url' => '',
                'http_method' => 'GET',
                'http_params' => '',
                'sms_default_to' => ''
            ];
            if ($smsRow && !empty($smsRow['setting_value'])) {
                $dec = json_decode($smsRow['setting_value'], true);
                if (is_array($dec)) { $sms = array_replace($sms, $dec); }
            }

            $this->render('settings', [ 'prefs' => $prefs, 'smtp' => $smtp, 'sms' => $sms ]);
        } catch (Throwable $e) {
            // Dacă orice eroare DB sau logică, afișăm o pagină prietenoasă în loc de 404
            http_response_code(500);
            include 'includes/header.php';
            echo '<div class="container py-4">';
            echo '<div class="alert alert-danger"><h4>Eroare la încărcarea setărilor notificări</h4>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
            echo '<a href="' . ROUTE_BASE . 'notifications" class="btn btn-outline-secondary">Înapoi la notificări</a>';
            echo '</div>';
            include 'includes/footer.php';
            exit;
        }
    }

    private function ensureSystemSettingsTable() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT,
                setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
                description TEXT,
                is_system BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (Throwable $e) {
            // Ignorăm – dacă nu se poate crea, restul paginii va încerca fallback-uri
        }
    }

    private function setSetting($key, $value, $type = 'json', $desc = null) {
        $exists = $this->db->fetch("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
        $val = $type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        if ($exists && isset($exists['id'])) {
            $this->db->query("UPDATE system_settings SET setting_value = ?, setting_type = ? WHERE id = ?", [$val, $type, $exists['id']]);
        } else {
            $this->db->query("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)", [$key, $val, $type, $desc]);
        }
    }
    // sendTestEmail / sendTestSms migrate spre Notifier; păstrăm doar helperul de setări mai sus.
    
    /**
     * V2: Display user notification preferences UI
     */
    public function preferences() {
        try {
            require_once __DIR__ . '/../models/NotificationPreference.php';
            
            $auth = Auth::getInstance();
            $currentUser = $auth->user();
            $userId = $currentUser->id ?? 0;
            $companyId = $currentUser->company_id ?? 0;
            
            if ($userId === 0) {
                throw new Exception('User ID invalid');
            }
            
            // Load preferințe existente
            $prefsModel = new NotificationPreference();
            $prefs = $prefsModel->getOrDefault($userId, $companyId);
            
            // Decode enabled_types JSON
            $enabledTypes = [];
            if (!empty($prefs['enabled_types'])) {
                $decoded = is_string($prefs['enabled_types']) ? json_decode($prefs['enabled_types'], true) : $prefs['enabled_types'];
                $enabledTypes = is_array($decoded) ? $decoded : [];
            }
            
            // Decode quiet_hours JSON
            $quietHours = ['start' => '22:00', 'end' => '08:00'];
            if (!empty($prefs['quiet_hours'])) {
                $decoded = is_string($prefs['quiet_hours']) ? json_decode($prefs['quiet_hours'], true) : $prefs['quiet_hours'];
                if (is_array($decoded)) {
                    $quietHours = array_merge($quietHours, $decoded);
                }
            }
            
            // VIEW SIMPLIFICAT pentru utilizatori normali (fără SMTP/SMS config)
            $this->render('preferences_simple', [
                'prefs' => array_merge($prefs, [
                    'enabled_types' => $enabledTypes,
                    'quiet_hours' => $quietHours
                ]),
                'currentUser' => $currentUser,
                'pageTitle' => 'Preferințele Mele'
            ]);
            
        } catch (Throwable $e) {
            $_SESSION['error_message'] = 'Eroare la încărcarea preferințelor: ' . $e->getMessage();
            $this->redirect('/notifications');
        }
    }
    
    /**
     * V2: Save user notification preferences (POST handler)
     */
    public function savePreferences() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ROUTE_BASE . 'notifications/preferences');
            exit;
        }
        
        try {
            require_once __DIR__ . '/../models/NotificationPreference.php';
            
            $auth = Auth::getInstance();
            $currentUser = $auth->user();
            $userId = $currentUser->id ?? 0;
            $companyId = $currentUser->company_id ?? 0;
            
            if ($userId === 0) {
                throw new Exception('User ID invalid');
            }
            
            // SIMPLIFICAT: Colectăm doar email, phone, enabled_types și quiet_hours
            // Notificările in-app sunt MEREU active
            // Email/SMS se activează automat dacă sunt completate
            $data = [
                'in_app_enabled' => 1, // MEREU activ
                'enabled_types' => $_POST['enabled_types'] ?? [],
                'quiet_hours' => $_POST['quiet_hours'] ?? ['start' => '22:00', 'end' => '08:00']
            ];
            
            // Email (validare și activare automată)
            $email = trim($_POST['email'] ?? '');
            if (!empty($email)) {
                $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
                if (!$validEmail) {
                    throw new Exception('Adresa de email nu este validă');
                }
                $data['email'] = $validEmail;
                $data['email_enabled'] = 1; // Activează automat dacă e completat
            } else {
                $data['email'] = $currentUser->email ?? null;
                $data['email_enabled'] = !empty($currentUser->email) ? 1 : 0;
            }
            
            // Telefon (validare și activare automată)
            $phone = trim($_POST['phone'] ?? '');
            if (!empty($phone)) {
                $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                if (strlen($cleanPhone) < 10) {
                    throw new Exception('Numărul de telefon nu este valid');
                }
                $data['phone'] = $cleanPhone;
                $data['sms_enabled'] = 1; // Activează automat dacă e completat
            } else {
                $data['phone'] = null;
                $data['sms_enabled'] = 0;
            }
            
            // Validation
            if (empty($data['enabled_types']) || !is_array($data['enabled_types'])) {
                throw new Exception('Selectează cel puțin un tip de notificare!');
            }
            
            // Setări default pentru preferințe avansate (controlate de admin)
            $data['min_priority'] = 'low';
            $data['frequency'] = 'immediate';
            $data['days_before_expiry'] = 30;
            $data['timezone'] = 'Europe/Bucharest';
            $data['push_enabled'] = 0;
            
            // Save to database
            $prefsModel = new NotificationPreference();
            $result = $prefsModel->createOrUpdate($userId, $companyId, $data);
            
            if ($result) {
                // Log action
                NotificationLog::log('preferences_update', 'success', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'email' => !empty($data['email']) ? 'set' : 'empty',
                    'phone' => !empty($data['phone']) ? 'set' : 'empty',
                    'enabled_types_count' => count($data['enabled_types'])
                ]);
                
                $_SESSION['success_message'] = 'Preferințele tale au fost salvate cu succes!';
            } else {
                throw new Exception('Eroare la salvarea preferințelor');
            }
            
        } catch (Throwable $e) {
            NotificationLog::log('preferences_update', 'error', [
                'user_id' => $userId ?? 0,
                'error' => $e->getMessage()
            ], null, $e->getMessage());
            
            $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
        }
        
        header('Location: ' . ROUTE_BASE . 'notifications/preferences');
        exit;
    }
    
    /**
     * V2: Send test notification to user (AJAX endpoint)
     */
    public function sendTest() {
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../models/NotificationPreference.php';
            
            $auth = Auth::getInstance();
            $currentUser = $auth->user();
            $userId = $currentUser->id ?? 0;
            $companyId = $currentUser->company_id ?? 0;
            
            if ($userId === 0) {
                throw new Exception('User not authenticated');
            }
            
            // Create test notification
            $testData = [
                'user_id' => $userId,
                'company_id' => $companyId,
                'type' => 'system_alert',
                'title' => 'Notificare Test',
                'message' => 'Aceasta este o notificare test trimisă la ' . date('Y-m-d H:i:s') . '. Dacă ați primit-o, configurarea funcționează corect!',
                'priority' => 'low',
                'action_url' => '/notifications/preferences'
            ];
            
            $notificationId = $this->notificationModel->create($testData);
            
            if ($notificationId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notificare test trimisă cu succes',
                    'notification_id' => $notificationId
                ]);
            } else {
                throw new Exception('Failed to create test notification');
            }
            
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>
