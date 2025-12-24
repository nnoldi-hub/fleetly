<?php
require_once __DIR__ . '/../../../../core/Controller.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';

/**
 * Admin Dashboard Controller - Marketplace overview
 */
class DashboardController extends Controller {
    private $orderModel;
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
        
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    /**
     * Admin dashboard
     */
    public function index() {
        // Get statistics
        $stats = $this->orderModel->getStatistics();
        
        // Add product stats
        $stats['total_products'] = $this->productModel->count([]);
        $stats['active_products'] = $this->productModel->count(['is_active' => 1]);
        
        // Get recent orders
        $recentOrders = $this->orderModel->getAll([], 10, 0);
        
        // Get category stats
        $categoryStats = $this->categoryModel->getWithProductCount();
        
        // Extract data for view
        extract([
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'categoryStats' => $categoryStats,
            'pageTitle' => 'Marketplace Admin Dashboard'
        ]);
        
        $viewFile = __DIR__ . '/../../views/admin/dashboard.php';
        include __DIR__ . '/../../../../includes/header.php';
        include $viewFile;
        include __DIR__ . '/../../../../includes/footer.php';
    }
}
