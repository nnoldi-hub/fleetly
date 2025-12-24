<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../../../core/Mailer.php';

/**
 * Checkout Controller - Order placement
 */
class CheckoutController extends Controller {
    private $cartModel;
    private $orderModel;
    private $orderItemModel;
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->productModel = new Product();
    }
    
    /**
     * Show checkout page
     */
    public function index() {
        $user = $this->auth->user();
        $items = $this->cartModel->getItems($user->company_id, $user->id);
        
        // Check if cart is empty
        if (empty($items)) {
            $_SESSION['error'] = 'Coșul tău este gol';
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        // Validate cart
        $issues = $this->cartModel->validateCart($user->company_id, $user->id);
        
        if (!empty($issues)) {
            $_SESSION['warning'] = 'Verifică produsele din coș - unele s-au modificat';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=cart');
            exit;
        }
        
        $summary = $this->cartModel->getSummary($user->company_id, $user->id);
        
        // Calculate tax (TVA 19%)
        $taxRate = 19.00;
        $taxAmount = $summary['subtotal'] * ($taxRate / 100);
        $total = $summary['subtotal'] + $taxAmount;
        
        $this->render('checkout', [
            'items' => $items,
            'summary' => $summary,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'pageTitle' => 'Finalizare Comandă'
        ]);
    }
    
    /**
     * Process checkout and create order
     */
    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=checkout');
            exit;
        }
        
        $user = $this->auth->user();
        $items = $this->cartModel->getItems($user->company_id, $user->id);
        
        // Validate cart
        if (empty($items)) {
            $_SESSION['error'] = 'Coșul este gol';
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        $issues = $this->cartModel->validateCart($user->company_id, $user->id);
        
        if (!empty($issues)) {
            $_SESSION['error'] = 'Verifică produsele din coș - unele s-au modificat';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=cart');
            exit;
        }
        
        // Get form data
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryNotes = trim($_POST['delivery_notes'] ?? '');
        
        // Calculate totals
        $summary = $this->cartModel->getSummary($user->company_id, $user->id);
        $taxRate = 19.00;
        $taxAmount = $summary['subtotal'] * ($taxRate / 100);
        $total = $summary['subtotal'] + $taxAmount;
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Create order
            $orderData = [
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $summary['subtotal'],
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'delivery_address' => $deliveryAddress,
                'delivery_notes' => $deliveryNotes
            ];
            
            $orderId = $this->orderModel->create($orderData);
            
            if (!$orderId) {
                throw new Exception('Eroare la creare comandă');
            }
            
            // Get order with order_number
            $order = $this->orderModel->getById($orderId);
            
            // Create order items from cart
            $cartItemsForOrder = [];
            foreach ($items as $item) {
                $product = $this->productModel->getById($item['product_id']);
                $cartItemsForOrder[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product['name'],
                    'sku' => $product['sku'],
                    'description' => $product['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            
            $itemsCreated = $this->orderItemModel->createFromCart($orderId, $cartItemsForOrder);
            
            if (!$itemsCreated) {
                throw new Exception('Eroare la salvare produse comandă');
            }
            
            // Clear cart
            $this->cartModel->clearCart($user->company_id, $user->id);
            
            // Commit transaction
            $this->db->commit();
            
            // Send confirmation emails
            $this->sendOrderEmails($order, $cartItemsForOrder);
            
            // Redirect to confirmation page
            $_SESSION['success'] = 'Comanda ta a fost plasată cu succes!';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=order-confirmation&order=' . $order['order_number']);
            exit;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            
            error_log('Checkout error: ' . $e->getMessage());
            $_SESSION['error'] = 'Eroare la plasare comandă. Te rugăm încearcă din nou.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=checkout');
            exit;
        }
    }
    
    /**
     * Send order confirmation emails
     */
    private function sendOrderEmails($order, $items) {
        try {
            $mailer = new Mailer();
            
            // Email to customer
            $customerSubject = 'Confirmare Comandă #' . $order['order_number'];
            $customerBody = $this->renderOrderEmail($order, $items, 'customer');
            
            $mailer->send(
                $order['user_email'],
                $order['user_name'],
                $customerSubject,
                $customerBody
            );
            
            // Email to admin (SuperAdmin)
            $adminEmail = 'admin@' . $_SERVER['HTTP_HOST'];
            $adminSubject = 'Comandă Nouă Marketplace #' . $order['order_number'];
            $adminBody = $this->renderOrderEmail($order, $items, 'admin');
            
            $mailer->send(
                $adminEmail,
                'Administrator',
                $adminSubject,
                $adminBody
            );
            
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render order email template
     */
    private function renderOrderEmail($order, $items, $recipient = 'customer') {
        ob_start();
        ?>
        <h2>Comandă <?= $recipient === 'customer' ? 'Plasată' : 'Nouă' ?> #<?= htmlspecialchars($order['order_number']) ?></h2>
        
        <?php if ($recipient === 'customer'): ?>
            <p>Bună <?= htmlspecialchars($order['user_name']) ?>,</p>
            <p>Mulțumim pentru comandă! Am primit comanda ta și o vom procesa în cel mai scurt timp.</p>
        <?php else: ?>
            <p>Comandă nouă primită în marketplace de la <strong><?= htmlspecialchars($order['company_name']) ?></strong></p>
        <?php endif; ?>
        
        <h3>Detalii Comandă</h3>
        <ul>
            <li><strong>Număr Comandă:</strong> <?= htmlspecialchars($order['order_number']) ?></li>
            <li><strong>Data:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></li>
            <li><strong>Status:</strong> În așteptare</li>
            <?php if ($recipient === 'admin'): ?>
                <li><strong>Companie:</strong> <?= htmlspecialchars($order['company_name']) ?></li>
                <li><strong>Utilizator:</strong> <?= htmlspecialchars($order['user_name']) ?> (<?= htmlspecialchars($order['user_email']) ?>)</li>
            <?php endif; ?>
        </ul>
        
        <h3>Produse</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Produs</th>
                    <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Cantitate</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Preț Unitar</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td style="padding: 10px; text-align: center; border: 1px solid #ddd;"><?= $item['quantity'] ?></td>
                        <td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?= number_format($item['price'], 2) ?> RON</td>
                        <td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?= number_format($item['quantity'] * $item['price'], 2) ?> RON</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong>Subtotal:</strong></td>
                    <td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong><?= number_format($order['subtotal'], 2) ?> RON</strong></td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;">TVA (<?= $order['tax_rate'] ?>%):</td>
                    <td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?= number_format($order['tax_amount'], 2) ?> RON</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong>TOTAL:</strong></td>
                    <td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong><?= number_format($order['total'], 2) ?> RON</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if (!empty($order['delivery_address'])): ?>
            <h3>Adresă Livrare</h3>
            <p><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($order['delivery_notes'])): ?>
            <h3>Observații</h3>
            <p><?= nl2br(htmlspecialchars($order['delivery_notes'])) ?></p>
        <?php endif; ?>
        
        <?php if ($recipient === 'customer'): ?>
            <p>Vei primi un email de confirmare când comanda ta va fi procesată.</p>
            <p>Pentru orice întrebări, te rugăm să ne contactezi.</p>
        <?php endif; ?>
        
        <p>Cu stimă,<br>Echipa Fleet Management</p>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Order confirmation page
     */
    public function confirmation() {
        $orderNumber = $_GET['order'] ?? '';
        
        if (empty($orderNumber)) {
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        $user = $this->auth->user();
        $order = $this->orderModel->getByOrderNumber($orderNumber, $user->company_id);
        
        if (!$order) {
            $_SESSION['error'] = 'Comanda nu a fost găsită';
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        $items = $this->orderItemModel->getByOrderId($order['id']);
        
        $this->render('order-confirmation', [
            'order' => $order,
            'items' => $items,
            'pageTitle' => 'Confirmare Comandă'
        ]);
    }
}
