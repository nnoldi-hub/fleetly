<?php
/**
 * Marketplace Module - Main Router
 * 
 * Handles all marketplace requests and routes to appropriate controllers
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        header('Location: ' . BASE_URL . '?action=login');
        exit;
    }

    $user = $auth->user();
} catch (Exception $e) {
    die('Initialization error: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine());
} catch (Error $e) {
    die('FATAL Initialization error: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine());
}

// Helper function to check if user is superadmin
function isSuperAdmin($user) {
    if (!$user) return false;
    // Check both role_slug (from DB join) and role (if set directly)
    return (isset($user->role_slug) && $user->role_slug === 'superadmin') 
        || (isset($user->role) && $user->role === 'superadmin');
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
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
        
    case 'admin-products':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->index();
        break;
        
    case 'admin-product-create':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->create();
        break;
        
    case 'admin-product-store':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->store();
        break;
        
    case 'admin-product-edit':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->edit();
        break;
        
    case 'admin-product-update':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->update();
        break;
        
    case 'admin-product-delete':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/CatalogAdminController.php';
        $controller = new CatalogAdminController();
        $controller->delete();
        break;
        
    case 'admin-orders':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->index();
        break;
        
    case 'admin-order':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->show();
        break;
        
    case 'admin-order-update-status':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/OrderAdminController.php';
        $controller = new OrderAdminController();
        $controller->updateStatus();
        break;
    
    // ========== PARTENERI & RECLAME - Vizualizare Utilizatori ==========
    case 'partners':
        require_once __DIR__ . '/controllers/PartnerController.php';
        $controller = new PartnerController();
        $controller->index();
        break;
        
    case 'partner-show':
        require_once __DIR__ . '/controllers/PartnerController.php';
        $controller = new PartnerController();
        $controller->show();
        break;
        
    case 'partner-redirect':
        require_once __DIR__ . '/controllers/PartnerController.php';
        $controller = new PartnerController();
        $controller->redirect();
        break;
        
    case 'partner-category':
        require_once __DIR__ . '/controllers/PartnerController.php';
        $controller = new PartnerController();
        $controller->category();
        break;
    
    // ========== PARTENERI & RECLAME - Admin (SuperAdmin Only) ==========
    case 'admin-partners':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->index();
        break;
        
    case 'admin-partner-create':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->create();
        break;
        
    case 'admin-partner-store':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->store();
        break;
        
    case 'admin-partner-edit':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->edit();
        break;
        
    case 'admin-partner-update':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->update();
        break;
        
    case 'admin-partner-delete':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->delete();
        break;
        
    case 'admin-partner-toggle-status':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->toggleStatus();
        break;
        
    case 'admin-partner-toggle-featured':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->toggleFeatured();
        break;
    
    // ========== CATEGORII PARTENERI - Admin ==========
    case 'admin-partner-categories':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->categories();
        break;
        
    case 'admin-partner-category-create':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->createCategory();
        break;
        
    case 'admin-partner-category-store':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->storeCategory();
        break;
        
    case 'admin-partner-category-edit':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->editCategory();
        break;
        
    case 'admin-partner-category-update':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->updateCategory();
        break;
        
    case 'admin-partner-category-delete':
        if (!isSuperAdmin($user)) {
            $_SESSION['error'] = 'Acces interzis';
            header('Location: ' . BASE_URL);
            exit;
        }
        require_once __DIR__ . '/controllers/admin/PartnerAdminController.php';
        $controller = new PartnerAdminController();
        $controller->deleteCategory();
        break;
        
    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
    }
} catch (Exception $e) {
    die('Controller error: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . '<br>Line: ' . $e->getLine() . '<br>Trace: <pre>' . $e->getTraceAsString() . '</pre>');
}
