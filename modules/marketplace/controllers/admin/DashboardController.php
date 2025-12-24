<?php
require_once __DIR__ . '/../../../../core/Controller.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Product.php';

/**
 * Admin Dashboard Controller - Marketplace overview
 */
class DashboardController extends Controller {
    private $orderModel;
    private $productModel;
    
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
    }
    
    /**
     * Admin dashboard
     */
    public function index() {
        // Get statistics
        $stats = $this->orderModel->getStatistics();
        
        // Get recent orders
        $recentOrders = $this->orderModel->getAll([], 10, 0);
        
        // Get product count
        $productCount = $this->productModel->count([]);
        
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'productCount' => $productCount,
            'pageTitle' => 'Marketplace Admin Dashboard'
        ]);
    }
}
