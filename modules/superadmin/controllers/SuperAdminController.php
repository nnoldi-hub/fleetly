<?php
require_once __DIR__ . '/../../../core/Mailer.php';

class SuperAdminController extends Controller {

    public function dashboard() {
        // Only SuperAdmin can access
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }

        // Basic metrics
        $db = Database::getInstance();
        $metrics = [
            'companies_total'   => $db->fetch("SELECT COUNT(*) AS c FROM companies")['c'] ?? 0,
            'companies_active'  => $db->fetch("SELECT COUNT(*) AS c FROM companies WHERE status='active'")['c'] ?? 0,
            'companies_trial'   => $db->fetch("SELECT COUNT(*) AS c FROM companies WHERE status='trial'")['c'] ?? 0,
            'companies_suspended' => $db->fetch("SELECT COUNT(*) AS c FROM companies WHERE status='suspended'")['c'] ?? 0,
            'users_total'       => $db->fetch("SELECT COUNT(*) AS c FROM users")['c'] ?? 0,
        ];

        // Recent companies
        $companies = $db->fetchAll("SELECT id, name, email, status, subscription_type, created_at FROM companies ORDER BY created_at DESC LIMIT 10");

        $this->render('dashboard', [
            'pageTitle' => 'SuperAdmin - Dashboard',
            'metrics' => $metrics,
            'companies' => $companies,
        ]);
    }

    public function companies() {
        // Guard: SuperAdmin only
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'subscription_type' => $_GET['subscription_type'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        try {
            $companyModel = new Company();
            $companies = $companyModel->getAll($filters);
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Eroare la încărcarea companiilor.';
            $companies = [];
        }

        $this->render('companies/index', [
            'pageTitle' => 'SuperAdmin - Companii',
            'companies' => $companies,
            'filters' => $filters,
        ]);
    }

    public function add() {
        // Guard: SuperAdmin only
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'registration_number' => trim($_POST['registration_number'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? 'România'),
                'subscription_type' => trim($_POST['subscription_type'] ?? 'basic'),
                'max_users' => (int)($_POST['max_users'] ?? 5),
                'max_vehicles' => (int)($_POST['max_vehicles'] ?? 10),
                'admin_email' => trim($_POST['admin_email'] ?? ''),
                'admin_username' => trim($_POST['admin_username'] ?? ''),
                'admin_first_name' => trim($_POST['admin_first_name'] ?? ''),
                'admin_last_name' => trim($_POST['admin_last_name'] ?? ''),
                'admin_password' => $_POST['admin_password'] ?? '',
                'created_by' => Auth::getInstance()->user()->id ?? null,
            ];

            // Basic validation
            $errors = [];
            if ($data['name'] === '') { $errors[] = 'Numele companiei este obligatoriu.'; }
            if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email companie invalid.'; }
            if ($data['admin_email'] && !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email administrator invalid.'; }

            if (empty($errors)) {
                $companyModel = new Company();
                $result = $companyModel->create($data);
                if (!empty($result['success'])) {
                    $_SESSION['success'] = 'Compania a fost creată cu succes.' . (!empty($result['admin_password']) ? ' Parola administrator generată: ' . htmlspecialchars($result['admin_password']) : '');
                    $this->redirect('/superadmin/companies');
                } else {
                    $_SESSION['error'] = $result['message'] ?? 'Eroare la crearea companiei.';
                }
            } else {
                $_SESSION['errors'] = $errors;
            }
        }

        $this->render('companies/add', [
            'pageTitle' => 'SuperAdmin - Adaugă companie',
        ]);
    }

    public function edit() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID companie invalid.';
            $this->redirect('/superadmin/companies');
        }

        $companyModel = new Company();
        try {
            $company = $companyModel->getById($id);
        } catch (Throwable $e) {
            $company = null;
        }
        if (!$company) {
            $_SESSION['error'] = 'Compania nu a fost găsită.';
            $this->redirect('/superadmin/companies');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'registration_number' => trim($_POST['registration_number'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'country' => trim($_POST['country'] ?? 'România'),
                'status' => trim($_POST['status'] ?? $company->status),
                'subscription_type' => trim($_POST['subscription_type'] ?? $company->subscription_type),
                'max_users' => (int)($_POST['max_users'] ?? $company->max_users),
                'max_vehicles' => (int)($_POST['max_vehicles'] ?? $company->max_vehicles),
            ];

            $errors = [];
            if ($data['name'] === '') { $errors[] = 'Numele companiei este obligatoriu.'; }
            if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email companie invalid.'; }

            if (empty($errors)) {
                $result = $companyModel->update($id, $data);
                if (!empty($result['success'])) {
                    $_SESSION['success'] = 'Compania a fost actualizată cu succes.';
                    $this->redirect('/superadmin/companies');
                } else {
                    $_SESSION['error'] = $result['message'] ?? 'Eroare la actualizare.';
                }
            } else {
                $_SESSION['errors'] = $errors;
            }
            // reload latest data for form after post
            $company = $companyModel->getById($id);
        }

        $this->render('companies/edit', [
            'pageTitle' => 'SuperAdmin - Editează companie',
            'company' => $company,
        ]);
    }

    public function changeStatus() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = $_POST['status'] ?? '';
        if ($id <= 0 || !in_array($status, ['active','trial','suspended','expired'])) {
            $this->json(['success' => false, 'message' => 'Date invalide'], 400);
        }
        $db = Database::getInstance();
        try {
            $db->query("UPDATE companies SET status = ? WHERE id = ?", [$status, $id]);
            Auth::getInstance()->logAudit(Auth::getInstance()->user()->id ?? null, $id, 'update', 'company', $id, null, ['status' => $status]);
            $this->json(['success' => true]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Eroare la schimbarea statusului'], 500);
        }
    }

    // Reset company admin account (password and optionally username/email)
    public function resetAdmin() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/superadmin/companies');
        }

        $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : (int)($_POST['company_id'] ?? 0);
        if ($companyId <= 0) {
            $_SESSION['error'] = 'Companie invalidă.';
            $this->redirect('/superadmin/companies');
        }

            $newUsername = trim($_POST['admin_username'] ?? '');
            $newEmail = trim($_POST['admin_email'] ?? '');
            $newPassword = trim($_POST['admin_password'] ?? '');

        $companyModel = new Company();
            $res = $companyModel->resetAdminAccount($companyId, $newUsername ?: null, $newEmail ?: null, $newPassword ?: null);
        if (!empty($res['success'])) {
            $msg = 'Parola administrator a fost resetată.';
            if (!empty($res['username'])) { $msg .= ' Username: ' . htmlspecialchars($res['username']) . '.'; }
            if (!empty($res['email'])) { $msg .= ' Email: ' . htmlspecialchars($res['email']) . '.'; }
            if (!empty($res['password'])) { $msg .= ' Parolă nouă: ' . htmlspecialchars($res['password']); }

            // Trimite email de notificare daca exista email
            try {
                if (!empty($res['email']) && !empty($res['password'])) {
                    $company = (new Company())->getById($companyId);
                    $subject = 'Resetare cont administrator - ' . ($company->name ?? 'Compania ta');
                    $html = '<p>Salut,</p>' .
                            '<p>Contul de administrator a fost resetat pentru compania <strong>' . htmlspecialchars($company->name ?? ('#'.$companyId)) . '</strong>.</p>' .
                            '<p>Credentiale noi:</p>' .
                            '<ul>' .
                            '<li>Utilizator: <strong>' . htmlspecialchars($res['username'] ?? '') . '</strong></li>' .
                            '<li>Parola: <strong>' . htmlspecialchars($res['password']) . '</strong></li>' .
                            '</ul>' .
                            '<p>Va rugam schimbati parola dupa prima autentificare.</p>';
                    $text = "Contul de administrator a fost resetat. Utilizator: " . ($res['username'] ?? '') . ", Parola: " . $res['password'] . ".";
                    // Avoid fatal if Mailer missing
                    if (class_exists('Mailer')) {
                        Mailer::send($res['email'], $subject, $html, $text);
                    }
                }
            } catch (\Throwable $e) {
                // Silent fail email
            }

            $_SESSION['success'] = $msg;
        } else {
            $_SESSION['error'] = $res['message'] ?? 'Eroare la resetarea contului admin.';
        }

        $this->redirect('/superadmin/companies/edit?id=' . $companyId);
    }

    // Start intervention mode (act as a company)
    public function actAs() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403); die('Acces interzis');
        }
        $id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
        if ($id <= 0) { $this->redirect('/superadmin/companies'); }
        $company = (new Company())->getById($id);
        if (!$company) { $_SESSION['error'] = 'Companie inexistentă'; $this->redirect('/superadmin/companies'); }
        // Optional: verifică status
        if ($company->status !== 'active' && $company->status !== 'trial') {
            $_SESSION['error'] = 'Compania nu este activă.'; $this->redirect('/superadmin/companies');
        }
        Auth::getInstance()->startActing($id, $company->name ?? '');
        // Configure tenant database for this company
        Database::getInstance()->setTenantDatabaseByCompanyId($id);
        $_SESSION['success'] = 'Acum gestionezi compania: ' . htmlspecialchars($company->name ?? ('#'.$id));
        $this->redirect('/dashboard');
    }

    // Exit intervention mode
    public function stopActing() {
        if (!Auth::getInstance()->isSuperAdmin()) { $this->redirect('/login'); }
        Auth::getInstance()->stopActing();
        // Clear tenant db in session (it will be re-resolved when acting again)
        if (isset($_SESSION['acting_company']['db'])) unset($_SESSION['acting_company']['db']);
        $_SESSION['success'] = 'Ai ieșit din modul de intervenție.';
        $this->redirect('/superadmin/companies');
    }
    
    /**
     * V2: Notifications Analytics Dashboard
     */
    public function notificationsDashboard() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis - SuperAdmin only');
        }
        
        $this->render('notifications_dashboard', [
            'pageTitle' => 'SuperAdmin - Notifications Analytics'
        ]);
    }
    
    /**
     * V2: Export notifications report
     */
    public function notificationsExport() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }
        
        $type = $_GET['type'] ?? 'general';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $db = Database::getInstance();
        
        // Generate CSV report based on type
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="notifications_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if ($type === 'company') {
            // Company comparison report
            fputcsv($output, ['Company', 'Total Notifications', 'Sent', 'Failed', 'Pending', 'Delivery Rate (%)']);
            
            $data = $db->fetchAll("
                SELECT 
                    c.name,
                    COUNT(n.id) as total,
                    SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN n.status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN n.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    ROUND(SUM(CASE WHEN n.status = 'sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(n.id), 2) as delivery_rate
                FROM companies c
                LEFT JOIN notifications n ON n.company_id = c.id 
                    AND n.created_at BETWEEN ? AND ?
                WHERE c.status = 'active'
                GROUP BY c.id, c.name
                ORDER BY total DESC
            ", [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['name'],
                    $row['total'],
                    $row['sent'],
                    $row['failed'],
                    $row['pending'],
                    $row['delivery_rate']
                ]);
            }
            
        } else {
            // General report
            fputcsv($output, ['Date', 'Company', 'Type', 'Channel', 'Status', 'User', 'Created At']);
            
            $data = $db->fetchAll("
                SELECT 
                    DATE(n.created_at) as date,
                    c.name as company,
                    n.type,
                    COALESCE(nq.channel, 'in_app') as channel,
                    n.status,
                    u.username,
                    n.created_at
                FROM notifications n
                LEFT JOIN companies c ON c.id = n.company_id
                LEFT JOIN users u ON u.id = n.user_id
                LEFT JOIN notification_queue nq ON nq.notification_id = n.id
                WHERE n.created_at BETWEEN ? AND ?
                ORDER BY n.created_at DESC
                LIMIT 10000
            ", [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['company'],
                    $row['type'],
                    $row['channel'],
                    $row['status'],
                    $row['username'],
                    $row['created_at']
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * V2: Template Manager (CRUD for global templates)
     */
    public function notificationTemplates() {
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }
        
        require_once __DIR__ . '/../../notifications/models/NotificationTemplate.php';
        
        $templateModel = new NotificationTemplate();
        $db = Database::getInstance();
        
        // Handle POST actions (create/update/delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            try {
                if ($action === 'create' || $action === 'update') {
                    $data = [
                        'slug' => $_POST['slug'] ?? '',
                        'name' => $_POST['name'] ?? '',
                        'email_subject' => $_POST['email_subject'] ?? '',
                        'email_body' => $_POST['email_body'] ?? '',
                        'sms_body' => $_POST['sms_body'] ?? '',
                        'push_title' => $_POST['push_title'] ?? '',
                        'push_body' => $_POST['push_body'] ?? '',
                        'in_app_title' => $_POST['in_app_title'] ?? '',
                        'in_app_message' => $_POST['in_app_message'] ?? '',
                        'available_variables' => $_POST['available_variables'] ?? [],
                        'default_priority' => $_POST['default_priority'] ?? 'medium',
                        'enabled' => isset($_POST['enabled']) ? 1 : 0
                    ];
                    
                    if ($action === 'create') {
                        $templateModel->create($data);
                        $_SESSION['success_message'] = 'Template created successfully!';
                    } else {
                        $id = (int)$_POST['id'];
                        $templateModel->update($id, $data);
                        $_SESSION['success_message'] = 'Template updated successfully!';
                    }
                } elseif ($action === 'delete') {
                    $id = (int)$_POST['id'];
                    $templateModel->delete($id);
                    $_SESSION['success_message'] = 'Template deleted successfully!';
                }
            } catch (Throwable $e) {
                $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
            }
            
            header('Location: ' . ROUTE_BASE . 'superadmin/notifications/templates');
            exit;
        }
        
        // Get all global templates
        $templates = $db->fetchAll("
            SELECT 
                id,
                slug,
                name,
                enabled,
                default_priority,
                created_at,
                (SELECT COUNT(*) FROM notifications WHERE template_id = notification_templates.id) as usage_count
            FROM notification_templates
            WHERE company_id IS NULL
            ORDER BY slug ASC
        ");
        
        $this->render('notification_templates', [
            'pageTitle' => 'SuperAdmin - Notification Templates',
            'templates' => $templates
        ]);
    }
}
