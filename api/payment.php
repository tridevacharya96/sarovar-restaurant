<?php
// api/payment.php
// Payment Gateway Abstraction Layer
// ─────────────────────────────────
// This file acts as the single point of integration for any payment gateway.
// To add a new gateway later, just add a new case in the switch blocks below.
// The frontend never talks to a gateway directly — always goes through this file.

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/settings.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'config':          getPaymentConfig();     break;  // Frontend fetches this on load
    case 'create_order':    createPaymentOrder();   break;  // Step 1: create gateway order
    case 'verify':          verifyPayment();        break;  // Step 2: verify after payment
    case 'cod':             processCOD();           break;  // Cash on Delivery flow
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/* ─────────────────────────────────────────────────────────────
   GET PAYMENT CONFIG
   Returns safe public config to the frontend (no secrets)
──────────────────────────────────────────────────────────── */
function getPaymentConfig() {
    $s = getSettings();
    echo json_encode([
        'gateway'          => $s['payment_gateway']        ?? 'none',
        'cod_enabled'      => ($s['payment_cod_enabled']   ?? '1') === '1',
        'online_enabled'   => ($s['payment_online_enabled'] ?? '0') === '1',
        'currency'         => $s['payment_currency']       ?? 'INR',
        'company_name'     => $s['payment_company_name']   ?? 'The Sarovar Court',
        'company_logo'     => $s['payment_company_logo']   ?? '',
        'company_color'    => $s['payment_company_color']  ?? '#D85A30',
        // Public key only — secret NEVER sent to frontend
        'razorpay_key_id'  => ($s['payment_gateway'] === 'razorpay')
                                ? ($s['razorpay_key_id'] ?? '') : '',
        'mode'             => $s['razorpay_mode'] ?? 'test',
    ]);
}

/* ─────────────────────────────────────────────────────────────
   CREATE PAYMENT ORDER
   Step 1: Create an order on the gateway before showing popup
──────────────────────────────────────────────────────────── */
function createPaymentOrder() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to place an order']);
        return;
    }

    $s       = getSettings();
    $gateway = $s['payment_gateway'] ?? 'none';
    $amount  = floatval($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid order amount']);
        return;
    }

    switch ($gateway) {
        case 'razorpay':
            createRazorpayOrder($s, $amount);
            break;

        // ── Add future gateways here ──────────────────────────
        // case 'cashfree':
        //     createCashfreeOrder($s, $amount);
        //     break;
        // case 'payu':
        //     createPayUOrder($s, $amount);
        //     break;
        // ─────────────────────────────────────────────────────

        default:
            echo json_encode(['error' => 'No payment gateway configured. Please contact the restaurant.']);
    }
}

/* ─────────────────────────────────────────────────────────────
   VERIFY PAYMENT
   Step 2: Verify the signature after customer pays
──────────────────────────────────────────────────────────── */
function verifyPayment() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    $s       = getSettings();
    $gateway = $s['payment_gateway'] ?? 'none';

    switch ($gateway) {
        case 'razorpay':
            verifyRazorpayPayment($s);
            break;

        // case 'cashfree':
        //     verifyCashfreePayment($s);
        //     break;

        default:
            echo json_encode(['error' => 'No gateway configured']);
    }
}

/* ─────────────────────────────────────────────────────────────
   COD FLOW
   No gateway needed — just save the order directly
──────────────────────────────────────────────────────────── */
function processCOD() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to place an order']);
        return;
    }

    $s = getSettings();
    if (($s['payment_cod_enabled'] ?? '1') !== '1') {
        echo json_encode(['error' => 'Cash on Delivery is not available']);
        return;
    }

    // Delegate to order.php saveOrder helper
    $result = saveOrderToDB('cod', 'pending', null, 'cod');
    echo json_encode($result);
}

/* ═════════════════════════════════════════════════════════════
   RAZORPAY INTEGRATION
   ─────────────────────────────────────────────────────────────
   To activate:
   1. Run payment_setup.sql
   2. In Admin → Site Settings → Payment:
      - Set payment_gateway = razorpay
      - Set payment_online_enabled = 1
      - Enter your Key ID and Key Secret
      - Set razorpay_mode = live (or test)
═════════════════════════════════════════════════════════════ */
function createRazorpayOrder($s, $amount) {
    $keyId     = $s['razorpay_key_id']     ?? '';
    $keySecret = $s['razorpay_key_secret'] ?? '';

    if (empty($keyId) || empty($keySecret)) {
        echo json_encode(['error' => 'Razorpay credentials not configured. Please add them in Admin → Site Settings.']);
        return;
    }

    // Convert to paise (Razorpay uses smallest currency unit)
    $amountPaise = intval($amount * 100);
    $currency    = $s['payment_currency'] ?? 'INR';
    $receiptId   = 'SAROVAR_' . time() . '_' . rand(1000, 9999);

    $payload = json_encode([
        'amount'   => $amountPaise,
        'currency' => $currency,
        'receipt'  => $receiptId,
        'notes'    => ['restaurant' => 'The Sarovar Court'],
    ]);

    // Call Razorpay Orders API
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_USERPWD        => "$keyId:$keySecret",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        echo json_encode(['error' => 'Could not connect to Razorpay. Check your internet connection.']);
        return;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data['id'])) {
        $msg = $data['error']['description'] ?? 'Razorpay order creation failed';
        echo json_encode(['error' => $msg]);
        return;
    }

    echo json_encode([
        'success'    => true,
        'gateway'    => 'razorpay',
        'order_id'   => $data['id'],         // razorpay_order_id
        'amount'     => $amountPaise,
        'currency'   => $currency,
        'key_id'     => $keyId,              // Public key — safe to send
        'receipt'    => $receiptId,
    ]);
}

