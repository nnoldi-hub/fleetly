<?php
/**
 * config/routes.php
 *
 * Scop: centralizarea DEFINI?IILOR DE RUTE �ntr-un singur loc.
 * Acum acest fi?ier ESTE utilizabil daca �l incluzi dupa ce instan?iezi $router �n index.php:
 *    require_once 'config/routes.php';
 *
 * IMPORTANT: Clasa Router are metoda addRoute($method, $path, $controller, $action)
 *            NU exista $router->add(). Versiunea veche era gre?ita ?i cauza confuzii.
 *
 * Recomandare: men?ine acest fi?ier doar ca sursa a listelor de rute; nu pune logica aici.
 */

if (!isset($router) || !is_object($router)) {
	throw new RuntimeException('Router nu este ini?ializat �nainte de includerea config/routes.php');
}

// Autentificare
$router->addRoute('GET', '/login', 'LoginController', 'index');
$router->addRoute('POST', '/login', 'LoginController', 'login');
$router->addRoute('GET', '/logout', 'LoginController', 'logout');
$router->addRoute('GET', '/forgot-password', 'LoginController', 'forgotPassword');

// Dashboard / Home
$router->addRoute('GET', '/', 'HomeController', 'index');
$router->addRoute('GET', '/dashboard', 'DashboardController', 'index');

// Vehicule
$router->addRoute('GET', '/vehicles', 'VehicleController', 'index');
$router->addRoute('GET', '/vehicles/add', 'VehicleController', 'add');
$router->addRoute('POST', '/vehicles/add', 'VehicleController', 'add');
$router->addRoute('GET', '/vehicles/edit', 'VehicleController', 'edit'); // id prin ?id=XX
$router->addRoute('POST', '/vehicles/edit', 'VehicleController', 'edit');
$router->addRoute('GET', '/vehicles/view', 'VehicleController', 'view');
$router->addRoute('GET', '/vehicles/delete', 'VehicleController', 'delete');
$router->addRoute('POST', '/vehicles/delete', 'VehicleController', 'delete');
$router->addRoute('POST', '/vehicles/updateMileage', 'VehicleController', 'updateMileage');
$router->addRoute('GET', '/vehicles/export', 'VehicleController', 'export');
$router->addRoute('POST', '/vehicles/store', 'VehicleController', 'store');

// Tipuri vehicule
$router->addRoute('GET', '/vehicle-types', 'VehicleTypeController', 'index');
$router->addRoute('GET', '/vehicle-types/add', 'VehicleTypeController', 'add');
$router->addRoute('POST', '/vehicle-types/add', 'VehicleTypeController', 'add');
$router->addRoute('GET', '/vehicle-types/edit', 'VehicleTypeController', 'edit');
$router->addRoute('POST', '/vehicle-types/edit', 'VehicleTypeController', 'edit');
$router->addRoute('POST', '/vehicle-types/delete', 'VehicleTypeController', 'delete');

// Documente
$router->addRoute('GET', '/documents', 'DocumentController', 'index');
$router->addRoute('GET', '/documents/add', 'DocumentController', 'add');
$router->addRoute('POST', '/documents/add', 'DocumentController', 'add');
$router->addRoute('GET', '/documents/edit', 'DocumentController', 'edit');
$router->addRoute('POST', '/documents/edit', 'DocumentController', 'edit');
$router->addRoute('GET', '/documents/expiring', 'DocumentController', 'expiring');
$router->addRoute('POST', '/documents/delete', 'DocumentController', 'delete');
$router->addRoute('GET', '/documents/export', 'DocumentController', 'export');
$router->addRoute('GET', '/documents/view', 'DocumentController', 'view');

