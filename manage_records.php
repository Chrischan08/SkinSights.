<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type_id'] ?? 3) == 1;

$success = '';
$error = '';
$search = trim($_GET['search'] ?? '');
$searchParam = "%$search%";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $pred = trim($_POST['predicted_diseases']);
        $conf = floatval($_POST['confidence_score']);
        $path = trim($_POST['image_path']);

        if (!empty($pred) && $conf >= 0 && $conf <= 1) {
            $stmt = $conn->prepare("INSERT INTO detection_log (user_id, predicted_diseases, confidence_score, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $user_id, $pred, $conf, $path);
            if ($stmt->execute()) {
                $success = "Diagnostic record added successfully.";
            } else {
                $error = "Failed to add record.";
            }
        } else {
            $error = "Please provide valid record details.";
        }
    } elseif ($action === 'update') {
        $log_id = intval($_POST['log_id']);
        $pred = trim($_POST['predicted_diseases']);
        $conf = floatval($_POST['confidence_score']);
        $path = trim($_POST['image_path']);

        if (!empty($pred) && $conf >= 0 && $conf <= 1) {
            if ($is_admin) {
                $stmt = $conn->prepare("UPDATE detection_log SET predicted_diseases = ?, confidence_score = ?, image_path = ? WHERE log_id = ?");
                $stmt->bind_param("sdsi", $pred, $conf, $path, $log_id);
            } else {
                $stmt = $conn->prepare("UPDATE detection_log SET predicted_diseases = ?, confidence_score = ?, image_path = ? WHERE log_id = ? AND user_id = ?");
                $stmt->bind_param("sdsii", $pred, $conf, $path, $log_id, $user_id);
            }
            if ($stmt->execute()) {
                $success = "Record updated successfully.";
            } else {
                $error = "Failed to update record.";
            }
        } else {
            $error = "Please provide valid record details.";
        }
    } elseif ($action === 'delete') {
        $log_id = intval($_POST['log_id']);
        if ($is_admin) {
            $stmt = $conn->prepare("DELETE FROM detection_log WHERE log_id = ?");
            $stmt->bind_param("i", $log_id);
        } else {
            $stmt = $conn->prepare("DELETE FROM detection_log WHERE log_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $log_id, $user_id);
        }
        if ($stmt->execute()) {
            $success = "Record deleted successfully.";
        } else {
            $error = "Failed to delete record.";
        }
    }
}

