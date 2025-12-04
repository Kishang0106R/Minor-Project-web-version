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
$order_id = intval($_POST['order_id'] ?? 0);
$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

// Validate input
if ($order_id <= 0 || $product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Check if order belongs to user and can be rated
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND product_id = ? AND can_rate = 1");
$stmt->bind_param("iii", $order_id, $user_id, $product_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or cannot be rated']);
    exit();
}

// Submit rating
$stmt = $conn->prepare("UPDATE orders SET rating = ?, review = ?, rating_date = NOW(), can_rate = 0 WHERE id = ?");
$stmt->bind_param("isi", $rating, $review, $order_id);

if ($stmt->execute()) {
    // Calculate points: Points = (Price × 300) + (Rating × 5,000)
    $stmt_price = $conn->prepare("SELECT p.product_price FROM products p JOIN orders o ON p.product_id = o.product_id WHERE o.id = ?");
    $stmt_price->bind_param("i", $order_id);
    $stmt_price->execute();
    $result_price = $stmt_price->get_result();

    if ($result_price->num_rows > 0) {
        $row = $result_price->fetch_assoc();
        $price = $row['product_price'];
        $points = ($price * 300) + ($rating * 5000);

        // Add points to user's total points
        $stmt_points = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt_points->bind_param("di", $points, $user_id);
        $stmt_points->execute();
        $stmt_points->close();
    }
    $stmt_price->close();

    echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
}

$stmt->close();
$conn->close();
?>
