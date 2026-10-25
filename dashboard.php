<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$is_admin = ($_SESSION['user_type_id'] ?? 3) == 1;

$total_records = 0;
if ($is_admin) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM detection_log");
    if ($res) { $total_records = $res->fetch_assoc()['cnt']; }
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM detection_log WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SkinSights</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0b132b; color: #fff; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 250px; background-color: #070a1a; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; border-right: 1px solid #1e293b; }
        .sidebar-brand { font-size: 22px; font-weight: bold; color: #3b82f6; margin-bottom: 30px; }
        .nav-links { display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .nav-link { color: #94a3b8; text-decoration: none; padding: 12px 15px; border-radius: 6px; font-weight: 500; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #0284c7; color: #fff; }
        .logout-btn { background-color: #ef4444; color: white; text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: bold; display: block; }
        .logout-btn:hover { background-color: #dc2626; }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; background-color: #0b132b; }
        h2 { font-size: 24px; margin-bottom: 8px; color: #f8fafc; }
        p.subtitle { color: #94a3b8; margin-bottom: 30px; }
        .card { background-color: #111c3a; padding: 25px; border-radius: 8px; border: 1px solid #1e293b; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .metric { font-size: 32px; font-weight: bold; color: #3b82f6; margin-top: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="sidebar-brand">SkinSights</div>
        <div class="nav-links">
            <?php if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1): ?>
                <a href="admin_panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">Admin Panel</a>
            <?php endif; ?>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="manage_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_records.php' ? 'active' : ''; ?>">Record Management</a>
            <?php if (isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 1): ?>
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
    <h2>Dashboard</h2>
    <p class="subtitle">Welcome back, <strong><?php echo htmlspecialchars($username); ?></strong>.</p>

    <div class="card">
        <h3>System Overview</h3>
        <p style="color: #94a3b8; margin-top: 5px;">Total Diagnostic Records in System</p>
        <div class="metric"><?php echo $total_records; ?></div>
    </div>
</div>

</body>
</html>