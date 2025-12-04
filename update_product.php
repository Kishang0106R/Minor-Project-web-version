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
$product_id = intval($_POST['product_id'] ?? 0);
$product_name = trim($_POST['product_name'] ?? '');
$product_description = trim($_POST['product_description'] ?? '');
$quantity = intval($_POST['quantity'] ?? 0);
$product_price = floatval($_POST['product_price'] ?? 0);

// Handle image upload if provided
$new_image = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $image = $_FILES['product_image'];
    $image_extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
    // Validate image type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($image_extension, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed types: JPG, JPEG, PNG, GIF']);
        exit();
    }

    // Generate unique filename
    $new_image = uniqid() . '.' . $image_extension;
    if (!move_uploaded_file($image['tmp_name'], 'product_images/' . $new_image)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit();
    }
}

// Validate input
if (empty($product_name) || empty($product_description) || $quantity <= 0 || $product_price <= 0) {
    // Clean up uploaded image if validation fails
    if ($new_image && file_exists('product_images/' . $new_image)) {
        unlink('product_images/' . $new_image);
    }
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

// Check if product belongs to user's team and get current image and status
$stmt = $conn->prepare("SELECT p.id, p.product_image, p.status FROM products p JOIN team_members tm ON p.team_id = tm.team_id WHERE p.id = ? AND tm.user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Clean up uploaded image if access check fails
    if ($new_image && file_exists('product_images/' . $new_image)) {
        unlink('product_images/' . $new_image);
    }
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit();
}

$current_product = $result->fetch_assoc();

// Check if product is approved or rejected
if ($current_product['status'] !== 'pending') {
    // Clean up uploaded image
    if ($new_image && file_exists('product_images/' . $new_image)) {
        unlink('product_images/' . $new_image);
    }
    echo json_encode(['success' => false, 'message' => 'Cannot edit ' . $current_product['status'] . ' products']);
    exit();
}

// Update product
if ($new_image) {
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_description = ?, quantity = ?, product_price = ?, product_image = ? WHERE id = ?");
    $stmt->bind_param("ssidsi", $product_name, $product_description, $quantity, $product_price, $new_image, $product_id);
} else {
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_description = ?, quantity = ?, product_price = ? WHERE id = ?");
    $stmt->bind_param("ssidi", $product_name, $product_description, $quantity, $product_price, $product_id);
}

if ($stmt->execute()) {
    // Clean up old image if new one was uploaded
    if ($new_image && !empty($current_product['product_image'])) {
        $old_image_path = 'product_images/' . $current_product['product_image'];
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
    }
    
    // Return updated product data
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'product' => [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'product_description' => $product_description,
            'quantity' => $quantity,
            'product_price' => $product_price,
            'product_image' => $new_image ?? $current_product['product_image']
        ]
    ]);
} else {
    // Clean up uploaded image if update fails
    if ($new_image && file_exists('product_images/' . $new_image)) {
        unlink('product_images/' . $new_image);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to update product']);
}

$stmt->close();
$conn->close();
?>
