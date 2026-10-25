<?php
session_start();
require_once 'db.php';

// Authentication & Admin Access Check (Role ID 1 = Admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type_id'] ?? 0) != 1) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Administrator';

// Search handling for users
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT u.user_id, u.user_username, 
                           COALESCE(ut.user_type_name, 'User') AS user_type_name, 
                           COALESCE(us.status_name, 'Active') AS status_name, 
                           u.user_created_at 
                           FROM user u 
                           LEFT JOIN user_type ut ON u.user_type_id = ut.user_type_id 
                           LEFT JOIN user_status us ON u.user_status_id = us.user_status_id 
                           WHERE u.user_username LIKE ? OR ut.user_type_name LIKE ? 
                           ORDER BY u.user_id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query("SELECT u.user_id, u.user_username, 
                          COALESCE(ut.user_type_name, 'User') AS user_type_name, 
                          COALESCE(us.status_name, 'Active') AS status_name, 
                          u.user_created_at 
                          FROM user u 
                          LEFT JOIN user_type ut ON u.user_type_id = ut.user_type_id 
                          LEFT JOIN user_status us ON u.user_status_id = us.user_status_id 
                          ORDER BY u.user_id DESC")->fetchAll();
}

// Fetch Audit Logs safely
try {
    $audit_logs = $pdo->query("SELECT * FROM audit_log ORDER BY 1 DESC LIMIT 20")->fetchAll();
} catch (\Exception $e) {
    $audit_logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - SkinSights</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #1e293b; text-align: left; color: #e2e8f0; font-size: 14px; }
        th { color: #94a3b8; font-weight: 600; background-color: #0f172a; }
        input[type="text"] { background-color: #0b132b; color: #fff; border: 1px solid #1e293b; padding: 10px; border-radius: 6px; width: 300px; outline: none; }
        input[type="text"]:focus { border-color: #3b82f6; }
        button { background-color: #0284c7; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
        button:hover { background-color: #0369a1; }
        .btn-secondary { background-color: #334155; margin-left: 8px; text-decoration: none; display: inline-block; text-align: center; }
        .btn-secondary:hover { background-color: #475569; }
        .search-form { display: flex; gap: 10px; margin-bottom: 15px; align-items: center; }
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
    <h2>Administrative Control Panel</h2>
    <p class="subtitle">Logged in as <strong><?php echo htmlspecialchars($username); ?></strong> (Administrator)</p>

    <!-- User Account Management & Search -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #f8fafc;">User Account Management & Search</h3>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search username or role..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <a href="admin_panel.php" class="btn-secondary" style="padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 14px; color: white;">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Current Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['user_username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['user_type_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['status_name']); ?></td>
                        <td><?php echo $u['user_created_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- System Audit & Activity Logs -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #f8fafc;">System Audit & Activity Logs</h3>
        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Activity Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($audit_logs) > 0): ?>
                    <?php foreach ($audit_logs as $log): 
                        $log_id = $log['audit_id'] ?? $log['id'] ?? reset($log);
                        $details = $log['action'] ?? $log['activity'] ?? $log['description'] ?? '';
                        if (!$details) {
                            foreach ($log as $val) {
                                if (is_string($val) && strlen($val) > 5 && !preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                                    $details = $val;
                                    break;
                                }
                            }
                        }
                        if (!$details) $details = "System activity recorded";

                        // Optional: Format generic login log dynamically if needed
                        if ($details === 'User logged in successfully.') {
                            $details = "User '{$username}' logged in successfully.";
                        }

                        $timestamp = $log['timestamp'] ?? $log['created_at'] ?? '';
                        if (!$timestamp) {
                            foreach ($log as $val) {
                                if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                                    $timestamp = $val;
                                    break;
                                }
                            }
                        }
                        if (!$timestamp) $timestamp = date('Y-m-d H:i:s');
                    ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($log_id); ?></td>
                        <td><strong><?php echo htmlspecialchars($details); ?></strong></td>
                        <td><?php echo htmlspecialchars($timestamp); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #94a3b8;">No audit logs recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>