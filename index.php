<?php
// index.php - Punctul de intrare în aplicație
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Filtru global: elimină diacriticele românești din tot output-ul (afişare fără diacritice)
if (!function_exists('fm_transliterate_ro')) {
    function fm_transliterate_ro($text) {
        if (!is_string($text) || $text === '') return $text;
        static $map = [
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T',
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t'
        ];
        // Skip fast if no ro diacritics present
        if (!preg_match('/[ĂÂÎȘŞȚŢăâîșşțţ]/u', $text)) { return $text; }
        return strtr($text, $map);
    }
}
if (!function_exists('fm_output_no_diacritics')) {
    function fm_output_no_diacritics($buffer) {
        // Option to bypass via query for debugging: ?keep_diacritics=1
        if (!empty($_GET['keep_diacritics'])) { return $buffer; }
        $out = fm_transliterate_ro($buffer);
        // Curăță artefactele de encodare tip "??" rezultate din text sursă deja degradat
        // Eliminăm doar dublele consecutive în contexte de text, nu semnul întrebării singular.
        $out = preg_replace('/([A-Za-z])\?\?/', '$1', $out);
        $out = preg_replace('/\?\?([\s<])/', '$1', $out);
        return $out;
    }
}

// Pornește output buffering cu filtrul de transliterare
ob_start('fm_output_no_diacritics');

// Include fișierele de configurare
require_once 'config/config.php';
require_once 'config/database.php';

// Include clasele de bază
require_once 'core/Database.php';
require_once 'core/Model.php';
require_once 'core/Controller.php';
require_once 'core/Router.php';
require_once 'core/Auth.php';
require_once 'core/User.php';
require_once 'core/Company.php';
require_once 'core/PublicStats.php';

// Include toate modelele
$modelFiles = glob('modules/*/models/*.php');
foreach ($modelFiles as $file) {
    require_once $file;
}

// Include toate controllerele
$controllerFiles = glob('modules/*/controllers/*.php');
foreach ($controllerFiles as $file) {
    require_once $file;
}

// Inițializare router
$router = new Router();

// Rute publice (landing page)
$router->addRoute('GET', '/home', 'LandingController', 'index');
$router->addRoute('GET', '/contact', 'LandingController', 'contact');
$router->addRoute('POST', '/contact/submit', 'LandingController', 'submitContact');

// Rute autentificare
$router->addRoute('GET', '/login', 'LoginController', 'index');
$router->addRoute('POST', '/login', 'LoginController', 'login');
$router->addRoute('GET', '/logout', 'LoginController', 'logout');
$router->addRoute('GET', '/forgot-password', 'LoginController', 'forgotPassword');

// Definire rute pentru modulul vehicule
// Root path - redirectionare inteligenta (landing pentru vizitatori, dashboard pentru utilizatori autentificati)
$router->addRoute('GET', '/', 'HomeController', 'index');
$router->addRoute('GET', '/dashboard', 'DashboardController', 'index');

// Rute vehicule
$router->addRoute('GET', '/vehicles', 'VehicleController', 'index');
$router->addRoute('GET', '/vehicles/add', 'VehicleController', 'add');
$router->addRoute('POST', '/vehicles/add', 'VehicleController', 'add');
$router->addRoute('GET', '/vehicles/edit', 'VehicleController', 'edit');
$router->addRoute('POST', '/vehicles/edit', 'VehicleController', 'edit');
$router->addRoute('GET', '/vehicles/view', 'VehicleController', 'view');
$router->addRoute('GET', '/vehicles/dashboard', 'VehicleController', 'dashboard');
$router->addRoute('GET', '/vehicles/delete', 'VehicleController', 'delete');
$router->addRoute('POST', '/vehicles/delete', 'VehicleController', 'delete');
$router->addRoute('POST', '/vehicles/updateMileage', 'VehicleController', 'updateMileage');
$router->addRoute('GET', '/vehicles/export', 'VehicleController', 'export');
$router->addRoute('POST', '/vehicles/store', 'VehicleController', 'store');

// Rute tipuri vehicule
$router->addRoute('GET', '/vehicle-types', 'VehicleTypeController', 'index');
$router->addRoute('GET', '/vehicle-types/add', 'VehicleTypeController', 'add');
$router->addRoute('POST', '/vehicle-types/add', 'VehicleTypeController', 'add');
$router->addRoute('GET', '/vehicle-types/edit', 'VehicleTypeController', 'edit');
$router->addRoute('POST', '/vehicle-types/edit', 'VehicleTypeController', 'edit');
$router->addRoute('POST', '/vehicle-types/delete', 'VehicleTypeController', 'delete');

