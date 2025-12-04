<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

# Get orders
$stmt = $conn->prepare("SELECT o.id, o.order_id, o.order_date, o.quantity, o.status,
    p.product_id, p.product_name, p.product_price, (o.quantity * p.product_price) as total_amount,
    o.rating, o.review, o.can_rate, t.team_name
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN teams t ON o.team_id = t.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => $row['id'],
        'order_id' => $row['order_id'],
        'order_date' => $row['order_date'],
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['product_price'],
        'total_amount' => $row['total_amount'],
        'status' => $row['status'],
        'rating' => $row['rating'],
        'review' => $row['review'],
        'can_rate' => $row['can_rate'],
        'team_name' => $row['team_name']
    ];
}

echo json_encode([
    'success' => true,
    'orders' => $orders
]);

$stmt->close();
$conn->close();
?>
