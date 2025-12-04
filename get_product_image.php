<?php
session_start();
require_once 'check_session_timeout.php';
check_session_timeout('UserLogin.html');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management_system";

// Get product ID from query string
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header("HTTP/1.0 500 Internal Server Error");
    exit;
}

// Get image data from database
$stmt = $conn->prepare("SELECT product_image, product_image_type FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$stmt->bind_result($image_data, $image_type);
$stmt->fetch();

// If no image data, return 404
if (empty($image_data)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Check if image_data is a filename (string) or BLOB data
if (is_string($image_data) && !preg_match('/[^\x20-\x7E]/', $image_data)) {
    // Likely a filename, serve from filesystem
    $image_path = __DIR__ . '/product_images/' . $image_data;
    if (file_exists($image_path)) {
        // Determine MIME type from file extension
        $ext = strtolower(pathinfo($image_data, PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $content_type = $mime_types[$ext] ?? 'image/jpeg';
        header("Content-Type: " . $content_type);
        readfile($image_path);
    } else {
        header("HTTP/1.0 404 Not Found");
    }
} else {
    // BLOB data
    header("Content-Type: " . ($image_type ?: 'image/jpeg'));
    echo $image_data;
}

$stmt->close();
$conn->close();
?>