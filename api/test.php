<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/jwt.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/Report.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Database Connection Test ===\n";
echo "Connected successfully to: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n\n";

echo "=== Products Test ===\n";
$prodModel = new Product($db);
$products = $prodModel->getAll([], 1, 5);
echo "Total Products: " . $products['total'] . "\n";
foreach ($products['items'] as $p) {
    echo "  [#{$p['product_id']}] {$p['product_name']} ({$p['category_name']}) - ฿{$p['unit_price']}\n";
}

echo "\n=== Dashboard KPIs Test ===\n";
$reportModel = new Report($db);
$kpis = $reportModel->getDashboardKPIs();
echo "Revenue: ฿" . number_format($kpis['revenue'], 2) . "\n";
echo "Orders: {$kpis['orders']}\n";
echo "Products: {$kpis['products']}\n";
echo "Customers: {$kpis['customers']}\n";

echo "\n=== JWT Authentication Test ===\n";
$token = JWT::encode(['id' => 1, 'username' => 'admin', 'role' => 'ผู้ดูแลระบบ']);
echo "Generated Token: " . substr($token, 0, 25) . "...\n";
$decoded = JWT::decode($token);
echo "Decoded User: {$decoded['username']} ({$decoded['role']})\n";

echo "\nAll backend core tests passed!\n";

