<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require_once __DIR__ . '/../config/database.php';
require_once '../config/database.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $database = new Database();
            $conn = $database->connect();
            
            $stmt = $conn->prepare("SELECT id, username, password_hash, email, is_active, last_login FROM admin_users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_login_time'] = time();
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];

                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                header("Location: index.php");
                exit;
            } else {
                $loginError = "Invalid username or password. Please try again.";
            }
           
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            // $loginError = "An error occurred. Please try again later.";
            $loginError = "PDO Error: " . $e->getMessage();
        }
    } else {
        $loginError = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Admin - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Admin Login</h2>
                <p>Access Portfolio Management System</p>
            </div>
            
            <?php if (isset($loginError)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($loginError) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" placeholder="Username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" placeholder="Password" required class="form-input">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="login-btn">
                    <span class="btn-text">Login to Dashboard</span>
                    <i class="fas fa-arrow-right btn-icon"></i>
                </button>
            </form>
            
            <div class="login-footer">
                <p>Portfolio CMS v1.0</p>
                <div class="social-links">
                    <a href="https://github.com/Kritarth123-prince" target="_blank" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="https://www.linkedin.com/in/kritarth-ranjan" target="_blank" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="background-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>

    <script src="js/login.js"></script>
</body>
</html>