<?php
session_start();
require_once __DIR__ . '/../src/autoload.php';

use App\Classes\User;

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'register') {
            if ($user->register($username, $password)) {
                $_SESSION['success'] = 'Registration successful. Please login.';
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Username already exists.';
                header('Location: index.php');
                exit;
            }
        } elseif ($action === 'login') {
            if ($user->login($username, $password)) {
                // Session variables are now set in the User::login method
                header('Location: dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = 'Invalid username or password.';
                header('Location: index.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

header('Location: index.php');
exit; 