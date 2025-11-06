<?php
// modules/user/controllers/UserController.php

class UserController extends Controller {
    
    public function index() {
        Auth::getInstance()->requireAuth();
        $current = Auth::getInstance()->user();
        $companyId = $current->company_id ?? null;
        if (!$companyId) {
            $_SESSION['errors'] = ['Companie necunoscută pentru contul curent.'];
            header('Location: ' . BASE_URL);
            exit;
        }
        $userModel = new User();
        $company = (new Company())->getById($companyId);
        $users = $userModel->getByCompany($companyId);
        $used = (int)$userModel->countByCompany($companyId);
        $limit = (int)($company->max_users ?? 0);
        $remaining = max(0, $limit - $used);
        $this->render('list', [
            'pageTitle' => 'Utilizatori',
            'users' => $users,
            'company' => $company,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining
        ]);
    }
    
    public function add() {
        Auth::getInstance()->requireAuth();
        $current = Auth::getInstance()->user();
        $companyId = $current->company_id ?? null;
        if (!$companyId) { header('Location: ' . BASE_URL . 'users'); exit; }
        $userModel = new User();
        $roles = $userModel->getAvailableRoles($companyId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'company_id' => $companyId,
                'role_id' => (int)($_POST['role_id'] ?? 0),
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'status' => $_POST['status'] ?? 'active',
                'password' => $_POST['password'] ?? null,
                'created_by' => $current->id ?? null,
            ];
            $res = $userModel->create($data);
            if ($res['success'] ?? false) {
                $_SESSION['success'] = 'Utilizator creat cu succes.' . (!empty($res['generated_password']) ? ' Parola: ' . $res['generated_password'] : '');
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            $_SESSION['errors'] = [$res['message'] ?? 'Eroare la creare.'];
        }
        $this->render('add', [
            'pageTitle' => 'Adaugă utilizator',
            'roles' => $roles
        ]);
    }
    
    public function edit() {
        Auth::getInstance()->requireAuth();
        $current = Auth::getInstance()->user();
        $companyId = $current->company_id ?? null;
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: ' . BASE_URL . 'users'); exit; }
        $userModel = new User();
        $user = $userModel->getById($id);
        if (!$user || ($companyId && $user->company_id != $companyId)) {
            $_SESSION['errors'] = ['Utilizator inexistent sau aparține altei companii.'];
            header('Location: ' . BASE_URL . 'users');
            exit;
        }
        $roles = $userModel->getAvailableRoles($companyId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? $user->role_id),
                'status' => $_POST['status'] ?? $user->status,
                'password' => $_POST['password'] ?? null,
            ];
            $res = $userModel->update($id, $data);
            if ($res['success'] ?? false) {
                $_SESSION['success'] = 'Utilizator actualizat.';
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            $_SESSION['errors'] = [$res['message'] ?? 'Eroare la actualizare.'];
        }
        $this->render('edit', [
            'pageTitle' => 'Editează utilizator',
            'user' => $user,
            'roles' => $roles
        ]);
    }
    
    public function delete() {
        Auth::getInstance()->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . 'users'); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: ' . BASE_URL . 'users'); exit; }
        $userModel = new User();
        $res = $userModel->delete($id);
        if ($res['success'] ?? false) {
            $_SESSION['success'] = 'Utilizator șters.';
        } else {
            $_SESSION['errors'] = [$res['message'] ?? 'Eroare la ștergere.'];
        }
        header('Location: ' . BASE_URL . 'users');
        exit;
    }
    
    public function profile() {
        $userId = $_SESSION['user_id'] ?? 1;
        // Load current SMS phone from system_settings
        $key = 'user_' . $userId . '_sms_to';
        $row = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        $smsPhone = $row['setting_value'] ?? '';
        $this->render('profile', [
            'pageTitle' => 'Profil Utilizator',
            'smsPhone' => $smsPhone
        ]);
    }
    
    public function saveProfile() {
        $userId = $_SESSION['user_id'] ?? 1;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'profile');
            exit;
        }
        $phone = trim($_POST['sms_phone'] ?? '');
        // Normalize: remove spaces, dashes, parentheses; convert leading 00 to +; ensure starts with + and 8-15 digits
        $raw = $phone;
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);
        if (strpos($phone, '00') === 0) { $phone = '+' . substr($phone, 2); }
        if ($phone !== '' && $phone[0] !== '+') { $phone = '+' . $phone; }
        if ($phone !== '' && !preg_match('/^\+[0-9]{8,15}$/', $phone)) {
            $_SESSION['errors'] = ['Număr de telefon invalid. Folosește formatul internațional, ex: +40712345678'];
            $_SESSION['old_sms_phone'] = $raw;
            header('Location: ' . BASE_URL . 'profile');
            exit;
        }
        $key = 'user_' . $userId . '_sms_to';
        $exists = $this->db->fetch("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
        if ($exists && isset($exists['id'])) {
            $this->db->query("UPDATE system_settings SET setting_value = ?, setting_type = 'string' WHERE id = ?", [$phone, $exists['id']]);
        } else {
            $this->db->query("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, 'string', 'Telefon SMS utilizator')", [$key, $phone]);
        }
        $_SESSION['success'] = 'Numărul pentru SMS a fost salvat.';
        header('Location: ' . BASE_URL . 'profile');
        exit;
    }
    
    public function settings() {
        $this->render('settings', [
            'pageTitle' => 'Setări Aplicație'
        ]);
    }
    
    public function logout() {
        // În viitor, aici va fi logica de logout
        // Pentru moment, redirectează la dashboard
        header('Location: ' . BASE_URL);
        exit;
    }
}
