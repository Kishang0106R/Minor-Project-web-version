<?php
session_start();

// Check if user is logged in (student)
if (!isset($_SESSION['user_id'])) {
    header("Location: UserLogin.html");
    exit();
}

// Get team_id from URL
$team_id = intval($_GET['team_id'] ?? 0);
if ($team_id <= 0) {
    header("Location: Profile.php"); // Redirect to user profile or appropriate page
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
    die("Connection failed: " . $conn->connect_error);
}

// Verify the user is a member of this team
$stmt_verify = $conn->prepare("SELECT t.team_name FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE t.id = ? AND tm.user_id = ?");
$stmt_verify->bind_param("ii", $team_id, $_SESSION['user_id']);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();

if ($result_verify->num_rows == 0) {
    header("Location: Profile.php"); // Redirect to user profile or appropriate page
    exit();
}

$team = $result_verify->fetch_assoc();
$team_name = $team['team_name'];
$stmt_verify->close();

$message = "";
$message_type = "";

// Handle product upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST['product_name']);
    $product_description = trim($_POST['product_description']);
    $quantity = intval($_POST['quantity']);
    $product_price = floatval($_POST['product_price']);

    // Validate input
    if (empty($product_name) || empty($product_description) || $quantity <= 0 || $product_price <= 0) {
        $message = "Please fill in all required fields with valid values.";
        $message_type = "error";
    } elseif (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] != 0) {
        $message = "Please select a valid product image.";
        $message_type = "error";
    } else {
        // Handle file upload - store image as BLOB in database
        $image_file = $_FILES['product_image'];

        // Check if file was uploaded successfully
        if ($image_file['error'] !== UPLOAD_ERR_OK) {
            $message = "File upload error.";
            $message_type = "error";
        } else {
            // Read and store image on filesystem
            $image_data = file_get_contents($image_file['tmp_name']);

            // Determine MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $image_type = finfo_file($finfo, $image_file['tmp_name']);
            finfo_close($finfo);

            $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if (!array_key_exists($image_type, $allowed_types)) {
                $message = "Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.";
                $message_type = "error";
            } else {
                $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'product_images';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = $allowed_types[$image_type];
                $filename = uniqid('prod_', true) . '.' . $ext;
                $dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file($image_file['tmp_name'], $dest)) {
                    $message = "Failed to move uploaded file.";
                    $message_type = "error";
                } else {
                    // Insert product with filename
                    $stmt = $conn->prepare("INSERT INTO products (product_name, product_description, quantity, product_price, product_image, team_id, uploader_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->bind_param("ssidsii", $product_name, $product_description, $quantity, $product_price, $filename, $team_id, $_SESSION['user_id']);

                    if ($stmt->execute()) {
                        $message = "Product uploaded successfully!";
                        $message_type = "success";
                    } else {
                        // Clean up file
                        if (file_exists($dest)) unlink($dest);
                        $message = "Error uploading product: " . $conn->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Product - <?php echo htmlspecialchars($team_name); ?></title>
    <link rel="stylesheet" href="teacher_admin.css" />
    <style>
        .upload-form {
            max-width: 600px;
            margin: 0 auto;
        }
        .upload-form input, .upload-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 15px;
        }
        .upload-form textarea {
            height: 100px;
            resize: vertical;
        }
        .upload-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .file-input {
            border: none;
            padding: 0;
        }
        .file-input input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($_SESSION['school_name']); ?></h1>
        <p>Upload Product for Team: <?php echo htmlspecialchars($team_name); ?></p>
        <div class="logout">
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>" style="display: block;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Upload New Product</h2>
            <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
                <label for="product_name">Product Name *</label>
                <input type="text" id="product_name" name="product_name" required />

                <label for="product_description">Product Description *</label>
                <textarea id="product_description" name="product_description" required></textarea>

                <label for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" min="1" required />

                <label for="product_price">Price (₹) *</label>
                <input type="number" id="product_price" name="product_price" min="0.01" step="0.01" required />

                <label for="product_image">Product Image *</label>
                <div class="file-input">
                    <input type="file" id="product_image" name="product_image" accept="image/*" required />
                </div>

                <button type="submit" class="btn">Upload Product</button>
                <a href="TeacherAdmin.php" class="btn" style="background: #6c757d; margin-left: 10px;">Back to Teams</a>
            </form>
        </div>
    </div>
</body>
</html>