// Asigurari
$router->addRoute('GET', '/insurance', 'InsuranceController', 'index');
$router->addRoute('GET', '/insurance/add', 'InsuranceController', 'add');
$router->addRoute('POST', '/insurance/add', 'InsuranceController', 'add');
$router->addRoute('GET', '/insurance/edit', 'InsuranceController', 'edit');
$router->addRoute('POST', '/insurance/edit', 'InsuranceController', 'edit');
$router->addRoute('GET', '/insurance/view', 'InsuranceController', 'view');
$router->addRoute('POST', '/insurance/delete', 'InsuranceController', 'delete');
$router->addRoute('GET', '/insurance/expiring', 'InsuranceController', 'expiring');

// ?oferi
$router->addRoute('GET', '/drivers', 'DriverController', 'index');
$router->addRoute('GET', '/drivers/add', 'DriverController', 'add');
$router->addRoute('POST', '/drivers/add', 'DriverController', 'add');
$router->addRoute('GET', '/drivers/edit', 'DriverController', 'edit');
$router->addRoute('POST', '/drivers/edit', 'DriverController', 'edit');
$router->addRoute('GET', '/drivers/view', 'DriverController', 'view');
$router->addRoute('POST', '/drivers/delete', 'DriverController', 'delete');

// �ntre?inere
$router->addRoute('GET', '/maintenance', 'MaintenanceController', 'index');
$router->addRoute('GET', '/maintenance/add', 'MaintenanceController', 'add');
$router->addRoute('POST', '/maintenance/add', 'MaintenanceController', 'add');
$router->addRoute('GET', '/maintenance/edit', 'MaintenanceController', 'edit');
$router->addRoute('POST', '/maintenance/edit', 'MaintenanceController', 'edit');
$router->addRoute('GET', '/maintenance/schedule', 'MaintenanceController', 'schedule');
$router->addRoute('GET', '/maintenance/history', 'MaintenanceController', 'history');
$router->addRoute('GET', '/maintenance/delete', 'MaintenanceController', 'delete');
$router->addRoute('POST', '/maintenance/delete', 'MaintenanceController', 'delete');

// Combustibil
$router->addRoute('GET', '/fuel', 'FuelController', 'index');
$router->addRoute('GET', '/fuel/add', 'FuelController', 'add');
$router->addRoute('POST', '/fuel/add', 'FuelController', 'add');
$router->addRoute('GET', '/fuel/last-odometer', 'FuelController', 'getVehicleLastOdometer');
$router->addRoute('GET', '/fuel/reports', 'FuelController', 'reports');
$router->addRoute('GET', '/fuel/consumption', 'FuelController', 'consumption');

// Rapoarte
$router->addRoute('GET', '/reports', 'ReportController', 'index');
$router->addRoute('GET', '/reports/fleet', 'ReportController', 'fleetReport');
$router->addRoute('GET', '/reports/vehicle', 'ReportController', 'vehicleReport');
$router->addRoute('GET', '/reports/costs', 'ReportController', 'costAnalysis');
$router->addRoute('GET', '/reports/maintenance', 'ReportController', 'maintenanceReport');
$router->addRoute('GET', '/reports/fuel', 'ReportController', 'fuelReport');
$router->addRoute('GET', '/reports/custom', 'ReportController', 'customReport');
$router->addRoute('GET', '/reports/fleet-overview-data', 'ReportController', 'fleetOverviewData');
$router->addRoute('GET', '/reports/cost-data', 'ReportController', 'costData');
$router->addRoute('GET', '/reports/maintenance-data', 'ReportController', 'maintenanceData');
$router->addRoute('GET', '/reports/fuel-consumption-data', 'ReportController', 'fuelConsumptionData');
$router->addRoute('POST', '/reports/generate', 'ReportController', 'generateAjax');
$router->addRoute('POST', '/reports/export', 'ReportController', 'exportAjax');

