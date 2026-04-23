<?php
// api/auth.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'check':
        checkAuth();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function register() {
    $conn = getConnection();
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['error' => 'All fields are required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    if (strlen($password) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Email already registered']);
        return;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user'    => ['name' => $name, 'email' => $email]
        ]);
    } else {
        echo json_encode(['error' => 'Registration failed']);
    }
    $conn->close();
}

function login() {
    $conn     = getConnection();
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Invalid email or password']);
        return;
    }

    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user'    => ['name' => $user['name'], 'email' => $user['email']]
        ]);
    } else {
        echo json_encode(['error' => 'Invalid email or password']);
    }
    $conn->close();
}

function logout() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // 1. Clear session data from memory
        $_SESSION = [];

        // 2. Expire the session cookie in the browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // 3. Destroy the session on the server
        session_destroy();
    }

    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function checkAuth() {
    if (isLoggedIn()) {
        echo json_encode(['loggedIn' => true, 'user' => getCurrentUser()]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}
?>