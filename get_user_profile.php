<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
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
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, phone, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $gender);
$stmt->fetch();

$userData = [
    'user_id' => $user_id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone ?? '',
    'gender' => $gender ?? '',
    'profile_photo' => 'images/profilePhoto.png'
];

$stmt->close();
$conn->close();

echo json_encode($userData);
?>