// SuperAdmin
$router->addRoute('GET', '/superadmin', 'SuperAdminController', 'dashboard');
$router->addRoute('GET', '/superadmin/dashboard', 'SuperAdminController', 'dashboard');
$router->addRoute('GET', '/superadmin/companies', 'SuperAdminController', 'companies');
$router->addRoute('GET', '/superadmin/companies/add', 'SuperAdminController', 'add');
$router->addRoute('POST', '/superadmin/companies/add', 'SuperAdminController', 'add');
$router->addRoute('GET', '/superadmin/companies/edit', 'SuperAdminController', 'edit');
$router->addRoute('POST', '/superadmin/companies/edit', 'SuperAdminController', 'edit');
$router->addRoute('POST', '/superadmin/companies/change-status', 'SuperAdminController', 'changeStatus');
$router->addRoute('POST', '/superadmin/companies/reset-admin', 'SuperAdminController', 'resetAdmin');
$router->addRoute('GET', '/superadmin/act-as', 'SuperAdminController', 'actAs');
$router->addRoute('GET', '/superadmin/stop-acting', 'SuperAdminController', 'stopActing');
// V2: Notifications Analytics
$router->addRoute('GET', '/superadmin/notifications', 'SuperAdminController', 'notificationsDashboard');
$router->addRoute('GET', '/superadmin/notifications/dashboard', 'SuperAdminController', 'notificationsDashboard');
$router->addRoute('GET', '/superadmin/notifications/export', 'SuperAdminController', 'notificationsExport');
$router->addRoute('GET', '/superadmin/notifications/templates', 'SuperAdminController', 'notificationTemplates');
$router->addRoute('POST', '/superadmin/notifications/templates', 'SuperAdminController', 'notificationTemplates');

// Utilizatori (profil & management)
$router->addRoute('GET', '/profile', 'UserController', 'profile');
$router->addRoute('POST', '/profile', 'UserController', 'saveProfile');
$router->addRoute('GET', '/settings', 'UserController', 'settings');
$router->addRoute('GET', '/users', 'UserController', 'index');
$router->addRoute('GET', '/users/add', 'UserController', 'add');
$router->addRoute('POST', '/users/add', 'UserController', 'add');
$router->addRoute('GET', '/users/edit', 'UserController', 'edit');
$router->addRoute('POST', '/users/edit', 'UserController', 'edit');
$router->addRoute('POST', '/users/delete', 'UserController', 'delete');

// Import CSV
$router->addRoute('GET', '/import', 'ImportController', 'index');
$router->addRoute('GET', '/import/download-vehicles-template', 'ImportController', 'downloadVehiclesTemplate');
$router->addRoute('GET', '/import/download-documents-template', 'ImportController', 'downloadDocumentsTemplate');
$router->addRoute('GET', '/import/download-drivers-template', 'ImportController', 'downloadDriversTemplate');
$router->addRoute('POST', '/import/upload-vehicles', 'ImportController', 'uploadVehicles');
$router->addRoute('POST', '/import/upload-documents', 'ImportController', 'uploadDocuments');
$router->addRoute('POST', '/import/upload-drivers', 'ImportController', 'uploadDrivers');

// Notificari & API
$router->addRoute('GET', '/notifications', 'NotificationController', 'index');
$router->addRoute('GET', '/notifications/alerts', 'NotificationController', 'alerts');
$router->addRoute('POST', '/notifications/dismiss', 'NotificationController', 'dismiss');
$router->addRoute('POST', '/notifications/mark-read', 'NotificationController', 'markAsRead');
$router->addRoute('POST', '/notifications/mark-all-read', 'NotificationController', 'markAllAsRead');
$router->addRoute('GET', '/notifications/unread-count', 'NotificationController', 'getUnreadCount');
$router->addRoute('POST', '/notifications/generate-system', 'NotificationController', 'generateSystemNotifications'); // legacy
$router->addRoute('GET', '/notifications/generate-system', 'NotificationController', 'generateSystemNotifications'); // legacy
// Nou: rută GET scurtă /notifications/generate (evită 404 pe query action)
$router->addRoute('GET', '/notifications/generate', 'NotificationController', 'generateSystemNotifications');
$router->addRoute('GET', '/notifications/settings', 'NotificationController', 'settings');
$router->addRoute('POST', '/notifications/settings', 'NotificationController', 'settings');
// V2: User preferences
$router->addRoute('GET', '/notifications/preferences', 'NotificationController', 'preferences');
$router->addRoute('POST', '/notifications/savePreferences', 'NotificationController', 'savePreferences');
$router->addRoute('POST', '/notifications/sendTest', 'NotificationController', 'sendTest');
$router->addRoute('GET', '/api/notifications', 'ApiController', 'notifications');

