<?php
// Initialize SQLite DB for instant zero-config testing
$sqliteFile = __DIR__ . '/northwind.sqlite';
if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

$pdo = new PDO('sqlite:' . $sqliteFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Execute schema
$schema = <<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  full_name TEXT NOT NULL,
  role TEXT DEFAULT 'ผู้ดูแลระบบ',
  avatar TEXT DEFAULT 'วช',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
  category_id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_name TEXT NOT NULL,
  description TEXT,
  icon TEXT DEFAULT '📦'
);

CREATE TABLE IF NOT EXISTS suppliers (
  supplier_id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_name TEXT NOT NULL,
  contact_name TEXT,
  contact_title TEXT,
  address TEXT,
  city TEXT,
  country TEXT,
  phone TEXT
);

CREATE TABLE IF NOT EXISTS shippers (
  shipper_id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_name TEXT NOT NULL,
  phone TEXT,
  icon TEXT DEFAULT '🚚'
);

CREATE TABLE IF NOT EXISTS employees (
  employee_id INTEGER PRIMARY KEY AUTOINCREMENT,
  last_name TEXT NOT NULL,
  first_name TEXT NOT NULL,
  title TEXT,
  birth_date DATE,
  hire_date DATE,
  address TEXT,
  city TEXT,
  country TEXT,
  notes TEXT,
  avatar TEXT DEFAULT '👤'
);

CREATE TABLE IF NOT EXISTS customers (
  customer_id TEXT PRIMARY KEY,
  company_name TEXT NOT NULL,
  contact_name TEXT,
  contact_title TEXT,
  address TEXT,
  city TEXT,
  country TEXT,
  postal_code TEXT,
  phone TEXT
);

