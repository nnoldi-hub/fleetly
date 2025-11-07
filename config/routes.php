<?php
/**
 * Route definitions file (DEPRECAT - nu este folosit)
 * 
 * NOTA: Acest fisier NU este folosit in aplicatie!
 * Rutele sunt definite direct in index.php folosind $router->addRoute()
 * 
 * Acest fisier foloseste $router->add() care nu exista in clasa Router.
 * Pentru a adauga rute noi, editeaza index.php, NU acest fisier!
 * 
 * @var Router $router
 */

// Authentication routes
$router->add("GET", "/login", "LoginController@index", "modules/auth/controllers/LoginController.php");
$router->add("POST", "/login", "LoginController@login", "modules/auth/controllers/LoginController.php");
$router->add("GET", "/logout", "LoginController@logout", "modules/auth/controllers/LoginController.php");
$router->add("GET", "/forgot-password", "LoginController@forgotPassword", "modules/auth/controllers/LoginController.php");

// Dashboard
$router->add("GET", "/", "DashboardController@index", "modules/dashboard/controllers/DashboardController.php");
$router->add("GET", "/dashboard", "DashboardController@index", "modules/dashboard/controllers/DashboardController.php");

// Vehicles
$router->add("GET", "/vehicles", "VehicleController@index", "modules/vehicles/controllers/VehicleController.php");
$router->add("GET", "/vehicles/add", "VehicleController@add", "modules/vehicles/controllers/VehicleController.php");
$router->add("POST", "/vehicles/add", "VehicleController@store", "modules/vehicles/controllers/VehicleController.php");
$router->add("GET", "/vehicles/edit/{id}", "VehicleController@edit", "modules/vehicles/controllers/VehicleController.php");
$router->add("POST", "/vehicles/edit/{id}", "VehicleController@update", "modules/vehicles/controllers/VehicleController.php");

// Drivers
$router->add("GET", "/drivers", "DriverController@index", "modules/drivers/controllers/DriverController.php");
$router->add("GET", "/drivers/add", "DriverController@add", "modules/drivers/controllers/DriverController.php");
$router->add("POST", "/drivers/add", "DriverController@store", "modules/drivers/controllers/DriverController.php");

// Fuel
$router->add("GET", "/fuel", "FuelController@index", "modules/fuel/controllers/FuelController.php");
$router->add("GET", "/fuel/add", "FuelController@add", "modules/fuel/controllers/FuelController.php");
$router->add("POST", "/fuel/add", "FuelController@store", "modules/fuel/controllers/FuelController.php");
$router->add("GET", "/fuel/reports", "FuelController@reports", "modules/fuel/controllers/FuelController.php");

// Maintenance
$router->add("GET", "/maintenance", "MaintenanceController@index", "modules/maintenance/controllers/MaintenanceController.php");
$router->add("GET", "/maintenance/add", "MaintenanceController@add", "modules/maintenance/controllers/MaintenanceController.php");
$router->add("POST", "/maintenance/add", "MaintenanceController@store", "modules/maintenance/controllers/MaintenanceController.php");

// SuperAdmin routes
$router->add("GET", "/superadmin/dashboard", "SuperAdminController@dashboard", "modules/superadmin/controllers/SuperAdminController.php");
$router->add("GET", "/superadmin/companies", "CompanyController@index", "modules/superadmin/controllers/CompanyController.php");
$router->add("GET", "/superadmin/companies/add", "CompanyController@add", "modules/superadmin/controllers/CompanyController.php");
$router->add("POST", "/superadmin/companies/add", "CompanyController@store", "modules/superadmin/controllers/CompanyController.php");

// Admin routes
$router->add("GET", "/admin/users", "UserController@index", "modules/admin/controllers/UserController.php");
$router->add("GET", "/admin/users/add", "UserController@add", "modules/admin/controllers/UserController.php");
$router->add("POST", "/admin/users/add", "UserController@store", "modules/admin/controllers/UserController.php");

// Import routes
$router->add("GET", "/import", "ImportController@index", "modules/import/controllers/ImportController.php");
$router->add("GET", "/import/download-vehicles-template", "ImportController@downloadVehiclesTemplate", "modules/import/controllers/ImportController.php");
$router->add("GET", "/import/download-documents-template", "ImportController@downloadDocumentsTemplate", "modules/import/controllers/ImportController.php");
$router->add("GET", "/import/download-drivers-template", "ImportController@downloadDriversTemplate", "modules/import/controllers/ImportController.php");
$router->add("POST", "/import/upload-vehicles", "ImportController@uploadVehicles", "modules/import/controllers/ImportController.php");
$router->add("POST", "/import/upload-documents", "ImportController@uploadDocuments", "modules/import/controllers/ImportController.php");
$router->add("POST", "/import/upload-drivers", "ImportController@uploadDrivers", "modules/import/controllers/ImportController.php");