// Fetch records with search filtering for Admin & Users
if ($is_admin) {
    if (!empty($search)) {
        // Admin can search by disease condition OR username
        $stmt = $conn->prepare("SELECT d.*, u.user_username FROM detection_log d LEFT JOIN user u ON d.user_id = u.user_id WHERE d.predicted_diseases LIKE ? OR u.user_username LIKE ? ORDER BY d.log_timestamp DESC");
        $stmt->bind_param("ss", $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT d.*, u.user_username FROM detection_log d LEFT JOIN user u ON d.user_id = u.user_id ORDER BY d.log_timestamp DESC");
    }
} else {
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM detection_log WHERE user_id = ? AND predicted_diseases LIKE ? ORDER BY log_timestamp DESC");
        $stmt->bind_param("is", $user_id, $searchParam);
    } else {
        $stmt = $conn->prepare("SELECT * FROM detection_log WHERE user_id = ? ORDER BY log_timestamp DESC");
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Management - SkinSights</title>
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
        .card { background-color: #111c3a; padding: 25px; border-radius: 8px; border: 1px solid #1e293b; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #1e293b; text-align: left; color: #e2e8f0; font-size: 14px; }
        th { color: #94a3b8; font-weight: 600; background-color: #0f172a; }
        input, select { background-color: #0b132b; color: #fff; border: 1px solid #1e293b; padding: 10px; border-radius: 6px; width: 100%; margin-bottom: 15px; font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: #3b82f6; }
        button { background-color: #0284c7; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        button:hover { background-color: #0369a1; }
        .btn-edit { background-color: #3b82f6; padding: 6px 12px; font-size: 13px; }
        .btn-edit:hover { background-color: #2563eb; }
        .btn-danger { background-color: #ef4444; padding: 6px 12px; font-size: 13px; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-secondary { background-color: #475569; padding: 10px 16px; font-size: 14px; margin-left: 10px; }
        .btn-secondary:hover { background-color: #334155; }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
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
    <h2>Records</h2>
    <p class="subtitle">Create, Read, Update, and Delete medical scan records.</p>

    <?php if ($success): ?><div class="alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Create / Edit Form -->
    <div class="card">
        <h3 id="form-title" style="margin-bottom: 15px; color: #f8fafc;">Add New Diagnostic Record</h3>
        <form method="POST" id="record-form">
            <input type="hidden" name="action" id="form-action" value="create">
            
            <label style="color: #94a3b8; font-size: 13px; display:block; margin-bottom:5px;">Predicted Condition</label>
            <input type="text" name="predicted_diseases" id="predicted_diseases" placeholder="e.g. Melanoma / Eczema" required>
            
            <label style="color: #94a3b8; font-size: 13px; display:block; margin-bottom:5px;">Confidence Score (0.0 to 1.0)</label>
            <input type="number" step="0.01" min="0" max="1" name="confidence_score" id="confidence_score" placeholder="e.g. 0.95" required>
            
            <label style="color: #94a3b8; font-size: 13px; display:block; margin-bottom:5px;">Image Reference Path</label>
            <input type="text" name="image_path" id="image_path" placeholder="uploads/skin_sample.jpg" required>
            
            <div style="display: flex; align-items: center;">
                <button type="submit" id="submit-btn">Create Record</button>
                <div id="cancel-btn-container" style="display: none;">
                    <button type="button" class="btn-secondary" onclick="resetForm()">Cancel</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Existing Records & Search Bar -->
    <div class="card">
        <h3 style="margin-bottom: 15px; color: #f8fafc;">Existing Records</h3>
        
        <!-- Search Form -->
        <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
            <input type="text" name="search" placeholder="<?php echo $is_admin ? 'Search by condition or username...' : 'Search by predicted condition...'; ?>" value="<?php echo htmlspecialchars($search); ?>" style="margin-bottom: 0;">
            <button type="submit" style="white-space: nowrap;">Search</button>
            <?php if (!empty($search)): ?>
                <a href="manage_records.php" style="background-color: #475569; color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center;">Reset</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if ($is_admin): ?><th>User</th><?php endif; ?>
                    <th>Condition</th>
                    <th>Confidence</th>
                    <th>Image Path</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['log_id']; ?></td>
                        <?php if ($is_admin): ?><td><?php echo htmlspecialchars($row['user_username'] ?? 'Unknown'); ?></td><?php endif; ?>
                        <td><strong><?php echo htmlspecialchars($row['predicted_diseases']); ?></strong></td>
                        <td><?php echo (floatval($row['confidence_score']) * 100) . '%'; ?></td>
                        <td><?php echo htmlspecialchars($row['image_path']); ?></td>
                        <td><?php echo $row['log_timestamp']; ?></td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <!-- Edit Button -->
                                <button type="button" class="btn-edit" onclick="editRecord(<?php echo $row['log_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['predicted_diseases']), ENT_QUOTES); ?>', <?php echo $row['confidence_score']; ?>, '<?php echo htmlspecialchars(addslashes($row['image_path']), ENT_QUOTES); ?>')">Edit</button>
                                
                                <!-- Delete Button -->
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this record?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="log_id" value="<?php echo $row['log_id']; ?>">
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo $is_admin ? 7 : 6; ?>" style="text-align: center; color: #94a3b8;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editRecord(id, condition, confidence, imagePath) {
    document.getElementById('form-title').innerText = 'Edit Diagnostic Record #' + id;
    document.getElementById('form-action').value = 'update';
    
    let logIdInput = document.getElementById('edit_log_id');
    if (!logIdInput) {
        logIdInput = document.createElement('input');
        logIdInput.type = 'hidden';
        logIdInput.name = 'log_id';
        logIdInput.id = 'edit_log_id';
        document.getElementById('record-form').appendChild(logIdInput);
    }
    logIdInput.value = id;

    document.getElementById('predicted_diseases').value = condition;
    document.getElementById('confidence_score').value = confidence;
    document.getElementById('image_path').value = imagePath;

    document.getElementById('submit-btn').innerText = 'Update Record';
    document.getElementById('cancel-btn-container').style.display = 'block';
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Add New Diagnostic Record';
    document.getElementById('form-action').value = 'create';
    
    let logIdInput = document.getElementById('edit_log_id');
    if (logIdInput) logIdInput.remove();

    document.getElementById('record-form').reset();
    document.getElementById('submit-btn').innerText = 'Create Record';
    document.getElementById('cancel-btn-container').style.display = 'none';
}
</script>

</body>
</html>