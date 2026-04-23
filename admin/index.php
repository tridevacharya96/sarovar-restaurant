<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Login – Sarovar Restaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;min-height:100vh;background:#0f0c0a;display:flex;align-items:center;justify-content:center;padding:20px}
        .login-wrap{width:100%;max-width:420px}
        .login-logo{text-align:center;margin-bottom:32px}
        .login-logo-icon{font-size:48px;display:block;margin-bottom:8px}
        .login-logo h1{font-size:28px;color:#fff;font-weight:600;letter-spacing:0.02em}
        .login-logo p{font-size:13px;color:rgba(255,255,255,0.4);margin-top:4px}
        .login-card{background:#1a1512;border:0.5px solid rgba(255,255,255,0.1);border-radius:16px;padding:36px}
        .login-card h2{font-size:18px;font-weight:500;color:#fff;margin-bottom:6px}
        .login-card .sub{font-size:13px;color:rgba(255,255,255,0.4);margin-bottom:28px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:7px;text-transform:uppercase;letter-spacing:0.06em}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.25);font-size:14px}
        .form-group input{width:100%;background:#0f0c0a;border:0.5px solid rgba(255,255,255,0.12);border-radius:10px;padding:11px 13px 11px 38px;font-size:14px;color:#fff;outline:none;transition:border-color 0.2s;font-family:'Poppins',sans-serif}
        .form-group input:focus{border-color:rgba(216,90,48,0.6)}
        .form-group input::placeholder{color:rgba(255,255,255,0.2)}
        .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,0.25);cursor:pointer;font-size:14px;padding:0}
        .btn-login{width:100%;padding:13px;background:#D85A30;border:none;border-radius:10px;color:#fff;font-size:14px;font-weight:500;cursor:pointer;transition:background 0.2s;font-family:'Poppins',sans-serif;margin-top:6px}
        .btn-login:hover{background:#993C1D}
        .btn-login:disabled{opacity:0.6;cursor:not-allowed}
        .error-msg{background:rgba(226,75,74,0.1);border:0.5px solid rgba(226,75,74,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#f09595;margin-bottom:16px;display:none}
        .login-hint{text-align:center;margin-top:20px;font-size:12px;color:rgba(255,255,255,0.25)}
        .back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,0.3);text-decoration:none;margin-bottom:20px;transition:color 0.2s}
        .back-link:hover{color:rgba(255,255,255,0.6)}
    </style>
</head>
<body>
<?php
require_once '../config/admin_session.php';

// Already logged in — go straight to dashboard
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';

    // ── Brute-force protection ──────────────────────────────────────
    $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attemptKey  = 'admin_login_attempts_' . md5($ip);
    $lockoutKey  = 'admin_login_lockout_'  . md5($ip);
    $maxAttempts = 5;
    $lockoutSecs = 300; // 5 minutes

    if (!isset($_SESSION[$lockoutKey])) $_SESSION[$lockoutKey] = 0;
    if (!isset($_SESSION[$attemptKey])) $_SESSION[$attemptKey] = 0;

    if ($_SESSION[$lockoutKey] > time()) {
        $remaining = ceil(($_SESSION[$lockoutKey] - time()) / 60);
        $error = "Too many failed attempts. Please try again in {$remaining} minute(s).";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                // Success — reset counters, regenerate session ID to prevent fixation
                $_SESSION[$attemptKey] = 0;
                $_SESSION[$lockoutKey] = 0;
                session_regenerate_id(true);

                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_name']     = $admin['name'];
                $_SESSION['admin_email']    = $admin['email'];
                $_SESSION['admin_username'] = $admin['username'];
                $conn->close();
                header('Location: dashboard.php');
                exit;
            } else {
                // Failed attempt
                $_SESSION[$attemptKey]++;
                $remaining = $maxAttempts - $_SESSION[$attemptKey];
                if ($_SESSION[$attemptKey] >= $maxAttempts) {
                    $_SESSION[$lockoutKey] = time() + $lockoutSecs;
                    $_SESSION[$attemptKey] = 0;
                    $error = 'Too many failed attempts. Account locked for 5 minutes.';
                    error_log("[Sarovar Admin] Login locked for IP: $ip");
                } else {
                    $error = "Invalid username or password. {$remaining} attempt(s) remaining.";
                }
            }
            $conn->close();
        }
    }
}
?>
<div class="login-wrap">
    <a href="../index.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to site</a>
    <div class="login-logo">
        <span class="login-logo-icon">🍽️</span>
        <h1>Sarovar</h1>
        <p>Restaurant Management System</p>
    </div>
    <div class="login-card">
        <h2>Admin Login</h2>
        <p class="sub">Sign in to your admin panel</p>
        <?php if ($error): ?>
        <div class="error-msg" style="display:block"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="admin" required autocomplete="username"/>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="pw" placeholder="••••••••" required autocomplete="current-password"/>
                    <button type="button" class="toggle-pw" onclick="togglePw()"><i class="fas fa-eye" id="pw-icon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
        <p class="login-hint">Contact your system administrator if you've lost access.</p>
    </div>
</div>
<script>
function togglePw(){
    const i=document.getElementById('pw');
    const ic=document.getElementById('pw-icon');
    i.type=i.type==='password'?'text':'password';
    ic.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';
}
</script>
</body>
</html>
