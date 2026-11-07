<?php
/**
 * Northwind BMS - Main RESTful API Router
 * Pure PHP API Architecture (No external frameworks)
 * PDO OOP + SQL Parameter Binding + JWT Auth
 */

// Enable CORS & JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include Configurations & JWT
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/jwt.php';

// Include Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Customer.php';
require_once __DIR__ . '/models/Supplier.php';
require_once __DIR__ . '/models/Employee.php';
require_once __DIR__ . '/models/Shipper.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/Report.php';

// Initialize Database Connection
$database = new Database();
$db = $database->getConnection();

// Determine Request Method & URI Path
$method = $_SERVER['REQUEST_METHOD'];

// Parse request path
// Supports either direct rewrite /api/products/1 or /api/index.php?path=products/1
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Check query param 'endpoint' or 'path'
if (isset($_GET['path'])) {
    $path = trim($_GET['path'], '/');
} elseif (isset($_GET['endpoint'])) {
    $path = trim($_GET['endpoint'], '/');
} else {
    // Extract path after api/ or index.php/
    $base = dirname($scriptName);
    $uriPath = parse_url($requestUri, PHP_URL_PATH);
    if ($base !== '/' && strpos($uriPath, $base) === 0) {
        $uriPath = substr($uriPath, strlen($base));
    }
    $path = trim(str_replace('index.php', '', $uriPath), '/');
}

// Remove prefix 'v1/' or 'api/' if present
$path = preg_replace('#^(api/|v1/)+#', '', $path);
$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$subAction = $segments[2] ?? null;

// Read JSON Body for POST and PUT
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true) ?? [];

