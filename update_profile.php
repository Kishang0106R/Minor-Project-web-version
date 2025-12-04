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

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$gender = trim($_POST['gender'] ?? '');

// Validation
$errors = [];

// Name validation (no restrictions)
if (empty($name)) {
    $errors[] = "Name is required";
}

// Phone validation (optional, but if provided must be exactly 10 digits)
if (!empty($phone)) {
    if (!preg_match('/^(\+\d{1,3}[- ]?)?\d{10}$/', $phone)) {
        $errors[] = "Please enter a valid 10 digit phone number. Country code is optional.";
    }
}

// Gender validation
$allowed_genders = ['male', 'female', 'other', ''];
if (!in_array(strtolower($gender), $allowed_genders)) {
    $errors[] = "Invalid gender value";
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit();
}

// Clean phone number
$phone = preg_replace('/[^\d+]/', '', $phone);

// Update user data
$stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, gender = ? WHERE id = ?");
$stmt->bind_param("sssi", $name, $phone, $gender, $user_id);

if ($stmt->execute()) {
    // Update session data
    $_SESSION['user_name'] = $name;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_gender'] = $gender;

    // Return the validated input data directly
    $userData = [
        'name' => $name,
        'phone' => $phone,
        'gender' => $gender,
        'email' => $_SESSION['user_email'] ?? '' // Use cached email from session
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'userData' => $userData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile',
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
