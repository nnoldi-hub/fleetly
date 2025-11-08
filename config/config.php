<?php
// config/config.php

// Production error handling
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('ROOT_PATH', dirname(__DIR__));
// Public base URL (for static assets, images, css/js). Should NOT include index.php
// CONFIGURE THIS for your production domain before deployment
// Detect environment (simple heuristic). In production set FM_ENV=prod in hosting panel.
$env = getenv('FM_ENV') ?: 'local';

// Hostico example domain/path (ADAPTEAZA la domeniul tau real):
// ex: https://fleetly.hostico.ro/  sau https://client.tau.ro/fleet/
if ($env === 'prod') {
	define('BASE_URL', 'https://YOUR_HOSTICO_DOMAIN/'); // MODIFICA
} else {
	define('BASE_URL', 'http://localhost/fleet-management/');
}
// Route base (pentru actiuni/form-uri cand mod_rewrite lipseste)
define('ROUTE_BASE', rtrim(BASE_URL, '/') . '/index.php/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Setări aplicație
define('APP_NAME', 'Fleet Management System');
define('APP_VERSION', '1.0.0');
define('ITEMS_PER_PAGE', 20);

// Setări notificări (deprecated - use config/mail.php)
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'your-email@gmail.com');
define('EMAIL_PASSWORD', 'your-app-password');
