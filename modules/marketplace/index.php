<?php
/**
 * Marketplace Module - Main Router
 * 
 * Handles all marketplace requests and routes to appropriate controllers
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../core/Auth.php';

    // Authentication check
    $auth = Auth::getInstance();
    if (!$auth->check()) {
        header('Location: ' . BASE_URL . 'modules/auth/index.php?action=login');
        exit;
    }

    $user = $auth->user();
} catch (Exception $e) {
    die('Initialization error: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine());
}

// Get action parameter
$action = $_GET['action'] ?? 'index';

// Simple routing based on action
try {
    switch ($action) {
        // Public routes
        case 'index':
        case 'browse':
            require_once __DIR__ . '/controllers/MarketplaceController.php';
            $controller = new MarketplaceController();
            $controller->index();
            break;
        
    case 'featured':
        require_once __DIR__ . '/controllers/MarketplaceController.php';
        $controller = new MarketplaceController();
        $controller->featured();
        break;
        
    case 'product':
        require_once __DIR__ . '/controllers/ProductController.php';
        $controller = new ProductController();
        $controller->show();
        break;
        
    case 'cart':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController();
        $controller->index();
        break;
        
    case 'cart-add':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController();
        $controller->add();
        break;
        
    case 'cart-update':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController();
        $controller->update();
        break;
        
    case 'cart-remove':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController();
        $controller->remove();
        break;
        
    case 'cart-clear':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController();
        $controller->clear();
        break;
        
    case 'checkout':
        require_once __DIR__ . '/controllers/CheckoutController.php';
        $controller = new CheckoutController();
        $controller->index();
        break;
        
    case 'checkout-process':
        require_once __DIR__ . '/controllers/CheckoutController.php';
        $controller = new CheckoutController();
        $controller->process();
        break;
        
    case 'order-confirmation':
        require_once __DIR__ . '/controllers/CheckoutController.php';
        $controller = new CheckoutController();
        $controller->confirmation();
        break;
        
    case 'orders':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController();
        $controller->index();
        break;
        
    case 'order':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController();
        $controller->show();
        break;
        
    // Admin routes (SuperAdmin only)
    case 'admin':
    case 'admin-dashboard':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
        
    case 'admin-products':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->index();
        break;
        
    case 'admin-product-create':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->create();
        break;
        
    case 'admin-product-store':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->store();
        break;
        
    case 'admin-product-edit':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->edit();
        break;
        
    case 'admin-product-update':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->update();
        break;
        
    case 'admin-product-delete':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->delete();
        break;
        
    case 'admin-orders':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->index();
        break;
        
    case 'admin-order':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->show();
        break;
        
    case 'admin-order-update-status':
        if ($user->role !== 'superadmin') {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->updateStatus();
        break;
        
    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
    }
} catch (Exception $e) {
    die('Controller error: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine() . '<br>Trace: <pre>' . $e->getTraceAsString() . '</pre>');
}
