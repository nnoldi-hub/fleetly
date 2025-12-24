<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

/**
 * Cart Controller - Shopping cart operations
 */
class CartController extends Controller {
    private $cartModel;
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    /**
     * Show cart page
     */
    public function index() {
        $user = $this->auth->user();
        $items = $this->cartModel->getItems($user->company_id, $user->id);
        $summary = $this->cartModel->getSummary($user->company_id, $user->id);
        
        // Validate cart (check for inactive products or price changes)
        $issues = $this->cartModel->validateCart($user->company_id, $user->id);
        
        $this->render('cart', [
            'items' => $items,
            'summary' => $summary,
            'issues' => $issues,
            'pageTitle' => 'Coș de Cumpărături'
        ]);
    }
    
    /**
     * Add product to cart (AJAX)
     */
    public function add() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $user = $this->auth->user();
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        
        // Validate product
        $product = $this->productModel->getById($productId);
        
        if (!$product || !$product['is_active']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produsul nu este disponibil']);
            exit;
        }
        
        // Add to cart
        $result = $this->cartModel->addItem(
            $user->company_id,
            $user->id,
            $productId,
            $quantity,
            $product['price']
        );
        
        if ($result) {
            $cartCount = $this->cartModel->getItemCount($user->company_id, $user->id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Produs adăugat în coș',
                'cart_count' => $cartCount
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Eroare la adăugare în coș']);
        }
        
        exit;
    }
    
    /**
     * Update cart item quantity (AJAX)
     */
    public function update() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $user = $this->auth->user();
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        $result = $this->cartModel->updateQuantity(
            $cartItemId,
            $user->company_id,
            $user->id,
            $quantity
        );
        
        if ($result) {
            $summary = $this->cartModel->getSummary($user->company_id, $user->id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cantitate actualizată',
                'summary' => $summary
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Eroare la actualizare']);
        }
        
        exit;
    }
    
    /**
     * Remove item from cart (AJAX)
     */
    public function remove() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $user = $this->auth->user();
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        
        $result = $this->cartModel->removeItem(
            $cartItemId,
            $user->company_id,
            $user->id
        );
        
        if ($result) {
            $summary = $this->cartModel->getSummary($user->company_id, $user->id);
            $cartCount = $this->cartModel->getItemCount($user->company_id, $user->id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Produs șters din coș',
                'summary' => $summary,
                'cart_count' => $cartCount
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Eroare la ștergere']);
        }
        
        exit;
    }
    
    /**
     * Clear entire cart
     */
    public function clear() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        $user = $this->auth->user();
        $this->cartModel->clearCart($user->company_id, $user->id);
        
        $_SESSION['success'] = 'Coșul a fost golit';
        header('Location: ' . BASE_URL . 'modules/marketplace/');
        exit;
    }
}