// Rute documente
$router->addRoute('GET', '/documents', 'DocumentController', 'index');
$router->addRoute('GET', '/documents/add', 'DocumentController', 'add');
$router->addRoute('POST', '/documents/add', 'DocumentController', 'add');
$router->addRoute('GET', '/documents/edit', 'DocumentController', 'edit');
$router->addRoute('POST', '/documents/edit', 'DocumentController', 'edit');
$router->addRoute('GET', '/documents/expiring', 'DocumentController', 'expiring');
$router->addRoute('POST', '/documents/delete', 'DocumentController', 'delete');
// Export route for documents
$router->addRoute('GET', '/documents/export', 'DocumentController', 'export');
// View document (lipsă anterior)
$router->addRoute('GET', '/documents/view', 'DocumentController', 'view');

// Rute asigurări
$router->addRoute('GET', '/insurance', 'InsuranceController', 'index');
$router->addRoute('GET', '/insurance/add', 'InsuranceController', 'add');
$router->addRoute('POST', '/insurance/add', 'InsuranceController', 'add');
$router->addRoute('GET', '/insurance/edit', 'InsuranceController', 'edit');
$router->addRoute('POST', '/insurance/edit', 'InsuranceController', 'edit');
$router->addRoute('GET', '/insurance/view', 'InsuranceController', 'view');
$router->addRoute('POST', '/insurance/delete', 'InsuranceController', 'delete');
$router->addRoute('GET', '/insurance/expiring', 'InsuranceController', 'expiring');

// Rute șoferi
$router->addRoute('GET', '/drivers', 'DriverController', 'index');
$router->addRoute('GET', '/drivers/add', 'DriverController', 'add');
$router->addRoute('POST', '/drivers/add', 'DriverController', 'add');
$router->addRoute('GET', '/drivers/edit', 'DriverController', 'edit');
$router->addRoute('POST', '/drivers/edit', 'DriverController', 'edit');
$router->addRoute('GET', '/drivers/view', 'DriverController', 'view');
$router->addRoute('POST', '/drivers/delete', 'DriverController', 'delete');

// Rute întreținere
$router->addRoute('GET', '/maintenance', 'MaintenanceController', 'index');
$router->addRoute('GET', '/maintenance/add', 'MaintenanceController', 'add');
$router->addRoute('POST', '/maintenance/add', 'MaintenanceController', 'add');
$router->addRoute('GET', '/maintenance/edit', 'MaintenanceController', 'edit');
$router->addRoute('POST', '/maintenance/edit', 'MaintenanceController', 'edit');
$router->addRoute('GET', '/maintenance/schedule', 'MaintenanceController', 'schedule');
$router->addRoute('GET', '/maintenance/history', 'MaintenanceController', 'history');
// Delete maintenance
$router->addRoute('GET', '/maintenance/delete', 'MaintenanceController', 'delete');
$router->addRoute('POST', '/maintenance/delete', 'MaintenanceController', 'delete');

// Rute combustibil
$router->addRoute('GET', '/fuel', 'FuelController', 'index');
$router->addRoute('GET', '/fuel/add', 'FuelController', 'add');
$router->addRoute('POST', '/fuel/add', 'FuelController', 'add');
$router->addRoute('GET', '/fuel/last-odometer', 'FuelController', 'getVehicleLastOdometer');
$router->addRoute('GET', '/fuel/reports', 'FuelController', 'reports');
$router->addRoute('GET', '/fuel/consumption', 'FuelController', 'consumption');

// Rute rapoarte
$router->addRoute('GET', '/reports', 'ReportController', 'index');
$router->addRoute('GET', '/reports/fleet', 'ReportController', 'fleetReport');
$router->addRoute('GET', '/reports/vehicle', 'ReportController', 'vehicleReport');
$router->addRoute('GET', '/reports/costs', 'ReportController', 'costAnalysis');
// Rapoarte suplimentare
$router->addRoute('GET', '/reports/maintenance', 'ReportController', 'maintenanceReport');
$router->addRoute('GET', '/reports/fuel', 'ReportController', 'fuelReport');
$router->addRoute('GET', '/reports/custom', 'ReportController', 'customReport');
// Endpoints pentru date (charturi) si actiuni AJAX
$router->addRoute('GET', '/reports/fleet-overview-data', 'ReportController', 'fleetOverviewData');
$router->addRoute('GET', '/reports/cost-data', 'ReportController', 'costData');
$router->addRoute('GET', '/reports/maintenance-data', 'ReportController', 'maintenanceData');
$router->addRoute('GET', '/reports/fuel-consumption-data', 'ReportController', 'fuelConsumptionData');
$router->addRoute('POST', '/reports/generate', 'ReportController', 'generateAjax');
$router->addRoute('POST', '/reports/export', 'ReportController', 'exportAjax');
// optional: export will be handled inside report-specific actions when query param export is present

