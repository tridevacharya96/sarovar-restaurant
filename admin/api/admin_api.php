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
    case 'get_settings':      getSettingsAdmin();      break;
    case 'save_settings':     saveSettings();          break;
    case 'event_bookings':        getEventBookings();         break;
    case 'update_event_booking':  updateEventBooking();       break;
    case 'event_booking_stats':   getEventBookingStats();     break;
    default:                  echo json_encode(['error'=>'Invalid action']);
}

/* ============================================================ BADGES */
function getBadges() {
    $conn = getConnection();
    $pending_orders       = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
    $pending_reservations = $conn->query("SELECT COUNT(*) c FROM reservations WHERE status='pending'")->fetch_assoc()['c'];
    $pending_events = $conn->query("SELECT COUNT(*) c FROM event_bookings WHERE status='pending'")->fetch_assoc()['c'];
    $new_messages         = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE created_at >= DATE_SUB(NOW(),INTERVAL 24 HOUR)")->fetch_assoc()['c'];
    echo json_encode(compact('pending_orders','pending_reservations','new_messages'));
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

    $stats = [
        'month_revenue'      => floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) v FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status!='cancelled'")->fetch_assoc()['v']),
        'month_orders'       => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['c']),
        'avg_order'          => floatval($conn->query("SELECT COALESCE(AVG(total_amount),0) v FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status!='cancelled'")->fetch_assoc()['v']),
        'delivered_count'    => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status='delivered'")->fetch_assoc()['c']),
        'cancelled_count'    => intval($conn->query("SELECT COUNT(*) c FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month' AND status='cancelled'")->fetch_assoc()['c']),
        'new_customers_month'=> intval($conn->query("SELECT COUNT(*) c FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['c']),
        'month_reservations' => intval($conn->query("SELECT COUNT(*) c FROM reservations WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['c']),
    ];

    $weeklyQ = $conn->query("
        SELECT DATE_FORMAT(created_at,'%a') day, COALESCE(SUM(total_amount),0) revenue
        FROM orders WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) AND status!='cancelled'
        GROUP BY DATE(created_at) ORDER BY DATE(created_at)
    ");
    $weekly=[];
    while ($r=$weeklyQ->fetch_assoc()) $weekly[]=$r;

    $peakQ = $conn->query("
        SELECT CONCAT(HOUR(created_at),':00') label, COUNT(*) count
        FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'
        GROUP BY HOUR(created_at) ORDER BY count DESC LIMIT 6
    ");
    $peak_hours=[];
    while ($r=$peakQ->fetch_assoc()) $peak_hours[]=['label'=>$r['label'].'-'.($r['label']+1).':00','count'=>$r['count']];

    $topQ = $conn->query("
        SELECT m.name, SUM(oi.quantity*oi.price) revenue
        FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id
        JOIN orders o ON oi.order_id=o.id
        WHERE DATE_FORMAT(o.created_at,'%Y-%m')='$month' AND o.status!='cancelled'
        GROUP BY m.id ORDER BY revenue DESC LIMIT 6
    ");
    $top_items=[];
    while ($r=$topQ->fetch_assoc()) $top_items[]=$r;

    echo json_encode(compact('stats','weekly','peak_hours','top_items'));
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

/* ============================================================ SITE SETTINGS */
function getSettingsAdmin() {
    $conn = getConnection();
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

    $stmt = $conn->prepare("UPDATE site_settings SET setting_val = ? WHERE setting_key = ?");
    $saved = 0;
    foreach ($settings as $key => $val) {
        $key = preg_replace('/[^a-z0-9_]/', '', $key); // sanitize key
        $stmt->bind_param("ss", $val, $key);
        if ($stmt->execute()) $saved++;
    }
    echo json_encode(['success' => true, 'saved' => $saved, 'message' => "$saved settings saved successfully"]);
    $conn->close();
}
?>
