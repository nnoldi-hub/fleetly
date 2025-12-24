<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

/**
 * Product Controller - Product details
 */
class ProductController extends Controller {
    private $productModel;
    private $cartModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
    }
    
    /**
     * Show product details
     */
    public function show() {
        $slug = $_GET['slug'] ?? '';
        
        if (empty($slug)) {
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        $product = $this->productModel->getBySlug($slug);
        
        if (!$product) {
            $_SESSION['error'] = 'Produsul nu a fost găsit';
            header('Location: ' . BASE_URL . 'modules/marketplace/');
            exit;
        }
        
        // Parse JSON fields
        if (!empty($product['specifications'])) {
            $product['specifications'] = json_decode($product['specifications'], true);
        }
        
        if (!empty($product['image_gallery'])) {
            $product['image_gallery'] = json_decode($product['image_gallery'], true);
        }
        
        // Get related products
        $relatedProducts = $this->productModel->getRelated(
            $product['id'], 
            $product['category_id'], 
            4
        );
        
        // Get cart count
        $user = Auth::getInstance()->user();
        $cartCount = $this->cartModel->getItemCount($user->company_id, $user->id);
        
        $this->render('product-detail', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'cartCount' => $cartCount,
            'pageTitle' => $product['name']
        ]);
    }
}
