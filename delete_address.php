<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
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

// Create addresses table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flat_house VARCHAR(255),
    building_apartment VARCHAR(255),
    street_road VARCHAR(255),
    landmark VARCHAR(255),
    area_locality VARCHAR(255),
    pincode VARCHAR(10) NOT NULL,
    district VARCHAR(100) NOT NULL,
    city VARCHAR(100) DEFAULT 'Delhi',
    state VARCHAR(100) DEFAULT 'Delhi (NCT)',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!$conn->query($table_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create addresses table: ' . $conn->error]);
    exit();
}

$user_id = $_SESSION['user_id'];
$address_id = intval($_POST['address_id'] ?? 0);

// Check if address belongs to user and if it's the default
$stmt = $conn->prepare("SELECT id, is_default FROM addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Address not found']);
    exit();
}
$address = $result->fetch_assoc();
$is_default = $address['is_default'];

// Delete address
$stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $user_id);

if ($stmt->execute()) {
    // If the deleted address was default, clear address from users table
    if ($is_default) {
        $clear_user_stmt = $conn->prepare("UPDATE users SET flat_house = NULL, building_apartment = NULL, street_road = NULL, landmark = NULL, area_locality = NULL, pincode = NULL, district = NULL, city = 'Delhi', state = 'Delhi (NCT)' WHERE id = ?");
        $clear_user_stmt->bind_param("i", $user_id);
        $clear_user_stmt->execute();
        $clear_user_stmt->close();
    }
    echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete address', 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
