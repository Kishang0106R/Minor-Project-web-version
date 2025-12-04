<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

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

$user_id = $_SESSION['user_id'];

// Handle both form data and JSON input
$input = file_get_contents('php://input');
$json_data = json_decode($input, true);

if ($json_data !== null) {
    // JSON input
    $product_id = intval($json_data['product_id'] ?? 0);
} else {
    // Form data
    $product_id = intval($_POST['product_id'] ?? 0);
}

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

// Check if product belongs to user's team and get status
$stmt = $conn->prepare("SELECT p.id, p.product_image, p.status FROM products p JOIN team_members tm ON p.team_id = tm.team_id WHERE p.id = ? AND tm.user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit();
}

$row = $result->fetch_assoc();

// Check if product is approved or rejected
if ($row['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete ' . $row['status'] . ' products']);
    exit();
}
$image_path = "product_images/" . $row['product_image'];

// Delete product
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    // Delete image file if it exists
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}

$stmt->close();
$conn->close();
?>
