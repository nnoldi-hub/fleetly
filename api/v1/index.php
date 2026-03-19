<?php
/**
 * API v1 Entry Point
 * 
 * Toate request-urile către /api/v1/* sunt rutate prin acest fișier.
 * 
 * @version 1.0.0
 * @since 2026-03-18
 */

// Error reporting pentru development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Setare timezone
date_default_timezone_set('Europe/Bucharest');

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// CORS Headers - Allow mobile apps and web
$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
    'https://fleetly.ro',
    'http://fleetly.ro',
    'capacitor://localhost',
    'ionic://localhost'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Mobile apps (Flutter, etc.) send no Origin header - allow them
// Also allow listed origins
if (empty($origin) || in_array($origin, $allowedOrigins)) {
    // For mobile apps with no origin, use * to allow
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
} else {
    // Allow all for mobile app compatibility
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Define base paths
define('API_ROOT', __DIR__);
define('APP_ROOT', dirname(dirname(__DIR__)));

// Autoload
require_once APP_ROOT . '/vendor/autoload.php';

// Load core files
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/core/Database.php';

// Load API core
require_once API_ROOT . '/core/ApiResponse.php';
require_once API_ROOT . '/core/JwtHandler.php';
require_once API_ROOT . '/core/ApiRouter.php';
require_once API_ROOT . '/middleware/AuthMiddleware.php';

// Load API controllers
require_once API_ROOT . '/controllers/AuthController.php';
require_once API_ROOT . '/controllers/VehicleController.php';
require_once API_ROOT . '/controllers/DriverController.php';
require_once API_ROOT . '/controllers/DashboardController.php';
require_once API_ROOT . '/controllers/DocumentController.php';
require_once API_ROOT . '/controllers/MaintenanceController.php';
require_once API_ROOT . '/controllers/FuelController.php';
require_once API_ROOT . '/controllers/InsuranceController.php';
require_once API_ROOT . '/controllers/NotificationController.php';

// Initialize router
$router = new ApiRouter();

// ============================================
// ROUTES DEFINITION
// ============================================

// --- Auth Routes (Public) ---
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/refresh', 'AuthController@refresh');
$router->post('/auth/logout', 'AuthController@logout');
$router->post('/auth/forgot-password', 'AuthController@forgotPassword');

// --- Protected Routes (require JWT) ---
$router->group(['middleware' => 'auth'], function($router) {
    
    // User profile
    $router->get('/auth/me', 'AuthController@me');
    $router->put('/auth/profile', 'AuthController@updateProfile');
    $router->put('/auth/password', 'AuthController@changePassword');
    
    // Dashboard
    $router->get('/dashboard/stats', 'DashboardController@stats');
    $router->get('/dashboard/alerts', 'DashboardController@alerts');
    
    // Vehicles
    $router->get('/vehicles', 'VehicleController@index');
    $router->get('/vehicles/{id}', 'VehicleController@show');
    $router->post('/vehicles', 'VehicleController@store');
    $router->put('/vehicles/{id}', 'VehicleController@update');
    $router->delete('/vehicles/{id}', 'VehicleController@destroy');
    $router->get('/vehicles/{id}/documents', 'VehicleController@documents');
    $router->get('/vehicles/{id}/maintenance', 'VehicleController@maintenance');
    $router->post('/vehicles/{id}/mileage', 'VehicleController@updateMileage');
    
    // Drivers
    $router->get('/drivers', 'DriverController@index');
    $router->get('/drivers/{id}', 'DriverController@show');
    $router->post('/drivers', 'DriverController@store');
    $router->put('/drivers/{id}', 'DriverController@update');
    $router->delete('/drivers/{id}', 'DriverController@destroy');
    $router->get('/drivers/{id}/documents', 'DriverController@documents');
    
    // Documents
    $router->get('/documents', 'DocumentController@index');
    $router->get('/documents/expiring', 'DocumentController@expiring');
    $router->get('/documents/{id}', 'DocumentController@show');
    $router->post('/documents', 'DocumentController@store');
    $router->put('/documents/{id}', 'DocumentController@update');
    $router->delete('/documents/{id}', 'DocumentController@destroy');
    
    // Maintenance
    $router->get('/maintenance', 'MaintenanceController@index');
    $router->get('/maintenance/scheduled', 'MaintenanceController@scheduled');
    $router->get('/maintenance/{id}', 'MaintenanceController@show');
    $router->post('/maintenance', 'MaintenanceController@store');
    $router->put('/maintenance/{id}', 'MaintenanceController@update');
    $router->delete('/maintenance/{id}', 'MaintenanceController@destroy');
    
    // Fuel
    $router->get('/fuel', 'FuelController@index');
    $router->get('/fuel/consumption/{vehicle_id}', 'FuelController@consumption');
    $router->get('/fuel/{id}', 'FuelController@show');
    $router->post('/fuel', 'FuelController@store');
    $router->put('/fuel/{id}', 'FuelController@update');
    $router->delete('/fuel/{id}', 'FuelController@destroy');
    
    // Insurance
    $router->get('/insurance', 'InsuranceController@index');
    $router->get('/insurance/expiring', 'InsuranceController@expiring');
    $router->get('/insurance/{id}', 'InsuranceController@show');
    $router->post('/insurance', 'InsuranceController@store');
    $router->put('/insurance/{id}', 'InsuranceController@update');
    $router->delete('/insurance/{id}', 'InsuranceController@destroy');
    
    // Notifications
    $router->get('/notifications', 'NotificationController@index');
    $router->get('/notifications/unread-count', 'NotificationController@unreadCount');
    $router->post('/notifications/{id}/read', 'NotificationController@markRead');
    $router->post('/notifications/read-all', 'NotificationController@markAllRead');
    $router->delete('/notifications/{id}', 'NotificationController@destroy');
    $router->post('/notifications/register-device', 'NotificationController@registerDevice');
    $router->delete('/notifications/unregister-device', 'NotificationController@unregisterDevice');
});

// ============================================
// DISPATCH REQUEST
// ============================================

try {
    // Get request path relative to /api/v1
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Detect base path dynamically (handles /fleet-management/api/v1 or /api/v1)
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = $scriptPath; // Will be /fleet-management/api/v1 or /api/v1
    
    // Remove query string
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // Remove base path
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    
    // Ensure path starts with /
    if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
    }
    
    // Get HTTP method
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Dispatch
    $router->dispatch($method, $path);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    ApiResponse::error(
        'Internal server error',
        500,
        ['debug' => $e->getMessage()] // Remove in production
    );
}
