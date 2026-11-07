<?php
/**
 * Customer Model (PDO OOP)
 */
class Customer {
    private $conn;
    private $table = "customers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '', $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = "";

        if (!empty($search)) {
            $where = "WHERE company_name LIKE ? OR contact_name LIKE ? OR customer_id LIKE ? OR city LIKE ? OR country LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
        }

        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$where}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        $sql = "SELECT c.*, COUNT(o.order_id) as order_count 
                FROM {$this->table} c 
                LEFT JOIN orders o ON c.customer_id = o.customer_id 
                {$where} 
                GROUP BY c.customer_id 
                ORDER BY c.company_name ASC 
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
            'limit' => (int)$limit
        ];
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (customer_id, company_name, contact_name, contact_title, address, city, country, postal_code, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            strtoupper(trim($data['customer_id'])),
            $data['company_name'],
            $data['contact_name'] ?? '',
            $data['contact_title'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['postal_code'] ?? '',
            $data['phone'] ?? ''
        ]);
        return $data['customer_id'];
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                company_name = ?, contact_name = ?, contact_title = ?, address = ?, city = ?, country = ?, postal_code = ?, phone = ? 
                WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['company_name'],
            $data['contact_name'] ?? '',
            $data['contact_title'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['postal_code'] ?? '',
            $data['phone'] ?? '',
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

