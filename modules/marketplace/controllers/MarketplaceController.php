<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Cart.php';

/**
 * Marketplace Controller - Browse products, search, filter
 */
class MarketplaceController extends Controller {
    private $productModel;
    private $categoryModel;
    private $cartModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->cartModel = new Cart();
    }
    
    /**
     * Main marketplace page - browse products
     */
    public function index() {
        try {
            $categoryId = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 12;
            $offset = ($page - 1) * $perPage;
            
            $filters = [
                'category_id' => $categoryId,
                'search' => $search
            ];
            
            $products = $this->productModel->getAll($filters, $perPage, $offset);
            $categories = $this->categoryModel->getWithProductCount();
            $total = $this->productModel->count($filters);
            $totalPages = ceil($total / $perPage);
            
            // Get cart count for navbar
            $auth = Auth::getInstance();
            $user = $auth->user();
            $cartCount = 0;
            
            if ($user && isset($user->company_id) && isset($user->id)) {
                $cartCount = $this->cartModel->getItemCount($user->company_id, $user->id);
            }
            
            $this->render('browse', [
                'products' => $products,
                'categories' => $categories,
                'currentCategory' => $categoryId,
                'search' => $search,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'cartCount' => $cartCount,
                'pageTitle' => 'Marketplace - Produse pentru Flotă'
            ]);
        } catch (Exception $e) {
            error_log('MarketplaceController::index error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Eroare: ' . htmlspecialchars($e->getMessage());
            exit;
        }
    }
    
    /**
     * Featured products homepage
     */
    public function featured() {
        $featuredProducts = $this->productModel->getFeatured(8);
        $categories = $this->categoryModel->getWithProductCount();
        
        $user = Auth::getInstance()->user();
        $cartCount = $this->cartModel->getItemCount($user->company_id, $user->id);
        
        $this->render('featured', [
            'products' => $featuredProducts,
            'categories' => $categories,
            'cartCount' => $cartCount,
            'pageTitle' => 'Marketplace - Produse Recomandate'
        ]);
    }
}
