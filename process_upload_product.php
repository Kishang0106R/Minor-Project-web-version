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

// No folder creation needed for BLOB storage

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
$product_name = trim($_POST['product_name'] ?? '');
$product_description = trim($_POST['product_description'] ?? '');
$quantity = intval($_POST['quantity'] ?? 0);
$product_price = floatval($_POST['product_price'] ?? 0);
$team_id = intval($_POST['team_id'] ?? 0);

// Validate input
if (empty($product_name) || empty($product_description) || $quantity <= 0 || $product_price <= 0 || $team_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

// Verify user is member of the team
$stmt_verify = $conn->prepare("SELECT id FROM team_members WHERE team_id = ? AND user_id = ?");
$stmt_verify->bind_param("ii", $team_id, $user_id);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You are not a member of this team']);
    exit();
}
$stmt_verify->close();

$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'product_images';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create image directory']);
        exit();
    }
}

// Handle file upload - store image in filesystem
if (!isset($_FILES['product_image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit();
}

$image_file = $_FILES['product_image'];
if ($image_file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit();
}

// Determine MIME type and validate
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$image_type = finfo_file($finfo, $image_file['tmp_name']);
finfo_close($finfo);
$allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
if (!array_key_exists($image_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    exit();
}

// Generate unique filename
$ext = $allowed_types[$image_type];
$filename = uniqid('prod_', true) . '.' . $ext;
$dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($image_file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit();
}

// Insert product record with filename
$stmt = $conn->prepare("INSERT INTO products (product_name, product_description, quantity, product_price, product_image, team_id, uploader_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("ssidsii", $product_name, $product_description, $quantity, $product_price, $filename, $team_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product uploaded successfully', 'filename' => $filename]);
} else {
    // Cleanup file on failure
    if (file_exists($dest)) unlink($dest);
    echo json_encode(['success' => false, 'message' => 'Failed to upload product: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
