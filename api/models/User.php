<?php
/**
 * User Model (PDO OOP)
 */
class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsernameOrEmail($identity) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$identity, $identity]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $sql = "SELECT id, username, email, full_name, role, avatar, created_at FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (username, email, password, full_name, role, avatar) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashed,
            $data['full_name'],
            $data['role'] ?? 'ผู้ดูแลระบบ',
            $data['avatar'] ?? 'วช'
        ]);
        return $this->conn->lastInsertId();
    }
}

