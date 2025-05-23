<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../src/autoload.php';

use App\Classes\Password;
use App\Classes\PasswordGenerator;
use App\Classes\Database;

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['encryption_key'])) {
    header('Location: index.php');
    exit;
}

// Debug database connection and user existence
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        echo "<!-- User found in database: ID=" . $user['id'] . ", Username=" . $user['username'] . " -->\n";
    } else {
        $_SESSION['error'] = 'User not found in database. Please try logging in again.';
        header('Location: logout.php');
        exit;
    }
} catch (Exception $e) {
    echo "<!-- Database error: " . htmlspecialchars($e->getMessage()) . " -->\n";
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

$password_manager = new Password($_SESSION['encryption_key']);
$password_generator = new PasswordGenerator();

// Debug information
echo "<!-- Debug Info: -->\n";
echo "<!-- User ID: " . $_SESSION['user_id'] . " -->\n";
echo "<!-- Encryption Key Set: " . (isset($_SESSION['encryption_key']) ? 'Yes' : 'No') . " -->\n";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_and_save'])) {
        $website = $_POST['website'] ?? '';
        $length = (int)$_POST['length'];
        $lowercase = (int)$_POST['lowercase'];
        $uppercase = (int)$_POST['uppercase'];
        $numbers = (int)$_POST['numbers'];
        $special = (int)$_POST['special'];
        
        try {
            // Generate password
            $password = $password_generator->generate($length, $lowercase, $uppercase, $numbers, $special);
            
            // Save password if website is provided
            if ($website) {
                if ($password_manager->save($_SESSION['user_id'], $website, $password)) {
                    $_SESSION['success'] = 'Password generated and saved successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to save password.';
                }
            }
            $_SESSION['generated_password'] = $password;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: dashboard.php');
        exit;
    }
}

// Get stored passwords
try {
    $stored_passwords = $password_manager->getAll($_SESSION['user_id']);
    echo "<!-- Number of stored passwords: " . count($stored_passwords) . " -->\n";
} catch (Exception $e) {
    $stored_passwords = [];
    $_SESSION['error'] = 'Failed to retrieve passwords: ' . $e->getMessage();
    echo "<!-- Error retrieving passwords: " . htmlspecialchars($e->getMessage()) . " -->\n";
}

// Show any error messages
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Show success messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Password Manager Dashboard</h3>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                    <div class="card-body">
                        <h4>Generate & Save Password</h4>
                        <form method="post" class="mb-4">
                            <div class="mb-3">
                                <label for="website" class="form-label">Website/Application</label>
                                <input type="text" class="form-control" id="website" name="website" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="length" class="form-label">Length</label>
                                    <input type="number" class="form-control" id="length" name="length" value="12" min="4" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="lowercase" class="form-label">Lowercase</label>
                                    <input type="number" class="form-control" id="lowercase" name="lowercase" value="3" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="uppercase" class="form-label">Uppercase</label>
                                    <input type="number" class="form-control" id="uppercase" name="uppercase" value="3" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="numbers" class="form-label">Numbers</label>
                                    <input type="number" class="form-control" id="numbers" name="numbers" value="3" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="special" class="form-label">Special</label>
                                    <input type="number" class="form-control" id="special" name="special" value="3" min="0" required>
                                </div>
                            </div>
                            <button type="submit" name="generate_and_save" class="btn btn-primary mt-3">Generate & Save Password</button>
                        </form>

                        <?php if (isset($_SESSION['generated_password'])): ?>
                        <div class="alert alert-success">
                            Generated Password: <strong><?= htmlspecialchars($_SESSION['generated_password']) ?></strong>
                        </div>
                        <?php endif; ?>

                        <h4>Stored Passwords</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Website/Application</th>
                                        <th>Password</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stored_passwords as $stored): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stored['website']) ?></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="password" class="form-control password-field" 
                                                       value="<?= htmlspecialchars($stored['password']) ?>" readonly>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    Show
                                                </button>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($stored['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    this.textContent = 'Show';
                }
            });
        });
    </script>
</body>
</html> 