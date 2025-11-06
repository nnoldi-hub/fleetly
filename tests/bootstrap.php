<?php
// Basic bootstrap for PHPUnit
define('BASE_PATH', realpath(__DIR__ . '/..'));

// Try Composer autoload if present
$vendor = BASE_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) {
    require_once $vendor;
}

// Minimal requires for core classes used in tests
require_once BASE_PATH . '/core/router.php';
require_once BASE_PATH . '/core/Util.php';
