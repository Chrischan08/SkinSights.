<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_code     = trim($_POST['otp_code']);
    $new_password = $_POST['new_password'];
    $confirm_pwd  = $_POST['confirm_password'];

    if (empty($otp_code) || empty($new_password) || empty($confirm_pwd)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_pwd) {
        $error = "Passwords do not match.";
    } else {
        // Validate OTP and check expiration
        $stmt = $conn->prepare("SELECT user_id, expires_at, is_used FROM password_reset_token WHERE otp_code = ? ORDER BY reset_id DESC LIMIT 1");
        $stmt->bind_param("s", $otp_code);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $user_id = $row['user_id'];
            $expires_at = strtotime($row['expires_at']);

            if ($row['is_used'] == 1) {
                $error = "This OTP code has already been used.";
            } elseif (time() > $expires_at) {
                $error = "This OTP code has expired.";
            } else {
                // Hash new password and update user record
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
                    $upd->bind_param("si", $hashed_password, $user_id);
                    $upd->execute();
                    $upd->close();

                    // Mark OTP as used
                    $mark = $conn->prepare("UPDATE password_reset_token SET is_used = 1 WHERE otp_code = ?");
                    $mark->bind_param("s", $otp_code);
                    $mark->execute();
                    $mark->close();

                    // Log event in audit_log
                    $action = "Password reset completed via OTP.";
                    $audit = $conn->prepare("INSERT INTO audit_log (user_id, action_perform) VALUES (?, ?)");
                    $audit->bind_param("is", $user_id, $action);
                    $audit->execute();
                    $audit->close();

                    $conn->commit();
                    $success = "Password reset successful! You can now <a href='login.php'>Login</a>.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to reset password: " . $e->getMessage();
                }
            }
        } else {
            $error = "Invalid OTP verification code.";
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
    <title>SkinSights - Reset Password</title>
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
        .success-msg a { color: #38bdf8; text-decoration: underline; }
        .links { margin-top: 20px; text-align: center; font-size: 13px; }
        .links a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h1>SkinSights</h1>
    <p>Reset Account Password</p>

    <?php if ($error): ?><div class="error-msg"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>

    <form method="POST" action="reset_password.php">
        <div class="form-group">
            <label for="otp_code">OTP Verification Code</label>
            <input type="text" id="otp_code" name="otp_code" placeholder="Enter 6-digit OTP" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        <button type="submit" class="btn">Update Password</button>
    </form>

    <div class="links">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>