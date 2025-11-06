<?php

class LoginController extends Controller {
    
    /**
     * Show login form
     */
    public function index() {
        // Redirect if already logged in
        if (Auth::getInstance()->check()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // Clear any output buffer and render standalone login page
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $viewPath = __DIR__ . '/../views/login.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            http_response_code(404);
            echo "Login view not found at: " . htmlspecialchars($viewPath);
        }
        exit;
    }
    
    /**
     * Process login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        $result = Auth::getInstance()->login($username, $password, $rememberMe);
        
        if ($result['success']) {
            // Redirect based on role
            $user = Auth::getInstance()->user();
            
            if ($user->role_slug === 'superadmin') {
                $this->redirect('/superadmin/dashboard');
            } else {
                // Select the tenant database for this user's company
                try {
                    if (!empty($user->company_id)) {
                        Database::getInstance()->setTenantDatabaseByCompanyId($user->company_id);
                    }
                } catch (Throwable $e) {
                }
                $this->redirect('/dashboard');
            }
        } else {
            $_SESSION['error'] = $result['message'];
            $this->redirect('/login');
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        Auth::getInstance()->logout();
        $_SESSION['success'] = 'Ai fost deconectat cu succes';
        $this->redirect('/login');
    }
    
    /**
     * Show password reset request form
     */
    public function forgotPassword() {
        $this->render('modules/auth/views/forgot_password', [
            'pageTitle' => 'Resetare Parolă'
        ]);
    }
    
    /**
     * Process password reset request
     */
    public function requestPasswordReset() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
        }
        
        $email = $_POST['email'] ?? '';
        
        // TODO: Implement password reset logic
        // 1. Check if user exists
        // 2. Generate reset token
        // 3. Send email with reset link
        
        $_SESSION['success'] = 'Dacă email-ul există în sistem, veți primi instrucțiuni de resetare';
        $this->redirect('/login');
    }
}
