<?php
// api/event_booking.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'packages':     getPackages();    break;
    case 'book':         bookEvent();      break;
    case 'my_bookings':  myBookings();     break;
    case 'cancel':       cancelBooking();  break;
    default: echo json_encode(['error' => 'Invalid action']);
}

/* ===== GET PACKAGES ===== */
function getPackages() {
    $conn = getConnection();

    // Check table exists first
    $check = $conn->query("SHOW TABLES LIKE 'event_packages'");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['error' => 'table_missing']);
        $conn->close();
        return;
    }

    $result   = $conn->query("SELECT * FROM event_packages WHERE is_active = 1 ORDER BY sort_order");
    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $row['features_list'] = explode('|', $row['features']);
        $packages[] = $row;
    }

    if (empty($packages)) {
        echo json_encode(['error' => 'no_packages']);
        $conn->close();
        return;
    }

    echo json_encode($packages);
    $conn->close();
}

/* ===== BOOK EVENT ===== */
function bookEvent() {
    $conn = getConnection();

    $name        = trim($_POST['name']             ?? '');
    $email       = trim($_POST['email']            ?? '');
    $phone       = trim($_POST['phone']            ?? '');
    $eventType   = trim($_POST['event_type']       ?? '');
    $eventDate   = trim($_POST['event_date']       ?? '');
    $timeSlot    = trim($_POST['time_slot']        ?? '');
    $guestCount  = trim($_POST['guest_count']      ?? '');
    $packageId   = intval($_POST['package_id']     ?? 0);
    $requests    = trim($_POST['special_requests'] ?? '');
    $userId      = $_SESSION['user_id'] ?? null;

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($eventType) || empty($eventDate) || empty($timeSlot)) {
        echo json_encode(['error' => 'All required fields must be filled']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address']);
        return;
    }

    if (strtotime($eventDate) < strtotime(date('Y-m-d'))) {
        echo json_encode(['error' => 'Event date cannot be in the past']);
        return;
    }

    // Check date availability
    $dateCheck = $conn->prepare("SELECT id FROM event_bookings WHERE event_date = ? AND status NOT IN ('cancelled')");
    $dateCheck->bind_param("s", $eventDate);
    $dateCheck->execute();
    if ($dateCheck->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Sorry, this date is already booked. Please choose another date.']);
        return;
    }

    // Get package details
    $pkgName  = null;
    $pkgPrice = null;
    $advance  = null;
    if ($packageId > 0) {
        $pkgStmt = $conn->prepare("SELECT name, price FROM event_packages WHERE id = ? AND is_active = 1");
        $pkgStmt->bind_param("i", $packageId);
        $pkgStmt->execute();
        $pkg = $pkgStmt->get_result()->fetch_assoc();
        if ($pkg) {
            $pkgName  = $pkg['name'];
            $pkgPrice = $pkg['price'];
            $advance  = round($pkgPrice * 0.25, 2);
        }
    }

    // Generate booking reference
    $ref = 'EVT' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);

    $stmt = $conn->prepare("
        INSERT INTO event_bookings
            (user_id, booking_ref, name, email, phone, event_type, event_date, time_slot,
             guest_count, package_id, package_name, package_price, advance_amount, special_requests)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issssssssissdd",
        $userId, $ref, $name, $email, $phone, $eventType,
        $eventDate, $timeSlot, $guestCount,
        $packageId, $pkgName, $pkgPrice, $advance, $requests
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success'        => true,
            'message'        => 'Booking enquiry submitted! Our team will contact you within 24 hours.',
            'booking_ref'    => $ref,
            'advance_amount' => $advance,
            'package_name'   => $pkgName,
        ]);
    } else {
        echo json_encode(['error' => 'Failed to submit booking. Please try again.']);
    }
    $conn->close();
}

/* ===== MY BOOKINGS ===== */
function myBookings() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to view your event bookings']);
        return;
    }
    $conn   = getConnection();
    $userId = $_SESSION['user_id'];
    $stmt   = $conn->prepare("
        SELECT eb.*, ep.features
        FROM event_bookings eb
        LEFT JOIN event_packages ep ON eb.package_id = ep.id
        WHERE eb.user_id = ?
        ORDER BY eb.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result   = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    echo json_encode($bookings);
    $conn->close();
}

/* ===== CANCEL BOOKING ===== */
function cancelBooking() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login']);
        return;
    }
    $conn      = getConnection();
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $userId    = $_SESSION['user_id'];

    // Only allow cancel if pending
    $stmt = $conn->prepare("
        UPDATE event_bookings SET status = 'cancelled'
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['error' => 'Cannot cancel this booking']);
    }
    $conn->close();
}
?>
