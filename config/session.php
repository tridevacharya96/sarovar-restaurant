<?php
// config/session.php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,   // 1 day
        'path'     => '/',
        'secure'   => false,   // set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'    // required for fetch() to send cookies
    ]);
    session_start();
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Return the current logged-in user's basic info.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}
?>
