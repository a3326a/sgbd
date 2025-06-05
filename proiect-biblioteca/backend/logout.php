<?php
// backend/logout.php
session_start();

// 1. Distrug sesiunea
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 2. Redirecționez către pagina de login
header('Location: ../landing.html');
exit;
?>
