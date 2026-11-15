
<?php

session_start();

require_once __DIR__ . '/../config/database.php';

$db = $conn ?? null;

if (!($db instanceof mysqli)) {
    die('Database connection not found.');
}

$base_url = '/DAAES_School_Management';

$countResult = $db->query("SELECT COUNT(*) AS total FROM admins");
$totalAdmins = (int) $countResult->fetch_assoc()['total'];

$message = '';

if ($totalAdmins > 0) {
    die('Admin account already exists. Initial setup is disabled.');
}

if (empty($_SESSION['create_admin_token'])) {
    $_SESSION['create_admin_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_SESSION['create_admin_token']) ||
        !hash_equals($_SESSION['create_admin_token'], $_POST['csrf_token'] ?? '')
    ) {
        die('Invalid request.');
    }

    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $username === '' || $email === '' || $password === '') {
        $message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must contain at least 8 characters.';
    } else {

        // Check again before creating the first account
        $check = $db->query("SELECT COUNT(*) AS total FROM admins");
        $currentTotal = (int) $check->fetch_assoc()['total'];

        if ($currentTotal > 0) {
            die('An admin account already exists. Setup disabled.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO admins (name, username, email, password)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssss",
            $name,
            $username,
            $email,
            $hashedPassword
        );

        if ($stmt->execute()) {

            unset($_SESSION['create_admin_token']);

            header("Location: {$base_url}/login/login.php?created=1");
            exit;

        } else {

            $message = 'Could not create admin. Username or email may already exist.';

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
<title>Create First Admin | DAAES School</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #07182f;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.setup-card {
    width: 100%;
    max-width: 460px;
    background: white;
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
}

h2 {
    color: #07182f;
    margin-top: 0;
}

p {
    color: #778397;
    font-size: 13px;
    line-height: 1.6;
}

label {
    display: block;
    color: #26364a;
    font-weight: bold;
    font-size: 12px;
    margin: 18px 0 8px;
}

input {
    width: 100%;
    height: 46px;
    border: 1px solid #dce2eb;
    border-radius: 8px;
    padding: 0 12px;
    outline: none;
}

input:focus {
    border-color: #c3a13b;
}

button {
    width: 100%;
    height: 48px;
    margin-top: 24px;
    border: 0;
    border-radius: 9px;
    background: #07182f;
    color: #e5c45d;
    font-weight: bold;
    cursor: pointer;
}

.message {
    color: #b42318;
    background: #fff0f0;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
}

</style>
</head>

<body>

<div class="setup-card">

    <h2>Create First Administrator</h2>

    <p>
        Set up the first authorized DAAES School administrator account.
    </p>

    <?php if ($message): ?>
        <div class="message">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['create_admin_token'], ENT_QUOTES, 'UTF-8'); ?>"
        >

        <label>ADMIN NAME</label>
        <input type="text" name="name" required>

        <label>USERNAME</label>
        <input type="text" name="username" required>

        <label>EMAIL</label>
        <input type="email" name="email" required>

        <label>PASSWORD</label>
        <input type="password" name="password" minlength="8" required>

        <button type="submit">
            CREATE ADMIN ACCOUNT
        </button>

    </form>

</div>

</body>
</html>
