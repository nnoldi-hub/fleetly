<?php
// modules/notifications/controllers/NotificationController.php

require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Notification.php';

class NotificationController extends Controller {
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->notificationModel = new Notification();
    }
    
    public function index() {
        $this->alerts();
    }
    
    public function alerts() {
        $page = $_GET['page'] ?? 1;
        $type = $_GET['type'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $status = $_GET['status'] ?? '';
        $per_page = (int)($_GET['per_page'] ?? 25);
        
        $conditions = [];
        // Determine user (fallback to 1 when auth/session not wired yet)
        $userId = $_SESSION['user_id'] ?? 1;
        $conditions['user_id'] = $userId;
        
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
            $notifications = $this->notificationModel->getAllWithDetails($conditions, $offset, $per_page);
            $totalRecords = $this->notificationModel->getTotalCount($conditions);
            $totalPages = ceil($totalRecords / $per_page);
            
            // Obținem statistici
            $stats = $this->notificationModel->getStatistics($userId);
            $unreadCount = $this->notificationModel->getUnreadCount($userId);
        } catch (Throwable $e) {
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
        
        // Folosim sistemul de layout standard
        $this->render('alerts', $data);
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
        
        if ($this->notificationModel->markAsRead($id)) {
            if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
                $this->json(['success' => true, 'message' => 'Notificarea a fost marcată ca citită']);
            } else {
                $_SESSION['success'] = 'Notificarea a fost marcată ca citită!';
                $this->redirect('/notifications');
            }
        } else {
            if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
                $this->json(['success' => false, 'message' => 'Eroare la marcarea notificării'], 500);
            } else {
                $_SESSION['errors'] = ['Eroare la marcarea notificării'];
                $this->redirect('/notifications');
            }
        }
    }
    
    public function markAllAsRead() {
        $userId = $_SESSION['user_id'] ?? 1;
        
        if ($this->notificationModel->markAllAsRead($userId)) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => true, 'message' => 'Toate notificările au fost marcate ca citite']);
            } else {
                $_SESSION['success'] = 'Toate notificările au fost marcate ca citite!';
                $this->redirect('/notifications');
            }
        } else {
            if (isset($_POST['ajax'])) {
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
        
        if ($this->notificationModel->delete($id)) {
            if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
                $this->json(['success' => true, 'message' => 'Notificarea a fost ștearsă']);
            } else {
                $_SESSION['success'] = 'Notificarea a fost ștearsă!';
                $this->redirect('/notifications');
            }
        } else {
            if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
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
        // Generăm notificări automate pentru sistem
        $notifications = [];
        
        // Obținem company_id al utilizatorului curent pentru broadcast
        $userId = $_SESSION['user_id'] ?? 1;
        $user = $this->db->fetch("SELECT company_id, role FROM users WHERE id = ?", [$userId]);
        $companyId = $user['company_id'] ?? null;
        
        // Verificăm asigurările care expiră
        $insuranceModel = new Insurance();
        $expiringInsurance = $insuranceModel->getExpiring(30); // 30 de zile
        
        foreach ($expiringInsurance as $insurance) {
            $daysUntilExpiry = (strtotime($insurance['end_date']) - time()) / (24 * 3600);
            
            $priority = 'medium';
            if ($daysUntilExpiry <= 7) $priority = 'high';
            elseif ($daysUntilExpiry <= 14) $priority = 'medium';
            else $priority = 'low';
            
            // Folosim metoda statică care respectă preferința de broadcast
            Notification::createInsuranceExpiryNotification(
                $insurance['id'],
                $insurance['license_plate'],
                $insurance['insurance_type'],
                $insurance['end_date'],
                $priority,
                $companyId
            );
        }
        
        // Verificăm mentenanța scadentă
        $maintenanceModel = new Maintenance();
        $dueMaintenance = $maintenanceModel->getDueMaintenance();
        
        foreach ($dueMaintenance as $maintenance) {
            // Folosim metoda statică care respectă preferința de broadcast
            Notification::createMaintenanceNotification(
                $maintenance['vehicle_id'],
                $maintenance['license_plate'],
                $maintenance['maintenance_type'],
                $companyId
            );
        }
        
        // Contorizăm notificările create (broadcast creează mai multe înregistrări)
        $created = count($expiringInsurance) + count($dueMaintenance);
        
        if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
            $this->json(['success' => true, 'message' => "Au fost generate notificări pentru $created evenimente", 'created' => $created]);
        } else {
            $_SESSION['success'] = "Au fost generate notificări automate pentru $created evenimente!";
            $this->redirect('/notifications');
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
}
?>
