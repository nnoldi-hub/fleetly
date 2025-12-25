<?php
/**
 * PartnerAdminController
 * 
 * Controller pentru administrarea partenerilor de către SuperAdmin
 */

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../models/Partner.php';
require_once __DIR__ . '/../../models/PartnerCategory.php';

class PartnerAdminController {
    private $partnerModel;
    private $categoryModel;
    private $user;
    
    public function __construct() {
        // Verifică dacă e SuperAdmin
        if (!Auth::getInstance()->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis. Doar SuperAdmin poate accesa această pagină.');
        }
        
        $this->partnerModel = new Partner();
        $this->categoryModel = new PartnerCategory();
        $this->user = Auth::getInstance()->user();
    }
    
    /**
     * Dashboard parteneri - lista tuturor partenerilor
     */
    public function index() {
        $filters = [
            'include_inactive' => true,
            'category_id' => $_GET['category_id'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        $partners = $this->partnerModel->getAll($filters);
        $categories = $this->categoryModel->getAll(true);
        
        include __DIR__ . '/../../views/admin/partners/index.php';
    }
    
    /**
     * Formular adăugare partener
     */
    public function create() {
        $categories = $this->categoryModel->getAll(true);
        $partner = null;
        $errors = [];
        
        include __DIR__ . '/../../views/admin/partners/form.php';
    }
    
    /**
     * Salvare partener nou
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $data = $this->sanitizeInput($_POST);
        $errors = $this->validatePartner($data);
        
        // Handle file uploads
        $data['logo'] = $this->handleFileUpload('logo', 'logos');
        $data['banner_image'] = $this->handleFileUpload('banner_image', 'banners');
        
        if (!empty($errors)) {
            $categories = $this->categoryModel->getAll(true);
            $partner = (object) $data;
            include __DIR__ . '/../../views/admin/partners/form.php';
            return;
        }
        
        $data['created_by'] = $this->user->id;
        
        try {
            $id = $this->partnerModel->create($data);
            $_SESSION['success'] = 'Partenerul a fost adăugat cu succes!';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la salvare: ' . $e->getMessage();
            $categories = $this->categoryModel->getAll(true);
            $partner = (object) $data;
            include __DIR__ . '/../../views/admin/partners/form.php';
        }
        exit;
    }
    
    /**
     * Formular editare partener
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $partner = $this->partnerModel->getById($id);
        
        if (!$partner) {
            $_SESSION['error'] = 'Partenerul nu a fost găsit.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $partner = (object) $partner;
        $categories = $this->categoryModel->getAll(true);
        $errors = [];
        
        // Obține statistici
        $stats = $this->partnerModel->getStats($id);
        
        include __DIR__ . '/../../views/admin/partners/form.php';
    }
    
    /**
     * Actualizare partener
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $data = $this->sanitizeInput($_POST);
        $errors = $this->validatePartner($data);
        
        // Handle file uploads (doar dacă s-a încărcat fișier nou)
        if (!empty($_FILES['logo']['name'])) {
            $data['logo'] = $this->handleFileUpload('logo', 'logos');
        }
        if (!empty($_FILES['banner_image']['name'])) {
            $data['banner_image'] = $this->handleFileUpload('banner_image', 'banners');
        }
        
        if (!empty($errors)) {
            $partner = (object) array_merge($this->partnerModel->getById($id), $data);
            $categories = $this->categoryModel->getAll(true);
            include __DIR__ . '/../../views/admin/partners/form.php';
            return;
        }
        
        try {
            $this->partnerModel->update($id, $data);
            $_SESSION['success'] = 'Partenerul a fost actualizat cu succes!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la actualizare: ' . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
        exit;
    }
    
    /**
     * Șterge partener
     */
    public function delete() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = 'ID invalid.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        try {
            $this->partnerModel->delete($id);
            $_SESSION['success'] = 'Partenerul a fost șters cu succes!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la ștergere: ' . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
        exit;
    }
    
    /**
     * Toggle status activ/inactiv
     */
    public function toggleStatus() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = 'ID invalid.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $partner = $this->partnerModel->getById($id);
        
        if ($partner) {
            $this->partnerModel->update($id, [
                'is_active' => $partner['is_active'] ? 0 : 1
            ]);
            $_SESSION['success'] = $partner['is_active'] ? 'Partenerul a fost dezactivat.' : 'Partenerul a fost activat.';
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
        exit;
    }
    
    /**
     * Toggle featured
     */
    public function toggleFeatured() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = 'ID invalid.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
            exit;
        }
        
