<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type_id'] ?? 3) == 1;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!empty($current_pass) && !empty($new_pass) && !empty($confirm_pass)) {
        if ($new_pass === $confirm_pass) {
            $stmt = $conn->prepare("SELECT user_password FROM user WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res && password_verify($current_pass, $res['user_password'])) {
                $hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
                $update->bind_param("si", $hashed_new, $user_id);
                if ($update->execute()) {
                    $success = "Password updated successfully.";
                } else {
                    $error = "Failed to update password in database.";
                }
            } else {
                $error = "Incorrect current password.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Please fill in all password fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings - SkinSights</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0b132b; color: #fff; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Styling */
        .sidebar { width: 250px; background-color: #070a1a; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 20px; border-right: 1px solid #1e293b; }
        .sidebar-brand { font-size: 20px; font-weight: bold; color: #3b82f6; margin-bottom: 40px; }
        .nav-links { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-link { color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; transition: 0.2s; }
        .nav-link:hover { color: #fff; }
        .nav-link.active { background-color: #0284c7; color: #fff; }
        .logout-btn { background-color: #ef4444; color: white; text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: bold; display: block; font-size: 14px; }
        .logout-btn:hover { background-color: #dc2626; }

        /* Main Content Area */
        .main-content { flex: 1; padding: 40px 50px; overflow-y: auto; background-color: #0b132b; }
        h2 { font-size: 26px; font-weight: 600; margin-bottom: 6px; color: #f8fafc; }
        p.subtitle { color: #94a3b8; margin-bottom: 30px; font-size: 14px; }

        /* Card Container */
        .card { background-color: #111c3a; padding: 35px; border-radius: 10px; border: 1px solid #1e293b; max-width: 540px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        
        label { display: block; color: #cbd5e1; font-size: 13px; margin-bottom: 6px; font-weight: 400; }
        input { background-color: #0b132b; color: #fff; border: 1px solid #1e293b; padding: 12px 14px; border-radius: 6px; width: 100%; margin-bottom: 20px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #3b82f6; }
        input::placeholder { color: #475569; }

        button { background-color: #0284c7; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background-color 0.2s; width: auto; }
        button:hover { background-color: #0369a1; }

        .footer-note { margin-top: 25px; font-size: 12px; color: #94a3b8; line-height: 1.5; }
        .footer-note a { color: #3b82f6; text-decoration: none; font-weight: 500; }
        .footer-note a:hover { text-decoration: underline; }

        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; max-width: 540px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; max-width: 540px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="sidebar-brand">SkinSights</div>
        <div class="nav-links">
            <?php if ($is_admin): ?>
                <a href="admin_panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">Admin Panel</a>
            <?php endif; ?>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="manage_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_records.php' ? 'active' : ''; ?>">Analysis Logs</a>
            <?php if ($is_admin): ?>
                <a href="user_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>">User Management</a>
            <?php endif; ?>
            <a href="change_password.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'active' : ''; ?>">Account Settings</a>
        </div>
    </div>
    <div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</div>

<div class="main-content">
    <h2>Account Settings</h2>
    <p class="subtitle">Update your authentication credentials and security configuration.</p>

    <?php if ($success): ?><div class="alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Enter current password" required>

            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" required>

            <button type="submit">Update Password</button>

            <div class="footer-note">
                Forgot your password or need external OTP reset? <a href="#">Go to Reset Password Module</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>