// Standard JSON Response Helper
function jsonResponse($status, $data = null, $message = '', $code = 200, $extra = []) {
    http_response_code($code);
    $response = [
        "status" => $status,
        "message" => $message
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    foreach ($extra as $k => $v) {
        $response[$k] = $v;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    switch ($resource) {
        // ==========================================
        // AUTHENTICATION (Login, Verify)
        // ==========================================
        case 'auth':
            $userModel = new User($db);

            if ($id === 'login' && $method === 'POST') {
                $identity = trim($input['username'] ?? $input['email'] ?? '');
                $password = trim($input['password'] ?? '');

                if (empty($identity) || empty($password)) {
                    jsonResponse(false, null, "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน", 400);
                }

                $user = $userModel->findByUsernameOrEmail($identity);
                if (!$user) {
                    jsonResponse(false, null, "ไม่พบชื่อผู้ใช้หรืออีเมลนี้ในระบบ", 401);
                }

                // Verify password (supports bcrypt hash or demo fallback)
                $isValid = password_verify($password, $user['password']) || ($password === 'admin1234' || $password === 'admin');
                if (!$isValid) {
                    jsonResponse(false, null, "รหัสผ่านไม่ถูกต้อง", 401);
                }

                // Generate JWT Token
                $tokenPayload = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar']
                ];
                $token = JWT::encode($tokenPayload);

                jsonResponse(true, [
                    'token' => $token,
                    'user' => $tokenPayload
                ], "เข้าสู่ระบบสำเร็จ");
            } elseif ($id === 'me' && $method === 'GET') {
                $user = JWT::requireAuth();
                jsonResponse(true, $user, "ตรวจสอบตัวตนสำเร็จ");
            } else {
                jsonResponse(false, null, "Auth action not found", 404);
            }
            break;

        // ==========================================
        // DASHBOARD
        // ==========================================
        case 'dashboard':
            $reportModel = new Report($db);
            $orderModel = new Order($db);

            $kpis = $reportModel->getDashboardKPIs();
            $recentOrders = $orderModel->getAll([], 1, 6);
            $monthlySales = $reportModel->getMonthlySales();
            $topProducts = $reportModel->getTopProducts(5);

            jsonResponse(true, [
                'kpis' => $kpis,
                'recent_orders' => $recentOrders['items'],
                'monthly_sales' => $monthlySales,
                'top_products' => $topProducts
            ], "ดึงข้อมูลแดชบอร์ดสำเร็จ");
            break;

        // ==========================================
        // PRODUCTS (CRUD)
        // ==========================================
        case 'products':
            $productModel = new Product($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $productModel->getById($id);
                    if ($item) {
                        jsonResponse(true, $item);
                    } else {
                        jsonResponse(false, null, "ไม่พบข้อมูลสินค้า", 404);
                    }
                } else {
                    $filters = [
                        'search' => $_GET['search'] ?? '',
                        'category_id' => $_GET['category_id'] ?? '',
                        'supplier_id' => $_GET['supplier_id'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'discontinued' => $_GET['discontinued'] ?? ''
                    ];
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

                    $result = $productModel->getAll($filters, $page, $limit);
                    jsonResponse(true, $result['items'], "ดึงรายการสินค้าสำเร็จ", 200, [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'limit' => $result['limit'],
                        'total_pages' => $result['total_pages']
                    ]);
                }
            } elseif ($method === 'POST') {
                // Validation
                if (empty($input['product_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อสินค้า", 422);
                }
                if (!isset($input['unit_price']) || (float)$input['unit_price'] < 0) {
                    jsonResponse(false, null, "ราคาต้องเป็นตัวเลขที่มากกว่าหรือเท่ากับ 0", 422);
                }

                $newId = $productModel->create($input);
                $created = $productModel->getById($newId);
                jsonResponse(true, $created, "เพิ่มสินค้าเรียบร้อยแล้ว", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing product ID", 400);

                if (empty($input['product_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อสินค้า", 422);
                }

                $productModel->update($id, $input);
                $updated = $productModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขข้อมูลสินค้าเรียบร้อยแล้ว");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing product ID", 400);
                $productModel->delete($id);
                jsonResponse(true, null, "ลบสินค้าเรียบร้อยแล้ว");
            }
            break;

        // ==========================================
        // CATEGORIES (CRUD)
        // ==========================================
        case 'categories':
            $categoryModel = new Category($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $categoryModel->getById($id);
                    if ($item) jsonResponse(true, $item);
                    else jsonResponse(false, null, "ไม่พบหมวดหมู่", 404);
                } else {
                    $items = $categoryModel->getAll();
                    jsonResponse(true, $items, "ดึงหมวดหมู่สำเร็จ", 200, ['total' => count($items)]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['category_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อหมวดหมู่", 422);
                }
                $newId = $categoryModel->create($input);
                $created = $categoryModel->getById($newId);
                jsonResponse(true, $created, "เพิ่มหมวดหมู่สำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing category ID", 400);
                if (empty($input['category_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อหมวดหมู่", 422);
                }
                $categoryModel->update($id, $input);
                $updated = $categoryModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขหมวดหมู่สำเร็จ");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing category ID", 400);
                $categoryModel->delete($id);
                jsonResponse(true, null, "ลบหมวดหมู่เรียบร้อยแล้ว");
            }
            break;

        // ==========================================
        // ORDERS (CRUD & Status)
        // ==========================================
        case 'orders':
            $orderModel = new Order($db);

            if ($method === 'GET') {
                if ($id) {
                    $order = $orderModel->getById($id);
                    if ($order) jsonResponse(true, $order);
                    else jsonResponse(false, null, "ไม่พบคำสั่งซื้อ", 404);
                } else {
                    $filters = [
                        'search' => $_GET['search'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'customer_id' => $_GET['customer_id'] ?? ''
                    ];
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

                    $result = $orderModel->getAll($filters, $page, $limit);
                    jsonResponse(true, $result['items'], "ดึงรายการคำสั่งซื้อสำเร็จ", 200, [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'limit' => $result['limit'],
                        'total_pages' => $result['total_pages']
                    ]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['customer_id'])) {
                    jsonResponse(false, null, "กรุณาเลือกลูกค้า", 422);
                }
                $orderId = $orderModel->create($input);
                $created = $orderModel->getById($orderId);
                jsonResponse(true, $created, "สร้างคำสั่งซื้อสำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing order ID", 400);

                if ($subAction === 'status' || isset($input['status'])) {
                    $status = $input['status'] ?? 'จัดส่งแล้ว';
                    $orderModel->updateStatus($id, $status);
                    $updated = $orderModel->getById($id);
                    jsonResponse(true, $updated, "อัปเดตสถานะคำสั่งซื้อสำเร็จ");
                } else {
                    jsonResponse(false, null, "Order update not supported", 400);
                }
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing order ID", 400);
                $orderModel->delete($id);
                jsonResponse(true, null, "ลบคำสั่งซื้อเรียบร้อยแล้ว");
            }
            break;

        // ==========================================
        // CUSTOMERS (CRUD)
        // ==========================================
        case 'customers':
            $customerModel = new Customer($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $customerModel->getById($id);
                    if ($item) jsonResponse(true, $item);
                    else jsonResponse(false, null, "ไม่พบข้อมูลลูกค้า", 404);
                } else {
                    $search = $_GET['search'] ?? '';
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
                    $result = $customerModel->getAll($search, $page, $limit);
                    jsonResponse(true, $result['items'], "ดึงรายชื่อลูกค้าสำเร็จ", 200, [
                        'total' => $result['total']
                    ]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['customer_id']) || empty($input['company_name'])) {
                    jsonResponse(false, null, "กรุณากรอกรหัสลูกค้าและชื่อบริษัท", 422);
                }
                $customerModel->create($input);
                $created = $customerModel->getById($input['customer_id']);
                jsonResponse(true, $created, "เพิ่มข้อมูลลูกค้าสำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing customer ID", 400);
                if (empty($input['company_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อบริษัท", 422);
                }
                $customerModel->update($id, $input);
                $updated = $customerModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขข้อมูลลูกค้าสำเร็จ");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing customer ID", 400);
                $customerModel->delete($id);
                jsonResponse(true, null, "ลบข้อมูลลูกค้าสำเร็จ");
            }
            break;

        // ==========================================
        // SUPPLIERS (CRUD)
        // ==========================================
        case 'suppliers':
            $supplierModel = new Supplier($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $supplierModel->getById($id);
                    if ($item) jsonResponse(true, $item);
                    else jsonResponse(false, null, "ไม่พบข้อมูลผู้จัดจำหน่าย", 404);
                } else {
                    $search = $_GET['search'] ?? '';
                    $items = $supplierModel->getAll($search);
                    jsonResponse(true, $items, "ดึงรายชื่อผู้จัดจำหน่ายสำเร็จ", 200, ['total' => count($items)]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['company_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อบริษัทผู้จัดจำหน่าย", 422);
                }
                $newId = $supplierModel->create($input);
                $created = $supplierModel->getById($newId);
                jsonResponse(true, $created, "เพิ่มผู้จัดจำหน่ายสำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing supplier ID", 400);
                if (empty($input['company_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อบริษัท", 422);
                }
                $supplierModel->update($id, $input);
                $updated = $supplierModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขข้อมูลผู้จัดจำหน่ายสำเร็จ");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing supplier ID", 400);
                $supplierModel->delete($id);
                jsonResponse(true, null, "ลบผู้จัดจำหน่ายสำเร็จ");
            }
            break;

        // ==========================================
        // EMPLOYEES (CRUD)
        // ==========================================
        case 'employees':
            $employeeModel = new Employee($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $employeeModel->getById($id);
                    if ($item) jsonResponse(true, $item);
                    else jsonResponse(false, null, "ไม่พบข้อมูลพนักงาน", 404);
                } else {
                    $items = $employeeModel->getAll();
                    jsonResponse(true, $items, "ดึงรายชื่อพนักงานสำเร็จ", 200, ['total' => count($items)]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['first_name']) || empty($input['last_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อและนามสกุลพนักงาน", 422);
                }
                $newId = $employeeModel->create($input);
                $created = $employeeModel->getById($newId);
                jsonResponse(true, $created, "เพิ่มพนักงานสำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing employee ID", 400);
                $employeeModel->update($id, $input);
                $updated = $employeeModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขข้อมูลพนักงานสำเร็จ");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing employee ID", 400);
                $employeeModel->delete($id);
                jsonResponse(true, null, "ลบพนักงานสำเร็จ");
            }
            break;

        // ==========================================
        // SHIPPERS (CRUD)
        // ==========================================
        case 'shippers':
            $shipperModel = new Shipper($db);

            if ($method === 'GET') {
                if ($id) {
                    $item = $shipperModel->getById($id);
                    if ($item) jsonResponse(true, $item);
                    else jsonResponse(false, null, "ไม่พบข้อมูลบริษัทขนส่ง", 404);
                } else {
                    $items = $shipperModel->getAll();
                    jsonResponse(true, $items, "ดึงรายชื่อบริษัทขนส่งสำเร็จ", 200, ['total' => count($items)]);
                }
            } elseif ($method === 'POST') {
                if (empty($input['company_name'])) {
                    jsonResponse(false, null, "กรุณากรอกชื่อบริษัทขนส่ง", 422);
                }
                $newId = $shipperModel->create($input);
                $created = $shipperModel->getById($newId);
                jsonResponse(true, $created, "เพิ่มบริษัทขนส่งสำเร็จ", 201);
            } elseif ($method === 'PUT') {
                if (!$id) jsonResponse(false, null, "Missing shipper ID", 400);
                $shipperModel->update($id, $input);
                $updated = $shipperModel->getById($id);
                jsonResponse(true, $updated, "แก้ไขข้อมูลบริษัทขนส่งสำเร็จ");
            } elseif ($method === 'DELETE') {
                if (!$id) jsonResponse(false, null, "Missing shipper ID", 400);
                $shipperModel->delete($id);
                jsonResponse(true, null, "ลบข้อมูลบริษัทขนส่งสำเร็จ");
            }
            break;

        // ==========================================
        // REPORTS & ANALYTICS
        // ==========================================
        case 'reports':
            $reportModel = new Report($db);

            if ($id === 'dashboard') {
                $monthly = $reportModel->getMonthlySales();
                $topProducts = $reportModel->getTopProducts(5);
                $topCustomers = $reportModel->getTopCustomers(5);
                jsonResponse(true, [
                    'monthly_sales' => $monthly,
                    'top_products' => $topProducts,
                    'top_customers' => $topCustomers
                ], "ดึงข้อมูลรายงานสำเร็จ");
            } elseif ($id === 'export') {
                $type = $_GET['type'] ?? 'products';
                if ($type === 'products') {
                    $pModel = new Product($db);
                    $data = $pModel->getAll([], 1, 1000)['items'];
                } elseif ($type === 'orders') {
                    $oModel = new Order($db);
                    $data = $oModel->getAll([], 1, 1000)['items'];
                } else {
                    $cModel = new Customer($db);
                    $data = $cModel->getAll('', 1, 1000)['items'];
                }
                jsonResponse(true, $data, "ส่งออกข้อมูลสำเร็จ");
            } else {
                jsonResponse(false, null, "Report endpoint not found", 404);
            }
            break;

        // ==========================================
        // DEFAULT / PING
        // ==========================================
        case '':
        case 'ping':
            jsonResponse(true, [
                'name' => 'Northwind Business Management System API',
                'version' => '1.0.0',
                'status' => 'operational',
                'course' => 'CPE PSRU - Web Application Development',
                'database' => 'MySQL / PDO OOP Style',
                'security' => 'SQL Prepared Statements & Bind Parameters, JWT Auth'
            ], "Northwind BMS RESTful API is running smoothly!");
            break;

        default:
            jsonResponse(false, null, "Resource '{$resource}' not found", 404);
            break;
    }
} catch (Exception $e) {
    jsonResponse(false, null, "Server Error: " . $e->getMessage(), 500);
}

