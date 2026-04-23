<?php
// api/menu.php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'all':
        getAllMenu();
        break;
    case 'category':
        getByCategory();
        break;
    case 'featured':
        getFeatured();
        break;
    case 'categories':
        getCategories();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getAllMenu() {
    $conn = getConnection();
    $result = $conn->query("
        SELECT m.*, c.name as category_name 
        FROM menu_items m 
        JOIN menu_categories c ON m.category_id = c.id 
        WHERE m.is_available = 1 
        ORDER BY c.id, m.name
    ");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    $conn->close();
}

function getByCategory() {
    $conn = getConnection();
    $categoryId = intval($_GET['id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT m.*, c.name as category_name 
        FROM menu_items m 
        JOIN menu_categories c ON m.category_id = c.id 
        WHERE m.category_id = ? AND m.is_available = 1
    ");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    $conn->close();
}

function getFeatured() {
    $conn = getConnection();
    $result = $conn->query("
        SELECT m.*, c.name as category_name 
        FROM menu_items m 
        JOIN menu_categories c ON m.category_id = c.id 
        WHERE m.is_featured = 1 AND m.is_available = 1 
        LIMIT 8
    ");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    $conn->close();
}

function getCategories() {
    $conn = getConnection();
    $result = $conn->query("SELECT * FROM menu_categories ORDER BY id");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode($categories);
    $conn->close();
}
?>