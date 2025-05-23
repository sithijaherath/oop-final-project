<?php

namespace App\Classes;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static array $config;

    private function __construct() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::loadConfig();
            try {
                // First try to connect to MySQL server without selecting a database
                $pdo = new PDO(
                    "mysql:host=" . self::$config['host'] . ";charset=" . self::$config['charset'],
                    self::$config['username'],
                    self::$config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

                // Check if database exists
                $dbname = self::$config['dbname'];
                $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
                
                if (!$result->fetch()) {
                    // Database doesn't exist, create it and its tables
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
                    $pdo->exec("USE $dbname");
                    
                    // Create tables
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS users (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            username VARCHAR(255) NOT NULL UNIQUE,
                            password_hash VARCHAR(255) NOT NULL,
                            encryption_key TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS passwords (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            website VARCHAR(255) NOT NULL,
                            password TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        )
                    ");
                }

                // Connect to the database
                self::$instance = new PDO(
                    "mysql:host=" . self::$config['host'] . 
                    ";dbname=" . self::$config['dbname'] . 
                    ";charset=" . self::$config['charset'],
                    self::$config['username'],
                    self::$config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private static function loadConfig(): void {
        self::$config = require __DIR__ . '/../../config/database.php';
    }
} 