// Rute SuperAdmin
$router->addRoute('GET', '/superadmin', 'SuperAdminController', 'dashboard');
$router->addRoute('GET', '/superadmin/dashboard', 'SuperAdminController', 'dashboard');
$router->addRoute('GET', '/superadmin/companies', 'SuperAdminController', 'companies');
$router->addRoute('GET', '/superadmin/companies/add', 'SuperAdminController', 'add');
$router->addRoute('POST', '/superadmin/companies/add', 'SuperAdminController', 'add');
$router->addRoute('GET', '/superadmin/companies/edit', 'SuperAdminController', 'edit');
$router->addRoute('POST', '/superadmin/companies/edit', 'SuperAdminController', 'edit');
$router->addRoute('POST', '/superadmin/companies/change-status', 'SuperAdminController', 'changeStatus');
// Reset admin account for a company
$router->addRoute('POST', '/superadmin/companies/reset-admin', 'SuperAdminController', 'resetAdmin');
// Intervention mode
$router->addRoute('GET', '/superadmin/act-as', 'SuperAdminController', 'actAs');
$router->addRoute('GET', '/superadmin/stop-acting', 'SuperAdminController', 'stopActing');

// API proxy routes (for footer notifications JSON)
$router->addRoute('GET', '/api/notifications', 'ApiController', 'notifications');

// Rute notificări
$router->addRoute('GET', '/notifications', 'NotificationController', 'index');
$router->addRoute('POST', '/notifications/dismiss', 'NotificationController', 'dismiss');
$router->addRoute('POST', '/notifications/mark-read', 'NotificationController', 'markAsRead');
$router->addRoute('POST', '/notifications/mark-all-read', 'NotificationController', 'markAllAsRead');
$router->addRoute('GET', '/notifications/unread-count', 'NotificationController', 'getUnreadCount');
$router->addRoute('POST', '/notifications/generate-system', 'NotificationController', 'generateSystemNotifications'); // legacy POST route
// Nou: rută GET dedicată pentru generare (evită 404 la query action=generate pe unele hostinguri)
$router->addRoute('GET', '/notifications/generate', 'NotificationController', 'generateSystemNotifications');
$router->addRoute('GET', '/notifications/generate-system', 'NotificationController', 'generateSystemNotifications');
$router->addRoute('GET', '/notifications/settings', 'NotificationController', 'settings');
$router->addRoute('POST', '/notifications/settings', 'NotificationController', 'settings');
// V2: User preferences
$router->addRoute('GET', '/notifications/preferences', 'NotificationController', 'preferences');
$router->addRoute('POST', '/notifications/savePreferences', 'NotificationController', 'savePreferences');
$router->addRoute('POST', '/notifications/sendTest', 'NotificationController', 'sendTest');

// Rute utilizator
$router->addRoute('GET', '/profile', 'UserController', 'profile');
$router->addRoute('POST', '/profile', 'UserController', 'saveProfile');
$router->addRoute('GET', '/settings', 'UserController', 'settings');
// Logout este deja definit la LoginController; evităm suprascrierea

// Rute management utilizatori (companie)
$router->addRoute('GET', '/users', 'UserController', 'index');
$router->addRoute('GET', '/users/add', 'UserController', 'add');
$router->addRoute('POST', '/users/add', 'UserController', 'add');
$router->addRoute('GET', '/users/edit', 'UserController', 'edit');
$router->addRoute('POST', '/users/edit', 'UserController', 'edit');
$router->addRoute('POST', '/users/delete', 'UserController', 'delete');

