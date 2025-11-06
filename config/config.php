<?php
// config/config.php

// Production error handling
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('ROOT_PATH', dirname(__DIR__));
// Public base URL (for static assets, images, css/js). Should NOT include index.php
// CONFIGURE THIS for your production domain before deployment
define('BASE_URL', 'http://localhost/fleet-management/');
// Route base (for application routes when mod_rewrite is not available)
define('ROUTE_BASE', BASE_URL . 'index.php/');
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
