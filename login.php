<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, user_password, user_status_id, user_type_id FROM user WHERE user_username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['user_password'])) {
                if ($user['user_status_id'] == 1) { // Active status
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type_id'] = $user['user_type_id'];

                    if ($user['user_type_id'] == 1) {
                        header("Location: admin_panel.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Account is not active.";
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Username not found.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SkinSights</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0b132b; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-card { background-color: #111c3a; padding: 40px; border-radius: 12px; border: 1px solid #1e293b; box-shadow: 0 8px 16px rgba(0,0,0,0.3); width: 420px; text-align: center; }
        h2 { color: #3b82f6; font-size: 24px; margin-bottom: 5px; font-weight: bold; }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 6px; font-weight: 500; color: #94a3b8; font-size: 13px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; box-sizing: border-box; background-color: #0b132b; border: 1px solid #1e293b; border-radius: 6px; color: #fff; font-size: 14px; outline: none; transition: 0.2s; }
        input[type="text"]::placeholder, input[type="password"]::placeholder { color: #475569; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #3b82f6; }
        button { width: 100%; padding: 12px; background-color: #0284c7; border: none; color: white; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 15px; transition: 0.2s; margin-top: 5px; }
        button:hover { background-color: #0369a1; }
        .footer-links { text-align: left; margin-top: 25px; font-size: 13px; }
        .footer-links a { color: #3b82f6; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }
        .error { background-color: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; text-align: left; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>SkinSights</h2>
    <div class="subtitle">Sign in to access your account</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit">Sign In</button>
    </form>

    <div class="footer-links">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
</div>

</body>
</html>