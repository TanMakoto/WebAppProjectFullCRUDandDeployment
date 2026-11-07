<?php
/**
 * Supplier Model (PDO OOP)
 */
class Supplier {
    private $conn;
    private $table = "suppliers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '') {
        $params = [];
        $where = "";

        if (!empty($search)) {
            $where = "WHERE s.company_name LIKE ? OR s.contact_name LIKE ? OR s.city LIKE ? OR s.country LIKE ?";
            $p = "%{$search}%";
            $params = [$p, $p, $p, $p];
        }

        $sql = "SELECT s.*, COUNT(p.product_id) as product_count 
                FROM {$this->table} s 
                LEFT JOIN products p ON s.supplier_id = p.supplier_id 
                {$where} 
                GROUP BY s.supplier_id 
                ORDER BY s.company_name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE supplier_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (company_name, contact_name, contact_title, address, city, country, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['company_name'],
            $data['contact_name'] ?? '',
            $data['contact_title'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['phone'] ?? ''
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                company_name = ?, contact_name = ?, contact_title = ?, address = ?, city = ?, country = ?, phone = ? 
                WHERE supplier_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['company_name'],
            $data['contact_name'] ?? '',
            $data['contact_title'] ?? '',
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['phone'] ?? '',
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE supplier_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