// Public landing pages (daca exista controllerele)
$router->addRoute('GET', '/home', 'LandingController', 'index');
$router->addRoute('GET', '/contact', 'LandingController', 'contact');
$router->addRoute('POST', '/contact/submit', 'LandingController', 'submitContact');

// ===== MODUL SERVICE =====
// Gestionare Servicii (externe și interne)
$router->addRoute('GET', '/service/services', 'ServiceController', 'index');
$router->addRoute('GET', '/service/services/add', 'ServiceController', 'add');
$router->addRoute('POST', '/service/services/add', 'ServiceController', 'add');
$router->addRoute('GET', '/service/services/view', 'ServiceController', 'view');
$router->addRoute('GET', '/service/services/edit', 'ServiceController', 'edit');
$router->addRoute('POST', '/service/services/edit', 'ServiceController', 'edit');
$router->addRoute('POST', '/service/services/delete', 'ServiceController', 'delete');
$router->addRoute('POST', '/service/services/activate', 'ServiceController', 'activate');
$router->addRoute('GET', '/service/services/internal-setup', 'ServiceController', 'internalSetup');
$router->addRoute('POST', '/service/services/internal-setup', 'ServiceController', 'internalSetup');
$router->addRoute('GET', '/api/services', 'ServiceController', 'apiGetServices');

// Atelier (Service Intern - Work Orders)
$router->addRoute('GET', '/service/workshop', 'WorkOrderController', 'index');
$router->addRoute('GET', '/service/workshop/add', 'WorkOrderController', 'add');
$router->addRoute('POST', '/service/workshop/add', 'WorkOrderController', 'add');
$router->addRoute('GET', '/service/workshop/view', 'WorkOrderController', 'view');
$router->addRoute('GET', '/service/workshop/edit', 'WorkOrderController', 'edit');
$router->addRoute('POST', '/service/workshop/edit', 'WorkOrderController', 'edit');
$router->addRoute('GET', '/service/workshop/vehicles', 'WorkOrderController', 'vehiclesInService');

// AJAX endpoints pentru atelier
$router->addRoute('POST', '/service/workshop/update-status', 'WorkOrderController', 'updateStatus');
$router->addRoute('POST', '/service/workshop/assign-mechanic', 'WorkOrderController', 'assignMechanic');
$router->addRoute('POST', '/service/workshop/add-part', 'WorkOrderController', 'addPart');
$router->addRoute('POST', '/service/workshop/start-labor', 'WorkOrderController', 'startLabor');
$router->addRoute('POST', '/service/workshop/end-labor', 'WorkOrderController', 'endLabor');
$router->addRoute('POST', '/service/workshop/update-checklist', 'WorkOrderController', 'updateChecklist');
$router->addRoute('POST', '/service/workshop/delete', 'WorkOrderController', 'delete');

// Mecanici (Service Mechanics)
$router->addRoute('GET', '/service/mechanics', 'MechanicController', 'index');
$router->addRoute('GET', '/service/mechanics/add', 'MechanicController', 'add');
$router->addRoute('POST', '/service/mechanics/add', 'MechanicController', 'add');
$router->addRoute('GET', '/service/mechanics/view', 'MechanicController', 'view');
$router->addRoute('GET', '/service/mechanics/edit', 'MechanicController', 'edit');
$router->addRoute('POST', '/service/mechanics/edit', 'MechanicController', 'edit');
$router->addRoute('GET', '/service/mechanics/delete', 'MechanicController', 'delete');

// Sf�r?it liste
