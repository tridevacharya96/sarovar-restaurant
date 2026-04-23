<?php
// api/order.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'place':
        placeOrder();
        break;
    case 'get':
        getOrders();
        break;
    case 'status':
        getOrderStatus();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function placeOrder() {
    // Must be logged in to place an order
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to place an order']);
        return;
    }

    $conn          = getConnection();
    $name          = trim($_POST['name']           ?? '');
    $email         = trim($_POST['email']          ?? '');
    $phone         = trim($_POST['phone']          ?? '');
    $address       = trim($_POST['address']        ?? '');
    $totalAmount   = floatval($_POST['total_amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'cod');
    $items         = json_decode($_POST['items']   ?? '[]', true);
    $couponCode    = strtoupper(trim($_POST['coupon_code']    ?? ''));
    $discountAmount= floatval($_POST['discount_amount'] ?? 0);

    // Always use the logged-in user's ID — never NULL
    $userId = $_SESSION['user_id'];

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        echo json_encode(['error' => 'All fields are required']);
        return;
    }

    if (empty($items) || !is_array($items)) {
        echo json_encode(['error' => 'Your cart is empty']);
        return;
    }

    if ($totalAmount <= 0) {
        echo json_encode(['error' => 'Invalid order total']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, name, email, phone, address, total_amount, payment_method, coupon_code, discount_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssdssd", $userId, $name, $email, $phone, $address, $totalAmount, $paymentMethod, $couponCode, $discountAmount);
        $stmt->execute();
        $orderId = $conn->insert_id;

        // Increment coupon usage count if a coupon was applied
        if (!empty($couponCode)) {
            $conn->query("UPDATE coupons SET used_count = used_count + 1 WHERE code = '$couponCode'");
        }

        $itemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, menu_item_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $menuItemId = intval($item['id']       ?? 0);
            $quantity   = intval($item['quantity'] ?? 1);
            $price      = floatval($item['price']  ?? 0);

            if ($menuItemId <= 0 || $quantity <= 0 || $price <= 0) {
                throw new Exception('Invalid item data in cart');
            }

            $itemStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $price);
            $itemStmt->execute();
        }

        $conn->commit();
        echo json_encode([
            'success'  => true,
            'message'  => 'Order placed successfully! Estimated delivery: 45–60 minutes.',
            'order_id' => $orderId
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Failed to place order: ' . $e->getMessage()]);
    }
    $conn->close();
}

function getOrders() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to view orders']);
        return;
    }

    $conn   = getConnection();
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT o.*,
               GROUP_CONCAT(CONCAT(m.name, ' x', oi.quantity) ORDER BY oi.id SEPARATOR ', ') AS items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items m   ON oi.menu_item_id = m.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);
    $conn->close();
}

function getOrderStatus() {
    $conn    = getConnection();
    $orderId = intval($_GET['id'] ?? 0);

    if ($orderId <= 0) {
        echo json_encode(['error' => 'Invalid order ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, status, created_at FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Order not found']);
    }
    $conn->close();
}
?>
