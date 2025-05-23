<?php

namespace App\Classes;

use PDO;
use PDOException;

class Password {
    private PDO $db;
    private string $key;

    public function __construct(string $key) {
        $this->db = Database::getInstance();
        $this->key = $key;
    }

    public function save(int $userId, string $website, string $password): bool {
        try {
            $encryptedPassword = $this->encrypt($password);
            $stmt = $this->db->prepare(
                "INSERT INTO passwords (user_id, website, password, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            return $stmt->execute([$userId, $website, $encryptedPassword]);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    public function getAll(int $userId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, website, password, created_at 
                 FROM passwords 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC"
            );
            $stmt->execute([$userId]);
            $passwords = $stmt->fetchAll();

            // Decrypt passwords
            return array_map(function($row) {
                $row['password'] = $this->decrypt($row['password']);
                return $row;
            }, $passwords);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    private function encrypt(string $password): string {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $this->key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $encryptedPassword): string {
        $data = base64_decode($encryptedPassword);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, 0, $iv);
    }
} 