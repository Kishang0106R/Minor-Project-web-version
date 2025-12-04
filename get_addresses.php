<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}
    header('Content-Type: application/json');

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch addresses
    $stmt = $conn->prepare("SELECT id, flat_house, building_apartment, street_road, landmark, area_locality, pincode, district, city, state, is_default FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }

    echo json_encode(['success' => true, 'addresses' => $addresses]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$stmt->close();
$conn->close();
?>
