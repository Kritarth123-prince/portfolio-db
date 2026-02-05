<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['csrf_token'] ?? '';
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Session expired. Please refresh and try again.';
    } elseif (!$current || !$new || !$confirm) {
        $error = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } else {
        try {
            $db  = new Database();
            $pdo = $db->connect();
            if (!$pdo) {
                throw new Exception('Database connection failed.');
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $username = $_SESSION['admin_user'] ?? '';
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                // removed updated_at to avoid missing-column errors
                $upd = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
                $upd->execute([$newHash, $user['id']]);
                $success = 'Password updated successfully.';
            }
        } catch (Exception $e) {
            error_log('Change password error: ' . $e->getMessage());
            $error = 'Error updating password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <style>
        .login-card h2 { margin-bottom: 1rem; }
        .success-message, .error-message {
            padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; font-weight: 600;
        }
        .success-message { background:#f0fff4; color:#22543d; border:1px solid #48bb78; }
        .error-message   { background:#fff5f5; color:#742a2a; border:1px solid #f56565; }
        .back-links { margin-top: 15px; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        .back-links a { color:#4a5568; text-decoration:none; font-weight:600; }
        .back-links a:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>Change Password</h2>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <input type="password" name="current_password" placeholder="Current Password" required class="form-input" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <input type="password" name="new_password" placeholder="New Password" required class="form-input" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required class="form-input" autocomplete="new-password">
                </div>
                <button type="submit" class="login-btn">Update Password</button>
            </form>

            <div class="back-links">
                <a href="index.php">← Back to Dashboard</a>
                <a href="logout.php" onclick="return confirm('Logout now?')">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>