CREATE TABLE IF NOT EXISTS products (
  product_id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_name TEXT NOT NULL,
  supplier_id INTEGER,
  category_id INTEGER,
  quantity_per_unit TEXT,
  unit_price REAL DEFAULT 0.00,
  units_in_stock INTEGER DEFAULT 0,
  units_on_order INTEGER DEFAULT 0,
  reorder_level INTEGER DEFAULT 0,
  discontinued INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS orders (
  order_id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id TEXT,
  employee_id INTEGER,
  order_date DATE NOT NULL,
  required_date DATE,
  shipped_date DATE,
  ship_via INTEGER,
  freight REAL DEFAULT 0.00,
  ship_name TEXT,
  ship_address TEXT,
  ship_city TEXT,
  ship_country TEXT,
  status TEXT DEFAULT 'จัดส่งแล้ว'
);

CREATE TABLE IF NOT EXISTS order_details (
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  unit_price REAL NOT NULL DEFAULT 0.00,
  quantity INTEGER NOT NULL DEFAULT 1,
  discount REAL DEFAULT 0.00,
  PRIMARY KEY (order_id, product_id)
);
SQL;

$pdo->exec($schema);

// Seed data
// Password hash for 'admin1234'
$hash = password_hash('admin1234', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (username, email, password, full_name, role, avatar) VALUES (?, ?, ?, ?, ?, ?)")
    ->execute(['admin', 'admin@northwind.co.th', $hash, 'วชิรา ล.', 'ผู้ดูแลระบบ', 'วช']);
$pdo->prepare("INSERT INTO users (username, email, password, full_name, role, avatar) VALUES (?, ?, ?, ?, ?, ?)")
    ->execute(['demo', 'demo@northwind.co.th', $hash, 'ผู้จัดการ สาขา', 'ผู้จัดการฝ่ายขาย', 'ผจ']);

// Categories
$cats = [
  [1, 'เครื่องดื่ม', 'Soft drinks, coffees, teas, beers, and ales', '🍹'],
  [2, 'เครื่องปรุงและซอส', 'Sweet and savory sauces, relishes, spreads, and seasonings', '🧂'],
  [3, 'ขนมหวานและของทานเล่น', 'Desserts, candies, and sweet breads', '🍰'],
  [4, 'ผลิตภัณฑ์จากนมและเนย', 'Cheeses, milk, butter', '🧀'],
  [5, 'ธัญพืชและแป้ง', 'Breads, crackers, pasta, and cereal', '🌾'],
  [6, 'เนื้อสัตว์และสัตว์ปีก', 'Prepared meats, poultry, cold cuts', '🥩'],
  [7, 'ผักและผลไม้สด', 'Dried fruit and bean curd, fresh produce', '🥗'],
  [8, 'อาหารทะเลสดและแช่แข็ง', 'Seaweed and fish, shellfish', '🐟'],
  [9, 'เบเกอรี่และของว่าง', 'Fresh bakery items, biscuits, and pies', '🥐']
];
$stmtCat = $pdo->prepare("INSERT INTO categories (category_id, category_name, description, icon) VALUES (?, ?, ?, ?)");
foreach ($cats as $c) $stmtCat->execute($c);

// Suppliers
$sups = [
  [1, 'Exotic Liquids', 'Charlotte Cooper', 'Purchasing Manager', '49 Gilbert St.', 'London', 'UK', '(171) 555-2222'],
  [2, 'New Orleans Cajun Delights', 'Shelley Burke', 'Order Administrator', 'P.O. Box 78934', 'New Orleans', 'USA', '(100) 555-4822'],
  [3, 'Grandma Kelly\'s Homestead', 'Regina Murphy', 'Sales Representative', '707 Oxford Rd.', 'Ann Arbor', 'USA', '(313) 555-5735'],
  [4, 'Tokyo Traders', 'Yoshi Nagase', 'Marketing Manager', '9-8 Sekimai Musashino-shi', 'Tokyo', 'Japan', '(03) 3555-5011'],
  [5, 'Cooperativa de Quesos \'Las Cabras\'', 'Antonio del Valle Saavedra', 'Export Administrator', 'Calle del Rosal 4', 'Oviedo', 'Spain', '(98) 598 76 54'],
  [6, 'Mayumi\'s', 'Mayumi Ohno', 'Marketing Representative', '92 Setsuko Chuo-ku', 'Osaka', 'Japan', '(06) 431-7877'],
  [7, 'Pavlova, Ltd.', 'Ian Devling', 'Marketing Manager', '74 Rose St. Moonie Ponds', 'Melbourne', 'Australia', '(03) 444-2343'],
  [8, 'Specialty Biscuits, Ltd.', 'Peter Wilson', 'Sales Representative', '29 King\'s Way', 'Manchester', 'UK', '(161) 555-4448']
];
$stmtSup = $pdo->prepare("INSERT INTO suppliers (supplier_id, company_name, contact_name, contact_title, address, city, country, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($sups as $s) $stmtSup->execute($s);

// Shippers
$ships = [
  [1, 'Speedy Express', '(503) 555-9831', '🚀'],
  [2, 'United Package', '(503) 555-3199', '📦'],
  [3, 'Federal Shipping', '(503) 555-9931', '🚢']
];
$stmtShip = $pdo->prepare("INSERT INTO shippers (shipper_id, company_name, phone, icon) VALUES (?, ?, ?, ?)");
foreach ($ships as $sh) $stmtShip->execute($sh);

// Employees
$emps = [
  [1, 'Davolio', 'Nancy', 'พนักงานขาย', '1968-12-08', '2012-05-01', '507 - 20th Ave. E. Apt. 2A', 'Seattle', 'USA', 'สำเร็จการศึกษาด้านจิตวิทยาและการตลาด', 'ND'],
  [2, 'Fuller', 'Andrew', 'ผู้จัดการฝ่ายขาย', '1972-02-19', '2012-08-14', '908 W. Capital Way', 'Tacoma', 'USA', 'ดุษฎีบัณฑิตสาขาการค้าระหว่างประเทศ', 'AF'],
  [3, 'Leverling', 'Janet', 'พนักงานขาย', '1973-08-30', '2012-04-01', '722 Moss Bay Blvd.', 'Kirkland', 'USA', 'ดูแลลูกค้ากลุ่มอเมริกาเหนือและเอเชีย', 'JL'],
  [4, 'Peacock', 'Margaret', 'พนักงานขายอาวุโส', '1977-09-19', '2013-05-03', '4110 Old Redmond Rd.', 'Redmond', 'USA', 'ยอดขายดีเด่นประจำปี 2023', 'MP'],
  [5, 'Buchanan', 'Steven', 'ผู้จัดการฝ่ายจัดซื้อ', '1975-03-04', '2013-10-17', '14 Garrett Hill', 'London', 'UK', 'ผู้เชี่ยวชาญด้านห่วงโซ่อุปทานยุโรป', 'SB'],
  [6, 'Suyama', 'Michael', 'พนักงานขาย', '1983-07-02', '2013-10-17', 'Coventry House Miner Rd.', 'London', 'UK', 'สื่อสารภาษาญี่ปุ่นและอังกฤษได้อย่างคล่องแคล่ว', 'MS'],
  [7, 'King', 'Robert', 'พนักงานขาย', '1980-05-29', '2014-01-02', 'Edgeham Hollow Winchester Way', 'London', 'UK', 'รับผิดชอบตลาดค้าส่งในสหราชอาณาจักร', 'RK'],
  [8, 'Callahan', 'Laura', 'ผู้ประสานงานขาย', '1978-01-09', '2014-03-05', '4726 - 11th Ave. N.E.', 'Seattle', 'USA', 'ดูแลระบบสต็อกสินค้าและคำสั่งซื้อออนไลน์', 'LC'],
  [9, 'Dodsworth', 'Anne', 'พนักงานขาย', '1986-01-27', '2014-11-15', '7 Houndstooth Rd.', 'London', 'UK', 'ดูแลตลาดภาคพื้นยุโรปและรัสเซีย', 'AD'],
  [10, 'Limpisthian', 'Wachira', 'ผู้ดูแลระบบระบบไอที', '1995-10-10', '2020-01-15', 'พิษณุโลก', 'พิษณุโลก', 'Thailand', 'ผู้พัฒนาและดูแล Northwind BMS', 'WL']
];
$stmtEmp = $pdo->prepare("INSERT INTO employees (employee_id, last_name, first_name, title, birth_date, hire_date, address, city, country, notes, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($emps as $e) $stmtEmp->execute($e);

// Customers
$custs = [
  ['ALFKI', 'Alfreds Futterkiste', 'Maria Anders', 'Sales Representative', 'Obere Str. 57', 'Berlin', 'Germany', '12209', '030-0074321'],
  ['ANATR', 'Ana Trujillo Emparedados y helados', 'Ana Trujillo', 'Owner', 'Avda. de la Constitución 2222', 'México D.F.', 'Mexico', '05021', '(5) 555-4729'],
  ['ANTON', 'Antonio Moreno Taquería', 'Antonio Moreno', 'Owner', 'Mataderos  2312', 'México D.F.', 'Mexico', '05023', '(5) 555-3932'],
  ['AROUT', 'Around the Horn', 'Thomas Hardy', 'Sales Representative', '120 Hanover Sq.', 'London', 'UK', 'WA1 1DP', '(171) 555-7788'],
  ['BERGS', 'Berglunds snabbköp', 'Christina Berglund', 'Order Administrator', 'Berguvsvägen  8', 'Luleå', 'Sweden', 'S-958 22', '0921-12 34 65'],
  ['BLAUS', 'Blauer See Delikatessen', 'Hanna Moos', 'Sales Representative', 'Forsterstr. 57', 'Mannheim', 'Germany', '68306', '0621-08460'],
  ['BLONP', 'Blondel père et fils', 'Frédérique Citeaux', 'Marketing Manager', '24, place Kléber', 'Strasbourg', 'France', '67000', '88.60.15.31'],
  ['BOLID', 'Bólido Comidas preparadas', 'Martín Sommer', 'Owner', 'C/ Araquil, 67', 'Madrid', 'Spain', '28023', '(91) 555 22 82'],
  ['BONAP', 'Bon app\'', 'Laurence Lebihan', 'Owner', '12, rue des Bouchers', 'Marseille', 'France', '13008', '91.24.45.40'],
  ['BOTTM', 'Bottom-Dollar Markets', 'Elizabeth Lincoln', 'Accounting Manager', '23 Tsawassen Blvd.', 'Tsawassen', 'Canada', 'T2F 8M4', '(604) 555-4729'],
  ['ERNSH', 'Ernst Handel', 'Roland Mendel', 'Sales Manager', 'Kirchgasse 6', 'Graz', 'Austria', '8010', '7675-3425'],
  ['SAVEA', 'Save-a-lot Markets', 'Jose Pavarotti', 'Purchasing Representative', '187 Suffolk Ln.', 'Boise', 'USA', '83720', '(208) 555-8097'],
  ['QUICK', 'QUICK-Stop', 'Horst Kloss', 'Accounting Manager', 'Taucherstraße 10', 'Cunewalde', 'Germany', '01307', '0372-035188']
];
$stmtCust = $pdo->prepare("INSERT INTO customers (customer_id, company_name, contact_name, contact_title, address, city, country, postal_code, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($custs as $cu) $stmtCust->execute($cu);

// Products
$prods = [
  [1, 'ชาไทย Chai', 1, 1, '10 กล่อง x 20 ซอง', 630.00, 39, 0, 10, 0],
  [2, 'น้ำเชื่อมอเนกประสงค์ Chang', 1, 1, '24 ขวด - 12 ออนซ์', 665.00, 17, 40, 25, 0],
  [3, 'น้ำเชื่อมผลไม้ Aniseed Syrup', 1, 2, '12 ขวด - 550 มล.', 350.00, 13, 70, 25, 0],
  [4, 'เครื่องเทศผสม Chef Anton\'s Cajun', 2, 2, '48 กระป๋อง - 6 ออนซ์', 770.00, 53, 0, 0, 0],
  [5, 'เครื่องปรุงรส Chef Anton\'s Gumbo', 2, 2, '36 กล่อง', 747.25, 0, 0, 0, 1],
  [6, 'แยมเบอร์รี่ Grandma\'s Boysenberry', 3, 2, '12 กระปุก - 8 ออนซ์', 875.00, 120, 0, 25, 0],
  [7, 'ลูกพลัมอบแห้ง Uncle Bob\'s', 3, 7, '12 แพ็ค - 1 ปอนด์', 1050.00, 15, 0, 10, 0],
  [8, 'ซอสแครนเบอร์รี่ Northwoods', 3, 2, '12 ขวด - 12 ออนซ์', 1400.00, 6, 0, 0, 0],
  [9, 'เนื้อโกเบ Mishi Kobe Niku', 4, 6, '18 ชิ้น - 500 กรัม', 3395.00, 29, 0, 0, 0],
  [10, 'ปลาเค็ม Ikura', 4, 8, '12 กระป๋อง - 500 มล.', 1085.00, 31, 0, 0, 0],
  [11, 'เค้กช็อกโกแลต Queso Cabrales', 5, 4, '1 กก. แพ็คเกจ', 735.00, 22, 30, 30, 0],
  [12, 'เนยแข็งสเปน Queso Manchego', 5, 4, '10 ชิ้น - 500 กรัม', 1330.00, 86, 0, 0, 0],
  [13, 'สาหร่ายทะเล Konbu', 6, 8, '2 กก. กล่อง', 210.00, 24, 0, 5, 0],
  [14, 'เต้าหู้หิมะ Tofu', 6, 7, '40 กล่อง - 100 กรัม', 813.75, 35, 0, 0, 0],
  [15, 'ซอสปรุงรส Genen Shouyu', 6, 2, '24 ขวด - 250 มล.', 542.50, 39, 0, 5, 0],
  [16, 'พัฟ Pavlova', 7, 3, '32 กล่อง - 500 กรัม', 610.75, 29, 0, 10, 0],
  [17, 'เนื้อแกะออสเตรเลีย Alice Mutton', 7, 6, '20 ถุง - 1 กก.', 1365.00, 0, 0, 0, 1],
  [18, 'สเต็กเนื้อ Carnarvon Tigers', 7, 8, '16 แพ็ค - 2 กก.', 2187.50, 42, 0, 0, 0],
  [19, 'บิสกิตชา Teatime Chocolate', 8, 3, '10 กล่อง x 12 ซอง', 322.00, 25, 0, 5, 0],
  [20, 'มาร์มาเลด Sir Rodney\'s', 8, 3, '30 กล่องขวัญ', 2835.00, 40, 0, 0, 0]
];
$stmtPrd = $pdo->prepare("INSERT INTO products (product_id, product_name, supplier_id, category_id, quantity_per_unit, unit_price, units_in_stock, units_on_order, reorder_level, discontinued) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($prods as $p) $stmtPrd->execute($p);

// Orders
$orders = [
  [10248, 'ALFKI', 5, '2024-07-04', '2024-08-01', '2024-07-16', 3, 1120.00, 'Alfreds Futterkiste', 'Obere Str. 57', 'Berlin', 'Germany', 'จัดส่งแล้ว'],
  [10249, 'ANATR', 6, '2024-07-05', '2024-08-16', '2024-07-10', 1, 402.50, 'Ana Trujillo Emparedados', 'Avda. de la Constitución 2222', 'México D.F.', 'Mexico', 'จัดส่งแล้ว'],
  [10250, 'ANTON', 4, '2024-07-08', '2024-08-05', '2024-07-12', 2, 2275.00, 'Antonio Moreno Taquería', 'Mataderos  2312', 'México D.F.', 'Mexico', 'จัดส่งแล้ว'],
  [10251, 'AROUT', 3, '2024-07-08', '2024-08-05', '2024-07-15', 1, 1435.00, 'Around the Horn', '120 Hanover Sq.', 'London', 'UK', 'จัดส่งแล้ว'],
  [10252, 'BERGS', 4, '2024-07-09', '2024-08-06', '2024-07-11', 2, 1785.00, 'Berglunds snabbköp', 'Berguvsvägen  8', 'Luleå', 'Sweden', 'จัดส่งแล้ว'],
  [10253, 'BLONP', 3, '2024-07-10', '2024-07-24', '2024-07-16', 2, 2030.00, 'Blondel père et fils', '24, place Kléber', 'Strasbourg', 'France', 'จัดส่งแล้ว'],
  [10254, 'BOLID', 5, '2024-07-11', '2024-08-08', '2024-07-23', 2, 805.00, 'Bólido Comidas preparadas', 'C/ Araquil, 67', 'Madrid', 'Spain', 'จัดส่งแล้ว'],
  [10255, 'BONAP', 9, '2024-07-12', '2024-08-09', '2024-07-15', 3, 5250.00, 'Bon app\'', '12, rue des Bouchers', 'Marseille', 'France', 'จัดส่งแล้ว'],
  [10256, 'ERNSH', 3, '2024-07-15', '2024-08-12', '2024-07-17', 2, 490.00, 'Ernst Handel', 'Kirchgasse 6', 'Graz', 'Austria', 'จัดส่งแล้ว'],
  [10257, 'SAVEA', 4, '2024-07-16', '2024-08-13', '2024-07-22', 3, 2870.00, 'Save-a-lot Markets', '187 Suffolk Ln.', 'Boise', 'USA', 'จัดส่งแล้ว'],
  [10258, 'QUICK', 1, '2024-10-18', '2024-11-15', null, 1, 1500.00, 'QUICK-Stop', 'Taucherstraße 10', 'Cunewalde', 'Germany', 'รอดำเนินการ'],
  [10259, 'ERNSH', 2, '2024-11-01', '2024-11-29', null, 2, 1200.00, 'Ernst Handel', 'Kirchgasse 6', 'Graz', 'Austria', 'กำลังเตรียมของ']
];
$stmtOrd = $pdo->prepare("INSERT INTO orders (order_id, customer_id, employee_id, order_date, required_date, shipped_date, ship_via, freight, ship_name, ship_address, ship_city, ship_country, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($orders as $o) $stmtOrd->execute($o);

// Order Details
$details = [
  [10248, 11, 490.00, 12, 0.00],
  [10248, 14, 650.00, 10, 0.00],
  [10248, 1, 630.00, 5, 0.00],
  [10249, 14, 650.00, 9, 0.00],
  [10249, 15, 542.50, 40, 0.00],
  [10250, 1, 630.00, 10, 0.00],
  [10250, 2, 665.00, 35, 0.15],
  [10251, 6, 875.00, 6, 0.05],
  [10251, 8, 1400.00, 15, 0.05],
  [10252, 9, 3395.00, 40, 0.05],
  [10252, 1, 630.00, 25, 0.05],
  [10253, 11, 735.00, 40, 0.00],
  [10254, 4, 770.00, 21, 0.15],
  [10255, 16, 610.75, 20, 0.00],
  [10255, 18, 2187.50, 25, 0.00],
  [10256, 1, 630.00, 15, 0.00],
  [10257, 12, 1330.00, 6, 0.00],
  [10257, 19, 322.00, 15, 0.00],
  [10258, 1, 630.00, 20, 0.00],
  [10258, 2, 665.00, 15, 0.00],
  [10259, 10, 1085.00, 10, 0.00]
];
$stmtDet = $pdo->prepare("INSERT INTO order_details (order_id, product_id, unit_price, quantity, discount) VALUES (?, ?, ?, ?, ?)");
foreach ($details as $d) $stmtDet->execute($d);

echo "Database initialized successfully in SQLite!\n";

