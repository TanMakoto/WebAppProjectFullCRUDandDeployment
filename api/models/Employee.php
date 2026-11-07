<?php
/**
 * Employee Model (PDO OOP)
 */
class Employee {
    private $conn;
    private $table = "employees";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $sql = "SELECT e.*, COUNT(o.order_id) as order_count 
                FROM {$this->table} e 
                LEFT JOIN orders o ON e.employee_id = o.employee_id 
                GROUP BY e.employee_id 
                ORDER BY e.employee_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE employee_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $initials = strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1));
        $sql = "INSERT INTO {$this->table} 
                (last_name, first_name, title, birth_date, hire_date, address, city, country, notes, avatar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['last_name'],
            $data['first_name'],
            $data['title'] ?? '',
            !empty($data['birth_date']) ? $data['birth_date'] : null,
            !empty($data['hire_date']) ? $data['hire_date'] : null,
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['notes'] ?? '',
            $data['avatar'] ?? $initials
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data) {
        $initials = strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1));
        $sql = "UPDATE {$this->table} SET 
                last_name = ?, first_name = ?, title = ?, birth_date = ?, hire_date = ?, 
                address = ?, city = ?, country = ?, notes = ?, avatar = ? 
                WHERE employee_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['last_name'],
            $data['first_name'],
            $data['title'] ?? '',
            !empty($data['birth_date']) ? $data['birth_date'] : null,
            !empty($data['hire_date']) ? $data['hire_date'] : null,
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['country'] ?? '',
            $data['notes'] ?? '',
            $data['avatar'] ?? $initials,
            $id
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE employee_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}

