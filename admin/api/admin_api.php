<?php
// admin/api/admin_api.php
require_once '../../config/database.php';
require_once '../../config/admin_session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':         getDashboard();         break;
    case 'badges':            getBadges();             break;
    case 'orders':            getOrders();             break;
    case 'update_order':      updateOrder();           break;
    case 'reservations':      getReservations();       break;
    case 'update_reservation':updateReservation();     break;
    case 'menu_items':        getMenuItems();          break;
    case 'categories':        getCategories();         break;
    case 'add_menu_item':     addMenuItem();           break;
    case 'edit_menu_item':    editMenuItem();          break;
    case 'toggle_availability':toggleAvailability();   break;
    case 'customers':         getCustomers();          break;
    case 'customer_orders':   getCustomerOrders();     break;
    case 'export_customers':  exportCustomers();       break;
    case 'reports':           getReports();            break;
    case 'messages':          getMessages();           break;
    case 'coupons':           getCoupons();            break;
    case 'add_coupon':        addCoupon();             break;
    case 'toggle_coupon':     toggleCoupon();          break;
    case 'delete_coupon':     deleteCoupon();          break;
    case 'get_settings':      getSettingsAdmin();      break;
    case 'save_settings':     saveSettings();          break;
    case 'get_reviews':       getReviewsAdmin();       break;
    case 'update_review':     updateReview();          break;
    case 'delete_review':     deleteReview();          break;
    case 'event_bookings':        getEventBookings();         break;
    case 'update_event_booking':  updateEventBooking();       break;
    case 'event_booking_stats':   getEventBookingStats();     break;
    default:                  echo json_encode(['error'=>'Invalid action']);
}

/* ============================================================ BADGES */
function getBadges() {
    $conn = getConnection();
    $safe = function($sql) use ($conn) {
        $r = $conn->query($sql);
        if (!$r) return 0;
        $row = $r->fetch_assoc();
        return $row ? intval(array_values($row)[0]) : 0;
    };
    $pending_orders       = $safe("SELECT COUNT(*) FROM orders WHERE status='pending'");
    $pending_reservations = $safe("SELECT COUNT(*) FROM reservations WHERE status='pending'");
    $pending_events       = $safe("SELECT COUNT(*) FROM event_bookings WHERE status='pending'");
    $new_messages         = $safe("SELECT COUNT(*) FROM contact_messages WHERE created_at >= DATE_SUB(NOW(),INTERVAL 24 HOUR)");
    echo json_encode(compact('pending_orders','pending_reservations','pending_events','new_messages'));
    $conn->close();
}

