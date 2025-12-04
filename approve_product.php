<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Teacher not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id'] ?? 0);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Check if product belongs to teacher's team
// Check if product belongs to teacher's team (use product_id column)
$stmt = $conn->prepare("SELECT p.product_id FROM products p JOIN teams t ON p.team_id = t.id WHERE p.product_id = ? AND t.teacher_id = ?");
$stmt->bind_param("ii", $product_id, $teacher_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Update product status to approved
$stmt = $conn->prepare("UPDATE products SET status = 'approved' WHERE product_id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product approved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to approve product']);
}

$stmt->close();
$conn->close();
?>
