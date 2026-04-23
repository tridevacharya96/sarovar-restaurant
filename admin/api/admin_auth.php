<?php
// admin/api/admin_auth.php
require_once '../../config/admin_session.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'logout') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_email'],
            $_SESSION['admin_username']
        );
        if (empty($_SESSION)) {
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }
    }

    // Simple relative redirect — works on any server/path/XAMPP setup
    header('Location: ../index.php');
    exit;
}

requireAdmin();
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid action']);
?>
