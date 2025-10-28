<?php

namespace Config;

class Database {
    private $host;
    private $dbName;
    private $username;
    private $password;
    private $connection;

    public function __construct($host, $dbName, $username, $password) {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    private function connect() {
        try {
            $host = $this->host;
            $port = null;
            // Support host:port format
            if (strpos($host, ':') !== false && substr($host, 0, 1) !== '/') {
                [$hostOnly, $portPart] = explode(':', $host, 2);
                if (ctype_digit($portPart)) {
                    $host = $hostOnly;
                    $port = (int)$portPart;
                }
            }

            $dsn = "mysql:host={$host};dbname={$this->dbName};charset=utf8mb4";
            if ($port) {
                $dsn = "mysql:host={$host};port={$port};dbname={$this->dbName};charset=utf8mb4";
            }

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new \PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}