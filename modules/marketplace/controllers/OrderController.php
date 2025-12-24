<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';

/**
 * Order Controller - Order history and details
 */
class OrderController extends Controller {
    private $orderModel;
    private $orderItemModel;
    
    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }
    
    /**
     * Show order history
     */
    public function index() {
        $user = $this->auth->user();
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $filters = ['status' => $status];
        
        $orders = $this->orderModel->getByCompany($user->company_id, $filters, $perPage, $offset);
        $total = $this->orderModel->count(array_merge($filters, ['company_id' => $user->company_id]));
        $totalPages = ceil($total / $perPage);
        
        $this->render('orders', [
            'orders' => $orders,
            'currentStatus' => $status,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'pageTitle' => 'Comenzile Mele'
        ]);
    }
    
    /**
     * Show order details
     */
    public function show() {
        $orderNumber = $_GET['order'] ?? '';
        
        if (empty($orderNumber)) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=orders');
            exit;
        }
        
        $user = $this->auth->user();
        $order = $this->orderModel->getByOrderNumber($orderNumber, $user->company_id);
        
        if (!$order) {
            $_SESSION['error'] = 'Comanda nu a fost găsită';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=orders');
            exit;
        }
        
        $items = $this->orderItemModel->getByOrderId($order['id']);
        
        $this->render('order-detail', [
            'order' => $order,
            'items' => $items,
            'pageTitle' => 'Comandă #' . $order['order_number']
        ]);
    }
}
