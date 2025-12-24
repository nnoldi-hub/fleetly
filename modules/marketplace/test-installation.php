<?php
/**
 * Marketplace Test Script
 * Quick verification that marketplace is properly installed
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';

$db = Database::getInstance();

echo "<h1>Marketplace Installation Test</h1>";
echo "<hr>";

// Test 1: Check tables exist
echo "<h2>1. Database Tables</h2>";
$tables = ['mp_categories', 'mp_products', 'mp_cart', 'mp_orders', 'mp_order_items'];
foreach ($tables as $table) {
    $result = $db->fetch("SHOW TABLES LIKE '$table'");
    $status = $result ? '✅' : '❌';
    echo "$status Table: <strong>$table</strong><br>";
}

// Test 2: Check seed data
echo "<hr><h2>2. Seed Data</h2>";
$catCount = $db->fetch("SELECT COUNT(*) as count FROM mp_categories");
echo "✅ Categories: <strong>" . $catCount['count'] . "</strong><br>";

$prodCount = $db->fetch("SELECT COUNT(*) as count FROM mp_products");
echo "✅ Products: <strong>" . $prodCount['count'] . "</strong><br>";

// Test 3: Display categories
echo "<hr><h2>3. Categories</h2>";
$categories = $db->fetchAll("SELECT * FROM mp_categories ORDER BY sort_order");
echo "<ul>";
foreach ($categories as $cat) {
    echo "<li><strong>{$cat['name']}</strong> ({$cat['slug']}) - {$cat['icon']}</li>";
}
echo "</ul>";

// Test 4: Display sample products
echo "<hr><h2>4. Sample Products (First 5)</h2>";
$products = $db->fetchAll("SELECT p.name, p.sku, p.price, c.name as category FROM mp_products p JOIN mp_categories c ON p.category_id = c.id ORDER BY p.id LIMIT 5");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Product</th><th>SKU</th><th>Price</th><th>Category</th></tr>";
foreach ($products as $prod) {
    echo "<tr>";
    echo "<td>{$prod['name']}</td>";
    echo "<td>{$prod['sku']}</td>";
    echo "<td>{$prod['price']} RON</td>";
    echo "<td>{$prod['category']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 5: Check if models load
echo "<hr><h2>5. Model Classes</h2>";
$modelFiles = ['Category.php', 'Product.php', 'Cart.php', 'Order.php', 'OrderItem.php'];
foreach ($modelFiles as $file) {
    $path = __DIR__ . '/models/' . $file;
    $status = file_exists($path) ? '✅' : '❌';
    echo "$status Model: <strong>$file</strong><br>";
}

// Test 6: Check controllers
echo "<hr><h2>6. Controller Classes</h2>";
$controllerFiles = [
    'MarketplaceController.php',
    'ProductController.php',
    'CartController.php',
    'CheckoutController.php',
    'OrderController.php'
];
foreach ($controllerFiles as $file) {
    $path = __DIR__ . '/controllers/' . $file;
    $status = file_exists($path) ? '✅' : '❌';
    echo "$status Controller: <strong>$file</strong><br>";
}

// Test 7: Check admin controllers
echo "<hr><h2>7. Admin Controller Classes</h2>";
$adminControllers = [
    'DashboardController.php',
    'CatalogAdminController.php',
    'OrderAdminController.php'
];
foreach ($adminControllers as $file) {
    $path = __DIR__ . '/controllers/admin/' . $file;
    $status = file_exists($path) ? '✅' : '❌';
    echo "$status Admin Controller: <strong>$file</strong><br>";
}

// Test 8: Check upload directory
echo "<hr><h2>8. Upload Directory</h2>";
$uploadDir = __DIR__ . '/../../uploads/marketplace/products/';
$status = is_dir($uploadDir) && is_writable($uploadDir) ? '✅' : '❌';
echo "$status Upload directory: <strong>$uploadDir</strong><br>";
if (is_dir($uploadDir)) {
    echo "   Writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";
}

echo "<hr>";
echo "<h2>✅ Marketplace MVP Installation Complete!</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "modules/marketplace/'>Browse Marketplace (Public)</a></li>";
echo "<li><a href='" . BASE_URL . "modules/marketplace/?action=admin-dashboard'>Admin Dashboard</a> (SuperAdmin only)</li>";
echo "<li><a href='" . BASE_URL . "modules/marketplace/?action=admin-products'>Manage Products</a> (SuperAdmin only)</li>";
echo "</ul>";
?>
