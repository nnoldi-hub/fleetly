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
        if (!class_exists('Auth')) {
            require_once __DIR__ . '/../../../../core/Auth.php';
        }
        $auth = Auth::getInstance();
        $user = $auth->user();
        
        if (!$user || $user->role !== 'superadmin') {
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
        try {
            // Initialize stats with defaults
            $stats = [
                'total_products' => 0,
                'active_products' => 0,
                'orders_today' => 0,
                'revenue_today' => 0,
                'orders_pending' => 0,
                'orders_month' => 0,
                'revenue_month' => 0
            ];
            
            // Get order statistics
            try {
                $orderStats = $this->orderModel->getStatistics();
                if ($orderStats) {
                    $stats = array_merge($stats, $orderStats);
                }
            } catch (Exception $e) {
                error_log("Order stats error: " . $e->getMessage());
            }
            
            // Get product stats
            try {
                $stats['total_products'] = $this->productModel->count([]);
                $stats['active_products'] = $this->productModel->count(['is_active' => 1]);
            } catch (Exception $e) {
                error_log("Product stats error: " . $e->getMessage());
            }
            
            // Get recent orders
            $recentOrders = [];
            try {
                $recentOrders = $this->orderModel->getAll([], 10, 0);
            } catch (Exception $e) {
                error_log("Recent orders error: " . $e->getMessage());
            }
            
            // Get category stats
            $categoryStats = [];
            try {
                $categoryStats = $this->categoryModel->getWithProductCount();
            } catch (Exception $e) {
                error_log("Category stats error: " . $e->getMessage());
            }
            
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
            
        } catch (Exception $e) {
            // Show error
            http_response_code(500);
            echo "<h1>Error loading dashboard</h1>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
}
