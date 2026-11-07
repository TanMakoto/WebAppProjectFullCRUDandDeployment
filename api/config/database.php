<?php
/**
 * Database Connection Class (PDO OOP Style)
 * CPE PSRU Web Application Project - Northwind BMS
 */

class Database {
    // Default / Local credentials
    private $host = "localhost";
    private $db_name = "northwind";
    private $username = "root";
    private $password = "root";
    private $port = "3306";
    private $charset = "utf8mb4";
    public $conn = null;

    public function __construct() {
        // Read configuration from environment variables if set (for hosting/production)
        if (getenv("DB_HOST")) $this->host = getenv("DB_HOST");
        if (getenv("DB_NAME")) $this->db_name = getenv("DB_NAME");
        if (getenv("DB_USER")) $this->username = getenv("DB_USER");
        if (getenv("DB_PASS") !== false) $this->password = getenv("DB_PASS");
        if (getenv("DB_PORT")) $this->port = getenv("DB_PORT");

        // Auto-detect InfinityFree hosting environment
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $isLiveHosting = (
            strpos($httpHost, 'infinityfree') !== false ||
            strpos($httpHost, 'epizy') !== false ||
            strpos($serverName, 'infinityfree') !== false ||
            strpos($serverName, 'epizy') !== false ||
            (!empty($httpHost) && strpos($httpHost, 'localhost') === false && strpos($httpHost, '127.0.0.1') === false)
        );

        if ($isLiveHosting) {
            $this->host = "sql103.infinityfree.com";
            $this->db_name = "if0_43378983_northwind";
            $this->username = "if0_43378983";
            $this->password = "XecsazwH3HG0t";
            $this->port = "3306";
        }
    }

    /**
     * Get PDO Database Connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        // Try MySQL connection
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Attempt connection
            try {
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // If password failed on localhost, try empty password for XAMPP
                if ($this->host === "localhost" && $this->password === "root") {
                    $this->conn = new PDO($dsn, $this->username, "", $options);
                    $this->password = "";
                } else {
                    throw $e;
                }
            }
        } catch (PDOException $exception) {
            // Check for SQLite fallback for instant zero-config offline testing
            $sqlitePath = __DIR__ . '/../../database/northwind.sqlite';
            if (file_exists($sqlitePath)) {
                try {
                    $this->conn = new PDO("sqlite:" . $sqlitePath, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    return $this->conn;
                } catch (Exception $sqe) {}
            }

            // Return JSON error response if database connection fails
            http_response_code(500);
            echo json_encode([
                "status" => false,
                "message" => "Database Connection Error: " . $exception->getMessage(),
                "hint" => "Please make sure MySQL is running and database is imported."
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        return $this->conn;
    }
}
