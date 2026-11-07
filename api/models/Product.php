<?php
/**
 * Product Model (PDO OOP Style with Prepared Statements & Parameter Binding)
 */
class Product {
    private $conn;
    private $table = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "p.product_name LIKE ?";
            $params[] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['supplier_id'])) {
            $where[] = "p.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        if (isset($filters['discontinued']) && $filters['discontinued'] !== '') {
            $where[] = "p.discontinued = ?";
            $params[] = (int)$filters['discontinued'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'in_stock') {
                $where[] = "p.units_in_stock > 10 AND p.discontinued = 0";
            } elseif ($filters['status'] === 'low_stock') {
                $where[] = "p.units_in_stock > 0 AND p.units_in_stock <= 10 AND p.discontinued = 0";
            } elseif ($filters['status'] === 'out_of_stock') {
                $where[] = "p.units_in_stock = 0 AND p.discontinued = 0";
            } elseif ($filters['status'] === 'discontinued') {
                $where[] = "p.discontinued = 1";
            }
        }

        $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} p {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Data query
        $sql = "SELECT p.*, c.category_name, c.icon as category_icon, s.company_name as supplier_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                {$whereClause} 
                ORDER BY p.product_id ASC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind parameters safely
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
        $sql = "SELECT p.*, c.category_name, s.company_name as supplier_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                WHERE p.product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (product_name, supplier_id, category_id, quantity_per_unit, unit_price, units_in_stock, units_on_order, reorder_level, discontinued) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['product_name'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            !empty($data['category_id']) ? (int)$data['category_id'] : null,
            $data['quantity_per_unit'] ?? '',
            (float)($data['unit_price'] ?? 0),
            (int)($data['units_in_stock'] ?? 0),
            (int)($data['units_on_order'] ?? 0),
            (int)($data['reorder_level'] ?? 0),
            (int)($data['discontinued'] ?? 0)
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                product_name = ?, supplier_id = ?, category_id = ?, quantity_per_unit = ?, 
                unit_price = ?, units_in_stock = ?, units_on_order = ?, reorder_level = ?, discontinued = ? 
                WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['product_name'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            !empty($data['category_id']) ? (int)$data['category_id'] : null,
            $data['quantity_per_unit'] ?? '',
            (float)($data['unit_price'] ?? 0),
            (int)($data['units_in_stock'] ?? 0),
            (int)($data['units_on_order'] ?? 0),
            (int)($data['reorder_level'] ?? 0),
            (int)($data['discontinued'] ?? 0),
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

