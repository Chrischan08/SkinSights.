<?php
session_start();
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Find user by profile email
        $stmt = $conn->prepare("SELECT u.user_id FROM user u JOIN user_profile up ON u.user_id = up.user_id WHERE up.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $user_id = $row['user_id'];
            
            // Generate temporary 6-digit OTP code
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $ins = $conn->prepare("INSERT INTO password_reset_token (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user_id, $otp, $expires_at);
            $ins->execute();
            $ins->close();

            $message = "OTP code generated: <strong>$otp</strong> (Expires in 15 mins).";
        } else {
            $error = "No account found associated with that email address.";
        }
        $stmt->close();
    } else {
        $error = "Please enter your email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinSights - Forgot Password</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background-color: #1e293b; border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        .card h1 { font-size: 24px; text-align: center; margin-bottom: 8px; color: #38bdf8; }
        .card p { font-size: 14px; text-align: center; color: #94a3b8; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; margin-bottom: 6px; color: #cbd5e1; }
        .form-group input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #475569; background-color: #0f172a; color: #fff; font-size: 14px; outline: none; }
        .btn { width: 100%; padding: 12px; background-color: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; }
        .error-msg { background-color: #7f1d1d; color: #fca5a5; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; text-align: center; }
        .success-msg { background-color: #064e3b; color: #6ee7b7; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; text-align: center; }
        .links { margin-top: 20px; text-align: center; font-size: 13px; }
        .links a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h1>SkinSights</h1>
    <p>Password Recovery</p>

    <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($message): ?><div class="success-msg"><?php echo $message; ?></div><?php endif; ?>

    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label for="email">Registered Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn">Send Recovery Code</button>
    </form>

    <div class="links">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>