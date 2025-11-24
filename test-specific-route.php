<?php
/**
 * Test specific route matching
 */

// Simulate the normalizePath function
function normalizePath($p) {
    if ($p === null) return '/';
    $p = parse_url($p, PHP_URL_PATH) ?? '/';
    if ($p === '') $p = '/';
    if ($p[0] !== '/') $p = '/' . $p;
    if (strlen($p) > 1) {
        $p = rtrim($p, '/');
    }
    return $p;
}

// Simulate matchPath
function matchPath($routePath, $uri) {
    $r = normalizePath($routePath);
    $u = normalizePath($uri);
    $r = str_replace('/index.php', '', $r);
    $u = str_replace('/index.php', '', $u);
    return ($r === $u);
}

echo "<h1>Route Matching Test</h1><pre>";

// Test cases
$tests = [
    'Case 1: /service/services (index page)' => [
        'uri' => '/service/services',
        'routes' => ['/service/services', '/service/services/view', '/service/workshop']
    ],
    'Case 2: /service/services/view?id=1 (details page)' => [
        'uri' => '/service/services/view?id=1',
        'routes' => ['/service/services', '/service/services/view', '/service/workshop']
    ],
    'Case 3: /service/workshop (dashboard)' => [
        'uri' => '/service/workshop',
        'routes' => ['/service/services', '/service/workshop', '/service/workshop/vehicles']
    ],
    'Case 4: /service/workshop/vehicles' => [
        'uri' => '/service/workshop/vehicles',
        'routes' => ['/service/workshop', '/service/workshop/vehicles', '/service/mechanics']
    ],
];

foreach ($tests as $testName => $test) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo $testName . "\n";
    echo str_repeat('=', 60) . "\n";
    
    $uri = $test['uri'];
    $normalizedUri = normalizePath($uri);
    
    echo "Original URI: $uri\n";
    echo "Normalized URI: $normalizedUri\n\n";
    
    echo "Checking against routes:\n";
    foreach ($test['routes'] as $route) {
        $normalizedRoute = normalizePath($route);
        $match = matchPath($route, $uri);
        $symbol = $match ? '✓ MATCH' : '✗ no match';
        echo "  $symbol  Route: $route (normalized: $normalizedRoute)\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "normalizePath removes query strings: " . (normalizePath('/test?id=1') === '/test' ? 'YES ✓' : 'NO ✗') . "\n";
echo "Exact match required: " . (matchPath('/service/services', '/service/services/view') ? 'NO ✗' : 'YES ✓') . "\n";
echo "Query strings ignored in matching: " . (matchPath('/service/services/view', '/service/services/view?id=1') ? 'YES ✓' : 'NO ✗') . "\n";

echo "</pre>";
