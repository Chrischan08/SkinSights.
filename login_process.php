<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=emptyfields");
        exit();
    }

    try {
        // Fetch user along with status and type
        $stmt = $pdo->prepare("SELECT * FROM user WHERE user_username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['user_password'])) {
            
            // Check if account status allows login (assuming status_id 1 is 'active')
            if ($user['user_status_id'] != 1) {
                header("Location: login.php?error=inactiveaccount");
                exit();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['user_username'];
            $_SESSION['user_type_id'] = $user['user_type_id'];

            // Log successful activity in audit_log
            $logStmt = $pdo->prepare("INSERT INTO audit_log (user_id, action_perform) VALUES (?, ?)");
            $logStmt->execute([$user['user_id'], 'User logged in successfully']);

            // Redirect to administrative or user dashboard based on role
            if ($user['user_type_id'] == 1) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();

        } else {
            header("Location: login.php?error=invalidcredentials");
            exit();
        }

    } catch (PDOException $e) {
        header("Location: login.php?error=sqlerror");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>