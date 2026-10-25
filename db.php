<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "skinsights";
$charset = "utf8mb4";

// 1. MySQLi Connection ($conn) for dashboard, login, user management
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset($charset);

// 2. PDO Connection ($pdo) for admin_panel.php and other pages
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (\PDOException $e) {
    die("PDO Connection Failed: " . $e->getMessage());
}
?>