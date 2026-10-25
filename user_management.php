<?php
session_start();
require_once 'db.php';

// Session & Admin Role Check (Role ID 1 = Admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type_id'] ?? 3) != 1) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$success = '';
$error = '';

// Handle CRUD Actions (Create, Update Username/Password/Role/Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE USER
    if ($action === 'create_user') {
        $new_username = trim($_POST['username']);
        $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $new_role = intval($_POST['user_type_id']);
        $new_status = intval($_POST['user_status_id'] ?? 1);

        if (!empty($new_username) && !empty($_POST['password'])) {
            $stmtCheck = $conn->prepare("SELECT user_id FROM user WHERE user_username = ?");
            $stmtCheck->bind_param("s", $new_username);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $stmtInsert = $conn->prepare("INSERT INTO user (user_username, user_password, user_type_id, user_status_id) VALUES (?, ?, ?, ?)");
                $stmtInsert->bind_param("ssii", $new_username, $new_password, $new_role, $new_status);
                if ($stmtInsert->execute()) {
                    $success = "User account created successfully.";
                } else {
                    $error = "Failed to create user account.";
                }
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }

    // UPDATE USER DETAILS (Username, Password, Role, Status)
    elseif ($action === 'update_user') {
        $target_id = intval($_POST['user_id']);
        $target_username = trim($_POST['user_username']);
        $target_role = intval($_POST['user_type_id'] ?? 1);
        $target_status = intval($_POST['user_status_id'] ?? 1);
        $target_password = $_POST['user_password'] ?? '';

        if (!empty($target_username)) {
            // Check if username is taken by another account
            $stmtCheck = $conn->prepare("SELECT user_id FROM user WHERE user_username = ? AND user_id != ?");
            $stmtCheck->bind_param("si", $target_username, $target_id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "Username is already taken by another account.";
            } else {
                if (!empty($target_password)) {
                    $hashed_password = password_hash($target_password, PASSWORD_DEFAULT);
                    $stmtUpdate = $conn->prepare("UPDATE user SET user_username = ?, user_password = ?, user_type_id = ?, user_status_id = ? WHERE user_id = ?");
                    $stmtUpdate->bind_param("ssiii", $target_username, $hashed_password, $target_role, $target_status, $target_id);
                } else {
                    $stmtUpdate = $conn->prepare("UPDATE user SET user_username = ?, user_type_id = ?, user_status_id = ? WHERE user_id = ?");
                    $stmtUpdate->bind_param("siii", $target_username, $target_role, $target_status, $target_id);
                }

                if ($stmtUpdate->execute()) {
                    $success = "User account updated successfully.";
                } else {
                    $error = "Failed to update user account.";
                }
            }
        } else {
            $error = "Username cannot be empty.";
        }
    }
}

// Fetch all users, roles, and statuses safely using MySQLi
$usersQuery = "SELECT u.user_id, u.user_username, u.user_type_id, u.user_status_id, 
               COALESCE(ut.user_type_name, 'User') AS user_type_name, 
               COALESCE(us.status_name, 'Active') AS status_name, 
               u.user_created_at 
               FROM user u 
               LEFT JOIN user_type ut ON u.user_type_id = ut.user_type_id 
               LEFT JOIN user_status us ON u.user_status_id = us.user_status_id 
               ORDER BY u.user_id ASC";
$usersResult = $conn->query($usersQuery);

// Fetch roles and statuses for dropdowns
$rolesResult = $conn->query("SELECT * FROM user_type");
$statusesResult = $conn->query("SELECT * FROM user_status");
$roles = []; if ($rolesResult) { while($r = $rolesResult->fetch_assoc()) { $roles[] = $r; } }

