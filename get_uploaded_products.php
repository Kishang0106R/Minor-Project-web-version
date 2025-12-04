<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

// For shop view, allow anonymous access
if (!isset($_GET['shop'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }
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
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

if (isset($_GET['shop'])) {
    // Get all products for shop view
    $stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_description, p.quantity, p.product_price, p.product_image, p.status, p.upload_date, u.name as uploader_name FROM products p JOIN users u ON p.uploader_id = u.id ORDER BY p.upload_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $user_id = $_SESSION['user_id'];
    // Get user's team
    $stmt = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'User not in a team']);
        exit();
    }

    $row = $result->fetch_assoc();
    $team_id = $row['team_id'];

    // Get products uploaded by team members
    $stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_description, p.quantity, p.product_price, p.product_image, p.status, p.upload_date, u.name as uploader_name FROM products p JOIN users u ON p.uploader_id = u.id WHERE p.team_id = ? ORDER BY p.upload_date DESC");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode(['success' => true, 'products' => $products]);

$stmt->close();
$conn->close();
?>
