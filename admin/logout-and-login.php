<?php
// admin/logout-and-login.php
// Called when "Staff Access" is clicked from the homepage.
// Clears any existing admin session so the login form always shows fresh.

require_once '../config/admin_session.php';

if (session_status() === PHP_SESSION_ACTIVE && isAdminLoggedIn()) {
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_email'],
        $_SESSION['admin_username']
    );
    if (empty($_SESSION)) {
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }
}

// Always redirect to login page
header('Location: index.php');
exit;
?>