$statuses = []; 
if ($statusesResult && $statusesResult->num_rows > 0) { 
    while($s = $statusesResult->fetch_assoc()) { 
        $statuses[] = $s; 
    } 
}
if (empty($statuses)) {
    $statuses = [
        ['status_id' => 1, 'status_name' => 'Active'],
        ['status_id' => 2, 'status_name' => 'Inactive']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - SkinSights</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0b132b; color: #fff; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 250px; background-color: #070a1a; display: flex; flex-direction: column; justify-content: space-between; padding: 25px 20px; border-right: 1px solid #1e293b; }
        .sidebar-brand { font-size: 20px; font-weight: bold; color: #3b82f6; margin-bottom: 40px; }
        .nav-links { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-link { color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; transition: 0.2s; }
        .nav-link:hover { color: #fff; }
        .nav-link.active { background-color: #0284c7; color: #fff; }
        .logout-btn { background-color: #ef4444; color: white; text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: bold; display: block; font-size: 14px; }
        .logout-btn:hover { background-color: #dc2626; }
        .main-content { flex: 1; padding: 40px 50px; overflow-y: auto; background-color: #0b132b; }
        h2 { font-size: 26px; font-weight: 600; margin-bottom: 6px; color: #f8fafc; }
        p.subtitle { color: #94a3b8; margin-bottom: 30px; font-size: 14px; }
        .card { background-color: #111c3a; padding: 35px; border-radius: 10px; border: 1px solid #1e293b; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #1e293b; text-align: left; color: #e2e8f0; font-size: 14px; }
        th { color: #94a3b8; font-weight: 600; background-color: #0f172a; }
        input, select { background-color: #0b132b; color: #fff; border: 1px solid #1e293b; padding: 10px; border-radius: 6px; width: 100%; font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: #3b82f6; }
        button { background-color: #0284c7; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        button:hover { background-color: #0369a1; }
        .btn-sm { padding: 6px 14px; font-size: 13px; }
        .grid-form { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end; }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="sidebar-brand">SkinSights</div>
        <div class="nav-links">
            <a href="admin_panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">Admin Panel</a>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="manage_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_records.php' ? 'active' : ''; ?>">Analysis Logs</a>
            <a href="user_management.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : ''; ?>">User Management</a>
            <a href="change_password.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'active' : ''; ?>">Account Settings</a>
        </div>
    </div>
    <div>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</div>

<div class="main-content">
    <h2>User Account Management</h2>
    <p class="subtitle">Create, view, update, activate, and deactivate user accounts and assign roles.</p>

    <?php if ($success): ?><div class="alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Create User Form -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #f8fafc;">Create New User Account</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            <div class="grid-form">
                <div>
                    <label style="color: #94a3b8; font-size: 12px; display:block; margin-bottom:5px;">Username</label>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>
                <div>
                    <label style="color: #94a3b8; font-size: 12px; display:block; margin-bottom:5px;">Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <div>
                    <label style="color: #94a3b8; font-size: 12px; display:block; margin-bottom:5px;">Role / Permission</label>
                    <select name="user_type_id">
                        <?php foreach($roles as $role): ?>
                            <option value="<?php echo $role['user_type_id']; ?>"><?php echo htmlspecialchars($role['user_type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="color: #94a3b8; font-size: 12px; display:block; margin-bottom:5px;">Account Status</label>
                    <select name="user_status_id">
                        <?php foreach($statuses as $status): 
                            $s_id = $status['status_id'] ?? $status['user_status_id'] ?? 1;
                            $s_name = $status['status_name'] ?? $status['user_status_name'] ?? $status['name'] ?? 'Active';
                        ?>
                            <option value="<?php echo $s_id; ?>"><?php echo htmlspecialchars($s_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" style="height: 42px;">Create User</button>
                </div>
            </div>
        </form>
    </div>

    <!-- View & Manage Users Table -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #f8fafc;">Registered User Accounts</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>New Password</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usersResult && $usersResult->num_rows > 0): ?>
                    <?php while ($row = $usersResult->fetch_assoc()): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                        <tr>
                            <td>#<?php echo $row['user_id']; ?></td>
                            <td>
                                <input type="text" name="user_username" value="<?php echo htmlspecialchars($row['user_username']); ?>" style="padding:6px; width:130px;" required>
                            </td>
                            <td>
                                <input type="password" name="user_password" placeholder="Leave blank to keep" style="padding:6px; width:140px;">
                            </td>
                            <td>
                                <select name="user_type_id" style="padding:6px; width:130px;">
                                    <?php foreach($roles as $role): ?>
                                        <option value="<?php echo $role['user_type_id']; ?>" <?php if($role['user_type_id'] == $row['user_type_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($role['user_type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="user_status_id" style="padding:6px; width:120px;">
                                    <?php foreach($statuses as $status): 
                                        $s_id = $status['status_id'] ?? $status['user_status_id'] ?? 1;
                                        $s_name = $status['status_name'] ?? $status['user_status_name'] ?? $status['name'] ?? 'Active';
                                    ?>
                                        <option value="<?php echo $s_id; ?>" <?php if($s_id == $row['user_status_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($s_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?php echo $row['user_created_at']; ?></td>
                            <td>
                                <button type="submit" class="btn-sm">Save</button>
                            </td>
                        </tr>
                    </form>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8;">No registered users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>