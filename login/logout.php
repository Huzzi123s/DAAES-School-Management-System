
<?php

session_start();

$base_url = '/DAAES_School_Management';

// Clear all session data
$_SESSION = [];

// Remove session cookie
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

}

// Destroy session
session_destroy();

// Prevent cached dashboard after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

header("Location: {$base_url}/login/login.php");
exit;

?>
