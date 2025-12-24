<?php
require_once __DIR__ . '/../../../../core/Controller.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';

/**
 * Catalog Admin Controller - Product management
 */
class CatalogAdminController extends Controller {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        parent::__construct();
        
        // Check if user is SuperAdmin
        $user = $this->auth->user();
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    /**
     * List all products
     */
    public function index() {
        $categoryId = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $filters = [
            'category_id' => $categoryId,
            'search' => $search
        ];
        
        $products = $this->productModel->getAll($filters, $perPage, $offset);
        $categories = $this->categoryModel->getAll(false);
        $total = $this->productModel->count($filters);
        $totalPages = ceil($total / $perPage);
        
        $this->render('admin/products', [
            'products' => $products,
            'categories' => $categories,
            'currentCategory' => $categoryId,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'pageTitle' => 'Administrare Produse'
        ]);
    }
    
    /**
     * Show create product form
     */
    public function create() {
        $categories = $this->categoryModel->getAll(false);
        
        $this->render('admin/product-form', [
            'product' => null,
            'categories' => $categories,
            'pageTitle' => 'Adaugă Produs Nou'
        ]);
    }
    
    /**
     * Store new product
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
            exit;
        }
        
        $data = $this->getProductDataFromPost();
        
        // Validate
        $errors = $this->validateProductData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-product-create');
            exit;
        }
        
        // Generate slug
        $data['slug'] = $this->productModel->generateSlug($data['name']);
        
        // Handle image upload
        if (!empty($_FILES['image_main']['name'])) {
            $uploadResult = $this->handleImageUpload($_FILES['image_main']);
            if ($uploadResult['success']) {
                $data['image_main'] = $uploadResult['path'];
            }
        }
        
        // Create product
        $productId = $this->productModel->create($data);
        
        if ($productId) {
            $_SESSION['success'] = 'Produs adăugat cu succes';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
        } else {
            $_SESSION['error'] = 'Eroare la adăugare produs';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-product-create');
        }
        
        exit;
    }
    
    /**
     * Show edit product form
     */
    public function edit() {
        $productId = (int)($_GET['id'] ?? 0);
        
        $product = $this->productModel->getById($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produs negăsit';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
            exit;
        }
        
        // Parse JSON fields
        if (!empty($product['specifications'])) {
            $product['specifications'] = json_decode($product['specifications'], true);
        }
        
        $categories = $this->categoryModel->getAll(false);
        
        $this->render('admin/product-form', [
            'product' => $product,
            'categories' => $categories,
            'pageTitle' => 'Editează Produs'
        ]);
    }
    
    /**
     * Update product
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
            exit;
        }
        
        $productId = (int)($_POST['product_id'] ?? 0);
        
        $data = $this->getProductDataFromPost();
        
        // Validate
        $errors = $this->validateProductData($data, $productId);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-product-edit&id=' . $productId);
            exit;
        }
        
        // Regenerate slug if name changed
        $existing = $this->productModel->getById($productId);
        if ($existing['name'] !== $data['name']) {
            $data['slug'] = $this->productModel->generateSlug($data['name'], $productId);
        }
        
        // Handle image upload
        if (!empty($_FILES['image_main']['name'])) {
            $uploadResult = $this->handleImageUpload($_FILES['image_main']);
            if ($uploadResult['success']) {
                $data['image_main'] = $uploadResult['path'];
                
                // Delete old image
                if (!empty($existing['image_main']) && file_exists(__DIR__ . '/../../../../' . $existing['image_main'])) {
                    unlink(__DIR__ . '/../../../../' . $existing['image_main']);
                }
            }
        }
        
        // Update product
        $result = $this->productModel->update($productId, $data);
        
        if ($result) {
            $_SESSION['success'] = 'Produs actualizat cu succes';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
        } else {
            $_SESSION['error'] = 'Eroare la actualizare produs';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-product-edit&id=' . $productId);
        }
        
        exit;
    }
    
    /**
     * Delete product
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
            exit;
        }
        
        $productId = (int)($_POST['product_id'] ?? 0);
        
        $result = $this->productModel->delete($productId);
        
        if ($result) {
            $_SESSION['success'] = 'Produs șters cu succes';
        } else {
            $_SESSION['error'] = 'Eroare la ștergere produs';
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-products');
        exit;
    }
    
    /**
     * Get product data from POST
     */
    private function getProductDataFromPost() {
        $data = [
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'currency' => trim($_POST['currency'] ?? 'RON'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];
        
        // Handle specifications (convert to JSON)
        if (!empty($_POST['specifications'])) {
            $specs = [];
            if (!empty($_POST['spec_key']) && is_array($_POST['spec_key'])) {
                foreach ($_POST['spec_key'] as $index => $key) {
                    $value = $_POST['spec_value'][$index] ?? '';
                    if (!empty($key) && !empty($value)) {
                        $specs[$key] = $value;
                    }
                }
            }
            $data['specifications'] = !empty($specs) ? json_encode($specs) : null;
        }
        
        return $data;
    }
    
    /**
     * Validate product data
     */
    private function validateProductData($data, $existingId = null) {
        $errors = [];
        
        if (empty($data['category_id'])) {
            $errors[] = 'Selectează o categorie';
        }
        
        if (empty($data['sku'])) {
            $errors[] = 'SKU este obligatoriu';
        }
        
        if (empty($data['name'])) {
            $errors[] = 'Numele produsului este obligatoriu';
        }
        
        if ($data['price'] <= 0) {
            $errors[] = 'Prețul trebuie să fie mai mare ca 0';
        }
        
        // Check SKU uniqueness
        if (!empty($data['sku'])) {
            $sql = "SELECT id FROM mp_products WHERE sku = ?";
            $params = [$data['sku']];
            
            if ($existingId) {
                $sql .= " AND id != ?";
                $params[] = $existingId;
            }
            
            $existing = $this->db->fetch($sql, $params);
            if ($existing) {
                $errors[] = 'SKU-ul există deja';
            }
        }
        
        return $errors;
    }
    
    /**
     * Handle image upload
     */
    private function handleImageUpload($file) {
        $uploadDir = __DIR__ . '/../../../../uploads/marketplace/products/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Tip fișier invalid'];
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fișier prea mare (max 5MB)'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true,
                'path' => 'uploads/marketplace/products/' . $filename
            ];
        } else {
            return ['success' => false, 'error' => 'Eroare la upload'];
        }
    }
}
