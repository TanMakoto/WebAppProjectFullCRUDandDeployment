<?php
/**
 * Order Model (PDO OOP)
 */
class Order {
    private $conn;
    private $table = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "(o.order_id LIKE ? OR c.company_name LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
            $sp = "%" . $filters['search'] . "%";
            $params[] = $sp;
            $params[] = $sp;
            $params[] = $sp;
            $params[] = $sp;
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['customer_id'])) {
            $where[] = "o.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} o 
                     LEFT JOIN customers c ON o.customer_id = c.customer_id 
                     LEFT JOIN employees e ON o.employee_id = e.employee_id 
                     {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Query orders with aggregated items and total amount
        $sql = "SELECT o.*, 
                       c.company_name as customer_name,
                       (e.first_name || ' ' || e.last_name) as employee_name,
                       s.company_name as shipper_name,
                       COUNT(od.product_id) as item_count,
                       COALESCE(SUM(od.quantity * od.unit_price * (1.0 - od.discount)), 0) + o.freight as total_amount
                FROM {$this->table} o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN employees e ON o.employee_id = e.employee_id
                LEFT JOIN shippers s ON o.ship_via = s.shipper_id
                LEFT JOIN order_details od ON o.order_id = od.order_id
                {$whereClause}
                GROUP BY o.order_id
                ORDER BY o.order_id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($idx++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => ceil($total / max(1, $limit))
        ];
    }

    public function getById($id) {
        $sql = "SELECT o.*, 
                       c.company_name as customer_name, c.contact_name, c.phone as customer_phone, c.address as customer_address,
                       (e.first_name || ' ' || e.last_name) as employee_name,
                       s.company_name as shipper_name, s.phone as shipper_phone
                FROM {$this->table} o
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN employees e ON o.employee_id = e.employee_id
                LEFT JOIN shippers s ON o.ship_via = s.shipper_id
                WHERE o.order_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) return null;

        // Fetch Order Details
        $itemSql = "SELECT od.*, p.product_name, p.quantity_per_unit,
                           (od.quantity * od.unit_price * (1.0 - od.discount)) as subtotal
                    FROM order_details od
                    JOIN products p ON od.product_id = p.product_id
                    WHERE od.order_id = ?";
        $itemStmt = $this->conn->prepare($itemSql);
        $itemStmt->execute([$id]);
        $order['items'] = $itemStmt->fetchAll();

        // Calculate subtotal, freight, grand total
        $subtotal = 0;
        foreach ($order['items'] as $item) {
            $subtotal += (float)$item['subtotal'];
        }
        $order['subtotal'] = $subtotal;
        $order['total_amount'] = $subtotal + (float)$order['freight'];

        return $order;
    }

    public function create($data) {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO {$this->table} 
                    (customer_id, employee_id, order_date, required_date, ship_via, freight, ship_name, ship_address, ship_city, ship_country, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['customer_id'],
                $data['employee_id'] ?? 1,
                $data['order_date'] ?? date('Y-m-d'),
                $data['required_date'] ?? date('Y-m-d', strtotime('+14 days')),
                $data['ship_via'] ?? 1,
                (float)($data['freight'] ?? 0),
                $data['ship_name'] ?? '',
                $data['ship_address'] ?? '',
                $data['ship_city'] ?? '',
                $data['ship_country'] ?? '',
                $data['status'] ?? 'รอดำเนินการ'
            ]);
            $orderId = $this->conn->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $itemSql = "INSERT INTO order_details (order_id, product_id, unit_price, quantity, discount) VALUES (?, ?, ?, ?, ?)";
                $itemStmt = $this->conn->prepare($itemSql);
                foreach ($data['items'] as $it) {
                    $itemStmt->execute([
                        $orderId,
                        $it['product_id'],
                        (float)$it['unit_price'],
                        (int)$it['quantity'],
                        (float)($it['discount'] ?? 0)
                    ]);
                }
            }

            $this->conn->commit();
            return $orderId;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = ?, shipped_date = ? WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $shippedDate = ($status === 'จัดส่งแล้ว') ? date('Y-m-d') : null;
        return $stmt->execute([$status, $shippedDate, $id]);
    }

    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            $delItems = $this->conn->prepare("DELETE FROM order_details WHERE order_id = ?");
            $delItems->execute([$id]);
            $delOrder = $this->conn->prepare("DELETE FROM {$this->table} WHERE order_id = ?");
            $delOrder->execute([$id]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }
}

