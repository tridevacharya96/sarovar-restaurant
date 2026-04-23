<?php
// api/coupon.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'validate': validateCoupon(); break;
    case 'remove':   echo json_encode(['success' => true]); break;
    default:         echo json_encode(['error' => 'Invalid action']);
}

function validateCoupon() {
    $conn  = getConnection();
    $code  = strtoupper(trim($_POST['code']        ?? ''));
    $order = floatval($_POST['order_amount'] ?? 0);

    if (empty($code)) {
        echo json_encode(['error' => 'Please enter a coupon code']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();

    if (!$coupon) {
        echo json_encode(['error' => 'Invalid coupon code']);
        return;
    }

    // Check validity dates
    $today = date('Y-m-d');
    if ($coupon['valid_from'] && $today < $coupon['valid_from']) {
        echo json_encode(['error' => 'This coupon is not active yet']);
        return;
    }
    if ($coupon['valid_until'] && $today > $coupon['valid_until']) {
        echo json_encode(['error' => 'This coupon has expired']);
        return;
    }

    // Check usage limit
    if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['error' => 'This coupon has reached its usage limit']);
        return;
    }

    // Check minimum order value
    if ($order < floatval($coupon['min_order_value'])) {
        echo json_encode(['error' => 'Minimum order of ₹' . number_format($coupon['min_order_value'], 0) . ' required for this coupon']);
        return;
    }

    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = $order * ($coupon['discount_value'] / 100);
        if ($coupon['max_discount'] !== null) {
            $discount = min($discount, floatval($coupon['max_discount']));
        }
    } else {
        $discount = floatval($coupon['discount_value']);
    }

    $discount = min($discount, $order); // Never discount more than order value
    $discount = round($discount, 2);

    echo json_encode([
        'success'       => true,
        'code'          => $coupon['code'],
        'description'   => $coupon['description'],
        'discount_type' => $coupon['discount_type'],
        'discount_value'=> $coupon['discount_value'],
        'discount'      => $discount,
        'message'       => $coupon['discount_type'] === 'percentage'
            ? $coupon['discount_value'] . '% off applied!'
            : '₹' . number_format($discount, 0) . ' off applied!',
    ]);
    $conn->close();
}
?>
