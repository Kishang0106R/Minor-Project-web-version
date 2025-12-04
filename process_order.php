<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

header('Content-Type: application/json');

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
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);

// Validate input
if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit();
}

// Check if user has completed personal information (name is required)
$stmt_check_profile = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt_check_profile->bind_param("i", $user_id);
$stmt_check_profile->execute();
$result_profile = $stmt_check_profile->get_result();
$user_profile = $result_profile->fetch_assoc();
$stmt_check_profile->close();

if (empty($user_profile['name'])) {
    echo json_encode(['success' => false, 'message' => 'Please complete your personal information (name is required) before placing an order. Go to Profile > Personal Information.']);
    exit();
}

// Check if user has at least one address
$stmt_check_address = $conn->prepare("SELECT COUNT(*) as address_count FROM addresses WHERE user_id = ?");
$stmt_check_address->bind_param("i", $user_id);
$stmt_check_address->execute();
$result_address = $stmt_check_address->get_result();
$address_data = $result_address->fetch_assoc();
$stmt_check_address->close();

if ($address_data['address_count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Please add at least one address before placing an order. Go to Profile > Manage Addresses.']);
    exit();
}

$stmt = $conn->prepare("SELECT quantity, team_id FROM products WHERE product_id = ? AND status IN ('active','approved')");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found or not available']);
    exit();
}

$product = $result->fetch_assoc();
if ($product['quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient quantity available']);
    exit();
}

$team_id = $product['team_id'];

// Generate unique order_id
$order_id = rand(100000, 999999);

// Insert order
$stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, product_id, team_id, quantity) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiii", $order_id, $user_id, $product_id, $team_id, $quantity);

if ($stmt->execute()) {
    // Update product quantity
    $new_quantity = $product['quantity'] - $quantity;
    $update_stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
    $update_stmt->bind_param("ii", $new_quantity, $product_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to place order']);
}

$stmt->close();
$conn->close();
?>
