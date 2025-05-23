<?php

namespace App\Classes;

use PDO;
use PDOException;

class User {
    private PDO $db;
    private string $username;
    private string $passwordHash;
    private ?string $key = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register(string $username, string $password): bool {
        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return false;
            }

            // Generate encryption key
            $key = openssl_random_pseudo_bytes(32);
            // Encrypt key with user's plain password
            $encryptedKey = $this->encryptKey($key, $password);
            
            // Hash the password for storage
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, encryption_key) VALUES (?, ?, ?)");
            return $stmt->execute([$username, $passwordHash, $encryptedKey]);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    public function login(string $username, string $password): bool {
        try {
            $stmt = $this->db->prepare("SELECT id, password_hash, encryption_key FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return false;
            }

            $this->username = $username;
            $this->passwordHash = $user['password_hash'];
            $this->key = $this->decryptKey($user['encryption_key'], $password);
            
            // Store user ID in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['encryption_key'] = $this->key;

            return true;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    private function encryptKey(string $key, string $password): string {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($key, 'AES-256-CBC', $password, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decryptKey(string $encryptedKey, string $password): string {
        $data = base64_decode($encryptedKey);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $password, 0, $iv);
    }

    public function getKey(): ?string {
        return $this->key;
    }

    public function getUsername(): string {
        return $this->username;
    }
} 