/* ============================================================ DASHBOARD */
function getDashboard() {
    $conn  = getConnection();
    $today = date('Y-m-d');
    $yday  = date('Y-m-d', strtotime('-1 day'));
    $month = date('Y-m');

    $stats = [
        'today_revenue'       => floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE DATE(created_at)='$today' AND status!='cancelled'")->fetch_assoc()['v']),
        'yesterday_revenue'   => floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE DATE(created_at)='$yday' AND status!='cancelled'")->fetch_assoc()['v']),
        'today_orders'        => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE DATE(created_at)='$today'")->fetch_assoc()['c']),
        'pending_orders'      => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c']),
        'total_customers'     => intval($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c']),
        'new_customers_week'  => intval($conn->query("SELECT COUNT(*) c FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['c']),
        'month_revenue'       => floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status!='cancelled'")->fetch_assoc()['v']),
        'month_orders'        => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['c']),
    ];

    $top = $conn->query("
        SELECT m.name, SUM(oi.quantity*oi.price) revenue
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id=m.id
        JOIN orders o ON oi.order_id=o.id
        WHERE o.status!='cancelled' AND DATE_FORMAT(o.created_at,'%Y-%m')='$month'
        GROUP BY m.id ORDER BY revenue DESC LIMIT 5
    ");
    $top_items = [];
    while ($r=$top->fetch_assoc()) $top_items[]=$r;

    $catq = $conn->query("
        SELECT c.name, COALESCE(SUM(oi.quantity*oi.price),0) revenue
        FROM menu_categories c
        LEFT JOIN menu_items m ON m.category_id=c.id
        LEFT JOIN order_items oi ON oi.menu_item_id=m.id
        LEFT JOIN orders o ON oi.order_id=o.id AND o.status!='cancelled' AND DATE_FORMAT(o.created_at,'%Y-%m')='$month'
        GROUP BY c.id ORDER BY revenue DESC
    ");
    $category_revenue=[];
    while ($r=$catq->fetch_assoc()) if ($r['revenue']>0) $category_revenue[]=$r;

    $recq = $conn->query("
        SELECT o.*, GROUP_CONCAT(CONCAT(m.name,' x',oi.quantity) ORDER BY oi.id SEPARATOR ', ') items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id=oi.order_id
        LEFT JOIN menu_items m ON oi.menu_item_id=m.id
        GROUP BY o.id ORDER BY o.created_at DESC LIMIT 6
    ");
    $recent_orders=[];
    while ($r=$recq->fetch_assoc()) $recent_orders[]=$r;

    echo json_encode(compact('stats','top_items','category_revenue','recent_orders'));
    $conn->close();
}

/* ============================================================ ORDERS */
function getOrders() {
    $conn   = getConnection();
    $filter = $_GET['filter'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $where  = [];
    if ($filter !== 'all') $where[] = "o.status='".mysqli_real_escape_string($conn,$filter)."'";
    if ($search) {
        $s = mysqli_real_escape_string($conn,$search);
        $where[] = "(o.name LIKE '%$s%' OR o.phone LIKE '%$s%' OR o.email LIKE '%$s%' OR o.id='$s')";
    }
    $wClause = $where ? 'WHERE '.implode(' AND ',$where) : '';
    $q = $conn->query("
        SELECT o.*, GROUP_CONCAT(CONCAT(m.name,' x',oi.quantity) ORDER BY oi.id SEPARATOR ', ') items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id=oi.order_id
        LEFT JOIN menu_items m ON oi.menu_item_id=m.id
        $wClause
        GROUP BY o.id ORDER BY o.created_at DESC LIMIT 200
    ");
    $orders=[];
    while ($r=$q->fetch_assoc()) $orders[]=$r;
    echo json_encode($orders);
    $conn->close();
}

function updateOrder() {
    $conn     = getConnection();
    $orderId  = intval($_POST['order_id'] ?? 0);
    $status   = mysqli_real_escape_string($conn,$_POST['status'] ?? '');
    $allowed  = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
    if (!in_array($status,$allowed)) { echo json_encode(['error'=>'Invalid status']); return; }
    $conn->query("UPDATE orders SET status='$status' WHERE id=$orderId");
    echo json_encode(['success'=>true,'message'=>'Order updated']);
    $conn->close();
}

/* ============================================================ RESERVATIONS */
function getReservations() {
    $conn   = getConnection();
    $filter = $_GET['filter'] ?? 'all';
    $where  = $filter !== 'all' ? "WHERE status='".mysqli_real_escape_string($conn,$filter)."'" : '';
    $q      = $conn->query("SELECT * FROM reservations $where ORDER BY date DESC, time DESC LIMIT 200");
    $rows=[];
    while ($r=$q->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

function updateReservation() {
    $conn   = getConnection();
    $id     = intval($_POST['reservation_id'] ?? 0);
    $status = mysqli_real_escape_string($conn,$_POST['status'] ?? '');
    $allowed= ['pending','confirmed','cancelled','completed'];
    if (!in_array($status,$allowed)) { echo json_encode(['error'=>'Invalid status']); return; }
    $conn->query("UPDATE reservations SET status='$status' WHERE id=$id");
    echo json_encode(['success'=>true]);
    $conn->close();
}

/* ============================================================ MENU */
function getMenuItems() {
    $conn = getConnection();
    $q    = $conn->query("SELECT m.*, c.name category_name FROM menu_items m JOIN menu_categories c ON m.category_id=c.id ORDER BY c.id,m.name");
    $rows=[];
    while ($r=$q->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

function getCategories() {
    $conn = getConnection();
    $q    = $conn->query("SELECT * FROM menu_categories ORDER BY id");
    $rows=[];
    while ($r=$q->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

function addMenuItem() {
    $conn    = getConnection();
    $name    = trim($_POST['name']        ?? '');
    $catId   = intval($_POST['category_id']  ?? 0);
    $price   = floatval($_POST['price']   ?? 0);
    $isVeg   = intval($_POST['is_veg']    ?? 1);
    $desc    = trim($_POST['description'] ?? '');
    $image   = trim($_POST['image']       ?? '');
    $feat    = intval($_POST['is_featured']?? 0);
    if (!$name || !$price) { echo json_encode(['error'=>'Name and price required']); return; }
    $stmt = $conn->prepare("INSERT INTO menu_items (category_id,name,description,price,image,is_veg,is_featured,is_available) VALUES (?,?,?,?,?,?,?,1)");
    $stmt->bind_param("issdsis",$catId,$name,$desc,$price,$image,$isVeg,$feat);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['error'=>'Failed to add item']);
    $conn->close();
}

function editMenuItem() {
    $conn   = getConnection();
    $id     = intval($_POST['item_id']      ?? 0);
    $name   = trim($_POST['name']           ?? '');
    $catId  = intval($_POST['category_id']  ?? 0);
    $price  = floatval($_POST['price']      ?? 0);
    $isVeg  = intval($_POST['is_veg']       ?? 1);
    $desc   = trim($_POST['description']    ?? '');
    $image  = trim($_POST['image']          ?? '');
    $feat   = intval($_POST['is_featured']  ?? 0);
    if (!$name || !$price || !$id) { echo json_encode(['error'=>'Missing fields']); return; }
    $stmt = $conn->prepare("UPDATE menu_items SET category_id=?,name=?,description=?,price=?,image=?,is_veg=?,is_featured=? WHERE id=?");
    $stmt->bind_param("issdssii",$catId,$name,$desc,$price,$image,$isVeg,$feat,$id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['error'=>'Failed to update']);
    $conn->close();
}

function toggleAvailability() {
    $conn  = getConnection();
    $id    = intval($_POST['item_id']      ?? 0);
    $avail = intval($_POST['is_available'] ?? 0);
    $conn->query("UPDATE menu_items SET is_available=$avail WHERE id=$id");
    echo json_encode(['success'=>true]);
    $conn->close();
}

/* ============================================================ CUSTOMERS */
function getCustomers() {
    $conn = getConnection();
    $q    = $conn->query("
        SELECT u.id, u.name, u.email, u.phone, u.created_at,
               COUNT(o.id) total_orders,
               COALESCE(SUM(o.total_amount),0) total_spent
        FROM users u
        LEFT JOIN orders o ON u.id=o.user_id AND o.status!='cancelled'
        GROUP BY u.id ORDER BY total_spent DESC
    ");
    $rows=[];
    while ($r=$q->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

function getCustomerOrders() {
    $conn   = getConnection();
    $userId = intval($_GET['user_id'] ?? 0);
    $q      = $conn->prepare("
        SELECT o.*, GROUP_CONCAT(CONCAT(m.name,' x',oi.quantity) ORDER BY oi.id SEPARATOR ', ') items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id=oi.order_id
        LEFT JOIN menu_items m ON oi.menu_item_id=m.id
        WHERE o.user_id=?
        GROUP BY o.id ORDER BY o.created_at DESC
    ");
    $q->bind_param("i",$userId);
    $q->execute();
    $result=$q->get_result();
    $rows=[];
    while ($r=$result->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

function exportCustomers() {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customers_'.date('Y-m-d').'.csv"');
    $conn = getConnection();
    $q    = $conn->query("
        SELECT u.name, u.email, u.phone, u.created_at,
               COUNT(o.id) total_orders,
               COALESCE(SUM(o.total_amount),0) total_spent
        FROM users u LEFT JOIN orders o ON u.id=o.user_id AND o.status!='cancelled'
        GROUP BY u.id ORDER BY total_spent DESC
    ");
    $out = fopen('php://output','w');
    fputcsv($out,['Name','Email','Phone','Joined','Total Orders','Total Spent (₹)']);
    while ($r=$q->fetch_assoc()) fputcsv($out,[$r['name'],$r['email'],$r['phone']??'',date('d M Y',strtotime($r['created_at'])),$r['total_orders'],round($r['total_spent'])]);
    fclose($out);
    $conn->close();
}

/* ============================================================ REPORTS */
function getReports() {
    $conn  = getConnection();
    $month = date('Y-m');

    // Safe single-value query helper — returns 0 if query fails
    $qv = function($sql) use ($conn) {
        $r = $conn->query($sql);
        if (!$r) return 0;
        $row = $r->fetch_assoc();
        return $row ? array_values($row)[0] : 0;
    };

    $stats = [
        'month_revenue'       => floatval($qv("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status NOT IN ('cancelled')")),
        'month_orders'        => intval($qv("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")),
        'avg_order'           => floatval($qv("SELECT COALESCE(AVG(total_amount),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status NOT IN ('cancelled')")),
        'delivered_count'     => intval($qv("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status='delivered'")),
        'cancelled_count'     => intval($qv("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status='cancelled'")),
        'new_customers_month' => intval($qv("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")),
        'month_reservations'  => intval($qv("SELECT COUNT(*) FROM reservations WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")),
        'total_revenue'       => floatval($qv("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status NOT IN ('cancelled')")),
        'total_orders'        => intval($qv("SELECT COUNT(*) FROM orders")),
        'total_customers'     => intval($qv("SELECT COUNT(*) FROM users")),
    ];

    // Weekly revenue — last 7 days, fill every day (no missing gaps)
    $weekly = [];
    for ($i = 6; $i >= 0; $i--) {
        $date    = date('Y-m-d', strtotime("-{$i} days"));
        $label   = date('D', strtotime("-{$i} days"));
        $revenue = floatval($qv("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)='$date' AND status NOT IN ('cancelled')"));
        $weekly[] = ['day' => $label, 'date' => $date, 'revenue' => $revenue];
    }

    // Monthly revenue — last 6 months
    $monthly = [];
    for ($i = 5; $i >= 0; $i--) {
        $m       = date('Y-m', strtotime("-{$i} months"));
        $label   = date('M Y', strtotime("-{$i} months"));
        $revenue = floatval($qv("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$m' AND status NOT IN ('cancelled')"));
        $monthly[] = ['month' => $label, 'revenue' => $revenue];
    }

    // Peak hours — no CONCAT, built safely in PHP
    $peakQ = $conn->query("
        SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
        FROM orders
        WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'
        GROUP BY HOUR(created_at)
        ORDER BY cnt DESC
        LIMIT 6
    ");
    $peak_hours = [];
    if ($peakQ) {
        while ($r = $peakQ->fetch_assoc()) {
            $hr  = intval($r['hr']);
            $hr2 = $hr + 1;
            $h1  = ($hr  % 12 === 0) ? 12 : $hr  % 12;
            $h2  = ($hr2 % 12 === 0) ? 12 : $hr2 % 12;
            $a1  = $hr  >= 12 ? 'PM' : 'AM';
            $a2  = $hr2 >= 12 ? 'PM' : 'AM';
            $peak_hours[] = ['label' => "{$h1}{$a1}–{$h2}{$a2}", 'count' => intval($r['cnt'])];
        }
    }

    // Top items this month
    $topQ = $conn->query("
        SELECT m.name, COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue, SUM(oi.quantity) AS qty
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o     ON oi.order_id = o.id
        WHERE DATE_FORMAT(o.created_at,'%Y-%m') = '$month'
          AND o.status NOT IN ('cancelled')
        GROUP BY m.id
        ORDER BY revenue DESC
        LIMIT 6
    ");
    $top_items = [];
    if ($topQ) while ($r = $topQ->fetch_assoc()) $top_items[] = $r;

    // Order status breakdown
    $sbQ = $conn->query("
        SELECT status, COUNT(*) AS cnt
        FROM orders
        WHERE DATE_FORMAT(created_at,'%Y-%m') = '$month'
        GROUP BY status
    ");
    $status_breakdown = [];
    if ($sbQ) while ($r = $sbQ->fetch_assoc()) $status_breakdown[$r['status']] = intval($r['cnt']);

    echo json_encode([
        'stats'            => $stats,
        'weekly'           => $weekly,
        'monthly'          => $monthly,
        'peak_hours'       => $peak_hours,
        'top_items'        => $top_items,
        'status_breakdown' => $status_breakdown,
    ]);
    $conn->close();
}

/* ============================================================ MESSAGES */
function getMessages() {
    $conn = getConnection();
    $q    = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100");
    $rows=[];
    while ($r=$q->fetch_assoc()) $rows[]=$r;
    echo json_encode($rows);
    $conn->close();
}

/* ============================================================ EVENT BOOKINGS */
function getEventBookings() {
    $conn   = getConnection();
    // Check table exists first
    $check = $conn->query("SHOW TABLES LIKE 'event_bookings'");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['error' => 'event_bookings table not found. Please run events_setup.sql first.']);
        $conn->close(); return;
    }
    $filter = $_GET['filter'] ?? 'all';
    $where  = $filter !== 'all' ? "WHERE eb.status='".mysqli_real_escape_string($conn,$filter)."'" : '';
    $q = $conn->query("
        SELECT eb.*, ep.name pkg_display_name
        FROM event_bookings eb
        LEFT JOIN event_packages ep ON eb.package_id = ep.id
        $where
        ORDER BY eb.created_at DESC LIMIT 200
    ");
    $rows = [];
    if ($q) while ($r = $q->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    $conn->close();
}

function updateEventBooking() {
    $conn       = getConnection();
    $id         = intval($_POST['booking_id']  ?? 0);
    $status     = mysqli_real_escape_string($conn, $_POST['status']      ?? '');
    $adminNotes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');
    $allowed    = ['pending','confirmed','cancelled','completed'];
    if (!in_array($status, $allowed)) { echo json_encode(['error'=>'Invalid status']); return; }
    $conn->query("UPDATE event_bookings SET status='$status', admin_notes='$adminNotes' WHERE id=$id");
    echo json_encode(['success' => true]);
    $conn->close();
}

function getEventBookingStats() {
    $conn  = getConnection();
    // Check table exists
    $check = $conn->query("SHOW TABLES LIKE 'event_bookings'");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['total'=>0,'pending'=>0,'confirmed'=>0,'month'=>0,'revenue'=>0]);
        $conn->close(); return;
    }
    $month = date('Y-m');
    $safe  = function($sql) use ($conn) {
        $r = $conn->query($sql);
        if (!$r) return 0;
        $row = $r->fetch_assoc();
        return $row ? array_values($row)[0] : 0;
    };
    $stats = [
        'total'     => intval($safe("SELECT COUNT(*) FROM event_bookings")),
        'pending'   => intval($safe("SELECT COUNT(*) FROM event_bookings WHERE status='pending'")),
        'confirmed' => intval($safe("SELECT COUNT(*) FROM event_bookings WHERE status='confirmed'")),
        'month'     => intval($safe("SELECT COUNT(*) FROM event_bookings WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")),
        'revenue'   => floatval($safe("SELECT COALESCE(SUM(package_price),0) FROM event_bookings WHERE status NOT IN ('cancelled') AND DATE_FORMAT(created_at,'%Y-%m')='$month'")),
    ];
    echo json_encode($stats);
    $conn->close();
}

/* ============================================================ COUPONS */
function getCoupons() {
    $conn = getConnection();
    $check = $conn->query("SHOW TABLES LIKE 'coupons'");
    if (!$check || $check->num_rows === 0) { echo json_encode([]); $conn->close(); return; }
    $q = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
    $rows = [];
    while ($r = $q->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    $conn->close();
}

function addCoupon() {
    $conn        = getConnection();
    $code        = strtoupper(trim($_POST['code']            ?? ''));
    $desc        = trim($_POST['description']                ?? '');
    $type        = $_POST['discount_type']                   ?? 'percentage';
    $value       = floatval($_POST['discount_value']         ?? 0);
    $minOrder    = floatval($_POST['min_order_value']        ?? 0);
    $maxDiscount = ($_POST['max_discount']  !== '' && isset($_POST['max_discount']))  ? floatval($_POST['max_discount'])  : null;
    $usageLimit  = ($_POST['usage_limit']   !== '' && isset($_POST['usage_limit']))   ? intval($_POST['usage_limit'])     : null;
    $validFrom   = ($_POST['valid_from']    !== '' && isset($_POST['valid_from']))     ? $_POST['valid_from']              : null;
    $validUntil  = ($_POST['valid_until']   !== '' && isset($_POST['valid_until']))    ? $_POST['valid_until']             : null;

    if (!$code || !$value) { echo json_encode(['error' => 'Code and discount value are required']); return; }

    $stmt = $conn->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdddiss", $code, $desc, $type, $value, $minOrder, $maxDiscount, $usageLimit, $validFrom, $validUntil);
    if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Coupon created']);
    else echo json_encode(['error' => 'Code already exists or invalid data']);
    $conn->close();
}

function toggleCoupon() {
    $conn = getConnection();
    $id   = intval($_POST['coupon_id'] ?? 0);
    $val  = intval($_POST['is_active'] ?? 0);
    $conn->query("UPDATE coupons SET is_active=$val WHERE id=$id");
    echo json_encode(['success' => true]);
    $conn->close();
}

function deleteCoupon() {
    $conn = getConnection();
    $id   = intval($_POST['coupon_id'] ?? 0);
    $conn->query("DELETE FROM coupons WHERE id=$id");
    echo json_encode(['success' => true]);
    $conn->close();
}

/* ============================================================ SITE SETTINGS */
function getSettingsAdmin() {
    $conn  = getConnection();
    $check = $conn->query("SHOW TABLES LIKE 'site_settings'");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['error' => 'Settings table not found. Please run settings_setup.sql first.']);
        $conn->close(); return;
    }
    $q    = $conn->query("SELECT * FROM site_settings ORDER BY group_name, id");
    $rows = [];
    while ($r = $q->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    $conn->close();
}

function saveSettings() {
    $conn     = getConnection();
    $settings = json_decode($_POST['settings'] ?? '{}', true);
    if (!$settings) { echo json_encode(['error' => 'No settings data received']); return; }
    $stmt  = $conn->prepare("UPDATE site_settings SET setting_val = ? WHERE setting_key = ?");
    $saved = 0;
    foreach ($settings as $key => $val) {
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        $stmt->bind_param("ss", $val, $key);
        if ($stmt->execute()) $saved++;
    }
    echo json_encode(['success' => true, 'saved' => $saved, 'message' => "$saved settings saved successfully"]);
    $conn->close();
}

/* ============================================================ REVIEWS ADMIN */
function getReviewsAdmin() {
    $conn   = getConnection();
    $check  = $conn->query("SHOW TABLES LIKE 'reviews'");
    if (!$check || $check->num_rows === 0) { echo json_encode([]); $conn->close(); return; }
    $filter = $_GET['filter'] ?? 'pending';
    $where  = $filter === 'all' ? '' : "WHERE status='" . mysqli_real_escape_string($conn,$filter) . "'";
    $q      = $conn->query("SELECT * FROM reviews $where ORDER BY created_at DESC LIMIT 200");
    $rows   = [];
    while ($r = $q->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    $conn->close();
}

function updateReview() {
    $conn       = getConnection();
    $id         = intval($_POST['review_id']   ?? 0);
    $status     = mysqli_real_escape_string($conn, $_POST['status']      ?? '');
    $reply      = mysqli_real_escape_string($conn, $_POST['admin_reply'] ?? '');
    $featured   = intval($_POST['is_featured'] ?? 0);
    $allowed    = ['pending','approved','rejected'];
    if (!in_array($status, $allowed)) { echo json_encode(['error'=>'Invalid status']); return; }
    $conn->query("UPDATE reviews SET status='$status', admin_reply='$reply', is_featured=$featured WHERE id=$id");
    echo json_encode(['success' => true]);
    $conn->close();
}

function deleteReview() {
    $conn = getConnection();
    $id   = intval($_POST['review_id'] ?? 0);
    $conn->query("DELETE FROM reviews WHERE id=$id");
    echo json_encode(['success' => true]);
    $conn->close();
}
?>
