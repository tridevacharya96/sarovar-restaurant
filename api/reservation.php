<?php
// api/reservation.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createReservation();
        break;
    case 'get':
        getReservations();
        break;
    case 'cancel':
        cancelReservation();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function createReservation() {
    $conn = getConnection();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $date     = trim($_POST['date'] ?? '');
    $time     = trim($_POST['time'] ?? '');
    $guests   = intval($_POST['guests'] ?? 0);
    $requests = trim($_POST['special_requests'] ?? '');
    $userId   = $_SESSION['user_id'] ?? null;

    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time) || $guests < 1) {
        echo json_encode(['error' => 'All required fields must be filled']);
        return;
    }

    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        echo json_encode(['error' => 'Reservation date cannot be in the past']);
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO reservations (user_id, name, email, phone, date, time, guests, special_requests)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssss", $userId, $name, $email, $phone, $date, $time, $guests, $requests);

    if ($stmt->execute()) {
        $reservationId = $conn->insert_id;
        echo json_encode([
            'success'        => true,
            'message'        => 'Reservation created successfully! We will confirm shortly.',
            'reservation_id' => $reservationId
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create reservation']);
    }
    $conn->close();
}

function getReservations() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to view reservations']);
        return;
    }
    $conn   = getConnection();
    $userId = $_SESSION['user_id'];
    $stmt   = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY date DESC, time DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result       = $stmt->get_result();
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    echo json_encode($reservations);
    $conn->close();
}

function cancelReservation() {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Please login to cancel reservation']);
        return;
    }
    $conn          = getConnection();
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    $userId        = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reservationId, $userId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
    } else {
        echo json_encode(['error' => 'Failed to cancel reservation']);
    }
    $conn->close();
}
?>