
<?php

session_start();

date_default_timezone_set('Asia/Karachi');

$base_url = '/DAAES_School_Management';

require_once __DIR__ . '/../config/database.php';

$db = $conn ?? null;

if (!($db instanceof mysqli)) {
    die('Database connection not found. Check config/database.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$base_url}/login/login.php");
    exit;
}

// CSRF verification
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['login_csrf']) ||
    !hash_equals($_SESSION['login_csrf'], $token)
) {
    $_SESSION['login_error'] = 'Invalid request. Please try again.';
    header("Location: {$base_url}/login/login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your username and password.';
    header("Location: {$base_url}/login/login.php");
    exit;
}

// Find only an admin account
$stmt = $db->prepare("
    SELECT id, name, username, email, password
    FROM admins
    WHERE username = ? OR email = ?
    LIMIT 1
");

if (!$stmt) {
    die('Database query failed: ' . htmlspecialchars($db->error));
}

$stmt->bind_param("ss", $username, $username);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

// Verify password
if (!$admin || !password_verify($password, $admin['password'])) {

    $_SESSION['login_error'] = 'Incorrect username/email or password.';
    header("Location: {$base_url}/login/login.php");
    exit;
}

// Create a fresh session ID
session_regenerate_id(true);

// Store admin session
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_name'] = $admin['name'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_email'] = $admin['email'];

unset($_SESSION['login_csrf']);
unset($_SESSION['login_error']);

// Redirect to dashboard
header("Location: {$base_url}/admin/dashboard.php");
exit;

?>
