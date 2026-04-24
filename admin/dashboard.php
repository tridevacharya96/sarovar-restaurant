<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard – The Sarovar Court</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="css/admin.css"/>
</head>
<body>
<?php
require_once '../config/admin_session.php';
requireAdmin();
$admin = getCurrentAdmin();
?>

<div class="shell">
    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sb-logo">
            <span class="sb-logo-icon">🍽️</span>
            <div>
                <div class="sb-logo-title">The Sarovar Court</div>
                <div class="sb-logo-sub">Admin panel</div>
            </div>
        </div>
        <nav class="sb-nav">
            <div class="sb-section">Overview</div>
            <a class="nav-item active" data-page="dashboard" href="#">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a class="nav-item" data-page="reports" href="#">
                <i class="fas fa-chart-line"></i> Reports
            </a>
            <div class="sb-section">Manage</div>
            <a class="nav-item" data-page="orders" href="#">
                <i class="fas fa-file-alt"></i> Orders
                <span class="nav-badge" id="pendingBadge">0</span>
            </a>
            <a class="nav-item" data-page="events" href="#">
             <i class="fas fa-calendar-star"></i> Event bookings
             <span class="nav-badge nav-badge-gray" id="pendingEvtBadge">0</span>
           </a>
            <a class="nav-item" data-page="reservations" href="#">
                <i class="fas fa-calendar-alt"></i> Reservations
                <span class="nav-badge nav-badge-gray" id="pendingResBadge">0</span>
            </a>
            <a class="nav-item" data-page="menu" href="#">
                <i class="fas fa-utensils"></i> Menu items
            </a>
            <a class="nav-item" data-page="customers" href="#">
                <i class="fas fa-users"></i> Customers
            </a>
            <div class="sb-section">Settings</div>
            <a class="nav-item" data-page="settings" href="#">
                <i class="fas fa-cog"></i> Site Settings
            </a>
            <a class="nav-item" data-page="reviews" href="#">
                <i class="fas fa-star"></i> Reviews
                <span class="nav-badge" id="pendingReviewsBadge" style="display:none">0</span>
            </a>
            <a class="nav-item" data-page="coupons" href="#">
                <i class="fas fa-tag"></i> Coupons
            </a>
            <a class="nav-item" data-page="messages" href="#">
                <i class="fas fa-envelope"></i> Messages
                <span class="nav-badge" id="msgBadge" style="display:none">0</span>
            </a>
        </nav>
        <div class="sb-bottom">
            <div class="sb-admin-info">
                <div class="sb-avatar"><?= strtoupper(substr($admin['name'],0,2)) ?></div>
                <div>
                    <div class="sb-admin-name"><?= htmlspecialchars($admin['name']) ?></div>
                    <div class="sb-admin-role">Super admin</div>
                </div>
            </div>
            <a href="api/admin_auth.php?action=logout" class="sb-logout" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <div class="topbar-title" id="pageTitle">Dashboard</div>
                    <div class="topbar-sub" id="pageSub">Welcome back, <?= htmlspecialchars($admin['name']) ?></div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search orders, customers..." />
                </div>
                <button class="topbar-icon-btn" onclick="loadPage('messages')" title="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="icon-badge" id="msgIconBadge" style="display:none">0</span>
                </button>
                <button class="topbar-icon-btn" onclick="refreshCurrent()" title="Refresh">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i>
                </button>
                <a href="../index.html" class="topbar-icon-btn" title="View site" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </header>

        <div class="content" id="content">
            <div class="page-loader"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<!-- ===== MODAL ===== -->
<div class="modal-overlay" id="modal" onclick="closeModal(event)">
    <div class="modal-box" id="modalBox"></div>
</div>

<!-- ===== TOAST ===== -->
<div class="toast-container" id="toastContainer"></div>

<script src="js/admin.js"></script>
</body>
</html>
