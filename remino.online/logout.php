<?php
session_start();

// Kosongkan session
$_SESSION = [];
session_unset();
session_destroy();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect
header("Location: login.php");
exit;
