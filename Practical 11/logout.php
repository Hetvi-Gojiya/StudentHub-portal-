<?php
// logout.php - Practical 10: Secure Logout
session_start();

require_once 'db.php';

// If user is logged in, optionally clear remember_token in DB
if (isset($_SESSION['user_id'])) {
    try {
        $clearToken = $pdo->prepare("UPDATE students SET remember_token = NULL WHERE id = ?");
        $clearToken->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Silently continue
    }
}

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy session cookie in browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Clear remember-me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 4. Destroy the session on server
session_destroy();

// 5. Redirect to login with logout success message
header("Location: login.php?msg=logout");
exit;
?>
