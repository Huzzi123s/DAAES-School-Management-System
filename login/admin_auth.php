
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Karachi');

$base_url = '/DAAES_School_Management';

// Check admin session
if (empty($_SESSION['admin_id'])) {

    header("Location: {$base_url}/login/login.php");
    exit;

}

?>
