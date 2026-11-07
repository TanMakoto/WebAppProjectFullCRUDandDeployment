<?php
/**
 * Category Model (PDO OOP)
 */
class Category {
    private $conn;
    private $table = "categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        // Include product count per category
        $sql = "SELECT c.*, COUNT(p.product_id) AS product_count 
                FROM {$this->table} c 
                LEFT JOIN products p ON c.category_id = p.category_id 
                GROUP BY c.category_id 
                ORDER BY c.category_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE category_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (category_name, description, icon) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['category_name'],
            $data['description'] ?? '',
            $data['icon'] ?? '📦'
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET category_name = ?, description = ?, icon = ? WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['category_name'],
            $data['description'] ?? '',
            $data['icon'] ?? '📦',
            $id
        ]);
    }

    public function delete($id) {
        // Disassociate products first or prevent delete if products exist
        $sql = "DELETE FROM {$this->table} WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

