<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

// Allow access for shop view
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID not provided']);
    exit();
}

$product_id = $_GET['product_id'];

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_description, p.quantity, p.product_price, p.product_image, p.status, p.upload_date, u.name as uploader_name, u.email as uploader_email, t.team_name FROM products p JOIN users u ON p.uploader_id = u.id LEFT JOIN teams t ON p.team_id = t.team_id WHERE p.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Product not found']);
    exit();
}

$product = $result->fetch_assoc();

echo json_encode(['success' => true, 'product' => $product]);

$stmt->close();
$conn->close();
?>
