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
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Create addresses table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($table_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create addresses table: ' . $conn->error]);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$user_id = $_SESSION['user_id'];
$flat_house = trim($_POST['flat_house'] ?? '');
$building_apartment = trim($_POST['building_apartment'] ?? '');
$street_road = trim($_POST['street_road'] ?? '');
$landmark = trim($_POST['landmark'] ?? '');
$area_locality = trim($_POST['area_locality'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$district = trim($_POST['district'] ?? '');
$city = trim($_POST['city'] ?? 'Delhi');
$state = trim($_POST['state'] ?? 'Delhi (NCT)');
$is_default = isset($_POST['is_default']) ? 1 : 0;

// Validate required fields
if (empty($pincode) || empty($district)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in pincode and district']);
    exit();
}

// If setting as default, unset other defaults and update users table
if ($is_default) {
    $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
    // Update users table with the new default address
    $update_user_stmt = $conn->prepare("UPDATE users SET flat_house = ?, building_apartment = ?, street_road = ?, landmark = ?, area_locality = ?, pincode = ?, district = ?, city = ?, state = ? WHERE id = ?");
    $update_user_stmt->bind_param("sssssssssi", $flat_house, $building_apartment, $street_road, $landmark, $area_locality, $pincode, $district, $city, $state, $user_id);
    $update_user_stmt->execute();
    $update_user_stmt->close();
}

// Insert address
$stmt = $conn->prepare("INSERT INTO addresses (user_id, flat_house, building_apartment, street_road, landmark, area_locality, pincode, district, city, state, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssssssi", $user_id, $flat_house, $building_apartment, $street_road, $landmark, $area_locality, $pincode, $district, $city, $state, $is_default);

if ($stmt->execute()) {
    $insert_id = $stmt->insert_id;
    // Fetch and return the inserted address
    $sel = $conn->prepare("SELECT id, flat_house, building_apartment, street_road, landmark, area_locality, pincode, district, city, state, is_default FROM addresses WHERE id = ?");
    $sel->bind_param('i', $insert_id);
    $sel->execute();
    $res = $sel->get_result();
    $address = $res->fetch_assoc();
    echo json_encode(['success' => true, 'message' => 'Address added successfully', 'address' => $address]);
    $sel->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add address', 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
