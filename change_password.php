<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_pwd = $_POST['current_password'];
    $new_pwd     = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
        $error = "Please fill in all fields.";
    } elseif ($new_pwd !== $confirm_pwd) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT user_password FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();

            if ($current_pwd === $row['user_password'] || password_verify($current_pwd, $row['user_password'])) {
                $hashed_pwd = password_hash($new_pwd, PASSWORD_BCRYPT);
                
                $upd = $conn->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
                $upd->bind_param("si", $hashed_pwd, $user_id);
                $upd->execute();
                $upd->close();

                $action = "User changed password via account settings.";
                $audit = $conn->prepare("INSERT INTO audit_log (user_id, action_perform) VALUES (?, ?)");
                $audit->bind_param("is", $user_id, $action);
                $audit->execute();
                $audit->close();

                $success = "Password changed successfully!";
            } else {
                $error = "Incorrect current password.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinSights - Account Settings</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background-color: #1e293b; border-right: 1px solid #334155; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; }
        .brand { font-size: 22px; font-weight: bold; color: #38bdf8; margin-bottom: 30px; }
        .nav-list { list-style: none; }
        .nav-item { margin-bottom: 12px; }
        .nav-link { color: #cbd5e1; text-decoration: none; font-size: 14px; padding: 10px 12px; display: block; border-radius: 6px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #0284c7; color: #fff; }
        
        .main-content { flex: 1; padding: 32px; overflow-y: auto; }
        .header { margin-bottom: 28px; }
        .header h1 { font-size: 24px; color: #f8fafc; }
        .header p { font-size: 14px; color: #94a3b8; margin-top: 4px; }
        
        .form-card { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 28px; max-width: 500px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; margin-bottom: 6px; color: #cbd5e1; }
        .form-group input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #475569; background-color: #0f172a; color: #fff; font-size: 14px; outline: none; }
        .btn { padding: 12px 24px; background-color: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; }
        .btn:hover { background-color: #0369a1; }
        
        .error-msg { background-color: #7f1d1d; color: #fca5a5; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; }
        .success-msg { background-color: #064e3b; color: #6ee7b7; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; }
        
        .reset-link-box { margin-top: 24px; padding-top: 20px; border-top: 1px solid #334155; font-size: 13px; color: #94a3b8; }
        .reset-link-box a { color: #38bdf8; text-decoration: none; font-weight: 600; }
        .logout-btn { background-color: #ef4444; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600; text-align: center; text-decoration: none; display: block; margin-top: auto; }
        .logout-btn:hover { background-color: #dc2626; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div>
            <div class="brand">SkinSights</div>
            <ul class="nav-list">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Analysis Logs</a></li>
                <li class="nav-item"><a href="#" class="nav-link">User Management</a></li>
                <li class="nav-item"><a href="change_password.php" class="nav-link active">Account Settings</a></li>
            </ul>
        </div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Account Settings</h1>
            <p>Update your authentication credentials and security configuration.</p>
        </div>

        <div class="form-card">
            <?php if ($error): ?><div class="error-msg"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="success-msg"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <form method="POST" action="change_password.php">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>

            <div class="reset-link-box">
                Forgot your password or need external OTP reset? <a href="reset_password.php">Go to Reset Password Module</a>
            </div>
        </div>
    </main>

</body>
</html>