<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Query user table
        $stmt = $conn->prepare("SELECT user_id, user_password, user_status_id FROM user WHERE user_username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Verify password hash or plain-text input
            if ($password === $row['user_password'] || password_verify($password, $row['user_password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $username;

                // Log session in user_session table
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $sess_stmt = $conn->prepare("INSERT INTO user_session (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $sess_stmt->bind_param("iss", $row['user_id'], $ip_address, $user_agent);
                $sess_stmt->execute();
                $sess_stmt->close();

                // Log event in audit_log table
                $action = "User logged in successfully.";
                $audit_stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_perform) VALUES (?, ?)");
                $audit_stmt->bind_param("is", $row['user_id'], $action);
                $audit_stmt->execute();
                $audit_stmt->close();

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Username not found.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinSights - Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background-color: #1e293b; border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        .card h1 { font-size: 24px; text-align: center; margin-bottom: 8px; color: #38bdf8; }
        .card p { font-size: 14px; text-align: center; color: #94a3b8; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; margin-bottom: 6px; color: #cbd5e1; }
        .form-group input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #475569; background-color: #0f172a; color: #fff; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #38bdf8; }
        .btn { width: 100%; padding: 12px; background-color: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background-color: #0369a1; }
        .error-msg { background-color: #7f1d1d; color: #fca5a5; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; text-align: center; }
        .links { margin-top: 20px; display: flex; justify-content: space-between; font-size: 13px; }
        .links a { color: #38bdf8; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <h1>SkinSights</h1>
    <p>Sign in to access your account</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="links">
        <a href="forgot_password.php">Forgot Password?</a>
        <a href="register.php">Create Account</a>
    </div>
</div>

</body>
</html>