        $partner = $this->partnerModel->getById($id);
        
        if ($partner) {
            $this->partnerModel->update($id, [
                'is_featured' => $partner['is_featured'] ? 0 : 1
            ]);
            $_SESSION['success'] = $partner['is_featured'] ? 'Partenerul nu mai este promovat.' : 'Partenerul este acum promovat!';
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partners');
        exit;
    }
    
    // ========== CATEGORII ==========
    
    /**
     * Lista categorii
     */
    public function categories() {
        $categories = $this->categoryModel->getAllWithCounts();
        include __DIR__ . '/../../views/admin/partners/categories.php';
    }
    
    /**
     * Formular adăugare categorie
     */
    public function createCategory() {
        $category = null;
        $errors = [];
        include __DIR__ . '/../../views/admin/partners/category-form.php';
    }
    
    /**
     * Salvare categorie nouă
     */
    public function storeCategory() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? 'fa-handshake'),
            'color' => trim($_POST['color'] ?? '#007bff'),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Numele categoriei este obligatoriu.';
        }
        
        if (!empty($errors)) {
            $category = (object) $data;
            include __DIR__ . '/../../views/admin/partners/category-form.php';
            return;
        }
        
        try {
            $this->categoryModel->create($data);
            $_SESSION['success'] = 'Categoria a fost adăugată cu succes!';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la salvare: ' . $e->getMessage();
            $category = (object) $data;
            include __DIR__ . '/../../views/admin/partners/category-form.php';
        }
        exit;
    }
    
    /**
     * Editare categorie
     */
    public function editCategory() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $category = $this->categoryModel->getById($id);
        
        if (!$category) {
            $_SESSION['error'] = 'Categoria nu a fost găsită.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $category = (object) $category;
        $errors = [];
        
        include __DIR__ . '/../../views/admin/partners/category-form.php';
    }
    
    /**
     * Actualizare categorie
     */
    public function updateCategory() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? 'fa-handshake'),
            'color' => trim($_POST['color'] ?? '#007bff'),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        try {
            $this->categoryModel->update($id, $data);
            $_SESSION['success'] = 'Categoria a fost actualizată cu succes!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la actualizare: ' . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
        exit;
    }
    
    /**
     * Șterge categorie
     */
    public function deleteCategory() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = 'ID invalid.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
            exit;
        }
        
        $result = $this->categoryModel->delete($id);
        
        if ($result) {
            $_SESSION['success'] = 'Categoria a fost ștearsă cu succes!';
        } else {
            $_SESSION['error'] = 'Nu se poate șterge categoria - are parteneri asociați.';
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-partner-categories');
        exit;
    }
    
    // ========== HELPERS ==========
    
    /**
     * Sanitizare input
     */
    private function sanitizeInput($data) {
        return [
            'category_id' => (int) ($data['category_id'] ?? 1),
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'promotional_text' => trim($data['promotional_text'] ?? ''),
            'website_url' => trim($data['website_url'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'address' => trim($data['address'] ?? ''),
            'discount_info' => trim($data['discount_info'] ?? ''),
            'promo_code' => trim($data['promo_code'] ?? ''),
            'is_featured' => isset($data['is_featured']) ? 1 : 0,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'valid_from' => !empty($data['valid_from']) ? $data['valid_from'] : null,
            'valid_until' => !empty($data['valid_until']) ? $data['valid_until'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0)
        ];
    }
    
    /**
     * Validare partener
     */
    private function validatePartner($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Numele partenerului este obligatoriu.';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email-ul nu este valid.';
        }
        
        if (!empty($data['website_url']) && !filter_var($data['website_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL-ul website-ului nu este valid.';
        }
        
        return $errors;
    }
    
    /**
     * Handle file upload
     */
    private function handleFileUpload($fieldName, $folder) {
        if (empty($_FILES[$fieldName]['name'])) {
            return null;
        }
        
        $file = $_FILES[$fieldName];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        $uploadDir = __DIR__ . '/../../../../uploads/marketplace/' . $folder . '/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/marketplace/' . $folder . '/' . $filename;
        }
        
        return null;
    }
}
