<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone_number']);
    $username   = trim($_POST['username']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check username uniqueness
        $check_stmt = $conn->prepare("SELECT user_id FROM user WHERE user_username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Username is already taken.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $user_type_id = 2;   // Standard User
            $user_status_id = 1; // Active

            $conn->begin_transaction();
            try {
                $user_stmt = $conn->prepare("INSERT INTO user (user_type_id, user_status_id, user_username, user_password) VALUES (?, ?, ?, ?)");
                $user_stmt->bind_param("iiss", $user_type_id, $user_status_id, $username, $hashed_password);
                $user_stmt->execute();
                $new_user_id = $conn->insert_id;
                $user_stmt->close();

                $profile_stmt = $conn->prepare("INSERT INTO user_profile (user_id, first_name, last_name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
                $profile_stmt->bind_param("issss", $new_user_id, $first_name, $last_name, $email, $phone);
                $profile_stmt->execute();
                $profile_stmt->close();

                $conn->commit();
                $success = "Registration successful! You can now <a href='login.php'>Login</a>.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinSights - Register</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background-color: #1e293b; border-radius: 12px; padding: 35px; width: 100%; max-width: 480px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        .card h1 { font-size: 24px; text-align: center; margin-bottom: 8px; color: #38bdf8; }
        .card p { font-size: 14px; text-align: center; color: #94a3b8; margin-bottom: 20px; }
        .form-row { display: flex; gap: 10px; }
        .form-group { margin-bottom: 14px; flex: 1; }
        .form-group label { display: block; font-size: 13px; margin-bottom: 4px; color: #cbd5e1; }
        .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569; background-color: #0f172a; color: #fff; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #38bdf8; }
        .btn { width: 100%; padding: 12px; background-color: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; margin-top: 10px; }
        .btn:hover { background-color: #0369a1; }
        .error-msg { background-color: #7f1d1d; color: #fca5a5; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; text-align: center; }
        .success-msg { background-color: #064e3b; color: #6ee7b7; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; text-align: center; }
        .success-msg a { color: #38bdf8; text-decoration: underline; }
        .links { margin-top: 18px; text-align: center; font-size: 13px; }
        .links a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h1>SkinSights</h1>
    <p>Create a new account</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" required>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>

    <div class="links">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

</body>
</html>