function verifyRazorpayPayment($s) {
    $keySecret       = $s['razorpay_key_secret']   ?? '';
    $orderId         = $_POST['razorpay_order_id']  ?? '';
    $paymentId       = $_POST['razorpay_payment_id'] ?? '';
    $signature       = $_POST['razorpay_signature'] ?? '';

    if (empty($keySecret) || empty($orderId) || empty($paymentId) || empty($signature)) {
        echo json_encode(['error' => 'Missing payment verification data']);
        return;
    }

    // Verify HMAC-SHA256 signature
    $expectedSig = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);

    if (!hash_equals($expectedSig, $signature)) {
        // Log failed verification attempt
        error_log("[Sarovar Payment] SIGNATURE MISMATCH — order: $orderId, payment: $paymentId");
        echo json_encode(['error' => 'Payment verification failed. Please contact the restaurant.']);
        return;
    }

    // Signature valid — save the order
    $result = saveOrderToDB('online', 'paid', $paymentId, 'razorpay');

    if ($result['success']) {
        // Update payment details on the order
        $conn = getConnection();
        $oid  = intval($result['order_id']);
        $pid  = mysqli_real_escape_string($conn, $paymentId);
        $gw   = 'razorpay';
        $conn->query("UPDATE orders SET payment_id='$pid', payment_gateway='$gw', payment_status='paid' WHERE id=$oid");
        $conn->close();
    }

    echo json_encode($result);
}

/* ─────────────────────────────────────────────────────────────
   SHARED: Save Order to DB
   Used by all payment flows after successful verification
──────────────────────────────────────────────────────────── */
function saveOrderToDB($paymentMethod, $paymentStatus, $paymentId, $gateway) {
    $conn          = getConnection();
    $name          = trim($_POST['name']             ?? '');
    $email         = trim($_POST['email']            ?? '');
    $phone         = trim($_POST['phone']            ?? '');
    $address       = trim($_POST['address']          ?? '');
    $totalAmount   = floatval($_POST['total_amount'] ?? 0);
    $items         = json_decode($_POST['items']     ?? '[]', true);
    $couponCode    = strtoupper(trim($_POST['coupon_code']    ?? ''));
    $discountAmount= floatval($_POST['discount_amount'] ?? 0);
    $userId        = $_SESSION['user_id'];

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        return ['error' => 'All fields are required'];
    }
    if (empty($items) || !is_array($items)) {
        return ['error' => 'Your cart is empty'];
    }
    if ($totalAmount <= 0) {
        return ['error' => 'Invalid order total'];
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO orders
                (user_id, name, email, phone, address, total_amount, payment_method,
                 coupon_code, discount_amount, payment_status, payment_id, payment_gateway)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssdssdss s",
            $userId, $name, $email, $phone, $address,
            $totalAmount, $paymentMethod,
            $couponCode, $discountAmount,
            $paymentStatus, $paymentId, $gateway
        );
        // Fix bind string
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO orders
                (user_id, name, email, phone, address, total_amount, payment_method,
                 coupon_code, discount_amount, payment_status, payment_id, payment_gateway)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssdssdss",
            $userId, $name, $email, $phone, $address,
            $totalAmount, $paymentMethod,
            $couponCode, $discountAmount,
            $paymentStatus, $paymentId, $gateway
        );
        $stmt->execute();
        $orderId = $conn->insert_id;

        // Save order items
        $itemStmt = $conn->prepare(
            "INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $menuItemId = intval($item['id']       ?? 0);
            $quantity   = intval($item['quantity'] ?? 1);
            $price      = floatval($item['price']  ?? 0);
            if ($menuItemId <= 0 || $quantity <= 0) continue;
            $itemStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $price);
            $itemStmt->execute();
        }

        // Increment coupon usage
        if (!empty($couponCode)) {
            $conn->query("UPDATE coupons SET used_count = used_count + 1 WHERE code = '" .
                         mysqli_real_escape_string($conn, $couponCode) . "'");
        }

        $conn->commit();
        return ['success' => true, 'order_id' => $orderId, 'message' => 'Order placed successfully!'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['error' => 'Failed to save order: ' . $e->getMessage()];
    } finally {
        $conn->close();
    }
}
?>
