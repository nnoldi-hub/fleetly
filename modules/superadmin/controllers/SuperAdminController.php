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

        error_log('[SuperAdmin] companies() invoked');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'subscription_type' => $_GET['subscription_type'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        try {
            $companyModel = new Company();
            $companies = $companyModel->getAll($filters);
        } catch (Throwable $e) {
            error_log('[SuperAdmin] companies() error: ' . $e->getMessage());
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

        error_log('[SuperAdmin] edit() invoked with id=' . ($_GET['id'] ?? 'null'));

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID companie invalid.';
            $this->redirect('/superadmin/companies');
        }

        $companyModel = new Company();
        try {
            $company = $companyModel->getById($id);
        } catch (Throwable $e) {
            error_log('[SuperAdmin] edit() getById error: ' . $e->getMessage());
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
                error_log('[SuperAdmin] resetAdmin email error: ' . $e->getMessage());
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
}
