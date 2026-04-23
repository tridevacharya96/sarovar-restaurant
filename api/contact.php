<?php
// api/contact.php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn    = getConnection();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['error' => 'Name, email and message are required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully! We will get back to you soon.']);
    } else {
        echo json_encode(['error' => 'Failed to send message']);
    }
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>