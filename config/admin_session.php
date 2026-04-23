<?php
// config/admin_session.php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        error_log("[Sarovar Admin] Unauthorised access attempt from $ip to $uri");

        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) &&
             str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        );

        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Please login.']);
        } else {
            // Simple relative redirect — works on localhost, XAMPP, live server
            // __FILE__ is always config/admin_session.php
            // admin/index.php is always one level up from config/ then into admin/
            $adminLogin = dirname(__DIR__) . '/admin/index.php';
            $depth = substr_count(
                str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME'] ?? ''),
                '/'
            ) - 1;
            $prefix = str_repeat('../', max(0, $depth));
            header('Location: ' . $prefix . 'admin/index.php');
        }
        exit;
    }
}

function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) return null;
    return [
        'id'       => $_SESSION['admin_id'],
        'name'     => $_SESSION['admin_name']     ?? 'Admin',
        'email'    => $_SESSION['admin_email']    ?? '',
        'username' => $_SESSION['admin_username'] ?? '',
    ];
}
