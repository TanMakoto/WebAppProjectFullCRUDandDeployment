<?php
/**
 * Shipper Model (PDO OOP)
 */
class Shipper {
    private $conn;
    private $table = "shippers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $sql = "SELECT s.*, COUNT(o.order_id) as shipment_count 
                FROM {$this->table} s 
                LEFT JOIN orders o ON s.shipper_id = o.ship_via 
                GROUP BY s.shipper_id 
                ORDER BY s.shipper_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE shipper_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (company_name, phone, icon) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['company_name'],
            $data['phone'] ?? '',
            $data['icon'] ?? '🚚'
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET company_name = ?, phone = ?, icon = ? WHERE shipper_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['company_name'],
            $data['phone'] ?? '',
            $data['icon'] ?? '🚚',
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE shipper_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

