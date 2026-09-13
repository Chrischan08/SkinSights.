<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user profile info
$stmt = $conn->prepare("SELECT up.first_name, up.last_name, up.email 
                        FROM user u 
                        JOIN user_profile up ON u.user_id = up.user_id 
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch detection statistics safely
$total_detections = 0;
$log_query = $conn->query("SHOW TABLES LIKE 'detection_log'");
if ($log_query && $log_query->num_rows > 0) {
    $count_res = $conn->query("SELECT COUNT(*) as total FROM detection_log WHERE user_id = $user_id");
    if ($count_res) {
        $total_detections = $count_res->fetch_assoc()['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinSights - Main Dashboard</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .header h1 { font-size: 24px; color: #f8fafc; }
        .user-badge { background-color: #334155; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #38bdf8; }
        
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .card { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 20px; }
        .card h3 { font-size: 13px; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; }
        .card .value { font-size: 22px; font-weight: bold; color: #38bdf8; }
        
        .section-title { font-size: 18px; margin-bottom: 16px; color: #cbd5e1; }
        .table-container { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #0f172a; color: #94a3b8; padding: 12px 16px; font-weight: 600; }
        td { padding: 14px 16px; border-top: 1px solid #334155; color: #e2e8f0; }
        
        .logout-btn { background-color: #ef4444; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: 600; text-align: center; text-decoration: none; display: block; margin-top: auto; }
        .logout-btn:hover { background-color: #dc2626; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div>
            <div class="brand">SkinSights</div>
            <ul class="nav-list">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Analysis Logs</a></li>
                <li class="nav-item"><a href="#" class="nav-link">User Management</a></li>
                <li class="nav-item"><a href="change_password.php" class="nav-link">Account Settings</a></li>
            </ul>
        </div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Workspace Overview</h1>
            <div class="user-badge">User ID: <?php echo $user_id; ?></div>
        </div>

        <div class="card-grid">
            <div class="card">
                <h3>Account Owner</h3>
                <div class="value"><?php echo htmlspecialchars(($user_info['first_name'] ?? 'User') . ' ' . ($user_info['last_name'] ?? 'Account')); ?></div>
            </div>
            <div class="card">
                <h3>Account Email</h3>
                <div class="value" style="font-size: 16px; margin-top: 6px;"><?php echo htmlspecialchars($user_info['email'] ?? 'Not set'); ?></div>
            </div>
            <div class="card">
                <h3>Total Detections</h3>
                <div class="value"><?php echo $total_detections; ?></div>
            </div>
        </div>

        <div class="section-title">System Status</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Database Table</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Authentication Engine</td>
                        <td>`user` / `user_session`</td>
                        <td style="color: #4ade80;">Active</td>
                    </tr>
                    <tr>
                        <td>Audit Tracking</td>
                        <td>`audit_log`</td>
                        <td style="color: #4ade80;">Logging Enabled</td>
                    </tr>
                    <tr>
                        <td>Recovery Service</td>
                        <td>`password_reset_token`</td>
                        <td style="color: #4ade80;">Operational</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>