// Rute import CSV
$router->addRoute('GET', '/import', 'ImportController', 'index');
$router->addRoute('GET', '/import/download-vehicles-template', 'ImportController', 'downloadVehiclesTemplate');
$router->addRoute('GET', '/import/download-documents-template', 'ImportController', 'downloadDocumentsTemplate');
$router->addRoute('GET', '/import/download-drivers-template', 'ImportController', 'downloadDriversTemplate');
$router->addRoute('POST', '/import/upload-vehicles', 'ImportController', 'uploadVehicles');
$router->addRoute('POST', '/import/upload-documents', 'ImportController', 'uploadDocuments');
$router->addRoute('POST', '/import/upload-drivers', 'ImportController', 'uploadDrivers');

// Rute Service Module
$router->addRoute('GET', '/service/services', 'ServiceController', 'index');
$router->addRoute('GET', '/service/services/add', 'ServiceController', 'add');
$router->addRoute('POST', '/service/services/add', 'ServiceController', 'add');
$router->addRoute('GET', '/service/services/edit', 'ServiceController', 'edit');
$router->addRoute('POST', '/service/services/edit', 'ServiceController', 'edit');
$router->addRoute('GET', '/service/services/view', 'ServiceController', 'view');
$router->addRoute('POST', '/service/services/delete', 'ServiceController', 'delete');
$router->addRoute('GET', '/service/services/internal-setup', 'ServiceController', 'internalSetup');
$router->addRoute('POST', '/service/services/internal-setup', 'ServiceController', 'internalSetup');
// Rute Workshop (Atelier)
$router->addRoute('GET', '/service/workshop', 'WorkOrderController', 'dashboard');
$router->addRoute('GET', '/service/workshop/add', 'WorkOrderController', 'add');
$router->addRoute('POST', '/service/workshop/add', 'WorkOrderController', 'add');
$router->addRoute('GET', '/service/workshop/edit', 'WorkOrderController', 'edit');
$router->addRoute('POST', '/service/workshop/edit', 'WorkOrderController', 'edit');
$router->addRoute('GET', '/service/workshop/view', 'WorkOrderController', 'view');
$router->addRoute('POST', '/service/workshop/delete', 'WorkOrderController', 'delete');
$router->addRoute('POST', '/service/workshop/update-status', 'WorkOrderController', 'updateStatus');
$router->addRoute('GET', '/service/workshop/vehicles', 'WorkOrderController', 'vehiclesInService');

// Obține calea curentă

// Normalizează calea pentru rutare corectă (suport și pentru subdirector ex: /fleet-management)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$calcBase = rtrim(dirname($scriptName), '/'); // ex: /fleet-management sau /
$configBase = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/'); // ex: /fleet-management


$path = $requestUri;
// 1) încearcă cu baza calculată din SCRIPT_NAME
if (!empty($calcBase) && $calcBase !== '/' && strpos($requestUri, $calcBase) === 0) {
    $path = substr($requestUri, strlen($calcBase));
}
// 2) dacă nu s-a decupat, încearcă cu baza din BASE_URL
elseif (!empty($configBase) && $configBase !== '/' && strpos($requestUri, $configBase) === 0) {
    $path = substr($requestUri, strlen($configBase));
}

// 3) elimină prefixul /index.php dacă este prezent (mod_rewrite dezactivat)
if (strpos($path, '/index.php') === 0) {
    $path = substr($path, strlen('/index.php'));
}

// Patch: acceptă și varianta /index.php/drivers/edit?id=XX când path rămâne gol după decupare
if ($path === '' || $path === '/') {
    // Dacă REQUEST_URI conține index.php/drivers/edit dar path a fost golit
    $raw = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/index\.php/(drivers|vehicles|users|maintenance|fuel|documents)/#', $raw)) {
        $parts = parse_url($raw, PHP_URL_PATH);
        // Elimină orice prefix până la /index.php/
        $parts = preg_replace('#^.*?/index\.php/#','/', $parts);
        if ($parts) { $path = rtrim($parts,'/'); if ($path[0] !== '/') $path = '/'.$path; }
    }
}

// Asigură leading slash
if ($path === '' || $path[0] !== '/') { $path = '/' . ltrim($path, '/'); }
// Elimină eventuale dubluri de slash
$path = preg_replace('#/+#','/',$path);

// Rutează cererea
try {
    $router->route($_SERVER['REQUEST_METHOD'], $path);
} catch (Exception $e) {
    // Dacă nu s-a găsit nicio rută, afișează o eroare 404
    http_response_code(404);
    echo "<h1>404 - Pagina nu a fost găsită</h1>";
    echo "<p>Calea solicitată nu există.</p>";
    echo "<a href='" . BASE_URL . "'>Înapoi la pagina principală</a>";
    exit;
}
