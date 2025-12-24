<?php
require_once __DIR__ . '/../../../../core/Controller.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/OrderItem.php';
require_once __DIR__ . '/../../.././../core/Mailer.php';

/**
 * Order Admin Controller - Order management
 */
class OrderAdminController extends Controller {
    private $orderModel;
    private $orderItemModel;
    
    public function __construct() {
        parent::__construct();
        
        // Check if user is SuperAdmin
        $user = Auth::getInstance()->user();
        $isSuperAdmin = (isset($user->role_slug) && $user->role_slug === 'superadmin') 
                     || (isset($user->role) && $user->role === 'superadmin');
        if (!$user || !$isSuperAdmin) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }
    
    /**
     * List all orders
     */
    public function index() {
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $filters = [
            'status' => $status,
            'search' => $search
        ];
        
        $orders = $this->orderModel->getAll($filters, $perPage, $offset);
        $total = $this->orderModel->count($filters);
        $totalPages = ceil($total / $perPage);
        
        $this->render('admin/orders', [
            'orders' => $orders,
            'currentStatus' => $status,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'pageTitle' => 'Administrare Comenzi'
        ]);
    }
    
    /**
     * Show order details
     */
    public function show() {
        $orderId = (int)($_GET['id'] ?? 0);
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Comandă negăsită';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-orders');
            exit;
        }
        
        $items = $this->orderItemModel->getByOrderId($orderId);
        
        $this->render('admin/order-detail', [
            'order' => $order,
            'items' => $items,
            'pageTitle' => 'Comandă #' . $order['order_number']
        ]);
    }
    
    /**
     * Update order status
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-orders');
            exit;
        }
        
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Comandă negăsită';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-orders');
            exit;
        }
        
        // Update status
        $result = $this->orderModel->updateStatus($orderId, $status, $adminNotes);
        
        if ($result) {
            // Send notification email
            $this->sendStatusUpdateEmail($order, $status);
            
            $_SESSION['success'] = 'Status actualizat cu succes';
        } else {
            $_SESSION['error'] = 'Eroare la actualizare status';
        }
        
        header('Location: ' . BASE_URL . 'modules/marketplace/?action=admin-order&id=' . $orderId);
        exit;
    }
    
    /**
     * Send status update email to customer
     */
    private function sendStatusUpdateEmail($order, $newStatus) {
        try {
            $mailer = new Mailer();
            
            $statusTexts = [
                'pending' => 'În așteptare',
                'confirmed' => 'Confirmată',
                'processing' => 'În procesare',
                'completed' => 'Finalizată',
                'cancelled' => 'Anulată'
            ];
            
            $statusText = $statusTexts[$newStatus] ?? $newStatus;
            
            $subject = 'Actualizare Comandă #' . $order['order_number'];
            
            $body = '<h2>Actualizare Status Comandă</h2>';
            $body .= '<p>Bună ' . htmlspecialchars($order['user_name']) . ',</p>';
            $body .= '<p>Statusul comenzii tale <strong>#' . htmlspecialchars($order['order_number']) . '</strong> a fost actualizat la: <strong>' . $statusText . '</strong></p>';
            
            if ($newStatus === 'completed') {
                $body .= '<p>Comanda ta a fost finalizată. Mulțumim pentru achiziție!</p>';
            } elseif ($newStatus === 'cancelled') {
                $body .= '<p>Comanda ta a fost anulată. Pentru detalii, te rugăm să ne contactezi.</p>';
            }
            
            $body .= '<p>Poți vedea detaliile comenzii în contul tău.</p>';
            $body .= '<p>Cu stimă,<br>Echipa Fleet Management</p>';
            
            $mailer->send(
                $order['user_email'],
                $order['user_name'],
                $subject,
                $body
            );
            
        } catch (Exception $e) {
            error_log('Email error: ' . $e->getMessage());
        }
    }
}
