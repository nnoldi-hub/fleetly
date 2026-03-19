<?php
/**
 * API Diagnostic Test
 * 
 * Acest fișier testează dacă API-ul funcționează corect.
 * Accesează: https://fleetly.ro/api/v1/test-api.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tests = [];
$allPassed = true;

// Test 1: PHP Version
$tests['php_version'] = [
    'test' => 'PHP Version >= 7.4',
    'value' => phpversion(),
    'passed' => version_compare(phpversion(), '7.4.0', '>=')
];

// Test 2: Required extensions
$requiredExtensions = ['json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql'];
foreach ($requiredExtensions as $ext) {
    $tests['ext_' . $ext] = [
        'test' => "Extension: $ext",
        'value' => extension_loaded($ext) ? 'loaded' : 'missing',
        'passed' => extension_loaded($ext)
    ];
}

// Test 3: Check vendor/autoload.php exists
$vendorAutoload = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$tests['vendor_autoload'] = [
    'test' => 'vendor/autoload.php exists',
    'value' => file_exists($vendorAutoload) ? $vendorAutoload : 'NOT FOUND',
    'passed' => file_exists($vendorAutoload)
];

// Test 4: Check JWT library
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
    $jwtExists = class_exists('Firebase\\JWT\\JWT');
    $tests['jwt_library'] = [
        'test' => 'Firebase JWT Library',
        'value' => $jwtExists ? 'loaded' : 'NOT FOUND',
        'passed' => $jwtExists
    ];
} else {
    $tests['jwt_library'] = [
        'test' => 'Firebase JWT Library',
        'value' => 'Cannot test (autoload missing)',
        'passed' => false
    ];
}

// Test 5: Check database config
$dbConfig = dirname(dirname(__DIR__)) . '/config/database.php';
$tests['db_config'] = [
    'test' => 'config/database.php exists',
    'value' => file_exists($dbConfig) ? 'exists' : 'NOT FOUND',
    'passed' => file_exists($dbConfig)
];

// Test 6: Check API core files
$apiCoreFiles = [
    'core/ApiResponse.php',
    'core/JwtHandler.php',
    'core/ApiRouter.php',
    'middleware/AuthMiddleware.php',
    'controllers/AuthController.php'
];

foreach ($apiCoreFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $tests['file_' . str_replace(['/', '.'], '_', $file)] = [
        'test' => "File: $file",
        'value' => file_exists($fullPath) ? 'exists' : 'NOT FOUND',
        'passed' => file_exists($fullPath)
    ];
}

// Test 7: Try database connection
if (file_exists($dbConfig)) {
    try {
        require_once $dbConfig;
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '');
            $tests['db_connection'] = [
                'test' => 'Database connection',
                'value' => 'Connected to ' . DB_NAME,
                'passed' => true
            ];
        } else {
            $tests['db_connection'] = [
                'test' => 'Database connection',
                'value' => 'DB constants not defined',
                'passed' => false
            ];
        }
    } catch (Exception $e) {
        $tests['db_connection'] = [
            'test' => 'Database connection',
            'value' => 'Error: ' . $e->getMessage(),
            'passed' => false
        ];
    }
} else {
    $tests['db_connection'] = [
        'test' => 'Database connection',
        'value' => 'Cannot test (config missing)',
        'passed' => false
    ];
}

// Test 8: mod_rewrite detection
$tests['mod_rewrite'] = [
    'test' => 'mod_rewrite',
    'value' => in_array('mod_rewrite', apache_get_modules() ?? []) ? 'enabled' : 'unknown (check server)',
    'passed' => true // Can't reliably test
];

// Calculate overall status
foreach ($tests as $test) {
    if (!$test['passed']) {
        $allPassed = false;
    }
}

echo json_encode([
    'success' => $allPassed,
    'message' => $allPassed ? 'All tests passed!' : 'Some tests failed',
    'server_info' => [
        'php_sapi' => php_sapi_name(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ],
    'tests' => $tests
], JSON_PRETTY_PRINT);
